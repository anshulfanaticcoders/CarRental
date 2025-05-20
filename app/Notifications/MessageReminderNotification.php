<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MessageReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $this->message->loadMissing(['sender', 'booking.vehicle']); // Eager load sender and booking with vehicle

        $sender = $this->message->sender;
        $senderName = $sender ? ($sender->first_name . ' ' . $sender->last_name) : 'A user';
        $booking = $this->message->booking;

        $mailMessage = (new MailMessage)
            ->subject('Reminder: You have an unread message regarding your booking')
            ->greeting('Hello ' . $notifiable->first_name . ',')
            ->line('This is a reminder that you have an unread message from ' . $senderName . ' regarding the booking detailed below. The message was sent approximately ' . $this->message->created_at->diffForHumans() . '.')
            ->line('**Message Preview:**')
            ->line(substr($this->message->message, 0, 200) . (strlen($this->message->message) > 200 ? '...' : ''));

        if ($booking) {
            $mailMessage->line('---')
                        ->line('**Booking Details:**');
            if ($booking->vehicle) {
                $mailMessage->line('Vehicle: ' . $booking->vehicle->name); // Assuming vehicle has a name attribute
            }
            $mailMessage->line('Booking Reference: ' . ($booking->booking_number ?? 'N/A'));
            $mailMessage->line('Pickup Date: ' . ($booking->pickup_date ? \Carbon\Carbon::parse($booking->pickup_date)->format('F j, Y H:i') : 'N/A'));
            $mailMessage->line('Return Date: ' . ($booking->return_date ? \Carbon\Carbon::parse($booking->return_date)->format('F j, Y H:i') : 'N/A'));
            $mailMessage->line('Pickup Location: ' . ($booking->pickup_location ?? 'N/A'));
            // Add more booking details as needed
        }

        $mailMessage->action('View Message & Booking', url('/bookings/' . $this->message->booking_id . '/chat')) // Adjusted URL
                    ->line('Please check your messages at your earliest convenience.');

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification for the database.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // Ensure booking is loaded for the booking_reference
        $this->message->loadMissing('booking');

        return [
            'message_id' => $this->message->id,
            'booking_id' => $this->message->booking_id, // Corrected from chat_id
            'sender_id' => $this->message->sender_id,
            'message_preview' => substr($this->message->message, 0, 50) . (strlen($this->message->message) > 50 ? '...' : ''),
            'notification_type' => 'message_reminder',
            'related_booking_reference' => $this->message->booking->booking_reference ?? null,
        ];
    }
}
