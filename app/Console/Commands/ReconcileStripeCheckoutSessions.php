<?php

namespace App\Console\Commands;

use App\Jobs\ProcessPaidCheckoutSessionJob;
use App\Models\StripeCheckoutPayload;
use Illuminate\Console\Command;

class ReconcileStripeCheckoutSessions extends Command
{
    protected $signature = 'stripe:reconcile-checkouts {--limit=100}';

    protected $description = 'Recheck unresolved Stripe sessions and queue paid booking fulfilment.';

    public function handle(): int
    {
        $limit = max(1, min((int) $this->option('limit'), 500));
        $records = StripeCheckoutPayload::query()
            ->whereNotNull('stripe_session_id')
            ->whereNull('booking_id')
            ->where('fulfilment_status', 'pending')
            ->where('created_at', '>=', now()->subDays(2))
            ->where(function ($query) {
                $query->whereNull('last_attempt_at')
                    ->orWhere('last_attempt_at', '<=', now()->subMinutes(5));
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($records as $record) {
            ProcessPaidCheckoutSessionJob::dispatch($record->stripe_session_id);
        }

        $this->info("Queued {$records->count()} unresolved Stripe checkout(s) for reconciliation.");

        return self::SUCCESS;
    }
}
