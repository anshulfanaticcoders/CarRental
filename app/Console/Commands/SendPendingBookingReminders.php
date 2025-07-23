<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendPendingBookingReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-pending-booking-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends reminders for pending bookings to vendors.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sending pending booking reminders...');

        $pendingBookings = \App\Models\Booking::where('booking_status', 'pending')->get();

        foreach ($pendingBookings as $booking) {
            // Ensure the booking has a vehicle and customer relationship loaded
            $booking->load('vehicle.vendor', 'customer');

            if ($booking->vehicle && $booking->vehicle->vendor && $booking->customer) {
                // Notify the vendor (User model)
                $booking->vehicle->vendor->notify(new \App\Notifications\Booking\PendingBookingReminderNotification(
                    $booking,
                    $booking->customer,
                    $booking->vehicle
                ));
                $this->info("Reminder sent for booking #{$booking->booking_number} to vendor {$booking->vehicle->vendor->email}");
            } else {
                $this->warn("Skipping booking #{$booking->booking_number}: Missing vehicle, vendor, or customer data.");
            }
        }

        $this->info('Pending booking reminders sent successfully.');
    }
}
