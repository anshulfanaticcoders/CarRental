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
    public function handleWebhook(Request $request)
    {
        Stripe::setApiKey(config('stripe.secret'));
        
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $endpointSecret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            Log::error('Stripe webhook invalid payload: ' . $e->getMessage());
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            Log::error('Stripe webhook signature verification failed: ' . $e->getMessage());
            return response('Invalid signature', 400);
        }

        Log::info('Stripe webhook received: ' . $event->type);

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
        }

        return response('Webhook Handled', 200);
    }

    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        // Get booking ID from metadata
        $bookingId = $paymentIntent->metadata->booking_id ?? null;
        
        if (!$bookingId) {
            Log::error('No booking ID found in payment intent metadata');
            return;
        }

        // Update booking status
        $booking = Booking::find($bookingId);
        if ($booking) {
            $booking->update([
                'payment_status' => 'paid',
                'booking_status' => 'confirmed'
            ]);

            // Create payment record
            BookingPayment::create([
                'booking_id' => $booking->id,
                'payment_method' => $paymentIntent->payment_method_types[0] ?? 'unknown',
                'transaction_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount / 100,
                'payment_status' => 'completed'
            ]);

            // Send confirmation notification
            $booking->customer->notify(new BookingConfirmedNotification($booking));
        }
    }

    protected function handlePaymentIntentFailed($paymentIntent)
    {
        $bookingId = $paymentIntent->metadata->booking_id ?? null;
        
        if ($bookingId) {
            Booking::where('id', $bookingId)->update([
                'payment_status' => 'failed',
                'booking_status' => 'cancelled'
            ]);
            
            Log::info("Payment failed for booking {$bookingId}");
        }
    }

    protected function handleChargeSucceeded($charge)
    {
        // Additional handling for successful charges if needed
    }

    protected function handleCheckoutSessionCompleted($session)
    {
        // Handle checkout.session.completed events if using Stripe Checkout
    }
}