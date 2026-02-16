<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecordGoService
{
    private string $baseUrl;
    private string $subscriptionKey;
    private string $partnerUser;
    private array $sellCodes;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.recordgo.base_url', 'https://api.recordgo.cloud'), '/');
        $this->subscriptionKey = (string) config('services.recordgo.subscription_key', '');
        $this->partnerUser = (string) config('services.recordgo.partner_user', '');
        $this->sellCodes = (array) config('services.recordgo.sellcodes', []);
        $this->timeout = (int) config('services.recordgo.timeout', 20);
    }

    public function getAvailability(array $payload): array
    {
        return $this->post('booking_getAvailability', $payload);
    }

    public function getAssociatedComplements(array $payload): array
    {
        return $this->post('booking_getAssociatedComplements', $payload);
    }

    public function bookingStore(array $payload): array
    {
        return $this->post('booking_store', $payload);
    }

    public function bookingUpdate(array $payload): array
    {
        return $this->post('booking_update', $payload);
    }

    public function bookingGetBy(array $query): array
    {
        if ($this->subscriptionKey === '') {
            Log::warning('RecordGo API misconfigured: missing subscription key');
            return [
                'ok' => false,
                'status' => null,
                'data' => null,
                'errors' => [['code' => 'MISCONFIGURED', 'description' => 'Missing RecordGo subscription key']],
            ];
        }

        $base = $this->baseUrl;
        $suffix = str_ends_with($base, '/brokers') ? '' : '/brokers';
        $url = $base . $suffix . '/booking_getBy/';

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->withHeaders([
                    'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
                ])
                ->get($url, $query);

            return $this->normalizeResponse('booking_getBy', $url, $response);
        } catch (\Exception $e) {
            Log::error('RecordGo request failed', [
                'endpoint' => 'booking_getBy',
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'status' => null,
                'data' => null,
                'errors' => [['code' => 'REQUEST_FAILED', 'description' => $e->getMessage()]],
            ];
        }
    }

    public function resolveSellCode(?string $country): ?string
    {
        $country = strtoupper(trim((string) $country));
        if ($country === '') {
            return $this->sellCodes['default'] ?? null;
        }

        return $this->sellCodes[$country] ?? ($this->sellCodes['default'] ?? null);
    }

    public function getPartnerUser(): string
    {
        return $this->partnerUser;
    }

    private function post(string $endpoint, array $payload): array
    {
        if ($this->subscriptionKey === '' || $this->partnerUser === '') {
            Log::warning('RecordGo API misconfigured: missing subscription key or partner user');
            return [
                'ok' => false,
                'status' => null,
                'data' => null,
                'errors' => [['code' => 'MISCONFIGURED', 'description' => 'Missing RecordGo credentials']],
            ];
        }

        $endpoint = ltrim($endpoint, '/');
        $base = $this->baseUrl;
        $suffix = str_ends_with($base, '/brokers') ? '' : '/brokers';
        $url = $base . $suffix . '/' . $endpoint . '/';

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
                ])
                ->post($url, $payload);

            return $this->normalizeResponse($endpoint, $url, $response);
        } catch (\Exception $e) {
            Log::error('RecordGo request failed', [
                'endpoint' => $endpoint,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'status' => null,
                'data' => null,
                'errors' => [['code' => 'REQUEST_FAILED', 'description' => $e->getMessage()]],
            ];
        }
    }

    private function normalizeResponse(string $endpoint, string $url, Response $response): array
    {
        $status = $response->status();
        $json = null;

        try {
            $json = $response->json();
        } catch (\Exception $e) {
            $json = null;
        }

        if (!$response->successful()) {
            Log::warning('RecordGo HTTP error', [
                'endpoint' => $endpoint,
                'url' => $url,
                'status' => $status,
                'body' => $response->body(),
            ]);

            return [
                'ok' => false,
                'status' => $status,
                'data' => is_array($json) ? $json : null,
                'errors' => [['code' => 'HTTP_' . $status, 'description' => 'HTTP request failed']],
            ];
        }

        $statusPayload = is_array($json) ? ($json['status'] ?? null) : null;
        $statusCode = is_array($statusPayload) ? ($statusPayload['idStatus'] ?? null) : null;
        if ($statusCode !== null && (int) $statusCode !== 200) {
            Log::warning('RecordGo API error status', [
                'endpoint' => $endpoint,
                'url' => $url,
                'status' => $statusCode,
                'detailed' => $statusPayload['detailedStatus'] ?? null,
            ]);

            return [
                'ok' => false,
                'status' => $statusCode,
                'data' => is_array($json) ? $json : null,
                'errors' => [[
                    'code' => (string) $statusCode,
                    'description' => (string) ($statusPayload['detailedStatus'] ?? 'RecordGo error'),
                ]],
            ];
        }

        return [
            'ok' => true,
            'status' => $status,
            'data' => is_array($json) ? $json : null,
            'errors' => null,
        ];
    }
}
