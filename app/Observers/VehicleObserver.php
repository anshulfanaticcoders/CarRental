<?php

namespace App\Observers;

use App\Jobs\TriggerGatewayLocationSync;
use App\Models\Vehicle;

class VehicleObserver
{
    /**
     * Location fields that trigger a gateway sync when changed.
     */
    private const LOCATION_FIELDS = [
        'vendor_location_id', 'full_vehicle_address', 'latitude', 'longitude',
        'location', 'city', 'country', 'location_type', 'status',
    ];

    public function created(Vehicle $vehicle): void
    {
        if ($this->hasSyncableLocation($vehicle)) {
            $this->triggerSync();
        }
    }

    public function updated(Vehicle $vehicle): void
    {
        if ($vehicle->wasChanged(self::LOCATION_FIELDS)) {
            $this->triggerSync();
        }
    }

    public function deleted(Vehicle $vehicle): void
    {
        if ($this->hasSyncableLocation($vehicle)) {
            $this->triggerSync();
        }
    }

    private function hasSyncableLocation(Vehicle $vehicle): bool
    {
        return $vehicle->vendor_location_id !== null
            || (filled($vehicle->full_vehicle_address) && $vehicle->latitude !== null);
    }

    private function triggerSync(): void
    {
        TriggerGatewayLocationSync::dispatch('vehicle')->afterResponse();
    }
}
