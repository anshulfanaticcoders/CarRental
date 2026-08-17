<?php

namespace App\Notifications\Payment;

use App\Notifications\Concerns\SendsAdminNotificationOncePerDay;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerts an admin that an external supplier reservation timed out with an unknown
 * outcome. The supplier may already hold a reservation, so the booking was NOT
 * auto-retried or auto-cancelled — a human must check the supplier portal before
 * retrying or refunding, to avoid a duplicate reservation.
 */
class AdminReservationManualCheckNotification extends Notification
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
        return sha1('reservation-manual-check|'.$this->booking->getKey());
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Action needed: reservation outcome unknown for Booking #'.$this->booking->booking_number)
            ->greeting('Hello Admin,')
            ->line('An external supplier reservation could not be confirmed because the supplier timed out.')
            ->line('The supplier may already hold a reservation for this booking, so it was NOT retried or cancelled automatically.')
            ->line('**Booking Number:** '.$this->booking->booking_number)
            ->line('**Provider:** '.($this->booking->provider_source ?: 'unknown'))
            ->when($this->reason !== '', fn ($mail) => $mail->line('**Details:** '.$this->reason))
            ->line('Please check the supplier portal: if a reservation exists, record its reference; otherwise retry or refund.')
            ->action('View Bookings', url('/customer-bookings'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Reservation needs manual check #'.$this->booking->booking_number,
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'dedupe_key' => $this->dedupeKey(),
            'provider_source' => $this->booking->provider_source,
            'reason' => $this->reason,
            'role' => 'admin',
            'message' => 'Supplier reservation for Booking #'.$this->booking->booking_number
                .' timed out with an unknown outcome. Check the supplier portal before retrying or refunding.',
        ];
    }
}
