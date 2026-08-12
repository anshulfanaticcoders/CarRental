<?php

namespace App\Notifications\Booking;

use App\Notifications\Concerns\FormatsBookingAmounts;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a PAID customer that the supplier could not confirm their reservation.
 * The booking is under manual review — never silently cancelled. Payment is
 * held; support will contact the customer about rebooking or a refund.
 */
class ReservationFailedCustomerNotification extends Notification
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
            'title' => 'Booking under review',
            'body' => "We could not confirm booking #{$bookingNumber} with the supplier. Your payment is safe — our team is reviewing it and will contact you.",
            'data' => [
                'type' => 'reservation_failed',
                'booking_id' => $this->booking->id ?? null,
                'booking_number' => $bookingNumber,
                'route' => '/(tabs)/bookings',
            ],
            'channelId' => 'bookings',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amounts = $this->getCustomerAmounts($this->booking);
        $firstName = $this->customer->first_name ?? 'there';

        return (new MailMessage)
            ->subject('Your booking needs attention - #'.$this->booking->booking_number)
            ->greeting('Hello '.$firstName.',')
            ->line('We were unable to confirm your reservation with the rental supplier.')
            ->line('**Your payment is safe.** No further amount will be taken, and nothing is lost.')
            ->line('**Booking Number:** '.$this->booking->booking_number)
            ->line('**Vehicle:** '.$this->getVehicleName())
            ->line('**Pickup:** '.($this->booking->pickup_location ?? 'N/A'))
            ->line('**Amount Paid:** '.$this->formatCurrencyAmount($amounts['paid'] ?? $amounts['total'], $amounts['currency']))
            ->line('Our team is already reviewing this booking and will contact you shortly with the next steps — either completing your reservation with the supplier or arranging a full refund.')
            ->line('If you would like to reach us first, reply to this email or use the support options on your booking page.')
            ->action('View Your Booking', url('/'.app()->getLocale().'/profile/bookings'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Booking under review #'.$this->booking->booking_number,
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'vehicle' => $this->getVehicleName(),
            'role' => 'customer',
            'message' => 'We could not confirm booking #'.$this->booking->booking_number
                .' with the supplier. Your payment is safe — our team is reviewing it and will contact you with next steps.',
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
