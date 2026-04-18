<?php

namespace App\Notifications\ApiBooking;

use App\Models\ApiBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApiBookingCancelledDriverNotification extends Notification
{
    use Queueable;

    protected $booking;

    public function __construct(ApiBooking $booking)
    {
        $this->booking = $booking;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reason = $this->booking->cancellation_reason ?: 'No reason provided';

        return (new MailMessage)
            ->subject('Booking Cancelled - #'.$this->booking->booking_number)
            ->greeting('Hello '.$this->booking->driver_first_name.',')
            ->line('Your booking has been cancelled.')
            ->line('**Booking Details:**')
            ->line('**Booking Number:** '.$this->booking->booking_number)
            ->line('**Vehicle:** '.$this->booking->vehicle_name)
            ->line('**Reason:** '.$reason)
            ->line('If you have any questions about this cancellation, please contact support.');
    }
}
