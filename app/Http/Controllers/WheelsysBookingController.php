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
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\ApiConnectionException;

class WheelsysBookingController extends Controller
{
    protected $wheelsysService;

    public function __construct(WheelsysService $wheelsysService)
    {
        $this->wheelsysService = $wheelsysService;
    }

    public function create(Request $request, $locale, $groupCode)
    {
        // Get search parameters from the request query string.
        $searchParams = $request->all();

        // Basic validation to ensure essential params are present
        if (empty($searchParams['date_from']) || empty($searchParams['date_to'])) {
            Log::warning('Wheelsys booking page accessed without required date parameters.');
            return redirect()->route('search', $locale)
                ->with('error', 'Missing search parameters. Please start a new search.');
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

            // The correct, priced extras are already part of the converted vehicle object
            $availableExtras = $vehicle['extras'] ?? [];

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
     * Process the booking and create a Stripe Payment Intent.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_group_code' => 'required|string',
            'vehicle_group_name' => 'required|string',
            'pickup_date' => 'required|date_format:d/m/Y',
            'pickup_time' => 'required',
            'return_date' => 'required|date_format:d/m/Y|after_or_equal:pickup_date',
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
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $validated = $validator->validated();

            // Convert dates from d/m/Y to Y-m-d for Carbon and database
            $pickupDate = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['pickup_date']);
            $returnDate = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['return_date']);
            $durationDays = $pickupDate->diffInDays($returnDate) ?: 1;

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

            $baseRate = (float) ($vehicleRate['TotalRate'] / 100);
            $extrasTotal = $this->calculateExtrasTotal($validated['selected_extras'] ?? [], $quoteResponse);
            $taxesTotal = $this->calculateTaxesTotal($baseRate, $extrasTotal);
            $grandTotal = $baseRate + $extrasTotal + $taxesTotal;

            $booking = WheelsysBooking::create([
                'wheelsys_quote_id' => $quoteResponse['Id'] ?? null,
                'wheelsys_group_code' => $validated['vehicle_group_code'],
                'wheelsys_status' => 'REQ',
                'vehicle_group_name' => $validated['vehicle_group_name'],
                'vehicle_category' => $vehicleRate['Category'] ?? '',
                'vehicle_sipp_code' => $vehicleRate['Acriss'] ?? '',
                'vehicle_image_url' => $vehicleRate['ImageUrl'] ?? '',
                'vehicle_passengers' => $vehicleRate['Pax'] ?? 4,
                'vehicle_doors' => $vehicleRate['Doors'] ?? 4,
                'vehicle_bags' => $vehicleRate['Bags'] ?? 0,
                'vehicle_suitcases' => $vehicleRate['Suitcases'] ?? 0,
                'pickup_station_code' => $validated['pickup_station_code'],
                'pickup_station_name' => $request->input('pickup_station_name', 'Main Location'),
                'pickup_date' => $pickupDate,
                'pickup_time' => $validated['pickup_time'],
                'return_station_code' => $validated['return_station_code'],
                'return_station_name' => $request->input('return_station_name', 'Main Location'),
                'return_date' => $returnDate,
                'return_time' => $validated['return_time'],
                'rental_duration_days' => $durationDays,
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
                'base_rate_total' => $baseRate,
                'extras_total' => $extrasTotal,
                'taxes_total' => $taxesTotal,
                'grand_total' => $grandTotal,
                'currency' => 'USD',
                'selected_extras' => $validated['selected_extras'] ?? [],
                'available_extras' => $quoteResponse['Options'] ?? [],
                'booking_status' => 'pending',
                'customer_notes' => $validated['customer_notes'] ?? null,
                'api_quote_response' => $quoteResponse,
                'user_id' => Auth::id(),
                'affiliate_code' => $validated['affiliate_code'] ?? session('affiliate_data.code'),
                'affiliate_data' => session('affiliate_data'),
                'session_id' => session()->getId(),
            ]);

            Stripe::setApiKey(config('services.stripe.secret'));

            $checkoutSession = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $validated['vehicle_group_name'],
                            'images' => [$booking->vehicle_image_url],
                        ],
                        'unit_amount' => (int) ($grandTotal * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('wheelsys.booking.success', ['booking' => $booking->id, 'locale' => $request->get('locale', 'en')]),
                'cancel_url' => route('wheelsys.booking.create', ['groupCode' => $booking->wheelsys_group_code, 'locale' => $request->get('locale', 'en')]),
                'metadata' => [
                    'booking_id' => $booking->id,
                    'booking_type' => 'wheelsys',
                ]
            ]);

            $booking->stripe_payment_intent_id = $checkoutSession->payment_intent;
            $booking->save();

            return response()->json([
                'sessionId' => $checkoutSession->id,
            ]);

        } catch (ApiConnectionException $e) {
            Log::error('Stripe API connection error during Wheelsys booking', [
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Could not connect to the payment service. Please check your internet connection and try again.'], 503);
        } catch (\Exception $e) {
            Log::error('Wheelsys booking store error', [
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Unable to process booking. Please try again or contact support.'], 500);
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
            $customerDetails = $booking->customer_details;
            $reservationData = [
                'pickup_date' => $booking->pickup_date->format('d/m/Y'),
                'pickup_time' => $booking->pickup_time,
                'return_date' => $booking->return_date->format('d/m/Y'),
                'return_time' => $booking->return_time,
                'pickup_station_code' => $booking->pickup_station_code,
                'return_station_code' => $booking->return_station_code,
                'vehicle_group_code' => $booking->wheelsys_group_code,
                'wheelsys_quote_id' => $booking->wheelsys_quote_id,
                'customer_first_name' => $customerDetails['first_name'],
                'customer_last_name' => $customerDetails['last_name'],
                'customer_email' => $customerDetails['email'],
                'customer_phone' => $customerDetails['phone'],
            ];

            $wheelsysResponse = $this->wheelsysService->createReservation($reservationData);

            // Update booking with Wheelsys response
            $booking->update([
                'wheelsys_booking_ref' => $wheelsysResponse['irn'] ?? null,
                'wheelsys_ref_no' => $wheelsysResponse['refno'] ?? null,
                'wheelsys_status' => $wheelsysResponse['status'] ?? 'REQ',
                'api_reservation_response' => $wheelsysResponse,
            ]);

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
