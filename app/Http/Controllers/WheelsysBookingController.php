<?php

namespace App\Http\Controllers;

use App\Models\WheelsysBooking;
use App\Services\WheelsysService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class WheelsysBookingController extends Controller
{
    protected $wheelsysService;

    public function __construct(WheelsysService $wheelsysService)
    {
        $this->wheelsysService = $wheelsysService;
    }

    public function create(Request $request, $locale, $groupCode)
    {
        // Get search parameters from session or request
        $searchParams = Session::get('wheelsysBookingForm', $request->all());

        // If still no search params, create default ones
        if (!$searchParams || !isset($searchParams['date_from'])) {
            $searchParams = [
                'pickup_station' => 'MAIN',
                'return_station' => 'MAIN',
                'date_from' => date('d/m/Y', strtotime('+1 day')), // Tomorrow
                'time_from' => '12:00',
                'date_to' => date('d/m/Y', strtotime('+4 days')), // 4 days from now
                'time_to' => '12:00',
            ];

            Log::info('Using default search parameters for Wheelsys booking', [
                'group_code' => $groupCode,
                'search_params' => $searchParams,
                'today' => date('d/m/Y'),
                'pickup_date_valid' => strtotime($searchParams['date_from']) > time()
            ]);
        }

        // Validate that dates are in the future
        $pickupTimestamp = strtotime($searchParams['date_from']);
        $todayTimestamp = strtotime(date('d/m/Y'));

        if ($pickupTimestamp <= $todayTimestamp) {
            Log::warning('Pickup date is in the past or today, adjusting to future dates', [
                'original_pickup_date' => $searchParams['date_from'],
                'today' => date('d/m/Y'),
                'pickup_timestamp' => $pickupTimestamp,
                'today_timestamp' => $todayTimestamp
            ]);

            // Adjust to future dates
            $searchParams['date_from'] = date('d/m/Y', strtotime('+1 day'));
            $searchParams['date_to'] = date('d/m/Y', strtotime('+4 days'));

            Log::info('Adjusted search parameters to future dates', [
                'new_pickup_date' => $searchParams['date_from'],
                'new_return_date' => $searchParams['date_to']
            ]);
        }

        try {
            Log::info('Wheelsys booking: Starting vehicle lookup', [
                'group_code' => $groupCode,
                'search_params' => $searchParams,
                'pickup_date' => $searchParams['date_from'],
                'return_date' => $searchParams['date_to']
            ]);

            // Get vehicle quote from Wheelsys API
            $response = $this->wheelsysService->getVehicles(
                $searchParams['pickup_station'] ?? 'MAIN',
                $searchParams['return_station'] ?? 'MAIN',
                $searchParams['date_from'],
                $searchParams['time_from'] ?? '12:00',
                $searchParams['date_to'],
                $searchParams['time_to'] ?? '12:00'
            );

            Log::info('Wheelsys booking: API response received', [
                'requested_group_code' => $groupCode,
                'response_structure' => array_keys($response),
                'has_rates' => isset($response['Rates']),
                'rates_count' => isset($response['Rates']) ? count($response['Rates']) : 0,
                'available_groups' => isset($response['Rates']) ? collect($response['Rates'])->pluck('GroupCode')->toArray() : [],
                'rate_details' => isset($response['Rates']) ? collect($response['Rates'])->map(function($rate) {
                    return [
                        'GroupCode' => $rate['GroupCode'],
                        'Category' => $rate['Category'],
                        'TotalRate' => $rate['TotalRate'],
                        'Availability' => $rate['Availability']
                    ];
                })->toArray() : []
            ]);

            // Check for Rates structure (actual API response)
            if (!isset($response['Rates'])) {
                throw new \Exception('No vehicles available for selected dates - API response missing Rates structure. Response: ' . json_encode($response));
            }

            // Find the specific vehicle group using 'GroupCode' field
            $vehicleRate = collect($response['Rates'])->firstWhere('GroupCode', $groupCode);

            if (!$vehicleRate) {
                $availableGroups = collect($response['Rates'])->pluck('GroupCode')->toArray();
                $rateDetails = collect($response['Rates'])->map(function($rate) {
                    return $rate['GroupCode'] . ' (' . $rate['Category'] . ')';
                })->toArray();

                Log::error('Vehicle group not found', [
                    'requested_group' => $groupCode,
                    'available_groups' => $availableGroups,
                    'rate_details' => $rateDetails,
                    'search_dates' => [
                        'pickup' => $searchParams['date_from'],
                        'return' => $searchParams['date_to']
                    ]
                ]);

                throw new \Exception("Vehicle group {$groupCode} not available for selected dates. Available groups: " . implode(', ', $rateDetails));
            }

            // Convert to standard vehicle format
            $vehicle = $this->wheelsysService->convertToStandardVehicle(
                $vehicleRate,
                $searchParams['pickup_station'] ?? 'MAIN'
            );

            Log::info('Vehicle converted to standard format', [
                'vehicle_is_null' => is_null($vehicle),
                'vehicle_data' => $vehicle,
                'original_rate' => $vehicleRate
            ]);

            // Get available options/extras
            $optionsResponse = $this->wheelsysService->getOptions();
            $availableExtras = $optionsResponse['Options'] ?? [];

            // Get station details
            $stationsResponse = $this->wheelsysService->getStations();
            $stations = $stationsResponse['Stations'] ?? [];

            $pickupStation = collect($stations)->firstWhere('Code', $searchParams['pickup_station'] ?? 'MAIN');
            $returnStation = collect($stations)->firstWhere('Code', $searchParams['return_station'] ?? 'MAIN');

            Log::info('Final data being sent to booking page', [
                'vehicle_price_per_day' => $vehicle['price_per_day'] ?? 'MISSING',
                'vehicle_brand' => $vehicle['brand'] ?? 'MISSING',
                'vehicle_model' => $vehicle['model'] ?? 'MISSING',
                'search_params' => $searchParams
            ]);

            return Inertia::render('WheelsysCar/Booking', [
                'vehicle' => $vehicle,
                'searchParams' => $searchParams,
                'availableExtras' => $availableExtras,
                'pickupStation' => $pickupStation,
                'returnStation' => $returnStation,
                'quoteResponse' => $response,
                'user' => Auth::user(),
                'locale' => $locale,
            ]);

        } catch (\Exception $e) {
            Log::error('Wheelsys booking creation error', [
                'group_code' => $groupCode,
                'search_params' => $searchParams,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // For debugging: Show the actual error instead of redirecting
            if (config('app.debug')) {
                throw new \Exception('Wheelsys API Error: ' . $e->getMessage() . '. Params: ' . json_encode($searchParams));
            }

            return redirect()->route('search', $locale)
                ->with('error', 'Unable to load booking details. Please try again.');
        }
    }

    /**
     * Process the booking form and redirect to payment
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_group_code' => 'required|string',
            'vehicle_group_name' => 'required|string',
            'pickup_date' => 'required|date',
            'pickup_time' => 'required',
            'return_date' => 'required|date|after_or_equal:pickup_date',
            'return_time' => 'required',
            'pickup_station_code' => 'required|string',
            'return_station_code' => 'required|string',
            'customer_first_name' => 'required|string|max:100',
            'customer_last_name' => 'required|string|max:100',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_age' => 'required|integer|min:18|max:99',
            'customer_address' => 'nullable|string|max:500',
            'customer_driver_licence' => 'nullable|string|max:50',
            'selected_extras' => 'nullable|array',
            'customer_notes' => 'nullable|string|max:1000',
            'affiliate_code' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Get validated data
            $validated = $validator->validated();

            // Calculate rental duration
            $pickupDate = \Carbon\Carbon::parse($validated['pickup_date']);
            $returnDate = \Carbon\Carbon::parse($validated['return_date']);
            $durationDays = $pickupDate->diffInDays($returnDate) ?: 1;

            // Get quote from Wheelsys API
            $quoteResponse = $this->wheelsysService->getVehicles(
                $validated['pickup_station_code'],
                $validated['return_station_code'],
                $validated['pickup_date'],
                $validated['pickup_time'],
                $validated['return_date'],
                $validated['return_time']
            );

            if (!isset($quoteResponse['Rates'])) {
                throw new \Exception('Unable to get pricing from Wheelsys API');
            }

            $vehicleRate = collect($quoteResponse['Rates'])->firstWhere('GroupCode', $validated['vehicle_group_code']);
            if (!$vehicleRate) {
                throw new \Exception('Vehicle no longer available for selected dates');
            }

            // Calculate pricing
            $baseRate = (float) ($vehicleRate['TotalRate'] / 100); // Convert from cents
            $extrasTotal = $this->calculateExtrasTotal($validated['selected_extras'] ?? [], $quoteResponse);
            $taxesTotal = $this->calculateTaxesTotal($baseRate, $extrasTotal);
            $grandTotal = $baseRate + $extrasTotal + $taxesTotal;

            // Create booking record
            $booking = WheelsysBooking::create([
                // Wheelsys specific fields
                'wheelsys_quote_id' => $quoteResponse['Id'] ?? null,
                'wheelsys_group_code' => $validated['vehicle_group_code'],
                'wheelsys_status' => 'REQ', // Requested status

                // Vehicle information
                'vehicle_group_name' => $validated['vehicle_group_name'],
                'vehicle_category' => $vehicleRate['Category'] ?? '',
                'vehicle_sipp_code' => $vehicleRate['Acriss'] ?? '',
                'vehicle_image_url' => $vehicleRate['ImageUrl'] ?? '',
                'vehicle_passengers' => $vehicleRate['Pax'] ?? 4,
                'vehicle_doors' => $vehicleRate['Doors'] ?? 4,
                'vehicle_bags' => $vehicleRate['Bags'] ?? 0,
                'vehicle_suitcases' => $vehicleRate['Suitcases'] ?? 0,

                // Rental details
                'pickup_station_code' => $validated['pickup_station_code'],
                'pickup_station_name' => $request->input('pickup_station_name', 'Main Location'),
                'pickup_date' => $validated['pickup_date'],
                'pickup_time' => $validated['pickup_time'],
                'return_station_code' => $validated['return_station_code'],
                'return_station_name' => $request->input('return_station_name', 'Main Location'),
                'return_date' => $validated['return_date'],
                'return_time' => $validated['return_time'],
                'rental_duration_days' => $durationDays,

                // Customer details
                'customer_details' => [
                    'first_name' => $validated['customer_first_name'],
                    'last_name' => $validated['customer_last_name'],
                    'email' => $validated['customer_email'],
                    'phone' => $validated['customer_phone'],
                    'age' => $validated['customer_age'],
                    'address' => $validated['customer_address'],
                    'driver_licence' => $validated['customer_driver_licence'],
                ],
                'customer_age' => $validated['customer_age'],
                'customer_driver_licence' => $validated['customer_driver_licence'],
                'customer_address' => $validated['customer_address'],

                // Pricing
                'base_rate_total' => $baseRate,
                'extras_total' => $extrasTotal,
                'taxes_total' => $taxesTotal,
                'grand_total' => $grandTotal,
                'currency' => 'USD',

                // Extras
                'selected_extras' => $validated['selected_extras'] ?? [],
                'available_extras' => $quoteResponse['Options'] ?? [],

                // Booking management
                'booking_status' => 'pending',
                'customer_notes' => $validated['customer_notes'] ?? null,

                // API data
                'api_quote_response' => $quoteResponse,

                // System fields
                'user_id' => Auth::id(),
                'affiliate_code' => $validated['affiliate_code'] ?? session('affiliate_data.code'),
                'affiliate_data' => session('affiliate_data'),
                'session_id' => session()->getId(),
            ]);

            // Store booking ID in session for payment processing
            session(['wheelsys_booking_id' => $booking->id]);

            Log::info('Wheelsys booking created', [
                'booking_id' => $booking->id,
                'group_code' => $booking->wheelsys_group_code,
                'total' => $booking->grand_total,
            ]);

            // Redirect to payment
            return redirect()->route('wheelsys.booking.payment', $booking->id);

        } catch (\Exception $e) {
            Log::error('Wheelsys booking store error', [
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', 'Unable to create booking. Please try again or contact support.')
                ->withInput();
        }
    }

    /**
     * Show payment page
     */
    public function payment(WheelsysBooking $booking)
    {
        // Verify booking belongs to current user or is guest
        if (Auth::check() && $booking->user_id !== Auth::id()) {
            abort(403);
        }

        // Check if booking is still pending payment
        if ($booking->booking_status !== 'pending' && $booking->stripe_payment_status !== 'pending') {
            return redirect()->route('wheelsys.booking.show', $booking->id)
                ->with('info', 'This booking has already been processed.');
        }

        return Inertia::render('WheelsysCar/Payment', [
            'booking' => $booking->load('user'),
            'stripe_key' => config('services.stripe.key'),
        ]);
    }

    /**
     * Process payment via Stripe
     */
    public function processPayment(Request $request, WheelsysBooking $booking)
    {
        $validator = Validator::make($request->all(), [
            'payment_method_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid payment method'], 400);
        }

        try {
            // Set Stripe key
            Stripe::setApiKey(config('services.stripe.secret'));

            // Create or confirm PaymentIntent
            if ($booking->stripe_payment_intent_id) {
                // Update existing PaymentIntent
                $paymentIntent = PaymentIntent::retrieve($booking->stripe_payment_intent_id);
                $paymentIntent = PaymentIntent::update(
                    $booking->stripe_payment_intent_id,
                    ['payment_method' => $request->payment_method_id]
                );
            } else {
                // Create new PaymentIntent
                $paymentIntent = PaymentIntent::create([
                    'amount' => (int) ($booking->grand_total * 100), // Convert to cents
                    'currency' => strtolower($booking->currency),
                    'payment_method' => $request->payment_method_id,
                    'confirmation_method' => 'manual',
                    'confirm' => true,
                    'metadata' => [
                        'booking_id' => $booking->id,
                        'booking_type' => 'wheelsys',
                        'customer_email' => $booking->customer_email,
                    ],
                ]);

                $booking->stripe_payment_intent_id = $paymentIntent->id;
                $booking->save();
            }

            // Handle payment result
            if ($paymentIntent->status === 'succeeded') {
                return $this->handleSuccessfulPayment($booking, $paymentIntent);
            } elseif ($paymentIntent->status === 'requires_action') {
                return response()->json([
                    'requires_action' => true,
                    'payment_intent_client_secret' => $paymentIntent->client_secret,
                ]);
            } else {
                throw new \Exception('Payment failed: ' . $paymentIntent->status);
            }

        } catch (\Exception $e) {
            Log::error('Wheelsys payment processing error', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Payment processing failed'], 500);
        }
    }

    /**
     * Handle successful payment
     */
    protected function handleSuccessfulPayment(WheelsysBooking $booking, $paymentIntent)
    {
        try {
            // Update booking payment status
            $booking->update([
                'stripe_payment_status' => 'succeeded',
                'amount_paid' => $booking->grand_total,
                'paid_at' => now(),
            ]);

            // Create reservation with Wheelsys API
            $this->createWheelsysReservation($booking);

            // Update booking status
            $booking->updateBookingStatus();

            Log::info('Wheelsys payment successful', [
                'booking_id' => $booking->id,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $booking->grand_total,
            ]);

            return response()->json([
                'success' => true,
                'redirect_url' => route('wheelsys.booking.success', $booking->id),
            ]);

        } catch (\Exception $e) {
            Log::error('Wheelsys post-payment processing error', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Payment successful but booking confirmation failed'], 500);
        }
    }

    /**
     * Create reservation with Wheelsys API
     */
    protected function createWheelsysReservation(WheelsysBooking $booking)
    {
        $customer = $booking->customer_details;

        $reservationData = [
            'agent' => config('services.wheelsys.agent_code'),
            'date_from' => $booking->pickup_date->format('Y-m-d'),
            'time_from' => $booking->pickup_time->format('H:i'),
            'date_to' => $booking->return_date->format('Y-m-d'),
            'time_to' => $booking->return_time->format('H:i'),
            'pickup_station' => $booking->pickup_station_code,
            'return_station' => $booking->return_station_code,
            'group' => $booking->wheelsys_group_code,
            'quoteref' => $booking->wheelsys_quote_id,
            'first_name' => $customer['first_name'],
            'last_name' => $customer['last_name'],
            'email' => $customer['email'],
            'phone' => $customer['phone'],
        ];

        // Call Wheelsys new-res endpoint
        $url = config('services.wheelsys.base_url') . "new-res_" . config('services.wheelsys.link_code') . ".html";

        $response = \Http::timeout(30)->post($url, $reservationData);

        if ($response->successful()) {
            $responseData = $response->json();

            // Update booking with Wheelsys response
            $booking->update([
                'wheelsys_booking_ref' => $responseData['irn'] ?? null,
                'wheelsys_ref_no' => $responseData['refno'] ?? null,
                'wheelsys_status' => $responseData['status'] ?? 'REQ',
                'api_reservation_response' => $responseData,
            ]);

            return $responseData;
        } else {
            throw new \Exception('Wheelsys reservation failed: ' . $response->body());
        }
    }

    /**
     * Show booking success page
     */
    public function success(WheelsysBooking $booking)
    {
        if ($booking->user_id && $booking->user_id !== Auth::id()) {
            abort(403);
        }

        return Inertia::render('WheelsysCar/BookingSuccess', [
            'booking' => $booking->load('user'),
        ]);
    }

    /**
     * Show booking details
     */
    public function show(WheelsysBooking $booking)
    {
        if ($booking->user_id && $booking->user_id !== Auth::id()) {
            abort(403);
        }

        return Inertia::render('WheelsysCar/BookingShow', [
            'booking' => $booking->load('user'),
        ]);
    }

    /**
     * Calculate total cost of selected extras
     */
    protected function calculateExtrasTotal(array $selectedExtras, array $quoteResponse): float
    {
        if (empty($selectedExtras) || !isset($quoteResponse['Options'])) {
            return 0;
        }

        $total = 0;
        $availableOptions = collect($quoteResponse['Options']);

        foreach ($selectedExtras as $selectedExtra) {
            $option = $availableOptions->firstWhere('Code', $selectedExtra['code']);
            if ($option && isset($option['Rate'])) {
                $total += (float) ($option['Rate'] / 100); // Convert from cents
            }
        }

        return $total;
    }

    /**
     * Calculate taxes total
     */
    protected function calculateTaxesTotal(float $baseRate, float $extrasTotal): float
    {
        // Simple tax calculation - adjust based on actual Wheelsys tax structure
        $subtotal = $baseRate + $extrasTotal;
        $taxRate = 0.065; // 6.5% based on station info
        return $subtotal * $taxRate;
    }
}