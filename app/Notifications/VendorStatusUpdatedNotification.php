<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorStatusUpdatedNotification extends Notification
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
        return ['mail', 'database']; // Notify via email only
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $status = ucfirst($this->vendorProfile->status);
        $message = (new MailMessage)
            ->subject('Vendor Status Update')
            ->greeting('Hello ' . $this->user->first_name . ',')
            ->line('Your vendor registration status has been updated.')
            ->line('**Company Name:** ' . $this->vendorProfile->company_name)
            ->line('**Status:** ' . $status);

        if ($this->vendorProfile->status === 'approved') {
            $message->line('Congratulations! Your vendor account has been approved. You can now start offering your services.')
                    ->action('Get Started', url('/vendor-status'));
        } elseif ($this->vendorProfile->status === 'rejected') {
            $message->line('We regret to inform you that your vendor application has been rejected. Please contact support for more details.')
                    ->action('Contact Support', url('/contact-us')); // Adjust URL as needed
        } else {
            $message->line('Your application is still under review. We will notify you once a decision is made.');
        }

        $message->line('Thank you for choosing our platform!');

        return $message;
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
            'status' => $this->vendorProfile->status,
            'message' => 'Your vendor status has been updated to ' . $this->vendorProfile->status . '.',
        ];
    }
}