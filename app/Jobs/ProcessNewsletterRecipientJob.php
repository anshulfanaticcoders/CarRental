<?php

namespace App\Jobs;

use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterCampaignLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessNewsletterRecipientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public array $backoff = [30, 60, 120];

    public function __construct(
        public NewsletterCampaignLog $log,
    ) {}

    public function handle(): void
    {
        $this->log->load(['campaign', 'subscription']);

        // Check campaign not cancelled
        if ($this->log->campaign->status === NewsletterCampaign::STATUS_CANCELLED) {
            return;
        }

        // Check subscriber still active
        if ($this->log->subscription->status !== 'subscribed') {
            $this->log->update(['status' => NewsletterCampaignLog::STATUS_FAILED, 'error_message' => 'Subscriber no longer active']);
            $this->log->campaign->increment('failed_count');
            $this->checkCompletion();
            return;
        }

        try {
            Mail::to($this->log->subscription->email)
                ->send(new NewsletterCampaignMail($this->log, $this->log->subscription));

            $this->log->update([
                'status' => NewsletterCampaignLog::STATUS_SENT,
                'sent_at' => now(),
            ]);

            $this->log->campaign->increment('sent_count');
        } catch (\Throwable $e) {
            Log::error("Failed to send campaign #{$this->log->campaign_id} to {$this->log->subscription->email}: " . $e->getMessage());

            $this->log->update([
                'status' => NewsletterCampaignLog::STATUS_FAILED,
                'error_message' => substr($e->getMessage(), 0, 500),
            ]);

            $this->log->campaign->increment('failed_count');
        }

        $this->checkCompletion();
    }

    private function checkCompletion(): void
    {
        $campaign = $this->log->campaign;
        $pendingCount = $campaign->logs()->where('status', NewsletterCampaignLog::STATUS_PENDING)->count();

        if ($pendingCount === 0 && $campaign->status === NewsletterCampaign::STATUS_SENDING) {
            $campaign->update([
                'status' => NewsletterCampaign::STATUS_SENT,
                'sent_at' => now(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessNewsletterRecipientJob failed for log #{$this->log->id}: " . $exception->getMessage());

        $this->log->update([
            'status' => NewsletterCampaignLog::STATUS_FAILED,
            'error_message' => substr($exception->getMessage(), 0, 500),
        ]);

        $this->log->campaign->increment('failed_count');
        $this->checkCompletion();
    }
}
