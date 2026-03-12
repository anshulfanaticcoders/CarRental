<?php

namespace App\Services\Locations\Fetchers;

use App\Services\SicilyByCarService;
use Illuminate\Support\Facades\Log;

class SicilyByCarLocationFetcher extends AbstractProviderLocationFetcher
{
    public function __construct(
        private readonly SicilyByCarService $sicilyByCarService
    ) {
    }

    public function key(): string
    {
        return 'sicily_by_car';
    }

    public function fetch(): array
    {
        try {
            $rawLocations = $this->sicilyByCarService->listLocations();
            if (empty($rawLocations)) {
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
                $locationType = $this->mapLocationType($type);
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

                if ($lat == 0 && $lng == 0) {
                    $fallback = $this->getFallbackCoords($airportCode, $id);
                    if ($fallback) {
                        $lat = $fallback[0];
                        $lng = $fallback[1];
                    }
                }

                $mapped[] = [
                    'id' => 'sicily_by_car_' . $id,
                    'label' => $name,
                    'below_label' => implode(', ', array_filter(array_map('trim', $belowParts), static fn ($value) => $value !== '')),
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
            Log::error('Sicily By Car location fetch error: ' . $e->getMessage(), ['exception' => $e]);
            return [];
        }
    }

    private function getFallbackCoords(string $iataCode, string $locationId): ?array
    {
        $byIata = [
            'VIE' => [48.1103, 16.5697],
            'FCO' => [41.8003, 12.2389],
            'LIN' => [45.4491, 9.2783],
            'MXP' => [45.6306, 8.7281],
            'PMO' => [38.1764, 13.0909],
            'CTA' => [37.4668, 15.0664],
        ];
        if ($iataCode !== '' && isset($byIata[strtoupper($iataCode)])) {
            return $byIata[strtoupper($iataCode)];
        }

        $byId = [
            'IT020' => [43.6839, 10.3927],
        ];

        return $byId[$locationId] ?? null;
    }

    private function mapLocationType(string $type): string
    {
        return match (strtolower(trim($type))) {
            'airport' => 'airport',
            'downtown' => 'downtown',
            'port' => 'port',
            'railway' => 'train',
            default => 'unknown',
        };
    }
}
