<?php

namespace App\Notifications\Booking;

use App\Notifications\Concerns\FormatsBookingAmounts;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * First email for an external-provider booking: payment captured, supplier
 * confirmation still in progress. Deliberately does NOT claim the booking is
 * confirmed — a follow-up (supplier-confirmed or reservation-failed) states
 * the final outcome. Includes guest credentials when an account was created.
 */
class BookingPaymentReceivedCustomerNotification extends Notification
{
    use FormatsBookingAmounts;
    use Queueable;

    protected $booking;

    protected $customer;

    protected $vehicle;

    protected ?string $tempPassword;

    public function __construct($booking, $customer, $vehicle = null, ?string $tempPassword = null)
    {
        $this->booking = $booking;
        $this->customer = $customer;
        $this->vehicle = $vehicle;
        $this->tempPassword = $tempPassword;
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
            'title' => 'Payment received',
            'body' => "Payment received for booking #{$bookingNumber}. We are confirming your reservation with the supplier.",
            'data' => [
                'type' => 'booking_payment_received',
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

        $mail = (new MailMessage)
            ->subject('Payment received - Booking #'.$this->booking->booking_number)
            ->greeting('Hello '.$firstName.',')
            ->line('Thank you — we have received your payment and are now confirming your reservation with the rental supplier.')
            ->line('**Booking Number:** '.$this->booking->booking_number)
            ->line('**Vehicle:** '.$this->getVehicleName())
            ->line('**Pickup:** '.($this->booking->pickup_location ?? 'N/A').' on '.($this->booking->pickup_date ?? 'N/A'))
            ->line('**Return:** '.($this->booking->return_location ?? 'N/A').' on '.($this->booking->return_date ?? 'N/A'))
            ->line('**Amount Paid:** '.$this->formatCurrencyAmount($amounts['paid'] ?? $amounts['total'], $amounts['currency']))
            ->line('You will receive a confirmation email as soon as the supplier issues your reservation reference. If anything cannot be confirmed, we will contact you right away — your payment is safe either way.');

        if ($this->tempPassword) {
            $mail->line('An account was created for you so you can track this booking:')
                ->line('**Email:** '.($this->customer->email ?? ''))
                ->line('**Temporary Password:** '.$this->tempPassword)
                ->line('Please log in and change your password.');
        }

        return $mail->action('View Your Booking', url('/'.app()->getLocale().'/profile/bookings'));
    }

    public function toArray(object $notifiable): array
    {
        $amounts = $this->getCustomerAmounts($this->booking);

        return [
            'title' => 'Payment received #'.$this->booking->booking_number,
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'vehicle' => $this->getVehicleName(),
            'total_amount' => $amounts['total'],
            'currency_symbol' => $this->getCurrencySymbol($amounts['currency']),
            'role' => 'customer',
            'message' => 'Payment received for booking #'.$this->booking->booking_number
                .'. We are confirming your reservation with the supplier and will email you the confirmation.',
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
