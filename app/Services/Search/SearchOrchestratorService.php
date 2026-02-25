<?php

namespace App\Services\Search;

use Illuminate\Support\Facades\Log;

class SearchOrchestratorService
{
    public function resolveProviderEntries(array $validated): array
    {
        $providerName = $validated['provider'] ?? 'mixed';
        $providerName = $providerName ?: 'mixed';
        $locationLat = $validated['latitude'] ?? null;
        $locationLng = $validated['longitude'] ?? null;
        $locationAddress = $validated['where'] ?? null;

        $errors = [];
        $filePath = public_path('unified_locations.json');
        if (!file_exists($filePath)) {
            Log::error('Unified locations file missing.', ['path' => $filePath]);
            $errors[] = 'unified_locations_missing';
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

        $raw = file_get_contents($filePath);
        $allLocations = json_decode($raw, true);
        if (!is_array($allLocations)) {
            Log::error('Unified locations JSON invalid.', ['path' => $filePath]);
            $errors[] = 'unified_locations_invalid';
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

        $unifiedLocationId = $validated['unified_location_id'] ?? null;
        $matchedLocation = null;

        if ($unifiedLocationId) {
            $matchedLocation = collect($allLocations)->first(function ($location) use ($unifiedLocationId) {
                return ($location['unified_location_id'] ?? null) == $unifiedLocationId;
            });
        }

        if (!$matchedLocation) {
            Log::warning('Unified location not found for search.', [
                'unified_location_id' => $unifiedLocationId,
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
        if (!empty($matchedLocation['providers'])) {
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
            'errors' => $errors,
        ];
    }
}
