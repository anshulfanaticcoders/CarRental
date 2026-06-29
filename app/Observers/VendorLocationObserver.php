<?php

namespace App\Observers;

use App\Jobs\TriggerGatewayLocationSync;
use App\Models\VendorLocation;

class VendorLocationObserver
{
    private const SEARCH_FIELDS = [
        'name',
        'city',
        'state',
        'country',
        'country_code',
        'latitude',
        'longitude',
        'location_type',
        'iata_code',
        'is_active',
    ];

    public function created(VendorLocation $vendorLocation): void
    {
        $this->triggerSync('vendor_location.created');
    }

    public function updated(VendorLocation $vendorLocation): void
    {
        if ($vendorLocation->wasChanged(self::SEARCH_FIELDS)) {
            $this->triggerSync('vendor_location.updated');
        }
    }

    public function deleted(VendorLocation $vendorLocation): void
    {
        $this->triggerSync('vendor_location.deleted');
    }

    private function triggerSync(string $source): void
    {
        TriggerGatewayLocationSync::dispatch($source)->afterResponse();
    }
}
