<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingHold;
use App\Models\BookingPayment;
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
                $this->handleChargeDispute($event->data->object);
                break;

            default:
                Log::info('Stripe Webhook: Unhandled event type', ['type' => $event->type]);
        }

        return response('Webhook handled', 200);
    }

    /**
     * Handle checkout.session.completed — queue booking creation.
     *
     * The webhook must ack fast: doing the Stripe retrieve + FX calls inline
     * pushed responses past Stripe's timeout, and the resulting concurrent
     * redeliveries deadlocked on the bookings unique index. The event payload
     * already carries payment_status, so unpaid sessions are filtered here and
     * the job re-verifies against a fresh retrieve before booking.
     */
    protected function handleCheckoutComplete($session)
    {
        $sessionId = $session->id ?? null;
        if (! $sessionId) {
            Log::warning('Stripe webhook session missing id');

            return;
        }

        if (($session->payment_status ?? null) !== 'paid') {
            Log::info('Checkout completed but payment not settled', [
                'session_id' => $sessionId,
                'payment_status' => $session->payment_status ?? null,
            ]);

            return;
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

        // Find any pending booking and mark as expired
        $booking = Booking::where('stripe_session_id', $session->id)->first();
        if ($booking) {
            $booking->update([
                'booking_status' => 'expired',
                'payment_status' => 'expired',
            ]);
            Log::info('Booking marked as expired', ['booking_id' => $booking->id]);
        }

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
    protected function handleChargeDispute($dispute)
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

        $booking->update([
            'provider_metadata' => array_merge($booking->provider_metadata ?? [], [
                'dispute_opened_at' => now()->toIso8601String(),
                'dispute_reason' => (string) ($dispute->reason ?? ''),
                'dispute_amount_minor' => (int) ($dispute->amount ?? 0),
                'dispute_currency' => strtoupper((string) ($dispute->currency ?? '')),
            ]),
        ]);

        Log::warning('Stripe Webhook: dispute opened on booking', [
            'booking_id' => $booking->id,
            'reason' => $dispute->reason ?? null,
        ]);

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
