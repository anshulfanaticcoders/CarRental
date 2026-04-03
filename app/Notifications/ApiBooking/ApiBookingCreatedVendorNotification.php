<?php

namespace App\Notifications\ApiBooking;

use App\Models\ApiBooking;
use App\Models\ApiConsumer;
use App\Notifications\Concerns\FormatsBookingAmounts;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApiBookingCreatedVendorNotification extends Notification
{
    use Queueable;
    use FormatsBookingAmounts;

    protected $booking;
    protected $consumer;

    public function __construct(ApiBooking $booking, ApiConsumer $consumer)
    {
        $this->booking = $booking;
        $this->consumer = $consumer;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Booking Request - #' . $this->booking->booking_number . ' — Action Required')
            ->greeting('Hello,')
            ->line('You have received a new booking request through **' . $this->consumer->name . '** (Provider API). Please review and confirm.')
            ->line('**Booking Details:**')
            ->line('**Booking Number:** ' . $this->booking->booking_number)
            ->line('**Vehicle:** ' . $this->booking->vehicle_name)
            ->line('**Driver:** ' . $this->booking->driver_first_name . ' ' . $this->booking->driver_last_name)
            ->line('**Pickup Location:** ' . $this->booking->pickup_location)
            ->line('**Pickup Date:** ' . $this->booking->pickup_date->format('Y-m-d'))
            ->line('**Pickup Time:** ' . $this->booking->pickup_time)
            ->line('**Return Date:** ' . $this->booking->return_date->format('Y-m-d'))
            ->line('**Return Time:** ' . $this->booking->return_time)
            ->line('**Total Amount:** ' . $this->formatCurrencyAmount((float) $this->booking->total_amount, $this->booking->currency))
            ->action('Review & Confirm Booking', url('/' . app()->getLocale() . '/external-bookings/' . $this->booking->id))
            ->line('Please confirm this booking from your dashboard. The customer will be notified once confirmed.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Booking Request #' . $this->booking->booking_number . ' — Please Confirm',
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'vehicle_name' => $this->booking->vehicle_name,
            'driver_name' => $this->booking->driver_first_name . ' ' . $this->booking->driver_last_name,
            'source' => $this->consumer->name,
            'total_amount' => (float) $this->booking->total_amount,
            'currency_symbol' => $this->getCurrencySymbol($this->booking->currency),
            'role' => 'vendor',
            'message' => 'New external booking #' . $this->booking->booking_number . ' from ' . $this->consumer->name . '.',
        ];
    }
}
