<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VendorProfile;
use App\Notifications\Booking\BookingCreatedAdminNotification;
use App\Notifications\Booking\BookingCreatedCompanyNotification;
use App\Notifications\Booking\BookingCreatedCustomerNotification;
use App\Notifications\Booking\BookingCreatedVendorNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Illuminate\Support\Str;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\BookingPayment;
use App\Models\BookingExtra;
use App\Models\Vehicle;

class PaymentController extends Controller
{
    public function charge(Request $request)
    {
        Stripe::setApiKey(config('stripe.secret'));

        try {
            $bookingData = $request->input('bookingData');
            
            // First save the customer and booking to database
            $customer = Customer::where('email', $bookingData['customer']['email'])->first();

            if (!$customer) {
                $customer = Customer::create([
                    'user_id' => auth()->id(),
                    'first_name' => $bookingData['customer']['first_name'],
                    'last_name' => $bookingData['customer']['last_name'],
                    'email' => $bookingData['customer']['email'],
                    'phone' => $bookingData['customer']['phone'] ?? null,
                    'flight_number' => $bookingData['customer']['flight_number'] ?? null,
                    'driver_age' => $bookingData['customer']['driver_age'] ?? null,
                ]);
            }

            // Generate a unique booking reference
            $bookingReference = Str::random(32);

            $booking = Booking::create([
                'booking_number' => uniqid('BOOK-'),
                'booking_reference' => $bookingReference,
                'customer_id' => $customer->id,
                'vehicle_id' => $bookingData['vehicle_id'],
                'pickup_date' => $bookingData['pickup_date'],
                'return_date' => $bookingData['return_date'],
                'pickup_time' => $bookingData['pickup_time'],
                'return_time' => $bookingData['return_time'],
                'pickup_location' => $bookingData['pickup_location'],
                'return_location' => $bookingData['return_location'],
                'total_days' => $bookingData['total_days'],
                'base_price' => $bookingData['base_price'],
                'preferred_day' => $bookingData['preferred_day'] ?? null,
                'extra_charges' => $bookingData['extra_charges'] ?? 0,
                'tax_amount' => $bookingData['tax_amount'] ?? 0,
                'discount_amount' => $bookingData['discount_amount'] ?? 0,
                'total_amount' => $bookingData['total_amount'],
                'pending_amount' => $bookingData['pending_amount'],
                'amount_paid' => $bookingData['amount_paid'],
                'payment_status' => 'pending',
                'booking_status' => 'pending',
                'plan' => $bookingData['plan'] ?? null,
                'plan_price' => $bookingData['plan_price'] ?? null,
                'notes' => $bookingData['notes'] ?? null,
                'cancellation_reason' => null, // Initialize as null
            ]);

            // Save extras if any
            if (!empty($bookingData['extras'])) {
                $extrasData = [];
                foreach ($bookingData['extras'] as $extra) {
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

            

            // Create Stripe customer
            $stripeCustomer = \Stripe\Customer::create([
                'email' => $customer->email,
                'name' => "{$customer->first_name} {$customer->last_name}",
                'phone' => $customer->phone ?? null,
                'address' => [
                    'line1' => '123 Test Street',
                    'city' => 'Berlin',
                    'country' => 'DE',
                    'postal_code' => '10115',
                ],
            ]);

            // Create Checkout Session
            $session = Session::create([
                'customer' => $stripeCustomer->id,
                'payment_method_types' => ['card', 'bancontact', 'sofort', 'klarna'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Car Rental Booking',
                            'description' => "Booking for vehicle ID {$bookingData['vehicle_id']}",
                        ],
                        'unit_amount' => $bookingData['amount_paid'] * 100,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => url('/booking-success/details?session_id={CHECKOUT_SESSION_ID}&booking_id=' . $booking->id),
                'cancel_url' => url('/booking/cancel'),
                'metadata' => [
                    'booking_id' => $booking->id,
                ],
            ]);

            // Create initial payment record
            BookingPayment::create([
                'booking_id' => $booking->id,
                'payment_method' => 'stripe',
                'transaction_id' => $session->id,
                'amount' => $bookingData['amount_paid'],
                'payment_status' => 'pending',
            ]);

            return response()->json([
                'sessionId' => $session->id,
            ]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            \Log::error('Stripe Error:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }


    public function success(Request $request)
{
    try {
        Stripe::setApiKey(config('stripe.secret'));

        $sessionId = $request->query('session_id');
        $bookingId = $request->query('booking_id');

        if (!$sessionId || !$bookingId) {
            Log::error('Missing session_id or booking_id in PaymentController::success');
            return redirect()->route('booking.create')->with('error', 'Invalid payment session');
        }

        $booking = Booking::findOrFail($bookingId);
        $customer = Customer::findOrFail($booking->customer_id);
        $vehicle = Vehicle::findOrFail($booking->vehicle_id);
        $payment = BookingPayment::where('booking_id', $booking->id)
                    ->where('transaction_id', $sessionId)
                    ->first();
        $vendorProfile = VendorProfile::where('user_id', $vehicle->vendor_id)->first();

        $session = Session::retrieve($sessionId);
        $actualPaymentMethod = 'stripe'; // Default value

        if ($session->payment_intent) {
            try {
                $paymentIntent = PaymentIntent::retrieve($session->payment_intent);
                if ($paymentIntent->payment_method) {
                    $paymentMethod = PaymentMethod::retrieve($paymentIntent->payment_method);
                    $actualPaymentMethod = $paymentMethod->type;
                }

                // Check payment intent status for success
                if ($paymentIntent->status === 'succeeded') {
                    // Update booking status
                    $booking->update([
                        'payment_status' => 'completed',
                    ]);

                    // Update payment status
                    if ($payment) {
                        $payment->update([
                            'payment_status' => 'succeeded',
                            'payment_method' => $actualPaymentMethod,
                            'transaction_id' => $paymentIntent->id, // Use PaymentIntent ID
                            'amount' => $booking->amount_paid,
                        ]);
                    } else {
                        // This case should ideally not happen if 'charge' method created a pending payment
                        BookingPayment::create([
                            'booking_id' => $booking->id,
                            'payment_method' => $actualPaymentMethod,
                            'transaction_id' => $paymentIntent->id, // Use PaymentIntent ID
                            'amount' => $booking->amount_paid,
                            'payment_status' => 'succeeded',
                        ]);
                    }

                    // Send notifications
            $adminEmail = env('VITE_ADMIN_EMAIL', 'default@admin.com');
            $admin = User::where('email', $adminEmail)->first();
            if ($admin) {
                $admin->notify(new BookingCreatedAdminNotification($booking, $customer, $vehicle));
            }

            $vendor = User::find($vehicle->vendor_id);
            if ($vendor) {
                Notification::route('mail', $vendor->email)
                    ->notify(new BookingCreatedVendorNotification($booking, $customer, $vehicle, $vendor));
            }

            if ($vendorProfile && $vendorProfile->company_email) {
                Notification::route('mail', $vendorProfile->company_email)
                    ->notify(new BookingCreatedCompanyNotification($booking, $customer, $vehicle, $vendorProfile));
            }

            Notification::route('mail', $customer->email)
                ->notify(new BookingCreatedCustomerNotification($booking, $customer, $vehicle));

            // Clear session storage
            session()->forget(['pending_booking_id', 'driverInfo', 'rentalDates', 'selectionData']);
            
            // Add JavaScript to clear browser sessionStorage
            $clearSessionScript = "
                <script>
                    if(window.sessionStorage) {
                        window.sessionStorage.clear();
                        console.log('Session storage cleared');
                    }
                </script>
            ";

            return Inertia::render('Booking/Success', [
                'booking' => $booking,
                'vehicle' => $vehicle,
                'customer' => $customer,
                'payment' => $payment, 
                'vendorProfile' => $vendorProfile,
                'plan' => $booking->plan ? ['plan_type' => $booking->plan, 'price' => $booking->plan_price] : null,
                'clearSessionScript' => $clearSessionScript,
                'session_id' => $sessionId, // Explicitly pass session_id
                'payment_intent_id' => $paymentIntent->id, // Explicitly pass payment_intent_id
                'payment_status' => $paymentIntent->status,
            ]);
                } else {
                    // PaymentIntent status is not 'succeeded'
                    Log::warning('PaymentIntent status not succeeded in PaymentController::success', [
                        'session_id' => $sessionId,
                        'payment_intent_id' => $paymentIntent->id,
                        'payment_intent_status' => $paymentIntent->status,
                    ]);
                    return redirect()->route('booking.create')->with('error', 'Payment not completed. Status: ' . $paymentIntent->status);
                }
            } catch (\Stripe\Exception\ApiErrorException $e) {
                Log::error('Stripe API Error while retrieving PaymentIntent/PaymentMethod in PaymentController::success', [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                ]);
                return redirect()->route('booking.create')->with('error', 'Error processing payment details: ' . $e->getMessage());
            }
        } else {

            Log::error('PaymentIntent ID missing in session object in PaymentController::success', ['session_id' => $sessionId]);
            return redirect()->route('booking.create')->with('error', 'Payment processing error: Missing Payment Intent.');
        }
    } catch (\Exception $e) {
        Log::error('Error in PaymentController::success', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return redirect()->route('booking.create')->with('error', 'Error processing payment: ' . $e->getMessage());
    }
}
    
}
