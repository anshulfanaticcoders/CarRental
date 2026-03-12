<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class LocationSearchService
{
    private $unifiedLocationsPath;
    private VrooemGatewayService $gatewayService;

    public function __construct(VrooemGatewayService $gatewayService)
    {
        $this->unifiedLocationsPath = public_path('unified_locations.json');
        $this->gatewayService = $gatewayService;
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
        if ($this->shouldUseGateway()) {
            return $this->gatewayService->listLocations();
        }

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

        if ($this->shouldUseGateway()) {
            $response = $this->gatewayService->searchLocations($searchTerm, $limit);

            return collect($response['results'] ?? [])
                ->map(fn ($result) => $result['location'] ?? null)
                ->filter()
                ->values()
                ->all();
        }

        $normalizedSearchTerm = $this->normalizeString($searchTerm);
        $allLocations = $this->getAllLocations();
        $limit = min($limit, 50);

        $scored = [];

        foreach ($allLocations as $location) {
            $location = $this->normalizeLocationRecord($location);
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

        $scored = $this->consolidateScoredResults($scored);

        return array_map(fn($item) => $item['location'], array_slice($scored, 0, $limit));
    }

    /**
     * Get a single location by its unified_location_id.
     */
    public function getLocationByUnifiedId(int $unifiedLocationId): ?array
    {
        if ($this->shouldUseGateway()) {
            return $this->gatewayService->getLocation($unifiedLocationId);
        }

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
        if ($this->shouldUseGateway()) {
            return $this->gatewayService->getLocationByProvider($provider, $providerId);
        }

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

    /**
     * Resolve a location for search flows, falling back to provider pickup IDs when a stale
     * unified_location_id comes from an older location source.
     */
    public function resolveSearchLocation(array $validated): ?array
    {
        $unifiedLocationId = (int) ($validated['unified_location_id'] ?? 0);
        if ($unifiedLocationId > 0) {
            $location = $this->getLocationByUnifiedId($unifiedLocationId);
            if ($location) {
                return $location;
            }
        }

        $providerPickupId = (string) ($validated['provider_pickup_id'] ?? '');
        if ($providerPickupId === '') {
            return null;
        }

        $provider = (string) ($validated['provider'] ?? '');
        if ($provider !== '' && $provider !== 'mixed') {
            $location = $this->getLocationByProviderId($providerPickupId, $provider);
            if ($location) {
                return $location;
            }
        }

        return $this->getLocationByAnyProviderPickupId($providerPickupId);
    }

    public function getLocationByAnyProviderPickupId(string $providerPickupId): ?array
    {
        $allLocations = $this->getAllLocations();

        $matchedLocation = collect($allLocations)->first(function ($location) use ($providerPickupId) {
            if (!isset($location['providers']) || !is_array($location['providers'])) {
                return false;
            }

            return collect($location['providers'])->contains(function ($providerInfo) use ($providerPickupId) {
                return (string) ($providerInfo['pickup_id'] ?? '') === $providerPickupId;
            });
        });

        return $matchedLocation ?: null;
    }

    private function shouldUseGateway(): bool
    {
        return (bool) config('vrooem.location_search_enabled')
            && !empty(config('vrooem.url'))
            && !empty(config('vrooem.api_key'));
    }

    private function consolidateScoredResults(array $scored): array
    {
        $groups = [];

        foreach ($scored as $item) {
            $location = $this->normalizeLocationRecord($item['location'] ?? []);
            $baseKey = $this->buildSearchResultBaseKey($location);
            if ($baseKey === '') {
                continue;
            }

            $groups[$baseKey][] = [
                'location' => $location,
                'score' => (int) ($item['score'] ?? 0),
            ];
        }

        $collapsed = [];
        foreach ($groups as $items) {
            $withIata = [];
            $withoutIata = [];

            foreach ($items as $item) {
                $iata = strtoupper(trim((string) ($item['location']['iata'] ?? '')));
                if ($iata !== '') {
                    $withIata[$iata][] = $item;
                } else {
                    $withoutIata[] = $item;
                }
            }

            $resolved = [];
            foreach ($withIata as $iataItems) {
                $resolved[] = $this->mergeScoredLocationGroup($iataItems);
            }

            if ($withoutIata !== []) {
                $mergedWithoutIata = $this->mergeScoredLocationGroup($withoutIata);

                if ($resolved === []) {
                    $resolved[] = $mergedWithoutIata;
                } else {
                    usort($resolved, fn (array $left, array $right): int => $this->compareScoredLocations($left, $right));
                    $resolved[0] = $this->mergeScoredLocations($resolved[0], $mergedWithoutIata);
                }
            }

            foreach ($resolved as $item) {
                $collapsed[] = $item;
            }
        }

        usort($collapsed, fn (array $left, array $right): int => $this->compareScoredLocations($left, $right));

        return $collapsed;
    }

    private function mergeScoredLocationGroup(array $items): array
    {
        usort($items, fn (array $left, array $right): int => $this->compareScoredLocations($left, $right));

        $merged = array_shift($items);
        foreach ($items as $item) {
            $merged = $this->mergeScoredLocations($merged, $item);
        }

        return $merged;
    }

    private function mergeScoredLocations(array $primary, array $secondary): array
    {
        $location = $this->mergeLocationRecords(
            $primary['location'] ?? [],
            $secondary['location'] ?? []
        );

        return [
            'location' => $location,
            'score' => max((int) ($primary['score'] ?? 0), (int) ($secondary['score'] ?? 0)),
        ];
    }

    private function mergeLocationRecords(array $primary, array $secondary): array
    {
        $merged = $primary;

        if (($merged['country'] ?? null) === null || $merged['country'] === '') {
            $merged['country'] = $secondary['country'] ?? null;
        }
        if (($merged['country_code'] ?? null) === null || $merged['country_code'] === '') {
            $merged['country_code'] = $secondary['country_code'] ?? null;
        }
        if (($merged['iata'] ?? null) === null || $merged['iata'] === '') {
            $merged['iata'] = $secondary['iata'] ?? null;
        }
        if (($merged['our_location_id'] ?? null) === null || $merged['our_location_id'] === '') {
            $merged['our_location_id'] = $secondary['our_location_id'] ?? null;
        }

        $aliases = array_merge($merged['aliases'] ?? [], $secondary['aliases'] ?? []);
        $merged['aliases'] = array_values(array_unique(array_filter($aliases)));

        $providers = $merged['providers'] ?? [];
        foreach ($secondary['providers'] ?? [] as $provider) {
            $exists = collect($providers)->contains(function ($existing) use ($provider) {
                return ($existing['provider'] ?? null) === ($provider['provider'] ?? null)
                    && (string) ($existing['pickup_id'] ?? '') === (string) ($provider['pickup_id'] ?? '');
            });

            if (!$exists) {
                $providers[] = $provider;
            }
        }
        $merged['providers'] = $providers;

        return $this->normalizeLocationRecord($merged);
    }

    private function compareScoredLocations(array $left, array $right): int
    {
        $leftLocation = $left['location'] ?? [];
        $rightLocation = $right['location'] ?? [];

        $leftHasIata = !empty($leftLocation['iata']);
        $rightHasIata = !empty($rightLocation['iata']);
        if ($leftHasIata !== $rightHasIata) {
            return $rightHasIata <=> $leftHasIata;
        }

        $leftProviders = count($leftLocation['providers'] ?? []);
        $rightProviders = count($rightLocation['providers'] ?? []);
        if ($leftProviders !== $rightProviders) {
            return $rightProviders <=> $leftProviders;
        }

        $leftScore = (int) ($left['score'] ?? 0);
        $rightScore = (int) ($right['score'] ?? 0);
        if ($leftScore !== $rightScore) {
            return $rightScore <=> $leftScore;
        }

        return strcmp((string) ($leftLocation['name'] ?? ''), (string) ($rightLocation['name'] ?? ''));
    }

    private function buildSearchResultBaseKey(array $location): string
    {
        $name = $this->normalizeString($location['name'] ?? ($location['label'] ?? ''));
        $city = $this->normalizeString($location['city'] ?? '');
        $type = $this->normalizeString($location['location_type'] ?? '');
        $country = $this->normalizeCountryKey($location);

        return implode('|', [$name, $city, $type, $country]);
    }

    private function normalizeLocationRecord(array $location): array
    {
        $location['unified_location_id'] = isset($location['unified_location_id'])
            ? (int) $location['unified_location_id']
            : null;
        $location['name'] = isset($location['name']) ? (string) $location['name'] : ($location['label'] ?? null);
        $location['city'] = isset($location['city']) ? (string) $location['city'] : null;
        $location['location_type'] = trim((string) ($location['location_type'] ?? '')) ?: 'unknown';
        $location['aliases'] = array_values(array_unique(array_filter($location['aliases'] ?? [])));
        $location['our_location_id'] = $location['our_location_id'] ?? null;
        $location['latitude'] = isset($location['latitude']) ? (float) $location['latitude'] : 0.0;
        $location['longitude'] = isset($location['longitude']) ? (float) $location['longitude'] : 0.0;

        $countryCode = strtoupper(trim((string) ($location['country_code'] ?? '')));
        if ($countryCode !== '') {
            $location['country_code'] = $countryCode;
            $countryName = $this->countryNameFromCode($countryCode);
            if ($countryName !== null) {
                $location['country'] = $countryName;
            }
        } else {
            $location['country_code'] = null;
        }

        $iata = strtoupper(trim((string) ($location['iata'] ?? '')));
        if ($iata !== '') {
            $location['iata'] = $iata;
        } else {
            $location['iata'] = null;
        }

        $location['providers'] = collect($location['providers'] ?? [])
            ->filter(fn ($provider) => is_array($provider) && !empty($provider['provider']) && array_key_exists('pickup_id', $provider))
            ->map(function (array $provider): array {
                return [
                    'provider' => strtolower(trim((string) $provider['provider'])),
                    'pickup_id' => (string) $provider['pickup_id'],
                    'original_name' => $provider['original_name'] ?? null,
                    'latitude' => isset($provider['latitude']) ? (float) $provider['latitude'] : null,
                    'longitude' => isset($provider['longitude']) ? (float) $provider['longitude'] : null,
                ];
            })
            ->values()
            ->all();

        return $location;
    }

    private function normalizeCountryKey(array $location): string
    {
        $countryCode = strtoupper(trim((string) ($location['country_code'] ?? '')));
        if ($countryCode !== '') {
            return $this->normalizeString($countryCode);
        }

        return $this->normalizeString($location['country'] ?? '');
    }

    private function countryNameFromCode(string $countryCode): ?string
    {
        $countryCode = strtoupper(trim($countryCode));
        if ($countryCode === '' || strlen($countryCode) !== 2) {
            return null;
        }

        $displayName = null;
        if (class_exists(\Locale::class)) {
            $displayName = \Locale::getDisplayRegion('-' . $countryCode, 'en');
            if (!is_string($displayName) || trim($displayName) === '' || strtoupper(trim($displayName)) === $countryCode) {
                $displayName = null;
            }
        }

        return $displayName ?: null;
    }
}
