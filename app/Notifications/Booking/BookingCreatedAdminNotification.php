<?php

namespace App\Notifications\Booking;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Concerns\FormatsBookingAmounts;

class BookingCreatedAdminNotification extends Notification
{
    use Queueable;
    use FormatsBookingAmounts;

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
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amounts = $this->getAdminAmounts($this->booking);
        $vehicleName = $this->getVehicleName();
        $location = $this->getLocation();
        $address = $this->getAddress();
        // $addressParts = array_filter([
        //     $this->vehicle->city,
        //     $this->vehicle->state,
        //     $this->vehicle->country,
        // ]);
        // $formattedAddress = implode(', ', $addressParts);

        return (new MailMessage)
            ->subject('New Booking Created - #' . $this->booking->booking_number)
            ->greeting('Hello Admin,')
            ->line('A new booking has been created and requires your review.')
            ->line('**Booking Details:**')
            ->line('**Booking Number:** ' . $this->booking->booking_number)
            ->line('**Vehicle:** ' . $vehicleName)
            ->line('**Location:** ' . $location)
            ->line('**Address:** ' . $address)
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
            ->line('Please review the booking details.');
    }

    public function toArray(object $notifiable): array
    {
        $amounts = $this->getAdminAmounts($this->booking);
        $vehicleName = $this->getVehicleName();
        $location = $this->getLocation();
        $address = $this->getAddress();
        // $addressParts = array_filter([
        //     $this->vehicle->city,
        //     $this->vehicle->state,
        //     $this->vehicle->country,
        // ]);
        // $formattedAddress = implode(', ', $addressParts);

        return [
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'vehicle' => $vehicleName,
            'location' => $location,
            'address' => $address,
            'pickup_date' => $this->booking->pickup_date->format('Y-m-d'),
            'pickup_time' => $this->booking->pickup_time,
            'return_date' => $this->booking->return_date->format('Y-m-d'),
            'return_time' => $this->booking->return_time,
            'total_amount' => $amounts['total'],
            'amount_paid' => $amounts['paid'],
            'pending_amount' => $amounts['pending'],
            'customer_name' => $this->customer->first_name . ' ' . $this->customer->last_name,
            'customer_email' => $this->customer->email,
            'currency_symbol' => $this->getCurrencySymbol($amounts['currency']),
            'message' => 'A new booking has been created for review.',
        ];
    }

    private function getVehicleName(): string
    {
        $brand = $this->vehicle?->brand ?? '';
        $model = $this->vehicle?->model ?? '';
        $name = trim($brand . ' ' . $model);

        if ($name !== '') {
            return $name;
        }

        return $this->booking->vehicle_name ?? 'Vehicle';
    }

    private function getLocation(): string
    {
        $location = $this->vehicle?->location ?? null;
        if (!empty($location)) {
            return $location;
        }

        return $this->booking->pickup_location
            ?? $this->booking->return_location
            ?? 'N/A';
    }

    private function getAddress(): string
    {
        $parts = array_filter([
            $this->vehicle?->city ?? null,
            $this->vehicle?->state ?? null,
            $this->vehicle?->country ?? null,
        ]);

        if (!empty($parts)) {
            return implode(', ', $parts);
        }

        return $this->getLocation();
    }
}
