<?php

namespace App\Notifications\Payment;

use App\Notifications\Concerns\SendsAdminNotificationOncePerDay;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminAuthorizationReviewNotification extends Notification
{
    use Queueable;
    use SendsAdminNotificationOncePerDay;

    public function __construct(
        protected string $sessionId,
        protected string $reason,
        protected bool $released,
        protected ?int $bookingId = null,
        protected ?string $bookingNumber = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function dedupeKey(): string
    {
        return sha1('authorization-review|'.$this->sessionId.'|'.($this->released ? 'released' : 'open'));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Action needed: card authorization review')
            ->greeting('Hello Admin,')
            ->line($this->released
                ? 'A supplier checkout was stopped safely and its card authorization was released.'
                : 'A supplier checkout was stopped, but its card authorization could not be released automatically.')
            ->when($this->bookingNumber, fn (MailMessage $mail) => $mail->line('**Booking Number:** '.$this->bookingNumber))
            ->line('**Stripe Session:** '.$this->sessionId)
            ->line('**Details:** '.$this->reason)
            ->line($this->released
                ? 'No supplier reservation was created. Review the incident for checkout diagnostics.'
                : 'Check Stripe immediately and release or reconcile the authorization manually. Do not create a supplier reservation for this checkout.')
            ->action('View Bookings', url('/customer-bookings'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->released ? 'Card authorization released' : 'Card authorization needs manual release',
            'booking_id' => $this->bookingId,
            'booking_number' => $this->bookingNumber,
            'stripe_session_id' => $this->sessionId,
            'dedupe_key' => $this->dedupeKey(),
            'authorization_released' => $this->released,
            'reason' => $this->reason,
            'role' => 'admin',
            'message' => $this->released
                ? 'A stopped supplier checkout had its card authorization released.'
                : 'A stopped supplier checkout still has an authorization that needs immediate manual review.',
        ];
    }
}
