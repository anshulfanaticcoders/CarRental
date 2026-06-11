<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VrooemGatewayService
{
    protected string $baseUrl;

    protected string $apiKey;

    protected int $timeout;

    protected int $connectTimeout;

    protected ?array $lastError = null;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('vrooem.url', ''), '/');
        $this->apiKey = (string) config('vrooem.api_key', '');
        $this->timeout = max(1, (int) config('vrooem.timeout', 30));
        $this->connectTimeout = max(1, (int) config('vrooem.connect_timeout', 5));
    }

    /**
     * Search vehicles across all providers via the gateway.
     *
     * Returns an array with keys: vehicles, provider_status, search_id
     */
    public function searchVehicles(array $params): array
    {
        $response = $this->request('POST', '/api/v1/vehicles/search', $params);

        if (! $response) {
            return ['vehicles' => [], 'provider_status' => [], 'search_id' => null];
        }

        return $response;
    }

    public function getLastError(): ?array
    {
        return $this->lastError;
    }

    /**
     * Search unified locations via the gateway.
     *
     * Returns array with keys: query, results, total
     */
    public function searchLocations(string $query, int $limit = 20): array
    {
        $response = $this->request('GET', '/api/v1/locations/search', [
            'query' => $query,
            'limit' => $limit,
        ]);

        if (! $response) {
            return ['query' => $query, 'results' => [], 'total' => 0];
        }

        return $response;
    }

    /**
     * List unified locations via the gateway.
     */
    public function listLocations(int $limit = 50, int $offset = 0): array
    {
        $response = $this->request('GET', '/api/v1/locations', [
            'limit' => $limit,
            'offset' => $offset,
        ]);

        return is_array($response) ? $response : [];
    }

    /**
     * List configured gateway suppliers and adapter status.
     */
    public function listSuppliers(): array
    {
        $response = $this->request('GET', '/api/v1/suppliers');

        return is_array($response) ? $response : ['suppliers' => [], 'total' => 0];
    }

    /**
     * Fetch a single unified location via the gateway.
     */
    public function getLocation(int $unifiedLocationId): ?array
    {
        return $this->request('GET', "/api/v1/locations/{$unifiedLocationId}");
    }

    /**
     * Fetch a single location by provider identifier via the gateway.
     */
    public function getLocationByProvider(string $provider, string $pickupId): ?array
    {
        return $this->request('GET', '/api/v1/locations/by-provider', [
            'provider' => $provider,
            'pickup_id' => $pickupId,
        ]);
    }

    /**
     * List one-way dropoff candidates for a provider + pickup.
     *
     * Returns an array of unified location dicts (same shape as getLocation()).
     */
    public function listDropoffCandidates(string $provider, int $pickupUnifiedId, ?string $countryCode = null, int $limit = 100): array
    {
        $params = [
            'provider' => $provider,
            'pickup_unified_id' => $pickupUnifiedId,
            'limit' => $limit,
        ];
        if ($countryCode !== null && $countryCode !== '') {
            $params['country_code'] = strtoupper($countryCode);
        }

        $response = $this->request('GET', '/api/v1/locations/dropoffs', $params);

        return is_array($response) ? $response : [];
    }

    /**
     * Create a booking through the gateway.
     *
     * Returns array with: gateway_booking_id, supplier_booking_id, status, supplier_id
     */
    public function createBooking(array $params): ?array
    {
        return $this->request('POST', '/api/v1/bookings', $params);
    }

    /**
     * Cancel a booking through the gateway.
     */
    public function cancelBooking(string $gatewayBookingId, string $supplierId, string $supplierBookingId, string $reason = ''): ?array
    {
        return $this->request('DELETE', "/api/v1/bookings/{$gatewayBookingId}", [
            'supplier_id' => $supplierId,
            'supplier_booking_id' => $supplierBookingId,
            'reason' => $reason,
        ]);
    }

    /**
     * Make an authenticated request to the gateway.
     */
    protected function request(string $method, string $path, array $params = []): ?array
    {
        $this->lastError = null;

        if ($this->baseUrl === '') {
            $this->lastError = [
                'type' => 'configuration',
                'message' => 'Gateway base URL is not configured.',
            ];
            Log::error('VrooemGateway: Base URL is not configured');

            return null;
        }

        $url = $this->baseUrl.$path;

        try {
            $http = Http::timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ]);

            $method = strtolower($method);
            if ($method === 'get' || $method === 'delete') {
                $queryString = ! empty($params) ? '?'.http_build_query($params) : '';
                $fullUrl = $url.$queryString;
                Log::info('VrooemGateway: Sending request', ['method' => $method, 'url' => $fullUrl]);
                $response = $http->$method($fullUrl);
            } else {
                Log::info('VrooemGateway: Sending request', [
                    'method' => $method,
                    'url' => $url,
                    'body' => $this->redactForLog($params),
                ]);
                $response = $http->$method($url, $params);
            }

            Log::info('VrooemGateway: Response received', [
                'status' => $response->status(),
                'body_length' => strlen($response->body()),
                'body_preview' => substr($response->body(), 0, 500),
            ]);

            if ($response->failed()) {
                $this->lastError = [
                    'type' => 'http',
                    'method' => $method,
                    'url' => $url,
                    'status' => $response->status(),
                    'body_preview' => substr($response->body(), 0, 1000),
                ];
                Log::error('VrooemGateway: Request failed', [
                    'method' => $method,
                    'url' => $url,
                    'status' => $response->status(),
                    'body_length' => strlen($response->body()),
                ]);

                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->lastError = [
                'type' => 'connection',
                'method' => $method,
                'url' => $url,
                'message' => $e->getMessage(),
            ];
            Log::error('VrooemGateway: Connection error', [
                'method' => $method,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function redactForLog(array $payload): array
    {
        $sensitive = [
            'address',
            'city',
            'country',
            'customer',
            'driver',
            'driving_license_country',
            'driving_license_number',
            'email',
            'first_name',
            'last_name',
            'phone',
            'postal_code',
        ];

        $redact = function ($value, ?string $key = null) use (&$redact, $sensitive) {
            if ($key !== null && in_array(strtolower($key), $sensitive, true)) {
                return '[redacted]';
            }

            if (is_array($value)) {
                $clean = [];
                foreach ($value as $childKey => $childValue) {
                    $clean[$childKey] = $redact($childValue, is_string($childKey) ? $childKey : null);
                }

                return $clean;
            }

            return $value;
        };

        return $redact($payload);
    }

    /**
     * Trigger a location sync in the gateway (runs in background on gateway side).
     * Call this when a vendor adds/updates a vehicle location.
     */
    public function triggerLocationSync(): void
    {
        try {
            $this->request('POST', '/api/v1/locations/sync');
            Log::info('VrooemGateway: Location sync triggered');
        } catch (\Exception $e) {
            Log::warning('VrooemGateway: Location sync trigger failed', ['error' => $e->getMessage()]);
        }
    }
}
