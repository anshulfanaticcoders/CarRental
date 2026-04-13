<?php

namespace App\Services\Skyscanner;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;

class CarHireQuoteLifecycleService
{
    public function createQuote(array $vehicle, array $search, ?CarbonInterface $now = null): array
    {
        $createdAt = $this->normalizeNow($now);
        $expiresAt = $createdAt->addMinutes((int) config('skyscanner.quote_ttl_minutes', 30));
        $quoteId = (string) Str::uuid();
        $pickupLocation = is_array(data_get($vehicle, 'location.pickup')) ? data_get($vehicle, 'location.pickup') : [];
        $dropoffLocation = is_array(data_get($vehicle, 'location.dropoff')) ? data_get($vehicle, 'location.dropoff') : [];

        return [
            'quote_id' => $quoteId,
            'case_id' => (string) config('skyscanner.case_id'),
            'created_at' => $createdAt->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
            'vehicle' => $this->buildVehicleSnapshot($vehicle),
            'supplier' => $vehicle['supplier'] ?? [],
            'specs' => $this->buildSpecsSnapshot($vehicle),
            'pricing' => $vehicle['pricing'] ?? [],
            'policies' => $vehicle['policies'] ?? [],
            'pickup_location_details' => $pickupLocation,
            'dropoff_location_details' => $dropoffLocation,
            'products' => is_array($vehicle['products'] ?? null) ? $vehicle['products'] : [],
            'extras_preview' => is_array($vehicle['extras_preview'] ?? null) ? $vehicle['extras_preview'] : [],
            'deeplink' => [
                'landing_page_url' => $this->buildOfferLandingUrl($quoteId),
                'quote_redirect_url' => $this->buildQuoteRedirectUrl($quoteId),
                'tracking_query_parameter' => 'skyscanner_redirectid',
                'signature_query_parameter' => 'signature',
            ],
            'data_quality_flags' => array_values(array_unique(array_filter($vehicle['data_quality_flags'] ?? []))),
            'search' => $search,
        ];
    }

    public function revalidate(array $quote, ?CarbonInterface $now = null): array
    {
        $expiresAt = CarbonImmutable::parse((string) ($quote['expires_at'] ?? ''));
        $currentTime = $this->normalizeNow($now);

        if ($currentTime->greaterThan($expiresAt)) {
            return [
                'valid' => false,
                'reason' => 'expired',
            ];
        }

        return [
            'valid' => true,
            'reason' => null,
        ];
    }

    public function detectMismatch(array $storedQuote, array $currentQuote): array
    {
        $storedVehicleId = $storedQuote['vehicle']['provider_vehicle_id'] ?? null;
        $currentVehicleId = $currentQuote['vehicle']['provider_vehicle_id'] ?? null;

        if ($storedVehicleId !== $currentVehicleId) {
            return [
                'mismatched' => true,
                'reason' => 'vehicle_changed',
            ];
        }

        $storedCurrency = $storedQuote['pricing']['currency'] ?? null;
        $currentCurrency = $currentQuote['pricing']['currency'] ?? null;

        if ($storedCurrency !== $currentCurrency) {
            return [
                'mismatched' => true,
                'reason' => 'currency_changed',
            ];
        }

        $storedPrice = $storedQuote['pricing']['total_price'] ?? null;
        $currentPrice = $currentQuote['pricing']['total_price'] ?? null;

        if ((float) $storedPrice !== (float) $currentPrice) {
            return [
                'mismatched' => true,
                'reason' => 'price_changed',
            ];
        }

        return [
            'mismatched' => false,
            'reason' => null,
        ];
    }

    private function normalizeNow(?CarbonInterface $now): CarbonImmutable
    {
        if ($now === null) {
            return CarbonImmutable::now('UTC');
        }

        return CarbonImmutable::instance($now);
    }

    private function buildVehicleSnapshot(array $vehicle): array
    {
        $specs = is_array($vehicle['specs'] ?? null) ? $vehicle['specs'] : [];
        $supplier = is_array($vehicle['supplier'] ?? null) ? $vehicle['supplier'] : [];

        return [
            'provider_vehicle_id' => $vehicle['provider_vehicle_id'] ?? null,
            'display_name' => $vehicle['display_name'] ?? null,
            'brand' => $vehicle['brand'] ?? null,
            'model' => $vehicle['model'] ?? null,
            'category' => $vehicle['category'] ?? null,
            'image_url' => $vehicle['image'] ?? null,
            'supplier_name' => $supplier['name'] ?? null,
            'supplier_code' => $supplier['code'] ?? null,
            'sipp_code' => $specs['sipp_code'] ?? null,
            'transmission' => $specs['transmission'] ?? null,
            'fuel_type' => $specs['fuel'] ?? null,
            'air_conditioning' => $specs['air_conditioning'] ?? null,
            'seats' => $specs['seating_capacity'] ?? null,
            'doors' => $specs['doors'] ?? null,
            'luggage' => [
                'small' => $specs['luggage_small'] ?? null,
                'medium' => $specs['luggage_medium'] ?? null,
                'large' => $specs['luggage_large'] ?? null,
            ],
        ];
    }

    private function buildSpecsSnapshot(array $vehicle): array
    {
        $specs = is_array($vehicle['specs'] ?? null) ? $vehicle['specs'] : [];

        return [
            'sipp_code' => $specs['sipp_code'] ?? null,
            'sipp_source' => $specs['sipp_source'] ?? null,
            'transmission' => $specs['transmission'] ?? null,
            'fuel' => $specs['fuel'] ?? null,
            'air_conditioning' => $specs['air_conditioning'] ?? null,
            'seating_capacity' => $specs['seating_capacity'] ?? null,
            'doors' => $specs['doors'] ?? null,
            'luggage_small' => $specs['luggage_small'] ?? null,
            'luggage_medium' => $specs['luggage_medium'] ?? null,
            'luggage_large' => $specs['luggage_large'] ?? null,
        ];
    }

    private function buildOfferLandingUrl(string $quoteId): ?string
    {
        if ($quoteId === '' || !Route::has('skyscanner.offer')) {
            return null;
        }

        return route('skyscanner.offer', array_filter([
            'locale' => app()->getLocale() ?: config('app.locale', 'en'),
            'quoteId' => $quoteId,
        ], static fn ($value) => $value !== null && $value !== ''));
    }

    private function buildQuoteRedirectUrl(string $quoteId): ?string
    {
        if (!Route::has('skyscanner.redirect')) {
            return null;
        }

        return route('skyscanner.redirect', [
            'quote_id' => $quoteId,
        ]);
    }
}
