<?php

namespace App\Services\Locations;

class ProviderLocationFetchManager
{
    /**
     * @var array<string, ProviderLocationFetcherInterface>
     */
    private array $fetchers = [];

    /**
     * @param  iterable<ProviderLocationFetcherInterface>  $fetchers
     */
    public function __construct(iterable $fetchers)
    {
        foreach ($fetchers as $fetcher) {
            $this->fetchers[$fetcher->key()] = $fetcher;
        }
    }

    public function getInternalVehicleLocations(): array
    {
        return $this->fetchByKey('internal');
    }

    public function fetchProviderLocations(string $providerName): array
    {
        return $this->fetchByKey($providerName);
    }

    public function fetchOkMobilityLocations(): array
    {
        return $this->fetchByKey('okmobility');
    }

    public function fetchAdobeLocations(): array
    {
        return $this->fetchByKey('adobe');
    }

    public function fetchLocautoLocations(): array
    {
        return $this->fetchByKey('locauto_rent');
    }

    public function fetchWheelsysLocations(): array
    {
        return $this->fetchByKey('wheelsys');
    }

    public function fetchRenteonLocations(): array
    {
        return $this->fetchByKey('renteon');
    }

    public function fetchFavricaLocations(): array
    {
        return $this->fetchByKey('favrica');
    }

    public function fetchXDriveLocations(): array
    {
        return $this->fetchByKey('xdrive');
    }

    public function fetchSicilyByCarLocations(): array
    {
        return $this->fetchByKey('sicily_by_car');
    }

    public function fetchRecordGoLocations(): array
    {
        return $this->fetchByKey('recordgo');
    }

    public function fetchSurpriceLocations(): array
    {
        return $this->fetchByKey('surprice');
    }

    public function getRegisteredKeys(): array
    {
        return array_keys($this->fetchers);
    }

    public function fetchByKey(string $key): array
    {
        $fetcher = $this->fetchers[$key] ?? null;

        return $fetcher?->fetch() ?? [];
    }
}
