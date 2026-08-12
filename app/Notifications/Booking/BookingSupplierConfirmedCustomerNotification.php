<?php

namespace App\Notifications\Booking;

use App\Notifications\Concerns\FormatsBookingAmounts;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Final confirmation for an external-provider booking: the supplier accepted
 * the reservation and issued a reference. Follows the payment-received email.
 */
class BookingSupplierConfirmedCustomerNotification extends Notification
{
    use FormatsBookingAmounts;
    use Queueable;

    protected $booking;

    protected $customer;

    protected $vehicle;

    public function __construct($booking, $customer, $vehicle = null)
    {
        $this->booking = $booking;
        $this->customer = $customer;
        $this->vehicle = $vehicle;
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

        return [
            'title' => 'Booking confirmed',
            'body' => "Booking #{$bookingNumber} is confirmed. Supplier reference: ".($this->booking->provider_booking_ref ?? ''),
            'data' => [
                'type' => 'booking_supplier_confirmed',
                'booking_id' => $this->booking->id ?? null,
                'booking_number' => $bookingNumber,
                'route' => '/(tabs)/bookings',
            ],
            'channelId' => 'bookings',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $firstName = $this->customer->first_name ?? 'there';

        return (new MailMessage)
            ->subject('Booking Confirmed - #'.$this->booking->booking_number)
            ->greeting('Hello '.$firstName.',')
            ->line('Great news — the supplier has confirmed your reservation.')
            ->line('**Booking Number:** '.$this->booking->booking_number)
            ->line('**Supplier Reference:** '.($this->booking->provider_booking_ref ?? 'N/A'))
            ->line('**Vehicle:** '.$this->getVehicleName())
            ->line('**Pickup:** '.($this->booking->pickup_location ?? 'N/A').' on '.($this->booking->pickup_date ?? 'N/A'))
            ->line('**Return:** '.($this->booking->return_location ?? 'N/A').' on '.($this->booking->return_date ?? 'N/A'))
            ->line('Please bring your driving licence and the payment card used for this booking to the pickup desk.')
            ->action('View Your Booking', url('/'.app()->getLocale().'/profile/bookings'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Booking confirmed #'.$this->booking->booking_number,
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'vehicle' => $this->getVehicleName(),
            'provider_booking_ref' => $this->booking->provider_booking_ref,
            'role' => 'customer',
            'message' => 'Booking #'.$this->booking->booking_number
                .' is confirmed. Supplier reference: '.($this->booking->provider_booking_ref ?? 'N/A').'.',
        ];
    }

    private function getVehicleName(): string
    {
        $brand = $this->vehicle?->brand ?? '';
        $model = $this->vehicle?->model ?? '';
        $name = trim($brand.' '.$model);

        return $name !== '' ? $name : ($this->booking->vehicle_name ?? 'Vehicle');
    }
}
