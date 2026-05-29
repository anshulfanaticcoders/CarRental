<?php

namespace App\Services;

use App\Services\Search\InternalLocationResolver;

class LocationSearchService
{
    public function __construct(
        private VrooemGatewayService $gatewayService,
        private InternalLocationResolver $internalLocationResolver
    ) {}

    /**
     * Normalize a string by removing diacritics and converting to lowercase.
     *
     * @param  string|null  $string
     * @return string
     */
    public function normalizeString($string)
    {
        if (is_null($string)) {
            return '';
        }

        $normalized = strtolower($string);
        if (function_exists('transliterator_transliterate')) {
            $transliterated = transliterator_transliterate('NFKD; [:Nonspacing Mark:] Remove; NFC;', $normalized);
            if (is_string($transliterated)) {
                $normalized = $transliterated;
            }
        } elseif (function_exists('iconv')) {
            $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
            if (is_string($transliterated)) {
                $normalized = $transliterated;
            }
        }
        $normalized = preg_replace('/[^a-z0-9]/', '', $normalized);

        return is_string($normalized) ? $normalized : '';
    }

    public function getAllLocations(int $limit = 50): array
    {
        $chunkSize = max(1, min(200, $limit));
        $offset = 0;
        $rawLocations = [];

        do {
            $batch = $this->gatewayService->listLocations($chunkSize, $offset);

            if (! is_array($batch) || $batch === []) {
                break;
            }

            $rawLocations = array_merge($rawLocations, $batch);
            $offset += count($batch);
        } while (count($rawLocations) < $limit && count($batch) === $chunkSize);

        return collect(array_slice($rawLocations, 0, $limit))
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

        if (! is_array($location)) {
            return null;
        }

        return $this->augmentEquivalentAirportProviders(
            $this->normalizeLocationRecord($location)
        );
    }

    /**
     * Get location information by provider ID.
     */
    public function getLocationByProviderId(string $providerId, string $provider): ?array
    {
        $location = $this->gatewayService->getLocationByProvider($provider, $providerId);

        if (! is_array($location)) {
            return null;
        }

        return $this->augmentEquivalentAirportProviders(
            $this->normalizeLocationRecord($location)
        );
    }

    /**
     * Resolve a location for search execution. The search results page must not
     * infer another airport/city from free text when the selected ID is stale.
     */
    public function resolveSearchLocation(array $validated): ?array
    {
        $unifiedLocationId = (int) ($validated['unified_location_id'] ?? 0);
        if ($unifiedLocationId > 0) {
            $location = $this->getLocationByUnifiedId($unifiedLocationId);
            if ($location && $this->searchRequestMatchesLocation($location, $validated)) {
                return $location;
            }
        }

        $providerPickupId = (string) ($validated['provider_pickup_id'] ?? '');
        if ($providerPickupId !== '') {
            $provider = (string) ($validated['provider'] ?? '');
            if ($provider !== '' && $provider !== 'mixed') {
                $location = $this->getLocationByProviderId($providerPickupId, $provider);
                if ($location && $this->searchRequestMatchesLocation($location, $validated)) {
                    return $location;
                }
            }

            $location = $this->getLocationByAnyProviderPickupId($providerPickupId);
            if ($location && $this->searchRequestMatchesLocation($location, $validated)) {
                return $location;
            }
        }

        return null;
    }

    public function nearbyLocations(array $validated, ?array $originLocation = null, int $limit = 4): array
    {
        $latitude = $originLocation['latitude'] ?? $validated['latitude'] ?? null;
        $longitude = $originLocation['longitude'] ?? $validated['longitude'] ?? null;

        if (! $this->hasUsableCoordinatePair($latitude, $longitude)) {
            return [];
        }

        $countryCode = strtoupper(trim((string) ($originLocation['country_code'] ?? '')));
        if ($countryCode === '') {
            $requestedCountry = trim((string) ($validated['country'] ?? ''));
            $countryCode = preg_match('/^[A-Z]{2}$/', strtoupper($requestedCountry))
                ? strtoupper($requestedCountry)
                : CountryCodeResolver::resolve($requestedCountry);
        }

        $excludeIds = collect([
            $originLocation['unified_location_id'] ?? null,
            $validated['unified_location_id'] ?? null,
        ])->filter()->map(fn ($id) => (int) $id)->unique()->all();

        return collect($this->getAllLocations(500))
            ->filter(fn (array $location): bool => $this->isNearbyRecommendationCandidate($location, $excludeIds, $countryCode))
            ->map(function (array $location) use ($latitude, $longitude): array {
                $location['distance_km'] = $this->distanceKm(
                    (float) $latitude,
                    (float) $longitude,
                    (float) $location['latitude'],
                    (float) $location['longitude']
                );

                return $location;
            })
            ->sortBy('distance_km')
            ->take(max(1, $limit))
            ->map(fn (array $location): array => $this->formatNearbyLocation($location))
            ->values()
            ->all();
    }

    public function getLocationByAnyProviderPickupId(string $providerPickupId): ?array
    {
        return collect($this->getAllLocations(250))->first(function ($location) use ($providerPickupId) {
            if (! isset($location['providers']) || ! is_array($location['providers'])) {
                return false;
            }

            return collect($location['providers'])->contains(function ($providerInfo) use ($providerPickupId) {
                return (string) ($providerInfo['pickup_id'] ?? '') === $providerPickupId;
            });
        });
    }

    private function resolveLocationFromSearchText(array $validated): ?array
    {
        $bestLocation = null;
        $bestScore = 0;

        foreach ($this->locationSearchTermsFromValidated($validated) as $term) {
            foreach ($this->searchLocations($term, 20) as $candidate) {
                $score = $this->scoreLocationCandidate($candidate, $validated, $term);
                if ($score > $bestScore) {
                    $bestLocation = $candidate;
                    $bestScore = $score;
                }
            }
        }

        if (! $bestLocation || $bestScore < 45) {
            return null;
        }

        return $this->augmentEquivalentAirportProviders($bestLocation);
    }

    private function isNearbyRecommendationCandidate(array $location, array $excludeIds, string $countryCode): bool
    {
        $unifiedLocationId = (int) ($location['unified_location_id'] ?? 0);
        if ($unifiedLocationId <= 0 || in_array($unifiedLocationId, $excludeIds, true)) {
            return false;
        }

        if ($countryCode !== '' && strtoupper((string) ($location['country_code'] ?? '')) !== $countryCode) {
            return false;
        }

        return $this->hasUsableCoordinatePair($location['latitude'] ?? null, $location['longitude'] ?? null)
            && ! empty($location['providers']);
    }

    private function searchRequestMatchesLocation(array $location, array $validated): bool
    {
        $requestedIata = $this->extractIataCode(implode(' ', array_filter([
            $validated['where'] ?? null,
            $validated['location'] ?? null,
        ])));

        if ($requestedIata !== null) {
            $locationIata = strtoupper(trim((string) ($location['iata'] ?? '')));
            if ($locationIata === '' || $locationIata !== $requestedIata) {
                return false;
            }
        }

        $requestedCountry = trim((string) ($validated['country'] ?? ''));
        if ($requestedCountry === '') {
            return true;
        }

        $requestedCountryCode = preg_match('/^[A-Z]{2}$/', strtoupper($requestedCountry))
            ? strtoupper($requestedCountry)
            : CountryCodeResolver::resolve($requestedCountry);
        $locationCountryCode = strtoupper(trim((string) ($location['country_code'] ?? '')));

        if ($requestedCountryCode !== '' && $locationCountryCode !== '' && $requestedCountryCode !== $locationCountryCode) {
            return false;
        }

        return true;
    }

    private function formatNearbyLocation(array $location): array
    {
        $providers = collect($location['providers'] ?? [])
            ->filter(fn ($provider) => is_array($provider))
            ->values();
        $externalProvider = $providers->first(fn (array $provider): bool => strtolower(trim((string) ($provider['provider'] ?? ''))) !== 'internal');
        $internalProvider = $providers->first(fn (array $provider): bool => strtolower(trim((string) ($provider['provider'] ?? ''))) === 'internal');
        $preferredProvider = $externalProvider ?: $internalProvider;

        return [
            'unified_location_id' => (int) $location['unified_location_id'],
            'name' => $location['name'] ?? null,
            'city' => $location['city'] ?? null,
            'country' => $location['country'] ?? null,
            'country_code' => $location['country_code'] ?? null,
            'latitude' => $location['latitude'] ?? null,
            'longitude' => $location['longitude'] ?? null,
            'location_type' => $location['location_type'] ?? null,
            'iata' => $location['iata'] ?? null,
            'provider_count' => (int) ($location['provider_count'] ?? $providers->count()),
            'distance_km' => round((float) ($location['distance_km'] ?? 0), 1),
            'recommended_provider' => $externalProvider ? 'mixed' : 'internal',
            'recommended_provider_pickup_id' => $preferredProvider['pickup_id'] ?? null,
        ];
    }

    private function hasUsableCoordinatePair(mixed $latitude, mixed $longitude): bool
    {
        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return false;
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;

        if ($latitude < -90.0 || $latitude > 90.0 || $longitude < -180.0 || $longitude > 180.0) {
            return false;
        }

        return ! (abs($latitude) < 0.000001 && abs($longitude) < 0.000001);
    }

    private function distanceKm(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $earthRadiusKm = 6371;
        $latDelta = deg2rad($toLatitude - $fromLatitude);
        $lonDelta = deg2rad($toLongitude - $fromLongitude);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLatitude)) * cos(deg2rad($toLatitude)) * sin($lonDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function locationSearchTermsFromValidated(array $validated): array
    {
        $terms = collect([
            $validated['where'] ?? null,
            $validated['location'] ?? null,
            $validated['pickup_location'] ?? null,
            trim((string) ($validated['city'] ?? '').' '.(string) ($validated['country'] ?? '')),
            $validated['city'] ?? null,
        ]);

        $iata = $this->extractIataCode(implode(' ', $terms->filter()->all()));
        if ($iata !== null) {
            $terms->push($iata);
        }

        return $terms
            ->filter(fn ($term) => is_string($term) && strlen(trim($term)) >= 2)
            ->map(fn ($term) => trim((string) $term))
            ->unique(fn ($term) => $this->normalizeString($term))
            ->values()
            ->all();
    }

    private function scoreLocationCandidate(array $candidate, array $validated, string $term): int
    {
        $score = 0;
        $termSignature = $this->normalizeString($term);
        $candidateName = $this->normalizeString($candidate['name'] ?? '');
        $candidateCity = $this->normalizeString($candidate['city'] ?? '');
        $candidateCountry = $this->normalizeString($candidate['country'] ?? '');

        if ($termSignature !== '' && $candidateName !== '') {
            if (str_contains($candidateName, $termSignature) || str_contains($termSignature, $candidateName)) {
                $score += 30;
            }
        }

        foreach ($candidate['aliases'] ?? [] as $alias) {
            $aliasSignature = $this->normalizeString($alias);
            if ($aliasSignature !== '' && $termSignature !== '' && (str_contains($aliasSignature, $termSignature) || str_contains($termSignature, $aliasSignature))) {
                $score += 20;
                break;
            }
        }

        $requestedIata = $this->extractIataCode($term) ?? $this->extractIataCode((string) ($validated['where'] ?? ''));
        if ($requestedIata !== null && $requestedIata === strtoupper((string) ($candidate['iata'] ?? ''))) {
            $score += 80;
        }

        $requestedCity = $this->normalizeString($validated['city'] ?? '');
        if ($requestedCity !== '' && $candidateCity !== '' && $requestedCity === $candidateCity) {
            $score += 25;
        }

        $requestedCountry = trim((string) ($validated['country'] ?? ''));
        $requestedCountryCode = strtoupper($requestedCountry);
        if (! preg_match('/^[A-Z]{2}$/', $requestedCountryCode)) {
            $requestedCountryCode = CountryCodeResolver::resolve($requestedCountry);
        }

        if ($requestedCountryCode !== '' && $requestedCountryCode === strtoupper((string) ($candidate['country_code'] ?? ''))) {
            $score += 25;
        } elseif ($requestedCountry !== '' && $candidateCountry !== '' && $this->normalizeString($requestedCountry) === $candidateCountry) {
            $score += 20;
        }

        if (($candidate['location_type'] ?? null) === 'airport' && (str_contains($termSignature, 'airport') || $requestedIata !== null)) {
            $score += 10;
        }

        $score += min(15, (int) ($candidate['provider_count'] ?? 0));

        return $score;
    }

    private function extractIataCode(?string $value): ?string
    {
        $value = strtoupper(trim((string) $value));
        if ($value === '') {
            return null;
        }

        if (preg_match('/\(([A-Z]{3})\)/', $value, $matches)) {
            return $matches[1];
        }

        if (preg_match('/\b([A-Z]{3})\b/', $value, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function normalizeLocationRecord(array $location): array
    {
        $location['unified_location_id'] = isset($location['unified_location_id'])
            ? (int) $location['unified_location_id']
            : null;
        $location['name'] = isset($location['name']) ? (string) $location['name'] : ($location['label'] ?? null);
        $location['address'] = isset($location['address']) ? (string) $location['address'] : null;
        $location['city'] = isset($location['city']) ? (string) $location['city'] : null;
        $location['state'] = isset($location['state']) ? (string) $location['state'] : null;
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
            ->filter(fn ($provider) => is_array($provider) && ! empty($provider['provider']) && array_key_exists('pickup_id', $provider))
            ->map(function (array $provider): array {
                $providerCountryCode = strtoupper(trim((string) ($provider['country_code'] ?? '')));
                $providerIata = strtoupper(trim((string) ($provider['iata'] ?? '')));

                return [
                    'provider' => strtolower(trim((string) $provider['provider'])),
                    'pickup_id' => (string) $provider['pickup_id'],
                    'original_name' => $provider['original_name'] ?? null,
                    'dropoffs' => collect($provider['dropoffs'] ?? [])
                        ->map(fn ($dropoffId) => trim((string) $dropoffId))
                        ->filter()
                        ->values()
                        ->all(),
                    'latitude' => isset($provider['latitude']) ? (float) $provider['latitude'] : null,
                    'longitude' => isset($provider['longitude']) ? (float) $provider['longitude'] : null,
                    'supports_one_way' => (bool) ($provider['supports_one_way'] ?? false),
                    'extended_location_code' => filled($provider['extended_location_code'] ?? null)
                        ? (string) $provider['extended_location_code']
                        : null,
                    'extended_dropoff_code' => filled($provider['extended_dropoff_code'] ?? null)
                        ? (string) $provider['extended_dropoff_code']
                        : null,
                    'country_code' => $providerCountryCode !== '' ? $providerCountryCode : null,
                    'iata' => $providerIata !== '' ? $providerIata : null,
                    'provider_code' => filled($provider['provider_code'] ?? null)
                        ? (string) $provider['provider_code']
                        : null,
                ];
            })
            ->values()
            ->all();
        $location = $this->internalLocationResolver->appendVerifiedProvider($location);
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
                if (! $this->airportsAreEquivalent($location, $candidate)) {
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
                ! empty($secondary['name']) && $secondary['name'] !== ($merged['name'] ?? null),
                fn ($collection) => $collection->push($secondary['name'])
            )
            ->filter()
            ->unique()
            ->values()
            ->all();

        $providers = collect($merged['providers'] ?? [])
            ->merge($secondary['providers'] ?? [])
            ->filter(fn ($provider) => is_array($provider) && ! empty($provider['provider']) && array_key_exists('pickup_id', $provider))
            ->unique(fn ($provider) => strtolower(trim((string) $provider['provider'])).'|'.(string) $provider['pickup_id'])
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

        return 'iata:'.$iata;
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

        if ($primaryKey !== null && $secondaryKey !== null && $primaryKey !== $secondaryKey) {
            return false;
        }

        return ! empty(array_intersect(
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
            $displayName = \Locale::getDisplayRegion('-'.$countryCode, 'en');
            if (! is_string($displayName) || trim($displayName) === '' || strtoupper(trim($displayName)) === $countryCode) {
                $displayName = null;
            }
        }

        return $displayName ?: null;
    }
}
