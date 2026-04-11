<?php

namespace App\Services\Skyscanner;

use App\Models\Vehicle;
use App\Models\VendorLocation;
use App\Services\Search\InternalSearchVehicleFactory;
use App\Services\Vehicles\InternalVehicleAvailabilityService;
use Carbon\Carbon;

class CarHireInternalInventoryService
{
    public function __construct(
        private readonly InternalSearchVehicleFactory $internalSearchVehicleFactory,
        private readonly InternalVehicleAvailabilityService $internalVehicleAvailabilityService,
    ) {
    }

    public function search(array $criteria): array
    {
        $pickupDateTime = Carbon::parse($criteria['pickup_date'] . ' ' . $criteria['pickup_time']);
        $dropoffDateTime = Carbon::parse($criteria['dropoff_date'] . ' ' . $criteria['dropoff_time']);
        $rentalDays = max(1, (int) ceil($pickupDateTime->diffInMinutes($dropoffDateTime) / 1440));

        $canonicalLocation = VendorLocation::query()
            ->whereKey($criteria['pickup_location_id'])
            ->where('is_active', true)
            ->first();

        if (!$canonicalLocation) {
            return [];
        }

        $query = Vehicle::query()
            ->where('vendor_location_id', $canonicalLocation->id);

        $this->internalVehicleAvailabilityService->apply($query, [
            'pickup_date' => $criteria['pickup_date'],
            'pickup_time' => $criteria['pickup_time'],
            'dropoff_date' => $criteria['dropoff_date'],
            'dropoff_time' => $criteria['dropoff_time'],
        ]);

        return $query
            ->with(['images', 'vendor.profile', 'vendorProfile', 'vendorProfileData', 'benefits', 'vendorPlans', 'addons', 'operatingHours', 'category', 'vendorLocation'])
            ->get()
            ->map(function (Vehicle $vehicle) use ($criteria, $rentalDays, $canonicalLocation) {
                $data = $vehicle->toArray();
                $data['vendor_location_id'] = $canonicalLocation->id;
                $data['location'] = $canonicalLocation->name ?: ($data['location'] ?? null);
                $data['full_vehicle_address'] = $this->joinAddress([
                    $canonicalLocation->address_line_1,
                    $canonicalLocation->address_line_2,
                    $canonicalLocation->city,
                    $canonicalLocation->state,
                    $canonicalLocation->country,
                ]) ?: ($data['full_vehicle_address'] ?? null);
                $data['city'] = $canonicalLocation->city ?: ($data['city'] ?? null);
                $data['state'] = $canonicalLocation->state ?: ($data['state'] ?? null);
                $data['country'] = $canonicalLocation->country ?: ($data['country'] ?? null);
                $data['latitude'] = $canonicalLocation->latitude ?? ($data['latitude'] ?? null);
                $data['longitude'] = $canonicalLocation->longitude ?? ($data['longitude'] ?? null);
                $data['location_type'] = $canonicalLocation->location_type ?: ($data['location_type'] ?? null);
                $data['location_phone'] = $canonicalLocation->phone ?: ($data['location_phone'] ?? null);
                $data['pickup_instructions'] = $canonicalLocation->pickup_instructions ?: ($data['pickup_instructions'] ?? null);
                $data['dropoff_instructions'] = $canonicalLocation->dropoff_instructions ?: ($data['dropoff_instructions'] ?? null);
                $data['category_name'] = $vehicle->category->name ?? null;
                $data['operating_hours'] = $vehicle->operatingHours->map(function ($hours) {
                    return [
                        'day' => $hours->day_of_week,
                        'is_open' => $hours->is_open,
                        'open_time' => $hours->open_time,
                        'close_time' => $hours->close_time,
                    ];
                })->values()->all();
                $data['currency'] = $criteria['currency'] ?? ($data['currency'] ?? 'EUR');

                return $this->internalSearchVehicleFactory->make($data, $rentalDays, [
                    'pickup_location_id' => (string) $criteria['pickup_location_id'],
                    'dropoff_location_id' => (string) ($criteria['dropoff_location_id'] ?? $criteria['pickup_location_id']),
                ]);
            })
            ->values()
            ->all();
    }

    private function joinAddress(array $parts): ?string
    {
        $parts = array_values(array_filter(array_map(
            fn ($value) => $value !== null && trim((string) $value) !== '' ? trim((string) $value) : null,
            $parts
        )));

        return $parts === [] ? null : implode(', ', $parts);
    }
}
