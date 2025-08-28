<?php

namespace App\Notifications\Booking;

use App\Models\GreenMotionBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GreenMotionBookingCreatedAdminNotification extends Notification implements ShouldQueue
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
            ->subject('New GreenMotion Booking Created - Admin Notification')
            ->greeting('Hello Admin,')
            ->line('A new GreenMotion booking has been created.')
            ->line('Booking Reference: ' . $this->booking->greenmotion_booking_ref)
            ->line('Customer Name: ' . $customerName)
            ->line('Customer Email: ' . $customerDetails['email'])
            ->line('Vehicle ID: ' . $this->booking->vehicle_id)
            ->line('Grand Total: ' . $this->booking->currency . ' ' . number_format($this->booking->grand_total, 2))
            ->action('View Booking', url(config('app.url') . '/admin/green-motion-bookings/' . $this->booking->id))
            ->line('Thank you for using our application!');
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
            'customer_email' => $this->booking->customer_details['email'],
            'grand_total' => $this->booking->grand_total,
            'message' => 'New GreenMotion booking created.',
        ];
    }
}
