<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingHold;
use App\Models\BookingPayment;
use App\Models\StripeCheckoutPayload;
use App\Models\User;
use App\Notifications\Payment\AdminChargeDisputeNotification;
use App\Notifications\Payment\AdminChargeRefundedNotification;
use App\Notifications\Payment\AdminManualRefundRequiredNotification;
use App\Notifications\Payment\AdminPaymentFailedNotification;
use App\Notifications\Payment\CustomerPaymentFailedNotification;
use App\Services\StripeBookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    private const SUPPLIER_FAST_PATH_WAIT_MILLISECONDS = 8000;

    private const SUPPLIER_FAST_PATH_POLL_MICROSECONDS = 250000;

    protected $bookingService;

    public function __construct(StripeBookingService $bookingService)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $this->bookingService = $bookingService;
    }

    /**
     * Handle incoming Stripe webhooks
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        if (! $webhookSecret) {
            Log::critical('Stripe Webhook: Missing webhook secret');

            return response('Webhook unavailable', 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe Webhook: Invalid payload', ['error' => $e->getMessage()]);

            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe Webhook: Invalid signature', ['error' => $e->getMessage()]);

            return response('Invalid signature', 400);
        }

        Log::info('Stripe Webhook received', ['type' => $event->type, 'id' => $event->id]);

        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutComplete($event->data->object);
                break;

            case 'checkout.session.async_payment_succeeded':
                $this->handleAsyncPaymentSucceeded($event->data->object);
                break;

            case 'checkout.session.expired':
                $this->handleCheckoutExpired($event->data->object);
                break;

            case 'checkout.session.async_payment_failed':
                // Klarna/Bancontact payment fell through after the session
                // completed. No booking exists yet — release the vehicle hold
                // now instead of letting it sit out its full lifetime.
                Log::warning('Stripe Webhook: async payment failed', ['session_id' => $event->data->object->id ?? null]);
                BookingHold::where('stripe_session_id', $event->data->object->id ?? '')
                    ->where('status', 'active')
                    ->update(['status' => 'released']);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;

            case 'charge.refunded':
                $this->handleChargeRefunded($event->data->object);
                break;

            case 'charge.dispute.created':
            case 'charge.dispute.updated':
            case 'charge.dispute.closed':
                $this->handleChargeDispute($event->data->object, $event->type);
                break;

            default:
                Log::info('Stripe Webhook: Unhandled event type', ['type' => $event->type]);
        }

        return response('Webhook handled', 200);
    }

    /**
     * Handle checkout.session.completed — queue booking creation.
     *
     * Stripe retrieval, FX calls, and supplier work stay in the queue to avoid
     * concurrent redelivery deadlocks. For manual-capture supplier sessions we
     * only wait on persisted state after dispatch, bounded below Stripe's hosted
     * Checkout redirect limit. The job remains the authoritative fulfilment path.
     */
    protected function handleCheckoutComplete($session)
    {
        $fastPathStartedAt = hrtime(true);
        $sessionId = $session->id ?? null;
        if (! $sessionId) {
            Log::warning('Stripe webhook session missing id');

            return;
        }

        $manualSupplierCapture = $this->bookingService->isManualSupplierCaptureMetadata(
            $session->metadata ?? null
        );
        if (($session->payment_status ?? null) !== 'paid' && ! $manualSupplierCapture) {
            Log::info('Checkout completed but payment not settled', [
                'session_id' => $sessionId,
                'payment_status' => $session->payment_status ?? null,
            ]);

            return;
        }

        $payload = StripeCheckoutPayload::firstOrCreate(
            ['stripe_session_id' => $sessionId],
            ['fulfilment_status' => 'pending']
        );
        $terminalPayload = in_array(
            $payload->fulfilment_status,
            StripeCheckoutPayload::TERMINAL_STATUSES,
            true
        );
        if (! $terminalPayload || empty($payload->payment_status)) {
            $payload->payment_status = $manualSupplierCapture ? 'authorized' : 'paid';
        }
        if (empty($payload->stripe_payment_intent_id)) {
            $payload->stripe_payment_intent_id = $session->payment_intent ?? null;
        }
        if (! $terminalPayload && ! $manualSupplierCapture) {
            $payload->paid_at ??= now();
        }
        // Stripe replays this webhook for days; a replay must never downgrade
        // a terminal payload back to pending (that resurrects deleted bookings).
        if (! $terminalPayload) {
            $payload->fulfilment_status = 'pending';
        }
        $payload->save();

        // The hold and the session share a 30-min lifetime; queued fulfilment
        // adds delay AFTER payment, so re-arm the hold now or a payment near
        // session expiry can lose its vehicle to another customer before the
        // job runs — turning a PAID booking into a manual refund.
        try {
            BookingHold::where('stripe_session_id', $sessionId)
                ->where('status', 'active')
                ->update(['expires_at' => now()->addMinutes(30)]);
        } catch (\Throwable $e) {
            Log::warning('Stripe Webhook: failed to extend hold for paid session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            \App\Jobs\ProcessPaidCheckoutSessionJob::dispatch($sessionId);
        } catch (\Exception $e) {
            Log::error('Webhook handler failed to queue booking creation', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            // A paid session with no booking = orphaned money. Alert admin once
            // per session (Stripe retries this webhook for days) so it is seen
            // in minutes, not found in logs later.
            $this->notifyAdminOrphanedPaymentOnce($sessionId, $e->getMessage(), $session->payment_intent ?? null);
            // Rethrow so the top-level handler responds non-2xx and Stripe retries.
            throw $e;
        }

        if ($manualSupplierCapture) {
            try {
                $elapsedMilliseconds = max(0, intdiv(hrtime(true) - $fastPathStartedAt, 1_000_000));
                $waitBudgetMilliseconds = max(
                    0,
                    self::SUPPLIER_FAST_PATH_WAIT_MILLISECONDS - $elapsedMilliseconds
                );
                $resolvedBeforeRedirect = $this->waitForSupplierResolution(
                    $sessionId,
                    $waitBudgetMilliseconds
                );
                Log::info('Stripe Webhook: supplier checkout fast-path wait finished', [
                    'session_id' => $sessionId,
                    'resolved_before_redirect' => $resolvedBeforeRedirect,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Stripe Webhook: supplier checkout fast-path wait failed', [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Stripe-hosted Checkout waits at most ten seconds for this webhook before
     * redirecting. Give the queued supplier flow most of that window so normal
     * confirmations land directly on the success page, while slower suppliers
     * still fall back to the polling status page.
     */
    protected function waitForSupplierResolution(string $sessionId, ?int $maxWaitMilliseconds = null): bool
    {
        $maxWaitMilliseconds ??= self::SUPPLIER_FAST_PATH_WAIT_MILLISECONDS;
        $maxWaitMilliseconds = max(0, min($maxWaitMilliseconds, self::SUPPLIER_FAST_PATH_WAIT_MILLISECONDS));
        $deadline = hrtime(true) + ($maxWaitMilliseconds * 1_000_000);

        do {
            $booking = Booking::query()
                ->where('stripe_session_id', $sessionId)
                ->first([
                    'booking_status',
                    'payment_status',
                    'provider_booking_ref',
                    'provider_metadata',
                ]);

            if ($booking && $this->supplierBookingHasResolved($booking)) {
                return true;
            }

            $payloadStatus = StripeCheckoutPayload::where('stripe_session_id', $sessionId)
                ->value('fulfilment_status');
            if (in_array($payloadStatus, StripeCheckoutPayload::TERMINAL_STATUSES, true)) {
                return true;
            }

            if ($maxWaitMilliseconds === 0 || hrtime(true) >= $deadline) {
                return false;
            }

            $remainingMicroseconds = max(1, intdiv($deadline - hrtime(true), 1000));
            usleep((int) min(self::SUPPLIER_FAST_PATH_POLL_MICROSECONDS, $remainingMicroseconds));
        } while (true);
    }

    private function supplierBookingHasResolved(Booking $booking): bool
    {
        $metadata = is_array($booking->provider_metadata) ? $booking->provider_metadata : [];

        if (! empty($metadata['reservation_manual_check'])
            || ! empty($metadata['payment_capture_manual_check'])) {
            return true;
        }

        if (in_array($booking->booking_status, ['cancelled', 'rejected', 'expired', 'reservation_failed'], true)
            || in_array($booking->payment_status, ['authorization_released', 'payment_cancelled', 'refund_pending', 'refunded'], true)) {
            return true;
        }

        return ! empty($booking->provider_booking_ref)
            && in_array($booking->booking_status, ['confirmed', 'completed'], true)
            && in_array($booking->payment_status, ['partial', 'paid'], true);
    }

    /**
     * Alert admin that a PAID checkout session failed to produce a booking.
     * Deduped per session by AdminManualRefundRequiredNotification::sendOnce()
     * so the days of webhook retries Stripe performs don't spam the inbox.
     */
    private function notifyAdminOrphanedPaymentOnce(?string $sessionId, string $error, ?string $paymentIntentId = null): void
    {
        try {
            $admin = User::where('email', config('admin.email'))->first();
            if ($admin) {
                AdminManualRefundRequiredNotification::sendOnce($admin, new AdminManualRefundRequiredNotification(
                    $paymentIntentId,
                    'Paid Stripe session failed to create a booking (webhook error)',
                    ['session_id' => $sessionId, 'error' => substr($error, 0, 300)]
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('Stripe Webhook: failed to send orphaned-payment alert', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle checkout.session.async_payment_succeeded — same queued path as
     * the synchronous completion; the delayed method has now settled.
     */
    protected function handleAsyncPaymentSucceeded($session)
    {
        $this->handleCheckoutComplete($session);
    }

    /**
     * Handle checkout.session.expired
     */
    protected function handleCheckoutExpired($session)
    {
        Log::info('Checkout session expired', ['session_id' => $session->id]);

        $payload = StripeCheckoutPayload::where('stripe_session_id', $session->id)->first();
        if ($payload?->payment_status === 'paid') {
            Log::warning('Ignoring expired event for a checkout already recorded as paid', [
                'session_id' => $session->id,
            ]);

            return;
        }

        // Only an unpaid, non-terminal booking may be expired. Stripe events
        // can arrive late or out of order and must never overwrite a paid row.
        $booking = Booking::where('stripe_session_id', $session->id)->first();
        if ($booking && $booking->booking_status === 'pending' && in_array($booking->payment_status, ['pending', 'unpaid', 'failed'], true)) {
            $booking->update([
                'booking_status' => 'expired',
                'payment_status' => 'expired',
            ]);
            Log::info('Booking marked as expired', ['booking_id' => $booking->id]);
        } elseif ($booking) {
            Log::warning('Ignoring expired event for a non-pending booking', [
                'session_id' => $session->id,
                'booking_id' => $booking->id,
                'booking_status' => $booking->booking_status,
                'payment_status' => $booking->payment_status,
            ]);

            return;
        }

        $payload?->update([
            'payment_status' => 'expired',
            'fulfilment_status' => 'expired',
        ]);

        BookingHold::where('stripe_session_id', $session->id)
            ->where('status', 'active')
            ->update(['status' => 'released']);
    }

    /**
     * Handle charge.refunded — refunds are performed manually in the Stripe
     * dashboard by design, so this event is how the app learns they happened.
     * Without it the booking stays confirmed/partial forever and any supplier
     * reservation silently stays live.
     */
    protected function handleChargeRefunded($charge)
    {
        $paymentIntentId = $charge->payment_intent ?? null;
        $booking = Booking::with('customer')
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->first();

        if (! $booking) {
            Log::info('Stripe Webhook: refund for a payment with no booking', [
                'payment_intent_id' => $paymentIntentId,
            ]);

            return;
        }

        $fullyRefunded = (bool) ($charge->refunded ?? false);
        $amountRefunded = (int) ($charge->amount_refunded ?? 0);
        $currency = (string) ($charge->currency ?? '');

        $updates = [
            'provider_metadata' => array_merge($booking->provider_metadata ?? [], [
                'refund_recorded_at' => now()->toIso8601String(),
                'refund_amount_minor' => $amountRefunded,
                'refund_currency' => strtoupper($currency),
                'fully_refunded' => $fullyRefunded,
                'supplier_cancellation_required' => $fullyRefunded && ! empty($booking->provider_booking_ref),
            ]),
        ];
        if ($fullyRefunded) {
            $updates['payment_status'] = 'refunded';
        }
        $booking->update($updates);

        if ($fullyRefunded) {
            BookingPayment::where('transaction_id', $paymentIntentId)
                ->update(['payment_status' => 'refunded']);
        }

        Log::info('Stripe Webhook: refund recorded on booking', [
            'booking_id' => $booking->id,
            'fully_refunded' => $fullyRefunded,
            'amount_refunded' => $amountRefunded,
        ]);

        try {
            $admin = User::where('email', config('admin.email'))->first();
            if ($admin) {
                $admin->notify(new AdminChargeRefundedNotification($booking, $amountRefunded, $currency, $fullyRefunded));
            }
        } catch (\Throwable $e) {
            Log::warning('Stripe Webhook: failed to send refund-recorded notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle charge.dispute.created — a chargeback has a response deadline and
     * takes the money if ignored; it must never live only in the Stripe dashboard.
     */
    protected function handleChargeDispute($dispute, string $eventType = 'charge.dispute.created')
    {
        $paymentIntentId = $dispute->payment_intent ?? null;
        $booking = Booking::with('customer')
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->first();

        if (! $booking) {
            Log::warning('Stripe Webhook: dispute for a payment with no booking', [
                'payment_intent_id' => $paymentIntentId,
            ]);

            return;
        }

        $isClosed = $eventType === 'charge.dispute.closed';
        $booking->update([
            'provider_metadata' => array_merge($booking->provider_metadata ?? [], [
                'dispute_opened_at' => now()->toIso8601String(),
                'dispute_last_event' => $eventType,
                'dispute_status' => (string) ($dispute->status ?? ''),
                'dispute_evidence_due_by' => ! empty($dispute->evidence_details->due_by)
                    ? \Carbon\Carbon::createFromTimestamp((int) $dispute->evidence_details->due_by)->toIso8601String()
                    : null,
                'dispute_closed_at' => $isClosed ? now()->toIso8601String() : null,
                'dispute_reason' => (string) ($dispute->reason ?? ''),
                'dispute_amount_minor' => (int) ($dispute->amount ?? 0),
                'dispute_currency' => strtoupper((string) ($dispute->currency ?? '')),
            ]),
        ]);

        Log::warning('Stripe Webhook: dispute opened on booking', [
            'booking_id' => $booking->id,
            'reason' => $dispute->reason ?? null,
        ]);

        if ($eventType !== 'charge.dispute.created') {
            return;
        }

        try {
            $admin = User::where('email', config('admin.email'))->first();
            if ($admin) {
                $admin->notify(new AdminChargeDisputeNotification(
                    $booking,
                    (string) ($dispute->reason ?? ''),
                    (int) ($dispute->amount ?? 0),
                    (string) ($dispute->currency ?? '')
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('Stripe Webhook: failed to send dispute notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle payment_intent.payment_failed
     */
    protected function handlePaymentFailed($paymentIntent)
    {
        Log::info('Payment failed', ['payment_intent_id' => $paymentIntent->id]);

        $booking = Booking::with(['customer', 'vehicle'])
            ->where('stripe_payment_intent_id', $paymentIntent->id)
            ->first();

        if (! $booking) {
            return;
        }

        // Out-of-order events: if the booking already succeeded (paid/partial),
        // a late payment_failed event must not regress its state. Only allow the
        // failed transition from pending / null payment_status.
        if (in_array($booking->payment_status, ['paid', 'partial'], true)) {
            Log::warning('Ignoring payment_failed event for booking already in paid/partial state', [
                'booking_id' => $booking->id,
                'current_status' => $booking->payment_status,
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return;
        }

        $booking->update(['payment_status' => 'failed']);

        BookingPayment::where('transaction_id', $paymentIntent->id)
            ->whereNotIn('payment_status', ['paid', 'partial'])
            ->update(['payment_status' => 'failed']);

        BookingHold::where('stripe_session_id', $booking->stripe_session_id)
            ->where('status', 'active')
            ->update(['status' => 'released']);

        Log::info('Booking payment marked as failed', ['booking_id' => $booking->id]);

        $customer = $booking->customer;
        $vehicle = $booking->vehicle;
        $customerUser = $customer?->user;

        try {
            if ($customerUser) {
                $customerUser->notify(new CustomerPaymentFailedNotification($booking, $customer, $vehicle));
            } elseif ($customer?->email) {
                Notification::route('mail', $customer->email)
                    ->notify(new CustomerPaymentFailedNotification($booking, $customer, $vehicle));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send customer payment-failed notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $adminEmail = config('admin.email');
            $admin = User::where('email', $adminEmail)->first();
            if ($admin) {
                $admin->notify(new AdminPaymentFailedNotification($booking, $customer, $vehicle));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send admin payment-failed notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
