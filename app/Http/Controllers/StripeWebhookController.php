<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\User;
use App\Notifications\Payment\AdminPaymentFailedNotification;
use App\Notifications\Payment\CustomerPaymentFailedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use App\Services\StripeBookingService;

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

            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;

            default:
                Log::info('Stripe Webhook: Unhandled event type', ['type' => $event->type]);
        }

        return response('Webhook handled', 200);
    }

    /**
     * Handle checkout.session.completed - Create booking
     */
    protected function handleCheckoutComplete($session)
    {
        try {
            $freshSession = $this->retrieveSession($session);

            if (!$freshSession) {
                return;
            }

            if ($freshSession->payment_status !== 'paid') {
                Log::info('Checkout completed but payment not settled', [
                    'session_id' => $freshSession->id,
                    'payment_status' => $freshSession->payment_status,
                ]);
                return;
            }

            $this->bookingService->createBookingFromSession($freshSession);
        } catch (\Exception $e) {
            Log::error('Webhook handler failed to create booking', [
                'session_id' => $session->id ?? null,
                'error' => $e->getMessage(),
            ]);
            // Rethrow so the top-level handler responds non-2xx and Stripe retries.
            throw $e;
        }
    }

    /**
     * Handle checkout.session.async_payment_succeeded - Create booking after delayed methods
     */
    protected function handleAsyncPaymentSucceeded($session)
    {
        try {
            $freshSession = $this->retrieveSession($session);

            if (!$freshSession) {
                return;
            }

            if ($freshSession->payment_status !== 'paid') {
                Log::warning('Async payment succeeded event received but session not paid', [
                    'session_id' => $freshSession->id,
                    'payment_status' => $freshSession->payment_status,
                ]);
                return;
            }

            $this->bookingService->createBookingFromSession($freshSession);
        } catch (\Exception $e) {
            Log::error('Async payment handler failed to create booking', [
                'session_id' => $session->id ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function retrieveSession($session)
    {
        $sessionId = $session->id ?? null;
        if (!$sessionId) {
            Log::warning('Stripe webhook session missing id');
            return null;
        }

        try {
            return StripeSession::retrieve($sessionId);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve Stripe session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            return $session;
        }
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
            $adminEmail = env('VITE_ADMIN_EMAIL', 'default@admin.com');
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
