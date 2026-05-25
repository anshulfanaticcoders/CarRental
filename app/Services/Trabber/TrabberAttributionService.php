<?php

namespace App\Services\Trabber;

use App\Models\TrabberClick;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Cookie;

class TrabberAttributionService
{
    public const COOKIE_NAME = 'trabber_attribution';

    public function store(Request $request, array $offerPayload, string $clickid): Cookie
    {
        $offer = $offerPayload['offer'] ?? [];
        $search = $offerPayload['search'] ?? [];
        $days = (int) config('trabber.attribution_days', 90);
        $expiresAt = now()->addDays($days);

        TrabberClick::create([
            'clickid' => $clickid,
            'offer_id' => $offer['offer_id'] ?? null,
            'source' => 'trabber',
            'clicked_url' => $request->fullUrl(),
            'landing_url' => $this->landingUrl($offerPayload),
            'search_metadata' => $search,
            'clicked_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        $payload = [
            'partner_source' => 'trabber',
            'trabber_clickid' => $clickid,
            'trabber_offer_id' => $offer['offer_id'] ?? null,
            'trabber_commission_rate' => (string) config('trabber.commission_rate', 0.05),
            'trabber_clicked_at' => now()->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
        ];

        session(['trabber.attribution' => $payload]);

        return cookie(
            self::COOKIE_NAME,
            $this->encode($payload),
            $days * 24 * 60,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'Lax'
        );
    }

    public function fromRequest(Request $request): array
    {
        $sessionPayload = session('trabber.attribution');
        if (is_array($sessionPayload) && $this->isActive($sessionPayload)) {
            return $sessionPayload;
        }

        $cookiePayload = $this->decode((string) $request->cookie(self::COOKIE_NAME, ''));

        return $this->isActive($cookiePayload) ? $cookiePayload : [];
    }

    public function landingUrl(array $offerPayload): string
    {
        $offerId = (string) data_get($offerPayload, 'offer.offer_id', '');
        $search = $offerPayload['search'] ?? [];
        $locale = (string) ($search['language'] ?? config('trabber.default_language', 'en'));

        if ($offerId !== '' && Route::has('trabber.offer')) {
            return route('trabber.offer', [
                'locale' => $locale,
                'offerId' => $offerId,
            ]);
        }

        $search = $offerPayload['search'] ?? [];
        $pickup = $search['pickup_location'] ?? [];
        $dropoff = $search['dropoff_location'] ?? $pickup;
        $pickupDateTime = isset($search['pickup_date_time']) ? \Carbon\Carbon::parse($search['pickup_date_time']) : now()->addDay();
        $dropoffDateTime = isset($search['dropoff_date_time']) ? \Carbon\Carbon::parse($search['dropoff_date_time']) : now()->addDays(2);

        return route('search', [
            'locale' => $search['language'] ?? config('trabber.default_language', 'en'),
            'where' => $pickup['name'] ?? null,
            'location' => $pickup['name'] ?? null,
            'city' => $pickup['city'] ?? null,
            'country' => $pickup['country'] ?? null,
            'latitude' => $pickup['latitude'] ?? null,
            'longitude' => $pickup['longitude'] ?? null,
            'provider' => 'mixed',
            'provider_pickup_id' => $pickup['vendor_location_id'] ?? null,
            'unified_location_id' => $pickup['unified_location_id'] ?? $pickup['id'] ?? null,
            'dropoff_unified_location_id' => $dropoff['unified_location_id'] ?? $dropoff['id'] ?? null,
            'dropoff_where' => $dropoff['name'] ?? null,
            'dropoff_latitude' => $dropoff['latitude'] ?? null,
            'dropoff_longitude' => $dropoff['longitude'] ?? null,
            'date_from' => $pickupDateTime->toDateString(),
            'date_to' => $dropoffDateTime->toDateString(),
            'start_time' => $pickupDateTime->format('H:i'),
            'end_time' => $dropoffDateTime->format('H:i'),
            'age' => $search['driver_age'] ?? 30,
            'currency' => $search['currency'] ?? config('trabber.default_currency', 'EUR'),
        ]);
    }

    private function encode(array $payload): string
    {
        return base64_encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    private function decode(string $payload): array
    {
        if ($payload === '') {
            return [];
        }

        $decoded = json_decode((string) base64_decode($payload, true), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function isActive(array $payload): bool
    {
        if (($payload['partner_source'] ?? null) !== 'trabber') {
            return false;
        }

        if (empty($payload['trabber_clickid']) || empty($payload['expires_at'])) {
            return false;
        }

        return now()->lessThan(\Carbon\Carbon::parse($payload['expires_at']));
    }
}
