<?php

namespace App\Console\Commands;

use App\Jobs\SendNewsletterCampaignJob;
use App\Models\NewsletterCampaign;
use Illuminate\Console\Command;

class SendScheduledNewsletterCampaigns extends Command
{
    protected $signature = 'newsletter:send-scheduled';
    protected $description = 'Send newsletter campaigns that are scheduled and due';

    public function handle(): int
    {
        $campaigns = NewsletterCampaign::where('status', NewsletterCampaign::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info('No scheduled campaigns to send.');
            return self::SUCCESS;
        }

        foreach ($campaigns as $campaign) {
            $this->info("Dispatching campaign #{$campaign->id}: {$campaign->subject}");
            SendNewsletterCampaignJob::dispatch($campaign);
        }

        $this->info("Dispatched {$campaigns->count()} campaign(s).");

        return self::SUCCESS;
    }
}
