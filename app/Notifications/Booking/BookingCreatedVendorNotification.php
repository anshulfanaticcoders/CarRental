<?php

namespace App\Notifications\Booking;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCreatedVendorNotification extends Notification
{
    use Queueable;

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
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
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
            ->line('**Total Amount:** $' . number_format($this->booking->total_amount, 2))
            ->line('**Amount Paid:** $' . number_format($this->booking->amount_paid, 2))
            ->line('**Pending Amount:** $' . number_format($this->booking->pending_amount, 2))
            ->line('**Customer Details:**')
            ->line('**Name:** ' . $this->customer->first_name . ' ' . $this->customer->last_name)
            ->line('**Email:** ' . $this->customer->email)
            ->line('**Phone:** ' . ($this->customer->phone ?: 'Not provided'))
            ->line('Please review the booking and update its status as needed.');
    }

    public function toArray(object $notifiable): array
    {
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
            'total_amount' => $this->booking->total_amount,
            'amount_paid' => $this->booking->amount_paid,
            'pending_amount' => $this->booking->pending_amount,
            'customer_name' => $this->customer->first_name . ' ' . $this->customer->last_name,
            'message' => 'A new booking has been made for your vehicle.',
        ];
    }
}