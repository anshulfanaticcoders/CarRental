<?php

namespace App\Notifications;

use App\Models\NewsletterSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class NewsletterSubscriptionConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected NewsletterSubscription $subscription,
        protected string $subscriptionLocale
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $confirmUrl = URL::temporarySignedRoute(
            'newsletter.confirm',
            now()->addHours(24),
            [
                'locale' => $this->subscriptionLocale,
                'subscription' => $this->subscription->id,
            ]
        );

        return (new MailMessage)
            ->subject('Confirm your newsletter subscription')
            ->greeting('Hello')
            ->line('Thanks for subscribing to our newsletter.')
            ->line('Please confirm your subscription by clicking the button below:')
            ->action('Confirm Subscription', $confirmUrl)
            ->line('If you did not request this, you can ignore this email.');
    }
}
