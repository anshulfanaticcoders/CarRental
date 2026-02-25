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
            $backupPath = $this->unifiedLocationsPath . '.bak';
            if (File::exists($backupPath)) {
                $backupContent = File::get($backupPath);
                $locations = json_decode($backupContent, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    Log::warning('Loaded unified locations from backup file.');
                    return $locations;
                }
            }
            return [];
        }

        return $locations;
    }

    /**
     * Search unified locations based on a search term with scoring and limit.
     *
     * @param string $searchTerm
     * @param int $limit
     * @return array
     */
    public function searchLocations(string $searchTerm, int $limit = 20): array
    {
        if (strlen($searchTerm) < 2) {
            return [];
        }

        $normalizedSearchTerm = $this->normalizeString($searchTerm);
        $allLocations = $this->getAllLocations();
        $limit = min($limit, 50);

        $scored = [];

        foreach ($allLocations as $location) {
            $label = $this->normalizeString($location['name'] ?? ($location['label'] ?? ''));
            $city = $this->normalizeString($location['city'] ?? '');
            $country = $this->normalizeString($location['country'] ?? '');
            $iata = $this->normalizeString($location['iata'] ?? '');

            $score = 0;

            // IATA exact match (highest priority - user typed airport code)
            if ($iata !== '' && $iata === $normalizedSearchTerm) {
                $score = 100;
            }
            // Exact name match
            elseif ($label === $normalizedSearchTerm) {
                $score = 90;
            }
            // Name starts with search term
            elseif (str_starts_with($label, $normalizedSearchTerm)) {
                $score = 80;
            }
            // City exact match
            elseif ($city === $normalizedSearchTerm) {
                $score = 70;
            }
            // City starts with search term
            elseif (str_starts_with($city, $normalizedSearchTerm)) {
                $score = 60;
            }
            // Name contains search term
            elseif (str_contains($label, $normalizedSearchTerm)) {
                $score = 50;
            }
            // City contains search term
            elseif (str_contains($city, $normalizedSearchTerm)) {
                $score = 40;
            }
            // Country match
            elseif (str_contains($country, $normalizedSearchTerm)) {
                $score = 20;
            }
            // Alias match
            else {
                foreach ($location['aliases'] ?? [] as $alias) {
                    if (str_contains($this->normalizeString($alias), $normalizedSearchTerm)) {
                        $score = 30;
                        break;
                    }
                }
            }

            // Provider original_name match (fallback)
            if ($score === 0 && !empty($location['providers'])) {
                foreach ($location['providers'] as $provider) {
                    $originalName = $provider['original_name'] ?? '';
                    if ($originalName !== '' && str_contains($this->normalizeString($originalName), $normalizedSearchTerm)) {
                        $score = 25;
                        break;
                    }
                }
            }

            if ($score > 0) {
                // Boost locations with more providers (more useful to users)
                $providerCount = count($location['providers'] ?? []);
                $score += min($providerCount, 5);

                $scored[] = ['location' => $location, 'score' => $score];
            }
        }

        // Sort by score descending, then by name alphabetically
        usort($scored, function ($a, $b) {
            if ($a['score'] !== $b['score']) {
                return $b['score'] - $a['score'];
            }
            return strcmp($a['location']['name'] ?? '', $b['location']['name'] ?? '');
        });

        return array_map(fn($item) => $item['location'], array_slice($scored, 0, $limit));
    }

    /**
     * Get a single location by its unified_location_id.
     */
    public function getLocationByUnifiedId(int $unifiedLocationId): ?array
    {
        $allLocations = $this->getAllLocations();

        return collect($allLocations)->first(function ($location) use ($unifiedLocationId) {
            return ($location['unified_location_id'] ?? null) == $unifiedLocationId;
        });
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
