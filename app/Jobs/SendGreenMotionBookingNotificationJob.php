<?php

namespace App\Jobs;

use App\Models\GreenMotionBooking;
use App\Models\User;
use App\Notifications\Booking\GreenMotionBookingCreatedAdminNotification;
use App\Notifications\Booking\GreenMotionBookingCreatedCustomerNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendGreenMotionBookingNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public GreenMotionBooking $booking)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $adminEmail = env('VITE_ADMIN_EMAIL', 'default@admin.com');
            $admin = User::where('email', $adminEmail)->first();
            if ($admin) {
                $admin->notify(new GreenMotionBookingCreatedAdminNotification($this->booking));
            }

            $customerDetails = $this->booking->customer_details;
            if (isset($customerDetails['email'])) {
                $customerNotifiable = new class(['email' => $customerDetails['email']]) extends \Illuminate\Foundation\Auth\User {
                    use \Illuminate\Notifications\Notifiable;
                    public $email;

                    public function routeNotificationForMail()
                    {
                        return $this->attributes['email'];
                    }
                };
                $customerNotifiable->notify(new GreenMotionBookingCreatedCustomerNotification($this->booking));
            }
            Log::info('Successfully sent GreenMotion booking notifications for booking ID: ' . $this->booking->id);
        } catch (\Exception $e) {
            Log::error('Failed to send GreenMotion booking notification from job: ' . $e->getMessage(), [
                'booking_id' => $this->booking->id,
                'exception' => $e,
            ]);
        }
    }
}
