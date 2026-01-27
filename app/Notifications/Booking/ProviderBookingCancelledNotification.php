<?php

namespace App\Notifications\Booking;

use App\Notifications\Concerns\FormatsBookingAmounts;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProviderBookingCancelledNotification extends Notification
{
    use Queueable;
    use FormatsBookingAmounts;

    protected $booking;
    protected $customer;

    public function __construct($booking, $customer = null)
    {
        $this->booking = $booking;
        $this->customer = $customer;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amounts = $this->getAdminAmounts($this->booking);
        $providerSource = ucwords(str_replace('_', ' ', (string) ($this->booking->provider_source ?? 'provider')));
        $providerBookingRef = $this->booking->provider_booking_ref ?: 'N/A';
        $pickupDate = $this->booking->pickup_date ? $this->booking->pickup_date->format('Y-m-d') : 'N/A';
        $returnDate = $this->booking->return_date ? $this->booking->return_date->format('Y-m-d') : 'N/A';

        $mailMessage = (new MailMessage)
            ->subject('Provider Booking Cancelled - #' . $this->booking->booking_number)
            ->greeting('Hello Admin,')
            ->line('A provider booking has been cancelled by a customer.')
            ->line('**Booking Number:** ' . $this->booking->booking_number)
            ->line('**Provider:** ' . $providerSource)
            ->line('**Provider Reference:** ' . $providerBookingRef)
            ->line('**Pickup Location:** ' . ($this->booking->pickup_location ?? 'N/A'))
            ->line('**Pickup Date:** ' . $pickupDate)
            ->line('**Pickup Time:** ' . ($this->booking->pickup_time ?? 'N/A'))
            ->line('**Return Location:** ' . ($this->booking->return_location ?? 'N/A'))
            ->line('**Return Date:** ' . $returnDate)
            ->line('**Return Time:** ' . ($this->booking->return_time ?? 'N/A'))
            ->line('**Total Amount:** ' . $this->formatCurrencyAmount($amounts['total'], $amounts['currency']))
            ->line('**Cancellation Reason:** ' . ($this->booking->cancellation_reason ?? 'N/A'));

        if ($this->customer) {
            $mailMessage
                ->line('**Customer Details:**')
                ->line('**Name:** ' . ($this->customer->first_name ?? '') . ' ' . ($this->customer->last_name ?? ''))
                ->line('**Email:** ' . ($this->customer->email ?? 'N/A'))
                ->line('**Phone:** ' . ($this->customer->phone ?: 'Not provided'));
        }

        return $mailMessage;
    }

    public function toArray(object $notifiable): array
    {
        $amounts = $this->getAdminAmounts($this->booking);

        return [
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'provider_source' => $this->booking->provider_source,
            'provider_booking_ref' => $this->booking->provider_booking_ref,
            'pickup_date' => $this->booking->pickup_date,
            'pickup_time' => $this->booking->pickup_time,
            'return_date' => $this->booking->return_date,
            'return_time' => $this->booking->return_time,
            'pickup_location' => $this->booking->pickup_location,
            'return_location' => $this->booking->return_location,
            'total_amount' => $amounts['total'],
            'currency' => $amounts['currency'],
            'cancellation_reason' => $this->booking->cancellation_reason,
            'message' => 'Provider booking #' . $this->booking->booking_number . ' has been cancelled.',
        ];
    }
}
