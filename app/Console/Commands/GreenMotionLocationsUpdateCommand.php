<?php

namespace App\Console\Commands;

use App\Services\GreenMotionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class GreenMotionLocationsUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'greenmotion:update-locations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches all GreenMotion locations globally and saves them to a JSON file.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(private GreenMotionService $greenMotionService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting GreenMotion locations update...');
        Log::info('GreenMotionLocationsUpdateCommand: Starting locations update.');

        $allLocations = [];

        try {
            // 1. Get all countries
            $this->info('Fetching country list...');
            $xmlCountries = $this->greenMotionService->getCountryList();

            if (is_null($xmlCountries) || empty($xmlCountries)) {
                $this->error('Failed to retrieve country data from GreenMotion API.');
                Log::error('GreenMotionLocationsUpdateCommand: Failed to retrieve country data.');
                return Command::FAILURE;
            }

            $countries = [];
            $dom = new \DOMDocument();
            @$dom->loadXML($xmlCountries);
            $xpath = new \DOMXPath($dom);

            $countryNodes = $xpath->query('//country');

            if ($countryNodes->length > 0) {
                foreach ($countryNodes as $countryNode) {
                    $countryID = '';
                    $countryName = '';

                    $countryIDNode = $countryNode->getElementsByTagName('countryID')->item(0);
                    if ($countryIDNode) {
                        $countryID = $countryIDNode->nodeValue;
                    }

                    $countryNameNode = $countryNode->getElementsByTagName('countryName')->item(0);
                    if ($countryNameNode) {
                        $countryName = $countryNameNode->nodeValue;
                    }

                    if (!empty($countryID) && !empty($countryName)) {
                        $countries[] = [
                            'countryID' => $countryID,
                            'countryName' => $countryName,
                        ];
                    }
                }
            } else {
                $this->error('No country elements found in XML response for country list.');
                Log::error('GreenMotionLocationsUpdateCommand: No country elements found in XML response for country list.');
                return Command::FAILURE;
            }

            $this->info(sprintf('Found %d countries. Fetching service areas for each...', count($countries)));

            // 2. For each country, get service areas
            foreach ($countries as $country) {
                $countryId = $country['countryID'];
                $countryName = $country['countryName'];
                $this->comment(sprintf('Fetching service areas for %s (ID: %s)...', $countryName, $countryId));

                $xmlServiceAreas = $this->greenMotionService->getServiceAreas($countryId);

                if (is_null($xmlServiceAreas) || empty($xmlServiceAreas)) {
                    $this->warn(sprintf('No service area data for %s (ID: %s) or API returned empty response. Skipping.', $countryName, $countryId));
                    Log::warning(sprintf('GreenMotionLocationsUpdateCommand: No service area data for %s (ID: %s).', $countryName, $countryId));
                    continue;
                }

                libxml_use_internal_errors(true);
                $xmlObjectServiceAreas = simplexml_load_string($xmlServiceAreas);

                if ($xmlObjectServiceAreas === false) {
                    $errors = libxml_get_errors();
                    foreach ($errors as $error) {
                        Log::error(sprintf('XML Parsing Error (Service Areas for %s): %s', $countryName, $error->message));
                    }
                    libxml_clear_errors();
                    $this->warn(sprintf('Failed to parse XML response for service areas for %s (ID: %s). Skipping.', $countryName, $countryId));
                    continue;
                }

                $serviceAreas = $this->parseServiceAreas($xmlObjectServiceAreas);
                $this->comment(sprintf('Found %d service areas for %s.', count($serviceAreas), $countryName));
                $allLocations = array_merge($allLocations, $serviceAreas);
            }

            // 3. Save aggregated data to JSON file
            $outputPath = public_path('greenmotion_locations.json');
            File::put($outputPath, json_encode($allLocations, JSON_PRETTY_PRINT));

            $this->info(sprintf('Successfully updated GreenMotion locations. Total %d locations saved to %s', count($allLocations), $outputPath));
            Log::info(sprintf('GreenMotionLocationsUpdateCommand: Successfully updated locations. Total %d locations.', count($allLocations)));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('An error occurred during the update: ' . $e->getMessage());
            Log::error('GreenMotionLocationsUpdateCommand: Error during update: ' . $e->getMessage(), ['exception' => $e]);
            return Command::FAILURE;
        }
    }

    /**
     * Helper to parse XML response for service areas.
     *
     * @param SimpleXMLElement $xmlObject
     * @return array
     */
    private function parseServiceAreas(SimpleXMLElement $xmlObject): array
    {
        $serviceAreas = [];

        // Use DOMDocument and DOMXPath for more robust XML parsing
        $dom = new \DOMDocument();
        // Suppress warnings/errors from loadXML for malformed XML, as SimpleXMLElement already handles errors
        @$dom->loadXML($xmlObject->asXML());
        $xpath = new \DOMXPath($dom);

        // Query for all 'servicearea' elements
        $nodes = $xpath->query('//servicearea');

        if ($nodes->length > 0) {
            foreach ($nodes as $serviceareaNode) {
                $locationID = '';
                $name = '';

                // Find child elements by tag name
                $locationIDNode = $serviceareaNode->getElementsByTagName('locationID')->item(0);
                if ($locationIDNode) {
                    $locationID = $locationIDNode->nodeValue;
                }

                $nameNode = $serviceareaNode->getElementsByTagName('name')->item(0);
                if ($nameNode) {
                    $name = $nameNode->nodeValue;
                }

                $serviceAreas[] = [
                    'locationID' => $locationID,
                    'name' => $name,
                ];
            }
        } else {
            Log::warning('No servicearea elements found using DOMXPath in XML response for parsing.');
        }
        return $serviceAreas;
    }
}
