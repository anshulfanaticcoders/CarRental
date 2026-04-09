<?php

namespace App\Services\Skyscanner;

use App\Models\Vehicle;
use App\Services\Search\InternalSearchVehicleFactory;
use Carbon\Carbon;

class CarHireInternalInventoryService
{
    public function __construct(
        private readonly InternalSearchVehicleFactory $internalSearchVehicleFactory,
    ) {
    }

    public function search(array $criteria): array
    {
        $referenceVehicle = Vehicle::query()
            ->whereKey($criteria['pickup_location_id'])
            ->whereIn('status', Vehicle::searchableStatuses())
            ->first();

        if (!$referenceVehicle) {
            return [];
        }

        $pickupDayOfWeek = Carbon::parse($criteria['pickup_date'])->dayOfWeekIso - 1;
        $dropoffDayOfWeek = Carbon::parse($criteria['dropoff_date'])->dayOfWeekIso - 1;
        $pickupDateTime = Carbon::parse($criteria['pickup_date'] . ' ' . $criteria['pickup_time']);
        $dropoffDateTime = Carbon::parse($criteria['dropoff_date'] . ' ' . $criteria['dropoff_time']);
        $rentalDays = max(1, (int) ceil($pickupDateTime->diffInMinutes($dropoffDateTime) / 1440));

        return Vehicle::query()
            ->whereIn('status', Vehicle::searchableStatuses())
            ->where('full_vehicle_address', $referenceVehicle->full_vehicle_address)
            ->where('location', $referenceVehicle->location)
            ->where('location_type', $referenceVehicle->location_type)
            ->where('city', $referenceVehicle->city)
            ->where('country', $referenceVehicle->country)
            ->when(
                $referenceVehicle->state === null,
                fn ($query) => $query->whereNull('state'),
                fn ($query) => $query->where('state', $referenceVehicle->state)
            )
            ->whereRaw('ROUND(latitude, 6) = ?', [round((float) $referenceVehicle->latitude, 6)])
            ->whereRaw('ROUND(longitude, 6) = ?', [round((float) $referenceVehicle->longitude, 6)])
            ->whereDoesntHave('bookings', function ($query) use ($criteria) {
                $query->whereNotIn('booking_status', ['cancelled', 'rejected'])
                    ->where(function ($nested) use ($criteria) {
                        $nested->whereBetween('bookings.pickup_date', [$criteria['pickup_date'], $criteria['dropoff_date']])
                            ->orWhereBetween('bookings.return_date', [$criteria['pickup_date'], $criteria['dropoff_date']])
                            ->orWhere(function ($overlap) use ($criteria) {
                                $overlap->where('bookings.pickup_date', '<=', $criteria['pickup_date'])
                                    ->where('bookings.return_date', '>=', $criteria['dropoff_date']);
                            });
                    });
            })
            ->whereDoesntHave('blockings', function ($query) use ($criteria) {
                $query->where(function ($nested) use ($criteria) {
                    $nested->whereBetween('blocking_start_date', [$criteria['pickup_date'], $criteria['dropoff_date']])
                        ->orWhereBetween('blocking_end_date', [$criteria['pickup_date'], $criteria['dropoff_date']])
                        ->orWhere(function ($overlap) use ($criteria) {
                            $overlap->where('blocking_start_date', '<=', $criteria['pickup_date'])
                                ->where('blocking_end_date', '>=', $criteria['dropoff_date']);
                        });
                });
            })
            ->whereHas('operatingHours', function ($query) use ($pickupDayOfWeek, $criteria) {
                $query->where('day_of_week', $pickupDayOfWeek)
                    ->where('is_open', true)
                    ->where('open_time', '<=', $criteria['pickup_time'])
                    ->where('close_time', '>=', $criteria['pickup_time']);
            })
            ->whereHas('operatingHours', function ($query) use ($dropoffDayOfWeek, $criteria) {
                $query->where('day_of_week', $dropoffDayOfWeek)
                    ->where('is_open', true)
                    ->where('open_time', '<=', $criteria['dropoff_time'])
                    ->where('close_time', '>=', $criteria['dropoff_time']);
            })
            ->with(['images', 'vendor.profile', 'vendorProfile', 'vendorProfileData', 'benefits', 'vendorPlans', 'addons', 'operatingHours', 'category'])
            ->get()
            ->map(function (Vehicle $vehicle) use ($criteria, $rentalDays) {
                $data = $vehicle->toArray();
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
}
