<?php

namespace App\Notifications\Affiliate;

use App\Models\Affiliate\AffiliateBusiness;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBusinessRegistrationNotification extends Notification
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
        $adminUrl = route('admin.affiliate.business-details', [
            'businessId' => $this->business->id,
        ]);

        $verificationUrl = route('admin.affiliate.business-verification');

        return (new MailMessage)
            ->subject('New Business Registration - ' . $this->business->name)
            ->greeting('Hello Admin Team!')
            ->line('A new business has registered for the Vrooem Affiliate Program.')
            ->line('**Business Details:**')
            ->line('• **Name:** ' . $this->business->name)
            ->line('• **Type:** ' . ucfirst(str_replace('_', ' ', $this->business->business_type)))
            ->line('• **Email:** ' . $this->business->contact_email)
            ->line('• **Phone:** ' . $this->business->contact_phone)
            ->line('• **Location:** ' . $this->business->city . ', ' . $this->business->country)
            ->line('• **Registration Date:** ' . $this->business->created_at->format('M j, Y \a\t g:i A'))
            ->line('• **Verification Status:** ' . ucfirst($this->business->verification_status))
            ->line('• **Business Status:** ' . ucfirst($this->business->status))
            ->line('')
            ->action('Review Business Application', $adminUrl)
            ->line('Or manage all business applications here: ' . $verificationUrl)
            ->line('Please review and verify the business to activate their affiliate account.')
            ->salutation('Best regards,')
            ->salutation('Vrooem System Notification');
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
            'contact_email' => $this->business->contact_email,
            'verification_status' => $this->business->verification_status,
            'business_status' => $this->business->status,
            'registration_date' => $this->business->created_at,
        ];
    }
}