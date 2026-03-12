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
        if ($locationHash === null) {
            return collect();
        }

        return $internalVehicles
            ->filter(fn ($vehicle) => $this->vehicleLocationHash((array) $vehicle) === $locationHash)
            ->values();
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
        return md5(
            (string) ($vehicle['city'] ?? '')
            . (string) ($vehicle['state'] ?? '')
            . (string) ($vehicle['country'] ?? '')
            . (string) ($vehicle['location'] ?? '')
        );
    }
}
