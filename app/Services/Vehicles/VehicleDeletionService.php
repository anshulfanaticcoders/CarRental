<?php

namespace App\Services\Vehicles;

use App\Models\ApiBooking;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VehicleDeletionService
{
    public function delete(Vehicle $vehicle): void
    {
        $vehicle->loadMissing(['images', 'bookings.damageProtection']);

        foreach ($vehicle->images as $image) {
            $path = $image->image_path ?: $image->image_url;

            if ($path) {
                $this->deleteStoragePath($path);
            }
        }

        foreach ($vehicle->bookings as $booking) {
            $damageProtection = $booking->damageProtection;

            if (!$damageProtection) {
                continue;
            }

            foreach ($damageProtection->before_images ?? [] as $imageKey) {
                $this->deleteStoragePath('damage_protections/before/' . $imageKey);
            }

            foreach ($damageProtection->after_images ?? [] as $imageKey) {
                $this->deleteStoragePath('damage_protections/after/' . $imageKey);
            }
        }

        DB::transaction(function () use ($vehicle) {
            ApiBooking::query()->where('vehicle_id', $vehicle->id)->delete();
            $vehicle->delete();
        });
    }

    /**
     * @param \Illuminate\Support\Collection<int, Vehicle>|\Illuminate\Database\Eloquent\Collection<int, Vehicle>|array<int, Vehicle> $vehicles
     */
    public function deleteMany(iterable $vehicles): int
    {
        $deletedCount = 0;

        foreach ($vehicles as $vehicle) {
            $this->delete($vehicle);
            $deletedCount++;
        }

        return $deletedCount;
    }

    private function deleteStoragePath(?string $path): void
    {
        if (!$path) {
            return;
        }

        try {
            Storage::disk('upcloud')->delete($path);
        } catch (\Throwable $exception) {
        }
    }
}
