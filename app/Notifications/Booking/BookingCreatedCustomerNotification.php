<?php

namespace App\Notifications\Booking;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCreatedCustomerNotification extends Notification
{
    use Queueable;

    protected $booking;
    protected $customer;
    protected $vehicle;

    public function __construct($booking, $customer, $vehicle)
    {
        $this->booking = $booking;
        $this->customer = $customer;
        $this->vehicle = $vehicle;
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
            ->subject('Your Booking Confirmation - #' . $this->booking->booking_number)
            ->greeting('Hello ' . $this->customer->first_name . ',') // Use $this->customer
            ->line('Thank you for your booking! It has been successfully submitted and is pending confirmation.')
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
            ->line('You will be notified once the booking is confirmed by the vendor.');
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
            'message' => 'Your booking has been submitted and is pending confirmation.',
        ];
    }
}