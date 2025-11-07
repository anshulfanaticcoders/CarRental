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
            // Prepare order data according to eSIM Access API v1 documentation
            $payload = [
                'assignEmail' => $orderData['customer_email'],
                'packageId' => $orderData['plan_id'],
                'quantity' => $orderData['quantity'] ?? 1,
            ];

            // Add optional parameters if provided
            if (isset($orderData['referenceCode'])) {
                $payload['referenceCode'] = $orderData['referenceCode'];
            }
            if (isset($orderData['iccid'])) {
                $payload['iccid'] = $orderData['iccid'];
            }

            // Use the correct endpoint based on documentation
            $endpoint = $this->baseUrl . '/api/v1/open/order/create';

            Log::info('Creating eSIM order', [
                'endpoint' => $endpoint,
                'packageId' => $orderData['plan_id'],
                'assignEmail' => $orderData['customer_email'],
            ]);

            $response = Http::withHeaders([
                'RT-AccessCode' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)
              ->post($endpoint, $payload);

            if ($response->successful() && $response->json('success')) {
                Log::info('eSIM order created successfully', [
                    'response' => $response->json('obj'),
                ]);
                return $response->json('obj');
            }

            // Enhanced error logging with API response details
            $responseBody = $response->body();
            $responseData = json_decode($responseBody, true);

            Log::error('Failed to create eSIM order', [
                'status' => $response->status(),
                'endpoint' => $endpoint,
                'payload' => $payload,
                'response_body' => $responseBody,
                'error_code' => $responseData['errorCode'] ?? 'unknown',
                'error_message' => $responseData['errorMsg'] ?? 'No error message',
            ]);

            throw new \Exception('Failed to create eSIM order: ' . $responseBody);
        } catch (RequestException $e) {
            Log::error('eSIM API request failed for order creation', [
                'message' => $e->getMessage(),
                'endpoint' => $endpoint ?? 'unknown',
                'payload' => $payload ?? [],
            ]);
            throw new \Exception('API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Get order details by order number
     */
    public function getOrderDetails(string $orderNo): array
    {
        try {
            $response = Http::withHeaders([
                'RT-AccessCode' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)
              ->post($this->baseUrl . '/api/v1/ec/order/status', ['orderNo' => $orderNo]);

            if ($response->successful() && $response->json('success')) {
                return $response->json('obj');
            }

            Log::error('Failed to fetch eSIM order details', [
                'status' => $response->status(),
                'body' => $response->body(),
                'orderNo' => $orderNo,
            ]);
            throw new \Exception('Failed to fetch order details: ' . $response->body());
        } catch (RequestException $e) {
            Log::error('eSIM API request failed for order details', ['message' => $e->getMessage()]);
            throw new \Exception('API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Check account balance
     */
    public function checkBalance(): array
    {
        try {
            $response = Http::withHeaders([
                'RT-AccessCode' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)
              ->post($this->baseUrl . '/api/v1/open/balance/query', (object)[]);

            if ($response->successful() && $response->json('success')) {
                return $response->json('obj');
            }

            Log::error('Failed to check eSIM account balance', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to check balance: ' . $response->body());
        } catch (RequestException $e) {
            Log::error('eSIM API request failed for balance check', ['message' => $e->getMessage()]);
            throw new \Exception('API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Check data usage for eSIM
     */
    public function checkDataUsage(array $esimTranNoList): array
    {
        try {
            $response = Http::withHeaders([
                'RT-AccessCode' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)
              ->post($this->baseUrl . '/api/v1/open/esim/usage/query', [
                  'esimTranNoList' => $esimTranNoList
              ]);

            if ($response->successful() && $response->json('success')) {
                return $response->json('obj');
            }

            Log::error('Failed to check eSIM data usage', [
                'status' => $response->status(),
                'body' => $response->body(),
                'esimTranNoList' => $esimTranNoList,
            ]);
            throw new \Exception('Failed to check data usage: ' . $response->body());
        } catch (RequestException $e) {
            Log::error('eSIM API request failed for data usage check', ['message' => $e->getMessage()]);
            throw new \Exception('API request failed: ' . $e->getMessage());
        }
    }
}
