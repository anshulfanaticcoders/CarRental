<?php

namespace App\Notifications\Payment;

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

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Action needed: supplier rejected paid Booking #'.$this->booking->booking_number)
            ->greeting('Hello Admin,')
            ->line('The supplier could not confirm a reservation for a PAID booking after all retries.')
            ->line('The booking is NOT cancelled — it is waiting in the reservation-failed queue for your decision.')
            ->line('**Booking Number:** '.$this->booking->booking_number)
            ->line('**Provider:** '.($this->booking->provider_source ?: 'unknown'))
            ->line('**Customer:** '.($this->booking->customer?->email ?? 'unknown'))
            ->when($this->reason !== '', fn ($mail) => $mail->line('**Supplier error:** '.$this->reason))
            ->line('Next steps: rebook manually via the supplier portal and record the reference, or cancel and refund the customer manually. The customer has been told their booking is under review.')
            ->action('View Bookings', url('/customer-bookings'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Supplier rejected paid booking #'.$this->booking->booking_number,
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'provider_source' => $this->booking->provider_source,
            'reason' => $this->reason,
            'role' => 'admin',
            'message' => 'Supplier rejected the reservation for PAID booking #'.$this->booking->booking_number
                .'. Held for manual review — rebook with the supplier or refund the customer.',
        ];
    }
}
