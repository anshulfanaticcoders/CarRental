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

    public function __construct()
    {
        $this->baseUrl = rtrim(config('vrooem.url'), '/');
        $this->apiKey = config('vrooem.api_key');
        $this->timeout = config('vrooem.timeout', 30);
        $this->connectTimeout = config('vrooem.connect_timeout', 5);
    }

    /**
     * Search vehicles across all providers via the gateway.
     *
     * Returns an array with keys: vehicles, provider_status, search_id
     */
    public function searchVehicles(array $params): array
    {
        $response = $this->request('POST', '/api/v1/vehicles/search', $params);

        if (!$response) {
            return ['vehicles' => [], 'provider_status' => [], 'search_id' => null];
        }

        return $response;
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

        if (!$response) {
            return ['query' => $query, 'results' => [], 'total' => 0];
        }

        return $response;
    }

    /**
     * List unified locations via the gateway.
     */
    public function listLocations(int $limit = 50): array
    {
        $response = $this->request('GET', '/api/v1/locations', [
            'limit' => $limit,
        ]);

        return is_array($response) ? $response : [];
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
        $url = $this->baseUrl . $path;

        try {
            $http = Http::timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ]);

            $method = strtolower($method);
            if ($method === 'get' || $method === 'delete') {
                $queryString = !empty($params) ? '?' . http_build_query($params) : '';
                $fullUrl = $url . $queryString;
                Log::info('VrooemGateway: Sending request', ['method' => $method, 'url' => $fullUrl]);
                $response = $http->$method($fullUrl);
            } else {
                Log::info('VrooemGateway: Sending request', ['method' => $method, 'url' => $url, 'body' => $params]);
                $response = $http->$method($url, $params);
            }

            Log::info('VrooemGateway: Response received', [
                'status' => $response->status(),
                'body_length' => strlen($response->body()),
                'body_preview' => substr($response->body(), 0, 500),
            ]);

            if ($response->failed()) {
                Log::error('VrooemGateway: Request failed', [
                    'method' => $method,
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('VrooemGateway: Connection error', [
                'method' => $method,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
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
