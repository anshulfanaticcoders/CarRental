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
        $dropoffUnifiedId = $validated['dropoff_unified_location_id'] ?? null;
        $pickupUnifiedId = $matchedLocation['unified_location_id'] ?? null;
        $isOneWay = ! empty($dropoffUnifiedId) && (string) $dropoffUnifiedId !== (string) $pickupUnifiedId;

        if ($isOneWay && $providerName === 'mixed') {
            $providerEntries = array_values(array_filter($providerEntries, function ($entry) {
                return in_array($entry['provider'], self::ONE_WAY_PROVIDERS, true);
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
