<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
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
            $this->bookingService->createBookingFromSession($session);
        } catch (\Exception $e) {
            Log::error('Webhook handler failed to create booking', ['error' => $e->getMessage()]);
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

        $booking = Booking::where('stripe_payment_intent_id', $paymentIntent->id)->first();
        if ($booking) {
            $booking->update([
                'payment_status' => 'failed',
            ]);

            // Update payment record
            BookingPayment::where('transaction_id', $paymentIntent->id)->update([
                'payment_status' => 'failed',
            ]);

            Log::info('Booking payment marked as failed', ['booking_id' => $booking->id]);
        }
    }
}
