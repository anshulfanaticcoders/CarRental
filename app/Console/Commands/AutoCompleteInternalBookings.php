<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCompleteInternalBookings extends Command
{
    protected $signature = 'app:auto-complete-internal-bookings';

    protected $description = 'Automatically complete due internal bookings at their scheduled return date and time.';

    public function handle(): int
    {
        $now = now();
        $completedCount = 0;
        $completedBookingIds = [];

        Booking::query()
            ->where('booking_status', 'confirmed')
            ->where(function ($query) {
                $query->where('provider_source', 'internal')
                    ->orWhere(function ($legacyInternal) {
                        $legacyInternal
                            ->whereNull('provider_source')
                            ->whereNotNull('vehicle_id');
                    });
            })
            ->whereDate('return_date', '<=', $now->toDateString())
            ->orderBy('id')
            ->lazyById()
            ->each(function (Booking $booking) use ($now, &$completedCount, &$completedBookingIds) {
                if ($this->scheduledReturnAt($booking)->greaterThan($now)) {
                    return;
                }

                $booking->forceFill([
                    'booking_status' => 'completed',
                ])->save();

                $completedCount++;
                $completedBookingIds[] = $booking->id;
            });

        if ($completedCount > 0) {
            Log::info('AutoCompleteInternalBookings: completed due internal bookings', [
                'count' => $completedCount,
                'booking_ids' => $completedBookingIds,
            ]);
        }

        $this->info("Completed {$completedCount} internal bookings.");

        return self::SUCCESS;
    }

    private function scheduledReturnAt(Booking $booking): Carbon
    {
        $returnDate = $booking->return_date instanceof Carbon
            ? $booking->return_date->copy()
            : Carbon::parse($booking->getRawOriginal('return_date'));
        $returnTime = trim((string) $booking->return_time);

        if ($returnTime === '') {
            return $returnDate->endOfDay();
        }

        return Carbon::parse($returnDate->toDateString() . ' ' . $returnTime);
    }
}
