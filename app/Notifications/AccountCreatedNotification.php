<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountCreatedNotification extends Notification
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
        return ['mail', 'database']; // Notify via email and store in database
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Account Created')
            ->greeting('Hello Admin,')
            ->line('A new user account has been created.')
            ->line('**Name:** ' . $this->user->first_name . ' ' . $this->user->last_name)
            ->line('**Email:** ' . $this->user->email)
            ->line('**Phone:** ' . $this->user->phone_code . ' ' . $this->user->phone)
            ->line('Please review the account details if necessary.')
            // ->action('View User', url('/admin/users/' . $this->user->id)) // Optional: Add a link
            ->line('Thank you!');
    }

    /**
     * Get the array representation of the notification for the database.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'name' => $this->user->first_name . ' ' . $this->user->last_name,
            'email' => $this->user->email,
            'phone' => $this->user->phone_code . ' ' . $this->user->phone,
            'message' => 'A new account has been created.',
        ];
    }
}