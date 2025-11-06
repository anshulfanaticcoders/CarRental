<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EsimAccessService
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = env('ESIM_ACCESS_API_KEY');
        $this->apiUrl = env('ESIM_ACCESS_API_URL');
    }

    public function getCountries()
    {
        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
        ])->get("{$this->apiUrl}/countries");

        return $response->json();
    }

    public function getPackages(string $countryCode)
    {
        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
        ])->get("{$this->apiUrl}/packages/{$countryCode}");

        return $response->json();
    }

    public function placeOrder(string $packageId, int $quantity)
    {
        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
        ])->post("{$this->apiUrl}/orders", [
            'package_id' => $packageId,
            'quantity' => $quantity,
        ]);

        return $response->json();
    }

    public function getOrderStatus(string $orderId)
    {
        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
        ])->get("{$this->apiUrl}/orders/{$orderId}");

        return $response->json();
    }
}
