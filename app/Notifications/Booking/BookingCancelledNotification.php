<?php

namespace App\Notifications\Booking;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Concerns\FormatsBookingAmounts;
use Illuminate\Support\Carbon;

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

        $vehicleName = $this->getVehicleName();
        $location = $this->getLocation();
        $address = $this->getAddress();
        $pickupDate = $this->formatDate($this->booking->pickup_date ?? null);
        $returnDate = $this->formatDate($this->booking->return_date ?? null);
        $pickupTime = $this->booking->pickup_time ?? 'N/A';
        $returnTime = $this->booking->return_time ?? 'N/A';

        $mailMessage = (new MailMessage)
            ->subject('Booking Cancelled - #' . $this->booking->booking_number)
            ->line('**Booking Details:**')
            ->line('**Booking Number:** ' . $this->booking->booking_number)
            ->line('**Vehicle:** ' . $vehicleName)
            ->line('**Location:** ' . $location)
            ->line('**Address:** ' . $address)
            ->line('**Pickup Date:** ' . $pickupDate)
            ->line('**Pickup Time:** ' . $pickupTime)
            ->line('**Return Date:** ' . $returnDate)
            ->line('**Return Time:** ' . $returnTime)
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
        $vehicleName = $this->getVehicleName();
        $location = $this->getLocation();
        $address = $this->getAddress();
        $pickupDate = $this->formatDate($this->booking->pickup_date ?? null);
        $returnDate = $this->formatDate($this->booking->return_date ?? null);
        $pickupTime = $this->booking->pickup_time ?? 'N/A';
        $returnTime = $this->booking->return_time ?? 'N/A';

        return [
            'title' => 'Booking Cancelled #' . $this->booking->booking_number,
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'vehicle' => $vehicleName,
            'location' => $location,
            'address' => $address,
            'pickup_date' => $pickupDate,
            'pickup_time' => $pickupTime,
            'return_date' => $returnDate,
            'return_time' => $returnTime,
            'total_amount' => $amounts['total'],
            'cancellation_reason' => $this->booking->cancellation_reason,
            'customer_name' => $this->customer->first_name . ' ' . $this->customer->last_name,
            'customer_email' => $this->customer->email,
            'currency_symbol' => $this->getCurrencySymbol($amounts['currency']),
            'role' => $this->recipientType,
            'message' => 'Booking #' . $this->booking->booking_number . ' has been cancelled.',
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

    private function formatDate($value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        if (!empty($value)) {
            return (string) $value;
        }

        return 'N/A';
    }
}
