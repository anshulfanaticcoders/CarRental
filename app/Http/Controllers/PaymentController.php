<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VendorProfile;
use App\Notifications\Booking\BookingCreatedAdminNotification;
use App\Notifications\Booking\BookingCreatedCompanyNotification;
use App\Notifications\Booking\BookingCreatedCustomerNotification;
use App\Notifications\Booking\BookingCreatedVendorNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\BookingPayment;
use App\Models\BookingExtra;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function createPaymentIntent(Request $request)
    {
        Stripe::setApiKey(config('stripe.secret'));
    
        try {
            // Validate required fields
            $validator = Validator::make($request->all(), [
                'customer' => 'required|array',
                'customer.email' => 'required|email',
                'amount_paid' => 'required|numeric|min:0',
                'vehicle_id' => 'required|exists:vehicles,id',
                'payment_method_type' => 'required|in:card,klarna,bancontact,apple_pay,paypal'
            ]);
    
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }
    
            $amount = $request->amount_paid * 100; // Convert to cents
            $currency = 'eur'; // Use EUR for Klarna and Bancontact
            
            // Create or find customer
            $customer = Customer::where('email', $request->customer['email'])->first();
            
            if (!$customer) {
                $customer = Customer::create([
                    'first_name' => $request->customer['first_name'] ?? null,
                    'last_name' => $request->customer['last_name'] ?? null,
                    'email' => $request->customer['email'],
                    'phone' => $request->customer['phone'] ?? null,
                    'flight_number' => $request->customer['flight_number'] ?? null,
                    'driver_age' => $request->customer['driver_age'] ?? null,
                ]);
            }
    
            // Create the booking first
            $booking = Booking::create([
                'booking_number' => uniqid('BOOK-'),
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
                'plan' => $request->plan,
                'plan_price' => $request->plan_price,
            ]);
    
            // Save extras if any
            if (!empty($request->extras)) {
                $extrasData = [];
                foreach ($request->extras as $extra) {
                    if ($extra['quantity'] > 0) {
                        $extrasData[] = [
                            'booking_id' => $booking->id,
                            'extra_type' => $extra['extra_type'],
                            'extra_name' => $extra['extra_name'],
                            'quantity' => $extra['quantity'],
                            'price' => $extra['price'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                
                if (!empty($extrasData)) {
                    BookingExtra::insert($extrasData);
                }
            }
    
            // Create payment intent based on selected method
            $paymentIntentParams = [
                'amount' => $amount,
                'currency' => $currency,
                'metadata' => [
                    'booking_id' => $booking->id,
                ],
                'description' => 'Car Rental Booking - Booking #' . $booking->booking_number,
            ];
    
            // Add payment method specific parameters
            switch ($request->payment_method_type) {
                case 'klarna':
                    $paymentIntentParams['payment_method_types'] = ['klarna'];
                    $paymentIntentParams['payment_method_options'] = [
                        'klarna' => [
                            'preferred_locale' => 'en-US' // Adjust based on customer location
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
                    
                default:
                    return response()->json(['error' => 'Invalid payment method'], 400);
            }
    
            $paymentIntent = PaymentIntent::create($paymentIntentParams);
    
            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id,
                'requiresAction' => $paymentIntent->status === 'requires_action' || 
                                  $paymentIntent->status === 'requires_payment_method',
                'success' => $paymentIntent->status === 'succeeded'
            ]);
    
        } catch (\Exception $e) {
            \Log::error('Payment intent creation failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function confirmPayment(Request $request)
    {
        Stripe::setApiKey(config('stripe.secret'));

        try {
            $paymentIntent = PaymentIntent::retrieve($request->paymentIntentId);
            
            if ($paymentIntent->status === 'succeeded') {
                // Update booking status
                $booking = Booking::find($paymentIntent->metadata->booking_id);
                if ($booking) {
                    $booking->update(['payment_status' => 'paid', 'booking_status' => 'confirmed']);
                    
                    // Create payment record
                    BookingPayment::create([
                        'booking_id' => $booking->id,
                        'payment_method' => $paymentIntent->payment_method_types[0],
                        'transaction_id' => $paymentIntent->id,
                        'amount' => $paymentIntent->amount / 100,
                        'payment_status' => 'completed',
                    ]);
                    
                    // Send notifications (same as in your BookingController)
                    $this->sendBookingNotifications($booking);
                }
                
                return response()->json(['success' => true]);
            }
            
            return response()->json(['error' => 'Payment not completed'], 400);
            
        } catch (\Exception $e) {
            Log::error('Payment confirmation failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    
    protected function sendBookingNotifications($booking)
    {
        $customer = $booking->customer;
        $vehicle = $booking->vehicle;
        
        // Notify Admin
        $adminEmail = env('VITE_ADMIN_EMAIL', 'default@admin.com');
        $admin = User::where('email', $adminEmail)->first();
        if ($admin) {
            $admin->notify(new BookingCreatedAdminNotification($booking, $customer, $vehicle));
        }

        // Notify Vendor
        $vendor = User::find($vehicle->vendor_id);
        if ($vendor) {
            Notification::route('mail', $vendor->email)
                ->notify(new BookingCreatedVendorNotification($booking, $customer, $vehicle, $vendor));
        }

        // Notify Company (VendorProfile)
        $vendorProfile = VendorProfile::where('user_id', $vehicle->vendor_id)->first();
        if ($vendorProfile && $vendorProfile->company_email) {
            Notification::route('mail', $vendorProfile->company_email)
                ->notify(new BookingCreatedCompanyNotification($booking, $customer, $vehicle, $vendorProfile));
        }

        // Notify Customer
        Notification::route('mail', $customer->email)
            ->notify(new BookingCreatedCustomerNotification($booking, $customer, $vehicle));
    }
}