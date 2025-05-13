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

    // Log the entire request
    \Log::info('PaymentController::charge Request:', $request->all());

    // Validate the request
    $validatedData = $request->validate([
        'bookingData.customer' => 'required|array',
        'bookingData.customer.first_name' => 'required|string|max:255',
        'bookingData.customer.last_name' => 'required|string|max:255',
        'bookingData.customer.email' => 'required|email|max:255',
        'bookingData.customer.phone' => 'nullable|string|max:20',
        'bookingData.customer.flight_number' => 'nullable|string|max:50',
        'bookingData.customer.driver_age' => 'nullable|integer|min:18|max:100',
        'bookingData.vehicle_id' => 'required|exists:vehicles,id',
        'bookingData.pickup_date' => 'required|date',
        'bookingData.return_date' => 'required|date|after:bookingData.pickup_date',
        'bookingData.pickup_time' => 'required|string|max:10',
        'bookingData.return_time' => 'required|string|max:10',
        'bookingData.pickup_location' => 'required|string|max:255',
        'bookingData.return_location' => 'required|string|max:255',
        'bookingData.total_days' => 'required|integer|min:1',
        'bookingData.base_price' => 'required|numeric|min:0',
        'bookingData.extra_charges' => 'nullable|numeric|min:0',
        'bookingData.total_amount' => 'required|numeric|min:0',
        'bookingData.pending_amount' => 'required|numeric|min:0',
        'bookingData.amount_paid' => 'required|numeric|min:0',
        'bookingData.plan' => 'nullable|string|max:255',
        'bookingData.plan_price' => 'nullable|numeric|min:0',
        'bookingData.extras' => 'nullable|array',
        'bookingData.extras.*.extra_type' => 'required|string|max:255',
        'bookingData.extras.*.extra_name' => 'required|string|max:255',
        'bookingData.extras.*.quantity' => 'required|integer|min:0',
        'bookingData.extras.*.price' => 'required|numeric|min:0',
    ]);

    // Extract bookingData
    $bookingData = $request->input('bookingData');
    \Log::info('Validated Booking Data:', $bookingData);

    // Ensure extra_charges fallback
    $bookingData['extra_charges'] = $bookingData['extra_charges'] ?? 0;

    // Extract customer data
    $customerData = $bookingData['customer'];

    // Find or create customer
    $customer = Customer::where('email', $customerData['email'])->first();
    if ($customer) {
        $customer->update([
            'first_name' => $customerData['first_name'],
            'last_name' => $customerData['last_name'],
            'phone' => $customerData['phone'] ?? null,
            'flight_number' => $customerData['flight_number'] ?? null,
            'driver_age' => $customerData['driver_age'] ?? null,
        ]);
    } else {
        $customer = Customer::create([
            'user_id' => auth()->id(),
            'first_name' => $customerData['first_name'],
            'last_name' => $customerData['last_name'],
            'email' => $customerData['email'],
            'phone' => $customerData['phone'] ?? null,
            'flight_number' => $customerData['flight_number'] ?? null,
            'driver_age' => $customerData['driver_age'] ?? null,
        ]);
    }

    // Vehicle logic
    $vehicle = Vehicle::findOrFail($bookingData['vehicle_id']);

    // Create booking
    $booking = Booking::create([
        'booking_number' => uniqid('BOOK-'),
        'booking_reference' => Str::random(32),
        'customer_id' => $customer->id,
        'vehicle_id' => $vehicle->id,
        'pickup_date' => $bookingData['pickup_date'],
        'return_date' => $bookingData['return_date'],
        'pickup_time' => $bookingData['pickup_time'],
        'return_time' => $bookingData['return_time'],
        'pickup_location' => $bookingData['pickup_location'],
        'return_location' => $bookingData['return_location'],
        'total_days' => $bookingData['total_days'],
        'base_price' => $bookingData['base_price'],
        'preferred_day' => $bookingData['preferred_day'],
        'extra_charges' => $bookingData['extra_charges'],
        'tax_amount' => $bookingData['tax_amount'] ?? 0,
        'discount_amount' => $bookingData['discount_amount'] ?? 0,
        'total_amount' => $bookingData['total_amount'],
        'pending_amount' => $bookingData['pending_amount'],
        'amount_paid' => $bookingData['amount_paid'],
        'payment_status' => 'pending',
        'booking_status' => 'pending',
        'plan' => $bookingData['plan'],
        'plan_price' => $bookingData['plan_price'],
        'notes' => $bookingData['notes'] ?? null,
    ]);

    // Process extras
    $extrasData = [];
    if (isset($bookingData['extras']) && is_array($bookingData['extras'])) {
        foreach ($bookingData['extras'] as $extra) {
            if (isset($extra['quantity']) && $extra['quantity'] > 0) {
                $extrasData[] = [
                    'booking_id' => $booking->id,
                    'extra_type' => $extra['extra_type'],
                    'extra_name' => $extra['extra_name'],
                    'quantity' => (int) $extra['quantity'],
                    'price' => (float) $extra['price'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
    }

    // Log extras data
    \Log::info('Extras Data to Insert:', $extrasData);

    // Insert extras
    try {
        if (!empty($extrasData)) {
            BookingExtra::insert($extrasData);
            \Log::info('Extras Inserted Successfully', ['count' => count($extrasData)]);
        } else {
            \Log::warning('No Extras to Insert');
        }
    } catch (\Exception $e) {
        \Log::error('Failed to Insert Extras:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        // Continue with the payment even if extras fail to save
    }

    // Proceed with Stripe payment
    try {
        $stripeCustomer = \Stripe\Customer::create([
            'email' => $customer->email,
            'name' => $customer->first_name . ' ' . $customer->last_name,
            'phone' => $customer->phone,
            'address' => [
                'line1' => '123 Test Street',
                'city' => 'Berlin',
                'country' => 'DE',
                'postal_code' => '10115',
            ],
        ]);

        $session = Session::create([
            'customer' => $stripeCustomer->id,
            'payment_method_types' => ['card', 'bancontact', 'sofort', 'klarna'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Car Rental Booking',
                        'description' => "Booking for vehicle ID {$vehicle->id}",
                    ],
                    'unit_amount' => (int) ($bookingData['amount_paid'] * 100),
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
        return response()->json([
            'error' => $e->getMessage(),
            'booking' => $booking,
        ], 400);
    }
}


public function success(Request $request)
{
    try {
        Stripe::setApiKey(config('stripe.secret'));

        $sessionId = $request->query('session_id');
        $bookingId = $request->query('booking_id');

        \Log::info('PaymentController::success Called', [
            'session_id' => $sessionId,
            'booking_id' => $bookingId,
        ]);

        if (!$sessionId || !$bookingId) {
            \Log::error('Missing session_id or booking_id in PaymentController::success');
            return redirect()->route('booking.create')->with('error', 'Invalid payment session');
        }

        $booking = Booking::findOrFail($bookingId);
        $customer = Customer::findOrFail($booking->customer_id);
        $vehicle = Vehicle::findOrFail($booking->vehicle_id);
        $vendorProfile = VendorProfile::where('user_id', $vehicle->vendor_id)->first();

        $session = Session::retrieve($sessionId);
        \Log::info('Stripe Session Data', [
            'session_id' => $session->id,
            'payment_status' => $session->payment_status,
            'payment_intent' => $session->payment_intent,
        ]);

        // Find the payment record
        $payment = BookingPayment::where('booking_id', $booking->id)
            ->where('transaction_id', $session->id)
            ->first();

        if (!$payment) {
            \Log::warning('Payment Record Not Found with Session ID, Attempting Fallback', [
                'booking_id' => $bookingId,
                'session_id' => $sessionId,
            ]);
            // Fallback: Try finding by booking_id only
            $payment = BookingPayment::where('booking_id', $booking->id)->first();

            if (!$payment) {
                \Log::error('Payment Record Still Not Found', [
                    'booking_id' => $bookingId,
                ]);
                // Create a new payment record if none exists
                $payment = BookingPayment::create([
                    'booking_id' => $booking->id,
                    'payment_method' => 'stripe',
                    'transaction_id' => $session->payment_intent ?? $session->id,
                    'amount' => $booking->amount_paid,
                    'payment_status' => 'pending',
                ]);
                \Log::info('Created New Payment Record', [
                    'payment_id' => $payment->id,
                ]);
            }
        }

        if ($session->payment_status === 'paid') {
            // Update booking status
            $booking->update([
                'payment_status' => 'completed',
                'booking_status' => 'confirmed',
            ]);
            \Log::info('Booking Status Updated', [
                'booking_id' => $booking->id,
                'payment_status' => $booking->payment_status,
            ]);

            // Update payment status
            $payment->update([
                'payment_status' => 'succeeded',
                'transaction_id' => $session->payment_intent ?? $session->id,
                'amount' => $booking->amount_paid,
            ]);
            \Log::info('Payment Status Updated', [
                'payment_id' => $payment->id,
                'payment_status' => $payment->payment_status,
                'transaction_id' => $payment->transaction_id,
            ]);

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
            ]);
        } else {
            \Log::warning('Payment Not Completed', [
                'session_id' => $sessionId,
                'payment_status' => $session->payment_status,
            ]);
            return redirect()->route('booking.create')->with('error', 'Payment not completed');
        }
    } catch (\Exception $e) {
        \Log::error('Error in PaymentController::success', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return redirect()->route('booking.create')->with('error', 'Error processing payment: ' . $e->getMessage());
    }
}
    
}