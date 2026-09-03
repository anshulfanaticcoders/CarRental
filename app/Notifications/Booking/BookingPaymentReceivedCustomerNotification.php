<?php

namespace App\Notifications\Booking;

use App\Notifications\Concerns\FormatsBookingAmounts;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * First email for an external-provider booking. New supplier checkouts have a
 * card authorization only; legacy sessions may already be captured. The copy
 * must state the truthful payment state while supplier confirmation is pending.
 */
class BookingPaymentReceivedCustomerNotification extends Notification implements ShouldQueue
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
        $authorized = $this->isAuthorizationOnly();

        return [
            'title' => $authorized ? 'Card authorized' : 'Payment received',
            'body' => $authorized
                ? "Your card is authorized for booking #{$bookingNumber}, but has not been charged. We are confirming with the supplier."
                : "Payment received for booking #{$bookingNumber}. We are confirming your reservation with the supplier.",
            'data' => [
                'type' => $authorized ? 'booking_card_authorized' : 'booking_payment_received',
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
        $authorized = $this->isAuthorizationOnly();

        $mail = (new MailMessage)
            ->subject(($authorized ? 'Card authorized' : 'Payment received').' - Booking #'.$this->booking->booking_number)
            ->greeting('Hello '.$firstName.',')
            ->line($authorized
                ? 'Thank you — your card has been authorized, but no payment has been captured. We are now confirming your reservation with the rental supplier.'
                : 'Thank you — we have received your payment and are now confirming your reservation with the rental supplier.')
            ->line('**Booking Number:** '.$this->booking->booking_number)
            ->line('**Vehicle:** '.$this->getVehicleName())
            ->line('**Pickup:** '.($this->booking->pickup_location ?? 'N/A').' on '.($this->booking->pickup_date ?? 'N/A'))
            ->line('**Return:** '.($this->booking->return_location ?? 'N/A').' on '.($this->booking->return_date ?? 'N/A'))
            ->line('**'.($authorized ? 'Amount Authorized' : 'Amount Paid').':** '.$this->formatCurrencyAmount(
                $authorized ? $this->authorizedAmount() : ($amounts['paid'] ?? $amounts['total']),
                $amounts['currency']
            ))
            ->line($authorized
                ? 'We will capture this amount only after the supplier issues your reservation reference. If the supplier cannot confirm, the authorization will be released.'
                : 'You will receive a confirmation email as soon as the supplier issues your reservation reference. If anything cannot be confirmed, we will contact you right away — your payment is safe either way.');

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
        $authorized = $this->isAuthorizationOnly();

        return [
            'title' => ($authorized ? 'Card authorized #' : 'Payment received #').$this->booking->booking_number,
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'vehicle' => $this->getVehicleName(),
            'total_amount' => $amounts['total'],
            'currency_symbol' => $this->getCurrencySymbol($amounts['currency']),
            'role' => 'customer',
            'message' => $authorized
                ? 'Your card is authorized but has not been charged for booking #'.$this->booking->booking_number
                    .'. We are confirming your reservation with the supplier.'
                : 'Payment received for booking #'.$this->booking->booking_number
                    .'. We are confirming your reservation with the supplier and will email you the confirmation.',
        ];
    }

    private function isAuthorizationOnly(): bool
    {
        return ($this->booking->payment_status ?? null) === 'authorized';
    }

    private function authorizedAmount(): float
    {
        $payment = $this->booking->relationLoaded('payments')
            ? $this->booking->payments->firstWhere('payment_status', 'authorized')
            : $this->booking->payments()->where('payment_status', 'authorized')->first();

        return (float) ($payment?->amount ?? 0);
    }

    private function getVehicleName(): string
    {
        $brand = $this->vehicle?->brand ?? '';
        $model = $this->vehicle?->model ?? '';
        $name = trim($brand.' '.$model);

        return $name !== '' ? $name : ($this->booking->vehicle_name ?? 'Vehicle');
    }
}
