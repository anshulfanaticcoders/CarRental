<?php

namespace App\Services\Locations\Fetchers;

use App\Services\LocautoRentService;

class LocautoLocationFetcher extends AbstractProviderLocationFetcher
{
    public function __construct(
        private readonly LocautoRentService $locautoRentService
    ) {
    }

    public function key(): string
    {
        return 'locauto_rent';
    }

    public function fetch(): array
    {
        $xmlResponse = $this->locautoRentService->getLocations();

        return $this->locautoRentService->parseLocationResponse($xmlResponse);
    }
}
