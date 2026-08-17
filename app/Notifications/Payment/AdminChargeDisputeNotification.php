<?php

namespace App\Notifications\Payment;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A chargeback has a response deadline and silently withdraws the funds if
 * ignored — it must never be discoverable only from the Stripe dashboard.
 */
class AdminChargeDisputeNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected $booking,
        protected string $reason,
        protected int $amountMinor,
        protected string $currency,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format($this->amountMinor / 100, 2).' '.strtoupper($this->currency);

        return (new MailMessage)
            ->subject('URGENT: payment dispute opened on Booking #'.$this->booking->booking_number)
            ->greeting('Hello Admin,')
            ->line("The customer's bank opened a dispute (chargeback) of {$amount} on this booking's payment.")
            ->line('**Booking Number:** '.$this->booking->booking_number)
            ->line('**Customer:** '.($this->booking->customer?->email ?? 'unknown'))
            ->line('**Dispute reason:** '.($this->reason ?: 'not given'))
            ->line('Respond with evidence in the Stripe dashboard before the deadline, or the funds are withdrawn automatically.')
            ->action('View Bookings', url('/customer-bookings'));
    }

    public function toArray(object $notifiable): array
    {
        $amount = number_format($this->amountMinor / 100, 2).' '.strtoupper($this->currency);

        return [
            'title' => 'Payment dispute on booking #'.$this->booking->booking_number,
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'dispute_reason' => $this->reason,
            'amount_minor' => $this->amountMinor,
            'currency' => strtoupper($this->currency),
            'role' => 'admin',
            'message' => "Chargeback of {$amount} opened on booking #".$this->booking->booking_number
                .' ('.($this->reason ?: 'no reason given').'). Respond in Stripe before the deadline.',
        ];
    }
}
