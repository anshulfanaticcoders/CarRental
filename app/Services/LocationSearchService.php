<?php

namespace App\Services;

class LocationSearchService
{
    public function __construct(private VrooemGatewayService $gatewayService)
    {
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

        $normalized = strtolower($string);
        $normalized = transliterator_transliterate('NFKD; [:Nonspacing Mark:] Remove; NFC;', $normalized);
        $normalized = preg_replace('/[^a-z0-9]/', '', $normalized);

        return $normalized;
    }

    public function getAllLocations(int $limit = 50): array
    {
        return collect($this->gatewayService->listLocations($limit))
            ->filter(fn ($location) => is_array($location))
            ->map(fn (array $location): array => $this->normalizeLocationRecord($location))
            ->values()
            ->all();
    }

    /**
     * Search unified locations via the gateway.
     */
    public function searchLocations(string $searchTerm, int $limit = 20): array
    {
        if (strlen($searchTerm) < 2) {
            return [];
        }

        $response = $this->gatewayService->searchLocations($searchTerm, $limit);

        $locations = collect($response['results'] ?? [])
            ->map(fn ($result) => is_array($result) ? ($result['location'] ?? null) : null)
            ->filter(fn ($location) => is_array($location))
            ->map(fn (array $location): array => $this->normalizeLocationRecord($location))
            ->values()
            ->all();

        return $this->collapseEquivalentAirportLocations($locations);
    }

    /**
     * Get a single location by its unified_location_id.
     */
    public function getLocationByUnifiedId(int $unifiedLocationId): ?array
    {
        $location = $this->gatewayService->getLocation($unifiedLocationId);

        if (!is_array($location)) {
            return null;
        }

        return $this->augmentEquivalentAirportProviders(
            $this->normalizeLocationRecord($location)
        );
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
        $location = $this->gatewayService->getLocationByProvider($provider, $providerId);

        if (!is_array($location)) {
            return null;
        }

        return $this->augmentEquivalentAirportProviders(
            $this->normalizeLocationRecord($location)
        );
    }

    /**
     * Resolve a location for search flows. The unified gateway path should normally provide a
     * valid unified_location_id, but we keep provider pickup lookup as a narrow fallback.
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
        return collect($this->getAllLocations(250))->first(function ($location) use ($providerPickupId) {
            if (!isset($location['providers']) || !is_array($location['providers'])) {
                return false;
            }

            return collect($location['providers'])->contains(function ($providerInfo) use ($providerPickupId) {
                return (string) ($providerInfo['pickup_id'] ?? '') === $providerPickupId;
            });
        });
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
        $location['provider_count'] = count($location['providers']);

        return $location;
    }

    private function collapseEquivalentAirportLocations(array $locations): array
    {
        $collapsed = [];

        foreach ($locations as $location) {
            if (($location['location_type'] ?? null) !== 'airport') {
                $collapsed[] = $location;
                continue;
            }

            $matchedIndex = null;
            foreach ($collapsed as $index => $existing) {
                if ($this->airportsAreEquivalent($existing, $location)) {
                    $matchedIndex = $index;
                    break;
                }
            }

            if ($matchedIndex === null) {
                $collapsed[] = $location;
                continue;
            }

            $collapsed[$matchedIndex] = $this->mergeLocationRecords(
                $collapsed[$matchedIndex],
                $location
            );
        }

        return array_values($collapsed);
    }

    private function augmentEquivalentAirportProviders(array $location): array
    {
        if (($location['location_type'] ?? null) !== 'airport') {
            return $location;
        }

        foreach ($this->airportSearchTerms($location) as $term) {
            foreach ($this->searchLocations($term, 20) as $candidate) {
                if (!$this->airportsAreEquivalent($location, $candidate)) {
                    continue;
                }

                $location = $this->mergeLocationRecords($location, $candidate);
            }
        }

        return $location;
    }

    private function mergeLocationRecords(array $primary, array $secondary): array
    {
        $merged = $primary;

        if (($merged['city'] ?? null) === null && ($secondary['city'] ?? null) !== null) {
            $merged['city'] = $secondary['city'];
        }

        if (($merged['country'] ?? null) === null && ($secondary['country'] ?? null) !== null) {
            $merged['country'] = $secondary['country'];
        }

        if ((float) ($merged['latitude'] ?? 0.0) === 0.0 && isset($secondary['latitude'])) {
            $merged['latitude'] = (float) $secondary['latitude'];
        }

        if ((float) ($merged['longitude'] ?? 0.0) === 0.0 && isset($secondary['longitude'])) {
            $merged['longitude'] = (float) $secondary['longitude'];
        }

        $aliases = collect($merged['aliases'] ?? [])
            ->merge($secondary['aliases'] ?? [])
            ->when(
                !empty($secondary['name']) && $secondary['name'] !== ($merged['name'] ?? null),
                fn ($collection) => $collection->push($secondary['name'])
            )
            ->filter()
            ->unique()
            ->values()
            ->all();

        $providers = collect($merged['providers'] ?? [])
            ->merge($secondary['providers'] ?? [])
            ->filter(fn ($provider) => is_array($provider) && !empty($provider['provider']) && array_key_exists('pickup_id', $provider))
            ->unique(fn ($provider) => strtolower(trim((string) $provider['provider'])) . '|' . (string) $provider['pickup_id'])
            ->values()
            ->all();

        $merged['aliases'] = $aliases;
        $merged['providers'] = $providers;
        $merged['provider_count'] = count($providers);

        return $merged;
    }

    private function equivalentAirportKey(array $location): ?string
    {
        $iata = strtoupper(trim((string) ($location['iata'] ?? '')));
        if ($iata === '') {
            return null;
        }

        return 'iata:' . $iata;
    }

    private function airportsAreEquivalent(array $primary, array $secondary): bool
    {
        if (($primary['location_type'] ?? null) !== 'airport' || ($secondary['location_type'] ?? null) !== 'airport') {
            return false;
        }

        $primaryKey = $this->equivalentAirportKey($primary);
        $secondaryKey = $this->equivalentAirportKey($secondary);
        if ($primaryKey !== null && $primaryKey === $secondaryKey) {
            return true;
        }

        return !empty(array_intersect(
            $this->airportLocationSignatures($primary),
            $this->airportLocationSignatures($secondary)
        ));
    }

    private function airportSearchTerms(array $location): array
    {
        return collect([
            $location['iata'] ?? null,
            $location['name'] ?? null,
            $location['city'] ?? null,
        ])
            ->filter(fn ($term) => is_string($term) && trim($term) !== '')
            ->map(fn ($term) => trim($term))
            ->unique()
            ->values()
            ->all();
    }

    private function airportLocationSignatures(array $location): array
    {
        return collect([$location['name'] ?? null])
            ->merge($location['aliases'] ?? [])
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => $this->normalizeString($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
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
