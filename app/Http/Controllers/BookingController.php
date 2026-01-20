<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Plan;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VendorProfile;
use App\Notifications\Booking\BookingCancelledNotification;
use App\Notifications\Booking\BookingCreatedAdminNotification;
use App\Notifications\Booking\BookingCreatedCompanyNotification;
use App\Notifications\Booking\BookingCreatedCustomerNotification;
use App\Notifications\Booking\BookingCreatedVendorNotification;
use App\Events\NewMessage;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\BookingExtra;
use App\Models\Customer;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Customer as StripeCustomer;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
class BookingController extends Controller
{
    public function allowAccess(Request $request, $locale)
    {
        Session::put('can_access_booking_page', true);
        return response()->json(['message' => 'Access to booking page granted.'], 200);
    }
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
            'pending_amount' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'payment_method_id' => 'required|string',
            'plan' => 'nullable|string|max:255',
            'plan_price' => 'nullable|integer',
            // Extras validation
            'extras' => 'nullable|array',
            'extras.*.extra_type' => 'required|string|max:255',
            'extras.*.extra_name' => 'required|string|max:255',
            'extras.*.quantity' => 'required|integer|min:0',
            'extras.*.price' => 'required|numeric|min:0',
        ]);

        // Ensure extra_charges is set to 0 if not provided
        $validatedData['extra_charges'] = $validatedData['extra_charges'] ?? 0;
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
            'preferred_day' => $request->input('preferred_day'),
            'extra_charges' => $validatedData['extra_charges'],
            'tax_amount' => $request->input('tax_amount', 0),
            'discount_amount' => $request->input('discount_amount', 0),
            'total_amount' => $request->input('total_amount'),
            'pending_amount' => $request->input('pending_amount'),
            'amount_paid' => $request->input('amount_paid'),
            'payment_status' => 'pending',
            'booking_status' => 'pending',
            'plan' => $request->input('plan'),
            'plan_price' => $request->input('plan_price'),
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
                'amount' => $request->input('amount_paid') * 100, // Amount in cents
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
                'amount' => $request->input('amount_paid'),
                'payment_status' => $paymentIntent->status,
            ]);

            // Send notifications after successful payment
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

            // Create welcome chat message from vendor to customer
            try {
                if ($vendor && $customer->user_id) {
                    $welcomeMessage = Message::create([
                        'sender_id' => $vendor->id,
                        'receiver_id' => $customer->user_id,
                        'booking_id' => $booking->id,
                        'message' => 'Hello, Thank you for booking! Feel free to ask anything about your rental.',
                    ]);

                    $welcomeMessage->load(['sender', 'receiver']);
                    broadcast(new NewMessage($welcomeMessage))->toOthers();
                }
            } catch (\Exception $e) {
                Log::warning('BookingController::store - Failed to create welcome message', [
                    'error' => $e->getMessage(),
                    'booking_id' => $booking->id
                ]);
                // Don't fail the booking if welcome message fails
            }

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
        // Get payment intent or session ID from query parameters
        $paymentIntentId = $request->query('payment_intent');
        $sessionId = $request->query('session_id');

        if (!$paymentIntentId && !$sessionId) {
            return response()->json(['error' => 'Payment Intent ID or Session ID is required'], 400);
        }

        // Fetch payment details
        $payment = null;

        if ($paymentIntentId) {
            $payment = BookingPayment::where('transaction_id', $paymentIntentId)->first();
        } else if ($sessionId) {
            $payment = BookingPayment::where('transaction_id', $sessionId)->first();
        }

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        // Fetch booking details
        $booking = Booking::with(['extras', 'customer', 'vehicle.vendorProfile'])->find($payment->booking_id);

        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        $vehicleId = $booking->vehicle_id;
        $vehicle = Vehicle::with(['specifications', 'images', 'category', 'user', 'vendorPlans'])->find($vehicleId);
        $plan = Plan::where('plan_type', $booking->plan)->first();

        // Return booking and payment details in JSON format
        return response()->json([
            'booking' => $booking,
            'payment' => $payment,
            'vehicle' => $vehicle,
            'extras' => $booking->extras,
            'customer' => $booking->customer,
            'plan' => $plan,
            'vendorProfile' => $booking->vehicle->vendorProfile,
        ]);
    }

    // this si for fetching all the booking details in Pages > Vendor >  Bookings.vue
    public function getAllBookings()
    {
        $vendorId = auth()->id();

        // Get all bookings where the vehicle belongs to the current vendor
        $bookings = Booking::with(['customer', 'vehicle', 'payments'])
            ->whereHas('vehicle', function ($query) use ($vendorId) {
                // Filter bookings based on vehicle vendor
                $query->where('vendor_id', $vendorId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Vendor/Bookings', [
            'bookings' => $bookings
        ]);
    }
    public function getVendorPaymentHistory()
    {
        $vendorId = auth()->id();

        // Get ALL payments for statistics (without pagination)
        $allPayments = BookingPayment::with(['booking.customer', 'booking.vehicle'])
            ->join('bookings', 'booking_payments.booking_id', '=', 'bookings.id')
            ->join('vehicles', 'bookings.vehicle_id', '=', 'vehicles.id')
            ->where('vehicles.vendor_id', $vendorId)
            ->select('booking_payments.*')
            ->orderBy('booking_payments.created_at', 'desc')
            ->get(); // <-- Get ALL payments for statistics

        // Get paginated payments for the table
        $payments = BookingPayment::with(['booking.customer', 'booking.vehicle'])
            ->join('bookings', 'booking_payments.booking_id', '=', 'bookings.id')
            ->join('vehicles', 'bookings.vehicle_id', '=', 'vehicles.id')
            ->where('vehicles.vendor_id', $vendorId)
            ->select('booking_payments.*')
            ->orderBy('booking_payments.created_at', 'desc')
            ->paginate(6); // <-- Paginated for table

        return Inertia::render('Vendor/Payments/Index', [
            'payments' => $payments->items(),
            'allPayments' => $allPayments, // <-- Add ALL payments for statistics
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
            ]
        ]);
    }


    // Method for Pending Bookings
    public function getPendingBookings()
    {
        $userId = Auth::id();

        $customer = Customer::where('user_id', $userId)->first();

        $pendingBookings = $customer ?
            Booking::where('customer_id', $customer->id)
                ->where('booking_status', 'pending')
                ->with('vehicle.images', 'vehicle.category', 'payments', 'vehicle.vendorProfile')
                ->orderBy('created_at', 'desc')
                ->paginate(3) :
            collect();

        return Inertia::render('Profile/Bookings/PendingBookings', [
            'bookings' => $pendingBookings,
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
                ->with('vehicle.images', 'vehicle.category', 'payments', 'vehicle.vendorProfile', 'vehicle.benefits')
                ->orderBy('created_at', 'desc')
                ->paginate(3) :
            collect();

        return Inertia::render('Profile/Bookings/ConfirmedBookings', [
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
                ->with('vehicle.images', 'vehicle.category', 'payments', 'vehicle.vendorProfile', 'vehicle.vendorProfileData', 'review')
                ->orderBy('created_at', 'desc')
                ->paginate(3) :
            collect();

        return Inertia::render('Profile/Bookings/CompletedBookings', [
            'bookings' => $completedBookings,
        ]);
    }

    public function show(Request $request, $locale, $id)
    {
        \Log::info('Booking show accessed', [
            'locale' => $locale,
            'booking_id' => $id,
            'user_id' => Auth::id(),
            'user_role' => Auth::user()?->role,
        ]);

        // Find the booking
        $booking = Booking::findOrFail($id);

        // Authorize: Ensure the booking belongs to the current user
        $userId = Auth::id();
        $customer = Customer::where('user_id', $userId)->first();

        if (!$customer || $booking->customer_id !== $customer->id) {
            abort(403, 'Unauthorized action.');
        }

        // Eager load relationships
        $booking->load(['payments', 'extras', 'customer']);

        // Check if internal vehicle exists
        if ($booking->vehicle_id) {
            $booking->load(['vehicle.images', 'vehicle.category', 'vehicle.vendorProfileData']);
        }

        // Normalize Vehicle Data
        $vehicleData = null;
        $vendorProfile = null;

        if ($booking->vehicle) {
            // Internal Vehicle
            $vehicleData = $booking->vehicle;
            $vendorProfile = $booking->vehicle->vendorProfileData;

            // Load vendor user if vendorProfile exists
            if ($vendorProfile && !$vendorProfile->relationLoaded('user')) {
                $vendorProfile->load('user');
            }
        } else {
            // External Vehicle (Fallback)
            $vehicleData = [
                'id' => null, // No internal ID
                'brand' => explode(' ', $booking->vehicle_name)[0] ?? 'Vehicle',
                'model' => $booking->vehicle_name, // Full name as model fallback
                'vehicle_name' => $booking->vehicle_name,
                'transmission' => 'Manual', // Default or need to store this if available
                'fuel' => 'Petrol', // Default
                'seating_capacity' => 5, // Default
                'images' => $booking->vehicle_image ? [
                    ['image_url' => $booking->vehicle_image, 'image_type' => 'primary']
                ] : [],
                'category' => [
                    'name' => 'Standard' // Default
                ],
                // Add this to prevent map errors if latitude/longitude are missing on normalized object
                'latitude' => null,
                'longitude' => null
            ];
            // No vendor profile for external providers usually, or generic
        }

        // Prepare Payment Data
        $payment = $booking->payments->first(); // Assuming one main payment or take successful one

        // Prepare Plan Data if any
        $plan = null;
        if ($booking->plan) {
            $plan = [
                'plan_type' => $booking->plan,
                'price' => $booking->plan_price
            ];
        }

        return Inertia::render('Booking/BookingDetails', [
            'booking' => $booking,
            'vehicle' => $vehicleData,
            'payment' => $payment,
            'vendorProfile' => $vendorProfile,
            'plan' => $plan,
        ]);
    }

    /**
     * Generate and download PDF for booking details
     */
    public function downloadPDF(Request $request, $locale, $id)
    {
        $booking = Booking::findOrFail($id);

        // Authorize: Ensure the booking belongs to the current user
        $userId = Auth::id();
        $customer = Customer::where('user_id', $userId)->first();

        if (!$customer || $booking->customer_id !== $customer->id) {
            abort(403, 'Unauthorized action.');
        }

        // Eager load relationships
        $booking->load(['payments', 'extras', 'customer', 'vendorProfile']);

        // Check if internal vehicle exists
        if ($booking->vehicle_id) {
            $booking->load(['vehicle.images', 'vehicle.category', 'vehicle.vendorProfile']);
        }

        // Load vendor user if vendorProfile exists
        if ($booking->vendorProfile) {
            $booking->vendorProfile->load('user');
        }

        // Normalize Vehicle Data
        $vehicleData = null;
        $vendorProfile = null;

        if ($booking->vehicle) {
            $vehicleData = $booking->vehicle;
            // Try to get vendorProfile from vehicle first, then from booking
            $vendorProfile = $booking->vehicle->vendorProfile ?? $booking->vendorProfile;
        } else {
            // For external providers, still try to get vendorProfile from booking
            $vendorProfile = $booking->vendorProfile;

            $vehicleData = (object) [
                'brand' => explode(' ', $booking->vehicle_name)[0] ?? 'Vehicle',
                'model' => $booking->vehicle_name,
                'vehicle_name' => $booking->vehicle_name,
                'transmission' => 'Manual',
                'fuel' => 'Petrol',
                'seating_capacity' => 5,
                'images' => $booking->vehicle_image ? [
                    ['image_url' => $booking->vehicle_image, 'image_type' => 'primary']
                ] : [],
                'category' => ['name' => 'Standard'],
            ];
        }

        // Ensure vendorProfile has user loaded
        if ($vendorProfile && !$vendorProfile->relationLoaded('user')) {
            $vendorProfile->load('user');
        }

        // Get payment - try to get the successful one or just the first one
        $payment = $booking->payments->where('status', 'success')->first()
               ?? $booking->payments->where('status', 'succeeded')->first()
               ?? $booking->payments->first()
               ?? (object)[
                   'currency' => $booking->booking_currency ?? 'USD',
                   'amount' => $booking->total_amount,
                   'status' => $booking->booking_status ?? 'pending',
               ];

        // Generate PDF
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdfs.booking-receipt', [
            'booking' => $booking,
            'vehicle' => $vehicleData,
            'payment' => $payment,
            'vendorProfile' => $vendorProfile,
        ]);

        return $pdf->download("booking-{$booking->booking_number}.pdf");
    }


    public function getCustomerBookingsForMessages()
    {
        $userId = Auth::id();

        $customer = Customer::where('user_id', $userId)->first();

        if (!$customer) {
            return Inertia::render('Messages/Index', [
                'bookings' => []
            ]);
        }

        $bookings = Booking::where('customer_id', $customer->id)
            ->with([
                'vehicle.vendor',
                'vehicle.images',
                'vehicle.category',
                'vehicle.vendorProfile',
                'payments'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        // For each booking, get the count of unread messages
        foreach ($bookings as $booking) {
            $booking->unread_count = Message::where('booking_id', $booking->id)
                ->where('receiver_id', $userId)
                ->whereNull('read_at')
                ->count();
        }

        return Inertia::render('Messages/Index', [
            'bookings' => $bookings
        ]);
    }


    public function cancelBooking(Request $request)
    {
        // Validate the request
        $validatedData = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'cancellation_reason' => 'required|string|min:3|max:500',
        ]);

        // Get the booking
        $booking = Booking::findOrFail($validatedData['booking_id']);

        // Make sure the booking belongs to the authenticated user
        $userId = auth()->id();
        $customer = Customer::where('user_id', $userId)->first();

        if (!$customer || $booking->customer_id !== $customer->id) {
            return response()->json([
                'message' => 'Unauthorized action'
            ], 403);
        }

        // Check if booking is already cancelled
        if ($booking->booking_status === 'cancelled') {
            return response()->json([
                'message' => 'Booking is already cancelled'
            ], 400);
        }

        // Update booking status and save cancellation reason
        $booking->booking_status = 'cancelled';
        $booking->cancellation_reason = $validatedData['cancellation_reason'];

        $vehicle = Vehicle::find($booking->vehicle_id);

        // Send notifications
        $adminEmail = env('VITE_ADMIN_EMAIL', 'default@admin.com');
        $admin = User::where('email', $adminEmail)->first();
        if ($admin) {
            $admin->notify(new BookingCancelledNotification($booking, $customer, $vehicle, 'admin'));
        }

        // Notify Vendor
        $vendor = User::find($booking->vehicle->vendor_id);
        if ($vendor) {
            $vendor->notify(new BookingCancelledNotification($booking, $customer, $vehicle, 'vendor'));
        }

        // Notify Company
        $vendorProfile = VendorProfile::where('user_id', $booking->vehicle->vendor_id)->first();
        if ($vendorProfile && $vendorProfile->company_email) {
            $companyUser = User::where('email', $vendorProfile->company_email)->first();
            if ($companyUser) {
                $companyUser->notify(new BookingCancelledNotification($booking, $customer, $vehicle, 'company'));
            }
        }

        $booking->pickup_date = null;
        $booking->return_date = null;
        $booking->save();

        // Update vehicle status to available
        if ($vehicle) {
            $vehicle->update(['status' => 'available']);
        }

        return redirect()->back()->with('success', 'Booking cancelled successfully');
    }


    public function getCustomerPaymentHistory(Request $request)
    {
        $userId = Auth::id();
        $customer = Customer::where('user_id', $userId)->first();

        if (!$customer) {
            return Inertia::render('Profile/IssuedPayments', [
                'payments' => [],
                'pagination' => null,
            ]);
        }

        $payments = BookingPayment::with(['booking.vehicle.vendorProfile'])
            ->whereHas('booking', function ($query) use ($customer) {
                $query->where('customer_id', $customer->id);
            })
            ->orderBy('booking_payments.created_at', 'desc')
            ->paginate(8); // Or any other number for pagination

        return Inertia::render('Profile/IssuedPayments', [
            'payments' => $payments->items(),
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'from' => $payments->firstItem(),
                'to' => $payments->lastItem(),
            ]
        ]);
    }

    public function getCancelledBookings(Request $request)
    {
        $userId = Auth::id();
        $customer = Customer::where('user_id', $userId)->first();

        $cancelledBookings = $customer ?
            Booking::where('customer_id', $customer->id)
                ->where('booking_status', 'cancelled')
                ->with('vehicle.images', 'vehicle.category', 'payments', 'vehicle.vendorProfile')
                ->orderBy('created_at', 'desc')
                ->paginate(3) :
            collect();

        return Inertia::render('Profile/Bookings/CancelledBookings', [
            'bookings' => $cancelledBookings,
        ]);
    }

    /**
     * Get all customer bookings (unified page with client-side filtering)
     */
    public function getAllCustomerBookings(Request $request)
    {
        $userId = Auth::id();
        $customer = Customer::where('user_id', $userId)->first();

        $bookings = $customer ?
            Booking::where('customer_id', $customer->id)
                ->with('vehicle.images', 'vehicle.category', 'payments', 'vehicle.vendorProfile', 'extras')
                ->orderBy('created_at', 'desc')
                ->paginate(10) :
            collect();

        return Inertia::render('Profile/Bookings/AllBookings', [
            'bookings' => $bookings,
        ]);
    }
}
