<?php

namespace App\Services\Search;

use App\Models\Vehicle;
use App\Models\VendorLocation;
use App\Services\CountryCodeResolver;

class InternalLocationResolver
{
    private array $airportLocationCache = [];

    public function appendVerifiedProvider(array $location): array
    {
        $externalProviders = collect($location['providers'] ?? [])
            ->filter(fn ($provider) => is_array($provider))
            ->reject(fn (array $provider): bool => $this->isInternalProvider($provider))
            ->values()
            ->all();

        $internalLocation = $this->resolveForUnifiedLocation($location);

        $location['providers'] = $externalProviders;
        if ($internalLocation === null) {
            $location['our_location_id'] = null;
            $location['provider_count'] = count($location['providers']);

            return $location;
        }

        $location['providers'][] = $this->providerPayload($internalLocation, $location);
        $location['our_location_id'] = $this->internalLocationHash($internalLocation);
        $location['provider_count'] = count($location['providers']);

        return $location;
    }

    public function resolveForUnifiedLocation(?array $location): ?VendorLocation
    {
        if (! is_array($location) || $location === []) {
            return null;
        }

        if ($this->isAirportLocation($location)) {
            return $this->resolveAirportByIataAndCountry($location);
        }

        foreach (($location['providers'] ?? []) as $provider) {
            if (! is_array($provider) || ! $this->isInternalProvider($provider)) {
                continue;
            }

            $pickupId = trim((string) ($provider['pickup_id'] ?? ''));
            if ($pickupId === '' || ! ctype_digit($pickupId)) {
                continue;
            }

            $resolved = $this->activeLocationWithVehiclesById((int) $pickupId);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    public function activeLocationWithVehiclesById(int $locationId): ?VendorLocation
    {
        if ($locationId <= 0) {
            return null;
        }

        return VendorLocation::query()
            ->whereKey($locationId)
            ->where('is_active', true)
            ->whereHas('vehicles', fn ($query) => $query->whereIn('status', Vehicle::searchableStatuses()))
            ->first();
    }

    public function resolveForCriteria(?array $criteria): ?VendorLocation
    {
        if (! is_array($criteria) || $criteria === []) {
            return null;
        }

        $directId = trim((string) ($criteria['vendor_location_id'] ?? $criteria['id'] ?? ''));
        if ($directId !== '' && ctype_digit($directId)) {
            $resolved = $this->activeLocationWithVehiclesById((int) $directId);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        $iata = strtoupper(trim((string) ($criteria['iata'] ?? $criteria['iata_code'] ?? '')));
        if ($iata === '') {
            return null;
        }

        $countryCode = $this->countryCodeForLocation($criteria);
        if ($countryCode !== '') {
            return $this->resolveForUnifiedLocation([
                'location_type' => 'airport',
                'iata' => $iata,
                'country_code' => $countryCode,
            ]);
        }

        $matches = VendorLocation::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(location_type) = ?', ['airport'])
            ->whereRaw('UPPER(iata_code) = ?', [$iata])
            ->whereHas('vehicles', fn ($query) => $query->whereIn('status', Vehicle::searchableStatuses()))
            ->limit(2)
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    public function internalProviderPickupId(?array $location): ?string
    {
        $resolved = $this->resolveForUnifiedLocation($location);

        return $resolved?->id !== null ? (string) $resolved->id : null;
    }

    public function internalLocationHash(VendorLocation $location): string
    {
        $payload = implode('', [
            (string) ($location->city ?? ''),
            (string) ($location->state ?? ''),
            (string) ($location->country ?? ''),
            (string) ($location->name ?? ''),
        ]);

        return 'internal_'.md5($payload);
    }

    private function resolveAirportByIataAndCountry(array $location): ?VendorLocation
    {
        $iata = strtoupper(trim((string) ($location['iata'] ?? '')));
        $countryCode = $this->countryCodeForLocation($location);
        if ($iata === '' || $countryCode === '') {
            return null;
        }

        $cacheKey = $countryCode.'|'.$iata;
        if (array_key_exists($cacheKey, $this->airportLocationCache)) {
            return $this->airportLocationCache[$cacheKey];
        }

        return $this->airportLocationCache[$cacheKey] = VendorLocation::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(location_type) = ?', ['airport'])
            ->whereRaw('UPPER(iata_code) = ?', [$iata])
            ->whereRaw('UPPER(country_code) = ?', [$countryCode])
            ->whereHas('vehicles', fn ($query) => $query->whereIn('status', Vehicle::searchableStatuses()))
            ->orderBy('id')
            ->first();
    }

    private function providerPayload(VendorLocation $location, array $sourceLocation): array
    {
        $countryCode = strtoupper(trim((string) ($location->country_code ?: ($sourceLocation['country_code'] ?? ''))));
        $iata = strtoupper(trim((string) ($location->iata_code ?: ($sourceLocation['iata'] ?? ''))));

        return [
            'provider' => 'internal',
            'pickup_id' => (string) $location->id,
            'original_name' => $location->name,
            'dropoffs' => [],
            'latitude' => isset($location->latitude) ? (float) $location->latitude : null,
            'longitude' => isset($location->longitude) ? (float) $location->longitude : null,
            'supports_one_way' => false,
            'extended_location_code' => null,
            'extended_dropoff_code' => null,
            'country_code' => $countryCode !== '' ? $countryCode : null,
            'iata' => $iata !== '' ? $iata : null,
            'provider_code' => null,
        ];
    }

    private function isAirportLocation(array $location): bool
    {
        return strtolower(trim((string) ($location['location_type'] ?? ''))) === 'airport';
    }

    private function isInternalProvider(array $provider): bool
    {
        return strtolower(trim((string) ($provider['provider'] ?? ''))) === 'internal';
    }

    private function countryCodeForLocation(array $location): string
    {
        $countryCode = strtoupper(trim((string) ($location['country_code'] ?? '')));
        if ($countryCode !== '') {
            return $countryCode;
        }

        $country = trim((string) ($location['country'] ?? ''));
        if ($country === '') {
            return '';
        }

        return CountryCodeResolver::resolve($country);
    }
}
