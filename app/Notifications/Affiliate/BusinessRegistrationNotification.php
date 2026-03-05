<?php

namespace App\Notifications\Affiliate;

use App\Models\Affiliate\AffiliateBusiness;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BusinessRegistrationNotification extends Notification
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
        $loginUrl = url('/login');

        return (new MailMessage)
            ->subject('Welcome to the Vrooem Partner Program!')
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('Thank you for registering **' . $this->business->name . '** with the Vrooem Partner Program.')
            ->line('We\'re currently reviewing your application. This usually takes 1-2 business days.')
            ->line('Once approved, you\'ll be able to:')
            ->line('- Generate unique QR codes for your business locations')
            ->line('- Track customer scans and engagement')
            ->line('- Earn commissions on every booking')
            ->line('- Monitor your earnings in real-time')
            ->line('You\'ll receive an email as soon as your account is approved.')
            ->action('Log In to Your Dashboard', $loginUrl)
            ->line('If you have any questions, please don\'t hesitate to contact our support team.')
            ->salutation('Best regards,')
            ->salutation('The Vrooem Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Welcome to Vrooem Partner Program',
            'role' => 'affiliate',
            'business_id' => $this->business->id,
            'business_name' => $this->business->name,
            'message' => 'Your partner account has been created. We\'re reviewing your application.',
        ];
    }
}
