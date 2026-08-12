<?php

namespace App\Notifications\Payment;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerts an admin that a captured payment needs a manual refund or
 * reconciliation. Vrooem never refunds automatically — this is the visibility
 * layer so no owed refund lives only in a log line.
 */
class AdminManualRefundRequiredNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected ?string $paymentIntentId,
        protected string $reason,
        protected array $context = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Action needed: manual refund/reconciliation required')
            ->greeting('Hello Admin,')
            ->line('A captured payment needs manual review — a booking could not be completed after the customer paid.')
            ->line('**Payment Intent:** '.($this->paymentIntentId ?: 'MISSING — check Stripe dashboard by session'))
            ->line('**Reason:** '.$this->reason);

        foreach ($this->context as $key => $value) {
            if (is_scalar($value) && (string) $value !== '') {
                $mail->line('**'.ucwords(str_replace('_', ' ', (string) $key)).':** '.$value);
            }
        }

        return $mail
            ->line('Verify the charge in Stripe and either complete the booking or refund the customer manually.')
            ->action('View Bookings', url('/admin/bookings'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Manual refund required'.($this->paymentIntentId ? ' ('.$this->paymentIntentId.')' : ''),
            'payment_intent_id' => $this->paymentIntentId,
            'reason' => $this->reason,
            'context' => $this->context,
            'role' => 'admin',
            'message' => 'A captured payment needs manual refund/reconciliation: '.$this->reason,
        ];
    }
}
