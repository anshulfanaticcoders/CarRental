<?php

namespace App\Services\Locations\Fetchers;

use App\Services\GreenMotionService;
use Illuminate\Support\Facades\Log;

abstract class AbstractGreenMotionLocationFetcher extends AbstractProviderLocationFetcher
{
    public function __construct(
        private readonly GreenMotionService $greenMotionService
    ) {
    }

    abstract protected function providerName(): string;

    public function key(): string
    {
        return $this->providerName();
    }

    public function fetch(): array
    {
        $providerName = $this->providerName();
        $allProviderLocations = [];

        $this->greenMotionService->setProvider($providerName);

        try {
            $xmlCountries = $this->greenMotionService->getCountryList();

            if (is_null($xmlCountries) || empty($xmlCountries)) {
                Log::error("GreenMotionLocationsUpdateCommand: Failed to retrieve country data for {$providerName}.");
                return [];
            }

            $countries = [];
            $domCountries = new \DOMDocument();
            libxml_use_internal_errors(true);
            if (!@$domCountries->loadXML($xmlCountries)) {
                libxml_clear_errors();
                return [];
            }
            libxml_clear_errors();

            $xpathCountries = new \DOMXPath($domCountries);
            $countryNodes = $xpathCountries->query('//country');

            if ($countryNodes->length === 0) {
                Log::error('GreenMotionLocationsUpdateCommand: No country elements found in XML response for country list.');
                return [];
            }

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

            foreach ($countries as $country) {
                $countryId = $country['countryID'];
                $countryName = $country['countryName'];
                $xmlServiceAreas = $this->greenMotionService->getServiceAreas($countryId);

                if (is_null($xmlServiceAreas) || empty($xmlServiceAreas)) {
                    Log::warning(sprintf('GreenMotionLocationsUpdateCommand: No service area data for %s (ID: %s).', $countryName, $countryId));
                    continue;
                }

                $domServiceAreas = new \DOMDocument();
                libxml_use_internal_errors(true);
                if (!@$domServiceAreas->loadXML($xmlServiceAreas)) {
                    libxml_clear_errors();
                    continue;
                }
                libxml_clear_errors();

                $xpathServiceAreas = new \DOMXPath($domServiceAreas);
                $serviceAreaNodes = $xpathServiceAreas->query('//servicearea');

                if ($serviceAreaNodes->length === 0) {
                    continue;
                }

                foreach ($serviceAreaNodes as $serviceareaNode) {
                    $locationIDNode = $xpathServiceAreas->query('locationID', $serviceareaNode)->item(0);
                    $locationId = $locationIDNode ? $locationIDNode->nodeValue : null;
                    $nameNode = $xpathServiceAreas->query('name', $serviceareaNode)->item(0);
                    $serviceAreaName = $nameNode ? $nameNode->nodeValue : 'Unknown Service Area';

                    if (!$locationId) {
                        continue;
                    }

                    $xmlLocationInfo = $this->greenMotionService->getLocationInfo($locationId);
                    if (is_null($xmlLocationInfo) || empty($xmlLocationInfo)) {
                        continue;
                    }

                    $domLocationInfo = new \DOMDocument();
                    libxml_use_internal_errors(true);
                    if (!@$domLocationInfo->loadXML($xmlLocationInfo)) {
                        libxml_clear_errors();
                        continue;
                    }
                    libxml_clear_errors();

                    $xpathLocationInfo = new \DOMXPath($domLocationInfo);
                    $locationInfoNode = $xpathLocationInfo->query('//location_info')->item(0);
                    if (!$locationInfoNode) {
                        continue;
                    }

                    $loc = (object) [];
                    $loc->location_id = $xpathLocationInfo->query('location_id', $locationInfoNode)->item(0)?->nodeValue;
                    $loc->location_name = $xpathLocationInfo->query('location_name', $locationInfoNode)->item(0)?->nodeValue;
                    $loc->address_city = $xpathLocationInfo->query('address_city', $locationInfoNode)->item(0)?->nodeValue;
                    $loc->address_county = $xpathLocationInfo->query('address_county', $locationInfoNode)->item(0)?->nodeValue;
                    $loc->address_postcode = $xpathLocationInfo->query('address_postcode', $locationInfoNode)->item(0)?->nodeValue;
                    $loc->latitude = $xpathLocationInfo->query('latitude', $locationInfoNode)->item(0)?->nodeValue;
                    $loc->longitude = $xpathLocationInfo->query('longitude', $locationInfoNode)->item(0)?->nodeValue;
                    $loc->iata = $xpathLocationInfo->query('iata', $locationInfoNode)->item(0)?->nodeValue;
                    $loc->is_airport = $xpathLocationInfo->query('is_airport', $locationInfoNode)->item(0)?->nodeValue;

                    $dropoffIds = [];
                    $onewayNodes = $xpathLocationInfo->query('oneway/location_id', $locationInfoNode);
                    if ($onewayNodes && $onewayNodes->length > 0) {
                        foreach ($onewayNodes as $owNode) {
                            $owId = trim($owNode->nodeValue);
                            if ($owId !== '') {
                                $dropoffIds[] = $owId;
                            }
                        }
                    }

                    $locationType = strtolower(trim((string) $loc->is_airport)) === 'y' ? 'airport' : '';

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
                        'iata' => trim((string) ($loc->iata ?? '')),
                        'location_type' => $locationType,
                        'dropoffs' => $dropoffIds,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error("Error fetching {$providerName} locations: " . $e->getMessage(), ['exception' => $e]);
        }

        return $allProviderLocations;
    }
}
