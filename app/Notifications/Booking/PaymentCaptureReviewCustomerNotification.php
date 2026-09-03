<?php

namespace App\Notifications\Booking;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentCaptureReviewCustomerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly mixed $booking) {}

    public function via(object $notifiable): array
    {
        $channels = ['database', 'mail'];
        if (! empty($notifiable->expo_push_token)) {
            $channels[] = \App\Notifications\Channels\ExpoPushChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $firstName = $this->booking->customer?->first_name ?? 'there';

        return (new MailMessage)
            ->subject('Your vehicle is reserved - payment review #'.$this->booking->booking_number)
            ->greeting('Hello '.$firstName.',')
            ->line('The rental supplier confirmed your vehicle reservation with reference **'.$this->booking->provider_booking_ref.'**.')
            ->line('Your payment status is under review because Stripe did not return a conclusive final capture result.')
            ->line('We will not create another supplier reservation. Our team has been alerted and will update you as soon as the payment is resolved.')
            ->action('View Your Booking', url('/'.app()->getLocale().'/profile/bookings'));
    }

    public function toExpoPush(object $notifiable): array
    {
        return [
            'title' => 'Vehicle reserved — payment review',
            'body' => 'Supplier reference '.$this->booking->provider_booking_ref.' is saved. Your payment status is under review.',
            'data' => [
                'type' => 'payment_capture_review',
                'booking_id' => $this->booking->id,
                'booking_number' => $this->booking->booking_number,
                'route' => '/(tabs)/bookings',
            ],
            'channelId' => 'bookings',
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Vehicle reserved — payment review',
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'supplier_reference' => $this->booking->provider_booking_ref,
            'role' => 'customer',
            'message' => 'The supplier reservation is confirmed. Your payment status is under review with support.',
        ];
    }
}
