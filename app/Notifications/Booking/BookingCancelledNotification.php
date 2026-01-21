<?php

namespace App\Notifications\Booking;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Concerns\FormatsBookingAmounts;

class BookingCancelledNotification extends Notification
{
    use Queueable;
    use FormatsBookingAmounts;

    protected $booking;
    protected $customer;
    protected $vehicle;
    protected $recipientType; // 'admin' or 'vendor'

    public function __construct($booking, $customer, $vehicle, $recipientType = 'vendor')
    {
        $this->booking = $booking;
        $this->customer = $customer;
        $this->vehicle = $vehicle;
        $this->recipientType = $recipientType;
    }

    public function via(object $notifiable): array
    {
        return $this->recipientType === 'admin' ? ['mail', 'database'] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // $addressParts = array_filter([
        //     $this->vehicle->city,
        //     $this->vehicle->state,
        //     $this->vehicle->country,
        // ]);
        // $formattedAddress = implode(', ', $addressParts);

        $amounts = $this->recipientType === 'admin'
            ? $this->getAdminAmounts($this->booking)
            : $this->getVendorAmounts($this->booking);

        $mailMessage = (new MailMessage)
            ->subject('Booking Cancelled - #' . $this->booking->booking_number)
            ->line('**Booking Details:**')
            ->line('**Booking Number:** ' . $this->booking->booking_number)
            ->line('**Vehicle:** ' . $this->vehicle->brand . ' ' . $this->vehicle->model)
            ->line('**Location:** ' . $this->vehicle->location)
            ->line('**Address:** ' . $this->vehicle->city . ', ' . $this->vehicle->state . ', ' .$this->vehicle->country)
            ->line('**Pickup Date:** ' . $this->booking->pickup_date->format('Y-m-d'))
            ->line('**Pickup Time:** ' . $this->booking->pickup_time)
            ->line('**Return Date:** ' . $this->booking->return_date->format('Y-m-d'))
            ->line('**Return Time:** ' . $this->booking->return_time)
            ->line('**Total Amount:** ' . $this->formatCurrencyAmount($amounts['total'], $amounts['currency']))
            ->line('**Cancellation Reason:** ' . $this->booking->cancellation_reason)
            ->line('**Customer Details:**')
            ->line('**Name:** ' . $this->customer->first_name . ' ' . $this->customer->last_name)
            ->line('**Email:** ' . $this->customer->email)
            ->line('**Phone:** ' . ($this->customer->phone ?: 'Not provided'));

        if ($this->recipientType === 'admin') {
            $mailMessage
                ->greeting('Hello Admin,')
                ->line('A booking has been cancelled by a customer.')
                ->action('View Booking', url('/admin/bookings/' . $this->booking->id))
                ->line('Please review the cancelled booking details.');
        } elseif ($this->recipientType === 'vendor') {
            $mailMessage
                ->greeting('Hello ' . $notifiable->first_name . ',')
                ->line('A booking for your vehicle has been cancelled by the customer.')
                ->action('View Bookings', url('/bookings'))
                ->line('Please review the cancellation details.');
        } else { // company
            $mailMessage
                ->greeting('Hello,')
                ->line('A booking for a vehicle managed by your company has been cancelled by the customer.')
                ->line('Please review the cancellation details.');
        }


        return $mailMessage;
    }

    public function toArray(object $notifiable): array
    {
        $amounts = $this->recipientType === 'admin'
            ? $this->getAdminAmounts($this->booking)
            : $this->getVendorAmounts($this->booking);
        // $addressParts = array_filter([
        //     $this->vehicle->city,
        //     $this->vehicle->state,
        //     $this->vehicle->country,
        // ]);
        // $formattedAddress = implode(', ', $addressParts);

        return [
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'vehicle' => $this->vehicle->brand . ' ' . $this->vehicle->model,
            'location' => $this->vehicle->location,
            'address' => $this->vehicle->city . ', ' . $this->vehicle->state . ', ' .$this->vehicle->country,
            'pickup_date' => $this->booking->pickup_date->format('Y-m-d'),
            'pickup_time' => $this->booking->pickup_time,
            'return_date' => $this->booking->return_date->format('Y-m-d'),
            'return_time' => $this->booking->return_time,
            'total_amount' => $amounts['total'],
            'cancellation_reason' => $this->booking->cancellation_reason,
            'customer_name' => $this->customer->first_name . ' ' . $this->customer->last_name,
            'customer_email' => $this->customer->email,
            'currency_symbol' => $this->getCurrencySymbol($amounts['currency']),
            'message' => 'Booking #' . $this->booking->booking_number . ' has been cancelled.',
        ];
    }
}
