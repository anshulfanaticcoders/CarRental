<?php

namespace App\Services\Trabber;

use App\Services\GatewaySearchParamsBuilder;
use App\Services\Vehicles\GatewayVehicleTransformer;
use App\Services\VrooemGatewayService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TrabberGatewayInventoryService
{
    public function __construct(
        private readonly GatewaySearchParamsBuilder $paramsBuilder,
        private readonly VrooemGatewayService $gatewayService,
        private readonly GatewayVehicleTransformer $vehicleTransformer,
    ) {}

    public function search(array $criteria, array $pickupLocation, ?array $dropoffLocation): array
    {
        $validated = $this->normalizeCriteria($criteria, $pickupLocation, $dropoffLocation);
        if (($validated['unified_location_id'] ?? 0) <= 0) {
            return [];
        }

        try {
            $params = $this->paramsBuilder->build($validated);
            $result = $this->gatewayService->searchVehicles($params);
        } catch (\Throwable $e) {
            Log::warning('Trabber gateway inventory search failed', [
                'error' => $e->getMessage(),
                'unified_location_id' => $validated['unified_location_id'] ?? null,
            ]);

            return [];
        }

        $rentalDays = $this->calculateRentalDays($criteria);

        return collect($result['vehicles'] ?? [])
            ->filter(fn ($vehicle) => is_array($vehicle))
            ->map(function (array $vehicle) use ($rentalDays) {
                try {
                    return $this->vehicleTransformer->transform($vehicle, $rentalDays);
                } catch (\Throwable $e) {
                    Log::warning('Trabber gateway vehicle transform failed', [
                        'vehicle_id' => $vehicle['id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);

                    return null;
                }
            })
            ->filter(fn ($vehicle) => is_array($vehicle) && $this->shouldIncludeVehicle($vehicle))
            ->values()
            ->all();
    }

    private function normalizeCriteria(array $criteria, array $pickupLocation, ?array $dropoffLocation): array
    {
        $pickupDateTime = Carbon::parse($criteria['pickup_date_time']);
        $dropoffDateTime = Carbon::parse($criteria['dropoff_date_time']);

        return array_filter([
            'unified_location_id' => (int) ($pickupLocation['unified_location_id'] ?? 0),
            'dropoff_unified_location_id' => (int) ($dropoffLocation['unified_location_id'] ?? ($pickupLocation['unified_location_id'] ?? 0)),
            'where' => $pickupLocation['name'] ?? null,
            'location' => $pickupLocation['name'] ?? null,
            'city' => $pickupLocation['city'] ?? null,
            'country' => $pickupLocation['country'] ?? null,
            'latitude' => $pickupLocation['latitude'] ?? null,
            'longitude' => $pickupLocation['longitude'] ?? null,
            'date_from' => $pickupDateTime->toDateString(),
            'date_to' => $dropoffDateTime->toDateString(),
            'start_time' => $pickupDateTime->format('H:i'),
            'end_time' => $dropoffDateTime->format('H:i'),
            'age' => $criteria['driver_age'] ?? 30,
            'currency' => strtoupper((string) ($criteria['currency'] ?? config('trabber.default_currency', 'EUR'))),
            'provider' => 'mixed',
        ], static fn ($value) => $value !== null && $value !== '' && $value !== 0);
    }

    private function calculateRentalDays(array $criteria): int
    {
        $pickup = Carbon::parse($criteria['pickup_date_time']);
        $dropoff = Carbon::parse($criteria['dropoff_date_time']);

        if ($dropoff->lessThanOrEqualTo($pickup)) {
            return 1;
        }

        return max(1, (int) ceil($pickup->diffInMinutes($dropoff) / 1440));
    }

    private function shouldIncludeVehicle(array $vehicle): bool
    {
        $price = (float) ($vehicle['total_price'] ?? data_get($vehicle, 'pricing.total_price', 0));
        if ($price <= 0) {
            return false;
        }

        $source = $this->normalizeProvider($vehicle['source'] ?? $vehicle['provider_code'] ?? data_get($vehicle, 'supplier.code'));
        if ($source === null || $source === 'internal') {
            return false;
        }

        return $this->whitelistedProviders()->isEmpty()
            || $this->whitelistedProviders()->contains($source);
    }

    private function whitelistedProviders(): Collection
    {
        return collect(config('trabber.provider_whitelist', []))
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
