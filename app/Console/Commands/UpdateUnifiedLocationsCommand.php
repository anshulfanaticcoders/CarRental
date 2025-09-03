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
    protected $description = 'Updates the unified_locations.json file with data from internal vehicles and GreenMotion API.';

    protected $greenMotionService;
    protected $locationSearchService;

    public function __construct(\App\Services\GreenMotionService $greenMotionService, \App\Services\LocationSearchService $locationSearchService)
    {
        parent::__construct();
        $this->greenMotionService = $greenMotionService;
        $this->locationSearchService = $locationSearchService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to update unified locations...');

        $internalLocations = $this->getInternalVehicleLocations();
        $this->info('Fetched ' . count($internalLocations) . ' internal vehicle locations.');

        $greenMotionLocations = $this->getGreenMotionLocations();
        $this->info('Fetched ' . count($greenMotionLocations) . ' GreenMotion locations.');

        $unifiedLocations = $this->mergeAndNormalizeLocations($internalLocations, $greenMotionLocations);
        $this->info('Merged ' . count($unifiedLocations) . ' unique unified locations.');

        $this->saveUnifiedLocations($unifiedLocations);

        $this->info('Unified locations updated successfully!');
    }

    private function getInternalVehicleLocations(): array
    {
        return \App\Models\Vehicle::select('city', 'state', 'country', 'latitude', 'longitude', 'location')
            ->whereNotNull('city')
            ->whereNotNull('country')
            ->get()
            ->map(function ($vehicle) {
                return [
                    'id' => 'internal_' . md5($vehicle->city . $vehicle->state . $vehicle->country . $vehicle->location),
                    'label' => $vehicle->city,
                    'below_label' => implode(', ', array_filter([$vehicle->state, $vehicle->country])),
                    'location' => $vehicle->location,
                    'city' => $vehicle->city,
                    'state' => $vehicle->state,
                    'country' => $vehicle->country,
                    'latitude' => (float) $vehicle->latitude,
                    'longitude' => (float) $vehicle->longitude,
                    'source' => 'internal',
                    'matched_field' => 'city', // Default to city for internal vehicles
                ];
            })
            ->unique(function ($item) {
                return $this->locationSearchService->normalizeString($item['label']) . $this->locationSearchService->normalizeString($item['below_label']);
            })
            ->values()
            ->toArray();
    }

    private function getGreenMotionLocations(): array
    {
        $allGreenMotionLocations = [];

        try {
            // 1. Get all countries
            $this->info('Fetching GreenMotion country list...');
            $xmlCountries = $this->greenMotionService->getCountryList();

            if (is_null($xmlCountries) || empty($xmlCountries)) {
                $this->error('Failed to retrieve country data from GreenMotion API.');
                \Illuminate\Support\Facades\Log::error('GreenMotionLocationsUpdateCommand: Failed to retrieve country data.');
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

            $this->info(sprintf('Found %d GreenMotion countries. Fetching service areas for each...', count($countries)));

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

                            $allGreenMotionLocations[] = [
                                'id' => 'gm_' . ($loc->location_id ?? $locationId),
                                'label' => $loc->location_name ?? $serviceAreaName,
                                'below_label' => implode(', ', array_filter([$loc->address_city, $loc->address_county, $loc->address_postcode])),
                                'location' => $loc->location_name ?? $serviceAreaName,
                                'city' => $loc->address_city,
                                'state' => $loc->address_county,
                                'country' => $countryName, // Use actual country name
                                'latitude' => (float) ($loc->latitude ?? 0),
                                'longitude' => (float) ($loc->longitude ?? 0),
                                'source' => 'greenmotion',
                                'matched_field' => 'location',
                                'greenmotion_location_id' => $loc->location_id ?? $locationId,
                            ];
                        } else {
                            $this->warn('GreenMotion GetLocationInfo response for ID ' . $locationId . ' in ' . $countryName . ' did not contain location_info node.');
                            \Illuminate\Support\Facades\Log::warning('GreenMotion GetLocationInfo response for ID ' . $locationId . ' in ' . $countryName . ' did not contain location_info node. Raw XML: ' . $xmlLocationInfo);
                        }
                    }
                } else {
                    $this->warn('GreenMotion GetServiceAreas response for ' . $countryName . ' did not contain any servicearea nodes.');
                    if ($xmlServiceAreas) {
                        $this->warn('Raw XML response from GetServiceAreas for ' . $countryName . ': ' . $xmlServiceAreas);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error('An unexpected error occurred while fetching GreenMotion locations: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Error fetching GreenMotion locations: ' . $e->getMessage(), ['exception' => $e]);
        }
        return $allGreenMotionLocations;
    }

    private function mergeAndNormalizeLocations(array $internal, array $greenMotion): array
    {
        $allLocations = array_merge($internal, $greenMotion);
        $uniqueLocations = [];
        $seenKeys = [];

        foreach ($allLocations as $location) {
            $normalizedLabel = $this->locationSearchService->normalizeString($location['label'] ?? '');
            $normalizedBelowLabel = $this->locationSearchService->normalizeString($location['below_label'] ?? '');
            $key = $normalizedLabel . '_' . $normalizedBelowLabel . '_' . $location['source']; // Include source in key for uniqueness

            if (!isset($seenKeys[$key])) {
                $uniqueLocations[] = $location;
                $seenKeys[$key] = true;
            }
        }
        return $uniqueLocations;
    }

    private function saveUnifiedLocations(array $locations)
    {
        $filePath = public_path('unified_locations.json');
        \Illuminate\Support\Facades\File::put($filePath, json_encode($locations, JSON_PRETTY_PRINT));
    }
}
