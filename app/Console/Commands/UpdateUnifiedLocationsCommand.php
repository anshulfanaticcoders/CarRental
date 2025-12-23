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
    protected $description = 'Updates the unified_locations.json file with fuzzy matching to merge similar locations across providers.';

    protected $greenMotionService;
    protected $okMobilityService;
    protected $locationSearchService;
    protected $locationMatchingService;
    protected $adobeCarService;
    protected $locautoRentService;
    protected $wheelsysService;
    protected $renteonService;

    public function __construct(
        \App\Services\GreenMotionService $greenMotionService,
        \App\Services\OkMobilityService $okMobilityService,
        \App\Services\LocationSearchService $locationSearchService,
        \App\Services\LocationMatchingService $locationMatchingService,
        \App\Services\AdobeCarService $adobeCarService,
        \App\Services\LocautoRentService $locautoRentService,
        \App\Services\WheelsysService $wheelsysService,
        \App\Services\RenteonService $renteonService
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
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to update unified locations...');

        $internalLocations = $this->getInternalVehicleLocations();
        $this->info('Fetched ' . count($internalLocations) . ' internal vehicle locations.');

        $greenMotionLocations = $this->fetchProviderLocations('greenmotion');
        $this->info('Fetched ' . count($greenMotionLocations) . ' GreenMotion locations.');

        $usaveLocations = $this->fetchProviderLocations('usave');
        $this->info('Fetched ' . count($usaveLocations) . ' U-SAVE locations.');

        $okMobilityLocations = $this->fetchOkMobilityLocations();
        $this->info('Fetched ' . count($okMobilityLocations) . ' OK Mobility locations.');

        $adobeLocations = $this->fetchAdobeLocations();
        $this->info('Fetched ' . count($adobeLocations) . ' Adobe locations.');

        $locautoLocations = $this->fetchLocautoLocations();
        $this->info('Fetched ' . count($locautoLocations) . ' Locauto Rent locations.');

        $wheelsysLocations = $this->fetchWheelsysLocations();
        $this->info('Fetched ' . count($wheelsysLocations) . ' Wheelsys locations.');

        $renteonLocations = $this->fetchRenteonLocations();
        $this->info('Fetched ' . count($renteonLocations) . ' Renteon locations.');

        $unifiedLocations = $this->mergeAndNormalizeLocations($internalLocations, $greenMotionLocations, $usaveLocations, $okMobilityLocations, $adobeLocations, $locautoLocations, $wheelsysLocations, $renteonLocations);
        $this->info('Merged into ' . count($unifiedLocations) . ' unique unified locations.');

        $this->saveUnifiedLocations(array_values($unifiedLocations));

        $this->info('Unified locations updated successfully!');
    }

    private function getInternalVehicleLocations(): array
    {
        return \App\Models\Vehicle::select('city', 'state', 'country', 'latitude', 'longitude', 'location')
            ->whereNotNull('city')
            ->whereNotNull('country')
            ->get()
            ->map(function ($vehicle) {
                // Use the location field for the label as requested
                $label = !empty($vehicle->location) ? $vehicle->location : $vehicle->city;

                return [
                    'id' => 'internal_' . md5($vehicle->city . $vehicle->state . $vehicle->country . $vehicle->location),
                    'label' => $label,
                    'below_label' => implode(', ', array_filter([$vehicle->city, $vehicle->state, $vehicle->country])),
                    'location' => $vehicle->location,
                    'city' => $vehicle->city,
                    'state' => $vehicle->state,
                    'country' => $vehicle->country,
                    'latitude' => (float) $vehicle->latitude,
                    'longitude' => (float) $vehicle->longitude,
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

        $this->info('Starting fuzzy matching with ' . count($allLocations) . ' total locations...');
        $this->info('Using similarity threshold: ' . ($this->locationMatchingService->getSimilarityThreshold() * 100) . '%');

        // Use fuzzy matching to cluster similar locations
        $clusters = $this->locationMatchingService->clusterLocations($allLocations);

        $this->info('Created ' . count($clusters) . ' location clusters.');
        $this->info('Merged ' . (count($allLocations) - count($clusters)) . ' duplicate locations.');

        // Build unified locations from clusters
        $unifiedLocations = [];
        foreach ($clusters as $index => $cluster) {
            $unified = $this->locationMatchingService->buildUnifiedLocation($cluster);

            if (!empty($unified['name'])) {
                $unifiedLocations[] = $unified;

                // Log significant merges (clusters with more than 1 location)
                if (count($cluster) > 1) {
                    $providerNames = collect($unified['providers'])->pluck('provider')->unique()->join(', ');
                    $this->comment(sprintf(
                        '  Merged %d locations into: "%s" [%s]%s',
                        count($cluster),
                        $unified['name'],
                        $providerNames ?: 'internal',
                        $unified['our_location_id'] ? ' (+ internal)' : ''
                    ));
                }
            }
        }

        return $unifiedLocations;
    }


    private function saveUnifiedLocations(array $locations)
    {
        $filePath = public_path('unified_locations.json');
        \Illuminate\Support\Facades\File::put($filePath, json_encode($locations, JSON_PRETTY_PRINT));
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

        // Log the response for debugging
        $this->comment('OK Mobility XML Response (first 500 chars): ' . substr($xmlResponse, 0, 500));

        $xmlObject = simplexml_load_string($xmlResponse);
        if ($xmlObject !== false) {
            // Register the correct namespace based on the actual response
            $xmlObject->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xmlObject->registerXPathNamespace('get', 'http://www.OKGroup.es/RentaCarWebService/getWSDL');

            // Try different XPath expressions to find the stations
            $stations = $xmlObject->xpath('//get:RentalStation');

            if (empty($stations)) {
                // Try alternative paths
                $stations = $xmlObject->xpath('//soap:Body//get:RentalStation');
                $this->comment('Found stations with alternative path: ' . count($stations));
            }

            if (empty($stations)) {
                // Try without namespace
                $stations = $xmlObject->xpath('//RentalStation');
                $this->comment('Found stations without namespace: ' . count($stations));
            }

            if (empty($stations)) {
                $this->error('No OK Mobility stations found in XML response');
                $this->error('Full XML response: ' . $xmlResponse);
                return [];
            }

            $this->info('Found ' . count($stations) . ' OK Mobility stations');

            foreach ($stations as $station) {
                $stationData = json_decode(json_encode($station), true);

                // Handle both array and object formats with null checking
                $stationId = is_array($stationData['StationID']) ? ($stationData['StationID'][0] ?? '') : ($stationData['StationID'] ?? '');
                $stationName = is_array($stationData['Station']) ? ($stationData['Station'][0] ?? '') : ($stationData['Station'] ?? '');
                $city = is_array($stationData['City']) ? ($stationData['City'][0] ?? '') : ($stationData['City'] ?? '');
                $countryId = is_array($stationData['CountryID']) ? ($stationData['CountryID'][0] ?? '') : ($stationData['CountryID'] ?? '');

                // Handle Latitude/Longitude which might not exist
                $latitude = 0;
                if (isset($stationData['Latitude'])) {
                    $latitude = is_array($stationData['Latitude']) ? (float) ($stationData['Latitude'][0] ?? 0) : (float) $stationData['Latitude'];
                }

                $longitude = 0;
                if (isset($stationData['Longitude'])) {
                    $longitude = is_array($stationData['Longitude']) ? (float) ($stationData['Longitude'][0] ?? 0) : (float) $stationData['Longitude'];
                }

                $locations[] = [
                    'id' => 'okmobility_' . $stationId,
                    'label' => $stationName,
                    'below_label' => implode(', ', array_filter([$city, $countryId])),
                    'location' => $stationName,
                    'city' => $city,
                    'state' => null,
                    'country' => $countryId,
                    'latitude' => (float) $latitude,
                    'longitude' => (float) $longitude,
                    'source' => 'okmobility',
                    'matched_field' => 'location',
                    'provider_location_id' => $stationId,
                ];
            }
        } else {
            $this->error('Failed to parse OK Mobility XML response');
            $this->error('XML Response: ' . $xmlResponse);
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

            $this->info('Processed ' . count($formattedLocations) . ' Renteon locations (filtered from ' . count($locations) . ' total)');
            return $formattedLocations;

        } catch (\Exception $e) {
            $this->error('Error fetching Renteon locations: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Renteon location fetch error: ' . $e->getMessage(), ['exception' => $e]);
            return [];
        }
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
            // Add more as needed
        ];

        return $countries[$countryCode] ?? $countryCode;
    }
}
