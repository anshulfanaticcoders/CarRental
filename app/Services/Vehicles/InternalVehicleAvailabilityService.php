<?php

namespace App\Services\Vehicles;

use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class InternalVehicleAvailabilityService
{
    private const NON_BLOCKING_BOOKING_STATUSES = ['cancelled', 'rejected', 'expired', 'completed'];
    private const NON_BLOCKING_API_BOOKING_STATUSES = ['cancelled'];

    public function apply(Builder $query, array $window): Builder
    {
        $normalized = $this->normalizeWindow($window);

        if (!$normalized['is_valid_window']) {
            $query->whereRaw('1 = 0');

            return $query;
        }

        $query->whereIn('status', Vehicle::searchableStatuses());

        $query->whereDoesntHave('bookings', function (Builder $bookingQuery) use ($normalized) {
            $bookingQuery
                ->whereNotIn('booking_status', self::NON_BLOCKING_BOOKING_STATUSES)
                ->where(function (Builder $overlapQuery) use ($normalized) {
                    $this->applyDateTimeOverlap(
                        $overlapQuery,
                        'pickup_date',
                        'pickup_time',
                        'return_date',
                        'return_time',
                        $normalized
                    );
                });
        });

        $query->whereDoesntHave('apiBookings', function (Builder $bookingQuery) use ($normalized) {
            $bookingQuery
                ->where('is_test', false)
                ->whereNotIn('status', self::NON_BLOCKING_API_BOOKING_STATUSES)
                ->where(function (Builder $overlapQuery) use ($normalized) {
                    $this->applyDateTimeOverlap(
                        $overlapQuery,
                        'pickup_date',
                        'pickup_time',
                        'return_date',
                        'return_time',
                        $normalized
                    );
                });
        });

        $query->whereDoesntHave('blockings', function (Builder $blockingQuery) use ($normalized) {
            $blockingQuery
                ->whereDate('blocking_start_date', '<=', $normalized['dropoff_date'])
                ->whereDate('blocking_end_date', '>=', $normalized['pickup_date']);
        });

        if ($normalized['has_times']) {
            $query->where(function (Builder $hoursQuery) use ($normalized) {
                $hoursQuery
                    ->whereDoesntHave('operatingHours')
                    ->orWhereHas('operatingHours', function (Builder $pickupHoursQuery) use ($normalized) {
                        $pickupHoursQuery->where('day_of_week', $normalized['pickup_day_of_week'])
                            ->where('is_open', true)
                            ->where('open_time', '<=', $normalized['pickup_time'])
                            ->where('close_time', '>=', $normalized['pickup_time']);
                    });
            });

            $query->where(function (Builder $hoursQuery) use ($normalized) {
                $hoursQuery
                    ->whereDoesntHave('operatingHours')
                    ->orWhereHas('operatingHours', function (Builder $dropoffHoursQuery) use ($normalized) {
                        $dropoffHoursQuery->where('day_of_week', $normalized['dropoff_day_of_week'])
                            ->where('is_open', true)
                            ->where('open_time', '<=', $normalized['dropoff_time'])
                            ->where('close_time', '>=', $normalized['dropoff_time']);
                    });
            });
        }

        return $query;
    }

    public function isVehicleAvailable(Vehicle $vehicle, array $window): bool
    {
        return $this->apply(
            Vehicle::query()->whereKey($vehicle->id),
            $window
        )->exists();
    }

    private function normalizeWindow(array $window): array
    {
        $pickupDate = Carbon::parse($window['pickup_date'])->toDateString();
        $dropoffDate = Carbon::parse($window['dropoff_date'])->toDateString();
        $pickupTime = isset($window['pickup_time']) ? trim((string) $window['pickup_time']) : null;
        $dropoffTime = isset($window['dropoff_time']) ? trim((string) $window['dropoff_time']) : null;
        $hasTimes = $pickupTime !== null && $pickupTime !== '' && $dropoffTime !== null && $dropoffTime !== '';
        $pickupDateTime = Carbon::parse($pickupDate . ' ' . ($hasTimes ? $pickupTime : '00:00'));
        $dropoffDateTime = Carbon::parse($dropoffDate . ' ' . ($hasTimes ? $dropoffTime : '23:59'));

        return [
            'pickup_date' => $pickupDate,
            'dropoff_date' => $dropoffDate,
            'pickup_time' => $hasTimes ? $pickupTime : '00:00',
            'dropoff_time' => $hasTimes ? $dropoffTime : '23:59',
            'pickup_day_of_week' => Carbon::parse($pickupDate)->dayOfWeekIso - 1,
            'dropoff_day_of_week' => Carbon::parse($dropoffDate)->dayOfWeekIso - 1,
            'has_times' => $hasTimes,
            'is_valid_window' => $dropoffDateTime->greaterThan($pickupDateTime),
        ];
    }

    private function applyDateTimeOverlap(
        Builder $query,
        string $startDateColumn,
        string $startTimeColumn,
        string $endDateColumn,
        string $endTimeColumn,
        array $window
    ): void {
        $query
            ->where(function (Builder $startsBeforeDropoff) use ($startDateColumn, $startTimeColumn, $window) {
                $startsBeforeDropoff
                    ->whereDate($startDateColumn, '<', $window['dropoff_date'])
                    ->orWhere(function (Builder $sameDropoffDate) use ($startDateColumn, $startTimeColumn, $window) {
                        $sameDropoffDate
                            ->whereDate($startDateColumn, '=', $window['dropoff_date'])
                            ->where(function (Builder $sameDayStartTime) use ($startTimeColumn, $window) {
                                $sameDayStartTime
                                    ->whereNull($startTimeColumn)
                                    ->orWhere($startTimeColumn, '<', $window['dropoff_time']);
                            });
                    });
            })
            ->where(function (Builder $endsAfterPickup) use ($endDateColumn, $endTimeColumn, $window) {
                $endsAfterPickup
                    ->whereDate($endDateColumn, '>', $window['pickup_date'])
                    ->orWhere(function (Builder $samePickupDate) use ($endDateColumn, $endTimeColumn, $window) {
                        $samePickupDate
                            ->whereDate($endDateColumn, '=', $window['pickup_date'])
                            ->where(function (Builder $sameDayEndTime) use ($endTimeColumn, $window) {
                                $sameDayEndTime
                                    ->whereNull($endTimeColumn)
                                    ->orWhere($endTimeColumn, '>', $window['pickup_time']);
                            });
                    });
            });
    }
}
