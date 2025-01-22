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

class BookingController extends Controller
{
    public function create()
    {
        return Inertia::render('Booking/Create');
    }

    public function store(Request $request)
    {
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
            'pickup_location' => 'required|string|max:255',
            'return_location' => 'required|string|max:255',
            'total_days' => 'required|integer|min:1',
            'base_price' => 'required|numeric|min:0',
            'extra_charges' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'payment_method_id' => 'required|string',

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
            'pickup_location' => $request->input('pickup_location'),
            'return_location' => $request->input('return_location'),
            'total_days' => $request->input('total_days'),
            'base_price' => $request->input('base_price'),
            'extra_charges' => $request->input('extra_charges', 0),
            'tax_amount' => $request->input('tax_amount', 0),
            'total_amount' => $request->input('total_amount'),
            'payment_status' => 'pending',
            'booking_status' => 'pending',
        ]);

        // Store extras
        // if (!empty($validatedData['extras'])) {
        //     foreach ($validatedData['extras'] as $extra) {
        //         if ($extra['quantity'] > 0) {
        //             \Log::info('Saving extra:', $extra); 
        //             BookingExtra::create([
        //                 'booking_id' => $booking->id,
        //                 'extra_type' => $extra['extra_type'],
        //                 'extra_name' => $extra['extra_name'],
        //                 'quantity' => $extra['quantity'],
        //                 'price' => $extra['price'],
        //             ]);
        //         }
        //     }
        // }

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

    // public function success(Request $request)
    // {
    //     $paymentIntentId = $request->input('payment_intent');
    //     $payment = BookingPayment::where('transaction_id', $paymentIntentId)->first();

    //     if ($payment) {
    //         $payment->update([
    //             'payment_status' => 'successful',
    //             'payment_date' => now(),
    //         ]);

    //         $payment->booking->update([
    //             'payment_status' => 'paid',
    //             'booking_status' => 'confirmed',
    //         ]);
    //     }

    //     return Inertia::render('Booking/Success');
    // }
    // public function success(Request $request)
    // {
    //     $paymentIntentId = $request->input('payment_intent');
    //     $payment = BookingPayment::where('transaction_id', $paymentIntentId)->first();

    //     if (!$payment) {
    //         return redirect()->route('booking-success')->with('error', 'Payment not found.');
    //     }

    //     // Update payment status and booking status
    //     $payment->update([
    //         'payment_status' => 'successful',
    //         'payment_date' => now(),
    //     ]);

    //     $booking = $payment->booking; // Lazy load the related booking

    //     if ($booking) {
    //         $booking->update([
    //             'payment_status' => 'paid',
    //             'booking_status' => 'confirmed',
    //         ]);
    //     }

    //     return redirect()->route('booking-success', [
    //         'booking_number' => $booking ? $booking->booking_number : 'N/A',
    //     ]);
    // }


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
    public function getCustomerBookingData()
    {
        // Get all bookings with related data, including vehicle images
        $bookings = Booking::with([
            'customer',
            'vehicle.images',
            'payments'
        ])
        ->orderBy('created_at', 'desc')
        ->get();
    
        return Inertia::render('Profile/PendingBookings', [
            'bookings' => $bookings
        ]);
    }
    
}