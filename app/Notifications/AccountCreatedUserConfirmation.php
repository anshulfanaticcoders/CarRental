<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountCreatedUserConfirmation extends Notification
{
    use Queueable;

    protected $user;

    /**
     * Create a new notification instance.
     */
    public function __construct($user)
    {
        $this->user = $user;
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
        $appName = config('app.name');

        return (new MailMessage)
            ->subject('Welcome to ' . $appName . '!')
            ->greeting('Hello ' . $this->user->first_name . ',')
            ->line('Thank you for creating your account on ' . $appName . '!')
            ->line('Your account has been successfully created.')
            ->line('**Your Account Details:**')
            ->line('**Email:** ' . $this->user->email)
            ->line('**Phone:** ' . ($this->user->phone_code ?? '') . ' ' . ($this->user->phone ?? 'Not provided'))
            ->line('You can now browse vehicles, make bookings, and manage your reservations.')
            ->action('Start Exploring', url('/' . app()->getLocale()))
            ->line('We are excited to have you on board!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Welcome!',
            'user_id' => $this->user->id,
            'name' => $this->user->first_name . ' ' . $this->user->last_name,
            'email' => $this->user->email,
            'role' => 'customer',
            'message' => 'Your account has been created successfully.',
        ];
    }
}
