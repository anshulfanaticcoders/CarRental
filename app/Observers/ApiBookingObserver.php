<?php

namespace App\Observers;

use App\Jobs\SendPartnerBookingWebhook;
use App\Models\ApiBooking;

/**
 * Single choke point for partner callbacks: every status change — admin,
 * vendor web, vendor mobile, partner's own cancel, auto-expiry, or any path
 * added later — notifies the partner's webhook (when they registered one).
 */
class ApiBookingObserver
{
    public function updated(ApiBooking $booking): void
    {
        if (! $booking->wasChanged('status')) {
            return;
        }

        SendPartnerBookingWebhook::dispatch($booking->id, 'booking.'.$booking->status);
    }
}
