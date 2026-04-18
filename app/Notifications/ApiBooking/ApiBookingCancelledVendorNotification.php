<?php

namespace App\Notifications\ApiBooking;

use App\Models\ApiBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApiBookingCancelledVendorNotification extends Notification
{
    use Queueable;

    protected $booking;

    public function __construct(ApiBooking $booking)
    {
        $this->booking = $booking;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reason = $this->booking->cancellation_reason ?: 'No reason provided';

        return (new MailMessage)
            ->subject('External Booking Cancelled - #'.$this->booking->booking_number)
            ->greeting('Hello,')
            ->line('An external booking for one of your vehicles has been cancelled.')
            ->line('**Booking Details:**')
            ->line('**Booking Number:** '.$this->booking->booking_number)
            ->line('**Vehicle:** '.$this->booking->vehicle_name)
            ->line('**Driver:** '.$this->booking->driver_first_name.' '.$this->booking->driver_last_name)
            ->line('**Reason:** '.$reason)
            ->action('View External Bookings', url('/'.app()->getLocale().'/external-bookings'))
            ->line('The vehicle is now available for the previously blocked dates.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'External Booking Cancelled #'.$this->booking->booking_number,
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'vehicle_name' => $this->booking->vehicle_name,
            'driver_name' => $this->booking->driver_first_name.' '.$this->booking->driver_last_name,
            'cancellation_reason' => $this->booking->cancellation_reason,
            'role' => 'vendor',
            'message' => 'External booking #'.$this->booking->booking_number.' has been cancelled.',
        ];
    }
}
