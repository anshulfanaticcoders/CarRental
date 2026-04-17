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
        $amount = number_format((float) $booking->total_amount, 2, '.', '');
        $currency = $booking->booking_currency ?: 'EUR';

        if ($awc) {
            $awc = trim($awc);
        }

        $voucher = $booking->promo_code ?? null;
        if (!$voucher) {
            $providerMeta = $booking->provider_metadata;
            if (is_array($providerMeta)) {
                $voucher = $providerMeta['promo_code'] ?? null;
            }
        }
        if ($voucher) {
            $voucher = (string) $voucher;
        }

        $query = [
            'tt' => 'ss',
            'tv' => 2,
            'merchant' => $advertiserId,
            'amount' => $amount,
            'ch' => 'aw',
            'parts' => 'DEFAULT:' . $amount,
            'vc' => $voucher ?? '',
            'cr' => $currency,
            'ref' => $booking->booking_number,
            'testmode' => config('awin.test_mode', true) ? '1' : '0',
            'p1' => $booking->provider_source ?? 'internal',
        ];

        if ($awc) {
            $query['cks'] = $awc;
        }

        $url = 'https://www.awin1.com/sread.php?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        Log::channel('awin')->info('Awin S2S: Sending conversion', [
            'booking_id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'url' => $url,
            'query' => $query,
        ]);

        try {
            $response = Http::withHeaders([
                'Referer' => rtrim((string) config('app.url', ''), '/') . '/',
            ])->timeout(15)->get($url);

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
