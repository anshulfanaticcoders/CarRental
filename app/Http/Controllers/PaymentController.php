<?php

namespace App\Http\Controllers;

use App\Models\BookingPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\BookingExtra;
use App\Models\User;
use App\Models\VendorProfile;
use App\Notifications\Booking\BookingCreatedAdminNotification;
use App\Notifications\Booking\BookingCreatedVendorNotification;
use App\Notifications\Booking\BookingCreatedCompanyNotification;
use App\Notifications\Booking\BookingCreatedCustomerNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Create a Stripe Payment Intent
     */
    public function createPaymentIntent(Request $request)
    {
        // 1. Set Stripe API key
        Stripe::setApiKey(config('stripe.secret'));
    
        // 2. Validate required fields
        $validator = Validator::make($request->all(), [
            'customer' => 'required|array',
            'customer.email' => 'required|email',
            'customer.first_name' => 'required|string',
            'customer.last_name' => 'required|string',
            'amount_paid' => 'required|numeric|min:0',
            'vehicle_id' => 'required|exists:vehicles,id',
            'payment_method_type' => 'required|in:card,klarna,bancontact,apple_pay,paypal',
            'currency' => 'required|in:eur,usd',
            'pickup_date' => 'required|date',
            'return_date' => 'required|date|after:pickup_date'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors(),
                'message' => 'Validation failed'
            ], 422);
        }
    
        try {
            // 3. Find or create customer
            $customer = Customer::firstOrCreate(
                ['email' => $request->customer['email']],
                [
                    'first_name' => $request->customer['first_name'],
                    'last_name' => $request->customer['last_name'],
                    'phone' => $request->customer['phone'] ?? null,
                    'flight_number' => $request->customer['flight_number'] ?? null,
                    'driver_age' => $request->customer['driver_age'] ?? null
                ]
            );
    
            // 4. Create booking record
            $booking = Booking::create([
                'booking_number' => 'BOOK-' . uniqid(),
                'customer_id' => $customer->id,
                'vehicle_id' => $request->vehicle_id,
                'pickup_date' => $request->pickup_date,
                'return_date' => $request->return_date,
                'pickup_time' => $request->pickup_time,
                'return_time' => $request->return_time,
                'pickup_location' => $request->pickup_location,
                'return_location' => $request->return_location,
                'total_days' => $request->total_days,
                'base_price' => $request->base_price,
                'preferred_day' => $request->preferred_day,
                'extra_charges' => $request->extra_charges ?? 0,
                'tax_amount' => $request->tax_amount ?? 0,
                'total_amount' => $request->total_amount,
                'pending_amount' => $request->pending_amount,
                'amount_paid' => $request->amount_paid,
                'payment_status' => 'pending',
                'booking_status' => 'pending',
                'plan' => $request->plan ?? 'Free Plan',
                'plan_price' => $request->plan_price ?? 0,
                'currency' => $request->currency
            ]);
    
            // 5. Save extras if any
            if (!empty($request->extras)) {
                $extrasData = array_map(function($extra) use ($booking) {
                    return [
                        'booking_id' => $booking->id,
                        'extra_type' => $extra['extra_type'],
                        'extra_name' => $extra['extra_name'],
                        'quantity' => $extra['quantity'],
                        'price' => $extra['price'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, array_filter($request->extras, function($extra) {
                    return $extra['quantity'] > 0;
                }));
                
                if (!empty($extrasData)) {
                    BookingExtra::insert($extrasData);
                }
            }
    
            // 6. Create payment intent parameters
            $paymentIntentParams = [
                'amount' => $request->amount_paid * 100, // Convert to cents
                'currency' => $request->currency,
                'metadata' => [
                    'booking_id' => $booking->id,
                    'customer_email' => $customer->email
                ],
                'description' => 'Booking #' . $booking->booking_number,
            ];
    
            // 7. Add payment method specific parameters
            switch ($request->payment_method_type) {
                case 'klarna':
                    $paymentIntentParams['payment_method_types'] = ['klarna'];
                    $paymentIntentParams['payment_method_options'] = [
                        'klarna' => [
                            'preferred_locale' => 'en-US' // Default to English
                        ]
                    ];
                    break;
                    
                case 'bancontact':
                    $paymentIntentParams['payment_method_types'] = ['bancontact'];
                    break;
                    
                case 'apple_pay':
                    $paymentIntentParams['payment_method_types'] = ['card', 'apple_pay'];
                    break;
                    
                case 'paypal':
                    $paymentIntentParams['payment_method_types'] = ['paypal'];
                    break;
                    
                default: // For regular cards
                    $paymentIntentParams['payment_method_types'] = ['card'];
            }
    
            // 8. Create the Payment Intent
            $paymentIntent = PaymentIntent::create($paymentIntentParams);
    
            // 9. Send notifications
            $this->sendBookingNotifications($booking, $customer);
    
            // 10. Return response
            return response()->json([
                'success' => true,
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id,
                'requiresAction' => in_array($paymentIntent->status, ['requires_action', 'requires_payment_method']),
                'status' => $paymentIntent->status
            ]);
    
        } catch (\Exception $e) {
            Log::error('Payment intent creation failed: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Payment processing failed'
            ], 500);
        }
    }

    /**
     * Confirm a payment (for async methods)
     */
    public function confirmPayment(Request $request)
    {
        try {
            // 1. Set Stripe API key
            Stripe::setApiKey(config('stripe.secret'));
            
            // 2. Retrieve payment intent
            $paymentIntent = PaymentIntent::retrieve($request->paymentIntentId);
            
            // 3. Check if payment succeeded
            if ($paymentIntent->status === 'succeeded') {
                $booking = Booking::find($paymentIntent->metadata->booking_id);
                
                if ($booking) {
                    // 4. Update booking status
                    $booking->update([
                        'payment_status' => 'paid',
                        'booking_status' => 'confirmed',
                        'stripe_payment_id' => $paymentIntent->id
                    ]);
                    
                    // 5. Create payment record
                    BookingPayment::create([
                        'booking_id' => $booking->id,
                        'payment_method' => $paymentIntent->payment_method_types[0] ?? 'unknown',
                        'transaction_id' => $paymentIntent->id,
                        'amount' => $paymentIntent->amount / 100,
                        'payment_status' => 'completed',
                        'currency' => $paymentIntent->currency
                    ]);
                    
                    // 6. Send notifications
                    $this->sendBookingNotifications($booking, $booking->customer);
                }
                
                return response()->json(['success' => true]);
            }
            
            return response()->json([
                'error' => 'Payment not completed',
                'status' => $paymentIntent->status
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('Payment confirmation failed: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Payment confirmation failed'
            ], 500);
        }
    }
    
    /**
     * Send booking notifications to all parties
     */
    protected function sendBookingNotifications($booking, $customer)
    {
        try {
            $vehicle = $booking->vehicle;
            
            // 1. Notify Admin
            $adminEmail = config('app.admin_email');
            if ($adminEmail) {
                $admin = User::where('email', $adminEmail)->first();
                if ($admin) {
                    $admin->notify(new BookingCreatedAdminNotification($booking, $customer, $vehicle));
                }
            }

            // 2. Notify Vendor
            if ($vehicle && $vehicle->vendor_id) {
                $vendor = User::find($vehicle->vendor_id);
                if ($vendor) {
                    Notification::route('mail', $vendor->email)
                        ->notify(new BookingCreatedVendorNotification($booking, $customer, $vehicle, $vendor));
                }
            }

            // 3. Notify Company
            if ($vehicle && $vehicle->vendor_id) {
                $vendorProfile = VendorProfile::where('user_id', $vehicle->vendor_id)->first();
                if ($vendorProfile && $vendorProfile->company_email) {
                    Notification::route('mail', $vendorProfile->company_email)
                        ->notify(new BookingCreatedCompanyNotification($booking, $customer, $vehicle, $vendorProfile));
                }
            }

            // 4. Notify Customer
            Notification::route('mail', $customer->email)
                ->notify(new BookingCreatedCustomerNotification($booking, $customer, $vehicle));

        } catch (\Exception $e) {
            Log::error('Notification sending failed: ' . $e->getMessage());
        }
    }
}