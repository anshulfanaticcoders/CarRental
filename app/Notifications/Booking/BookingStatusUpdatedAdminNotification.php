<?php

namespace App\Notifications\Booking;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingStatusUpdatedAdminNotification extends Notification
{
    use Queueable;

    protected $booking;
    protected $customer;
    protected $vehicle;
    protected $vendor;

    public function __construct($booking, $customer, $vehicle, $vendor)
    {
        $this->booking = $booking;
        $this->customer = $customer;
        $this->vehicle = $vehicle;
        $this->vendor = $vendor;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
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
            ->subject('Booking Status Updated - #' . $this->booking->booking_number)
            ->greeting('Hello Admin,')
            ->line('The status of a booking has been updated by a vendor.')
            ->line('**Booking Details:**')
            ->line('**Booking Number:** ' . $this->booking->booking_number)
            ->line('**Vehicle:** ' . $this->vehicle->brand . ' ' . $this->vehicle->model)
            ->line('**Location:** ' . $this->vehicle->location)
            ->line('**Address:** ' . $this->vehicle->city . ', ' . $this->vehicle->state . ', ' .$this->vehicle->country)
            ->line('**Pickup Date:** ' . $this->booking->pickup_date->format('Y-m-d'))
            ->line('**Pickup Time:** ' . $this->booking->pickup_time)
            ->line('**Return Date:** ' . $this->booking->return_date->format('Y-m-d'))
            ->line('**Return Time:** ' . $this->booking->return_time)
            ->line('**New Status:** ' . ucfirst($this->booking->booking_status))
            ->line('**Vendor Details:**')
            ->line('**Name:** ' . $this->vendor->first_name . ' ' . $this->vendor->last_name)
            ->line('**Email:** ' . $this->vendor->email)
            ->line('**Customer Details:**')
            ->line('**Name:** ' . $this->customer->first_name . ' ' . $this->customer->last_name)
            ->line('**Email:** ' . $this->customer->email)
            ->line('Please review the updated booking details.');
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
            'status' => $this->booking->booking_status,
            'vendor_name' => $this->vendor->first_name . ' ' . $this->vendor->last_name,
            'vendor_email' => $this->vendor->email,
            'customer_name' => $this->customer->first_name . ' ' . $this->customer->last_name,
            'customer_email' => $this->customer->email,
            'message' => 'Booking status updated to ' . $this->booking->booking_status . '.',
        ];
    }
}