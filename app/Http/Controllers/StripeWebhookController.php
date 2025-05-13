<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Notifications\Booking\BookingConfirmedNotification;

class StripeWebhookController extends Controller
{
    /**
     * Handle Stripe webhook events
     */
    public function handleWebhook(Request $request)
    {
        // 1. Set Stripe API key from config
        Stripe::setApiKey(config('stripe.secret'));
        
        // 2. Get webhook payload and signature
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('stripe.webhook_secret');

        try {
            // 3. Verify webhook signature
            $event = Webhook::constructEvent(
                $payload, 
                $sigHeader, 
                $endpointSecret
            );

            // 4. Log received event type
            Log::info('Stripe webhook received: ' . $event->type);

            // 5. Handle specific event types
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event->data->object);
                    break;
                    
                case 'payment_intent.payment_failed':
                    $this->handlePaymentIntentFailed($event->data->object);
                    break;
                    
                case 'charge.succeeded':
                    $this->handleChargeSucceeded($event->data->object);
                    break;
                    
                case 'checkout.session.completed':
                    $this->handleCheckoutSessionCompleted($event->data->object);
                    break;
                    
                default:
                    // Log unhandled event types
                    Log::info('Unhandled webhook event type: ' . $event->type);
            }

            // 6. Return success response
            return response('Webhook Handled', 200);

        } catch (\UnexpectedValueException $e) {
            // Handle invalid payload
            Log::error('Invalid webhook payload: ' . $e->getMessage());
            return response('Invalid payload', 400);
            
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Handle invalid signature
            Log::error('Invalid webhook signature: ' . $e->getMessage());
            return response('Invalid signature', 400);
        }
    }

    /**
     * Handle successful payment intent
     */
    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        // 1. Get booking ID from metadata
        $bookingId = $paymentIntent->metadata->booking_id ?? null;
        
        if (!$bookingId) {
            Log::error('No booking ID found in payment intent metadata');
            return;
        }

        // 2. Find the booking record
        $booking = Booking::find($bookingId);
        
        if (!$booking) {
            Log::error('Booking not found for ID: ' . $bookingId);
            return;
        }

        // 3. Update booking status
        $booking->update([
            'payment_status' => 'paid',
            'booking_status' => 'confirmed',
            'stripe_payment_id' => $paymentIntent->id
        ]);

        // 4. Create payment record
        BookingPayment::create([
            'booking_id' => $booking->id,
            'payment_method' => $paymentIntent->payment_method_types[0] ?? 'unknown',
            'transaction_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount / 100, // Convert from cents
            'payment_status' => 'completed',
            'currency' => $paymentIntent->currency
        ]);

        // 5. Send confirmation notification
        try {
            $booking->customer->notify(new BookingConfirmedNotification($booking));
        } catch (\Exception $e) {
            Log::error('Failed to send confirmation: ' . $e->getMessage());
        }

        Log::info('Successfully processed payment for booking: ' . $bookingId);
    }

    /**
     * Handle failed payment intent
     */
    protected function handlePaymentIntentFailed($paymentIntent)
    {
        // 1. Get booking ID from metadata
        $bookingId = $paymentIntent->metadata->booking_id ?? null;
        
        if (!$bookingId) {
            Log::error('No booking ID in failed payment intent');
            return;
        }

        // 2. Update booking status
        Booking::where('id', $bookingId)->update([
            'payment_status' => 'failed',
            'booking_status' => 'cancelled',
            'failure_reason' => $paymentIntent->last_payment_error->message ?? null
        ]);
        
        Log::error("Payment failed for booking {$bookingId}. Reason: " . 
                 ($paymentIntent->last_payment_error->message ?? 'Unknown'));
    }

    /**
     * Handle successful charges (additional layer)
     */
    protected function handleChargeSucceeded($charge)
    {
        // Optional: Add any charge-specific logic here
        Log::info('Charge succeeded: ' . $charge->id);
    }

    /**
     * Handle completed checkout sessions
     */
    protected function handleCheckoutSessionCompleted($session)
    {
        // Optional: Add logic if using Stripe Checkout
        Log::info('Checkout session completed: ' . $session->id);
    }
}