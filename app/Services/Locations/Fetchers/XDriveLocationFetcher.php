<?php

namespace App\Services\Locations\Fetchers;

use App\Services\XDriveService;
use Illuminate\Support\Facades\Log;

class XDriveLocationFetcher extends FavricaLocationFetcher
{
    public function __construct(
        private readonly XDriveService $xdriveService
    ) {
    }

    public function key(): string
    {
        return 'xdrive';
    }

    public function fetch(): array
    {
        try {
            $locations = $this->xdriveService->getLocations();
            if (empty($locations)) {
                Log::warning('XDrive API returned empty locations response.');
                return [];
            }

            return $this->mapLocations($locations, 'xdrive');
        } catch (\Exception $e) {
            Log::error('XDrive location fetch error: ' . $e->getMessage(), ['exception' => $e]);
            return [];
        }
    }
}
