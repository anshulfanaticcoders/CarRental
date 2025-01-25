<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\BookingExtra;
use App\Models\Customer;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Customer as StripeCustomer;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
class BookingController extends Controller
{
    public function create()
    {
        return Inertia::render('Booking/Create');
    }

    public function store(Request $request)
    {
        // print_r($request->all());
        // die();
        $validatedData = $request->validate([
            // Customer validation
            'customer.first_name' => 'required|string|max:255',
            'customer.last_name' => 'required|string|max:255',
            'customer.email' => 'required|email|max:255',
            'customer.phone' => 'nullable|string|max:20',
            'customer.flight_number' => 'nullable|string|max:50', // Added flight number validation
            'customer.driver_age' => 'nullable|integer|min:18|max:100',
            'vehicle_id' => 'required|exists:vehicles,id',

            // Booking validation
            'pickup_date' => 'required|date',
            'return_date' => 'required|date|after:pickup_date',
            'pickup_time' => 'required|string|max:10', // Added pickup time validation
            'return_time' => 'required|string|max:10', 
            'pickup_location' => 'required|string|max:255',
            'return_location' => 'required|string|max:255',
            'total_days' => 'required|integer|min:1',
            'base_price' => 'required|numeric|min:0',
            'extra_charges' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'payment_method_id' => 'required|string',
            'plan' => 'required|string|max:255',
            // Extras validation
            'extras' => 'nullable|array',
            'extras.*.extra_type' => 'required|string|max:255',
            'extras.*.extra_name' => 'required|string|max:255',
            'extras.*.quantity' => 'required|integer|min:0',
            'extras.*.price' => 'required|numeric|min:0',
        ]);

        // Customer logic (unchanged from your implementation)
        $customer = Customer::where('email', $request->input('customer.email'))->first();

        if ($customer) {
            // Update existing customer (except email)
            $customer->update([
                'first_name' => $request->input('customer.first_name'),
                'last_name' => $request->input('customer.last_name'),
                'phone' => $request->input('customer.phone'),
                'flight_number' => $request->input('customer.flight_number'),
            ]);
        } else {
            // Create new customer
            $customer = Customer::create([
                'user_id' => auth()->id(), // Assuming the user is authenticated
                'first_name' => $request->input('customer.first_name'),
                'last_name' => $request->input('customer.last_name'),
                'email' => $request->input('customer.email'),
                'phone' => $request->input('customer.phone'),
                'flight_number' => $request->input('customer.flight_number'),
                'driver_age' => $request->input('customer.driver_age'),
            ]);
        }


        // Vehicle logic (unchanged)
        $vehicle = Vehicle::findOrFail($validatedData['vehicle_id']);

        // Booking creation
        $booking = Booking::create([
            'booking_number' => uniqid('BOOK-'),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'pickup_date' => $request->input('pickup_date'),
            'return_date' => $request->input('return_date'),
            'pickup_time' => $request->input('pickup_time'),
            'return_time' => $request->input('return_time'), 
            'pickup_location' => $request->input('pickup_location'),
            'return_location' => $request->input('return_location'),
            'total_days' => $request->input('total_days'),
            'base_price' => $request->input('base_price'),
            'extra_charges' => $request->input('extra_charges', 0),
            'tax_amount' => $request->input('tax_amount', 0),
            'total_amount' => $request->input('total_amount'),
            'payment_status' => 'pending',
            'booking_status' => 'pending',
            'plan' => $request->input('plan'),
        ]);

        $extrasData = [];
        foreach ($validatedData['extras'] as $extra) {
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

        // Insert all extras at once
        if (!empty($extrasData)) {
            BookingExtra::insert($extrasData);
        }


        // Initialize Stripe
        Stripe::setApiKey(config('stripe.secret'));

        // Check if the customer already has a Stripe customer ID
        if ($customer->stripe_customer_id) {
            $stripeCustomerId = $customer->stripe_customer_id;
        } else {
            // Create a new Stripe customer
            $stripeCustomer = StripeCustomer::create([
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'payment_method' => $request->input('payment_method_id'),
                'invoice_settings' => [
                    'default_payment_method' => $request->input('payment_method_id'),
                ],
            ]);

            // Save the Stripe customer ID
            $customer->update(['stripe_customer_id' => $stripeCustomer->id]);
            $stripeCustomerId = $stripeCustomer->id;
        }

        // Create a payment intent with Stripe
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $request->input('total_amount') * 100, // Amount in cents
                'currency' => 'USD',
                'customer' => $stripeCustomerId,
                'payment_method' => $request->input('payment_method_id'),
                'off_session' => true, // Allow payments even if the user is not present
                'confirm' => true, // Automatically confirm the payment
                'description' => 'Car Rental Booking - Booking #' . $booking->booking_number,
                'metadata' => [
                    'booking_id' => $booking->id,
                ],
            ]);

            // Save payment intent
            BookingPayment::create([
                'booking_id' => $booking->id,
                'payment_method' => 'Stripe',
                'transaction_id' => $paymentIntent->id,
                'amount' => $request->input('total_amount'),
                'payment_status' => $paymentIntent->status,
            ]);


            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
            ]);
        } catch (\Stripe\Exception\CardException $e) {
            // Handle payment failure
            return response()->json([
                'error' => $e->getMessage(),
                'booking' => $booking, // Return booking details for further processing
            ], 400);
        }
    }

    public function getBookingDetails(Request $request)
    {

        // Get payment intent from query parameters
        $paymentIntentId = $request->query('payment_intent');
        // print_r($paymentIntentId);

        if (!$paymentIntentId) {
            return response()->json(['error' => 'Payment Intent ID is required'], 400);
        }

        // Fetch payment details
        $payment = BookingPayment::where('transaction_id', $paymentIntentId)->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        // Fetch booking details
        $booking = Booking::with(['extras', 'customer'])->find($payment->booking_id);
        $vehicleId = $booking->vehicle_id;
        $vehicle = Vehicle::with(['specifications', 'images', 'category', 'user'])->find($vehicleId);

        // Return booking and payment details in JSON format
        return response()->json([
            'booking' => $booking,
            'payment' => $payment,
            'vehicle' => $vehicle,
            'extras' => $booking->extras,
            'customer' => $booking->customer,
        ]);
    }

    // this si for fetching all the booking details in Pages > Vendor >  Bookings.vue
    public function getAllBookings()
    {
        // Get all bookings with related data
        $bookings = Booking::with(['customer', 'vehicle', 'payments'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Vendor/Bookings', [
            'bookings' => $bookings
        ]);
    }

// public function getCustomerBookingData()
// {
//     // Get the current authenticated user's ID
//     $userId = auth()->id();

//     // Find the customer associated with the current user
//     $customer = Customer::where('user_id', $userId)->first();

//     if (!$customer) {
//         return response()->json([
//             'message' => 'No customer found for the current user.',
//         ], 404);
//     }

//     // Retrieve all bookings for the customer
//     $bookings = Booking::where('customer_id', $customer->id)->get();
  
//     // Optionally, you can load related data (e.g., extras, payments)
//     $bookings->load('customer','vehicle.images', 'payments');

//        return Inertia::render('Profile/PendingBookings', [
//         'bookings' => $bookings
//     ]);
// }
// Method for Pending Bookings
public function getPendingBookings()
{
    $userId = Auth::id();

    $customer = Customer::where('user_id', $userId)->first();

    $pendingBookings = $customer ? 
        Booking::where('customer_id', $customer->id)
            ->where('booking_status', 'pending')
            ->with('vehicle.images', 'payments')
            ->orderBy('created_at', 'desc')
            ->get() : 
        collect();

    return Inertia::render('Profile/PendingBookings', [
        'bookings' => $pendingBookings
    ]);
}

// Method for Confirmed Bookings
public function getConfirmedBookings()
{
    $userId = Auth::id();

    $customer = Customer::where('user_id', $userId)->first();

    $confirmedBookings = $customer ? 
        Booking::where('customer_id', $customer->id)
            ->where('booking_status', 'confirmed')
            ->with('vehicle.images', 'payments')
            ->orderBy('created_at', 'desc')
            ->get() : 
        collect();

    return Inertia::render('Profile/ConfirmedBookings', [
        'bookings' => $confirmedBookings
    ]);
}

public function getCompletedBookings()
{
    $userId = Auth::id();

    $customer = Customer::where('user_id', $userId)->first();

    $completedBookings = $customer ? 
        Booking::where('customer_id', $customer->id)
            ->where('booking_status', 'completed')
            ->with('vehicle.images', 'payments')
            ->orderBy('created_at', 'desc')
            ->get() : 
        collect();

    return Inertia::render('Profile/CompletedBookings', [
        'bookings' => $completedBookings
    ]);
}
}