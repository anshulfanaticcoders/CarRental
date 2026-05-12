<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Services\LocationSearchService;
use App\Services\VrooemGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(
        private LocationSearchService $locationSearchService,
        private VrooemGatewayService $gateway,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'min:2', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $q = $validated['q'] ?? null;
        $limit = $validated['limit'] ?? 15;

        $locations = $q
            ? $this->locationSearchService->searchLocations($q, $limit)
            : $this->locationSearchService->getAllLocations($limit);

        return response()->json([
            'results' => array_map(fn ($l) => $this->transform($l), $locations),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $location = $this->locationSearchService->getLocationByUnifiedId($id);

        if (! $location) {
            return response()->json(['message' => 'Location not found.'], 404);
        }

        return response()->json(['location' => $this->transform($location)]);
    }

    public function dropoffsFor(int $id): JsonResponse
    {
        $pickup = $this->locationSearchService->getLocationByUnifiedId($id);

        if (! $pickup) {
            return response()->json(['results' => []]);
        }

        $countryCode = $pickup['country_code'] ?? null;
        $candidates = [];

        foreach ($pickup['providers'] ?? [] as $entry) {
            $provider = $entry['provider'] ?? null;
            if (! is_string($provider) || $provider === '') {
                continue;
            }

            $seededIds = is_array($entry['dropoffs'] ?? null) ? $entry['dropoffs'] : [];
            if (! empty($seededIds)) {
                foreach ($seededIds as $dropoffId) {
                    $loc = $this->locationSearchService->getLocationByProviderId((string) $dropoffId, $provider);
                    if (is_array($loc) && ! empty($loc['unified_location_id'])) {
                        $candidates[(int) $loc['unified_location_id']] = $loc;
                    }
                }
                continue;
            }

            $list = $this->gateway->listDropoffCandidates(
                provider: $provider,
                pickupUnifiedId: $id,
                countryCode: is_string($countryCode) ? $countryCode : null,
            );
            foreach ($list as $loc) {
                if (! is_array($loc)) {
                    continue;
                }
                $uid = (int) ($loc['unified_location_id'] ?? 0);
                if ($uid > 0 && ! isset($candidates[$uid])) {
                    $candidates[$uid] = $loc;
                }
            }
        }

        unset($candidates[$id]);

        return response()->json([
            'results' => array_values(array_map(fn ($l) => $this->transform($l), $candidates)),
        ]);
    }

    private function transform(array $l): array
    {
        return [
            'unified_location_id' => $l['unified_location_id'] ?? null,
            'name' => $l['name'] ?? null,
            'address' => $l['address'] ?? null,
            'city' => $l['city'] ?? null,
            'state' => $l['state'] ?? null,
            'country' => $l['country'] ?? null,
            'country_code' => $l['country_code'] ?? null,
            'iata' => $l['iata'] ?? null,
            'latitude' => $l['latitude'] ?? null,
            'longitude' => $l['longitude'] ?? null,
            'location_type' => $l['location_type'] ?? 'unknown',
        ];
    }
}
