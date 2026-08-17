<?php

namespace App\Notifications\Payment;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Refunds are performed manually in the Stripe dashboard by design. This
 * closes the loop: when Stripe reports the refund, the booking is updated and
 * the admin is reminded of the follow-through (cancel the supplier reservation
 * if one exists, close out the booking).
 */
class AdminChargeRefundedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected $booking,
        protected int $amountRefundedMinor,
        protected string $currency,
        protected bool $fullyRefunded,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format($this->amountRefundedMinor / 100, 2).' '.strtoupper($this->currency);

        return (new MailMessage)
            ->subject('Refund recorded for Booking #'.$this->booking->booking_number)
            ->greeting('Hello Admin,')
            ->line('Stripe reported a '.($this->fullyRefunded ? 'FULL' : 'PARTIAL')." refund of {$amount} for this booking.")
            ->line('**Booking Number:** '.$this->booking->booking_number)
            ->line('**Customer:** '.($this->booking->customer?->email ?? 'unknown'))
            ->when(! empty($this->booking->provider_booking_ref), fn ($mail) => $mail
                ->line('**Supplier reservation '.$this->booking->provider_booking_ref.' is still active.** Cancel it with the supplier or the car stays reserved and invoiced.'))
            ->line('The booking record has been updated automatically. If the booking should be closed, cancel it in the dashboard.')
            ->action('View Bookings', url('/customer-bookings'));
    }

    public function toArray(object $notifiable): array
    {
        $amount = number_format($this->amountRefundedMinor / 100, 2).' '.strtoupper($this->currency);

        return [
            'title' => 'Refund recorded for booking #'.$this->booking->booking_number,
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'amount_refunded_minor' => $this->amountRefundedMinor,
            'currency' => strtoupper($this->currency),
            'fully_refunded' => $this->fullyRefunded,
            'role' => 'admin',
            'message' => ($this->fullyRefunded ? 'Full' : 'Partial')." refund of {$amount} recorded for booking #".$this->booking->booking_number
                .(! empty($this->booking->provider_booking_ref)
                    ? '. Supplier reservation '.$this->booking->provider_booking_ref.' still needs cancelling.'
                    : '.'),
        ];
    }
}
