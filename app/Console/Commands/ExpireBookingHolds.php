<?php

namespace App\Console\Commands;

use App\Models\BookingHold;
use Illuminate\Console\Command;

class ExpireBookingHolds extends Command
{
    protected $signature = 'booking-holds:expire';

    protected $description = 'Expire stale checkout vehicle holds.';

    public function handle(): int
    {
        $count = BookingHold::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$count} booking hold(s).");

        return self::SUCCESS;
    }
}
