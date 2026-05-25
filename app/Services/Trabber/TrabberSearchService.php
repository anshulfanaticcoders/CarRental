<?php

namespace App\Services\Trabber;

use App\Models\Vehicle;
use App\Models\VendorLocation;
use App\Services\Search\InternalSearchVehicleFactory;
use App\Services\Vehicles\InternalVehicleAvailabilityService;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TrabberSearchService
{
    public function __construct(
        private readonly TrabberLocationResolver $locationResolver,
        private readonly InternalVehicleAvailabilityService $availabilityService,
        private readonly InternalSearchVehicleFactory $vehicleFactory,
        private readonly TrabberGatewayInventoryService $gatewayInventoryService,
        private readonly TrabberOfferStoreService $offerStore
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

        $offers = [];
        $searchPayload = $this->searchPayload(
            $criteria,
            $currency,
            $pickupLocation,
            $dropoffLocation,
            $pickupUnifiedLocation,
            $dropoffUnifiedLocation
        );

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
                    ->map(fn (Vehicle $vehicle) => $this->mapInternalOffer($vehicle, $pickupLocation, $dropoffLocation, $rentalDays, $currency, $searchPayload))
                    ->values()
                    ->all()
            );
        }

        if (in_array($scope, ['providers', 'mixed'], true) && $pickupUnifiedLocation) {
            $offers = array_merge(
                $offers,
                collect($this->gatewayInventoryService->search($criteria, $pickupUnifiedLocation, $dropoffUnifiedLocation))
                    ->map(fn (array $vehicle) => $this->mapVehicleOffer($vehicle, $currency, $searchPayload))
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
        array $searchPayload
    ): array {
        $internalVehicle = $this->vehicleFactory->make($vehicle->toArray(), $rentalDays, [
            'pickup_location_id' => (string) $pickupLocation->id,
            'dropoff_location_id' => (string) $dropoffLocation->id,
        ]);

        return $this->mapVehicleOffer($internalVehicle, $currency, $searchPayload);
    }

    private function mapVehicleOffer(array $vehicle, string $currency, array $searchPayload): array
    {
        $offerId = (string) Str::uuid();
        $price = (float) (
            Arr::get($vehicle, 'pricing.total_price')
            ?? $vehicle['total_price']
            ?? $vehicle['total']
            ?? 0
        );
        $vehicleName = $vehicle['display_name'] ?? trim((string) (($vehicle['brand'] ?? '').' '.($vehicle['model'] ?? '')));
        $supplierName = Arr::get($vehicle, 'supplier.name')
            ?? $vehicle['supplier_name']
            ?? $vehicle['source']
            ?? 'Vrooem';

        $offer = [
            'offer_id' => $offerId,
            'vehicle_name' => $vehicleName,
            'supplier_name' => $supplierName,
            'sipp' => Arr::get($vehicle, 'specs.sipp_code') ?: ($vehicle['sipp_code'] ?? null),
            'price' => round($price, 2),
            'currency' => Arr::get($vehicle, 'pricing.currency') ?: ($vehicle['currency'] ?? $currency),
            'image_url' => $vehicle['image'] ?? $vehicle['image_url'] ?? null,
            'inclusions' => $this->resolveInclusions($vehicle),
            'fuel_policy' => Arr::get($vehicle, 'policies.fuel_policy') ?: ($vehicle['fuel_policy'] ?? Arr::get($vehicle, 'benefits.fuel_policy')),
            'mileage_policy' => Arr::get($vehicle, 'policies.mileage_policy') ?: ($vehicle['mileage'] ?? null),
            'cancellation_policy' => Arr::get($vehicle, 'policies.cancellation') ?: ($vehicle['cancellation'] ?? null),
            'deeplink_url' => route('trabber.redirect', ['offer_id' => $offerId]),
        ];

        $this->offerStore->put($offerId, [
            'offer' => $offer,
            'vehicle' => $vehicle,
            'search' => $searchPayload,
            'created_at' => now('UTC')->toIso8601String(),
            'expires_at' => now('UTC')->addMinutes((int) config('trabber.offer_ttl_minutes', 60))->toIso8601String(),
        ]);

        return $offer;
    }

    private function resolveInclusions(array $internalVehicle): array
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

        return array_values(array_unique($inclusions));
    }

    private function deduplicateOffers(array $offers): Collection
    {
        return collect($offers)
            ->unique(function (array $offer): string {
                return strtolower(implode('|', [
                    $offer['supplier_name'] ?? '',
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
}
