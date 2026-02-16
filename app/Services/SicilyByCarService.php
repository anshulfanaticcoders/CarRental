<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SicilyByCarService
{
    private string $baseUrl;
    private string $accountCode;
    private string $apiKey;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.sicily_by_car.base_url', 'https://booking.sbc.it/dev'), '/');
        $this->accountCode = (string) config('services.sicily_by_car.account_code', '');
        $this->apiKey = (string) config('services.sicily_by_car.api_key', '');
        $this->timeout = (int) config('services.sicily_by_car.timeout', 20);
    }

    public function listLocations(): array
    {
        $result = $this->postJson('locations/list', []);
        if (!$result['ok']) {
            return [];
        }

        $locations = $result['data']['locations'] ?? null;
        return is_array($locations) ? $locations : [];
    }

    public function listServices(): array
    {
        $result = $this->postJson('services/list', []);
        if (!$result['ok']) {
            return [];
        }

        $services = $result['data']['services'] ?? null;
        return is_array($services) ? $services : [];
    }

    public function offersAvailability(array $payload): array
    {
        return $this->postJson('offers/availability', $payload);
    }

    public function offerDetails(array $payload): array
    {
        return $this->postJson('offers/details', $payload);
    }

    public function createReservation(array $payload): array
    {
        return $this->postJson('reservations/create', $payload);
    }

    public function commitReservation(string $reservationId): array
    {
        return $this->postJson('reservations/commit', ['reservationId' => $reservationId]);
    }

    public function ignoreReservation(string $reservationId): array
    {
        return $this->postJson('reservations/ignore', ['reservationId' => $reservationId]);
    }

    public function modifyReservation(array $payload): array
    {
        return $this->postJson('reservations/modify', $payload);
    }

    public function retrieveReservation(string $reservationId): array
    {
        return $this->postJson('reservations/retrieve', ['reservationId' => $reservationId]);
    }

    public function searchReservation(array $payload): array
    {
        return $this->postJson('reservations/search', $payload);
    }

    public function cancelReservation(string $reservationId): array
    {
        return $this->postJson('reservations/cancel', ['reservationId' => $reservationId]);
    }

    private function postJson(string $endpoint, array $payload): array
    {
        $endpoint = ltrim($endpoint, '/');

        if ($this->accountCode === '') {
            Log::warning('SicilyByCar API misconfigured: missing account code');
            return [
                'ok' => false,
                'status' => null,
                'data' => null,
                'errors' => [['code' => 'MISCONFIGURED', 'description' => 'Missing SBC account code']],
            ];
        }

        if ($this->apiKey === '') {
            Log::warning('SicilyByCar API misconfigured: missing API key');
            return [
                'ok' => false,
                'status' => null,
                'data' => null,
                'errors' => [['code' => 'MISCONFIGURED', 'description' => 'Missing SBC API key']],
            ];
        }

        $url = $this->baseUrl . '/v2/' . rawurlencode($this->accountCode) . '/' . $endpoint;

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                ])
                ->post($url, $payload);

            return $this->normalizeResponse($endpoint, $url, $response);
        } catch (\Exception $e) {
            Log::error('SicilyByCar request failed', [
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
            $errors = [];

            if (is_array($json)) {
                $detailsErrors = $json['errors'] ?? null;
                if (is_array($detailsErrors)) {
                    foreach ($detailsErrors as $err) {
                        if (is_array($err)) {
                            $errors[] = [
                                'code' => (string) ($err['code'] ?? 'ERROR'),
                                'description' => (string) ($err['description'] ?? $err['message'] ?? 'Request failed'),
                            ];
                        }
                    }
                }
            }

            Log::warning('SicilyByCar HTTP error', [
                'endpoint' => $endpoint,
                'url' => $url,
                'status' => $status,
                'body' => $response->body(),
            ]);

            return [
                'ok' => false,
                'status' => $status,
                'data' => is_array($json) ? $json : null,
                'errors' => !empty($errors) ? $errors : [['code' => 'HTTP_' . $status, 'description' => 'HTTP request failed']],
            ];
        }

        $apiErrors = null;
        if (is_array($json)) {
            $apiErrors = $json['errors'] ?? null;
        }

        if (is_array($apiErrors) && count($apiErrors) > 0) {
            Log::warning('SicilyByCar API returned errors', [
                'endpoint' => $endpoint,
                'url' => $url,
                'status' => $status,
                'errors' => $apiErrors,
                'requestId' => is_array($json) ? ($json['requestId'] ?? null) : null,
            ]);
        }

        return [
            'ok' => true,
            'status' => $status,
            'data' => is_array($json) ? $json : null,
            'errors' => null,
        ];
    }
}
