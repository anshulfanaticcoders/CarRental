<?php

namespace App\Jobs;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterCampaignLog;
use App\Models\NewsletterSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNewsletterCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        public NewsletterCampaign $campaign,
    ) {}

    public function handle(): void
    {
        // Guard against duplicate dispatch
        if ($this->campaign->status !== NewsletterCampaign::STATUS_DRAFT
            && $this->campaign->status !== NewsletterCampaign::STATUS_SCHEDULED) {
            Log::warning("Campaign #{$this->campaign->id} already in status '{$this->campaign->status}', skipping.");
            return;
        }

        $this->campaign->update([
            'status' => NewsletterCampaign::STATUS_SENDING,
            'sending_started_at' => now(),
        ]);

        $subscribers = NewsletterSubscription::where('status', 'subscribed')->get();

        if ($subscribers->isEmpty()) {
            $this->campaign->update([
                'status' => NewsletterCampaign::STATUS_SENT,
                'sent_at' => now(),
                'total_recipients' => 0,
            ]);
            return;
        }

        $this->campaign->update(['total_recipients' => $subscribers->count()]);

        // Bulk-create log rows in chunks
        $subscribers->chunk(500)->each(function ($chunk) {
            $rows = $chunk->map(fn ($sub) => [
                'campaign_id' => $this->campaign->id,
                'subscription_id' => $sub->id,
                'status' => NewsletterCampaignLog::STATUS_PENDING,
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();

            NewsletterCampaignLog::insert($rows);
        });

        // Dispatch per-recipient jobs in chunks with delay between chunks
        $logs = NewsletterCampaignLog::where('campaign_id', $this->campaign->id)
            ->where('status', NewsletterCampaignLog::STATUS_PENDING)
            ->get();

        $chunkIndex = 0;
        $logs->chunk(50)->each(function ($chunk) use (&$chunkIndex) {
            $delay = $chunkIndex * 2; // 2 seconds between chunks
            foreach ($chunk as $log) {
                ProcessNewsletterRecipientJob::dispatch($log)
                    ->delay(now()->addSeconds($delay));
            }
            $chunkIndex++;
        });
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Campaign #{$this->campaign->id} failed: " . $exception->getMessage());

        $this->campaign->update([
            'status' => NewsletterCampaign::STATUS_FAILED,
        ]);
    }
}
