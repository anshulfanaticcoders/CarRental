<?php

namespace App\Console\Commands;

use App\Services\Locations\ProviderLocationFetchManager;
use Illuminate\Console\Command;

class UpdateUnifiedLocationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'locations:update-unified
                            {--provider= : Fetch only a specific provider by key (diagnostic mode, does not update file)}
                            {--retries=3 : Maximum retry attempts for API providers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates unified_locations.json by grouping locations by city and location type.';

    protected $locationSearchService;
    protected $locationMatchingService;
    protected ProviderLocationFetchManager $providerLocationFetchManager;

    public function __construct(
        \App\Services\LocationSearchService $locationSearchService,
        \App\Services\LocationMatchingService $locationMatchingService,
        ProviderLocationFetchManager $providerLocationFetchManager
    ) {
        parent::__construct();
        $this->locationSearchService = $locationSearchService;
        $this->locationMatchingService = $locationMatchingService;
        $this->providerLocationFetchManager = $providerLocationFetchManager;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to update unified locations...');

        $providerFilter = $this->option('provider');
        $maxRetries = max(1, (int) $this->option('retries'));
        $registry = $this->getProviderRegistry();

        if ($providerFilter !== null) {
            $validKeys = array_column($registry, 'key');
            if (!in_array($providerFilter, $validKeys, true)) {
                $this->error("Unknown provider key: {$providerFilter}");
                $this->info('Available providers: ' . implode(', ', $validKeys));
                return 1;
            }
            $registry = array_values(array_filter($registry, fn ($p) => $p['key'] === $providerFilter));
            $this->info("Single-provider mode: {$registry[0]['label']}");
        }

        $results = [];
        $locationsByProvider = [];

        foreach ($registry as $provider) {
            $attempts = $provider['retry'] ? $maxRetries : 1;
            $locations = $this->fetchWithRetry($provider['key'], $provider['label'], $attempts);
            $count = count($locations);

            $results[] = [
                'provider' => $provider['label'],
                'count' => $count,
                'status' => $count > 0 ? 'OK' : 'EMPTY',
            ];

            $locationsByProvider[] = $locations;
        }

        $this->printSummaryTable($results);

        if ($providerFilter !== null) {
            $this->info('Single-provider mode: skipping merge and file update.');
            return 0;
        }

        $allLocations = array_merge(...$locationsByProvider);
        if (empty($allLocations)) {
            $this->warn('No locations fetched from any provider. Skipping file update.');
            return 1;
        }

        $unifiedLocations = $this->mergeAndNormalizeLocations(...$locationsByProvider);
        $this->info('Merged into ' . count($unifiedLocations) . ' unique unified locations.');

        $this->saveUnifiedLocations(array_values($unifiedLocations));

        $this->info('Unified locations updated successfully!');
        return 0;
    }

    private function getProviderRegistry(): array
    {
        return [
            ['key' => 'internal', 'label' => 'Internal', 'retry' => false],
            ['key' => 'okmobility', 'label' => 'OK Mobility', 'retry' => true],
            ['key' => 'adobe', 'label' => 'Adobe', 'retry' => true],
            ['key' => 'greenmotion', 'label' => 'GreenMotion', 'retry' => true],
            ['key' => 'usave', 'label' => 'U-SAVE', 'retry' => true],
            ['key' => 'locauto_rent', 'label' => 'Locauto Rent', 'retry' => true],
            ['key' => 'wheelsys', 'label' => 'Wheelsys', 'retry' => true],
            ['key' => 'renteon', 'label' => 'Renteon', 'retry' => true],
            ['key' => 'favrica', 'label' => 'Favrica', 'retry' => true],
            ['key' => 'xdrive', 'label' => 'XDrive', 'retry' => true],
            ['key' => 'sicily_by_car', 'label' => 'Sicily By Car', 'retry' => true],
            ['key' => 'recordgo', 'label' => 'Record Go', 'retry' => true],
            ['key' => 'surprice', 'label' => 'Surprice', 'retry' => true],
        ];
    }

    private function fetchWithRetry(string $key, string $label, int $maxAttempts): array
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $locations = $this->providerLocationFetchManager->fetchByKey($key);

            if (count($locations) > 0) {
                $this->info("Fetched " . count($locations) . " {$label} locations.");
                return $locations;
            }

            if ($attempt < $maxAttempts) {
                $delay = $attempt * 3;
                $this->warn("{$label}: empty result (attempt {$attempt}/{$maxAttempts}), retrying in {$delay}s...");
                sleep($delay);
            } else {
                $suffix = $maxAttempts > 1 ? " after {$maxAttempts} attempts" : '';
                $this->warn("{$label}: 0 locations{$suffix}.");
            }
        }

        return [];
    }

    private function printSummaryTable(array $results): void
    {
        $this->newLine();
        $this->table(
            ['Provider', 'Locations', 'Status'],
            array_map(fn ($r) => [$r['provider'], $r['count'], $r['status']], $results)
        );

        $succeeded = count(array_filter($results, fn ($r) => $r['count'] > 0));
        $failed = count(array_filter($results, fn ($r) => $r['count'] === 0));
        $totalRaw = array_sum(array_column($results, 'count'));
        $this->info("{$succeeded} providers succeeded, {$failed} returned empty. {$totalRaw} raw locations total.");
    }

    private function mergeAndNormalizeLocations(array ...$locationGroups): array
    {
        $allLocations = array_merge(...$locationGroups);

        if (empty($allLocations)) {
            return [];
        }

        $normalizedLocations = [];
        foreach ($allLocations as $location) {
            $normalized = $this->normalizeLocationForGrouping($location);
            if ($normalized['key'] === '|') {
                continue;
            }

            $normalizedLocations[] = $normalized['location'];
        }

        $unifiedLocations = [];
        foreach ($this->locationMatchingService->clusterLocations($normalizedLocations) as $cluster) {
            $unified = $this->buildUnifiedLocationFromCluster($cluster);

            if (!empty($unified)) {
                $unifiedLocations[] = $unified;
            }
        }

        return $unifiedLocations;
    }

    private function normalizeLocationForGrouping(array $location): array
    {
        $label = trim((string) ($location['label'] ?? $location['name'] ?? ''));
        $city = trim((string) ($location['city'] ?? ''));
        $type = strtolower(trim((string) ($location['location_type'] ?? '')));
        $countryMetadata = $this->resolveCountryMetadata(
            $location['country'] ?? null,
            $location['country_code'] ?? null
        );

        if ($type === '' || $type === 'unknown') {
            $type = $this->locationMatchingService->detectLocationType($location);
        }
        if ($type === 'unknown') {
            $type = '';
        }

        if ($city === '') {
            $city = $this->extractCityFromLabel($label, $type);
        } elseif ($type !== '' && $type !== 'unknown' && $this->isSuspiciousCityName($city, $label)) {
            $extractedCity = $this->extractCityFromLabel($label, $type);
            if ($extractedCity !== '') {
                $city = $extractedCity;
            }
        }

        $cityDisplay = $this->normalizeTitleCase($city);
        if ($cityDisplay === '') {
            $cityDisplay = $this->normalizeTitleCase($label);
        }

        $typeDisplay = $type !== '' ? strtolower($type) : '';
        $key = $this->locationSearchService->normalizeString($cityDisplay) . '|' . $this->locationSearchService->normalizeString($typeDisplay);

        if ($cityDisplay !== '') {
            $location['city'] = $cityDisplay;
        }
        if ($countryMetadata['name'] !== null) {
            $location['country'] = $countryMetadata['name'];
        }
        if ($countryMetadata['code'] !== null) {
            $location['country_code'] = $countryMetadata['code'];
        }
        $location['label'] = $label;
        $location['location_type'] = $typeDisplay !== '' ? $typeDisplay : 'unknown';

        return [
            'key' => $key,
            'city' => $cityDisplay,
            'type' => $typeDisplay,
            'location' => $location,
        ];
    }

    private function buildUnifiedLocationFromCluster(array $locations): array
    {
        if (empty($locations)) {
            return [];
        }

        $cityScores = [];
        $typeScores = [];

        foreach ($locations as $location) {
            $normalized = $this->normalizeLocationForGrouping($location);
            $city = $normalized['city'];
            $type = $normalized['type'];
            $label = trim((string) ($normalized['location']['label'] ?? $normalized['location']['name'] ?? ''));

            if ($city !== '') {
                $cityKey = $this->locationSearchService->normalizeString($city);
                if (!isset($cityScores[$cityKey])) {
                    $cityScores[$cityKey] = [
                        'city' => $city,
                        'count' => 0,
                        'label_matches' => 0,
                    ];
                }
                $cityScores[$cityKey]['count']++;
                if ($label !== '' && str_contains($this->locationSearchService->normalizeString($label), $cityKey)) {
                    $cityScores[$cityKey]['label_matches']++;
                }
            }

            if ($type !== '') {
                if (!isset($typeScores[$type])) {
                    $typeScores[$type] = 0;
                }
                $typeScores[$type]++;
            }
        }

        uasort($cityScores, static function (array $left, array $right): int {
            if ($left['count'] !== $right['count']) {
                return $right['count'] <=> $left['count'];
            }

            if (($left['label_matches'] ?? 0) !== ($right['label_matches'] ?? 0)) {
                return ($right['label_matches'] ?? 0) <=> ($left['label_matches'] ?? 0);
            }

            if (strlen($left['city']) !== strlen($right['city'])) {
                return strlen($right['city']) <=> strlen($left['city']);
            }

            return strcmp($left['city'], $right['city']);
        });

        arsort($typeScores);

        $city = $cityScores !== [] ? (string) reset($cityScores)['city'] : '';
        $type = $typeScores !== [] ? (string) array_key_first($typeScores) : '';

        return $this->buildUnifiedLocationFromGroup($locations, $city, $type);
    }

    private function buildUnifiedLocationFromGroup(array $locations, string $city, string $type): array
    {
        if (empty($locations) || $city === '') {
            return [];
        }

        $typeLabel = $type !== '' ? $this->normalizeTitleCase($type) : '';
        $name = trim($city . ($typeLabel !== '' ? ' ' . $typeLabel : ''));
        if ($type === '' || $type === 'unknown') {
            $representativeLabel = $this->selectRepresentativeLocationLabel($locations, $city, $type);
            if ($representativeLabel !== null) {
                $name = $representativeLabel;
            }
        }

        $aliases = [];
        $providers = [];
        $ourLocationId = null;
        $iataCodes = [];
        $country = null;
        $countryCode = null;
        $latSum = 0.0;
        $lonSum = 0.0;
        $coordCount = 0;

        foreach ($locations as $location) {
            $label = trim((string) ($location['label'] ?? $location['name'] ?? ''));
            if ($label !== '' && $label !== $name) {
                $aliases[] = $label;
            }

            $iata = $this->extractIataCode($location);
            if ($iata !== '') {
                $iataCodes[] = $iata;
            }

            if (($location['source'] ?? '') === 'internal') {
                $ourLocationId = $location['id'] ?? null;
            } elseif (!empty($location['provider_location_id'])) {
                $providerData = [
                    'provider' => $location['source'] ?? 'unknown',
                    'pickup_id' => $location['provider_location_id'],
                    'original_name' => $label,
                    'dropoffs' => $location['dropoffs'] ?? [],
                ];

                // Preserve per-entry coordinates if available
                if ($this->hasValidCoordinates($location)) {
                    $providerData['latitude'] = (float) $location['latitude'];
                    $providerData['longitude'] = (float) $location['longitude'];
                }

                foreach ($this->providerLocationMetadataMap() as $sourceKey => $outputKey) {
                    $value = $location[$sourceKey] ?? null;
                    if ($value !== null && $value !== '') {
                        $providerData[$outputKey] = $value;
                    }
                }

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

            $countryMetadata = $this->resolveCountryMetadata(
                $location['country'] ?? null,
                $location['country_code'] ?? null
            );
            if ($country === null && !empty($countryMetadata['name'])) {
                $country = $countryMetadata['name'];
            }
            if ($countryCode === null && !empty($countryMetadata['code'])) {
                $countryCode = $countryMetadata['code'];
            }

            if ($this->hasValidCoordinates($location)) {
                $latSum += (float) $location['latitude'];
                $lonSum += (float) $location['longitude'];
                $coordCount++;
            }
        }

        $aliases = array_values(array_unique($aliases));
        $iataCodes = array_values(array_unique($iataCodes));
        $resolvedIata = count($iataCodes) === 1 ? $iataCodes[0] : null;

        $avgLat = $coordCount > 0 ? $latSum / $coordCount : 0;
        $avgLon = $coordCount > 0 ? $lonSum / $coordCount : 0;

        return [
            'unified_location_id' => $this->generateUnifiedLocationId($name, $city, $type, $countryCode, $resolvedIata),
            'name' => $name,
            'aliases' => $aliases,
            'city' => $city,
            'country' => $country,
            'country_code' => $countryCode,
            'latitude' => round($avgLat, 6),
            'longitude' => round($avgLon, 6),
            'location_type' => $type !== '' ? $type : 'unknown',
            'iata' => $resolvedIata,
            'providers' => $providers,
            'our_location_id' => $ourLocationId,
        ];
    }

    private function extractCityFromLabel(string $label, string $type): string
    {
        $label = trim($label);
        if ($label === '') {
            return '';
        }

        $cleanLabel = preg_replace('/\s*[\(\[]\s*[A-Za-z]{3}\s*[\)\]]\s*/', ' ', $label);
        $cleanLabel = trim(preg_replace('/\s+/', ' ', (string) $cleanLabel));
        $lowerLabel = strtolower($cleanLabel);

        $suffixes = [];
        if ($type === 'airport') {
            $suffixes = ['international airport', 'intl airport', 'intl. airport', 'airport'];
        } elseif ($type === 'downtown') {
            $suffixes = ['downtown', 'city center', 'centre ville', 'zentrum', 'central'];
        } elseif ($type === 'port') {
            $suffixes = ['port', 'harbour', 'harbor', 'ferry'];
        } elseif ($type === 'train') {
            $suffixes = ['train station', 'railway station', 'station', 'gare', 'bahnhof'];
        } else {
            $suffixes = [
                'international airport',
                'intl airport',
                'intl. airport',
                'airport',
                'downtown',
                'city center',
                'centre ville',
                'zentrum',
                'central',
                'port',
                'harbour',
                'harbor',
                'ferry',
                'train station',
                'railway station',
                'station',
                'gare',
                'bahnhof',
            ];
        }

        foreach ($suffixes as $suffix) {
            if (str_ends_with($lowerLabel, $suffix)) {
                $city = trim(substr($cleanLabel, 0, -strlen($suffix)));
                if ($city !== '') {
                    return $city;
                }
            }
        }

        return $cleanLabel;
    }

    private function extractIataCode(array $location): string
    {
        $iata = trim((string) ($location['iata'] ?? ''));
        if ($iata !== '' && preg_match('/^[A-Za-z]{3}$/', $iata)) {
            return strtoupper($iata);
        }

        $label = (string) ($location['label'] ?? $location['name'] ?? '');
        if (preg_match('/[\(\[]\s*([A-Za-z]{3})\s*[\)\]]/', $label, $m)) {
            return strtoupper($m[1]);
        }

        return '';
    }

    private function hasValidCoordinates(array $location): bool
    {
        $lat = $location['latitude'] ?? null;
        $lon = $location['longitude'] ?? null;

        if (!is_numeric($lat) || !is_numeric($lon)) {
            return false;
        }

        $lat = (float) $lat;
        $lon = (float) $lon;

        if ($lat == 0.0 && $lon == 0.0) {
            return false;
        }

        return !($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180);
    }

    private function saveUnifiedLocations(array $locations)
    {
        $filePath = public_path('unified_locations.json');
        $tmpPath = $filePath . '.tmp';

        $payload = json_encode($locations, JSON_PRETTY_PRINT);
        if ($payload === false) {
            $this->error('Failed to encode unified locations JSON: ' . json_last_error_msg());
            return;
        }

        try {
            \Illuminate\Support\Facades\File::put($tmpPath, $payload);

            $decoded = json_decode($payload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid unified locations JSON: ' . json_last_error_msg());
                \Illuminate\Support\Facades\File::delete($tmpPath);
                return;
            }

            if (\Illuminate\Support\Facades\File::exists($filePath)) {
                \Illuminate\Support\Facades\File::delete($filePath);
            }

            \Illuminate\Support\Facades\File::move($tmpPath, $filePath);
        } catch (\Exception $e) {
            $this->error('Failed to write unified locations file: ' . $e->getMessage());
            \Illuminate\Support\Facades\File::delete($tmpPath);
        }
    }

    private function normalizeTitleCase(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = str_replace(['_', '-'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return ucwords(strtolower($value));
    }

    private function normalizeCountryName(?string $country): ?string
    {
        $value = trim((string) $country);
        if ($value === '') {
            return null;
        }

        $normalized = $value;
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        if ($transliterated !== false && $transliterated !== '') {
            $normalized = $transliterated;
        }

        $key = strtoupper(preg_replace('/[^A-Z]/', '', $normalized));
        $map = [
            'TR' => 'Turkiye',
            'TURKEY' => 'Turkiye',
            'TURKIYE' => 'Turkiye',
        ];

        if (isset($map[$key])) {
            return $map[$key];
        }

        return $this->normalizeTitleCase($value);
    }

    private function resolveCountryMetadata(?string $country, ?string $countryCode = null): array
    {
        $rawCountry = trim((string) $country);
        $rawCountryCode = strtoupper(trim((string) $countryCode));

        if ($rawCountryCode === '' && preg_match('/^[A-Za-z]{2}$/', $rawCountry)) {
            $rawCountryCode = strtoupper($rawCountry);
            $rawCountry = '';
        }

        if ($rawCountry !== '' && ctype_digit($rawCountry)) {
            $rawCountry = '';
        }

        $normalizedCode = $rawCountryCode !== '' ? $rawCountryCode : null;
        $normalizedCountry = $rawCountry !== '' ? $this->normalizeCountryName($rawCountry) : null;

        if ($normalizedCode !== null) {
            $normalizedCountry = $this->countryNameFromCode($normalizedCode) ?? $normalizedCountry;
        } elseif ($normalizedCountry !== null) {
            $normalizedCode = $this->countryCodeFromName($normalizedCountry);
        }

        return [
            'name' => $normalizedCountry,
            'code' => $normalizedCode,
        ];
    }

    private function countryCodeFromName(string $countryName): ?string
    {
        $normalizedCountry = $this->normalizeCountryName($countryName);
        if ($normalizedCountry === null) {
            return null;
        }

        static $countryLookup = null;
        if ($countryLookup === null) {
            $countryLookup = [];

            if (class_exists(\ResourceBundle::class)) {
                $bundle = \ResourceBundle::create('en', 'ICUDATA-region');
                $countries = $bundle?->get('Countries');
                if ($countries instanceof \ResourceBundle) {
                    foreach ($countries as $code => $name) {
                        $code = strtoupper(trim((string) $code));
                        if (!preg_match('/^[A-Z]{2}$/', $code)) {
                            continue;
                        }

                        $normalizedName = $this->normalizeCountryName((string) $name);
                        if ($normalizedName !== null) {
                            $countryLookup[$this->locationSearchService->normalizeString($normalizedName)] = $code;
                        }
                    }
                }
            }

            foreach ($this->getCountryFallbackMap() as $code => $name) {
                $normalizedName = $this->normalizeCountryName($name);
                if ($normalizedName !== null) {
                    $countryLookup[$this->locationSearchService->normalizeString($normalizedName)] = $code;
                }
            }
        }

        $normalizedKey = $this->locationSearchService->normalizeString($normalizedCountry);
        return $countryLookup[$normalizedKey] ?? null;
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

        if ($displayName === null) {
            $fallback = $this->getCountryName($countryCode);
            $displayName = $fallback !== $countryCode ? $fallback : null;
        }

        return $displayName !== null ? $this->normalizeCountryName($displayName) : null;
    }

    private function providerLocationMetadataMap(): array
    {
        return [
            'provider_extended_location_code' => 'extended_location_code',
            'provider_extended_dropoff_code' => 'extended_dropoff_code',
            'provider_code' => 'provider_code',
            'country_code' => 'country_code',
            'iata' => 'iata',
        ];
    }

    private function generateUnifiedLocationId(
        string $name,
        string $city,
        string $type,
        ?string $countryCode = null,
        ?string $iata = null
    ): int {
        $identity = implode('|', [
            strtolower(trim($name)),
            strtolower(trim($city)),
            strtolower(trim($type)),
            strtoupper(trim((string) $countryCode)),
            strtoupper(trim((string) $iata)),
        ]);

        return (int) sprintf('%u', crc32($identity));
    }

    private function getCountryName($countryCode): ?string
    {
        $countries = $this->getCountryFallbackMap();

        return $countries[$countryCode] ?? $countryCode;
    }

    private function getCountryFallbackMap(): array
    {
        return [
            'AE' => 'United Arab Emirates',
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'ES' => 'Spain',
            'IT' => 'Italy',
            'FR' => 'France',
            'DE' => 'Germany',
            'GR' => 'Greece',
            'MA' => 'Morocco',
            'TR' => 'Turkiye',
            // Add more as needed
        ];
    }

    private function isSuspiciousCityName(string $city, string $label): bool
    {
        $normalizedCity = trim($city);
        if ($normalizedCity === '') {
            return true;
        }

        $normalizedCityKey = $this->locationSearchService->normalizeString($normalizedCity);
        $normalizedLabelKey = $this->locationSearchService->normalizeString($label);

        if ($normalizedCityKey === '') {
            return true;
        }

        if ($normalizedLabelKey !== '' && str_contains($normalizedLabelKey, $normalizedCityKey)) {
            return false;
        }

        return strlen($normalizedCityKey) <= 3;
    }

    private function selectRepresentativeLocationLabel(array $locations, string $city, string $type): ?string
    {
        $cityKey = $this->locationSearchService->normalizeString($city);
        $typeLabel = $type !== '' ? $this->normalizeTitleCase($type) : '';
        $genericKeys = array_filter([
            $cityKey,
            $typeLabel !== '' ? $this->locationSearchService->normalizeString(trim($city . ' ' . $typeLabel)) : null,
        ]);

        $bestLabel = null;
        $bestScore = null;

        foreach ($locations as $location) {
            $label = trim((string) ($location['label'] ?? $location['name'] ?? ''));
            if ($label === '') {
                continue;
            }

            $normalizedLabel = $this->locationSearchService->normalizeString($label);
            $isGeneric = in_array($normalizedLabel, $genericKeys, true);

            $score = $isGeneric ? 0 : 1000;
            if ($cityKey !== '' && str_contains($normalizedLabel, $cityKey)) {
                $score += 100;
            }
            if ($this->hasValidCoordinates($location)) {
                $score += 10;
            }
            $score += strlen($label);

            if ($bestLabel === null || $score > $bestScore) {
                $bestLabel = $label;
                $bestScore = $score;
            }
        }

        return $bestLabel;
    }
}
