<?php

namespace App\Services;

use Tapfiliate\Tapfiliate;
use Illuminate\Support\Facades\Log;

class TapfiliateService
{
    protected $tapfiliate;
    protected $programId;

    public function __construct()
    {
        $apiKey = config('services.tapfiliate.api_key');
        $this->programId = config('services.tapfiliate.program_id');

        if (!$apiKey || !$this->programId) {
            Log::error('Tapfiliate API Key or Program ID not configured.');
            throw new \Exception('Tapfiliate API Key or Program ID not configured.');
        }

        $this->tapfiliate = new Tapfiliate($apiKey);
    }

    public function createAffiliate(array $data)
    {
        try {
            $response = $this->tapfiliate->affiliates->create($data);
            return $response;
        } catch (\Exception $e) {
            Log::error('Tapfiliate - Error creating affiliate: ' . $e->getMessage());
            throw $e;
        }
    }

    public function trackConversion($order, $affiliateId)
    {
        try {
            $conversionData = [
                'external_id' => $order->id,
                'amount' => $order->total_amount, // Assuming 'total_amount' is the order total
                'currency' => $order->currency, // Assuming 'currency' is available on the order
                'commissions' => [
                    [
                        'amount' => $order->total_amount * 0.10, // Example: 10% commission
                        'currency' => $order->currency,
                    ]
                ],
                'program_id' => $this->programId,
                'affiliate_id' => $affiliateId,
            ];

            $response = $this->tapfiliate->conversions->create($conversionData);
            return $response;
        } catch (\Exception $e) {
            Log::error('Tapfiliate - Error tracking conversion: ' . $e->getMessage());
            throw $e;
        }
    }
}
