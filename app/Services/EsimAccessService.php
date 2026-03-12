<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EsimAccessService
{
    private string $apiKey;
    private string $baseUrl;
    private float $markupPercentage;

    public function __construct()
    {
        $this->apiKey = (string) config('services.esim_access.api_key', '');
        $this->baseUrl = rtrim((string) config('services.esim_access.base_url', ''), '/');
        $this->markupPercentage = (float) config('services.esim_access.markup_percentage', 20);
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '' && $this->baseUrl !== '';
    }

    public function getCountries(): array
    {
        return $this->request('GET', '/countries');
    }

    public function getPlans(string $countryCode): array
    {
        return $this->request('GET', '/plans', [
            'country_code' => strtoupper($countryCode),
        ]);
    }

    public function createOrder(array $payload): array
    {
        $payload['markup_percentage'] = $this->markupPercentage;

        return $this->request('POST', '/orders', $payload);
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 500,
                'error' => 'eSIM service is not configured.',
                'data' => [],
            ];
        }

        try {
            $client = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(15);

            $url = $this->baseUrl . $path;
            $method = strtoupper($method);
            $response = $method === 'GET'
                ? $client->get($url, $payload)
                : $client->post($url, $payload);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'error' => $response->successful() ? null : ($response->json('message') ?? 'eSIM API request failed.'),
                'data' => $response->json() ?? [],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 500,
                'error' => $e->getMessage(),
                'data' => [],
            ];
        }
    }
}
