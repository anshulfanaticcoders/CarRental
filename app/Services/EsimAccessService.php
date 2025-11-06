<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class EsimAccessService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.esim_access.api_key');
        $this->baseUrl = config('services.esim_access.base_url');
    }

    /**
     * Get available countries from eSIM Access API
     */
    public function getCountries(): array
    {
        try {
            $response = Http::withHeaders([
                'RT-AccessCode' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)
              ->post($this->baseUrl . '/api/v1/open/location/list', (object)[]);

            if ($response->successful() && $response->json('success')) {
                return $response->json('obj.locationList');
            }

            Log::error('Failed to fetch eSIM countries', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to fetch countries: ' . $response->body());
        } catch (RequestException $e) {
            Log::error('eSIM API request failed', ['message' => $e->getMessage()]);
            throw new \Exception('API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Get eSIM plans for a specific country
     */
    public function getPlansByCountry(string $countryCode): array
    {
        try {
            $response = Http::withHeaders([
                'RT-AccessCode' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)
              ->post($this->baseUrl . '/api/v1/open/package/list', ['locationCode' => $countryCode]);

            if ($response->successful() && $response->json('success')) {
                // Assuming the response has a 'packageList' in the 'obj'
                return $response->json('obj.packageList', []);
            }

            Log::error('Failed to fetch eSIM plans for country ' . $countryCode, [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to fetch plans for ' . $countryCode . ': ' . $response->body());
        } catch (RequestException $e) {
            Log::error('eSIM API request failed for plans', ['message' => $e->getMessage()]);
            throw new \Exception('API request failed for plans: ' . $e->getMessage());
        }
    }

    /**
     * Create an eSIM order
     */
    public function createOrder(array $orderData): array
    {
        try {
            Log::warning('The /orders endpoint may be outdated based on the new API documentation.');
            $response = Http::withHeaders([
                'RT-AccessCode' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)
              ->post($this->baseUrl . '/orders', $orderData);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Failed to create eSIM order: ' . $response->body());
        } catch (RequestException $e) {
            throw new \Exception('API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Get order details by order ID
     */
    public function getOrderDetails(string $orderId): array
    {
        try {
            Log::warning('The /orders/{orderId} endpoint may be outdated based on the new API documentation.');
            $response = Http::withHeaders([
                'RT-AccessCode' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)
              ->get($this->baseUrl . '/orders/' . $orderId);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Failed to fetch order details: ' . $response->body());
        } catch (RequestException $e) {
            throw new \Exception('API request failed: ' . $e->getMessage());
        }
    }
}
