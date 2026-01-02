<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\BookingExtra;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StripeBookingService
{
    /**
     * Create a booking from a Stripe Checkout Session
     */
    public function createBookingFromSession($session)
    {
        Log::info('StripeBookingService: Starting creation', ['session_id' => $session->id]);

        // Idempotency check
        $existingBooking = Booking::where('stripe_session_id', $session->id)->first();
        if ($existingBooking) {
            Log::info('StripeBookingService: Booking already exists', ['booking_id' => $existingBooking->id]);
            return $existingBooking;
        }

        $metadata = $session->metadata;

        // Start Transaction
        DB::beginTransaction();

        try {
            // Find or create customer
            $customer = $this->findOrCreateCustomer($metadata);
            Log::info('StripeBookingService: Customer processed', ['customer_id' => $customer->id]);

            // Create booking
            $booking = Booking::create([
                'booking_number' => Booking::generateBookingNumber(),
                'customer_id' => $customer->id,
                'vehicle_id' => null, // External vehicle
                'provider_source' => $metadata->vehicle_source ?? 'greenmotion',
                'provider_vehicle_id' => $metadata->vehicle_id ?? null,
                'vehicle_name' => ($metadata->vehicle_brand ?? '') . ' ' . ($metadata->vehicle_model ?? ''),
                'vehicle_image' => $metadata->vehicle_image ?? null,
                'pickup_date' => $metadata->pickup_date,
                'pickup_time' => $metadata->pickup_time,
                'return_date' => $metadata->dropoff_date,
                'return_time' => $metadata->dropoff_time,
                'pickup_location' => $metadata->pickup_location,
                'return_location' => $metadata->dropoff_location ?? $metadata->pickup_location,
                'plan' => $metadata->package ?? 'BAS',
                'total_days' => (int) ($metadata->number_of_days ?? 1),
                'base_price' => (float) ($metadata->total_amount ?? 0),
                'tax_amount' => 0,
                'total_amount' => (float) ($metadata->total_amount ?? 0),
                'amount_paid' => (float) ($metadata->payable_amount ?? 0),
                'pending_amount' => (float) ($metadata->pending_amount ?? 0),
                'booking_currency' => $metadata->currency ?? 'EUR',
                'payment_status' => 'partial',
                'booking_status' => 'confirmed',
                'stripe_session_id' => $session->id,
                'stripe_payment_intent_id' => $session->payment_intent,
            ]);

            Log::info('StripeBookingService: Booking record created', ['booking_id' => $booking->id]);

            // Create payment record
            BookingPayment::create([
                'booking_id' => $booking->id,
                'payment_method' => 'stripe',
                'transaction_id' => $session->payment_intent,
                'amount' => (float) ($metadata->payable_amount ?? 0),
                'payment_status' => 'succeeded',
                'payment_date' => now(),
            ]);

            // Create extras
            $extrasData = json_decode($metadata->extras_data ?? '[]', true);

            if (!empty($extrasData)) {
                // New Logic: Use detailed data from frontend
                foreach ($extrasData as $extraItem) {
                    BookingExtra::create([
                        'booking_id' => $booking->id,
                        'extra_type' => 'optional',
                        'extra_name' => $extraItem['name'] ?? 'Unknown Extra',
                        'quantity' => (int) ($extraItem['qty'] ?? 1),
                        'price' => (float) ($extraItem['total'] ?? 0),
                    ]);
                }
            } else {
                // Fallback Logic (Old)
                $extras = json_decode($metadata->extras ?? '[]', true);
                if (!empty($extras)) {
                    // Pre-fetch all needed addons to avoid N+1
                    $addonIds = array_keys($extras);
                    $addons = \App\Models\BookingAddon::whereIn('id', $addonIds)->get()->keyBy('id');

                    foreach ($extras as $extraId => $quantity) {
                        if ($quantity > 0) {
                            $addon = $addons->find($extraId);

                            // Fallback if addon not found (shouldn't happen if IDs are valid)
                            $name = $addon ? $addon->extra_name : "Extra #$extraId";
                            $price = $addon ? $addon->price : 0;

                            BookingExtra::create([
                                'booking_id' => $booking->id,
                                'extra_type' => 'optional',
                                'extra_name' => $name,
                                'quantity' => (int) $quantity,
                                'price' => (float) $price,
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            Log::info('StripeBookingService: Transaction committed successfully', ['booking_id' => $booking->id]);

            return $booking;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('StripeBookingService: Error creating booking - Transaction Rolled Back', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function findOrCreateCustomer($metadata)
    {
        $email = $metadata->customer_email ?? null;

        if ($email) {
            $customer = Customer::where('email', $email)->first();
            if ($customer) {
                return $customer;
            }
        }

        return Customer::create([
            'name' => $metadata->customer_name ?? 'Guest',
            'email' => $email ?? 'guest_' . time() . '@temp.com',
            'phone' => $metadata->customer_phone ?? null,
        ]);
    }
}
