<?php

namespace App\Notifications\ApiBooking;

use App\Models\ApiBooking;
use App\Notifications\Concerns\FormatsBookingAmounts;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApiBookingCreatedDriverNotification extends Notification
{
    use Queueable;
    use FormatsBookingAmounts;

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
        return (new MailMessage)
            ->subject('Your Booking is Confirmed - #' . $this->booking->booking_number)
            ->greeting('Hello ' . $this->booking->driver_first_name . ',')
            ->line('Great news! Your booking has been confirmed by the vendor. Here are your reservation details.')
            ->line('**Booking Details:**')
            ->line('**Booking Number:** ' . $this->booking->booking_number)
            ->line('**Vehicle:** ' . $this->booking->vehicle_name)
            ->line('**Pickup Location:** ' . $this->booking->pickup_location)
            ->line('**Pickup Date:** ' . $this->booking->pickup_date->format('Y-m-d'))
            ->line('**Pickup Time:** ' . $this->booking->pickup_time)
            ->line('**Return Date:** ' . $this->booking->return_date->format('Y-m-d'))
            ->line('**Return Time:** ' . $this->booking->return_time)
            ->line('**Return Location:** ' . $this->booking->return_location)
            ->line('**Total Amount:** ' . $this->formatCurrencyAmount((float) $this->booking->total_amount, $this->booking->currency))
            ->line('Please arrive at the pickup location on time with your driving license and booking number.');
    }
}
