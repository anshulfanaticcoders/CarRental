<?php

namespace App\Notifications\Booking;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Concerns\FormatsBookingAmounts;
use Illuminate\Support\Carbon;

class BookingCancelledCustomerNotification extends Notification
{
    use Queueable;
    use FormatsBookingAmounts;

    protected $booking;
    protected $customer;
    protected $vehicle;
    protected $cancellationReason;

    public function __construct($booking, $customer, $vehicle, string $cancellationReason)
    {
        $this->booking = $booking;
        $this->customer = $customer;
        $this->vehicle = $vehicle;
        $this->cancellationReason = $cancellationReason;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amounts = $this->getCustomerAmounts($this->booking);
        $vehicleName = $this->getVehicleName();
        $pickupDate = $this->formatDate($this->booking->pickup_date ?? null);
        $returnDate = $this->formatDate($this->booking->return_date ?? null);

        return (new MailMessage)
            ->subject('Booking Cancelled - #' . $this->booking->booking_number)
            ->greeting('Hello ' . $this->customer->first_name . ',')
            ->line('Your booking has been cancelled by the platform administration.')
            ->line('**Booking Details:**')
            ->line('**Booking Number:** ' . $this->booking->booking_number)
            ->line('**Vehicle:** ' . $vehicleName)
            ->line('**Pickup:** ' . ($this->booking->pickup_location ?? 'N/A') . ' on ' . $pickupDate)
            ->line('**Return:** ' . ($this->booking->return_location ?? 'N/A') . ' on ' . $returnDate)
            ->line('**Total Amount:** ' . $this->formatCurrencyAmount($amounts['total'], $amounts['currency']))
            ->line('**Reason:** ' . $this->cancellationReason)
            ->line('If you have any questions about this cancellation, please contact our support team.')
            ->action('View Your Bookings', url('/bookings'));
    }

    public function toArray(object $notifiable): array
    {
        $amounts = $this->getCustomerAmounts($this->booking);

        return [
            'title' => 'Booking Cancelled #' . $this->booking->booking_number,
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'vehicle' => $this->getVehicleName(),
            'pickup_date' => $this->formatDate($this->booking->pickup_date ?? null),
            'return_date' => $this->formatDate($this->booking->return_date ?? null),
            'total_amount' => $amounts['total'],
            'cancellation_reason' => $this->cancellationReason,
            'currency_symbol' => $this->getCurrencySymbol($amounts['currency']),
            'role' => 'customer',
            'message' => 'Your booking #' . $this->booking->booking_number . ' has been cancelled by the platform.',
        ];
    }

    private function getVehicleName(): string
    {
        $brand = $this->vehicle?->brand ?? '';
        $model = $this->vehicle?->model ?? '';
        $name = trim($brand . ' ' . $model);

        return $name !== '' ? $name : ($this->booking->vehicle_name ?? 'Vehicle');
    }

    private function formatDate($value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        return !empty($value) ? (string) $value : 'N/A';
    }
}
