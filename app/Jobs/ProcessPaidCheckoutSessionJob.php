<?php

namespace App\Jobs;

use App\Exceptions\StripeAuthorizationAmountMismatchException;
use App\Models\Booking;
use App\Models\StripeCheckoutPayload;
use App\Models\User;
use App\Notifications\Payment\AdminAuthorizationReviewNotification;
use App\Notifications\Payment\AdminManualRefundRequiredNotification;
use App\Services\StripeBookingService;
use App\Services\StripePaymentLifecycleService;
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

    public function handle(StripeBookingService $service, $paymentLifecycle = null): void
    {
        $payload = StripeCheckoutPayload::firstOrCreate(
            ['stripe_session_id' => $this->sessionId],
            ['payload' => null]
        );

        // Terminal payloads are never re-fulfilled. Without this, a fulfilled
        // payload whose booking was deliberately deleted (e.g. test-data
        // cleanup) gets resurrected by queued retries or the reconcile sweep.
        if (in_array($payload->fulfilment_status, StripeCheckoutPayload::TERMINAL_STATUSES, true)) {
            if ($payload->fulfilment_status === 'fulfilled'
                && $payload->booking_id
                && ! Booking::whereKey($payload->booking_id)->exists()) {
                $payload->update([
                    'fulfilment_status' => 'manual_review',
                    'last_error' => 'Booking deleted after fulfilment — not recreating.',
                ]);
                Log::info('ProcessPaidCheckoutSessionJob: booking deleted after fulfilment, flagged for manual review', [
                    'session_id' => $this->sessionId,
                    'booking_id' => $payload->booking_id,
                ]);
            }

            return;
        }

        if ($payload->fulfilment_status === 'supplier_pending'
            && $payload->booking_id
            && Booking::whereKey($payload->booking_id)->exists()) {
            return;
        }

        $payload->update([
            'fulfilment_status' => 'processing',
            'fulfilment_attempts' => $payload->fulfilment_attempts + 1,
            'last_attempt_at' => now(),
            'last_error' => null,
        ]);

        $session = null;
        $manualSupplierCapture = false;

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $session = StripeSession::retrieve($this->sessionId);
            $manualSupplierCapture = $service->isManualSupplierCaptureMetadata($session->metadata ?? null);
            $authorized = false;
            if ($manualSupplierCapture && ! empty($session->payment_intent)) {
                $paymentLifecycle ??= app(StripePaymentLifecycleService::class);
                $authorized = $paymentLifecycle->isAuthorized((string) $session->payment_intent);
            }

            if (($session->payment_status ?? null) !== 'paid' && ! $authorized) {
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
                'payment_status' => $authorized ? 'authorized' : 'paid',
                'stripe_payment_intent_id' => $session->payment_intent ?? null,
                'paid_at' => $authorized ? null : ($payload->paid_at ?? now()),
            ]);

            $booking = $service->createBookingFromSession($session);
            if ($booking) {
                $payload->update([
                    'booking_id' => $booking->id,
                    'fulfilment_status' => $authorized ? 'supplier_pending' : 'fulfilled',
                    'fulfilled_at' => $authorized ? null : now(),
                    'last_error' => null,
                ]);

                return;
            }

            // The service may have resolved the payload itself (e.g. 'ignored'
            // for a paid session that isn't a car-rental checkout) — keep that.
            if (in_array($payload->refresh()->fulfilment_status, StripeCheckoutPayload::TERMINAL_STATUSES, true)) {
                return;
            }

            // The service already raised the manual-refund alert. Persist the
            // unresolved paid state for operations; never refund automatically.
            $payload->update([
                'fulfilment_status' => 'manual_review',
                'last_error' => 'Paid session could not be converted into a booking.',
            ]);
        } catch (StripeAuthorizationAmountMismatchException $e) {
            $released = false;
            if ($manualSupplierCapture && ! empty($session?->payment_intent)) {
                try {
                    $paymentLifecycle ??= app(StripePaymentLifecycleService::class);
                    $paymentLifecycle->releaseAuthorization(
                        (string) $session->payment_intent,
                        'release_checkout_'.$this->sessionId
                    );
                    $released = true;
                } catch (Throwable $releaseError) {
                    Log::critical('ProcessPaidCheckoutSessionJob: mismatched authorization release failed', [
                        'session_id' => $this->sessionId,
                        'error' => $releaseError->getMessage(),
                    ]);
                }
            }

            $payload->update([
                'payment_status' => $released ? 'authorization_released' : 'authorized',
                'fulfilment_status' => 'manual_review',
                'last_error' => substr($e->getMessage(), 0, 2000),
            ]);
            $this->notifyAuthorizationReview($payload, $e->getMessage(), $released);
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
        $payload = StripeCheckoutPayload::where('stripe_session_id', $this->sessionId)->first();
        $payload?->update([
            'fulfilment_status' => 'manual_review',
            'last_error' => substr($e->getMessage(), 0, 2000),
        ]);

        if ($payload
            && $payload->payment_status === 'authorized'
            && ! $payload->booking_id
            && ! empty($payload->stripe_payment_intent_id)) {
            try {
                app(StripePaymentLifecycleService::class)->releaseAuthorization(
                    (string) $payload->stripe_payment_intent_id,
                    'release_checkout_'.$this->sessionId
                );
                $payload->update(['payment_status' => 'authorization_released']);
                Log::warning('ProcessPaidCheckoutSessionJob: orphaned card authorization released', [
                    'session_id' => $this->sessionId,
                ]);
                $this->notifyAuthorizationReview(
                    $payload,
                    'Checkout fulfilment exhausted retries before a booking could be created.',
                    true
                );

                return;
            } catch (Throwable $releaseError) {
                Log::critical('ProcessPaidCheckoutSessionJob: orphaned authorization needs manual release', [
                    'session_id' => $this->sessionId,
                    'error' => $releaseError->getMessage(),
                ]);
                $this->notifyAuthorizationReview($payload, $releaseError->getMessage(), false);

                return;
            }
        }

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

    private function notifyAuthorizationReview(
        StripeCheckoutPayload $payload,
        string $reason,
        bool $released
    ): void {
        try {
            $admin = User::where('email', config('admin.email'))->first();
            if ($admin) {
                AdminAuthorizationReviewNotification::sendOnce(
                    $admin,
                    new AdminAuthorizationReviewNotification(
                        $this->sessionId,
                        $reason,
                        $released,
                        $payload->booking_id,
                        $payload->booking_id
                            ? Booking::whereKey($payload->booking_id)->value('booking_number')
                            : null,
                    )
                );
            }
        } catch (Throwable $notifyError) {
            Log::warning('ProcessPaidCheckoutSessionJob: failed to send authorization review alert', [
                'session_id' => $this->sessionId,
                'error' => $notifyError->getMessage(),
            ]);
        }
    }
}
