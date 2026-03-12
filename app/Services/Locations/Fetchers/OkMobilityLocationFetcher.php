<?php

namespace App\Services\Locations\Fetchers;

use App\Services\OkMobilityService;

class OkMobilityLocationFetcher extends AbstractProviderLocationFetcher
{
    public function __construct(
        private readonly OkMobilityService $okMobilityService
    ) {
    }

    public function key(): string
    {
        return 'okmobility';
    }

    public function fetch(): array
    {
        $xmlResponse = $this->okMobilityService->getStations();

        if (!$xmlResponse) {
            return [];
        }

        $locations = [];
        $countryMap = $this->buildCountryMap($this->okMobilityService->getCountries());
        $xmlObject = simplexml_load_string($xmlResponse);
        if ($xmlObject === false) {
            return [];
        }

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
            return [];
        }

        foreach ($stations as $station) {
            $stationData = json_decode(json_encode($station), true);
            $stationId = is_array($stationData['StationID']) ? ($stationData['StationID'][0] ?? '') : ($stationData['StationID'] ?? '');
            $stationName = is_array($stationData['Station']) ? ($stationData['Station'][0] ?? '') : ($stationData['Station'] ?? '');
            $city = is_array($stationData['City']) ? ($stationData['City'][0] ?? '') : ($stationData['City'] ?? '');
            $countryId = is_array($stationData['CountryID']) ? ($stationData['CountryID'][0] ?? '') : ($stationData['CountryID'] ?? '');
            $address = isset($stationData['Address']) ? (is_array($stationData['Address']) ? ($stationData['Address'][0] ?? '') : ($stationData['Address'] ?? '')) : '';
            $locationType = isset($stationData['LocationType']) ? (is_array($stationData['LocationType']) ? ($stationData['LocationType'][0] ?? '') : ($stationData['LocationType'] ?? '')) : '';
            $stationType = isset($stationData['StationType']) ? (is_array($stationData['StationType']) ? ($stationData['StationType'][0] ?? '') : ($stationData['StationType'] ?? '')) : '';
            $latitude = isset($stationData['Latitude']) ? (is_array($stationData['Latitude']) ? (float) ($stationData['Latitude'][0] ?? 0) : (float) $stationData['Latitude']) : 0;
            $longitude = isset($stationData['Longitude']) ? (is_array($stationData['Longitude']) ? (float) ($stationData['Longitude'][0] ?? 0) : (float) $stationData['Longitude']) : 0;

            if (!$stationId || !$stationName) {
                continue;
            }

            $normalizedCity = $this->normalizeTitleCase($city ?: $stationName);
            $countryInfo = $countryMap[(string) $countryId] ?? null;
            $countryName = $countryInfo['name'] ?? (!ctype_digit((string) $countryId) ? (string) $countryId : null);
            $countryCode = $countryInfo['code'] ?? null;
            $normalizedCountry = $this->normalizeCountryName($countryName);
            $finalType = $this->resolveLocationType($locationType, $stationType, $stationName, $address);
            $normalizedType = $this->normalizeTitleCase($finalType);
            $hasKnownType = !empty($normalizedType) && strtolower($normalizedType) !== 'unknown';
            $displayName = $hasKnownType ? trim($normalizedCity . ' ' . $normalizedType) : $normalizedCity;

            $locations[] = [
                'id' => 'okmobility_' . $stationId,
                'label' => $displayName,
                'below_label' => implode(', ', array_filter([$normalizedCity, $normalizedCountry])),
                'location' => $displayName,
                'city' => $normalizedCity,
                'state' => null,
                'country' => $normalizedCountry,
                'country_code' => $countryCode,
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
                'source' => 'okmobility',
                'matched_field' => 'location',
                'provider_location_id' => $stationId,
                'location_type' => $finalType,
            ];
        }

        return $locations;
    }

    private function buildCountryMap(mixed $xmlResponse): array
    {
        if (!is_string($xmlResponse) || trim($xmlResponse) === '') {
            return [];
        }

        $xmlObject = simplexml_load_string($xmlResponse);
        if ($xmlObject === false) {
            return [];
        }

        $xmlObject->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xmlObject->registerXPathNamespace('get', 'http://www.OKGroup.es/RentaCarWebService/getWSDL');

        $countries = $xmlObject->xpath('//Country');
        if (empty($countries)) {
            $countries = $xmlObject->xpath('//get:Country');
        }

        $countryMap = [];
        foreach ($countries as $country) {
            $countryData = json_decode(json_encode($country), true);
            $countryId = trim((string) ($countryData['countryCode'] ?? $countryData['CountryCode'] ?? ''));
            if ($countryId === '') {
                continue;
            }

            $countryMap[$countryId] = [
                'name' => $this->normalizeCountryName((string) ($countryData['country'] ?? $countryData['Country'] ?? '')),
                'code' => strtoupper(trim((string) ($countryData['ISOCode'] ?? $countryData['isoCode'] ?? ''))) ?: null,
            ];
        }

        return $countryMap;
    }

    private function resolveLocationType(?string $locationType, ?string $stationType, string $stationName, string $address): string
    {
        $locationType = strtolower(trim((string) $locationType));
        if ($locationType !== '' && $locationType !== 'unknown') {
            return $locationType;
        }

        if (trim((string) $stationType) === '2') {
            return 'airport';
        }

        $detectedType = $this->inferLocationType(trim($stationName . ' ' . $address));

        return $detectedType !== '' ? $detectedType : 'unknown';
    }

    private function inferLocationType(string $address): string
    {
        $address = strtolower(trim($address));
        if ($address === '') {
            return 'unknown';
        }

        if (preg_match('/\b(aeropuerto|airport|aerodromo|terminal)\b/', $address)) {
            return 'airport';
        }
        if (preg_match('/\b(puerto|port|harbor|harbour|ferry|marina)\b/', $address)) {
            return 'port';
        }
        if (preg_match('/\b(train|railway|rail|station|estacion|gare|bahnhof)\b/', $address)) {
            return 'train';
        }
        if (preg_match('/\b(downtown|city center|city centre|center|centre|centro|central)\b/', $address)) {
            return 'downtown';
        }

        return 'unknown';
    }
}
