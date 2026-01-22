<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LocationMatchingService
{
    /**
     * Weights for similarity calculation
     */
    private const WEIGHT_GPS = 0.40;      // GPS proximity weight
    private const WEIGHT_NAME = 0.30;      // Name similarity weight
    private const WEIGHT_CITY = 0.15;      // City match weight
    private const WEIGHT_TYPE = 0.15;      // Location type weight

    /**
     * Thresholds
     */
    private const SIMILARITY_THRESHOLD = 0.75;  // 75% similarity to merge
    private const GPS_MERGE_RADIUS_KM = 5;      // Default: locations within 5km can merge
    private const GPS_AIRPORT_RADIUS_KM = 8;    // Airports: within 8km (they're large)
    private const GPS_DOWNTOWN_RADIUS_KM = 2;   // Downtown: within 2km

    /**
     * Location type keywords for detection
     */
    private const AIRPORT_KEYWORDS = ['airport', 'aeropuerto', 'aeroport', 'aeroporto', 'flughafen', 'aéroport', 'terminal'];
    private const DOWNTOWN_KEYWORDS = ['downtown', 'centro', 'city center', 'centre ville', 'zentrum', 'central'];
    private const PORT_KEYWORDS = ['port', 'puerto', 'porto', 'harbour', 'harbor', 'ferry'];
    private const TRAIN_KEYWORDS = ['train', 'station', 'railway', 'gare', 'bahnhof', 'estacion'];

    protected $locationSearchService;

    public function __construct(LocationSearchService $locationSearchService)
    {
        $this->locationSearchService = $locationSearchService;
    }

    /**
     * Cluster similar locations together.
     * Returns an array of clusters, where each cluster contains similar locations.
     *
     * @param array $locations All locations from all sources
     * @return array Array of clusters
     */
    public function clusterLocations(array $locations): array
    {
        if (empty($locations)) {
            return [];
        }

        $clusters = [];
        $assigned = []; // Track which locations are already assigned to a cluster

        foreach ($locations as $i => $location) {
            if (in_array($i, $assigned)) {
                continue; // Already assigned to a cluster
            }

            // Start a new cluster with this location
            $cluster = [$location];
            $assigned[] = $i;

            // Find all similar locations
            foreach ($locations as $j => $otherLocation) {
                if ($i === $j || in_array($j, $assigned)) {
                    continue;
                }

                $similarity = $this->calculateSimilarityScore($location, $otherLocation);

                if ($similarity >= self::SIMILARITY_THRESHOLD) {
                    $cluster[] = $otherLocation;
                    $assigned[] = $j;

                    Log::debug("Merged locations", [
                        'location1' => $location['label'] ?? 'Unknown',
                        'location2' => $otherLocation['label'] ?? 'Unknown',
                        'similarity' => round($similarity * 100, 2) . '%'
                    ]);
                }
            }

            $clusters[] = $cluster;
        }

        Log::info("Location clustering complete", [
            'total_locations' => count($locations),
            'clusters_created' => count($clusters),
            'merged_count' => count($locations) - count($clusters)
        ]);

        return $clusters;
    }

    /**
     * Calculate overall similarity score between two locations.
     *
     * @param array $loc1 First location
     * @param array $loc2 Second location
     * @return float Similarity score between 0 and 1
     */
    public function calculateSimilarityScore(array $loc1, array $loc2): float
    {
        $exactKey1 = $this->buildExactMatchKey($loc1);
        $exactKey2 = $this->buildExactMatchKey($loc2);

        if ($exactKey1 !== '' && $exactKey1 === $exactKey2) {
            return 1.0;
        }

        // Check for terminal conflict FIRST - different terminals should NEVER merge
        // e.g., "Dubai Terminal 1" and "Dubai Terminal 2" should stay separate
        if ($this->hasTerminalConflict($loc1, $loc2)) {
            return 0;
        }

        // Calculate individual scores
        $gpsScore = $this->calculateGpsSimilarity($loc1, $loc2);
        $nameScore = $this->calculateNameSimilarity($loc1, $loc2);
        $cityScore = $this->calculateCitySimilarity($loc1, $loc2);
        $typeScore = $this->calculateTypeSimilarity($loc1, $loc2);

        // If GPS distance is too far (> 50km), don't merge regardless of name
        if ($gpsScore < 0.1) {
            return 0;
        }

        // Special case: If names are almost identical (>90%), give bonus
        if ($nameScore > 0.90) {
            $nameScore = min(1.0, $nameScore * 1.1);
        }

        // Calculate weighted score
        $totalScore = (
            ($gpsScore * self::WEIGHT_GPS) +
            ($nameScore * self::WEIGHT_NAME) +
            ($cityScore * self::WEIGHT_CITY) +
            ($typeScore * self::WEIGHT_TYPE)
        );

        return $totalScore;
    }

    private function buildExactMatchKey(array $location): string
    {
        $name = $location['label'] ?? $location['name'] ?? '';
        $city = $location['city'] ?? '';
        $country = $location['country'] ?? '';

        $normalizedName = $this->locationSearchService->normalizeString($name);
        $normalizedCity = $this->locationSearchService->normalizeString($city);
        $normalizedCountry = $this->locationSearchService->normalizeString($country);

        if ($normalizedName === '' || $normalizedCity === '' || $normalizedCountry === '') {
            return '';
        }

        return $normalizedName . '|' . $normalizedCity . '|' . $normalizedCountry;
    }

    /**
     * Check if two locations have conflicting terminal numbers.
     * E.g., "Dubai Terminal 1" should NOT merge with "Dubai Terminal 2"
     *
     * @param array $loc1 First location
     * @param array $loc2 Second location
     * @return bool True if there's a terminal conflict (should NOT merge)
     */
    private function hasTerminalConflict(array $loc1, array $loc2): bool
    {
        $name1 = strtolower($loc1['label'] ?? $loc1['name'] ?? '');
        $name2 = strtolower($loc2['label'] ?? $loc2['name'] ?? '');

        // Extract terminal numbers from both names
        $terminal1 = $this->extractTerminalNumber($name1);
        $terminal2 = $this->extractTerminalNumber($name2);

        // If both have terminal numbers and they're different, there's a conflict
        if ($terminal1 !== null && $terminal2 !== null && $terminal1 !== $terminal2) {
            Log::debug("Terminal conflict detected", [
                'loc1' => $name1,
                'terminal1' => $terminal1,
                'loc2' => $name2,
                'terminal2' => $terminal2
            ]);
            return true;
        }

        return false;
    }

    /**
     * Extract terminal number from a location name.
     *
     * @param string $name Location name
     * @return int|null Terminal number or null if not found
     */
    private function extractTerminalNumber(string $name): ?int
    {
        // Match "Terminal 1", "Terminal 2", "T1", "T2", "T 1", etc.
        if (preg_match('/terminal\s*(\d+)/i', $name, $matches)) {
            return (int) $matches[1];
        }
        if (preg_match('/\bt\s*(\d+)\b/i', $name, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Calculate GPS proximity similarity using Haversine formula.
     *
     * @param array $loc1 First location
     * @param array $loc2 Second location
     * @return float Similarity score between 0 and 1
     */
    private function calculateGpsSimilarity(array $loc1, array $loc2): float
    {
        $lat1 = $loc1['latitude'] ?? null;
        $lon1 = $loc1['longitude'] ?? null;
        $lat2 = $loc2['latitude'] ?? null;
        $lon2 = $loc2['longitude'] ?? null;

        // If coordinates are missing, return neutral score
        if (!$lat1 || !$lon1 || !$lat2 || !$lon2) {
            return 0.5;
        }

        // If coordinates are exactly the same
        if ($lat1 == $lat2 && $lon1 == $lon2) {
            return 1.0;
        }

        $distance = $this->haversineDistance($lat1, $lon1, $lat2, $lon2);

        // Determine radius based on location type
        $type1 = $this->detectLocationType($loc1);
        $type2 = $this->detectLocationType($loc2);

        $maxRadius = self::GPS_MERGE_RADIUS_KM;
        if ($type1 === 'airport' || $type2 === 'airport') {
            $maxRadius = self::GPS_AIRPORT_RADIUS_KM;
        } elseif ($type1 === 'downtown' || $type2 === 'downtown') {
            $maxRadius = self::GPS_DOWNTOWN_RADIUS_KM;
        }

        // Convert distance to similarity score
        if ($distance <= $maxRadius) {
            return 1.0 - ($distance / $maxRadius) * 0.3; // Close = high score
        } elseif ($distance <= $maxRadius * 2) {
            return 0.5 - (($distance - $maxRadius) / $maxRadius) * 0.3;
        } elseif ($distance <= 50) {
            return 0.2 - ($distance / 250); // Gradual decay to ~0
        }

        return 0; // Too far apart
    }

    /**
     * Calculate Haversine distance between two GPS coordinates.
     *
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @return float Distance in kilometers
     */
    public function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Calculate name similarity using multiple algorithms.
     *
     * @param array $loc1 First location
     * @param array $loc2 Second location
     * @return float Similarity score between 0 and 1
     */
    private function calculateNameSimilarity(array $loc1, array $loc2): float
    {
        $name1 = $loc1['label'] ?? $loc1['name'] ?? '';
        $name2 = $loc2['label'] ?? $loc2['name'] ?? '';

        if (empty($name1) || empty($name2)) {
            return 0;
        }

        // Normalize names
        $normalized1 = $this->normalizeLocationName($name1);
        $normalized2 = $this->normalizeLocationName($name2);

        // If normalized names are identical
        if ($normalized1 === $normalized2) {
            return 1.0;
        }

        // Check if one contains the other (e.g., "Avenue Houman" vs "Avenue Houman, 40008")
        if (str_contains($normalized1, $normalized2) || str_contains($normalized2, $normalized1)) {
            $longerLen = max(strlen($normalized1), strlen($normalized2));
            $shorterLen = min(strlen($normalized1), strlen($normalized2));
            return $shorterLen / $longerLen;
        }

        // Calculate Levenshtein-based similarity
        $levenshteinScore = $this->levenshteinSimilarity($normalized1, $normalized2);

        // Calculate Metaphone (phonetic) similarity
        $metaphoneScore = $this->metaphoneSimilarity($name1, $name2);

        // Use the higher of the two scores
        return max($levenshteinScore, $metaphoneScore);
    }

    /**
     * Normalize a location name for comparison.
     *
     * @param string $name The location name
     * @return string Normalized name
     */
    private function normalizeLocationName(string $name): string
    {
        $name = strtolower($name);

        // Remove common suffixes/prefixes that don't affect identity
        // Terminal numbers ARE removed here to allow merging (e.g., "Dubai T1" + "Dubai T2" merge to "Dubai Airport")
        // But each provider's original_name is preserved for display on vehicle cards
        $removePatterns = [
            '/\b(international|intl|int\'l)\b/i',
            '/\b(terminal\s*\d*)\b/i',  // Terminal 1, Terminal 2, etc.
            '/\b(t\d)\b/i',             // T1, T2, etc.
            '/[,\.\-\/\\\\]+/',  // Punctuation
            '/\b\d{4,5}\b/', // Postal codes (4-5 digits)
            '/\s+/', // Multiple spaces
        ];

        foreach ($removePatterns as $pattern) {
            $name = preg_replace($pattern, ' ', $name);
        }

        // Transliterate (remove diacritics)
        if (function_exists('transliterator_transliterate')) {
            $name = transliterator_transliterate('NFKD; [:Nonspacing Mark:] Remove; NFC;', $name);
        }

        // Remove non-alphanumeric
        $name = preg_replace('/[^a-z0-9\s]/', '', $name);

        return trim(preg_replace('/\s+/', ' ', $name));
    }

    /**
     * Calculate Levenshtein-based similarity.
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     * @return float Similarity score between 0 and 1
     */
    private function levenshteinSimilarity(string $str1, string $str2): float
    {
        if ($str1 === $str2) {
            return 1.0;
        }

        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen === 0) {
            return 1.0;
        }

        $distance = levenshtein($str1, $str2);

        return 1 - ($distance / $maxLen);
    }

    /**
     * Calculate Metaphone (phonetic) similarity.
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     * @return float Similarity score between 0 and 1
     */
    private function metaphoneSimilarity(string $str1, string $str2): float
    {
        $metaphone1 = metaphone($str1);
        $metaphone2 = metaphone($str2);

        if ($metaphone1 === $metaphone2) {
            return 0.95; // Very similar phonetically
        }

        return $this->levenshteinSimilarity($metaphone1, $metaphone2) * 0.8;
    }

    /**
     * Calculate city similarity.
     *
     * @param array $loc1 First location
     * @param array $loc2 Second location
     * @return float Similarity score between 0 and 1
     */
    private function calculateCitySimilarity(array $loc1, array $loc2): float
    {
        $city1 = $this->normalizeLocationName($loc1['city'] ?? '');
        $city2 = $this->normalizeLocationName($loc2['city'] ?? '');

        if (empty($city1) || empty($city2)) {
            // Check country as fallback
            $country1 = $this->normalizeLocationName($loc1['country'] ?? '');
            $country2 = $this->normalizeLocationName($loc2['country'] ?? '');

            if (!empty($country1) && !empty($country2) && $country1 === $country2) {
                return 0.5; // Same country, unknown city
            }
            return 0.3; // Neutral
        }

        if ($city1 === $city2) {
            return 1.0;
        }

        // Check if one contains the other
        if (str_contains($city1, $city2) || str_contains($city2, $city1)) {
            return 0.9;
        }

        return $this->levenshteinSimilarity($city1, $city2);
    }

    /**
     * Calculate location type similarity.
     *
     * @param array $loc1 First location
     * @param array $loc2 Second location
     * @return float Similarity score between 0 and 1
     */
    private function calculateTypeSimilarity(array $loc1, array $loc2): float
    {
        $type1 = $this->detectLocationType($loc1);
        $type2 = $this->detectLocationType($loc2);

        if ($type1 === $type2) {
            return 1.0;
        }

        // Different types but could still be same location
        if ($type1 === 'unknown' || $type2 === 'unknown') {
            return 0.7;
        }

        return 0.3; // Different types (e.g., airport vs downtown)
    }

    /**
     * Detect the type of location based on name keywords.
     *
     * @param array $location The location data
     * @return string Location type: 'airport', 'downtown', 'port', 'train', or 'unknown'
     */
    public function detectLocationType(array $location): string
    {
        $name = strtolower($location['label'] ?? $location['name'] ?? '');

        foreach (self::AIRPORT_KEYWORDS as $keyword) {
            if (str_contains($name, $keyword)) {
                return 'airport';
            }
        }

        foreach (self::DOWNTOWN_KEYWORDS as $keyword) {
            if (str_contains($name, $keyword)) {
                return 'downtown';
            }
        }

        foreach (self::PORT_KEYWORDS as $keyword) {
            if (str_contains($name, $keyword)) {
                return 'port';
            }
        }

        foreach (self::TRAIN_KEYWORDS as $keyword) {
            if (str_contains($name, $keyword)) {
                return 'train';
            }
        }

        return 'unknown';
    }

    /**
     * Build a unified location from a cluster of similar locations.
     *
     * @param array $cluster Array of similar locations
     * @return array The unified location
     */
    public function buildUnifiedLocation(array $cluster): array
    {
        if (empty($cluster)) {
            return [];
        }

        // Find the best name (prefer longer, more descriptive names)
        $bestName = '';
        $bestLocation = $cluster[0];
        $aliases = [];

        foreach ($cluster as $location) {
            $name = $location['label'] ?? $location['name'] ?? '';
            $aliases[] = $name;

            // Prefer names with more meaningful words
            if (strlen($name) > strlen($bestName) && !preg_match('/\d{4,5}$/', $name)) {
                $bestName = $name;
                $bestLocation = $location;
            }
        }

        $resolvedType = $bestLocation['location_type'] ?? $this->detectLocationType($bestLocation);
        $resolvedCity = trim((string) ($bestLocation['city'] ?? ''));

        if ($resolvedType === 'unknown') {
            $resolvedType = $this->detectLocationType($bestLocation);
        }

        if ($resolvedType !== 'unknown' && $resolvedCity !== '' && strcasecmp(trim($bestName), $resolvedCity) === 0) {
            $bestName = trim($resolvedCity . ' ' . ucfirst($resolvedType));
        }

        // Remove duplicates and the main name from aliases
        $aliases = array_values(array_unique(array_filter($aliases, fn($a) => $a !== $bestName)));

        // Collect all provider information
        $providers = [];
        $ourLocationId = null;

        foreach ($cluster as $location) {
            if (($location['source'] ?? '') === 'internal') {
                $ourLocationId = $location['id'] ?? null;
            } elseif (!empty($location['provider_location_id'])) {
                $providerData = [
                    'provider' => $location['source'] ?? 'unknown',
                    'pickup_id' => $location['provider_location_id'],
                    'original_name' => $location['label'] ?? $location['name'] ?? '',
                    'dropoffs' => [],
                ];

                // Check if this provider already exists
                $exists = false;
                foreach ($providers as $existing) {
                    if (
                        $existing['provider'] === $providerData['provider'] &&
                        $existing['pickup_id'] === $providerData['pickup_id']
                    ) {
                        $exists = true;
                        break;
                    }
                }

                if (!$exists) {
                    $providers[] = $providerData;
                }
            }
        }

        // Calculate average coordinates
        $latSum = 0;
        $lonSum = 0;
        $coordCount = 0;

        foreach ($cluster as $location) {
            if (!empty($location['latitude']) && !empty($location['longitude'])) {
                $latSum += (float) $location['latitude'];
                $lonSum += (float) $location['longitude'];
                $coordCount++;
            }
        }

        $avgLat = $coordCount > 0 ? $latSum / $coordCount : ($bestLocation['latitude'] ?? 0);
        $avgLon = $coordCount > 0 ? $lonSum / $coordCount : ($bestLocation['longitude'] ?? 0);

        return [
            'unified_location_id' => crc32(strtolower($bestName)), // Use full name with terminal number for unique ID
            'name' => $bestName,
            'aliases' => $aliases,
            'city' => $bestLocation['city'] ?? null,
            'country' => $bestLocation['country'] ?? null,
            'latitude' => round($avgLat, 6),
            'longitude' => round($avgLon, 6),
            'location_type' => $this->detectLocationType($bestLocation),
            'providers' => $providers,
            'our_location_id' => $ourLocationId,
        ];
    }

    /**
     * Find similar locations for a given location from all locations.
     *
     * @param array $location The location to find matches for
     * @param array $allLocations All available locations
     * @return array Array of similar locations with similarity scores
     */
    public function findSimilarLocations(array $location, array $allLocations): array
    {
        $similar = [];

        foreach ($allLocations as $otherLocation) {
            // Skip self-comparison
            if (
                ($location['id'] ?? '') === ($otherLocation['id'] ?? '') &&
                ($location['source'] ?? '') === ($otherSource['source'] ?? '')
            ) {
                continue;
            }

            $score = $this->calculateSimilarityScore($location, $otherLocation);

            if ($score >= self::SIMILARITY_THRESHOLD) {
                $similar[] = [
                    'location' => $otherLocation,
                    'similarity' => $score,
                ];
            }
        }

        // Sort by similarity score descending
        usort($similar, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return $similar;
    }

    /**
     * Get the similarity threshold currently in use.
     *
     * @return float The threshold value
     */
    public function getSimilarityThreshold(): float
    {
        return self::SIMILARITY_THRESHOLD;
    }
}
