<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactUsUserConfirmation extends Notification
{
    use Queueable;

    protected $contactData;

    /**
     * Create a new notification instance.
     */
    public function __construct($contactData)
    {
        $this->contactData = $contactData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('We Received Your Message - '.config('app.name'))
            ->greeting('Hello '.$this->contactData['name'].',')
            ->line('Thank you for reaching out to '.config('app.name').'. We have received your message:')
            ->line('> '.$this->contactData['message'])
            ->line('Our team will review your message and get back to you as soon as possible.')
            ->action('Visit Our Website', url('/'.app()->getLocale()))
            ->line('Thank you for choosing '.config('app.name').'.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'name' => $this->contactData['name'],
            'email' => $this->contactData['email'],
            'message' => $this->contactData['message'],
        ];
    }
}
