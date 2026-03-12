<?php

namespace App\Services\Locations\Fetchers;

class USaveLocationFetcher extends AbstractGreenMotionLocationFetcher
{
    protected function providerName(): string
    {
        return 'usave';
    }
}
