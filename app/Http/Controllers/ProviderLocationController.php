<?php

namespace App\Http\Controllers;

use App\Services\LocationSearchService;
use Illuminate\Http\Request;

class ProviderLocationController extends Controller
{
    public function __construct(
        private readonly LocationSearchService $locationSearchService
    ) {
    }

    public function getDropoffLocationsForProvider(Request $request, string $provider, string $location_id)
    {
        $dropoffIds = [];
        $pickupLocation = $this->locationSearchService->getLocationByProviderId($location_id, $provider);

        foreach ($pickupLocation['providers'] ?? [] as $providerEntry) {
            if (($providerEntry['provider'] ?? null) === $provider
                && (string) ($providerEntry['pickup_id'] ?? '') === $location_id) {
                $dropoffIds = $providerEntry['dropoffs'] ?? [];
                break;
            }
        }

        if (empty($dropoffIds)) {
            return response()->json(['locations' => []]);
        }

        $dropoffLocations = [];
        foreach ($dropoffIds as $dropoffId) {
            $location = $this->locationSearchService->getLocationByProviderId((string) $dropoffId, $provider);
            if (empty($location)) {
                continue;
            }

            $dropoffLocations[$location['unified_location_id']] = $location;
        }

        return response()->json([
            'locations' => array_values($dropoffLocations),
        ]);
    }
}
