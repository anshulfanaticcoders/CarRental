<?php

namespace App\Observers;

use App\Models\Booking;
use App\Services\PartnerReversalService;

/**
 * Single choke point for "this booking just died": every path that cancels,
 * rejects, fails or refunds a booking (admin, vendor, webhook, mobile API,
 * reservation job) routes through an Eloquent update, so the partner-money
 * reversal cannot be forgotten by any of them — or by paths added later.
 */
class BookingObserver
{
    public function updated(Booking $booking): void
    {
        $becameDead = ($booking->wasChanged('booking_status')
                && in_array($booking->booking_status, PartnerReversalService::DEAD_BOOKING_STATUSES, true))
            || ($booking->wasChanged('payment_status')
                && in_array($booking->payment_status, PartnerReversalService::DEAD_PAYMENT_STATUSES, true));

        if ($becameDead) {
            app(PartnerReversalService::class)->reverseFor(
                $booking,
                $booking->booking_status.'/'.$booking->payment_status
            );
        }
    }
}
