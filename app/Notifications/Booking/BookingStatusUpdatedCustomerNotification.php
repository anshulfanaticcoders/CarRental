<?php

namespace App\Notifications\Booking;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingStatusUpdatedCustomerNotification extends Notification
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
        $channels = ['database', 'mail'];
        if (! empty($notifiable->expo_push_token)) {
            $channels[] = \App\Notifications\Channels\ExpoPushChannel::class;
        }
        return $channels;
    }

    public function toExpoPush(object $notifiable): array
    {
        $bookingNumber = $this->booking->booking_number ?? '';
        $status = ucfirst((string) ($this->booking->booking_status ?? 'updated'));
        $vehicleName = trim(($this->vehicle->brand ?? '').' '.($this->vehicle->model ?? '')) ?: 'your booking';
        return [
            'title' => 'Booking status updated',
            'body' => "{$vehicleName} #{$bookingNumber} is now {$status}.",
            'data' => [
                'type' => 'booking_status_updated',
                'booking_id' => $this->booking->id ?? null,
                'booking_number' => $bookingNumber,
                'status' => $this->booking->booking_status ?? null,
                'route' => '/(tabs)/bookings',
            ],
            'channelId' => 'bookings',
        ];
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
            ->subject('Booking Status Update - #'.$this->booking->booking_number)
            ->greeting('Hello '.$this->customer->first_name.',')
            ->line('The status of your booking has been updated.')
            ->line('**Booking Details:**')
            ->line('**Booking Number:** '.$this->booking->booking_number)
            ->line('**Vehicle:** '.$this->vehicle->brand.' '.$this->vehicle->model)
            ->line('**Location:** '.$this->vehicle->location)
            ->line('**Address:** '.$this->vehicle->city.', '.$this->vehicle->state.', '.$this->vehicle->country)
            ->line('**Pickup Date:** '.$this->booking->pickup_date->format('Y-m-d'))
            ->line('**Pickup Time:** '.$this->booking->pickup_time)
            ->line('**Return Date:** '.$this->booking->return_date->format('Y-m-d'))
            ->line('**Return Time:** '.$this->booking->return_time)
            ->line('**New Status:** '.ucfirst($this->booking->booking_status))
            ->line('**Vendor Details:**')
            ->line('**Name:** '.$this->vendor->first_name.' '.$this->vendor->last_name)
            ->line('**Email:** '.$this->vendor->email)
            ->action('View Your Booking', url('/'.app()->getLocale().'/booking/'.$this->booking->id))
            ->line('Please contact the vendor if you have any questions.');
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
            'title' => 'Booking Status Updated #'.$this->booking->booking_number,
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'vehicle' => $this->vehicle->brand.' '.$this->vehicle->model,
            'location' => $this->vehicle->location,
            'address' => $this->vehicle->city.', '.$this->vehicle->state.', '.$this->vehicle->country,
            'pickup_date' => $this->booking->pickup_date->format('Y-m-d'),
            'pickup_time' => $this->booking->pickup_time,
            'return_date' => $this->booking->return_date->format('Y-m-d'),
            'return_time' => $this->booking->return_time,
            'status' => $this->booking->booking_status,
            'vendor_name' => $this->vendor->first_name.' '.$this->vendor->last_name,
            'vendor_email' => $this->vendor->email,
            'role' => 'customer',
            'message' => 'Your booking status has been updated to '.$this->booking->booking_status.'.',
        ];
    }
}
