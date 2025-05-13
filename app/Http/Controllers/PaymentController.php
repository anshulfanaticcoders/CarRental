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

        Log::info('PaymentController::charge Request:', $request->all());

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
            'bookingData.extras.*.extra_type' => 'required_if:bookingData.extras,string|max:255',
            'bookingData.extras.*.extra_name' => 'required_if:bookingData.extras,string|max:255',
            'bookingData.extras.*.quantity' => 'required_if:bookingData.extras,integer|min:0',
            'bookingData.extras.*.price' => 'required_if:bookingData.extras,numeric|min:0',
        ]);

        $bookingData = $request->input('bookingData');
        $bookingData['extra_charges'] = $bookingData['extra_charges'] ?? 0;

        // Handle customer
        $customerData = $bookingData['customer'];
        $customer = Customer::where('email', $customerData['email'])->first();
        if ($customer) {
            $customer->update([
                'first_name' => $customerData['first_name'],
                'last_name' => $customerData['last_name'],
                'phone' => $customerData['phone'],
                'flight_number' => $customerData['flight_number'],
                'driver_age' => $customerData['driver_age'],
            ]);
        } else {
            $customer = Customer::create([
                'user_id' => auth()->id(),
                'first_name' => $customerData['first_name'],
                'last_name' => $customerData['last_name'],
                'email' => $customerData['email'],
                'phone' => $customerData['phone'],
                'flight_number' => $customerData['flight_number'],
                'driver_age' => $customerData['driver_age'],
            ]);
        }

        // Get vehicle
        $vehicle = Vehicle::findOrFail($bookingData['vehicle_id']);

        // Create booking
        $booking = Booking::create([
            'booking_number' => 'BOOK-' . strtoupper(uniqid()),
            'booking_reference' => strtoupper(\Illuminate\Support\Str::random(32)),
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
            'preferred_day' => $bookingData['preferred_day'] ?? 'day',
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
        if (!empty($bookingData['extras'])) {
            foreach ($bookingData['extras'] as $extra) {
                if ($extra['quantity'] > 0) {
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
            if (!empty($extrasData)) {
                BookingExtra::insert($extrasData);
                Log::info('Extras Inserted:', ['count' => count($extrasData)]);
            }
        }

        // Create Stripe Checkout session
        try {
            $stripeCustomer = \Stripe\Customer::create([
                'email' => $customer->email,
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'phone' => $customer->phone,
            ]);

            $session = Session::create([
                'customer' => $stripeCustomer->id,
                'payment_method_types' => ['card', 'bancontact', 'klarna'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Car Rental Booking - ' . $booking->booking_number,
                            'description' => "Vehicle: {$vehicle->brand} (ID: {$vehicle->id})",
                        ],
                        'unit_amount' => (int) ($bookingData['amount_paid'] * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}&booking_id=' . $booking->id,
                'cancel_url' => route('booking.cancel'),
                'metadata' => [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                ],
            ]);

            // Store payment record
            BookingPayment::create([
                'booking_id' => $booking->id,
                'payment_method' => 'stripe',
                'transaction_id' => $session->id,
                'amount' => $bookingData['amount_paid'],
                'payment_status' => 'pending',
            ]);

            Log::info('Stripe Session Created:', [
                'session_id' => $session->id,
                'booking_id' => $booking->id,
            ]);

            return response()->json(['sessionId' => $session->id]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe Error in charge:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Payment processing failed: ' . $e->getMessage()], 400);
        }
    }

    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        $bookingId = $request->query('booking_id');

        Log::info('PaymentController::success', [
            'session_id' => $sessionId,
            'booking_id' => $bookingId,
        ]);

        if (!$sessionId || !$bookingId) {
            Log::error('Missing session_id or booking_id');
            return redirect()->route('booking.create')->with('error', 'Invalid payment session');
        }

        try {
            $booking = Booking::with(['customer', 'vehicle', 'payments'])->findOrFail($bookingId);
            $vendorProfile = VendorProfile::where('user_id', $booking->vehicle->vendor_id)->first();

            // Clear session storage
            $clearSessionScript = "<script>window.sessionStorage.clear(); console.log('Session storage cleared');</script>";

            return Inertia::render('Booking/Success', [
                'booking' => $booking,
                'vehicle' => $booking->vehicle,
                'customer' => $booking->customer,
                'payment' => $booking->payments->first(),
                'vendorProfile' => $vendorProfile,
                'plan' => $booking->plan ? ['plan_type' => $booking->plan, 'price' => $booking->plan_price] : null,
                'clearSessionScript' => $clearSessionScript,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in PaymentController::success:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('booking.create')->with('error', 'Failed to load booking details');
        }
    }

    public function webhook(Request $request)
    {
        Stripe::setApiKey(config('stripe.secret'));

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Webhook Signature Verification Failed:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Webhook signature verification failed'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $bookingId = $session->metadata->booking_id ?? null;

            Log::info('Webhook: checkout.session.completed', [
                'session_id' => $session->id,
                'booking_id' => $bookingId,
                'payment_status' => $session->payment_status,
            ]);

            if (!$bookingId) {
                Log::error('Webhook: Missing booking_id in session metadata');
                return response()->json(['error' => 'Missing booking_id'], 400);
            }

            try {
                $booking = Booking::with(['customer', 'vehicle'])->findOrFail($bookingId);
                $customer = $booking->customer;
                $vehicle = $booking->vehicle;

                if ($session->payment_status === 'paid') {
                    // Update booking
                    $booking->update([
                        'payment_status' => 'completed',
                        'booking_status' => 'confirmed',
                    ]);

                    // Update or create payment record
                    $payment = BookingPayment::where('booking_id', $booking->id)
                        ->where('transaction_id', $session->id)
                        ->first();

                    if ($payment) {
                        $payment->update([
                            'payment_status' => 'succeeded',
                            'transaction_id' => $session->payment_intent ?? $session->id,
                            'amount' => $booking->amount_paid,
                        ]);
                    } else {
                        $payment = BookingPayment::create([
                            'booking_id' => $booking->id,
                            'payment_method' => 'stripe',
                            'transaction_id' => $session->payment_intent ?? $session->id,
                            'amount' => $booking->amount_paid,
                            'payment_status' => 'succeeded',
                        ]);
                    }

                    Log::info('Webhook: Booking and Payment Updated:', [
                        'booking_id' => $booking->id,
                        'payment_id' => $payment->id,
                    ]);

                    // Send notifications
                    try {
                        $adminEmail = env('VITE_ADMIN_EMAIL', 'default@admin.com');
                        $admin = User::where('email', $adminEmail)->first();
                        if ($admin) {
                            $admin->notify(new BookingCreatedAdminNotification($booking, $customer, $vehicle));
                            Log::info('Webhook: Admin Notification Sent', ['email' => $adminEmail]);
                        }

                        $vendor = User::find($vehicle->vendor_id);
                        if ($vendor) {
                            Notification::route('mail', $vendor->email)->notify(
                                new BookingCreatedVendorNotification($booking, $customer, $vehicle, $vendor)
                            );
                            Log::info('Webhook: Vendor Notification Sent', ['email' => $vendor->email]);
                        }

                        $vendorProfile = VendorProfile::where('user_id', $vehicle->vendor_id)->first();
                        if ($vendorProfile && $vendorProfile->company_email) {
                            Notification::route('mail', $vendorProfile->company_email)->notify(
                                new BookingCreatedCompanyNotification($booking, $customer, $vehicle, $vendorProfile)
                            );
                            Log::info('Webhook: Company Notification Sent', ['email' => $vendorProfile->company_email]);
                        }

                        Notification::route('mail', $customer->email)->notify(
                            new BookingCreatedCustomerNotification($booking, $customer, $vehicle)
                        );
                        Log::info('Webhook: Customer Notification Sent', ['email' => $customer->email]);
                    } catch (\Exception $e) {
                        Log::error('Webhook: Notification Error:', ['message' => $e->getMessage()]);
                    }

                    return response()->json(['status' => 'success']);
                } else {
                    Log::warning('Webhook: Payment not completed', [
                        'session_id' => $session->id,
                        'payment_status' => $session->payment_status,
                    ]);
                    return response()->json(['status' => 'payment_not_completed']);
                }
            } catch (\Exception $e) {
                Log::error('Webhook: Error processing booking:', [
                    'booking_id' => $bookingId,
                    'message' => $e->getMessage(),
                ]);
                return response()->json(['error' => 'Failed to process webhook'], 400);
            }
        }

        return response()->json(['status' => 'ignored']);
    }

    public function cancel(Request $request)
    {
        Log::info('PaymentController::cancel Called');
        return redirect()->route('booking.create')->with('error', 'Payment was cancelled');
    }
}