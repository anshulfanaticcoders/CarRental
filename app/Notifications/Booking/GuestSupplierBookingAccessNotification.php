<?php

namespace App\Notifications\Booking;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GuestSupplierBookingAccessNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly mixed $booking,
        private readonly mixed $customer,
        private readonly string $temporaryPassword,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Vrooem account access - Booking #'.$this->booking->booking_number)
            ->greeting('Hello '.($this->customer->first_name ?? 'there').',')
            ->line('We created an account so you can follow your booking while the rental supplier confirms it.')
            ->line('Your card may show a temporary authorization, but it has not been captured yet.')
            ->line('**Booking Number:** '.$this->booking->booking_number)
            ->line('**Login Email:** '.($this->customer->email ?? ''))
            ->line('**Temporary Password:** '.$this->temporaryPassword)
            ->line('This message provides account access only. Your reservation is not confirmed until you receive the final confirmation with the supplier reference.')
            ->action('View Booking Status', url('/'.app()->getLocale().'/profile/bookings'))
            ->line('Please change your password after signing in.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Account access ready',
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'role' => 'customer',
            'message' => 'Your account is ready. Supplier confirmation for booking #'.$this->booking->booking_number.' is still in progress.',
        ];
    }
}
