<?php

namespace App\Services\Skyscanner;

use App\Services\OfferService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class CarHireQuoteLifecycleService
{
    private const PUBLIC_SUPPLIER_NAME = 'Vrooem';

    public function createQuote(array $vehicle, array $search, ?CarbonInterface $now = null): array
    {
        $createdAt = $this->normalizeNow($now);
        $expiresAt = $createdAt->addMinutes((int) config('skyscanner.quote_ttl_minutes', 30));
        $quoteId = (string) Str::uuid();
        $pickupLocation = is_array(data_get($vehicle, 'location.pickup')) ? data_get($vehicle, 'location.pickup') : [];
        $dropoffLocation = is_array(data_get($vehicle, 'location.dropoff')) ? data_get($vehicle, 'location.dropoff') : [];
        $resolvedOffers = app(OfferService::class)->resolveAppliedOffers([
            'placement' => 'search',
        ]);

        return [
            'quote_id' => $quoteId,
            'case_id' => (string) config('skyscanner.case_id'),
            'created_at' => $createdAt->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
            'free_esim_included' => (bool) ($resolvedOffers['free_esim_included'] ?? false),
            'applied_offers' => $resolvedOffers['applied_offers'] ?? [],
            'vehicle' => $this->buildVehicleSnapshot($vehicle),
            'supplier' => $this->publicSupplier(is_array($vehicle['supplier'] ?? null) ? $vehicle['supplier'] : []),
            'specs' => $this->buildSpecsSnapshot($vehicle),
            'pricing' => $vehicle['pricing'] ?? [],
            'net_pricing' => is_array($vehicle['net_pricing'] ?? null) ? $vehicle['net_pricing'] : ($vehicle['pricing'] ?? []),
            'policies' => $vehicle['policies'] ?? [],
            'pickup_location_details' => $pickupLocation,
            'dropoff_location_details' => $this->hasLocationDetails($dropoffLocation) ? $dropoffLocation : $pickupLocation,
            'products' => is_array($vehicle['products'] ?? null) ? $vehicle['products'] : [],
            'booking_products' => is_array($vehicle['booking_products'] ?? null) ? $vehicle['booking_products'] : (is_array($vehicle['products'] ?? null) ? $vehicle['products'] : []),
            'extras_preview' => is_array($vehicle['extras_preview'] ?? null) ? $vehicle['extras_preview'] : [],
            'insurance_options' => is_array($vehicle['insurance_options'] ?? null) ? $vehicle['insurance_options'] : [],
            'coverages' => $this->buildCoveragesSnapshot($vehicle),
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
            'source' => $vehicle['source'] ?? null,
            'provider_code' => $vehicle['provider_code'] ?? null,
            'provider_product_id' => $vehicle['provider_product_id'] ?? null,
            'provider_rate_id' => $vehicle['provider_rate_id'] ?? null,
            'display_name' => $vehicle['display_name'] ?? null,
            'brand' => $vehicle['brand'] ?? null,
            'model' => $vehicle['model'] ?? null,
            'category' => $vehicle['category'] ?? null,
            'image_url' => $vehicle['image'] ?? null,
            'supplier_name' => self::PUBLIC_SUPPLIER_NAME,
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
            'booking_context' => is_array($vehicle['booking_context'] ?? null) ? $vehicle['booking_context'] : [],
        ];
    }

    private function publicSupplier(array $supplier): array
    {
        return [
            'code' => $supplier['code'] ?? null,
            'name' => self::PUBLIC_SUPPLIER_NAME,
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

    private function hasLocationDetails(array $location): bool
    {
        foreach (['name', 'address', 'city', 'country', 'country_code', 'iata', 'latitude', 'longitude'] as $key) {
            $value = trim((string) ($location[$key] ?? ''));

            if ($value !== '') {
                return true;
            }
        }

        return false;
    }

    private function buildCoveragesSnapshot(array $vehicle): array
    {
        $insuranceOptions = is_array($vehicle['insurance_options'] ?? null) ? $vehicle['insurance_options'] : [];
        $pricing = is_array($vehicle['pricing'] ?? null) ? $vehicle['pricing'] : [];
        $currency = (string) ($pricing['currency'] ?? 'EUR');
        $coverages = [];

        foreach ($insuranceOptions as $option) {
            if (! is_array($option)) {
                continue;
            }

            $type = strtolower(trim((string) ($option['coverage_type'] ?? $option['type'] ?? $option['id'] ?? '')));

            if (str_contains($type, 'cdw') || str_contains($type, 'collision')) {
                $key = 'cdw';
            } elseif (in_array($type, ['tp', 'third_party', 'third-party'], true) || str_contains($type, 'third')) {
                $key = 'tp';
            } elseif (in_array($type, ['tw', 'theft'], true) || str_contains($type, 'theft')) {
                $key = 'tw';
            } else {
                continue;
            }

            $coverages[$key] = array_filter([
                'included' => array_key_exists('included', $option) ? (bool) $option['included'] : null,
                'excess_amount' => $option['excess_amount'] ?? null,
                'deposit_amount' => $option['deposit_amount'] ?? null,
                'currency' => $option['currency'] ?? $currency,
                'description' => $option['description'] ?? $option['name'] ?? null,
            ], static fn ($value) => $value !== null && $value !== '');
        }

        if (! isset($coverages['cdw']) && array_key_exists('excess_amount', $pricing)) {
            $coverages['cdw'] = array_filter([
                'included' => true,
                'excess_amount' => $pricing['excess_amount'],
                'deposit_amount' => $pricing['deposit_amount'] ?? null,
                'currency' => $pricing['deposit_currency'] ?? $currency,
            ], static fn ($value) => $value !== null && $value !== '');
        }

        if (! isset($coverages['tw']) && array_key_exists('excess_theft_amount', $pricing)) {
            $coverages['tw'] = array_filter([
                'included' => true,
                'excess_amount' => $pricing['excess_theft_amount'],
                'currency' => $pricing['deposit_currency'] ?? $currency,
            ], static fn ($value) => $value !== null && $value !== '');
        }

        return $coverages;
    }

    private function buildOfferLandingUrl(string $quoteId): ?string
    {
        if ($quoteId === '' || ! Route::has('skyscanner.offer')) {
            return null;
        }

        return route('skyscanner.offer', array_filter([
            'locale' => app()->getLocale() ?: config('app.locale', 'en'),
            'quoteId' => $quoteId,
        ], static fn ($value) => $value !== null && $value !== ''));
    }

    private function buildQuoteRedirectUrl(string $quoteId): ?string
    {
        if (! Route::has('skyscanner.redirect')) {
            return null;
        }

        return route('skyscanner.redirect', [
            'quote_id' => $quoteId,
        ]);
    }
}
