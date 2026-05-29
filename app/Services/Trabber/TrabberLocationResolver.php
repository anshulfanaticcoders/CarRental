<?php

namespace App\Services\Trabber;

use App\Models\Vehicle;
use App\Models\VendorLocation;
use App\Services\LocationSearchService;
use App\Services\Search\InternalLocationResolver;
use Illuminate\Support\Collection;

class TrabberLocationResolver
{
    public function __construct(
        private readonly LocationSearchService $locationSearchService,
        private readonly InternalLocationResolver $internalLocationResolver
    ) {}

    public function listLocations(): Collection
    {
        if (in_array((string) config('trabber.inventory_scope', 'mixed'), ['providers', 'mixed'], true)) {
            return $this->unifiedLocations();
        }

        return $this->canonicalLocations();
    }

    private function canonicalLocations(): Collection
    {
        return VendorLocation::query()
            ->where('is_active', true)
            ->whereHas('vehicles', fn ($query) => $query->whereIn('status', Vehicle::searchableStatuses()))
            ->orderBy('country_code')
            ->orderBy('city')
            ->orderBy('name')
            ->get()
            ->map(fn (VendorLocation $location) => [
                'id' => (string) $location->id,
                'name' => $location->name,
                'iata' => $location->iata_code,
                'city' => $location->city,
                'country' => $location->country,
                'country_code' => $location->country_code,
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
                'location_type' => $location->location_type,
                'providers' => ['internal'],
            ]);
    }

    private function unifiedLocations(): Collection
    {
        return collect($this->locationSearchService->getAllLocations(1000))
            ->filter(fn ($location) => is_array($location) && $this->supportsTrabberLocation($location))
            ->sortBy(fn (array $location) => sprintf(
                '%d|%s',
                strtolower((string) ($location['location_type'] ?? '')) === 'airport' ? 0 : 1,
                strtolower((string) ($location['name'] ?? ''))
            ))
            ->values()
            ->map(fn (array $location) => [
                'id' => (string) ($location['unified_location_id'] ?? ''),
                'name' => $this->nullableString($location['name'] ?? null),
                'iata' => $this->nullableString($location['iata'] ?? null),
                'city' => $this->nullableString($location['city'] ?? null),
                'country' => $this->nullableString($location['country'] ?? null),
                'country_code' => $this->nullableString($location['country_code'] ?? null),
                'latitude' => isset($location['latitude']) ? (float) $location['latitude'] : null,
                'longitude' => isset($location['longitude']) ? (float) $location['longitude'] : null,
                'location_type' => $this->nullableString($location['location_type'] ?? null),
                'providers' => $this->supportedProviders($location)->values()->all(),
            ])
            ->filter(fn (array $location) => $location['id'] !== '' && $location['name'] !== null);
    }

    public function resolve(?array $location): ?VendorLocation
    {
        if (! $location) {
            return null;
        }

        $resolved = $this->internalLocationResolver->resolveForCriteria($location);
        if ($resolved !== null) {
            return $resolved;
        }

        $geonameId = $this->nullableString($location['geoname_id'] ?? null);
        if ($geonameId !== null) {
            $resolved = VendorLocation::query()
                ->where('is_active', true)
                ->where(function ($query) use ($geonameId) {
                    $query->where('code', $geonameId)
                        ->orWhere('code', 'geoname:'.$geonameId);
                })
                ->first();

            if ($resolved) {
                return $resolved;
            }
        }

        $latitude = $this->nullableFloat($location['latitude'] ?? $location['lat'] ?? null);
        $longitude = $this->nullableFloat($location['longitude'] ?? $location['lng'] ?? $location['lon'] ?? null);
        if ($latitude === null || $longitude === null) {
            return null;
        }

        $maxDistanceKm = (float) config('trabber.location_radius_km', 50);

        return VendorLocation::query()
            ->where('is_active', true)
            ->whereHas('vehicles', fn ($query) => $query->whereIn('status', Vehicle::searchableStatuses()))
            ->get()
            ->map(function (VendorLocation $candidate) use ($latitude, $longitude) {
                $candidate->distance_km = $this->distanceKm(
                    $latitude,
                    $longitude,
                    (float) $candidate->latitude,
                    (float) $candidate->longitude
                );

                return $candidate;
            })
            ->filter(fn (VendorLocation $candidate) => $candidate->distance_km <= $maxDistanceKm)
            ->sortBy('distance_km')
            ->first();
    }

    public function resolveUnified(?array $location): ?array
    {
        if (! $location) {
            return null;
        }

        $unifiedLocationId = $this->nullableString($location['unified_location_id'] ?? null);
        if ($unifiedLocationId !== null && ctype_digit($unifiedLocationId)) {
            $resolved = $this->locationSearchService->getLocationByUnifiedId((int) $unifiedLocationId);
            if (is_array($resolved) && $resolved !== []) {
                return $resolved;
            }
        }

        $vendorLocation = $this->resolve($location);
        $locations = collect($this->locationSearchService->getAllLocations(1000))
            ->filter(fn ($candidate) => is_array($candidate));

        if ($vendorLocation) {
            $byInternalProvider = $locations->first(function (array $candidate) use ($vendorLocation): bool {
                return collect($candidate['providers'] ?? [])->contains(function ($provider) use ($vendorLocation): bool {
                    return is_array($provider)
                        && strtolower(trim((string) ($provider['provider'] ?? ''))) === 'internal'
                        && trim((string) ($provider['pickup_id'] ?? '')) === (string) $vendorLocation->id;
                });
            });

            if (is_array($byInternalProvider)) {
                return $byInternalProvider;
            }
        }

        $iata = strtoupper($this->nullableString($location['iata'] ?? $location['iata_code'] ?? $vendorLocation?->iata_code ?? null) ?? '');
        if ($iata !== '') {
            $byIata = $locations->first(fn (array $candidate) => strtoupper(trim((string) ($candidate['iata'] ?? ''))) === $iata);

            if (is_array($byIata)) {
                return $byIata;
            }
        }

        $latitude = $this->nullableFloat($location['latitude'] ?? $location['lat'] ?? $vendorLocation?->latitude ?? null);
        $longitude = $this->nullableFloat($location['longitude'] ?? $location['lng'] ?? $location['lon'] ?? $vendorLocation?->longitude ?? null);
        if ($latitude === null || $longitude === null) {
            return null;
        }

        $maxDistanceKm = (float) config('trabber.location_radius_km', 50);

        return $locations
            ->map(function (array $candidate) use ($latitude, $longitude) {
                $candidate['distance_km'] = $this->distanceKm(
                    $latitude,
                    $longitude,
                    (float) ($candidate['latitude'] ?? 0),
                    (float) ($candidate['longitude'] ?? 0)
                );

                return $candidate;
            })
            ->filter(fn (array $candidate) => ($candidate['distance_km'] ?? INF) <= $maxDistanceKm)
            ->sortBy('distance_km')
            ->first();
    }

    private function distanceKm(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $earthRadiusKm = 6371;
        $latDelta = deg2rad($toLat - $fromLat);
        $lngDelta = deg2rad($toLng - $fromLng);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($lngDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function supportsTrabberLocation(array $location): bool
    {
        return $this->supportedProviders($location)->isNotEmpty();
    }

    private function supportedProviders(array $location): \Illuminate\Support\Collection
    {
        $scope = (string) config('trabber.inventory_scope', 'mixed');
        $whitelist = collect(config('trabber.provider_whitelist', []))
            ->map(fn ($provider) => strtolower(trim((string) $provider)))
            ->filter()
            ->values();

        return collect($location['providers'] ?? [])
            ->filter(fn ($provider) => is_array($provider))
            ->map(fn (array $provider) => strtolower(trim((string) ($provider['provider'] ?? ''))))
            ->filter()
            ->filter(function (string $provider) use ($scope, $whitelist): bool {
                if ($scope === 'providers' && $provider === 'internal') {
                    return false;
                }

                return $whitelist->isEmpty() || $provider === 'internal' || $whitelist->contains($provider);
            })
            ->unique()
            ->values();
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
