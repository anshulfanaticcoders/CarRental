<?php

namespace App\Http\Controllers;

use App\Models\NewsletterCampaignLog;
use App\Models\NewsletterSubscription;
use Illuminate\Http\Request;

class NewsletterTrackingController extends Controller
{
    public function trackOpen(Request $request, NewsletterCampaignLog $log)
    {
        // Log first open timestamp, always increment counter
        if (! $log->opened_at) {
            $log->update(['opened_at' => now()]);
            $log->campaign->increment('opened_count');
        }
        $log->increment('open_count');

        // Return 1x1 transparent GIF
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($pixel, 200, [
            'Content-Type' => 'image/gif',
            'Content-Length' => strlen($pixel),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function trackClick(Request $request, NewsletterCampaignLog $log)
    {
        $url = $request->query('url');

        if (! $url) {
            return redirect('/');
        }

        // Log first click timestamp, always increment counter
        if (! $log->clicked_at) {
            $log->update(['clicked_at' => now()]);
            $log->campaign->increment('clicked_count');
        }
        $log->increment('click_count');

        return redirect($url);
    }

    public function unsubscribe(NewsletterSubscription $subscription)
    {
        if ($subscription->status !== 'unsubscribed') {
            $subscription->update([
                'status' => 'unsubscribed',
                'unsubscribed_at' => now(),
            ]);

            // Increment unsubscribed count on any active campaigns that included this subscriber
            $subscription->campaignLogs()
                ->whereHas('campaign', fn ($q) => $q->whereIn('status', ['sending', 'sent']))
                ->each(function ($log) {
                    $log->campaign->increment('unsubscribed_count');
                });
        }

        return redirect('/')->with('success', 'You have been unsubscribed from our newsletter.');
    }
}
