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
        $params = [
            'unified_location_id' => $validated['unified_location_id'],
            'pickup_date' => $validated['date_from'] ?? now()->addDays(1)->format('Y-m-d'),
            'dropoff_date' => $validated['date_to'] ?? now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => $validated['start_time'] ?? '09:00',
            'dropoff_time' => $validated['end_time'] ?? '09:00',
            'driver_age' => $validated['age'] ?? 30,
        ];

        if (!empty($validated['dropoff_unified_location_id'])) {
            $params['dropoff_unified_location_id'] = $validated['dropoff_unified_location_id'];
        }

        // Pass provider location mappings from the JSON file so the gateway
        // doesn't need to look them up in its own database.
        $location = $this->locationSearchService->resolveSearchLocation($validated);
        if ($location && !empty($location['providers'])) {
            $providerLocations = $location['providers'];
            $requestedProvider = strtolower(trim((string) ($validated['provider'] ?? 'mixed')));

            if ($requestedProvider !== '' && $requestedProvider !== 'mixed') {
                $providerLocations = array_values(array_filter($providerLocations, function (array $providerLocation) use ($requestedProvider) {
                    return strtolower(trim((string) ($providerLocation['provider'] ?? ''))) === $requestedProvider;
                }));
            }

            $params['provider_locations'] = $providerLocations;
            $params['country_code'] = $location['country_code'] ?? null;
        }

        return $params;
    }
}
