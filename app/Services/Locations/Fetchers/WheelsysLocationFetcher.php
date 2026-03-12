<?php

namespace App\Services\Locations\Fetchers;

use App\Services\WheelsysService;
use Illuminate\Support\Facades\Log;

class WheelsysLocationFetcher extends AbstractProviderLocationFetcher
{
    public function __construct(
        private readonly WheelsysService $wheelsysService
    ) {
    }

    public function key(): string
    {
        return 'wheelsys';
    }

    public function fetch(): array
    {
        try {
            $response = $this->wheelsysService->getStations();
            if (empty($response) || !isset($response['Stations'])) {
                Log::warning('Wheelsys API response missing Stations key.', ['response' => $response]);
                return [];
            }

            $locations = [];
            foreach ($response['Stations'] as $station) {
                if (empty($station['Code']) || empty($station['Name'])) {
                    continue;
                }

                $stationInfo = $station['StationInformation'] ?? [];
                $city = $stationInfo['City'] ?? null;
                $country = $station['Country'] ?? null;
                $address = $stationInfo['Address'] ?? null;

                $locations[] = [
                    'id' => 'wheelsys_' . $station['Code'],
                    'label' => $station['Name'],
                    'below_label' => implode(', ', array_filter([$address, $city, $country])),
                    'location' => $station['Name'],
                    'city' => $city,
                    'state' => null,
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
            Log::error('Error fetching Wheelsys locations: ' . $e->getMessage());
            return [];
        }
    }
}
