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
        // A deleted vehicle used to hard-delete its partner bookings with it —
        // no status guard, no notification. The partner's customer kept a
        // valid-looking VRO- reference for a booking that no longer existed
        // and found out at the counter. Active obligations block the delete.
        $this->assertNoActiveObligations($vehicle);

        $vehicle->loadMissing('images');

        foreach ($vehicle->images as $image) {
            $this->deleteStoragePath($image->image_path ?: $image->image_url);
            $this->deleteStoragePath($image->thumbnail_path ?: $image->thumbnail_url);
        }

        DB::transaction(function () use ($vehicle) {
            ApiBooking::query()->where('vehicle_id', $vehicle->id)->delete();
            $vehicle->delete();
        });
    }

    public function hasActiveObligations(Vehicle $vehicle): bool
    {
        $activePartnerBooking = ApiBooking::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('is_test', false)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('return_date', '>=', now()->startOfDay())
            ->exists();

        $activeCustomerBooking = $vehicle->bookings()
            ->whereNotIn('booking_status', ['cancelled', 'completed', 'rejected', 'expired'])
            ->where('return_date', '>=', now()->startOfDay())
            ->exists();

        return $activePartnerBooking || $activeCustomerBooking;
    }

    private function assertNoActiveObligations(Vehicle $vehicle): void
    {
        if ($this->hasActiveObligations($vehicle)) {
            throw new \RuntimeException(
                'Vehicle #'.$vehicle->id.' has active bookings (customer or partner API). '
                .'Cancel or complete them before deleting the vehicle.'
            );
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Vehicle>|\Illuminate\Database\Eloquent\Collection<int, Vehicle>|array<int, Vehicle>  $vehicles
     */
    public function deleteMany(iterable $vehicles): int
    {
        $deletedCount = 0;

        foreach ($vehicles as $vehicle) {
            // One blocked vehicle must not poison the whole batch — skip it,
            // log it, keep deleting the rest.
            if ($this->hasActiveObligations($vehicle)) {
                \Illuminate\Support\Facades\Log::warning('VehicleDeletionService: skipped vehicle with active bookings', [
                    'vehicle_id' => $vehicle->id,
                ]);

                continue;
            }

            $this->delete($vehicle);
            $deletedCount++;
        }

        return $deletedCount;
    }

    private function deleteStoragePath(?string $path): void
    {
        if (! $path) {
            return;
        }

        try {
            Storage::disk('upcloud')->delete($path);
        } catch (\Throwable $exception) {
        }
    }
}
