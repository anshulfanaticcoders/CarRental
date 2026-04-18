<?php

namespace App\Http\Controllers;

use App\Services\LocationSearchService;
use App\Services\VrooemGatewayService;

class ProviderLocationController extends Controller
{
    public function __construct(
        private readonly LocationSearchService $locationSearchService,
        private readonly VrooemGatewayService $gateway,
    ) {}

    public function getDropoffLocationsForProvider(string $provider, string $location_id)
    {
        $pickupLocation = $this->locationSearchService->getLocationByProviderId($location_id, $provider);

        if (empty($pickupLocation)) {
            return response()->json(['locations' => []]);
        }

        $pickupProviderEntry = null;
        foreach ($pickupLocation['providers'] ?? [] as $entry) {
            if (($entry['provider'] ?? null) === $provider
                && (string) ($entry['pickup_id'] ?? '') === $location_id) {
                $pickupProviderEntry = $entry;
                break;
            }
        }

        // Prefer explicitly seeded dropoff IDs when a provider's sync populates them.
        $dropoffIds = $pickupProviderEntry['dropoffs'] ?? [];
        if (! empty($dropoffIds)) {
            $seeded = [];
            foreach ($dropoffIds as $dropoffId) {
                $location = $this->locationSearchService->getLocationByProviderId((string) $dropoffId, $provider);
                if (! empty($location)) {
                    $seeded[$location['unified_location_id']] = $location;
                }
            }

            return response()->json(['locations' => array_values($seeded)]);
        }

        // Fallback: live gateway lookup across all of this provider's locations in the same country.
        $unifiedId = (int) ($pickupLocation['unified_location_id'] ?? 0);
        $countryCode = $pickupLocation['country_code'] ?? null;

        if ($unifiedId <= 0) {
            return response()->json(['locations' => []]);
        }

        $candidates = $this->gateway->listDropoffCandidates(
            provider: $provider,
            pickupUnifiedId: $unifiedId,
            countryCode: $countryCode,
        );

        return response()->json(['locations' => array_values($candidates)]);
    }
}
