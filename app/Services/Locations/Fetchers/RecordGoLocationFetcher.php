<?php

namespace App\Services\Locations\Fetchers;

class RecordGoLocationFetcher extends AbstractProviderLocationFetcher
{
    public function key(): string
    {
        return 'recordgo';
    }

    public function fetch(): array
    {
        $branches = [
            34901 => ['name' => 'Tenerife South', 'country' => 'IC', 'lat' => 28.0445, 'lng' => -16.5725, 'type' => 'airport', 'iata' => 'TFS'],
            34902 => ['name' => 'Las Palmas', 'country' => 'IC', 'lat' => 27.9319, 'lng' => -15.3866, 'type' => 'airport', 'iata' => 'LPA'],
            34903 => ['name' => 'Lanzarote', 'country' => 'IC', 'lat' => 28.9455, 'lng' => -13.6052, 'type' => 'airport', 'iata' => 'ACE'],
            34904 => ['name' => 'Chafiras', 'country' => 'IC', 'lat' => 28.0561, 'lng' => -16.6329, 'type' => 'downtown'],
            39001 => ['name' => 'Palermo', 'country' => 'IT', 'lat' => 38.1764, 'lng' => 13.0909, 'type' => 'airport', 'iata' => 'PMO'],
            39002 => ['name' => 'Catania', 'country' => 'IT', 'lat' => 37.4668, 'lng' => 15.0664, 'type' => 'airport', 'iata' => 'CTA'],
            39003 => ['name' => 'Olbia', 'country' => 'IT', 'lat' => 40.8986, 'lng' => 9.5176, 'type' => 'airport', 'iata' => 'OLB'],
            39004 => ['name' => 'Cagliari', 'country' => 'IT', 'lat' => 39.2515, 'lng' => 9.0543, 'type' => 'airport', 'iata' => 'CAG'],
            39005 => ['name' => 'Rome', 'country' => 'IT', 'lat' => 41.8003, 'lng' => 12.2389, 'type' => 'airport', 'iata' => 'FCO'],
            39006 => ['name' => 'Milan Bergamo', 'country' => 'IT', 'lat' => 45.6739, 'lng' => 9.7041, 'type' => 'airport', 'iata' => 'BGY'],
            35001 => ['name' => 'Lisbon', 'country' => 'PT', 'lat' => 38.7756, 'lng' => -9.1354, 'type' => 'airport', 'iata' => 'LIS'],
            35002 => ['name' => 'Faro', 'country' => 'PT', 'lat' => 37.0144, 'lng' => -7.9659, 'type' => 'airport', 'iata' => 'FAO'],
            35003 => ['name' => 'Porto', 'country' => 'PT', 'lat' => 41.2481, 'lng' => -8.6814, 'type' => 'airport', 'iata' => 'OPO'],
            30001 => ['name' => 'Athens', 'country' => 'GR', 'lat' => 37.9364, 'lng' => 23.9445, 'type' => 'airport', 'iata' => 'ATH'],
            30002 => ['name' => 'Thessaloniki', 'country' => 'GR', 'lat' => 40.5197, 'lng' => 22.9709, 'type' => 'airport', 'iata' => 'SKG'],
            30003 => ['name' => 'Zakynthos', 'country' => 'GR', 'lat' => 37.7509, 'lng' => 20.8843, 'type' => 'airport', 'iata' => 'ZTH'],
            30004 => ['name' => 'Rhodes', 'country' => 'GR', 'lat' => 36.4054, 'lng' => 28.0862, 'type' => 'airport', 'iata' => 'RHO'],
        ];

        $locations = [];
        foreach ($branches as $branchId => $info) {
            $label = trim((string) $info['name']);
            $countryCode = strtoupper((string) $info['country']);
            $locations[] = [
                'id' => 'recordgo_' . $branchId,
                'label' => $label,
                'below_label' => trim($label . ', ' . $countryCode),
                'location' => $label,
                'city' => $this->normalizeTitleCase($label),
                'state' => null,
                'country' => $countryCode,
                'latitude' => (float) $info['lat'],
                'longitude' => (float) $info['lng'],
                'source' => 'recordgo',
                'matched_field' => 'location',
                'provider_location_id' => (string) $branchId,
                'location_type' => $info['type'] ?? 'airport',
                'iata' => $info['iata'] ?? null,
            ];
        }

        return $locations;
    }
}
