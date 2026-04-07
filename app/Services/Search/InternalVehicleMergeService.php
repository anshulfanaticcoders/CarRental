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
            $filtered = $internalVehicles
                ->filter(fn ($vehicle) => $this->vehicleLocationHash((array) $vehicle) === $locationHash);
            if ($filtered->isNotEmpty()) {
                return $filtered->values();
            }
        }

        // Fallback: if hash matching failed or returned empty, include all internal
        // vehicles that were already location-filtered by the DB query in SearchController.
        return $internalVehicles->values();
    }

    private function extractInternalLocationHash(?array $matchedLocation): ?string
    {
        $internalLocationId = $this->normalizeTextValue(
            $matchedLocation['our_location_id'] ?? '',
            ['our_location_id', 'id', 'value']
        );
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
            $this->normalizeTextValue($vehicle['city'] ?? ($legacyPayload['city'] ?? ''), ['city', 'name', 'label', 'value'])
            . $this->normalizeTextValue($vehicle['state'] ?? ($legacyPayload['state'] ?? ''), ['state', 'name', 'label', 'value'])
            . $this->normalizeTextValue($vehicle['country'] ?? ($legacyPayload['country'] ?? ''), ['country', 'name', 'label', 'value', 'code'])
            . $this->normalizeTextValue($legacyPayload['location'] ?? ($vehicle['location'] ?? ''), ['location', 'name', 'address', 'formatted_address', 'label', 'value'])
        );
    }

    private function normalizeTextValue(mixed $value, array $preferredKeys = []): string
    {
        if (is_string($value) || is_numeric($value)) {
            return trim((string) $value);
        }

        if (!is_array($value)) {
            return '';
        }

        foreach ($preferredKeys as $key) {
            if (!array_key_exists($key, $value)) {
                continue;
            }

            $normalized = $this->normalizeTextValue($value[$key], $preferredKeys);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        foreach ($value as $item) {
            $normalized = $this->normalizeTextValue($item, $preferredKeys);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return '';
    }
}
