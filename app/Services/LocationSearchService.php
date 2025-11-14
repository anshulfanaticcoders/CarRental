<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class LocationSearchService
{
    private $unifiedLocationsPath;

    public function __construct()
    {
        $this->unifiedLocationsPath = public_path('unified_locations.json');
    }

    /**
     * Normalize a string by removing diacritics and converting to lowercase.
     *
     * @param string|null $string
     * @return string
     */
    public function normalizeString($string)
    {
        if (is_null($string)) {
            return '';
        }

        // Convert to lowercase and remove diacritics
        $normalized = strtolower($string);
        $normalized = transliterator_transliterate('NFKD; [:Nonspacing Mark:] Remove; NFC;', $normalized);
        $normalized = preg_replace('/[^a-z0-9]/', '', $normalized); // Keep only alphanumeric

        return $normalized;
    }

    /**
     * Get all unified locations from the JSON file.
     *
     * @return array
     */
    public function getAllLocations(): array
    {
        if (!File::exists($this->unifiedLocationsPath)) {
            Log::warning('Unified locations file not found: ' . $this->unifiedLocationsPath);
            return [];
        }

        $jsonContent = File::get($this->unifiedLocationsPath);
        $locations = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Error decoding unified_locations.json: ' . json_last_error_msg());
            return [];
        }

        return $locations;
    }

    /**
     * Search unified locations based on a search term.
     *
     * @param string $searchTerm
     * @return array
     */
    public function searchLocations(string $searchTerm): array
    {
        if (strlen($searchTerm) < 2) { // Minimum 2 characters for search
            return [];
        }

        $normalizedSearchTerm = $this->normalizeString($searchTerm);
        $allLocations = $this->getAllLocations();

        $filteredLocations = collect($allLocations)->filter(function ($location) use ($normalizedSearchTerm) {
            $label = $this->normalizeString($location['label'] ?? '');
            $belowLabel = $this->normalizeString($location['below_label'] ?? '');
            $city = $this->normalizeString($location['city'] ?? '');
            $state = $this->normalizeString($location['state'] ?? '');
            $country = $this->normalizeString($location['country'] ?? '');

            return str_contains($label, $normalizedSearchTerm) ||
                   str_contains($belowLabel, $normalizedSearchTerm) ||
                   str_contains($city, $normalizedSearchTerm) ||
                   str_contains($state, $normalizedSearchTerm) ||
                   str_contains($country, $normalizedSearchTerm);
        })->values()->all();

        return $filteredLocations;
    }

    /**
     * Get location information by provider ID.
     *
     * @param string $providerId
     * @param string $provider
     * @return array|null
     */
    public function getLocationByProviderId(string $providerId, string $provider): ?array
    {
        $allLocations = $this->getAllLocations();

        $matchedLocation = collect($allLocations)->first(function ($location) use ($providerId, $provider) {
            if (!isset($location['providers'])) {
                return false;
            }

            return collect($location['providers'])->first(function ($providerInfo) use ($providerId, $provider) {
                return ($providerInfo['provider'] === $provider) &&
                       ($providerInfo['pickup_id'] === $providerId);
            });
        });

        return $matchedLocation ?: null;
    }
}
