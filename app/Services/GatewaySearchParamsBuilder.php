<?php

namespace App\Services;

class GatewaySearchParamsBuilder
{
    private LocationSearchService $locationSearchService;

    public function __construct(LocationSearchService $locationSearchService)
    {
        $this->locationSearchService = $locationSearchService;
    }

    public function build(array $validated): array
    {
        // Resolve first so stale web URLs can recover from an old unified_location_id
        // using the visible search text before we call the provider gateway.
        $location = $this->locationSearchService->resolveSearchLocation($validated);
        $resolvedUnifiedLocationId = $location['unified_location_id'] ?? $validated['unified_location_id'];

        $params = [
            'unified_location_id' => $resolvedUnifiedLocationId,
            'pickup_date' => $validated['date_from'] ?? now()->addDays(1)->format('Y-m-d'),
            'dropoff_date' => $validated['date_to'] ?? now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => $validated['start_time'] ?? '09:00',
            'dropoff_time' => $validated['end_time'] ?? '09:00',
            'driver_age' => $validated['age'] ?? 30,
        ];

        if (! empty($validated['dropoff_unified_location_id'])) {
            $requestedPickupId = (int) ($validated['unified_location_id'] ?? 0);
            $requestedDropoffId = (int) $validated['dropoff_unified_location_id'];

            $params['dropoff_unified_location_id'] = $requestedDropoffId === $requestedPickupId
                ? $resolvedUnifiedLocationId
                : $validated['dropoff_unified_location_id'];
        }

        // Pass provider location mappings from the JSON file so the gateway
        // doesn't need to look them up in its own database.
        if ($location && ! empty($location['providers'])) {
            $providerLocations = $location['providers'];
            $requestedProvider = strtolower(trim((string) ($validated['provider'] ?? 'mixed')));
            $requestedPickupId = trim((string) ($validated['provider_pickup_id'] ?? ''));

            if ($requestedProvider !== '' && $requestedProvider !== 'mixed') {
                $providerLocations = array_values(array_filter($providerLocations, function (array $providerLocation) use ($requestedProvider) {
                    return strtolower(trim((string) ($providerLocation['provider'] ?? ''))) === $requestedProvider;
                }));
            }

            if ($requestedProvider !== '' && $requestedProvider !== 'mixed' && $requestedPickupId !== '') {
                $matchingPickupLocations = array_values(array_filter($providerLocations, function (array $providerLocation) use ($requestedPickupId) {
                    return trim((string) ($providerLocation['pickup_id'] ?? '')) === $requestedPickupId;
                }));

                if (! empty($matchingPickupLocations)) {
                    $providerLocations = $matchingPickupLocations;
                }
            }

            // Exclude 'internal' provider — internal vehicles are already queried directly from MySQL
            $providerLocations = array_values(array_filter($providerLocations, function (array $pl) {
                return strtolower(trim($pl['provider'] ?? '')) !== 'internal';
            }));

            $params['provider_locations'] = $providerLocations;
            $params['country_code'] = $location['country_code'] ?? null;
        }

        return $params;
    }
}
