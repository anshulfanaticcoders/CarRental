<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FavricaService
{
    private string $baseUrl;
    private string $keyHack;
    private string $username;
    private string $password;
    private string $userAgent;
    private bool $verifySsl;
    private int $timeout;
    private int $connectTimeout;

    private int $maxRetries = 3;
    private int $circuitBreakerThreshold = 5;
    private int $circuitBreakerTimeout = 60;
    private int $failures = 0;
    private ?int $lastFailureTime = null;
    private bool $circuitOpen = false;

    public function __construct()
    {
        $config = config('services.favrica');

        if (empty($config['base_url']) || empty($config['key_hack'])) {
            throw new \Exception('Favrica credentials are not configured correctly.');
        }

        $this->baseUrl = rtrim((string) $config['base_url'], '/');
        $this->keyHack = (string) $config['key_hack'];
        $this->username = (string) ($config['username'] ?? '');
        $this->password = (string) ($config['password'] ?? '');
        $this->userAgent = (string) ($config['user_agent'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        $this->verifySsl = (bool) ($config['verify_ssl'] ?? false);
        $this->timeout = (int) ($config['timeout'] ?? 30);
        $this->connectTimeout = (int) ($config['connect_timeout'] ?? 10);
    }

    public function getLocations(): array
    {
        $response = $this->get('JsonLocations.aspx', [
            'Key_Hack' => $this->keyHack,
        ]);

        return is_array($response) ? $response : [];
    }

    public function getGroups(): array
    {
        $response = $this->get('JsonGroup.aspx', [
            'Key_Hack' => $this->keyHack,
        ]);

        return is_array($response) ? $response : [];
    }

    public function searchRez(
        string $pickupId,
        string $dropoffId,
        string $pickupDate,
        string $pickupTime,
        string $dropoffDate,
        string $dropoffTime,
        string $currency
    ): array {
        if ($this->username === '' || $this->password === '') {
            Log::warning('Favrica search attempted without username/password.');
            return [];
        }

        $pickupParts = $this->splitDateTime($pickupDate, $pickupTime);
        $dropoffParts = $this->splitDateTime($dropoffDate, $dropoffTime);

        $params = [
            'Key_Hack' => $this->keyHack,
            'User_Name' => $this->username,
            'User_Pass' => $this->password,
            'Pickup_ID' => $pickupId,
            'Drop_Off_ID' => $dropoffId,
            'Pickup_Day' => $pickupParts['day'],
            'Pickup_Month' => $pickupParts['month'],
            'Pickup_Year' => $pickupParts['year'],
            'Pickup_Hour' => $pickupParts['hour'],
            'Pickup_Min' => $pickupParts['minute'],
            'Drop_Off_Day' => $dropoffParts['day'],
            'Drop_Off_Month' => $dropoffParts['month'],
            'Drop_Off_Year' => $dropoffParts['year'],
            'Drop_Off_Hour' => $dropoffParts['hour'],
            'Drop_Off_Min' => $dropoffParts['minute'],
            'Currency' => $currency,
        ];

        $response = $this->get('JsonRez.aspx', $params);
        if (!is_array($response) || empty($response)) {
            return [];
        }

        $first = $response[0] ?? null;
        if (is_array($first) && isset($first['success']) && strtolower((string) $first['success']) === 'false') {
            Log::warning('Favrica search returned error', [
                'error' => $first['error'] ?? 'unknown',
            ]);
            return [];
        }

        return $response;
    }

    public function createReservation(array $payload): ?array
    {
        if ($this->username === '' || $this->password === '') {
            Log::warning('Favrica reservation attempted without username/password.');
            return null;
        }

        $params = array_merge([
            'Key_Hack' => $this->keyHack,
            'User_Name' => $this->username,
            'User_Pass' => $this->password,
        ], $payload);

        return $this->get('JsonRez_Save.aspx', $params);
    }

    public function cancelReservation(string $rezId): ?array
    {
        if ($this->username === '' || $this->password === '') {
            Log::warning('Favrica cancel attempted without username/password.');
            return null;
        }

        return $this->get('JsonCancel.aspx', [
            'Key_Hack' => $this->keyHack,
            'User_Name' => $this->username,
            'User_Pass' => $this->password,
            'Rez_ID' => $rezId,
        ]);
    }

    private function get(string $endpoint, array $params): ?array
    {
        if ($this->isCircuitOpen()) {
            Log::warning('Favrica circuit breaker open', ['endpoint' => $endpoint]);
            return null;
        }

        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $filteredParams = array_filter($params, static fn($value) => $value !== null && $value !== '');

        return $this->executeWithRetry(function () use ($url, $filteredParams, $endpoint) {
            Log::info('Favrica API request', [
                'endpoint' => $endpoint,
                'params' => $this->sanitizeParams($filteredParams),
            ]);

            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
                'Accept' => 'application/json',
            ])
                ->withOptions([
                    'verify' => $this->verifySsl,
                    'connect_timeout' => $this->connectTimeout,
                    'timeout' => $this->timeout,
                ])
                ->timeout($this->timeout)
                ->get($url, $filteredParams);

            if (!$response->successful()) {
                throw new \Exception('HTTP ' . $response->status());
            }

            $payload = $response->json();
            if (!is_array($payload)) {
                throw new \Exception('Non-JSON response');
            }

            $this->resetCircuitBreaker();
            return $payload;
        }, $endpoint);
    }

    private function splitDateTime(string $date, string $time): array
    {
        $stamp = trim($date . ' ' . $time);
        try {
            $dt = Carbon::parse($stamp);
        } catch (\Exception $e) {
            $dt = Carbon::now();
        }

        return [
            'day' => $dt->format('d'),
            'month' => $dt->format('m'),
            'year' => $dt->format('Y'),
            'hour' => $dt->format('H'),
            'minute' => $dt->format('i'),
        ];
    }

    private function sanitizeParams(array $params): array
    {
        $masked = $params;
        foreach (['Key_Hack', 'User_Name', 'User_Pass'] as $key) {
            if (array_key_exists($key, $masked)) {
                $masked[$key] = '***';
            }
        }
        return $masked;
    }

    private function executeWithRetry(callable $callback, string $operation): ?array
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                return $callback();
            } catch (\Exception $e) {
                $lastException = $e;
                $this->recordFailure();

                Log::warning('Favrica API attempt failed', [
                    'operation' => $operation,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $this->maxRetries) {
                    $delay = min((int) pow(2, $attempt), 10);
                    sleep($delay);
                }
            }
        }

        Log::error('Favrica API operation failed after retries', [
            'operation' => $operation,
            'error' => $lastException ? $lastException->getMessage() : 'unknown',
        ]);

        return null;
    }

    private function isCircuitOpen(): bool
    {
        if ($this->circuitOpen) {
            if ($this->lastFailureTime !== null && (time() - $this->lastFailureTime) > $this->circuitBreakerTimeout) {
                $this->circuitOpen = false;
                $this->failures = 0;
                Log::info('Favrica circuit breaker reset after timeout');
                return false;
            }
            return true;
        }

        return false;
    }

    private function recordFailure(): void
    {
        $this->failures++;
        $this->lastFailureTime = time();

        if ($this->failures >= $this->circuitBreakerThreshold) {
            $this->circuitOpen = true;
            Log::warning('Favrica circuit breaker opened', ['failures' => $this->failures]);
        }
    }

    private function resetCircuitBreaker(): void
    {
        if ($this->failures > 0) {
            Log::info('Favrica circuit breaker reset', ['failures' => $this->failures]);
            $this->failures = 0;
            $this->circuitOpen = false;
        }
    }
}
