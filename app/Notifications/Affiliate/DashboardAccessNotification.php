<?php

namespace App\Notifications\Affiliate;

use App\Models\Affiliate\AffiliateBusiness;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DashboardAccessNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private AffiliateBusiness $business
    ) {}

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
        $dashboardUrl = route('affiliate.business.dashboard', [
            'token' => $this->business->dashboard_access_token,
        ]);

        return (new MailMessage)
            ->subject('Vrooem Affiliate Dashboard - New Access Link')
            ->greeting('Hello ' . $this->business->name . ' Team!')
            ->line('You requested a new access link for your Vrooem Affiliate Dashboard.')
            ->line('Your dashboard access token has been refreshed and is now ready to use.')
            ->action('Access Your Dashboard', $dashboardUrl)
            ->line('What you can do in your dashboard:')
            ->line('• Generate and manage QR codes for your locations')
            ->line('• Track customer scans and engagement analytics')
            ->line('• Monitor your commissions and earnings in real-time')
            ->line('• Update your business information and settings')
            ->line('• Download QR codes for printing and distribution')
            ->line('Important security information:')
            ->line('• This dashboard access link works like a password and never expires')
            ->line('• Keep your dashboard access link secure and private')
            ->line('• You can use this link repeatedly to access your dashboard')
            ->line('• If you didn\'t request this new access link, please contact us immediately')
            ->line('If you have any questions or need assistance, our support team is here to help!')
            ->salutation('Best regards,')
            ->salutation('The Vrooem Affiliate Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'business_id' => $this->business->id,
            'business_name' => $this->business->name,
            'business_email' => $this->business->contact_email,
            'dashboard_token' => $this->business->dashboard_access_token,
            'token_expires_at' => $this->business->dashboard_token_expires_at,
        ];
    }
}