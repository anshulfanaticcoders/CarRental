<?php

namespace App\Notifications\Affiliate;

use App\Models\Affiliate\AffiliateBusiness;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BusinessRegistrationNotification extends Notification
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
        $verificationUrl = route('affiliate.business.verify', [
            'token' => $this->business->verification_token,
            'locale' => 'en', // Default locale, could be enhanced to detect user locale
        ]);

        $dashboardUrl = route('affiliate.business.dashboard', [
            'token' => $this->business->dashboard_access_token,
            'locale' => 'en',
        ]);

        return (new MailMessage)
            ->subject('Welcome to Vrooem Affiliate Program - Verify Your Business')
            ->greeting('Hello ' . $this->business->name . ' Team!')
            ->line('Thank you for registering your business with the Vrooem Affiliate Program!')
            ->line('We\'re excited to partner with you to offer exclusive discounts to your customers.')
            ->action('Verify Your Business Email', $verificationUrl)
            ->line('Once verified, you\'ll be able to:')
            ->line('• Generate unique QR codes for your business locations')
            ->line('• Track customer scans and engagement')
            ->line('• Monitor commissions and earnings')
            ->line('• Manage your affiliate dashboard')
            ->line('Your verification link is valid for 7 days.')
            ->line('After verification, you can access your dashboard at: ' . $dashboardUrl)
            ->line('If you have any questions, please don\'t hesitate to contact our support team.')
            ->salutation('Best regards,')
            ->salutation('The Vrooem Team');
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
            'business_type' => $this->business->business_type,
            'verification_token' => $this->business->verification_token,
            'dashboard_token' => $this->business->dashboard_access_token,
        ];
    }
}