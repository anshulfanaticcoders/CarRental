<?php

namespace App\Notifications\Payment;

use App\Notifications\Concerns\SendsAdminNotificationOncePerDay;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerts an admin that an external supplier DEFINITIVELY rejected a paid
 * booking's reservation (all retries exhausted). The booking is held in
 * reservation_failed for manual review: rebook with the supplier or refund.
 */
class AdminReservationFailedNotification extends Notification
{
    use Queueable;
    use SendsAdminNotificationOncePerDay;

    protected $booking;

    protected string $reason;

    public function __construct($booking, string $reason = '')
    {
        $this->booking = $booking;
        $this->reason = $reason;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function dedupeKey(): string
    {
        return sha1('reservation-failed|'.$this->booking->getKey());
    }

    public function toMail(object $notifiable): MailMessage
    {
        $captured = in_array($this->booking->payment_status, ['partial', 'paid', 'refund_pending'], true)
            || (float) $this->booking->amount_paid > 0;

        $mail = (new MailMessage)
            ->subject('Action needed: supplier rejected Booking #'.$this->booking->booking_number)
            ->greeting('Hello Admin,')
            ->line('The supplier could not confirm a reservation after all retries.')
            ->line('**Booking Number:** '.$this->booking->booking_number)
            ->line('**Provider:** '.($this->booking->provider_source ?: 'unknown'))
            ->line('**Customer:** '.($this->booking->customer?->email ?? 'unknown'))
            ->when($this->reason !== '', fn ($message) => $message->line('**Supplier error:** '.$this->reason));

        if ($captured) {
            $mail->line('Payment was captured. Rebook manually or complete the flagged refund.')
                ->line('The customer has been told the booking is under review.');
        } else {
            $mail->line('No payment was captured; the Stripe card authorization was released.')
                ->line('Do not issue a refund for this booking.');
        }

        return $mail->action('View Bookings', url('/customer-bookings'));
    }

    public function toArray(object $notifiable): array
    {
        $captured = in_array($this->booking->payment_status, ['partial', 'paid', 'refund_pending'], true)
            || (float) $this->booking->amount_paid > 0;

        return [
            'title' => 'Supplier rejected booking #'.$this->booking->booking_number,
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'dedupe_key' => $this->dedupeKey(),
            'provider_source' => $this->booking->provider_source,
            'reason' => $this->reason,
            'role' => 'admin',
            'message' => $captured
                ? 'Supplier rejected booking #'.$this->booking->booking_number.'. Payment was captured; rebook or refund.'
                : 'Supplier rejected booking #'.$this->booking->booking_number.'. Card authorization released; no refund is due.',
        ];
    }
}
