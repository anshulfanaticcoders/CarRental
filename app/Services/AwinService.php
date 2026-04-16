<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AwinService
{
    public function sendConversion(Booking $booking, ?string $awc = null): array
    {
        $advertiserId = config('awin.advertiser_id');
        $apiKey = config('awin.api_key');

        if (!$apiKey) {
            Log::channel('awin')->warning('Awin: API key not configured, skipping S2S', [
                'booking_id' => $booking->id,
            ]);
            return ['success' => false, 'reason' => 'api_key_missing'];
        }

        $amount = round((float) $booking->total_amount, 2);
        $currency = $booking->booking_currency ?: 'EUR';

        $order = [
            'orderReference' => $booking->booking_number,
            'amount' => $amount,
            'currency' => $currency,
            'channel' => 'aw',
            'isTest' => config('awin.test_mode', true),
            'commissionGroups' => [
                [
                    'code' => 'DEFAULT',
                    'amount' => $amount,
                ],
            ],
        ];

        if ($awc) {
            $order['awc'] = $awc;
        }

        $voucher = $booking->promo_code ?? null;
        if (!$voucher) {
            $providerMeta = $booking->provider_metadata;
            if (is_array($providerMeta)) {
                $voucher = $providerMeta['promo_code'] ?? null;
            }
        }
        if ($voucher) {
            $order['voucher'] = $voucher;
        }

        $order['custom'] = [
            '1' => $booking->provider_source ?? 'internal',
        ];

        $url = config('awin.api_endpoint') . $advertiserId . '/orders';

        Log::channel('awin')->info('Awin S2S: Sending conversion', [
            'booking_id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'url' => $url,
            'payload' => $order,
        ]);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post($url, [
                'orders' => [$order],
            ]);

            $status = $response->status();
            $body = $response->json() ?? $response->body();

            Log::channel('awin')->info('Awin S2S: Response received', [
                'booking_id' => $booking->id,
                'status' => $status,
                'body' => $body,
            ]);

            if (in_array($status, [200, 202])) {
                return ['success' => true, 'status' => $status, 'body' => $body];
            }

            if ($status === 206) {
                Log::channel('awin')->warning('Awin S2S: Partial success', [
                    'booking_id' => $booking->id,
                    'body' => $body,
                ]);
                return ['success' => true, 'partial' => true, 'status' => $status, 'body' => $body];
            }

            Log::channel('awin')->error('Awin S2S: Failed', [
                'booking_id' => $booking->id,
                'status' => $status,
                'body' => $body,
            ]);
            return ['success' => false, 'status' => $status, 'body' => $body];

        } catch (\Exception $e) {
            Log::channel('awin')->error('Awin S2S: Exception', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
