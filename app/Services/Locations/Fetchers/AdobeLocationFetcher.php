<?php

namespace App\Services\Locations\Fetchers;

use App\Services\AdobeCarService;

class AdobeLocationFetcher extends AbstractProviderLocationFetcher
{
    public function __construct(
        private readonly AdobeCarService $adobeCarService
    ) {
    }

    public function key(): string
    {
        return 'adobe';
    }

    public function fetch(): array
    {
        $offices = $this->adobeCarService->getOfficeList();

        if (empty($offices)) {
            return [];
        }

        $locations = [];
        foreach ($offices as $office) {
            if (empty($office['code']) || empty($office['name']) || !isset($office['coordinates'][0], $office['coordinates'][1])) {
                continue;
            }

            $city = $this->extractCity((string) ($office['address'] ?? ''), (string) $office['name']);
            $locationType = !empty($office['atAirport']) ? 'airport' : 'downtown';

            $locations[] = [
                'id' => 'adobe_' . $office['code'],
                'label' => $office['name'],
                'below_label' => $office['address'] ?? '',
                'location' => $office['name'],
                'city' => $city,
                'state' => null,
                'country' => 'Costa Rica',
                'latitude' => (float) $office['coordinates'][0],
                'longitude' => (float) $office['coordinates'][1],
                'source' => 'adobe',
                'matched_field' => 'location',
                'provider_location_id' => $office['code'],
                'location_type' => $locationType,
            ];
        }

        return $locations;
    }

    private function extractCity(string $address, string $officeName): ?string
    {
        $name = trim($officeName);
        $name = preg_replace('/\[.*?\]/', '', $name);
        $name = preg_replace('/\s*\/\s*.*$/', '', $name);
        $name = preg_replace('/\b(aeropuerto|airport|centro|downtown)\b/i', '', $name);
        $name = trim((string) $name);
        if ($name !== '') {
            return $this->normalizeTitleCase($name);
        }

        if ($address !== '') {
            $parts = array_map('trim', explode(',', $address));
            if (count($parts) >= 3) {
                return $this->normalizeTitleCase($parts[count($parts) - 3]);
            }
        }

        return null;
    }
}
