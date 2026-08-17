<?php

namespace App\Jobs;

use App\Models\StripeCheckoutPayload;
use App\Models\User;
use App\Notifications\Payment\AdminManualRefundRequiredNotification;
use App\Services\StripeBookingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
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
class ProcessPaidCheckoutSessionJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 6;

    public int $timeout = 120;

    public int $uniqueFor = 300;

    /** Backoff in seconds — 1m, 5m, 15m, 1h, 2h. */
    public array $backoff = [60, 300, 900, 3600, 7200];

    public function __construct(public string $sessionId) {}

    public function uniqueId(): string
    {
        return $this->sessionId;
    }

    public function handle(StripeBookingService $service): void
    {
        $payload = StripeCheckoutPayload::firstOrCreate(
            ['stripe_session_id' => $this->sessionId],
            ['payload' => null]
        );
        $payload->update([
            'fulfilment_status' => 'processing',
            'fulfilment_attempts' => $payload->fulfilment_attempts + 1,
            'last_attempt_at' => now(),
            'last_error' => null,
        ]);

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $session = StripeSession::retrieve($this->sessionId);

            if (($session->payment_status ?? null) !== 'paid') {
                $payload->update([
                    'payment_status' => (string) ($session->payment_status ?? 'unpaid'),
                    'fulfilment_status' => ($session->status ?? null) === 'expired' ? 'expired' : 'pending',
                ]);
                Log::info('ProcessPaidCheckoutSessionJob: session not paid, skipping', [
                    'session_id' => $this->sessionId,
                    'payment_status' => $session->payment_status ?? null,
                ]);

                return;
            }

            $payload->update([
                'payment_status' => 'paid',
                'stripe_payment_intent_id' => $session->payment_intent ?? null,
                'paid_at' => $payload->paid_at ?? now(),
            ]);

            $booking = $service->createBookingFromSession($session);
            if ($booking) {
                $payload->update([
                    'booking_id' => $booking->id,
                    'fulfilment_status' => 'fulfilled',
                    'fulfilled_at' => now(),
                    'last_error' => null,
                ]);

                return;
            }

            // The service already raised the manual-refund alert. Persist the
            // unresolved paid state for operations; never refund automatically.
            $payload->update([
                'fulfilment_status' => 'manual_review',
                'last_error' => 'Paid session could not be converted into a booking.',
            ]);
        } catch (Throwable $e) {
            $payload->update([
                'fulfilment_status' => 'pending',
                'last_error' => substr($e->getMessage(), 0, 2000),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        StripeCheckoutPayload::where('stripe_session_id', $this->sessionId)->update([
            'fulfilment_status' => 'manual_review',
            'last_error' => substr($e->getMessage(), 0, 2000),
        ]);
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
