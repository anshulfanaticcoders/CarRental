<?php

namespace App\Notifications\Booking;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Concerns\FormatsBookingAmounts;

class BookingCreatedVendorNotification extends Notification
{
    use Queueable;
    use FormatsBookingAmounts;

    protected $booking;
    protected $customer;
    protected $vehicle;
    protected $vendor; // Add vendor property

    public function __construct($booking, $customer, $vehicle, $vendor)
    {
        $this->booking = $booking;
        $this->customer = $customer;
        $this->vehicle = $vehicle;
        $this->vendor = $vendor; // Initialize vendor
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database']; // Notify via email and save to database
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amounts = $this->getVendorAmounts($this->booking);
        // $addressParts = array_filter([
        //     $this->vehicle->city,
        //     $this->vehicle->state,
        //     $this->vehicle->country,
        // ]);
        // $formattedAddress = implode(', ', $addressParts);

        return (new MailMessage)
            ->subject('New Booking for Your Vehicle - #' . $this->booking->booking_number)
            ->greeting('Hello ' . $this->vendor->first_name . ',') // Use $this->vendor
            ->line('A new booking has been made for your vehicle.')
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
            ->line('**Amount Paid:** ' . $this->formatCurrencyAmount($amounts['paid'], $amounts['currency']))
            ->line('**Pending Amount:** ' . $this->formatCurrencyAmount($amounts['pending'], $amounts['currency']))
            ->line('**Customer Details:**')
            ->line('**Name:** ' . $this->customer->first_name . ' ' . $this->customer->last_name)
            ->line('**Email:** ' . $this->customer->email)
            ->line('**Phone:** ' . ($this->customer->phone ?: 'Not provided'))
            ->line('Please review the booking and update its status as needed.');
    }

    public function toArray(object $notifiable): array
    {
        $amounts = $this->getVendorAmounts($this->booking);
        // $addressParts = array_filter([
        //     $this->vehicle->city,
        //     $this->vehicle->state,
        //     $this->vehicle->country,
        // ]);
        // $formattedAddress = implode(', ', $addressParts);

        return [
            'title' => 'New Booking #' . $this->booking->booking_number,
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
            'amount_paid' => $amounts['paid'],
            'pending_amount' => $amounts['pending'],
            'customer_name' => $this->customer->first_name . ' ' . $this->customer->last_name,
            'currency_symbol' => $this->getCurrencySymbol($amounts['currency']),
            'role' => 'vendor',
            'message' => 'A new booking has been made for your vehicle.',
        ];
    }
}
