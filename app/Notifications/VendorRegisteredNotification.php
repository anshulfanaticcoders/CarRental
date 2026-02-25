<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorRegisteredNotification extends Notification
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
        return ['mail', 'database']; // Notify via email and store in database
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Vendor Registration')
            ->greeting('Hello Admin,')
            ->line('A new vendor has registered and is awaiting approval.')
            ->line('**Company Name:** ' . $this->vendorProfile->company_name)
            ->line('**Email:** ' . $this->user->email)
            ->line('**Company Phone:** ' . $this->vendorProfile->company_phone_number)
            ->line('**Status:** ' . ucfirst($this->vendorProfile->status))
            // ->action('Review Vendor', url('/admin/vendors/' . $this->user->id)) // Optional: Add a link
            ->line('Please review the vendor details and approve or reject the registration.');
    }

    /**
     * Get the array representation of the notification for the database.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Vendor Registration',
            'user_id' => $this->user->id,
            'company_name' => $this->vendorProfile->company_name,
            'email' => $this->user->email,
            'company_phone' => $this->vendorProfile->company_phone_number,
            'status' => $this->vendorProfile->status,
            'role' => 'admin',
            'message' => 'A new vendor has registered.',
        ];
    }
}