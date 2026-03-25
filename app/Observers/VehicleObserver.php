<?php

namespace App\Observers;

use App\Models\Vehicle;
use App\Services\VrooemGatewayService;
use Illuminate\Support\Facades\Log;

class VehicleObserver
{
    /**
     * Location fields that trigger a gateway sync when changed.
     */
    private const LOCATION_FIELDS = [
        'full_vehicle_address', 'latitude', 'longitude',
        'location', 'city', 'country', 'location_type',
    ];

    public function created(Vehicle $vehicle): void
    {
        if ($vehicle->full_vehicle_address && $vehicle->latitude) {
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
        if ($vehicle->full_vehicle_address) {
            $this->triggerSync();
        }
    }

    private function triggerSync(): void
    {
        // Dispatch async so we don't block the vendor's request
        dispatch(function () {
            try {
                app(VrooemGatewayService::class)->triggerLocationSync();
            } catch (\Exception $e) {
                Log::warning('VehicleObserver: Failed to trigger location sync', ['error' => $e->getMessage()]);
            }
        })->afterResponse();
    }
}
