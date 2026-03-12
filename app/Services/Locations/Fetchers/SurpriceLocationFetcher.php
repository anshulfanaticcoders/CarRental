<?php

namespace App\Services\Locations\Fetchers;

use App\Services\SurpriceService;
use Illuminate\Support\Facades\Log;

class SurpriceLocationFetcher extends AbstractProviderLocationFetcher
{
    public function __construct(
        private readonly SurpriceService $surpriceService
    ) {
    }

    public function key(): string
    {
        return 'surprice';
    }

    public function fetch(): array
    {
        try {
            $stations = $this->surpriceService->getLocations(500);
            if (empty($stations)) {
                return [];
            }

            $locations = [];
            foreach ($stations as $station) {
                if (!is_array($station)) {
                    continue;
                }

                $locationCode = $station['locationCode'] ?? null;
                $extendedCode = $station['extendedLocationCode'] ?? $locationCode;
                $name = trim((string) ($station['name'] ?? ''));
                if ($locationCode === null || $name === '') {
                    continue;
                }

                $address = $station['address'] ?? [];
                $coords = $address['coordinates'] ?? [];
                $country = $address['country'] ?? [];
                $countryCode = strtoupper((string) ($country['code'] ?? ''));
                $city = trim((string) ($address['city'] ?? ''));
                $stationType = strtolower((string) ($station['stationType'] ?? 'office'));

                $lat = (float) ($coords['lat'] ?? $coords['latitude'] ?? 0);
                $lng = (float) ($coords['lon'] ?? $coords['longitude'] ?? 0);
                $iata = strlen((string) $locationCode) === 3 ? strtoupper((string) $locationCode) : null;

                $locations[] = [
                    'id' => 'surprice_' . $locationCode,
                    'label' => $name,
                    'below_label' => trim($name . ', ' . $countryCode),
                    'location' => $name,
                    'city' => $this->normalizeTitleCase($city),
                    'state' => null,
                    'country' => $countryCode,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'source' => 'surprice',
                    'matched_field' => 'location',
                    'provider_location_id' => $locationCode,
                    'provider_extended_location_code' => $extendedCode,
                    'location_type' => $stationType,
                    'iata' => $iata,
                ];
            }

            return $locations;
        } catch (\Exception $e) {
            Log::error('Surprice location fetch error: ' . $e->getMessage(), ['exception' => $e]);
            return [];
        }
    }
}
