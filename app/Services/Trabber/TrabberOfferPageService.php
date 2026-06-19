<?php

namespace App\Services\Trabber;

use App\Services\LocationSearchService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;

class TrabberOfferPageService
{
    private const PUBLIC_SUPPLIER_NAME = 'Vrooem';

    public function __construct(
        private readonly TrabberFuelPolicyFormatter $fuelPolicyFormatter,
        private readonly LocationSearchService $locationSearchService,
    ) {}

    public function quoteFromPayload(array $payload): array
    {
        $offer = is_array($payload['offer'] ?? null) ? $payload['offer'] : [];
        $vehicle = is_array($payload['vehicle'] ?? null) ? $payload['vehicle'] : [];
        $search = $this->normalizeSearch(is_array($payload['search'] ?? null) ? $payload['search'] : []);
        $trabberPricing = is_array($payload['trabber_pricing'] ?? null) ? $payload['trabber_pricing'] : [];
        $pricing = $this->pricing($vehicle, $offer, $search, $trabberPricing);
        $netPricing = $this->netPricing($pricing, $trabberPricing);
        $createdAt = CarbonImmutable::parse($payload['created_at'] ?? now('UTC'))->utc();
        $expiresAt = CarbonImmutable::parse(
            $payload['expires_at'] ?? $createdAt->addMinutes((int) config('trabber.offer_ttl_minutes', 60))->toIso8601String()
        )->utc();

        return [
            'quote_id' => (string) ($offer['offer_id'] ?? ''),
            'case_id' => 'trabber',
            'created_at' => $createdAt->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
            'free_esim_included' => (bool) ($offer['free_esim_included'] ?? false),
            'applied_offers' => $this->arrayList($offer['applied_offers'] ?? []),
            'inclusions' => $this->stringList($offer['inclusions'] ?? []),
            'vehicle' => $this->vehicle($vehicle, $offer),
            'supplier' => $this->supplier($vehicle, $offer),
            'specs' => $this->specs($vehicle),
            'pricing' => $pricing,
            'net_pricing' => $netPricing,
            'policies' => $this->policies($vehicle, $offer),
            'pickup_location_details' => $this->locationDetails(Arr::get($payload, 'search.pickup_location', [])),
            'dropoff_location_details' => $this->locationDetails(Arr::get($payload, 'search.dropoff_location', [])),
            'products' => $this->products($vehicle, $pricing),
            'booking_products' => $this->products($vehicle, $netPricing),
            'extras_preview' => $this->extrasPreview($vehicle),
            'deeplink' => [
                'landing_page_url' => $this->offerUrl((string) ($offer['offer_id'] ?? ''), $search),
                'quote_redirect_url' => Route::has('trabber.redirect') ? route('trabber.redirect', ['offer_id' => $offer['offer_id'] ?? '']) : null,
                'tracking_query_parameter' => (string) config('trabber.click_parameter', 'clickid'),
            ],
            'data_quality_flags' => [],
            'search' => $search,
        ];
    }

    public function revalidate(array $quote): array
    {
        $expiresAt = CarbonImmutable::parse((string) ($quote['expires_at'] ?? now('UTC')->subMinute()->toIso8601String()));

        if (CarbonImmutable::now('UTC')->greaterThan($expiresAt)) {
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

    public function searchAgainUrl(string $locale, array $quote): string
    {
        if (! Route::has('search')) {
            return '/';
        }

        $search = is_array($quote['search'] ?? null) ? $quote['search'] : [];
        $pickup = is_array($quote['pickup_location_details'] ?? null) ? $quote['pickup_location_details'] : [];
        $dropoff = is_array($quote['dropoff_location_details'] ?? null) ? $quote['dropoff_location_details'] : [];
        $source = strtolower(trim((string) data_get($quote, 'vehicle.source', data_get($quote, 'supplier.code', 'mixed'))));
        $provider = $source === '' ? 'mixed' : $source;
        $pickupUnifiedLocationId = $this->resolveSearchAgainUnifiedLocationId($search, $pickup, 'pickup_location_id', $provider);
        $dropoffUnifiedLocationId = $this->resolveSearchAgainUnifiedLocationId($search, $dropoff, 'dropoff_location_id', $provider)
            ?? $pickupUnifiedLocationId;

        return route('search', array_filter([
            'locale' => $locale,
            'where' => $pickup['name'] ?? null,
            'city' => $pickup['city'] ?? null,
            'country' => $pickup['country'] ?? null,
            'latitude' => $pickup['latitude'] ?? null,
            'longitude' => $pickup['longitude'] ?? null,
            'unified_location_id' => $pickupUnifiedLocationId,
            'provider' => $provider,
            'provider_pickup_id' => $pickup['provider_location_id'] ?? null,
            'date_from' => $search['pickup_date'] ?? null,
            'date_to' => $search['dropoff_date'] ?? null,
            'start_time' => $search['pickup_time'] ?? null,
            'end_time' => $search['dropoff_time'] ?? null,
            'age' => $search['driver_age'] ?? null,
            'currency' => $search['currency'] ?? null,
            'dropoff_where' => $dropoff['name'] ?? null,
            'dropoff_unified_location_id' => $dropoffUnifiedLocationId,
            'dropoff_location_id' => $dropoff['provider_location_id'] ?? null,
            'dropoff_latitude' => $dropoff['latitude'] ?? null,
            'dropoff_longitude' => $dropoff['longitude'] ?? null,
        ], static fn ($value) => $value !== null && $value !== ''));
    }

    private function resolveSearchAgainUnifiedLocationId(array $search, array $location, string $searchKey, string $provider): ?int
    {
        foreach ([
            $search[$searchKey] ?? null,
            $location['unified_location_id'] ?? null,
            $location['id'] ?? null,
        ] as $candidate) {
            $normalized = $this->positiveIntegerOrNull($candidate);

            if ($normalized !== null) {
                return $normalized;
            }
        }

        $providerPickupId = trim((string) ($location['provider_location_id'] ?? ''));

        if ($providerPickupId === '' || $provider === 'internal' || $provider === 'mixed') {
            return null;
        }

        $searchPayload = [
            'provider' => $provider,
            'provider_pickup_id' => $providerPickupId,
            'where' => $location['name'] ?? null,
            'city' => $location['city'] ?? null,
            'country' => $location['country'] ?? null,
            'latitude' => $location['latitude'] ?? null,
            'longitude' => $location['longitude'] ?? null,
        ];

        $resolvedLocation = $this->locationSearchService->resolveSearchLocation($searchPayload);
        $resolvedUnifiedLocationId = $this->positiveIntegerOrNull($resolvedLocation['unified_location_id'] ?? null);

        if ($resolvedUnifiedLocationId !== null) {
            return $resolvedUnifiedLocationId;
        }

        return $this->resolveFallbackSearchAgainUnifiedLocationId($searchPayload, $location);
    }

    private function resolveFallbackSearchAgainUnifiedLocationId(array $searchPayload, array $location): ?int
    {
        foreach ($this->searchAgainLocationTerms($location) as $term) {
            foreach ($this->locationSearchService->searchLocations($term, 10) as $candidate) {
                if (! is_array($candidate) || ! $this->searchAgainLocationMatches($candidate, $location)) {
                    continue;
                }

                return $this->positiveIntegerOrNull($candidate['unified_location_id'] ?? null);
            }
        }

        foreach ($this->locationSearchService->nearbyLocations($searchPayload, null, 1) as $candidate) {
            if (! is_array($candidate) || ! $this->searchAgainLocationMatches($candidate, $location)) {
                continue;
            }

            return $this->positiveIntegerOrNull($candidate['unified_location_id'] ?? null);
        }

        return null;
    }

    private function searchAgainLocationTerms(array $location): array
    {
        return collect([
            $location['name'] ?? null,
            trim((string) ($location['city'] ?? '').' '.(string) ($location['country'] ?? '')),
            $location['city'] ?? null,
        ])
            ->filter(fn ($term): bool => is_string($term) && strlen(trim($term)) >= 2)
            ->map(fn ($term): string => trim((string) $term))
            ->unique()
            ->values()
            ->all();
    }

    private function searchAgainLocationMatches(array $candidate, array $location): bool
    {
        if ($this->positiveIntegerOrNull($candidate['unified_location_id'] ?? null) === null) {
            return false;
        }

        $requestedCountryCode = strtoupper(trim((string) ($location['country_code'] ?? '')));
        $candidateCountryCode = strtoupper(trim((string) ($candidate['country_code'] ?? '')));

        if ($requestedCountryCode !== '' && $candidateCountryCode !== '' && $requestedCountryCode !== $candidateCountryCode) {
            return false;
        }

        $requestedCountry = strtolower(trim((string) ($location['country'] ?? '')));
        $candidateCountry = strtolower(trim((string) ($candidate['country'] ?? '')));

        if ($requestedCountryCode === '' && $candidateCountryCode === '' && $requestedCountry !== '' && $candidateCountry !== '' && $requestedCountry !== $candidateCountry) {
            return false;
        }

        if ($this->hasUsableCoordinatePair($location['latitude'] ?? null, $location['longitude'] ?? null)
            && $this->hasUsableCoordinatePair($candidate['latitude'] ?? null, $candidate['longitude'] ?? null)
        ) {
            $distanceKm = $this->distanceKm(
                (float) $location['latitude'],
                (float) $location['longitude'],
                (float) $candidate['latitude'],
                (float) $candidate['longitude'],
            );

            if ($distanceKm > 75.0) {
                return false;
            }
        }

        return true;
    }

    private function positiveIntegerOrNull(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value) && preg_match('/^[1-9]\d*$/', trim($value)) === 1) {
            return (int) $value;
        }

        return null;
    }

    private function hasUsableCoordinatePair(mixed $latitude, mixed $longitude): bool
    {
        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return false;
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;

        if ($latitude < -90.0 || $latitude > 90.0 || $longitude < -180.0 || $longitude > 180.0) {
            return false;
        }

        return ! (abs($latitude) < 0.000001 && abs($longitude) < 0.000001);
    }

    private function distanceKm(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $earthRadiusKm = 6371;
        $latDelta = deg2rad($toLatitude - $fromLatitude);
        $lonDelta = deg2rad($toLongitude - $fromLongitude);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLatitude)) * cos(deg2rad($toLatitude)) * sin($lonDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function vehicle(array $vehicle, array $offer): array
    {
        $specs = is_array($vehicle['specs'] ?? null) ? $vehicle['specs'] : [];
        $supplier = $this->supplier($vehicle, $offer);

        return [
            'provider_vehicle_id' => $this->stringOrNull($vehicle['provider_vehicle_id'] ?? $vehicle['id'] ?? null),
            'gateway_vehicle_id' => $this->stringOrNull($vehicle['gateway_vehicle_id'] ?? data_get($vehicle, 'booking_context.provider_payload.gateway_vehicle_id')),
            'gateway_search_id' => $this->stringOrNull(
                $vehicle['gateway_search_id']
                    ?? data_get($vehicle, 'booking_context.provider_payload.gateway_search_id')
                    ?? data_get($vehicle, 'booking_context.provider_payload.search_id')
            ),
            'source' => $this->stringOrNull($vehicle['source'] ?? $supplier['code'] ?? null),
            'provider_code' => $this->stringOrNull($vehicle['provider_code'] ?? $supplier['code'] ?? null),
            'provider_product_id' => $this->stringOrNull($vehicle['provider_product_id'] ?? null),
            'provider_rate_id' => $this->stringOrNull($vehicle['provider_rate_id'] ?? null),
            'display_name' => $this->displayName($vehicle, $offer),
            'brand' => $this->stringOrNull($vehicle['brand'] ?? null),
            'model' => $this->stringOrNull($vehicle['model'] ?? null),
            'category' => $this->stringOrNull($vehicle['category'] ?? null),
            'image_url' => $this->stringOrNull($vehicle['image'] ?? $vehicle['image_url'] ?? $offer['image_url'] ?? null),
            'supplier_name' => $supplier['name'],
            'supplier_code' => $supplier['code'],
            'sipp_code' => $this->stringOrNull($specs['sipp_code'] ?? $vehicle['sipp_code'] ?? $offer['sipp'] ?? null),
            'transmission' => $this->stringOrNull($specs['transmission'] ?? $vehicle['transmission'] ?? null),
            'fuel_type' => $this->stringOrNull($specs['fuel'] ?? $vehicle['fuel'] ?? $vehicle['fuel_type'] ?? null),
            'air_conditioning' => $this->boolOrNull($specs['air_conditioning'] ?? $vehicle['air_conditioning'] ?? null),
            'seats' => $this->intOrNull($specs['seating_capacity'] ?? $vehicle['seating_capacity'] ?? $vehicle['seats'] ?? null),
            'doors' => $this->intOrNull($specs['doors'] ?? $vehicle['doors'] ?? null),
            'luggage' => [
                'small' => $this->intOrNull($specs['luggage_small'] ?? data_get($vehicle, 'luggage.small')),
                'medium' => $this->intOrNull($specs['luggage_medium'] ?? data_get($vehicle, 'luggage.medium')),
                'large' => $this->intOrNull($specs['luggage_large'] ?? data_get($vehicle, 'luggage.large') ?? $vehicle['luggage_capacity'] ?? null),
            ],
            'booking_context' => is_array($vehicle['booking_context'] ?? null) ? $vehicle['booking_context'] : [],
        ];
    }

    private function supplier(array $vehicle, array $offer): array
    {
        $supplier = is_array($vehicle['supplier'] ?? null) ? $vehicle['supplier'] : [];
        $code = $this->stringOrNull($supplier['code'] ?? $vehicle['provider_code'] ?? $vehicle['source'] ?? $vehicle['supplier_code'] ?? null);

        return [
            'code' => $code ?? 'internal',
            'name' => self::PUBLIC_SUPPLIER_NAME,
        ];
    }

    private function pricing(array $vehicle, array $offer, array $search, array $trabberPricing): array
    {
        $pricing = is_array($vehicle['pricing'] ?? null) ? $vehicle['pricing'] : [];
        $total = $this->floatOrNull($pricing['total_price'] ?? $vehicle['total_price'] ?? $vehicle['total'] ?? $offer['price'] ?? null);
        $grossTotal = $this->floatOrNull($trabberPricing['gross_total_price'] ?? null) ?? $total;
        $days = $this->rentalDays($search);

        return [
            'currency' => $this->stringOrNull($pricing['currency'] ?? $vehicle['currency'] ?? $offer['currency'] ?? $search['currency'] ?? config('trabber.default_currency', 'EUR')),
            'total_price' => $grossTotal,
            'price_per_day' => $this->floatOrNull($trabberPricing['gross_price_per_day'] ?? null)
                ?? $this->floatOrNull($pricing['price_per_day'] ?? $pricing['daily_rate'] ?? ($grossTotal !== null ? $grossTotal / $days : null)),
            'deposit_amount' => $this->floatOrNull($pricing['deposit_amount'] ?? $vehicle['security_deposit'] ?? null),
            'deposit_currency' => $this->stringOrNull($pricing['deposit_currency'] ?? $pricing['currency'] ?? $search['currency'] ?? null),
            'excess_amount' => $this->floatOrNull($pricing['excess_amount'] ?? null),
            'excess_theft_amount' => $this->floatOrNull($pricing['excess_theft_amount'] ?? null),
        ];
    }

    private function netPricing(array $displayPricing, array $trabberPricing): array
    {
        return array_merge($displayPricing, [
            'total_price' => $this->floatOrNull($trabberPricing['net_total_price'] ?? null) ?? $displayPricing['total_price'],
            'price_per_day' => $this->floatOrNull($trabberPricing['net_price_per_day'] ?? null) ?? $displayPricing['price_per_day'],
        ]);
    }

    private function policies(array $vehicle, array $offer): array
    {
        $policies = is_array($vehicle['policies'] ?? null) ? $vehicle['policies'] : [];
        $cancellation = $policies['cancellation'] ?? $offer['cancellation_policy'] ?? $vehicle['cancellation'] ?? null;

        return [
            'mileage_policy' => $this->stringOrNull($policies['mileage_policy'] ?? $offer['mileage_policy'] ?? $vehicle['mileage_policy'] ?? $vehicle['mileage'] ?? null),
            'mileage_limit_km' => $this->floatOrNull($policies['mileage_limit_km'] ?? null),
            'fuel_policy' => $this->fuelPolicyFormatter->label(
                $policies['fuel_policy_label'] ?? null,
                data_get($vehicle, 'supplier_data.fuel_policy_label'),
                $vehicle['fuel_policy_label'] ?? null,
                data_get($vehicle, 'benefits.fuel_policy_label'),
                $policies['fuel_policy'] ?? null,
                $offer['fuel_policy'] ?? null,
                $vehicle['fuel_policy'] ?? null,
                data_get($vehicle, 'benefits.fuel_policy'),
            ),
            'cancellation' => is_array($cancellation) ? $cancellation : [
                'available' => $cancellation !== null,
                'description' => $this->stringOrNull($cancellation),
            ],
        ];
    }

    private function specs(array $vehicle): array
    {
        $specs = is_array($vehicle['specs'] ?? null) ? $vehicle['specs'] : [];

        return [
            'sipp_code' => $specs['sipp_code'] ?? $vehicle['sipp_code'] ?? null,
            'sipp_source' => $specs['sipp_source'] ?? null,
            'transmission' => $specs['transmission'] ?? $vehicle['transmission'] ?? null,
            'fuel' => $specs['fuel'] ?? $vehicle['fuel'] ?? $vehicle['fuel_type'] ?? null,
            'air_conditioning' => $specs['air_conditioning'] ?? $vehicle['air_conditioning'] ?? null,
            'seating_capacity' => $specs['seating_capacity'] ?? $vehicle['seating_capacity'] ?? $vehicle['seats'] ?? null,
            'doors' => $specs['doors'] ?? $vehicle['doors'] ?? null,
            'luggage_small' => $specs['luggage_small'] ?? data_get($vehicle, 'luggage.small'),
            'luggage_medium' => $specs['luggage_medium'] ?? data_get($vehicle, 'luggage.medium'),
            'luggage_large' => $specs['luggage_large'] ?? data_get($vehicle, 'luggage.large') ?? $vehicle['luggage_capacity'] ?? null,
        ];
    }

    private function products(array $vehicle, array $pricing): array
    {
        if (is_array($vehicle['products'] ?? null) && $vehicle['products'] !== []) {
            return $vehicle['products'];
        }

        $providerProducts = data_get($vehicle, 'booking_context.provider_payload.products');
        if (is_array($providerProducts) && $providerProducts !== []) {
            $products = array_values(array_filter($providerProducts, 'is_array'));
            if ($products !== []) {
                return $products;
            }
        }

        return [[
            'type' => 'BAS',
            'name' => 'Basic Rental',
            'subtitle' => 'Standard package',
            'total' => $pricing['total_price'] ?? null,
            'price_per_day' => $pricing['price_per_day'] ?? null,
            'deposit' => $pricing['deposit_amount'] ?? null,
            'deposit_currency' => $pricing['deposit_currency'] ?? $pricing['currency'] ?? null,
            'benefits' => [],
            'currency' => $pricing['currency'] ?? null,
            'is_basic' => true,
        ]];
    }

    private function extrasPreview(array $vehicle): array
    {
        foreach (['extras_preview', 'extras', 'options'] as $key) {
            if (is_array($vehicle[$key] ?? null) && $vehicle[$key] !== []) {
                $extras = array_values(array_filter($vehicle[$key], 'is_array'));
                if ($extras !== []) {
                    return $extras;
                }
            }
        }

        $providerExtras = data_get($vehicle, 'booking_context.provider_payload.extras');

        return is_array($providerExtras)
            ? array_values(array_filter($providerExtras, 'is_array'))
            : [];
    }

    private function normalizeSearch(array $search): array
    {
        $pickupDateTime = CarbonImmutable::parse((string) ($search['pickup_date_time'] ?? now()->addDay()->format('Y-m-d H:i:s')));
        $dropoffDateTime = CarbonImmutable::parse((string) ($search['dropoff_date_time'] ?? now()->addDays(2)->format('Y-m-d H:i:s')));

        return [
            'pickup_date' => $pickupDateTime->toDateString(),
            'pickup_time' => $pickupDateTime->format('H:i'),
            'dropoff_date' => $dropoffDateTime->toDateString(),
            'dropoff_time' => $dropoffDateTime->format('H:i'),
            'driver_age' => $search['driver_age'] ?? 30,
            'currency' => strtoupper((string) ($search['currency'] ?? config('trabber.default_currency', 'EUR'))),
            'language' => $search['language'] ?? config('trabber.default_language', 'en'),
            'pickup_location_id' => data_get($search, 'pickup_location.unified_location_id') ?? data_get($search, 'pickup_location.id'),
            'dropoff_location_id' => data_get($search, 'dropoff_location.unified_location_id') ?? data_get($search, 'dropoff_location.id'),
        ];
    }

    private function locationDetails(mixed $location): array
    {
        $location = is_array($location) ? $location : [];

        return [
            'provider_location_id' => $this->stringOrNull($location['vendor_location_id'] ?? $location['id'] ?? null),
            'name' => $this->stringOrNull($location['name'] ?? null),
            'address' => $this->stringOrNull($location['address'] ?? $location['name'] ?? null),
            'city' => $this->stringOrNull($location['city'] ?? null),
            'state' => $this->stringOrNull($location['state'] ?? null),
            'country' => $this->stringOrNull($location['country'] ?? null),
            'country_code' => $this->stringOrNull($location['country_code'] ?? null),
            'location_type' => $this->stringOrNull($location['location_type'] ?? null),
            'iata' => $this->stringOrNull($location['iata'] ?? null),
            'phone' => $this->stringOrNull($location['phone'] ?? null),
            'pickup_instructions' => $this->stringOrNull($location['pickup_instructions'] ?? null),
            'dropoff_instructions' => $this->stringOrNull($location['dropoff_instructions'] ?? null),
            'latitude' => $this->floatOrNull($location['latitude'] ?? null),
            'longitude' => $this->floatOrNull($location['longitude'] ?? null),
        ];
    }

    private function offerUrl(string $offerId, array $search): ?string
    {
        if ($offerId === '' || ! Route::has('trabber.offer')) {
            return null;
        }

        return route('trabber.offer', [
            'locale' => $search['language'] ?? config('trabber.default_language', 'en'),
            'offerId' => $offerId,
        ]);
    }

    private function displayName(array $vehicle, array $offer): ?string
    {
        return $this->stringOrNull($vehicle['display_name'] ?? $offer['vehicle_name'] ?? trim((string) (($vehicle['brand'] ?? '').' '.($vehicle['model'] ?? ''))));
    }

    private function rentalDays(array $search): int
    {
        if (empty($search['pickup_date']) || empty($search['dropoff_date'])) {
            return 1;
        }

        $pickup = CarbonImmutable::parse($search['pickup_date'].' '.($search['pickup_time'] ?? '09:00'));
        $dropoff = CarbonImmutable::parse($search['dropoff_date'].' '.($search['dropoff_time'] ?? '09:00'));

        if ($dropoff->lessThanOrEqualTo($pickup)) {
            return 1;
        }

        return max(1, (int) ceil($pickup->diffInMinutes($dropoff) / 1440));
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function floatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function boolOrNull(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (bool) $value;
    }

    private function arrayList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_filter($values, 'is_array'));
    }

    private function stringList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (mixed $value): ?string => $this->stringOrNull($value),
            $values,
        )));
    }
}
