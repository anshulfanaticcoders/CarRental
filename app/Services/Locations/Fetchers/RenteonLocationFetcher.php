<?php

namespace App\Services\Locations\Fetchers;

use App\Services\RenteonService;
use Illuminate\Support\Facades\Log;

class RenteonLocationFetcher extends AbstractProviderLocationFetcher
{
    public function __construct(
        private readonly RenteonService $renteonService
    ) {
    }

    public function key(): string
    {
        return 'renteon';
    }

    public function fetch(): array
    {
        try {
            $locations = $this->renteonService->getLocations();
            if (empty($locations)) {
                Log::warning('Renteon API returned empty response.');
                return [];
            }

            $providerCodes = $this->resolveProviderCodes();
            $allowedLocationCodes = $this->resolveProviderLocationCodes($providerCodes);
            $allowedSet = !empty($allowedLocationCodes) ? array_fill_keys($allowedLocationCodes, true) : [];
            $officeCoords = $this->buildOfficeCoordinateMap($providerCodes);

            $formattedLocations = [];
            foreach ($locations as $location) {
                if (empty($location['Code']) || empty($location['Name'])) {
                    continue;
                }
                if (($location['Category'] ?? null) !== 'PickupDropoff' && ($location['Category'] ?? null) !== 'City') {
                    continue;
                }
                if (!empty($allowedSet) && !isset($allowedSet[$location['Code']])) {
                    continue;
                }

                $coords = $officeCoords[$location['Code']] ?? [0, 0];
                $formattedLocations[] = [
                    'id' => 'renteon_' . $location['Code'],
                    'label' => $location['Name'],
                    'below_label' => $location['Path'],
                    'location' => $location['Name'],
                    'city' => $this->extractCityFromPath($location['Path']),
                    'state' => null,
                    'country' => $this->getCountryName($location['CountryCode'] ?? null),
                    'latitude' => (float) $coords[0],
                    'longitude' => (float) $coords[1],
                    'source' => 'renteon',
                    'matched_field' => 'location',
                    'provider_location_id' => $location['Code'],
                    'location_type' => strtolower(trim((string) ($location['Type'] ?? ''))) ?: 'unknown',
                ];
            }

            return $formattedLocations;
        } catch (\Exception $e) {
            Log::error('Renteon location fetch error: ' . $e->getMessage(), ['exception' => $e]);
            return [];
        }
    }

    private function resolveProviderCodes(): array
    {
        $allowedProviders = config('services.renteon.allowed_providers');
        $providerCode = config('services.renteon.provider_code');

        $codes = [];
        if (is_array($allowedProviders)) {
            $codes = $allowedProviders;
        } elseif (is_string($allowedProviders) && trim($allowedProviders) !== '') {
            $codes = array_map('trim', explode(',', $allowedProviders));
        }

        if (empty($codes) && is_string($providerCode) && trim($providerCode) !== '') {
            $codes[] = trim($providerCode);
        }

        $codes = array_values(array_filter($codes, static fn ($value) => $value !== ''));

        return array_values(array_unique($codes));
    }

    private function resolveProviderLocationCodes(array $providerCodes): array
    {
        if (empty($providerCodes)) {
            return [];
        }

        $locationCodes = [];
        foreach ($providerCodes as $providerCode) {
            $details = $this->renteonService->getProviderDetails($providerCode);
            if (!is_array($details)) {
                continue;
            }

            $locations = $details['Locations'] ?? $details['locations'] ?? [];
            if (is_array($locations)) {
                foreach ($locations as $location) {
                    if (!is_array($location)) {
                        continue;
                    }

                    $code = $location['Code'] ?? $location['LocationCode'] ?? $location['code'] ?? $location['location_code'] ?? null;
                    if (is_string($code) && $code !== '') {
                        $locationCodes[] = $code;
                    }
                }
            }

            $offices = $details['Offices'] ?? $details['offices'] ?? [];
            if (is_array($offices)) {
                foreach ($offices as $office) {
                    if (!is_array($office)) {
                        continue;
                    }

                    $code = $office['LocationCode'] ?? $office['location_code'] ?? null;
                    if (is_string($code) && $code !== '') {
                        $locationCodes[] = $code;
                    }
                }
            }
        }

        $locationCodes = array_values(array_filter($locationCodes, static fn ($value) => $value !== ''));

        return array_values(array_unique($locationCodes));
    }

    private function buildOfficeCoordinateMap(array $providerCodes): array
    {
        $coordMap = [];
        foreach ($providerCodes as $providerCode) {
            $details = $this->renteonService->getProviderDetails($providerCode);
            if (!is_array($details)) {
                continue;
            }

            $offices = $details['Offices'] ?? [];
            foreach ($offices as $office) {
                $locCode = $office['LocationCode'] ?? null;
                $lat = $office['Latitude'] ?? null;
                $lng = $office['Longitude'] ?? null;
                if ($locCode && is_numeric($lat) && is_numeric($lng) && ($lat != 0 || $lng != 0)) {
                    $coordMap[$locCode] = [(float) $lat, (float) $lng];
                }
            }
        }

        return $coordMap;
    }

    private function extractCityFromPath(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $parts = explode('>', $path);

        return trim($parts[0] ?? '');
    }
}
