<?php

namespace App\Services\Locations\Fetchers;

use App\Services\FavricaService;
use Illuminate\Support\Facades\Log;

class FavricaLocationFetcher extends AbstractProviderLocationFetcher
{
    public function __construct(
        private readonly FavricaService $favricaService
    ) {
    }

    public function key(): string
    {
        return 'favrica';
    }

    public function fetch(): array
    {
        try {
            $locations = $this->favricaService->getLocations();
            if (empty($locations)) {
                Log::warning('Favrica API returned empty locations response.');
                return [];
            }

            return $this->mapLocations($locations, 'favrica');
        } catch (\Exception $e) {
            Log::error('Favrica location fetch error: ' . $e->getMessage(), ['exception' => $e]);
            return [];
        }
    }

    protected function mapLocations(array $locations, string $source): array
    {
        $formattedLocations = [];
        foreach ($locations as $location) {
            if (empty($location['location_id']) || empty($location['location_name'])) {
                continue;
            }

            [$lat, $lng] = $this->parseMapsPoint($location['maps_point'] ?? null);
            $address = trim((string) ($location['address'] ?? ''));
            $city = $this->extractCity($address, $location['location_name'] ?? '');
            $country = $this->normalizeCountryName($location['country'] ?? null);
            $locationName = trim((string) $location['location_name']);

            $formattedLocations[] = [
                'id' => $source . '_' . $location['location_id'],
                'label' => $locationName,
                'below_label' => $address !== '' ? $address : implode(', ', array_filter([$city, $country])),
                'location' => $locationName,
                'city' => $city,
                'state' => null,
                'country' => $country,
                'latitude' => $lat,
                'longitude' => $lng,
                'source' => $source,
                'matched_field' => 'location',
                'provider_location_id' => (string) $location['location_id'],
                'location_type' => $this->resolveLocationType($location),
                'iata' => trim((string) ($location['iata'] ?? '')) ?: null,
            ];
        }

        return $formattedLocations;
    }

    protected function parseMapsPoint(?string $mapsPoint): array
    {
        $mapsPoint = trim((string) $mapsPoint);
        if ($mapsPoint === '') {
            return [0.0, 0.0];
        }

        $mapsPoint = $this->normalizeMapsPointString($mapsPoint);
        $dmsPattern = "/(\d+(?:\.\d+)?)\s*D\s*(\d+(?:\.\d+)?)\s*M\s*(\d+(?:\.\d+)?)\s*S?\s*([NSEW])/i";
        if (preg_match_all($dmsPattern, $mapsPoint, $dmsMatches, PREG_SET_ORDER) && count($dmsMatches) >= 2) {
            $coords = [];
            foreach ($dmsMatches as $match) {
                $deg = (float) $match[1];
                $min = (float) $match[2];
                $sec = (float) $match[3];
                $hem = strtoupper((string) $match[4]);
                $decimal = $deg + ($min / 60.0) + ($sec / 3600.0);
                if ($hem === 'S' || $hem === 'W') {
                    $decimal *= -1;
                }
                $coords[$hem] = $decimal;
            }

            $lat = $coords['N'] ?? $coords['S'] ?? null;
            $lng = $coords['E'] ?? $coords['W'] ?? null;
            if ($lat !== null && $lng !== null) {
                return [$lat, $lng];
            }
        }

        $dmsGroupPattern = "/(\d+(?:\.\d+)?)\s*D\s*(\d+(?:\.\d+)?)\s*M\s*(\d+(?:\.\d+)?)\s*S?\s*([NSEW])?/i";
        if (preg_match_all($dmsGroupPattern, $mapsPoint, $dmsMatches, PREG_SET_ORDER) && count($dmsMatches) >= 2) {
            $decimals = [];
            $hemispheres = [];
            foreach ($dmsMatches as $match) {
                $deg = (float) $match[1];
                $min = (float) $match[2];
                $sec = (float) $match[3];
                $hem = !empty($match[4]) ? strtoupper($match[4]) : null;
                $decimal = $deg + ($min / 60.0) + ($sec / 3600.0);
                $decimals[] = $decimal;
                $hemispheres[] = $hem;
            }

            $lat = $decimals[0];
            $lng = $decimals[1];
            if ($hemispheres[0] === 'S') {
                $lat *= -1;
            }
            if ($hemispheres[1] === 'W') {
                $lng *= -1;
            }
            if ($hemispheres[1] === 'S') {
                $tmp = $lat;
                $lat = $lng;
                $lng = $tmp;
                $lat *= -1;
            }

            if ($lat != 0 || $lng != 0) {
                return [$lat, $lng];
            }
        }

        preg_match_all("/-?\d+(?:\.\d+)?/", $mapsPoint, $matches);
        $numbers = $matches[0] ?? [];
        if (count($numbers) >= 2) {
            $a = (float) $numbers[count($numbers) - 2];
            $b = (float) $numbers[count($numbers) - 1];

            if (stripos($mapsPoint, 'point') !== false) {
                return [$b, $a];
            }
            if (abs($a) > 90 && abs($b) <= 90) {
                return [$b, $a];
            }

            return [$a, $b];
        }

        return [0.0, 0.0];
    }

    protected function normalizeMapsPointString(string $value): string
    {
        $value = ltrim($value, " \t\n\r\0\x0B");
        if (substr($value, 0, 3) === "\xE2\x80\x93" || substr($value, 0, 3) === "\xE2\x80\x94") {
            $value = ltrim(substr($value, 3));
        }

        $value = str_replace("\xC2\xB0", 'D', $value);
        $value = str_replace("\xC2\xBA", 'D', $value);
        $value = str_replace("\xE2\x80\x99", 'M', $value);
        $value = str_replace("\xE2\x80\x98", 'M', $value);
        $value = str_replace("'", 'M', $value);
        $value = str_replace("\xE2\x80\x9D", 'S', $value);
        $value = str_replace("\xE2\x80\x9C", 'S', $value);
        $value = str_replace("\xE2\x80\xB3", 'S', $value);
        $value = str_replace('"', 'S', $value);

        return $value;
    }

    protected function extractCity(string $address, string $fallback): ?string
    {
        $address = trim($address);
        if ($address === '') {
            return trim($fallback) ?: null;
        }

        $parts = explode(',', $address);
        $city = trim($parts[0] ?? '');

        return $city !== '' ? $city : (trim($fallback) ?: null);
    }

    protected function resolveLocationType(array $location): string
    {
        $iata = trim((string) ($location['iata'] ?? ''));
        $isAirport = strtolower((string) ($location['isairport'] ?? '')) === 'true';
        if ($iata !== '' || $isAirport) {
            return 'airport';
        }

        $name = strtolower((string) ($location['location_name'] ?? ''));
        if (str_contains($name, 'airport')) {
            return 'airport';
        }

        return 'unknown';
    }
}
