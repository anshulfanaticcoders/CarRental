<?php

namespace App\Notifications\Booking;

use App\Notifications\Concerns\SendsAdminNotificationOncePerDay;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerts an admin that a booking was created from a paid Stripe session with
 * incomplete data and had to be filled in with a default. The money and the
 * booking are safe — but the stored value is a guess and must be corrected
 * before the rental starts.
 */
class AdminBookingNeedsCorrectionNotification extends Notification
{
    use Queueable;
    use SendsAdminNotificationOncePerDay;

    public function __construct(
        protected $booking,
        protected string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function dedupeKey(): string
    {
        return sha1('booking-needs-correction|'.$this->booking->getKey());
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Action needed: check booking details for Booking #'.$this->booking->booking_number)
            ->greeting('Hello Admin,')
            ->line('A booking was created from a paid Stripe session that was missing some details, so a default was stored instead.')
            ->line('**Booking Number:** '.$this->booking->booking_number)
            ->line('**Details:** '.$this->reason)
            ->line('Confirm the correct values with the customer and update the booking before pickup.')
            ->action('View Bookings', url('/customer-bookings'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Booking needs correction #'.$this->booking->booking_number,
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'dedupe_key' => $this->dedupeKey(),
            'reason' => $this->reason,
            'role' => 'admin',
            'message' => 'Booking #'.$this->booking->booking_number.' was created with defaulted details: '.$this->reason,
        ];
    }
}
