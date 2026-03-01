<?php

namespace App\Services\Newsletter;

use App\Models\NewsletterCampaignLog;
use App\Models\NewsletterSubscription;
use Illuminate\Support\Facades\URL;

class NewsletterContentProcessor
{
    public function process(string $content, NewsletterCampaignLog $log, NewsletterSubscription $subscription): string
    {
        // Replace {{email}} variable
        $content = str_replace('{{email}}', e($subscription->email), $content);

        // Wrap <a href="..."> links with click-tracking redirects (skip mailto: and # links)
        $content = preg_replace_callback(
            '/<a\s([^>]*?)href=["\']([^"\']+)["\']/i',
            function ($matches) use ($log) {
                $attributes = $matches[1];
                $originalUrl = $matches[2];

                // Skip mailto:, tel:, and anchor links
                if (preg_match('/^(mailto:|tel:|#)/i', $originalUrl)) {
                    return $matches[0];
                }

                $trackingUrl = $this->trackClickUrl($log, $originalUrl);
                return '<a ' . $attributes . 'href="' . $trackingUrl . '"';
            },
            $content
        );

        return $content;
    }

    public function trackingPixelUrl(NewsletterCampaignLog $log): string
    {
        if (! $log->id) {
            return '#';
        }

        return URL::signedRoute('newsletter.track.open', ['log' => $log->id]);
    }

    public function trackClickUrl(NewsletterCampaignLog $log, string $originalUrl): string
    {
        if (! $log->id) {
            return $originalUrl;
        }

        return URL::signedRoute('newsletter.track.click', [
            'log' => $log->id,
            'url' => $originalUrl,
        ]);
    }

    public function unsubscribeUrl(NewsletterSubscription $subscription): string
    {
        if (! $subscription->id) {
            return '#';
        }

        return URL::signedRoute('newsletter.unsubscribe', [
            'subscription' => $subscription->id,
        ]);
    }
}
