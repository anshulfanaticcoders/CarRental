<?php

namespace App\Services\Search;

use Illuminate\Support\Collection;

class InternalVehicleMergeService
{
    public function forGatewayMerge(
        Collection $internalVehicles,
        array $validated,
        ?array $matchedLocation,
        bool $isOneWay
    ): Collection {
        if ($isOneWay) {
            return collect();
        }

        $provider = (string) ($validated['provider'] ?? 'mixed');
        if ($provider !== '' && $provider !== 'mixed' && $provider !== 'internal') {
            return collect();
        }

        $locationHash = $this->extractInternalLocationHash($matchedLocation);
        if ($locationHash !== null) {
            return $internalVehicles
                ->filter(fn ($vehicle) => $this->vehicleLocationHash((array) $vehicle) === $locationHash)
                ->values();
        }

        // Fallback: no our_location_id — filter by city match against the search location.
        $searchCity = strtolower(trim($matchedLocation['city'] ?? ($validated['city'] ?? '')));
        $searchCountry = strtolower(trim($matchedLocation['country'] ?? ($validated['country'] ?? '')));

        if (!$searchCity && !$searchCountry) {
            return $internalVehicles->values();
        }

        return $internalVehicles->filter(function ($vehicle) use ($searchCity, $searchCountry) {
            $v = (array) $vehicle;
            $legacyPayload = $v['booking_context']['provider_payload'] ?? [];
            $vCity = strtolower(trim($v['city'] ?? ($legacyPayload['city'] ?? '')));
            $vCountry = strtolower(trim($v['country'] ?? ($legacyPayload['country'] ?? '')));

            if ($searchCity && $vCity) {
                return $vCity === $searchCity;
            }
            if ($searchCountry && $vCountry) {
                return $vCountry === $searchCountry;
            }
            return false;
        })->values();
    }

    private function extractInternalLocationHash(?array $matchedLocation): ?string
    {
        $internalLocationId = (string) ($matchedLocation['our_location_id'] ?? '');
        if ($internalLocationId === '') {
            return null;
        }

        $hash = preg_replace('/^internal_/i', '', $internalLocationId);
        if (!is_string($hash) || !preg_match('/^[a-f0-9]{32}$/i', $hash)) {
            return null;
        }

        return strtolower($hash);
    }

    private function vehicleLocationHash(array $vehicle): string
    {
        $legacyPayload = $vehicle['booking_context']['provider_payload'] ?? [];

        return md5(
            (string) ($vehicle['city'] ?? ($legacyPayload['city'] ?? ''))
            . (string) ($vehicle['state'] ?? ($legacyPayload['state'] ?? ''))
            . (string) ($vehicle['country'] ?? ($legacyPayload['country'] ?? ''))
            . (string) ($vehicle['location'] ?? ($legacyPayload['location'] ?? ''))
        );
    }
}
