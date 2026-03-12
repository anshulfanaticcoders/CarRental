<?php

namespace App\Services\Locations;

interface ProviderLocationFetcherInterface
{
    public function key(): string;

    public function fetch(): array;
}
