<?php

namespace App\Mail;

use App\Models\NewsletterCampaignLog;
use App\Models\NewsletterSubscription;
use App\Services\Newsletter\NewsletterContentProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterCampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $processedContent;
    public string $unsubscribeUrl;
    public string $trackingPixelUrl;

    public function __construct(
        public NewsletterCampaignLog $log,
        public NewsletterSubscription $subscription,
    ) {
        $processor = new NewsletterContentProcessor();
        $this->processedContent = $processor->process($log->campaign->content, $log, $subscription);
        $this->unsubscribeUrl = $processor->unsubscribeUrl($subscription);
        $this->trackingPixelUrl = $processor->trackingPixelUrl($log);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->log->campaign->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.newsletter-campaign',
        );
    }

    public function headers(): \Illuminate\Mail\Mailables\Headers
    {
        return new \Illuminate\Mail\Mailables\Headers(
            text: [
                'List-Unsubscribe' => '<' . $this->unsubscribeUrl . '>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ],
        );
    }
}
