<?php

namespace App\Services\Search;

use App\Services\LocationSearchService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SearchOrchestratorService
{
    // Providers whose API supports one-way rentals (different pickup and dropoff locations).
    // Source of truth: vrooem-gateway adapters where `supports_one_way = True`.
    // Names here match unified_locations.json public provider IDs (see gateway registry.py _PROVIDER_ALIASES).
    // When adding/removing adapters in the gateway, update this list to match.
    private const ONE_WAY_PROVIDERS = [
        'greenmotion',
        'usave',
        'adobe',
        'click2rent',
        'easirent',
        'locauto_rent',
        'recordgo',
        'renteon',
        'surprice',
        'sicily_by_car',
    ];

    public function __construct(private LocationSearchService $locationSearchService) {}

    public function resolveProviderEntries(array $validated): array
    {
        $providerName = $validated['provider'] ?? 'mixed';
        $providerName = $providerName ?: 'mixed';
        $locationLat = $validated['latitude'] ?? null;
        $locationLng = $validated['longitude'] ?? null;
        $locationAddress = $validated['where'] ?? null;

        $errors = [];
        $unifiedLocationId = $validated['unified_location_id'] ?? null;
        $matchedLocation = $this->locationSearchService->resolveSearchLocation($validated);

        if (! $matchedLocation) {
            Log::warning('Unified location not found for search.', [
                'unified_location_id' => $unifiedLocationId,
                'provider_pickup_id' => $validated['provider_pickup_id'] ?? null,
            ]);

            return [
                'providerName' => $providerName,
                'matchedLocation' => null,
                'providerEntries' => [],
                'locationLat' => $locationLat,
                'locationLng' => $locationLng,
                'locationAddress' => $locationAddress,
                'errors' => $errors,
            ];
        }

        $providerEntries = [];
        if (! empty($matchedLocation['providers'])) {
            $providerEntriesSource = $matchedLocation['providers'];

            if ($providerName !== 'mixed') {
                $providerEntriesSource = array_values(array_filter($providerEntriesSource, function ($provider) use ($providerName) {
                    return ($provider['provider'] ?? null) === $providerName;
                }));
            }

            foreach ($providerEntriesSource as $provider) {
                $providerEntries[] = [
                    'provider' => $provider['provider'],
                    'pickup_id' => $provider['pickup_id'],
                    'original_name' => $provider['original_name'] ?? $matchedLocation['name'] ?? $locationAddress,
                    'latitude' => $provider['latitude'] ?? null,
                    'longitude' => $provider['longitude'] ?? null,
                ];
            }
        }

        // One-way filtering: when dropoff differs from pickup, remove non-one-way providers
        // AND providers that have no presence at the dropoff. Without the dropoff-presence
        // check, the gateway silently falls back to a round-trip quote for those adapters,
        // producing misleading "one-way" results and no dropoff coordinates downstream.
        $dropoffUnifiedId = $validated['dropoff_unified_location_id'] ?? null;
        $pickupUnifiedId = $matchedLocation['unified_location_id'] ?? null;
        $isOneWay = ! empty($dropoffUnifiedId) && (string) $dropoffUnifiedId !== (string) $pickupUnifiedId;

        if ($isOneWay && $providerName === 'mixed') {
            $dropoffProviders = $this->resolveDropoffProviderSet((int) $dropoffUnifiedId);

            $providerEntries = array_values(array_filter($providerEntries, function ($entry) use ($dropoffProviders) {
                $provider = strtolower(trim((string) ($entry['provider'] ?? '')));
                if (! in_array($provider, self::ONE_WAY_PROVIDERS, true)) {
                    return false;
                }
                // If we could not resolve the dropoff location, keep the adapter-level
                // filter only. The gateway will still discard providers that genuinely
                // cannot fulfill the route.
                if ($dropoffProviders === null) {
                    return true;
                }

                return in_array($provider, $dropoffProviders, true);
            }));
        }

        $locationLat = $matchedLocation['latitude'] ?? $locationLat;
        $locationLng = $matchedLocation['longitude'] ?? $locationLng;
        $locationAddress = $matchedLocation['name'] ?? $locationAddress;

        return [
            'providerName' => $providerName,
            'matchedLocation' => $matchedLocation,
            'providerEntries' => $providerEntries,
            'locationLat' => $locationLat,
            'locationLng' => $locationLng,
            'locationAddress' => $locationAddress,
            'isOneWay' => $isOneWay,
            'errors' => $errors,
        ];
    }

    /**
     * Resolve the set of providers that serve the given dropoff location.
     * Returns null when the dropoff cannot be resolved — callers should fall
     * back to provider-level filtering only.
     *
     * @return list<string>|null
     */
    private function resolveDropoffProviderSet(int $dropoffUnifiedId): ?array
    {
        if ($dropoffUnifiedId <= 0) {
            return null;
        }

        $dropoffLocation = $this->locationSearchService->getLocationByUnifiedId($dropoffUnifiedId);
        if (empty($dropoffLocation) || empty($dropoffLocation['providers'])) {
            return null;
        }

        $providers = [];
        foreach ($dropoffLocation['providers'] as $provider) {
            $id = strtolower(trim((string) ($provider['provider'] ?? '')));
            if ($id !== '') {
                $providers[] = $id;
            }
        }

        return array_values(array_unique($providers));
    }

    public function filterGatewayVehiclesForRequestedProvider(Collection $vehicles, array $validated): Collection
    {
        $providerName = strtolower(trim((string) ($validated['provider'] ?? 'mixed')));

        if ($providerName === '' || $providerName === 'mixed') {
            return $vehicles->values();
        }

        if ($providerName === 'internal') {
            return collect();
        }

        return $vehicles
            ->filter(function ($vehicle) use ($providerName) {
                $source = is_array($vehicle)
                    ? strtolower(trim((string) ($vehicle['source'] ?? '')))
                    : strtolower(trim((string) ($vehicle->source ?? '')));

                return $source === $providerName;
            })
            ->values();
    }
}
