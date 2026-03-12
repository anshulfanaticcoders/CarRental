<?php

namespace App\Services\Locations\Fetchers;

class GreenMotionLocationFetcher extends AbstractGreenMotionLocationFetcher
{
    protected function providerName(): string
    {
        return 'greenmotion';
    }
}
