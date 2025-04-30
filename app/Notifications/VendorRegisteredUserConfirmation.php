<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorRegisteredUserConfirmation extends Notification
{
    use Queueable;

    protected $vendorProfile;
    protected $user;

    /**
     * Create a new notification instance.
     */
    public function __construct($vendorProfile, $user)
    {
        $this->vendorProfile = $vendorProfile;
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail']; // Notify via email only
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Vendor Registration Submitted')
            ->greeting('Hello ' . $this->user->first_name . ',')
            ->line('Thank you for registering as a vendor!')
            ->line('Your vendor registration has been submitted and is pending approval.')
            ->line('**Company Name:** ' . $this->vendorProfile->company_name)
            ->line('**Email:** ' . $this->user->email)
            ->line('**Company Phone:** ' . $this->vendorProfile->company_phone_number)
            ->line('We will notify you once your registration is reviewed.')
            ->action('View Status', url('/vendor/status'))
            ->line('Thank you for joining our platform!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'company_name' => $this->vendorProfile->company_name,
            'email' => $this->user->email,
            'company_phone' => $this->vendorProfile->company_phone_number,
            'message' => 'Your vendor registration has been submitted.',
        ];
    }
}