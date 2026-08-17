<?php

namespace App\Console\Commands;

use App\Models\ApiBooking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * A partner booking left `pending` blocks the vehicle's availability window —
 * on our own site and on every partner feed — and nothing ever released it:
 * no expiry column, no cleanup. A pending booking whose pickup time has come
 * and gone was never confirmed by anyone and is dead by definition; cancel it
 * so the inventory window frees up.
 */
class ExpireStalePartnerBookings extends Command
{
    protected $signature = 'api-bookings:expire-stale-pending
        {--dry-run : List what would be expired without changing anything}';

    protected $description = 'Cancel partner API bookings still pending after their pickup time has passed.';

    public function handle(): int
    {
        $stale = ApiBooking::query()
            ->where('status', 'pending')
            ->where('pickup_date', '<', now())
            ->get();

        if ($stale->isEmpty()) {
            $this->info('No stale pending partner bookings.');

            return self::SUCCESS;
        }

        foreach ($stale as $booking) {
            $this->line("#{$booking->booking_number}: pending past pickup ({$booking->pickup_date}) — expiring");

            if ($this->option('dry-run')) {
                continue;
            }

            $booking->update([
                'status' => 'cancelled',
                'cancellation_reason' => 'Auto-expired: still pending after the pickup time passed.',
                'cancelled_at' => now(),
            ]);

            Log::info('ExpireStalePartnerBookings: expired stale pending booking', [
                'api_booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'is_test' => (bool) $booking->is_test,
            ]);
        }

        return self::SUCCESS;
    }
}
