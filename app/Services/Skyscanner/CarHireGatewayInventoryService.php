<?php

namespace App\Services\Skyscanner;

use App\Services\GatewaySearchParamsBuilder;
use App\Services\LocationSearchService;
use App\Services\VrooemGatewayService;
use App\Services\Vehicles\GatewayVehicleTransformer;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CarHireGatewayInventoryService
{
    public function __construct(
        private readonly LocationSearchService $locationSearchService,
        private readonly GatewaySearchParamsBuilder $gatewaySearchParamsBuilder,
        private readonly VrooemGatewayService $gatewayService,
        private readonly GatewayVehicleTransformer $gatewayVehicleTransformer,
    ) {
    }

    public function search(array $criteria): array
    {
        $validated = $this->normalizeCriteria($criteria);
        if (($validated['unified_location_id'] ?? 0) <= 0) {
            return [];
        }

        $location = $this->locationSearchService->resolveSearchLocation($validated);
        if (!is_array($location) || $location === []) {
            return [];
        }

        $params = $this->gatewaySearchParamsBuilder->build($validated);
        $result = $this->gatewayService->searchVehicles($params);
        $rentalDays = $this->calculateRentalDays($criteria);

        return collect($result['vehicles'] ?? [])
            ->filter(fn ($vehicle) => is_array($vehicle))
            ->map(fn (array $vehicle) => $this->gatewayVehicleTransformer->transform($vehicle, $rentalDays))
            ->filter(fn ($vehicle) => is_array($vehicle) && $this->shouldIncludeVehicle($vehicle))
            ->values()
            ->all();
    }

    private function normalizeCriteria(array $criteria): array
    {
        return array_filter([
            'unified_location_id' => (int) ($criteria['pickup_location_id'] ?? 0),
            'dropoff_unified_location_id' => (int) ($criteria['dropoff_location_id'] ?? 0),
            'date_from' => $criteria['pickup_date'] ?? null,
            'date_to' => $criteria['dropoff_date'] ?? null,
            'start_time' => $criteria['pickup_time'] ?? null,
            'end_time' => $criteria['dropoff_time'] ?? null,
            'age' => $criteria['driver_age'] ?? null,
            'currency' => strtoupper((string) ($criteria['currency'] ?? 'EUR')),
            'provider' => 'mixed',
        ], static fn ($value) => $value !== null && $value !== '' && $value !== 0);
    }

    private function calculateRentalDays(array $criteria): int
    {
        $pickupDate = trim((string) ($criteria['pickup_date'] ?? ''));
        $pickupTime = trim((string) ($criteria['pickup_time'] ?? '09:00'));
        $dropoffDate = trim((string) ($criteria['dropoff_date'] ?? ''));
        $dropoffTime = trim((string) ($criteria['dropoff_time'] ?? '09:00'));

        if ($pickupDate === '' || $dropoffDate === '') {
            return 1;
        }

        $pickup = Carbon::parse($pickupDate . ' ' . $pickupTime);
        $dropoff = Carbon::parse($dropoffDate . ' ' . $dropoffTime);

        if ($dropoff->lessThanOrEqualTo($pickup)) {
            return 1;
        }

        return max(1, (int) ceil($pickup->diffInMinutes($dropoff) / 1440));
    }

    private function shouldIncludeVehicle(array $vehicle): bool
    {
        $source = $this->normalizeProvider(
            $vehicle['source']
                ?? $vehicle['provider_code']
                ?? data_get($vehicle, 'supplier.code')
        );

        if ($source === null || $source === 'internal') {
            return false;
        }

        return $this->whitelistedProviders()->isEmpty()
            || $this->whitelistedProviders()->contains($source);
    }

    private function whitelistedProviders(): Collection
    {
        return collect(config('skyscanner.provider_whitelist', []))
            ->map(fn ($provider) => $this->normalizeProvider($provider))
            ->filter()
            ->values();
    }

    private function normalizeProvider(mixed $provider): ?string
    {
        $provider = strtolower(trim((string) ($provider ?? '')));

        return $provider === '' ? null : $provider;
    }
}
