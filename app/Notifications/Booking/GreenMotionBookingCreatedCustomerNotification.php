<?php

namespace App\Notifications\Booking;

use App\Models\GreenMotionBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GreenMotionBookingCreatedCustomerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public GreenMotionBooking $booking;

    /**
     * Create a new notification instance.
     */
    public function __construct(GreenMotionBooking $booking)
    {
        $this->booking = $booking;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $customerDetails = $this->booking->customer_details;
        $customerName = $customerDetails['firstname'] . ' ' . $customerDetails['surname'];

        return (new MailMessage)
            ->subject('Your GreenMotion Booking Confirmation')
            ->greeting('Hello ' . $customerName . ',')
            ->line('Thank you for your GreenMotion booking!')
            ->line('Your booking reference is: ' . $this->booking->greenmotion_booking_ref)
            ->line('Vehicle ID: ' . $this->booking->vehicle_id)
            ->line('Pickup Date: ' . $this->booking->start_date . ' at ' . $this->booking->start_time)
            ->line('Return Date: ' . $this->booking->end_date . ' at ' . $this->booking->end_time)
            ->line('Total Amount Paid: ' . $this->booking->currency . ' ' . number_format($this->booking->grand_total, 2))
            ->action('View Your Booking', url(config('app.url') . '/green-motion-booking-details/' . $this->booking->id))
            ->line('We look forward to seeing you!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_ref' => $this->booking->greenmotion_booking_ref,
            'grand_total' => $this->booking->grand_total,
            'message' => 'Your GreenMotion booking has been confirmed.',
        ];
    }
}
