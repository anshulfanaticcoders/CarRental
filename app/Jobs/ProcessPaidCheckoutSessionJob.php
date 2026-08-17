<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\Payment\AdminManualRefundRequiredNotification;
use App\Services\StripeBookingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;
use Throwable;

/**
 * Fulfils a PAID checkout session off the webhook request. The webhook used to
 * do everything inline — Stripe retrieve + FX calls inside an open DB
 * transaction — and a slow dependency pushed the response past Stripe's
 * timeout, triggering concurrent redeliveries that deadlocked on the unique
 * index. Now the webhook acks fast and this job does the work, with retries
 * spread over ~3 hours; the 15-minute rescue sweep and the orphaned-payment
 * alert are the backstops behind it.
 */
class ProcessPaidCheckoutSessionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 6;

    public int $timeout = 120;

    /** Backoff in seconds — 1m, 5m, 15m, 1h, 2h. */
    public array $backoff = [60, 300, 900, 3600, 7200];

    public function __construct(public string $sessionId) {}

    public function handle(StripeBookingService $service): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $session = StripeSession::retrieve($this->sessionId);

        if (($session->payment_status ?? null) !== 'paid') {
            Log::info('ProcessPaidCheckoutSessionJob: session not paid, skipping', [
                'session_id' => $this->sessionId,
                'payment_status' => $session->payment_status ?? null,
            ]);

            return;
        }

        $service->createBookingFromSession($session);
    }

    public function failed(Throwable $e): void
    {
        Log::error('ProcessPaidCheckoutSessionJob exhausted retries — paid session has no booking', [
            'session_id' => $this->sessionId,
            'error' => $e->getMessage(),
        ]);

        try {
            $admin = User::where('email', config('admin.email'))->first();
            if ($admin) {
                AdminManualRefundRequiredNotification::sendOnce($admin, new AdminManualRefundRequiredNotification(
                    null,
                    'Paid Stripe session failed to create a booking (fulfilment job exhausted retries)',
                    ['session_id' => $this->sessionId, 'error' => substr($e->getMessage(), 0, 300)]
                ));
            }
        } catch (Throwable $notifyError) {
            Log::warning('ProcessPaidCheckoutSessionJob: failed to send orphaned-payment alert', [
                'session_id' => $this->sessionId,
                'error' => $notifyError->getMessage(),
            ]);
        }
    }
}
