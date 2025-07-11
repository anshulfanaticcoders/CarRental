<?php

namespace App\Listeners;

use App\Events\BookingCompleted; // Your existing booking event
use App\Services\TapfiliateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcessTapfiliateBookingConversion
{
    protected $tapfiliateService;

    public function __construct(TapfiliateService $tapfiliateService)
    {
        $this->tapfiliateService = $tapfiliateService;
    }

    public function handle(BookingCompleted $event)
    {
        $booking = $event->booking;
        $customer = $booking->customer; // Assuming booking has a customer relationship

        // Get the referrer's referral code if available (e.g., from a previous session or user's referred_by_user_id)
        // This part might need more specific logic based on how you link referred users to referrers.
        // For simplicity, let's assume the referred user has a 'referred_by_user_id'
        $referrerCode = null;
        if ($customer->referred_by_user_id) {
            $referrerUser = \App\Models\User::find($customer->referred_by_user_id);
            if ($referrerUser && $referrerUser->tapfiliateMapping) {
                $referrerCode = $referrerUser->tapfiliateMapping->referral_code;
            }
        }

        $this->tapfiliateService->trackConversion(
            'booking-' . $booking->id, // Unique ID for this booking conversion
            $booking->total_amount, // The amount of the booking
            $customer->id, // The ID of the customer who made the booking
            $referrerCode, // The referral code that led to this booking
            null, // Use default program ID
            ['booking_id' => $booking->id, 'customer_email' => $customer->email] // Optional metadata
        );
    }
}
