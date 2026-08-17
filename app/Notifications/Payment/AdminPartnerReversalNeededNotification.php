<?php

namespace App\Notifications\Payment;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A cancelled/refunded booking has partner money attached that the system
 * cannot un-pay automatically (a paid affiliate commission, or an Awin
 * transaction that must be declined in their dashboard). Human action needed.
 */
class AdminPartnerReversalNeededNotification extends Notification
{
    use Queueable;

    /** @param array<int, string> $actions */
    public function __construct(
        protected $booking,
        protected array $actions,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Action needed: partner commission on dead Booking #'.$this->booking->booking_number)
            ->greeting('Hello Admin,')
            ->line('This booking was cancelled or refunded, but partner commission attached to it needs manual follow-up:');

        foreach ($this->actions as $action) {
            $mail->line('- '.preg_replace('/^MANUAL:\s*/', '', $action));
        }

        return $mail
            ->line('Until this is done, we are paying commission on money we returned.')
            ->action('View Bookings', url('/customer-bookings'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Partner commission needs reversal — booking #'.$this->booking->booking_number,
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'actions' => $this->actions,
            'role' => 'admin',
            'message' => 'Booking #'.$this->booking->booking_number.' died after partner commission fired: '
                .implode(' ', array_map(fn ($a) => preg_replace('/^MANUAL:\s*/', '', $a), $this->actions)),
        ];
    }
}
