<?php

namespace App\Notifications\Affiliate;

use App\Models\Affiliate\AffiliateBusiness;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BusinessRegistrationAdminNotification extends Notification
{
    use Queueable;

    public function __construct(
        private AffiliateBusiness $business
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = url('/admin/affiliate/partners');

        return (new MailMessage)
            ->subject('New Affiliate Business Registered — Review Required')
            ->greeting('Hello Admin,')
            ->line('A new affiliate business has registered and requires your review.')
            ->line('**Business Details:**')
            ->line('- **Name:** ' . $this->business->name)
            ->line('- **Type:** ' . ucfirst(str_replace('_', ' ', $this->business->business_type)))
            ->line('- **Contact:** ' . $this->business->contact_email)
            ->line('- **Location:** ' . ($this->business->city ? $this->business->city . ', ' : '') . ($this->business->country ?? 'N/A'))
            ->line('- **Registered:** ' . $this->business->created_at->format('M j, Y \a\t g:i A'))
            ->action('Review Application', $verificationUrl)
            ->line('Please review and approve or reject this application.')
            ->salutation('Best regards,')
            ->salutation('Vrooem System');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Affiliate Registration',
            'role' => 'admin',
            'business_id' => $this->business->id,
            'business_name' => $this->business->name,
            'business_type' => $this->business->business_type,
            'contact_email' => $this->business->contact_email,
            'message' => $this->business->name . ' has registered as an affiliate partner and needs review.',
        ];
    }
}
