<?php

namespace App\Services\Trabber;

use App\Models\Vehicle;
use App\Models\VendorLocation;
use App\Services\CurrencyConversionService;
use App\Services\OfferService;
use App\Services\Pricing\PayablePercentageService;
use App\Services\Search\InternalSearchVehicleFactory;
use App\Services\Vehicles\InternalVehicleAvailabilityService;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TrabberSearchService
{
    private const PUBLIC_SUPPLIER_NAME = 'Vrooem';

    public function __construct(
        private readonly TrabberLocationResolver $locationResolver,
        private readonly InternalVehicleAvailabilityService $availabilityService,
        private readonly InternalSearchVehicleFactory $vehicleFactory,
        private readonly TrabberGatewayInventoryService $gatewayInventoryService,
        private readonly TrabberOfferStoreService $offerStore,
        private readonly PayablePercentageService $payablePercentageService,
        private readonly TrabberFuelPolicyFormatter $fuelPolicyFormatter,
        private readonly OfferService $offerService,
        private readonly CurrencyConversionService $currencyConversionService
    ) {}

    public function search(array $criteria): array
    {
        $pickupLocation = $this->locationResolver->resolve($criteria['pickup'] ?? null);
        $dropoffLocation = $this->locationResolver->resolve($criteria['dropoff'] ?? ($criteria['pickup'] ?? null));
        $scope = (string) config('trabber.inventory_scope', 'mixed');
        $usesProviders = in_array($scope, ['providers', 'mixed'], true);
        $pickupUnifiedLocation = $usesProviders ? $this->locationResolver->resolveUnified($criteria['pickup'] ?? null) : null;
        $dropoffUnifiedLocation = $usesProviders ? $this->locationResolver->resolveUnified($criteria['dropoff'] ?? ($criteria['pickup'] ?? null)) : null;

        if ((! $pickupLocation && ! $pickupUnifiedLocation) || (! $dropoffLocation && ! $dropoffUnifiedLocation)) {
            return [
                'offers' => [],
                'meta' => [
                    'message' => 'No matching Vrooem location was found for the requested pickup or dropoff.',
                ],
            ];
        }

        $pickupDateTime = Carbon::parse($criteria['pickup_date_time']);
        $dropoffDateTime = Carbon::parse($criteria['dropoff_date_time']);
        $rentalDays = max(1, (int) ceil($pickupDateTime->diffInMinutes($dropoffDateTime) / 1440));
        $currency = strtoupper((string) ($criteria['currency'] ?? config('trabber.default_currency', 'EUR')));
        $currency = $this->normalizeCurrency($currency);

        $offers = [];
        $searchPayload = $this->searchPayload(
            $criteria,
            $currency,
            $pickupLocation,
            $dropoffLocation,
            $pickupUnifiedLocation,
            $dropoffUnifiedLocation
        );
        $resolvedOffers = $this->offerService->resolveAppliedOffers([
            'placement' => 'search',
        ]);

        if (in_array($scope, ['internal', 'mixed'], true) && $pickupLocation && $dropoffLocation) {
            $query = Vehicle::query()
                ->with([
                    'category',
                    'images',
                    'vendorProfile',
                    'vendorProfileData',
                    'vendorLocation',
                    'benefits',
                    'addons',
                    'vendorPlans',
                ])
                ->where('vendor_location_id', $pickupLocation->id);

            $this->availabilityService->apply($query, [
                'pickup_date' => $pickupDateTime->toDateString(),
                'pickup_time' => $pickupDateTime->format('H:i'),
                'dropoff_date' => $dropoffDateTime->toDateString(),
                'dropoff_time' => $dropoffDateTime->format('H:i'),
            ]);

            $offers = array_merge(
                $offers,
                $query
                    ->limit((int) config('trabber.search_limit', 50))
                    ->get()
                    ->map(fn (Vehicle $vehicle) => $this->mapInternalOffer($vehicle, $pickupLocation, $dropoffLocation, $rentalDays, $currency, $searchPayload, $resolvedOffers))
                    ->values()
                    ->all()
            );
        }

        if (in_array($scope, ['providers', 'mixed'], true) && $pickupUnifiedLocation) {
            $offers = array_merge(
                $offers,
                collect($this->gatewayInventoryService->search($criteria, $pickupUnifiedLocation, $dropoffUnifiedLocation))
                    ->map(fn (array $vehicle) => $this->mapVehicleOffer($vehicle, $currency, $searchPayload, $resolvedOffers))
                    ->values()
                    ->all()
            );
        }

        $offers = $this->deduplicateOffers($offers)
            ->take((int) config('trabber.search_limit', 50))
            ->values()
            ->all();

        foreach ($offers as $offer) {
            $offerId = (string) ($offer['offer_id'] ?? '');

            if ($offerId === '') {
                continue;
            }

            $this->offerStore->putOfferResults($offerId, [
                'selected_offer_id' => $offerId,
                'search' => $searchPayload,
                'offers' => $offers,
            ]);
        }

        return [
            'offers' => $offers,
            'meta' => [
                'source' => 'trabber',
                'inventory_scope' => $scope,
                'pickup_location_id' => (string) ($pickupUnifiedLocation['unified_location_id'] ?? $pickupLocation?->id),
                'dropoff_location_id' => (string) ($dropoffUnifiedLocation['unified_location_id'] ?? $dropoffLocation?->id),
                'currency' => $currency,
                'language' => $criteria['language'] ?? config('trabber.default_language', 'en'),
                'user_country' => $criteria['user_country'] ?? config('trabber.default_user_country', 'AE'),
                'offer_count' => count($offers),
            ],
        ];
    }

    private function mapInternalOffer(
        Vehicle $vehicle,
        VendorLocation $pickupLocation,
        VendorLocation $dropoffLocation,
        int $rentalDays,
        string $currency,
        array $searchPayload,
        array $resolvedOffers
    ): array {
        $internalVehicle = $this->vehicleFactory->make($vehicle->toArray(), $rentalDays, [
            'pickup_location_id' => (string) $pickupLocation->id,
            'dropoff_location_id' => (string) $dropoffLocation->id,
        ]);

        return $this->mapVehicleOffer($internalVehicle, $currency, $searchPayload, $resolvedOffers);
    }

    private function mapVehicleOffer(array $vehicle, string $currency, array $searchPayload, array $resolvedOffers): array
    {
        $offerId = (string) Str::uuid();
        $price = round((float) (
            Arr::get($vehicle, 'pricing.total_price')
            ?? $vehicle['total_price']
            ?? $vehicle['total']
            ?? 0
        ), 2);
        $markupRate = $this->payablePercentageService->rate();
        $rentalDays = $this->rentalDays($searchPayload);
        $sourceCurrency = $this->sourceCurrency($vehicle, $currency);
        $currencyContext = $this->customerCurrencyContext($sourceCurrency, $currency);
        $customerPrice = $this->customerAmount($price, $currencyContext);
        $grossPrice = $this->grossAmount($customerPrice, $markupRate);
        $customerVehicle = $this->vehicleWithCustomerCurrency($vehicle, $currencyContext);
        $netPricePerDay = $rentalDays > 0 ? round($price / $rentalDays, 2) : $price;
        $customerNetPricePerDay = $rentalDays > 0 ? round($customerPrice / $rentalDays, 2) : $customerPrice;
        $grossPricePerDay = $rentalDays > 0 ? round($grossPrice / $rentalDays, 2) : $grossPrice;
        $vehicleName = $this->displayName($vehicle, 'Vehicle');
        $supplierName = self::PUBLIC_SUPPLIER_NAME;
        $offerCurrency = $currencyContext['currency'];
        $fuelPolicy = $this->fuelPolicyFormatter->label(
            Arr::get($vehicle, 'policies.fuel_policy_label'),
            Arr::get($vehicle, 'supplier_data.fuel_policy_label'),
            $vehicle['fuel_policy_label'] ?? null,
            Arr::get($vehicle, 'benefits.fuel_policy_label'),
            Arr::get($vehicle, 'policies.fuel_policy'),
            $vehicle['fuel_policy'] ?? null,
            Arr::get($vehicle, 'benefits.fuel_policy'),
        );
        $pricing = $this->pricingSnapshot($customerVehicle, $grossPrice, $grossPricePerDay, $offerCurrency);
        $mileage = $this->mileageDetails($vehicle);
        $policies = $this->policiesSnapshot($vehicle, $fuelPolicy, $mileage);
        $pickupLocationDetails = $this->locationDetails($vehicle, $searchPayload, 'pickup');
        $dropoffLocationDetails = $this->locationDetails($vehicle, $searchPayload, 'dropoff');

        $offer = [
            'offer_id' => $offerId,
            'vehicle_name' => $vehicleName,
            'supplier_name' => $supplierName,
            'sipp' => Arr::get($vehicle, 'specs.sipp_code') ?: ($vehicle['sipp_code'] ?? null),
            'price' => $grossPrice,
            'currency' => $offerCurrency,
            'image_url' => $vehicle['image'] ?? $vehicle['image_url'] ?? null,
            'inclusions' => $this->resolveInclusions($vehicle, $resolvedOffers),
            'free_esim_included' => (bool) ($resolvedOffers['free_esim_included'] ?? false),
            'applied_offers' => $this->normalizeAppliedOffers($resolvedOffers['applied_offers'] ?? []),
            'vehicle' => $this->vehicleSnapshot($customerVehicle, $supplierName),
            'specs' => $this->specsSnapshot($customerVehicle),
            'pricing' => $pricing,
            'policies' => $policies,
            'pickup_location_details' => $pickupLocationDetails,
            'dropoff_location_details' => $dropoffLocationDetails,
            'pickup_location' => $pickupLocationDetails,
            'dropoff_location' => $dropoffLocationDetails,
            'security_deposit' => $this->securityDeposit($pricing),
            'capacity' => $this->capacity($vehicle),
            'mileage' => $mileage,
            'coverages' => $this->coverages($vehicle, $offerCurrency),
            'fuel_policy' => $fuelPolicy,
            'mileage_policy' => $mileage['policy'] ?? null,
            'cancellation_policy' => $policies['cancellation'] ?? null,
            'deeplink_url' => route('trabber.redirect', ['offer_id' => $offerId]),
        ];

        $this->offerStore->put($offerId, [
            'offer' => $offer,
            'vehicle' => $customerVehicle,
            'search' => $searchPayload,
            'trabber_pricing' => [
                'markup_rate' => $markupRate,
                'payment_percentage' => round($markupRate * 100, 2),
                'net_total_price' => round($customerPrice, 2),
                'gross_total_price' => $grossPrice,
                'net_price_per_day' => $customerNetPricePerDay,
                'gross_price_per_day' => $grossPricePerDay,
                'source_currency' => $sourceCurrency,
                'currency' => $offerCurrency,
            ],
            'created_at' => now('UTC')->toIso8601String(),
            'expires_at' => now('UTC')->addMinutes((int) config('trabber.offer_ttl_minutes', 60))->toIso8601String(),
        ]);

        return $offer;
    }

    private function resolveInclusions(array $internalVehicle, array $resolvedOffers): array
    {
        $inclusions = [];

        foreach (($internalVehicle['products'][0]['benefits'] ?? []) as $benefit) {
            if (is_string($benefit) && trim($benefit) !== '') {
                $inclusions[] = trim($benefit);
            }
        }

        if (($internalVehicle['policies']['mileage_policy'] ?? null) === 'unlimited') {
            $inclusions[] = 'Unlimited mileage';
        }

        if (($resolvedOffers['free_esim_included'] ?? false) === true) {
            $inclusions[] = 'Free eSIM included';
        }

        return array_values(array_unique($inclusions));
    }

    private function normalizeAppliedOffers(array $offers): array
    {
        return array_values(array_filter(array_map(static function (array $offer): ?array {
            $normalized = array_filter([
                'id' => $offer['id'] ?? null,
                'name' => $offer['name'] ?? null,
                'slug' => $offer['slug'] ?? null,
                'title' => $offer['title'] ?? null,
                'description' => $offer['description'] ?? null,
                'effect_type' => $offer['effect_type'] ?? null,
                'effect_payload' => is_array($offer['effect_payload'] ?? null) ? $offer['effect_payload'] : null,
                'discount_amount' => array_key_exists('discount_amount', $offer) ? $offer['discount_amount'] : null,
            ], static fn ($value) => $value !== null);

            return $normalized === [] ? null : $normalized;
        }, $offers)));
    }

    private function vehicleSnapshot(array $vehicle, string $supplierName): array
    {
        $specs = $this->specsSnapshot($vehicle);
        $luggage = $this->payload([
            'small' => $specs['luggage_small'] ?? null,
            'medium' => $specs['luggage_medium'] ?? null,
            'large' => $specs['luggage_large'] ?? null,
        ]);

        return $this->payload([
            'provider_vehicle_id' => $this->stringOrNull($vehicle['provider_vehicle_id'] ?? $vehicle['id'] ?? null),
            'source' => $this->stringOrNull($vehicle['source'] ?? null),
            'provider_code' => $this->stringOrNull($vehicle['provider_code'] ?? Arr::get($vehicle, 'supplier.code') ?? $vehicle['source'] ?? null),
            'provider_product_id' => $this->stringOrNull($vehicle['provider_product_id'] ?? null),
            'provider_rate_id' => $this->stringOrNull($vehicle['provider_rate_id'] ?? null),
            'display_name' => $this->displayName($vehicle),
            'brand' => $this->stringOrNull($vehicle['brand'] ?? null),
            'model' => $this->stringOrNull($vehicle['model'] ?? null),
            'category' => $this->stringOrNull($vehicle['category'] ?? null),
            'image_url' => $this->stringOrNull($vehicle['image'] ?? $vehicle['image_url'] ?? null),
            'supplier_name' => $supplierName,
            'supplier_code' => $this->stringOrNull(Arr::get($vehicle, 'supplier.code') ?? $vehicle['provider_code'] ?? $vehicle['source'] ?? null),
            'sipp_code' => $specs['sipp_code'] ?? null,
            'transmission' => $specs['transmission'] ?? null,
            'fuel_type' => $specs['fuel'] ?? null,
            'air_conditioning' => $specs['air_conditioning'] ?? null,
            'seats' => $specs['seating_capacity'] ?? null,
            'doors' => $specs['doors'] ?? null,
            'luggage' => $luggage,
        ]);
    }

    private function specsSnapshot(array $vehicle): array
    {
        return $this->payload([
            'sipp_code' => $this->stringOrNull(Arr::get($vehicle, 'specs.sipp_code') ?: ($vehicle['sipp_code'] ?? null)),
            'sipp_source' => $this->stringOrNull(Arr::get($vehicle, 'specs.sipp_source')),
            'transmission' => $this->stringOrNull(Arr::get($vehicle, 'specs.transmission') ?? $vehicle['transmission'] ?? null),
            'fuel' => $this->stringOrNull(Arr::get($vehicle, 'specs.fuel') ?? $vehicle['fuel'] ?? $vehicle['fuel_type'] ?? null),
            'air_conditioning' => $this->boolOrNull(Arr::get($vehicle, 'specs.air_conditioning') ?? $vehicle['air_conditioning'] ?? $vehicle['airConditioning'] ?? null),
            'seating_capacity' => $this->numberOrNull(Arr::get($vehicle, 'specs.seating_capacity') ?? $vehicle['seating_capacity'] ?? $vehicle['seats'] ?? null),
            'doors' => $this->numberOrNull(Arr::get($vehicle, 'specs.doors') ?? $vehicle['doors'] ?? null),
            'luggage_small' => $this->numberOrNull(Arr::get($vehicle, 'specs.luggage_small') ?? $vehicle['luggageSmall'] ?? Arr::get($vehicle, 'luggage.small')),
            'luggage_medium' => $this->numberOrNull(Arr::get($vehicle, 'specs.luggage_medium') ?? $vehicle['luggageMed'] ?? Arr::get($vehicle, 'luggage.medium')),
            'luggage_large' => $this->numberOrNull(Arr::get($vehicle, 'specs.luggage_large') ?? $vehicle['luggageLarge'] ?? Arr::get($vehicle, 'luggage.large') ?? $vehicle['luggage_capacity'] ?? $vehicle['suitcases'] ?? null),
        ]);
    }

    private function pricingSnapshot(array $vehicle, float $grossPrice, float $grossPricePerDay, string $currency): array
    {
        $sourcePricing = is_array($vehicle['pricing'] ?? null) ? $vehicle['pricing'] : [];
        $deposit = $this->securityDepositFromVehicle($vehicle, $currency);

        return $this->payload([
            'currency' => $currency,
            'total_price' => $grossPrice,
            'price_per_day' => $grossPricePerDay,
            'deposit_amount' => $deposit['amount'] ?? null,
            'deposit_currency' => $deposit['currency'] ?? null,
            'excess_amount' => $this->numberOrNull($sourcePricing['excess_amount'] ?? data_get($vehicle, 'benefits.excess_amount')),
            'excess_theft_amount' => $this->numberOrNull($sourcePricing['excess_theft_amount'] ?? data_get($vehicle, 'benefits.excess_theft_amount')),
        ]);
    }

    private function vehicleWithCustomerCurrency(array $vehicle, array $currencyContext): array
    {
        $vehicle['currency'] = $currencyContext['currency'];

        if (is_array($vehicle['pricing'] ?? null)) {
            $vehicle['pricing'] = $this->customerPricing($vehicle['pricing'], $currencyContext);
        }

        foreach (['products', 'extras_preview', 'extras', 'options', 'insurance_options'] as $key) {
            if (is_array($vehicle[$key] ?? null)) {
                $vehicle[$key] = $this->customerMoneyList($vehicle[$key], $currencyContext);
            }
        }

        foreach (['total_price', 'price_per_day', 'daily_rate', 'total', 'price_per_week', 'price_per_month'] as $key) {
            if (array_key_exists($key, $vehicle)) {
                $vehicle[$key] = $this->customerAmountOrOriginal($vehicle[$key], $currencyContext);
            }
        }

        return $vehicle;
    }

    private function customerPricing(array $pricing, array $currencyContext): array
    {
        foreach (['total_price', 'price_per_day', 'daily_rate', 'total'] as $key) {
            if (array_key_exists($key, $pricing)) {
                $pricing[$key] = $this->customerAmountOrOriginal($pricing[$key], $currencyContext);
            }
        }

        $pricing['currency'] = $currencyContext['currency'];

        return $pricing;
    }

    private function customerMoneyList(array $items, array $currencyContext): array
    {
        return array_values(array_map(function ($item) use ($currencyContext) {
            if (! is_array($item)) {
                return $item;
            }

            foreach (['total', 'price_per_day', 'daily_rate', 'price', 'total_price', 'amount', 'total_for_booking'] as $key) {
                if (array_key_exists($key, $item)) {
                    $item[$key] = $this->customerAmountOrOriginal($item[$key], $currencyContext);
                }
            }

            $item['currency'] = $currencyContext['currency'];

            foreach (['total_for_booking_currency', 'Total_for_this_booking_currency'] as $key) {
                if (array_key_exists($key, $item)) {
                    $item[$key] = $currencyContext['currency'];
                }
            }

            return $item;
        }, $items));
    }

    private function customerCurrencyContext(string $sourceCurrency, string $requestedCurrency): array
    {
        $sourceCurrency = $this->normalizeCurrency($sourceCurrency);
        $requestedCurrency = $this->normalizeCurrency($requestedCurrency);

        if ($sourceCurrency === $requestedCurrency) {
            return [
                'currency' => $requestedCurrency,
                'source_currency' => $sourceCurrency,
                'rate' => 1.0,
            ];
        }

        $conversion = $this->currencyConversionService->convert(1.0, $sourceCurrency, $requestedCurrency);

        if (! ($conversion['success'] ?? false)) {
            return [
                'currency' => $sourceCurrency,
                'source_currency' => $sourceCurrency,
                'rate' => 1.0,
            ];
        }

        return [
            'currency' => $this->normalizeCurrency($conversion['to_currency'] ?? $requestedCurrency),
            'source_currency' => $this->normalizeCurrency($conversion['from_currency'] ?? $sourceCurrency),
            'rate' => (float) ($conversion['converted_amount'] ?? $conversion['rate'] ?? 1.0),
        ];
    }

    private function sourceCurrency(array $vehicle, string $fallback): string
    {
        return $this->normalizeCurrency(
            Arr::get($vehicle, 'pricing.currency')
                ?? ($vehicle['currency'] ?? data_get($vehicle, 'benefits.deposit_currency') ?? $fallback)
        );
    }

    private function customerAmountOrOriginal(mixed $amount, array $currencyContext): mixed
    {
        if (! is_numeric($amount)) {
            return $amount;
        }

        return $this->customerAmount((float) $amount, $currencyContext);
    }

    private function customerAmount(float $amount, array $currencyContext): float
    {
        return round($amount * (float) ($currencyContext['rate'] ?? 1.0), 2);
    }

    private function policiesSnapshot(array $vehicle, ?string $fuelPolicy, array $mileage): array
    {
        $policies = is_array($vehicle['policies'] ?? null) ? $vehicle['policies'] : [];
        $cancellation = $policies['cancellation'] ?? $vehicle['cancellation'] ?? null;

        return $this->payload([
            'mileage_policy' => $mileage['policy'] ?? null,
            'mileage_limit_km' => $mileage['allowance'] ?? null,
            'mileage' => $mileage,
            'fuel_policy' => $fuelPolicy,
            'cancellation' => is_array($cancellation) ? $cancellation : $this->stringOrNull($cancellation),
        ]);
    }

    private function locationDetails(array $vehicle, array $searchPayload, string $type): array
    {
        $location = Arr::get($vehicle, "location.{$type}");
        $location = is_array($location) ? $location : [];
        $searchLocation = $searchPayload["{$type}_location"] ?? [];
        $searchLocation = is_array($searchLocation) ? $searchLocation : [];
        $prefix = $type === 'dropoff' ? 'dropoff' : 'pickup';
        $atAirport = $this->airportFlag($vehicle, $location, $searchLocation);
        $requiresShuttle = $this->requiresShuttle($vehicle, $location, $prefix);

        return $this->payload([
            'id' => $this->stringOrNull($location['provider_location_id'] ?? $searchLocation['id'] ?? null),
            'provider_location_id' => $this->stringOrNull($location['provider_location_id'] ?? $vehicle["provider_{$prefix}_id"] ?? $vehicle["provider_{$prefix}_office_id"] ?? null),
            'name' => $this->stringOrNull($location['name'] ?? $vehicle["{$prefix}_station_name"] ?? $vehicle["{$prefix}_office"] ?? $searchLocation['name'] ?? $vehicle['full_vehicle_address'] ?? null),
            'location_type' => $this->stringOrNull($location['location_type'] ?? $searchLocation['location_type'] ?? $vehicle['location_type'] ?? null),
            'iata' => $this->stringOrNull($location['iata'] ?? $searchLocation['iata'] ?? null),
            'city' => $this->stringOrNull($location['city'] ?? $searchLocation['city'] ?? $vehicle['city'] ?? null),
            'country' => $this->stringOrNull($location['country'] ?? $searchLocation['country'] ?? $vehicle['country'] ?? null),
            'country_code' => $this->stringOrNull($location['country_code'] ?? $searchLocation['country_code'] ?? null),
            'address' => $this->stringOrNull($location['address'] ?? $vehicle["{$prefix}_address"] ?? ($prefix === 'pickup' ? ($vehicle['office_address'] ?? $vehicle['full_vehicle_address'] ?? null) : null) ?? $searchLocation['address'] ?? null),
            'latitude' => $this->numberOrNull($location['latitude'] ?? ($prefix === 'pickup' ? ($vehicle['latitude'] ?? null) : null) ?? $searchLocation['latitude'] ?? null),
            'longitude' => $this->numberOrNull($location['longitude'] ?? ($prefix === 'pickup' ? ($vehicle['longitude'] ?? null) : null) ?? $searchLocation['longitude'] ?? null),
            'at_airport' => $atAirport,
            'in_terminal' => $this->inTerminal($vehicle, $location, $prefix, $atAirport, $requiresShuttle),
            'requires_shuttle' => $requiresShuttle,
            'phone' => $this->stringOrNull($location['phone'] ?? $vehicle['office_phone'] ?? null),
            'instructions' => $this->stringOrNull($location["{$prefix}_instructions"] ?? $vehicle["{$prefix}_instructions"] ?? null),
        ]);
    }

    private function securityDeposit(array $pricing): array
    {
        return $this->payload([
            'amount' => $pricing['deposit_amount'] ?? null,
            'currency' => $pricing['deposit_currency'] ?? ($pricing['currency'] ?? null),
        ]);
    }

    private function securityDepositFromVehicle(array $vehicle, string $currency): array
    {
        $pricing = is_array($vehicle['pricing'] ?? null) ? $vehicle['pricing'] : [];

        return $this->payload([
            'amount' => $this->numberOrNull($pricing['deposit_amount'] ?? $vehicle['security_deposit'] ?? $vehicle['deposit'] ?? data_get($vehicle, 'benefits.deposit_amount') ?? data_get($vehicle, 'products.0.deposit')),
            'currency' => $this->stringOrNull($pricing['deposit_currency'] ?? data_get($vehicle, 'benefits.deposit_currency') ?? data_get($vehicle, 'products.0.deposit_currency') ?? $currency),
        ]);
    }

    private function capacity(array $vehicle): array
    {
        $specs = $this->specsSnapshot($vehicle);

        return $this->payload([
            'seats' => $specs['seating_capacity'] ?? null,
            'bags' => $this->bagCount($vehicle, $specs),
            'luggage' => $this->payload([
                'small' => $specs['luggage_small'] ?? null,
                'medium' => $specs['luggage_medium'] ?? null,
                'large' => $specs['luggage_large'] ?? null,
            ]),
        ]);
    }

    private function mileageDetails(array $vehicle): array
    {
        $policy = $this->mileagePolicy($vehicle);
        $allowance = $this->mileageAllowance($vehicle);

        if ($policy === null && $allowance !== null) {
            $policy = 'limited';
        }

        return $this->payload([
            'policy' => $policy,
            'allowance' => $policy === 'limited' ? $allowance : null,
            'unit' => $policy === 'limited' && $allowance !== null ? 'km' : null,
            'period' => $policy === 'limited' && $allowance !== null ? $this->mileagePeriod($vehicle) : null,
        ]);
    }

    private function coverages(array $vehicle, string $currency): array
    {
        $pricing = is_array($vehicle['pricing'] ?? null) ? $vehicle['pricing'] : [];
        $insuranceOptions = is_array($vehicle['insurance_options'] ?? null) ? $vehicle['insurance_options'] : [];

        return $this->payload([
            'cdw' => $this->coveragePayload(
                'CDW',
                $this->findCoverageOption($insuranceOptions, ['cdw', 'collision', 'damage', 'ldw']),
                $this->numberOrNull($pricing['excess_amount'] ?? data_get($vehicle, 'benefits.excess_amount')),
                $currency
            ),
            'tp' => $this->coveragePayload(
                'TP',
                $this->findCoverageOption($insuranceOptions, ['tp', 'tpl', 'third party', 'liability', 'pli']),
                null,
                $currency,
                $this->numberOrNull($vehicle['pli'] ?? data_get($vehicle, 'supplier_data.pli'))
            ),
            'tw' => $this->coveragePayload(
                'TW',
                $this->findCoverageOption($insuranceOptions, ['tw', 'theft']),
                $this->numberOrNull($pricing['excess_theft_amount'] ?? data_get($vehicle, 'benefits.excess_theft_amount')),
                $currency
            ),
        ]);
    }

    private function coveragePayload(string $type, ?array $option, ?float $excessAmount, string $currency, ?float $liabilityAmount = null): ?array
    {
        if ($option === null && $excessAmount === null && $liabilityAmount === null) {
            return null;
        }

        return $this->payload([
            'type' => $type,
            'included' => $this->boolOrNull($option['included'] ?? null) ?? true,
            'name' => $this->stringOrNull($option['name'] ?? $type),
            'description' => $this->stringOrNull($option['description'] ?? null),
            'excess_amount' => $this->numberOrNull($option['excess_amount'] ?? $excessAmount),
            'deposit_amount' => $this->numberOrNull($option['deposit_amount'] ?? null),
            'liability_amount' => $liabilityAmount,
            'currency' => $this->stringOrNull($option['currency'] ?? $currency),
        ]);
    }

    private function findCoverageOption(array $options, array $needles): ?array
    {
        foreach ($options as $option) {
            if (! is_array($option)) {
                continue;
            }

            $text = strtolower(implode(' ', array_filter([
                $this->stringOrNull($option['id'] ?? null),
                $this->stringOrNull($option['name'] ?? null),
                $this->stringOrNull($option['coverage_type'] ?? null),
                $this->stringOrNull($option['description'] ?? null),
            ])));

            foreach ($needles as $needle) {
                if (str_contains($text, $needle)) {
                    return $option;
                }
            }
        }

        return null;
    }

    private function mileagePolicy(array $vehicle): ?string
    {
        $value = strtolower($this->stringOrNull(Arr::get($vehicle, 'policies.mileage_policy') ?? $vehicle['mileage_policy'] ?? $vehicle['mileage'] ?? null) ?? '');

        return in_array($value, ['limited', 'unlimited'], true) ? $value : null;
    }

    private function mileageAllowance(array $vehicle): ?float
    {
        return $this->numberOrNull(
            Arr::get($vehicle, 'policies.mileage_limit_km')
            ?? data_get($vehicle, 'benefits.limited_km_per_day_range')
            ?? data_get($vehicle, 'benefits.limited_km_per_week_range')
            ?? data_get($vehicle, 'benefits.limited_km_per_month_range')
            ?? data_get($vehicle, 'supplier_data.mileage_limit_km')
            ?? data_get($vehicle, 'products.0.mileage')
        );
    }

    private function mileagePeriod(array $vehicle): string
    {
        $explicit = $this->stringOrNull(data_get($vehicle, 'supplier_data.mileage_period') ?? data_get($vehicle, 'supplier_data.mileage_allowance_period'));
        if ($explicit !== null) {
            return $explicit;
        }

        if ($this->numberOrNull(data_get($vehicle, 'benefits.limited_km_per_week_range')) !== null) {
            return 'per_week';
        }

        if ($this->numberOrNull(data_get($vehicle, 'benefits.limited_km_per_month_range')) !== null) {
            return 'per_month';
        }

        if ($this->numberOrNull(data_get($vehicle, 'benefits.limited_km_per_day_range')) !== null) {
            return 'per_day';
        }

        if (($vehicle['source'] ?? null) === 'internal' && $this->numberOrNull(Arr::get($vehicle, 'policies.mileage_limit_km')) !== null) {
            return 'per_day';
        }

        return 'per_rental';
    }

    private function bagCount(array $vehicle, array $specs): ?float
    {
        $explicit = $this->numberOrNull($vehicle['bags'] ?? null);
        if ($explicit !== null) {
            return $explicit;
        }

        $large = $this->numberOrNull($specs['luggage_large'] ?? null) ?? 0.0;
        $medium = $this->numberOrNull($specs['luggage_medium'] ?? null) ?? 0.0;
        $small = $this->numberOrNull($specs['luggage_small'] ?? null) ?? 0.0;
        $total = $large + $medium + $small;

        return $total > 0 ? $total : null;
    }

    private function airportFlag(array $vehicle, array $location, array $searchLocation): bool
    {
        $explicit = $this->boolOrNull($vehicle['at_airport'] ?? $location['at_airport'] ?? $location['is_airport'] ?? null);
        if ($explicit !== null) {
            return $explicit;
        }

        $type = strtolower($this->stringOrNull($location['location_type'] ?? $searchLocation['location_type'] ?? $vehicle['location_type'] ?? null) ?? '');

        return $type === 'airport' || $this->stringOrNull($location['iata'] ?? $searchLocation['iata'] ?? null) !== null;
    }

    private function requiresShuttle(array $vehicle, array $location, string $prefix): bool
    {
        $explicit = $this->boolOrNull($vehicle["{$prefix}_requires_shuttle"] ?? $location['requires_shuttle'] ?? $location['shuttle_required'] ?? null);
        if ($explicit !== null) {
            return $explicit;
        }

        $text = strtolower(implode(' ', array_filter([
            $this->stringOrNull($location['name'] ?? null),
            $this->stringOrNull($location['address'] ?? null),
            $this->stringOrNull($location["{$prefix}_instructions"] ?? null),
            $this->stringOrNull($vehicle["{$prefix}_instructions"] ?? null),
            $this->stringOrNull($vehicle["{$prefix}_address"] ?? null),
            $this->stringOrNull($vehicle['office_address'] ?? null),
        ])));

        return str_contains($text, 'shuttle') || str_contains($text, 'transfer bus');
    }

    private function inTerminal(array $vehicle, array $location, string $prefix, bool $atAirport, bool $requiresShuttle): bool
    {
        $explicit = $this->boolOrNull($vehicle["{$prefix}_in_terminal"] ?? $location['in_terminal'] ?? null);
        if ($explicit !== null) {
            return $explicit;
        }

        $text = strtolower(implode(' ', array_filter([
            $this->stringOrNull($location['name'] ?? null),
            $this->stringOrNull($location['address'] ?? null),
            $this->stringOrNull($vehicle["{$prefix}_address"] ?? null),
            $this->stringOrNull($vehicle['office_address'] ?? null),
            $this->stringOrNull($vehicle['full_vehicle_address'] ?? null),
        ])));

        return $atAirport && ! $requiresShuttle && (str_contains($text, 'terminal') || $this->stringOrNull($location['iata'] ?? null) !== null);
    }

    private function payload(array $values): array
    {
        return array_filter($values, static fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    private function displayName(array $vehicle, ?string $fallback = null): ?string
    {
        $displayName = $this->stringOrNull($vehicle['display_name'] ?? null);
        if ($displayName !== null) {
            return $displayName;
        }

        $name = trim(implode(' ', array_filter([
            $this->stringOrNull($vehicle['brand'] ?? null),
            $this->stringOrNull($vehicle['model'] ?? null),
        ])));

        return $name === '' ? $fallback : $name;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (is_array($value) || is_object($value)) {
            return null;
        }

        $string = trim((string) ($value ?? ''));

        return $string === '' ? null : $string;
    }

    private function numberOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function boolOrNull(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === 1 || $value === '1' || $value === 'true' || $value === 'yes') {
            return true;
        }

        if ($value === 0 || $value === '0' || $value === 'false' || $value === 'no') {
            return false;
        }

        return null;
    }

    private function deduplicateOffers(array $offers): Collection
    {
        return collect($offers)
            ->unique(function (array $offer): string {
                return strtolower(implode('|', [
                    data_get($offer, 'vehicle.supplier_code') ?? $offer['supplier_name'] ?? '',
                    $offer['vehicle_name'] ?? '',
                    $offer['sipp'] ?? '',
                    (string) ($offer['price'] ?? ''),
                    $offer['currency'] ?? '',
                ]));
            })
            ->values();
    }

    private function searchPayload(
        array $criteria,
        string $currency,
        ?VendorLocation $pickupLocation,
        ?VendorLocation $dropoffLocation,
        ?array $pickupUnifiedLocation,
        ?array $dropoffUnifiedLocation
    ): array {
        return [
            'pickup_location' => $this->locationPayload($pickupLocation, $pickupUnifiedLocation),
            'dropoff_location' => $this->locationPayload($dropoffLocation, $dropoffUnifiedLocation),
            'pickup_date_time' => $criteria['pickup_date_time'],
            'dropoff_date_time' => $criteria['dropoff_date_time'],
            'currency' => $currency,
            'language' => $criteria['language'] ?? config('trabber.default_language', 'en'),
            'user_country' => $criteria['user_country'] ?? config('trabber.default_user_country', 'AE'),
            'driver_age' => $criteria['driver_age'] ?? null,
        ];
    }

    private function locationPayload(?VendorLocation $location, ?array $unifiedLocation): array
    {
        return [
            'id' => (string) ($unifiedLocation['unified_location_id'] ?? $location?->id),
            'unified_location_id' => $unifiedLocation['unified_location_id'] ?? null,
            'vendor_location_id' => $location?->id,
            'name' => $unifiedLocation['name'] ?? $location?->name,
            'iata' => $unifiedLocation['iata'] ?? $location?->iata_code,
            'city' => $unifiedLocation['city'] ?? $location?->city,
            'country' => $unifiedLocation['country'] ?? $location?->country,
            'country_code' => $unifiedLocation['country_code'] ?? $location?->country_code,
            'latitude' => (float) ($unifiedLocation['latitude'] ?? $location?->latitude ?? 0),
            'longitude' => (float) ($unifiedLocation['longitude'] ?? $location?->longitude ?? 0),
        ];
    }

    private function grossAmount(float $amount, float $markupRate): float
    {
        return round($amount * (1 + max(0, $markupRate)), 2);
    }

    private function normalizeCurrency(mixed $currency): string
    {
        $currency = strtoupper(trim((string) ($currency ?? '')));

        return $currency !== '' ? $currency : 'EUR';
    }

    private function rentalDays(array $searchPayload): int
    {
        $pickup = Carbon::parse($searchPayload['pickup_date_time']);
        $dropoff = Carbon::parse($searchPayload['dropoff_date_time']);

        if ($dropoff->lessThanOrEqualTo($pickup)) {
            return 1;
        }

        return max(1, (int) ceil($pickup->diffInMinutes($dropoff) / 1440));
    }
}
