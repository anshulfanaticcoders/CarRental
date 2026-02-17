<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateUnifiedLocationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'locations:update-unified';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates unified_locations.json by grouping locations by city and location type.';

    protected $greenMotionService;
    protected $okMobilityService;
    protected $locationSearchService;
    protected $locationMatchingService;
    protected $adobeCarService;
    protected $locautoRentService;
    protected $wheelsysService;
    protected $renteonService;
    protected $favricaService;
    protected $xdriveService;
    protected $sicilyByCarService;

    public function __construct(
        \App\Services\GreenMotionService $greenMotionService,
        \App\Services\OkMobilityService $okMobilityService,
        \App\Services\LocationSearchService $locationSearchService,
        \App\Services\LocationMatchingService $locationMatchingService,
        \App\Services\AdobeCarService $adobeCarService,
        \App\Services\LocautoRentService $locautoRentService,
        \App\Services\WheelsysService $wheelsysService,
        \App\Services\RenteonService $renteonService,
        \App\Services\FavricaService $favricaService,
        \App\Services\XDriveService $xdriveService,
        \App\Services\SicilyByCarService $sicilyByCarService
    ) {
        parent::__construct();
        $this->greenMotionService = $greenMotionService;
        $this->okMobilityService = $okMobilityService;
        $this->locationSearchService = $locationSearchService;
        $this->locationMatchingService = $locationMatchingService;
        $this->adobeCarService = $adobeCarService;
        $this->locautoRentService = $locautoRentService;
        $this->wheelsysService = $wheelsysService;
        $this->renteonService = $renteonService;
        $this->favricaService = $favricaService;
        $this->xdriveService = $xdriveService;
        $this->sicilyByCarService = $sicilyByCarService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to update unified locations...');

        $internalLocations = $this->getInternalVehicleLocations();
        $this->info('Fetched ' . count($internalLocations) . ' internal vehicle locations.');

        $okMobilityLocations = $this->fetchOkMobilityLocations();
        $this->info('Fetched ' . count($okMobilityLocations) . ' OK Mobility locations.');

        $adobeLocations = $this->fetchAdobeLocations();
        $this->info('Fetched ' . count($adobeLocations) . ' Adobe locations.');

        $greenMotionLocations = $this->fetchProviderLocations('greenmotion');
        $this->info('Fetched ' . count($greenMotionLocations) . ' GreenMotion locations.');

        $usaveLocations = $this->fetchProviderLocations('usave');
        $this->info('Fetched ' . count($usaveLocations) . ' U-SAVE locations.');

        $locautoLocations = $this->fetchLocautoLocations();
        $this->info('Fetched ' . count($locautoLocations) . ' Locauto Rent locations.');

        $wheelsysLocations = $this->fetchWheelsysLocations();
        $this->info('Fetched ' . count($wheelsysLocations) . ' Wheelsys locations.');

        $renteonLocations = $this->fetchRenteonLocations();
        $this->info('Fetched ' . count($renteonLocations) . ' Renteon locations.');

        $favricaLocations = $this->fetchFavricaLocations();
        $this->info('Fetched ' . count($favricaLocations) . ' Favrica locations.');

        $xdriveLocations = $this->fetchXDriveLocations();
        $this->info('Fetched ' . count($xdriveLocations) . ' XDrive locations.');

        $sicilyByCarLocations = $this->fetchSicilyByCarLocations();
        $this->info('Fetched ' . count($sicilyByCarLocations) . ' Sicily By Car locations.');

        $recordGoLocations = $this->fetchRecordGoLocations();
        $this->info('Fetched ' . count($recordGoLocations) . ' Record Go locations.');

        $unifiedLocations = $this->mergeAndNormalizeLocations(
            $internalLocations,
            $greenMotionLocations,
            $usaveLocations,
            $okMobilityLocations,
            $adobeLocations,
            $locautoLocations,
            $wheelsysLocations,
            $renteonLocations,
            $favricaLocations,
            $xdriveLocations,
            $sicilyByCarLocations,
            $recordGoLocations
        );
        $this->info('Merged into ' . count($unifiedLocations) . ' unique unified locations.');

        $this->saveUnifiedLocations(array_values($unifiedLocations));

        $this->info('Unified locations updated successfully!');
    }

    private function getInternalVehicleLocations(): array
    {
        return \App\Models\Vehicle::select('city', 'state', 'country', 'latitude', 'longitude', 'location', 'location_type')
            ->whereNotNull('city')
            ->whereNotNull('country')
            ->get()
            ->map(function ($vehicle) {
                // Use the location field for the label as requested
                // $label = !empty($vehicle->location) ? $vehicle->location : $vehicle->city;
                
                // Combine city and location_type for the label
                $label = $vehicle->city;
                if (!empty($vehicle->location_type)) {
                    $label .= ' ' . $vehicle->location_type;
                } elseif (!empty($vehicle->location)) {
                     $label = $vehicle->location;
                }

                return [
                    'id' => 'internal_' . md5($vehicle->city . $vehicle->state . $vehicle->country . $vehicle->location),
                    'provider_location_id' => 'internal_' . md5($vehicle->city . $vehicle->state . $vehicle->country . $vehicle->location),
                    'label' => $label,
                    'below_label' => implode(', ', array_filter([$vehicle->city, $vehicle->state, $vehicle->country])),
                    'location' => $vehicle->location,
                    'city' => $vehicle->city,
                    'state' => $vehicle->state,
                    'country' => $vehicle->country,
                    'latitude' => (float) $vehicle->latitude,
                    'longitude' => (float) $vehicle->longitude,
                    'location_type' => $vehicle->location_type,
                    'source' => 'internal',
                    'matched_field' => 'location',
                ];
            })
            ->unique(function ($item) {
                return $this->locationSearchService->normalizeString($item['label']) . $this->locationSearchService->normalizeString($item['below_label']);
            })
            ->values()
            ->toArray();
    }

    private function fetchProviderLocations(string $providerName): array
    {
        $allProviderLocations = [];
        $this->greenMotionService->setProvider($providerName);

        try {
            // 1. Get all countries
            $this->info("Fetching {$providerName} country list...");
            $xmlCountries = $this->greenMotionService->getCountryList();

            if (is_null($xmlCountries) || empty($xmlCountries)) {
                $this->error("Failed to retrieve country data from {$providerName} API.");
                \Illuminate\Support\Facades\Log::error("GreenMotionLocationsUpdateCommand: Failed to retrieve country data for {$providerName}.");
                return [];
            }

            $countries = [];
            $domCountries = new \DOMDocument();
            libxml_use_internal_errors(true);
            if (!@$domCountries->loadXML($xmlCountries)) {
                $errors = libxml_get_errors();
                foreach ($errors as $error) {
                    $this->error('XML Parsing Error (GetCountryList): ' . $error->message);
                }
                $this->error('Raw XML response from GetCountryList that failed parsing: ' . $xmlCountries);
                libxml_clear_errors();
                return [];
            }
            libxml_clear_errors();

            $xpathCountries = new \DOMXPath($domCountries);
            $countryNodes = $xpathCountries->query('//country');

            if ($countryNodes->length > 0) {
                foreach ($countryNodes as $countryNode) {
                    $countryIDNode = $xpathCountries->query('countryID', $countryNode)->item(0);
                    $countryID = $countryIDNode ? $countryIDNode->nodeValue : null;
                    $countryNameNode = $xpathCountries->query('countryName', $countryNode)->item(0);
                    $countryName = $countryNameNode ? $countryNameNode->nodeValue : 'Unknown Country';

                    if (!empty($countryID) && !empty($countryName)) {
                        $countries[] = [
                            'countryID' => $countryID,
                            'countryName' => $countryName,
                        ];
                    }
                }
            } else {
                $this->error('No country elements found in XML response for country list.');
                \Illuminate\Support\Facades\Log::error('GreenMotionLocationsUpdateCommand: No country elements found in XML response for country list.');
                return [];
            }

            $this->info(sprintf('Found %d %s countries. Fetching service areas for each...', count($countries), $providerName));

            // 2. For each country, get service areas
            foreach ($countries as $country) {
                $countryId = $country['countryID'];
                $countryName = $country['countryName'];
                $this->comment(sprintf('Fetching service areas for %s (ID: %s)...', $countryName, $countryId));

                $xmlServiceAreas = $this->greenMotionService->getServiceAreas($countryId);

                if (is_null($xmlServiceAreas) || empty($xmlServiceAreas)) {
                    $this->warn(sprintf('No service area data for %s (ID: %s) or API returned empty response. Skipping.', $countryName, $countryId));
                    \Illuminate\Support\Facades\Log::warning(sprintf('GreenMotionLocationsUpdateCommand: No service area data for %s (ID: %s).', $countryName, $countryId));
                    continue;
                }

                $domServiceAreas = new \DOMDocument();
                libxml_use_internal_errors(true);
                if (!@$domServiceAreas->loadXML($xmlServiceAreas)) {
                    $errors = libxml_get_errors();
                    foreach ($errors as $error) {
                        $this->error(sprintf('XML Parsing Error (Service Areas for %s): %s', $countryName, $error->message));
                    }
                    $this->error('Raw XML response for service areas for ' . $countryName . ' that failed parsing: ' . $xmlServiceAreas);
                    libxml_clear_errors();
                    continue;
                }
                libxml_clear_errors();

                $xpathServiceAreas = new \DOMXPath($domServiceAreas);
                $serviceAreaNodes = $xpathServiceAreas->query('//servicearea');

                if ($serviceAreaNodes->length > 0) {
                    foreach ($serviceAreaNodes as $serviceareaNode) {
                        $locationIDNode = $xpathServiceAreas->query('locationID', $serviceareaNode)->item(0);
                        $locationId = $locationIDNode ? $locationIDNode->nodeValue : null;
                        $nameNode = $xpathServiceAreas->query('name', $serviceareaNode)->item(0);
                        $serviceAreaName = $nameNode ? $nameNode->nodeValue : 'Unknown Service Area';

                        if (!$locationId) {
                            $this->warn('Skipping service area with no locationID: ' . $serviceAreaName . ' in ' . $countryName);
                            continue;
                        }

                        $xmlLocationInfo = $this->greenMotionService->getLocationInfo($locationId);

                        if (is_null($xmlLocationInfo) || empty($xmlLocationInfo)) {
                            $this->warn('GreenMotion getLocationInfo returned null or empty for ID: ' . $locationId . ' (' . $serviceAreaName . ') in ' . $countryName . '. Skipping.');
                            continue;
                        }

                        $domLocationInfo = new \DOMDocument();
                        libxml_use_internal_errors(true);
                        if (!@$domLocationInfo->loadXML($xmlLocationInfo)) {
                            $errors = libxml_get_errors();
                            foreach ($errors as $error) {
                                $this->error('XML Parsing Error (GetLocationInfo for ID ' . $locationId . ' in ' . $countryName . '): ' . $error->message);
                            }
                            $this->error('Raw XML response from GetLocationInfo for ID ' . $locationId . ' in ' . $countryName . ' that failed parsing: ' . $xmlLocationInfo);
                            libxml_clear_errors();
                            continue;
                        }
                        libxml_clear_errors();

                        $xpathLocationInfo = new \DOMXPath($domLocationInfo);
                        $locationInfoNode = $xpathLocationInfo->query('//location_info')->item(0);

                        if ($locationInfoNode) {
                            $loc = (object) [];
                            $loc->location_id = $xpathLocationInfo->query('location_id', $locationInfoNode)->item(0)?->nodeValue;
                            $loc->location_name = $xpathLocationInfo->query('location_name', $locationInfoNode)->item(0)?->nodeValue;
                            $loc->address_city = $xpathLocationInfo->query('address_city', $locationInfoNode)->item(0)?->nodeValue;
                            $loc->address_county = $xpathLocationInfo->query('address_county', $locationInfoNode)->item(0)?->nodeValue;
                            $loc->address_postcode = $xpathLocationInfo->query('address_postcode', $locationInfoNode)->item(0)?->nodeValue;
                            $loc->latitude = $xpathLocationInfo->query('latitude', $locationInfoNode)->item(0)?->nodeValue;
                            $loc->longitude = $xpathLocationInfo->query('longitude', $locationInfoNode)->item(0)?->nodeValue;

                            $allProviderLocations[] = [
                                'id' => $providerName . '_' . ($loc->location_id ?? $locationId),
                                'label' => $loc->location_name ?? $serviceAreaName,
                                'below_label' => implode(', ', array_filter([$loc->address_city, $loc->address_county, $loc->address_postcode])),
                                'location' => $loc->location_name ?? $serviceAreaName,
                                'city' => $loc->address_city,
                                'state' => $loc->address_county,
                                'country' => $countryName,
                                'latitude' => (float) ($loc->latitude ?? 0),
                                'longitude' => (float) ($loc->longitude ?? 0),
                                'source' => $providerName,
                                'matched_field' => 'location',
                                'provider_location_id' => $loc->location_id ?? $locationId,
                            ];
                        } else {
                            $this->warn("{$providerName} GetLocationInfo response for ID {$locationId} in {$countryName} did not contain location_info node.");
                            \Illuminate\Support\Facades\Log::warning("{$providerName} GetLocationInfo response for ID {$locationId} in {$countryName} did not contain location_info node. Raw XML: " . $xmlLocationInfo);
                        }
                    }
                } else {
                    $this->warn("{$providerName} GetServiceAreas response for {$countryName} did not contain any servicearea nodes.");
                    if ($xmlServiceAreas) {
                        $this->warn("Raw XML response from GetServiceAreas for {$countryName}: " . $xmlServiceAreas);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error("An unexpected error occurred while fetching {$providerName} locations: " . $e->getMessage());
            \Illuminate\Support\Facades\Log::error("Error fetching {$providerName} locations: " . $e->getMessage(), ['exception' => $e]);
        }
        return $allProviderLocations;
    }

    private function mergeAndNormalizeLocations(array ...$locationGroups): array
    {
        $allLocations = array_merge(...$locationGroups);

        if (empty($allLocations)) {
            return [];
        }

        $groups = [];
        foreach ($allLocations as $location) {
            $normalized = $this->normalizeLocationForGrouping($location);
            $key = $normalized['key'];
            if ($key === '|') {
                continue;
            }

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'city' => $normalized['city'],
                    'type' => $normalized['type'],
                    'locations' => [],
                ];
            }

            $groups[$key]['locations'][] = $normalized['location'];
        }

        $unifiedLocations = [];
        foreach ($groups as $group) {
            $unified = $this->buildUnifiedLocationFromGroup(
                $group['locations'],
                $group['city'],
                $group['type']
            );

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

        if ($type === '' || $type === 'unknown') {
            $type = $this->locationMatchingService->detectLocationType($location);
        }
        if ($type === 'unknown') {
            $type = '';
        }

        if ($city === '') {
            $city = $this->extractCityFromLabel($label, $type);
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
        $location['label'] = $label;
        $location['location_type'] = $typeDisplay !== '' ? $typeDisplay : 'unknown';

        return [
            'key' => $key,
            'city' => $cityDisplay,
            'type' => $typeDisplay,
            'location' => $location,
        ];
    }

    private function buildUnifiedLocationFromGroup(array $locations, string $city, string $type): array
    {
        if (empty($locations) || $city === '') {
            return [];
        }

        $typeLabel = $type !== '' ? $this->normalizeTitleCase($type) : '';
        $name = trim($city . ($typeLabel !== '' ? ' ' . $typeLabel : ''));

        $aliases = [];
        $providers = [];
        $ourLocationId = null;
        $iataCodes = [];
        $country = null;
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
                    'dropoffs' => [],
                ];

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

            if ($country === null && !empty($location['country'])) {
                $country = $location['country'];
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
            'unified_location_id' => crc32(strtolower($name)),
            'name' => $name,
            'aliases' => $aliases,
            'city' => $city,
            'country' => $country,
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

    private function fetchOkMobilityLocations(): array
    {
        $this->info('Fetching OK Mobility locations...');
        $xmlResponse = $this->okMobilityService->getStations();

        if (!$xmlResponse) {
            $this->error('Failed to retrieve locations from OK Mobility API.');
            return [];
        }

        $locations = [];

        $xmlObject = simplexml_load_string($xmlResponse);
        if ($xmlObject !== false) {
            $xmlObject->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xmlObject->registerXPathNamespace('get', 'http://www.OKGroup.es/RentaCarWebService/getWSDL');

            $stations = $xmlObject->xpath('//get:RentalStation');

            if (empty($stations)) {
                $stations = $xmlObject->xpath('//soap:Body//get:RentalStation');
            }

            if (empty($stations)) {
                $stations = $xmlObject->xpath('//RentalStation');
            }

            if (empty($stations)) {
                $this->error('No OK Mobility stations found in XML response');
                return [];
            }

            $this->info('Found ' . count($stations) . ' OK Mobility stations');

            foreach ($stations as $station) {
                $stationData = json_decode(json_encode($station), true);

                $stationId = is_array($stationData['StationID']) ? ($stationData['StationID'][0] ?? '') : ($stationData['StationID'] ?? '');
                $stationName = is_array($stationData['Station']) ? ($stationData['Station'][0] ?? '') : ($stationData['Station'] ?? '');
                $city = is_array($stationData['City']) ? ($stationData['City'][0] ?? '') : ($stationData['City'] ?? '');
                $countryId = is_array($stationData['CountryID']) ? ($stationData['CountryID'][0] ?? '') : ($stationData['CountryID'] ?? '');
                $address = '';
                if (isset($stationData['Address'])) {
                    $address = is_array($stationData['Address']) ? ($stationData['Address'][0] ?? '') : ($stationData['Address'] ?? '');
                }
                $locationType = '';
                if (isset($stationData['LocationType'])) {
                    $locationType = is_array($stationData['LocationType']) ? ($stationData['LocationType'][0] ?? '') : ($stationData['LocationType'] ?? '');
                }

                $latitude = 0;
                if (isset($stationData['Latitude'])) {
                    $latitude = is_array($stationData['Latitude']) ? (float) ($stationData['Latitude'][0] ?? 0) : (float) $stationData['Latitude'];
                }

                $longitude = 0;
                if (isset($stationData['Longitude'])) {
                    $longitude = is_array($stationData['Longitude']) ? (float) ($stationData['Longitude'][0] ?? 0) : (float) $stationData['Longitude'];
                }

                if (!$stationId || !$stationName) {
                    continue;
                }

                $normalizedCity = $this->normalizeTitleCase($city ?: $stationName);
                $normalizedType = $this->normalizeTitleCase($locationType);
                $hasKnownType = !empty($normalizedType) && strtolower($normalizedType) !== 'unknown';
                $displayName = $hasKnownType ? trim($normalizedCity . ' ' . $normalizedType) : $normalizedCity;
                $detectedType = $this->inferOkMobilityLocationTypeFromAddress($address);
                $finalType = strtolower(trim($locationType));
                if ($finalType === '' || $finalType === 'unknown') {
                    $finalType = $detectedType;
                }
                if ($finalType === '') {
                    $finalType = 'unknown';
                }

                $locations[] = [
                    'id' => 'okmobility_' . $stationId,
                    'label' => $displayName,
                    'below_label' => implode(', ', array_filter([$normalizedCity, $countryId])),
                    'location' => $displayName,
                    'city' => $normalizedCity,
                    'state' => null,
                    'country' => $countryId,
                    'latitude' => (float) $latitude,
                    'longitude' => (float) $longitude,
                    'source' => 'okmobility',
                    'matched_field' => 'location',
                    'provider_location_id' => $stationId,
                    'location_type' => $finalType,
                ];
            }
        } else {
            $this->error('Failed to parse OK Mobility XML response');
        }

        $this->info('Processed ' . count($locations) . ' OK Mobility locations');
        return $locations;
    }

    private function fetchAdobeLocations(): array
    {
        $this->info('Fetching Adobe Car locations...');
        $offices = $this->adobeCarService->getOfficeList();

        if (empty($offices)) {
            $this->error('Failed to retrieve locations from Adobe Car API.');
            return [];
        }

        $locations = [];
        foreach ($offices as $office) {
            // Basic validation for essential fields
            if (empty($office['code']) || empty($office['name']) || !isset($office['coordinates'][0], $office['coordinates'][1])) {
                continue;
            }

            $locations[] = [
                'id' => 'adobe_' . $office['code'],
                'label' => $office['name'],
                'below_label' => $office['address'] ?? '',
                'location' => $office['name'],
                'city' => null, // Adobe API does not provide a separate city field
                'state' => null, // Adobe API does not provide a separate state field
                'country' => 'Costa Rica', // Assuming all locations are in Costa Rica as per docs
                'latitude' => (float) $office['coordinates'][0],
                'longitude' => (float) $office['coordinates'][1],
                'source' => 'adobe',
                'matched_field' => 'location',
                'provider_location_id' => $office['code'],
            ];
        }

        return $locations;
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

    private function inferOkMobilityLocationTypeFromAddress(string $address): string
    {
        $address = strtolower(trim($address));
        if ($address === '') {
            return 'unknown';
        }

        $airportPattern = '/\b(aeropuerto|airport|aerodromo|terminal)\b/';
        $portPattern = '/\b(puerto|port|harbor|harbour|ferry|marina)\b/';
        $trainPattern = '/\b(train|railway|rail|station|estacion|gare|bahnhof)\b/';
        $downtownPattern = '/\b(downtown|city center|city centre|center|centre|centro|central)\b/';

        if (preg_match($airportPattern, $address)) {
            return 'airport';
        }
        if (preg_match($portPattern, $address)) {
            return 'port';
        }
        if (preg_match($trainPattern, $address)) {
            return 'train';
        }
        if (preg_match($downtownPattern, $address)) {
            return 'downtown';
        }

        return 'unknown';
    }

    private function fetchLocautoLocations(): array
    {
        $this->info('Fetching Locauto Rent locations...');

        // Locauto uses predefined locations, not API
        $xmlResponse = $this->locautoRentService->getLocations();

        // For Locauto, null response is expected since we use predefined locations
        $locations = $this->locautoRentService->parseLocationResponse($xmlResponse);

        $this->info('Fetched ' . count($locations) . ' Locauto Rent locations (predefined).');

        return $locations;
    }

    private function fetchWheelsysLocations(): array
    {
        $this->info('Fetching Wheelsys locations...');
        try {
            $response = $this->wheelsysService->getStations();

            if (empty($response) || !isset($response['Stations'])) {
                $this->error('Failed to retrieve stations from Wheelsys API or empty response.');
                \Illuminate\Support\Facades\Log::warning('Wheelsys API response missing Stations key.', ['response' => $response]);
                return [];
            }

            $locations = [];
            foreach ($response['Stations'] as $station) {
                // Safety checks for required fields
                if (empty($station['Code']) || empty($station['Name'])) {
                    continue;
                }

                $stationInfo = $station['StationInformation'] ?? [];
                $city = $stationInfo['City'] ?? null;
                // Country code is in the root station object in the docs example
                $country = $station['Country'] ?? null;
                $address = $stationInfo['Address'] ?? null;
                $zip = $stationInfo['ZipCode'] ?? null;

                $locations[] = [
                    'id' => 'wheelsys_' . $station['Code'],
                    'label' => $station['Name'],
                    'below_label' => implode(', ', array_filter([$address, $city, $country])),
                    'location' => $station['Name'],
                    'city' => $city,
                    'state' => null, // Not provided directly
                    'country' => $country,
                    'latitude' => (float) ($station['Lat'] ?? 0),
                    'longitude' => (float) ($station['Long'] ?? 0),
                    'source' => 'wheelsys',
                    'matched_field' => 'location',
                    'provider_location_id' => $station['Code'],
                ];
            }

            return $locations;

        } catch (\Exception $e) {
            $this->error('Error fetching Wheelsys locations: ' . $e->getMessage());
            return [];
        }
    }

    private function fetchRenteonLocations(): array
    {
        $this->info('Fetching Renteon locations...');
        try {
            $locations = $this->renteonService->getLocations();

            if (empty($locations)) {
                $this->error('Failed to retrieve locations from Renteon API or empty response.');
                \Illuminate\Support\Facades\Log::warning('Renteon API returned empty response.');
                return [];
            }

            $providerCodes = $this->resolveRenteonProviderCodes();
            $allowedLocationCodes = $this->resolveRenteonProviderLocationCodes($providerCodes);
            $allowedSet = !empty($allowedLocationCodes) ? array_fill_keys($allowedLocationCodes, true) : [];

            $formattedLocations = [];
            foreach ($locations as $location) {
                // Safety checks for required fields
                if (empty($location['Code']) || empty($location['Name'])) {
                    continue;
                }

                // Only include locations that are for pickup/dropoff
                if ($location['Category'] !== 'PickupDropoff' && $location['Category'] !== 'City') {
                    continue;
                }

                if (!empty($allowedSet) && !isset($allowedSet[$location['Code']])) {
                    continue;
                }

                $formattedLocations[] = [
                    'id' => 'renteon_' . $location['Code'],
                    'label' => $location['Name'],
                    'below_label' => $location['Path'],
                    'location' => $location['Name'],
                    'city' => $this->extractCityFromPath($location['Path']),
                    'state' => null, // Not provided in Renteon response
                    'country' => $this->getCountryName($location['CountryCode']),
                    'latitude' => 0, // Not provided in Renteon response
                    'longitude' => 0, // Not provided in Renteon response
                    'source' => 'renteon',
                    'matched_field' => 'location',
                    'provider_location_id' => $location['Code'],
                ];
            }

            $infoSuffix = '';
            if (!empty($providerCodes)) {
                $infoSuffix = ' for providers: ' . implode(', ', $providerCodes);
            }
            $this->info('Processed ' . count($formattedLocations) . ' Renteon locations (filtered from ' . count($locations) . ' total)' . $infoSuffix);
            return $formattedLocations;

        } catch (\Exception $e) {
            $this->error('Error fetching Renteon locations: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Renteon location fetch error: ' . $e->getMessage(), ['exception' => $e]);
            return [];
        }
    }

    private function resolveRenteonProviderCodes(): array
    {
        $allowedProviders = config('services.renteon.allowed_providers');
        $providerCode = config('services.renteon.provider_code');

        $codes = [];
        if (is_array($allowedProviders)) {
            $codes = $allowedProviders;
        } elseif (is_string($allowedProviders) && trim($allowedProviders) !== '') {
            $codes = array_map('trim', explode(',', $allowedProviders));
        }

        if (empty($codes) && is_string($providerCode) && trim($providerCode) !== '') {
            $codes[] = trim($providerCode);
        }

        $codes = array_values(array_filter($codes, static fn($value) => $value !== ''));
        return array_values(array_unique($codes));
    }

    private function resolveRenteonProviderLocationCodes(array $providerCodes): array
    {
        if (empty($providerCodes)) {
            return [];
        }

        $locationCodes = [];
        foreach ($providerCodes as $providerCode) {
            $details = $this->renteonService->getProviderDetails($providerCode);
            if (!is_array($details)) {
                continue;
            }

            $locations = $details['Locations'] ?? $details['locations'] ?? [];
            if (is_array($locations)) {
                foreach ($locations as $location) {
                    if (!is_array($location)) {
                        continue;
                    }
                    $code = $location['Code'] ?? $location['LocationCode'] ?? $location['code'] ?? $location['location_code'] ?? null;
                    if (is_string($code) && $code !== '') {
                        $locationCodes[] = $code;
                    }
                }
            }

            $offices = $details['Offices'] ?? $details['offices'] ?? [];
            if (is_array($offices)) {
                foreach ($offices as $office) {
                    if (!is_array($office)) {
                        continue;
                    }
                    $code = $office['LocationCode'] ?? $office['location_code'] ?? null;
                    if (is_string($code) && $code !== '') {
                        $locationCodes[] = $code;
                    }
                }
            }
        }

        $locationCodes = array_values(array_filter($locationCodes, static fn($value) => $value !== ''));
        return array_values(array_unique($locationCodes));
    }

    private function fetchFavricaLocations(): array
    {
        $this->info('Fetching Favrica locations...');

        try {
            $locations = $this->favricaService->getLocations();
            if (empty($locations)) {
                $this->error('Failed to retrieve locations from Favrica API or empty response.');
                \Illuminate\Support\Facades\Log::warning('Favrica API returned empty locations response.');
                return [];
            }

            $formattedLocations = [];
            foreach ($locations as $location) {
                if (empty($location['location_id']) || empty($location['location_name'])) {
                    continue;
                }

                [$lat, $lng] = $this->parseFavricaMapsPoint($location['maps_point'] ?? null);
                $address = trim((string) ($location['address'] ?? ''));
                $city = $this->extractFavricaCity($address, $location['location_name'] ?? '');
                $country = $this->normalizeCountryName($location['country'] ?? null);

                $locationName = trim((string) $location['location_name']);
                $formattedLocations[] = [
                    'id' => 'favrica_' . $location['location_id'],
                    'label' => $locationName,
                    'below_label' => $address !== '' ? $address : implode(', ', array_filter([$city, $country])),
                    'location' => $locationName,
                    'city' => $city,
                    'state' => null,
                    'country' => $country,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'source' => 'favrica',
                    'matched_field' => 'location',
                    'provider_location_id' => (string) $location['location_id'],
                    'location_type' => $this->resolveFavricaLocationType($location),
                    'iata' => trim((string) ($location['iata'] ?? '')) ?: null,
                ];
            }

            $this->info('Processed ' . count($formattedLocations) . ' Favrica locations (from ' . count($locations) . ' total).');
            return $formattedLocations;
        } catch (\Exception $e) {
            $this->error('Error fetching Favrica locations: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Favrica location fetch error: ' . $e->getMessage(), ['exception' => $e]);
            return [];
        }
    }

    private function fetchXDriveLocations(): array
    {
        $this->info('Fetching XDrive locations...');

        try {
            $locations = $this->xdriveService->getLocations();
            if (empty($locations)) {
                $this->error('Failed to retrieve locations from XDrive API or empty response.');
                \Illuminate\Support\Facades\Log::warning('XDrive API returned empty locations response.');
                return [];
            }

            $formattedLocations = [];
            foreach ($locations as $location) {
                if (empty($location['location_id']) || empty($location['location_name'])) {
                    continue;
                }

                [$lat, $lng] = $this->parseFavricaMapsPoint($location['maps_point'] ?? null);
                $address = trim((string) ($location['address'] ?? ''));
                $city = $this->extractFavricaCity($address, $location['location_name'] ?? '');
                $country = $location['country'] ?? null;

                $locationName = trim((string) $location['location_name']);
                $formattedLocations[] = [
                    'id' => 'xdrive_' . $location['location_id'],
                    'label' => $locationName,
                    'below_label' => $address !== '' ? $address : implode(', ', array_filter([$city, $country])),
                    'location' => $locationName,
                    'city' => $city,
                    'state' => null,
                    'country' => $country,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'source' => 'xdrive',
                    'matched_field' => 'location',
                    'provider_location_id' => (string) $location['location_id'],
                    'location_type' => $this->resolveFavricaLocationType($location),
                    'iata' => trim((string) ($location['iata'] ?? '')) ?: null,
                ];
            }

            $this->info('Processed ' . count($formattedLocations) . ' XDrive locations (from ' . count($locations) . ' total).');
            return $formattedLocations;
        } catch (\Exception $e) {
            $this->error('Error fetching XDrive locations: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('XDrive location fetch error: ' . $e->getMessage(), ['exception' => $e]);
            return [];
        }
    }

    private function fetchSicilyByCarLocations(): array
    {
        $this->info('Fetching Sicily By Car locations...');

        try {
            $rawLocations = $this->sicilyByCarService->listLocations();
            if (empty($rawLocations)) {
                $this->warn('No locations returned from Sicily By Car (or credentials missing).');
                return [];
            }

            $mapped = [];
            foreach ($rawLocations as $location) {
                if (!is_array($location)) {
                    continue;
                }

                $id = (string) ($location['id'] ?? '');
                $name = trim((string) ($location['name'] ?? ''));
                if ($id === '' || $name === '') {
                    continue;
                }

                $type = (string) ($location['type'] ?? '');
                $locationType = $this->mapSbcLocationType($type);

                $airportCode = trim((string) ($location['airportCode'] ?? ''));
                $address = $location['address'] ?? [];
                $city = is_array($address) ? (string) ($address['city'] ?? '') : '';
                $country = is_array($address) ? (string) ($address['country'] ?? '') : '';

                $belowParts = [];
                if (is_array($address)) {
                    $belowParts[] = trim((string) ($address['addressLineOne'] ?? ''));
                    $belowParts[] = trim((string) ($address['addressLineTwo'] ?? ''));
                    $belowParts[] = trim((string) ($address['city'] ?? ''));
                    $belowParts[] = trim((string) ($address['province'] ?? ''));
                    $belowParts[] = trim((string) ($address['country'] ?? ''));
                }

                $coords = $location['coordinates'] ?? [];
                $lat = is_array($coords) ? (float) ($coords['latitude'] ?? 0) : 0.0;
                $lng = is_array($coords) ? (float) ($coords['longitude'] ?? 0) : 0.0;

                $mapped[] = [
                    'id' => 'sicily_by_car_' . $id,
                    'label' => $name,
                    'below_label' => implode(', ', array_filter(array_map('trim', $belowParts), static fn($v) => $v !== '')),
                    'location' => $name,
                    'city' => $city !== '' ? $this->normalizeTitleCase($city) : null,
                    'state' => null,
                    'country' => $country !== '' ? strtoupper(trim($country)) : null,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'source' => 'sicily_by_car',
                    'matched_field' => 'location',
                    'provider_location_id' => $id,
                    'location_type' => $locationType,
                    'iata' => $airportCode !== '' ? strtoupper($airportCode) : null,
                ];
            }

            return $mapped;
        } catch (\Exception $e) {
            $this->error('Error fetching Sicily By Car locations: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Sicily By Car location fetch error: ' . $e->getMessage(), ['exception' => $e]);
            return [];
        }
    }

    private function mapSbcLocationType(string $type): string
    {
        $type = strtolower(trim($type));
        return match ($type) {
            'airport' => 'airport',
            'downtown' => 'downtown',
            'port' => 'port',
            'railway' => 'train',
            default => 'unknown',
        };
    }

    private function fetchRecordGoLocations(): array
    {
        $this->info('Loading Record Go branch list...');

        $branches = [
            'IC' => [
                34901 => 'Tenerife South',
                34902 => 'Las Palmas',
                34903 => 'Lanzarote',
                34904 => 'Chafiras',
            ],
            'IT' => [
                39001 => 'Palermo',
                39002 => 'Catania',
                39003 => 'Olbia',
                39004 => 'Cagliari',
                39005 => 'Rome',
                39006 => 'Milan Bergamo',
            ],
            'PT' => [
                35001 => 'Lisbon',
                35002 => 'Faro',
                35003 => 'Porto',
            ],
            'GR' => [
                30001 => 'Athens',
                30002 => 'Thessaloniki',
                30003 => 'Zakynthos',
                30004 => 'Rhodes',
            ],
        ];

        $locations = [];
        foreach ($branches as $country => $list) {
            foreach ($list as $branchId => $name) {
                $label = trim((string) $name);
                $countryCode = strtoupper((string) $country);
                $locations[] = [
                    'id' => 'recordgo_' . $branchId,
                    'label' => $label,
                    'below_label' => trim($label . ', ' . $countryCode),
                    'location' => $label,
                    'city' => $this->normalizeTitleCase($label),
                    'state' => null,
                    'country' => $countryCode,
                    'latitude' => null,
                    'longitude' => null,
                    'source' => 'recordgo',
                    'matched_field' => 'location',
                    'provider_location_id' => (string) $branchId,
                    'location_type' => 'unknown',
                    'iata' => null,
                ];
            }
        }

        return $locations;
    }

    private function parseFavricaMapsPoint(?string $mapsPoint): array
    {
        $mapsPoint = trim((string) $mapsPoint);
        if ($mapsPoint === '') {
            return [0.0, 0.0];
        }

        // DMS format (e.g. "25°15'16\"N 55°21'23\"E")
        $dmsPattern = '/(\d+(?:\.\d+)?)\s*[°º]\s*(\d+(?:\.\d+)?)\s*[\'’]\s*(\d+(?:\.\d+)?)\s*(?:\"|″)?\s*([NSEW])/iu';
        if (preg_match_all($dmsPattern, $mapsPoint, $dmsMatches, PREG_SET_ORDER) && count($dmsMatches) >= 2) {
            $coords = [];
            foreach ($dmsMatches as $m) {
                $deg = (float) $m[1];
                $min = (float) $m[2];
                $sec = (float) $m[3];
                $hem = strtoupper((string) $m[4]);
                $decimal = $deg + ($min / 60.0) + ($sec / 3600.0);
                if ($hem === 'S' || $hem === 'W') {
                    $decimal *= -1;
                }
                $coords[$hem] = $decimal;
            }

            $lat = $coords['N'] ?? $coords['S'] ?? null;
            $lng = $coords['E'] ?? $coords['W'] ?? null;
            if ($lat !== null && $lng !== null) {
                return [$lat, $lng];
            }
        }

        // Decimal / WKT / SRID formats
        preg_match_all('/-?\d+(?:\.\d+)?/', $mapsPoint, $matches);
        $numbers = $matches[0] ?? [];
        if (count($numbers) >= 2) {
            $a = (float) $numbers[count($numbers) - 2];
            $b = (float) $numbers[count($numbers) - 1];

            // WKT POINT is typically "lng lat"
            if (stripos($mapsPoint, 'point') !== false) {
                return [$b, $a];
            }

            // Heuristic: if first looks like longitude and second like latitude
            if (abs($a) > 90 && abs($b) <= 90) {
                return [$b, $a];
            }

            return [$a, $b];
        }

        return [0.0, 0.0];
    }

    private function extractFavricaCity(string $address, string $fallback): ?string
    {
        $address = trim($address);
        if ($address === '') {
            return trim($fallback) ?: null;
        }

        $parts = explode(',', $address);
        $city = trim($parts[0] ?? '');
        return $city !== '' ? $city : (trim($fallback) ?: null);
    }

    private function resolveFavricaLocationType(array $location): string
    {
        $iata = trim((string) ($location['iata'] ?? ''));
        $isAirport = strtolower((string) ($location['isairport'] ?? '')) === 'true';
        if ($iata !== '' || $isAirport) {
            return 'airport';
        }

        $name = strtolower((string) ($location['location_name'] ?? ''));
        if (str_contains($name, 'airport')) {
            return 'airport';
        }

        return 'unknown';
    }

    private function extractCityFromPath($path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // Extract city from path like "Abu Dhabi > Abu Dhabi airport"
        $parts = explode('>', $path);
        return trim($parts[0] ?? '');
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

        return $map[$key] ?? $value;
    }

    private function getCountryName($countryCode): ?string
    {
        $countries = [
            'AE' => 'United Arab Emirates',
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'ES' => 'Spain',
            'IT' => 'Italy',
            'FR' => 'France',
            'DE' => 'Germany',
            'GR' => 'Greece',
            'TR' => 'Turkiye',
            // Add more as needed
        ];

        return $countries[$countryCode] ?? $countryCode;
    }
}
