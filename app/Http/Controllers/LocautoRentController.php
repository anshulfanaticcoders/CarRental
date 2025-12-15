<?php

namespace App\Http\Controllers;

use App\Services\LocautoRentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Helpers\SchemaBuilder;

class LocautoRentController extends Controller
{
    protected $locautoRentService;

    public function __construct(LocautoRentService $locautoRentService)
    {
        $this->locautoRentService = $locautoRentService;
    }

    /**
     * Show vehicles for a specific location
     */
    public function showVehicles(Request $request, $locale)
    {
        $validated = $request->validate([
            'pickup_location_id' => 'required|string',
            'dropoff_location_id' => 'nullable|string',
            'date_from' => 'required|date',
            'start_time' => 'required|string',
            'date_to' => 'required|date|after:date_from',
            'end_time' => 'required|string',
            'age' => 'nullable|integer|min:18|max:99',
            'currency' => 'nullable|string|max:3',
        ]);

        try {
            Log::info('Fetching Locauto vehicles', [
                'pickup_location_id' => $validated['pickup_location_id'],
                'date_from' => $validated['date_from'],
                'date_to' => $validated['date_to'],
            ]);

            $response = $this->locautoRentService->getVehicles(
                $validated['pickup_location_id'],
                $validated['date_from'],
                $validated['start_time'],
                $validated['date_to'],
                $validated['end_time'],
                $validated['age'] ?? 35,
                []
            );

            if (!$response) {
                Log::error('No response from Locauto API');
                return Inertia::render('LocautoRentCars', [
                    'vehicles' => [],
                    'error' => 'Unable to fetch vehicles. Please try again.',
                    'filters' => $validated,
                ]);
            }

            $vehicles = $this->locautoRentService->parseVehicleResponse($response);

            Log::info('Parsed ' . count($vehicles) . ' Locauto vehicles');

            // Generate schema for SEO
            $vehicleSchema = SchemaBuilder::vehicleList($vehicles, 'Locauto Rent Vehicles', $validated);

            return Inertia::render('LocautoRentCars', [
                'vehicles' => $vehicles,
                'filters' => $validated,
                'schema' => $vehicleSchema,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in LocautoRentController@showVehicles: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return Inertia::render('LocautoRentCars', [
                'vehicles' => [],
                'error' => 'An error occurred while fetching vehicles.',
                'filters' => $validated,
            ]);
        }
    }

    /**
     * Show single vehicle details
     */
    public function showVehicle(Request $request, $locale, $id)
    {
        // Accept parameters as sent by the frontend
        $validated = $request->validate([
            'location_id' => 'required|string',
            'dropoff_location_id' => 'nullable|string',
            'start_date' => 'required|date',
            'start_time' => 'required|string',
            'end_date' => 'required|date|after:start_date',
            'end_time' => 'required|string',
            'age' => 'nullable|integer|min:18|max:99',
            'currency' => 'nullable|string|max:3',
        ]);

        try {
            // Since Locauto doesn't have a single vehicle API, we'll get all vehicles and filter by ID
            $response = $this->locautoRentService->getVehicles(
                $validated['location_id'],
                $validated['start_date'],
                $validated['start_time'],
                $validated['end_date'],
                $validated['end_time'],
                $validated['age'] ?? 35,
                []
            );

            if (!$response) {
                Log::error('No response from Locauto API');
                return Inertia::render('LocautoRentSingle', [
                    'vehicle' => null,
                    'error' => 'Unable to fetch vehicle details. Please try again.',
                ]);
            }

            $allVehicles = $this->locautoRentService->parseVehicleResponse($response);

            // Find the specific vehicle by ID (SIPP code)
            $vehicle = collect($allVehicles)->firstWhere('id', $id);

            if (!$vehicle) {
                Log::warning('Vehicle not found: ' . $id, ['available_ids' => collect($allVehicles)->pluck('id')->toArray()]);
                return Inertia::render('LocautoRentSingle', [
                    'vehicle' => null,
                    'error' => 'Vehicle not found.',
                ]);
            }

            // Add additional details that might be useful
            $vehicle['pickup_location_id'] = $validated['location_id'];
            $vehicle['dropoff_location_id'] = $validated['dropoff_location_id'] ?? $validated['location_id'];
            $vehicle['date_from'] = $validated['start_date'];
            $vehicle['date_to'] = $validated['end_date'];
            $vehicle['start_time'] = $validated['start_time'];
            $vehicle['end_time'] = $validated['end_time'];
            $vehicle['age'] = $validated['age'] ?? 35;

            // Look up full location details from predefined locations
            $allLocations = $this->locautoRentService->parseLocationResponse();
            $pickupLocation = collect($allLocations)->firstWhere('provider_location_id', $validated['location_id']);
            $dropoffLocation = !empty($validated['dropoff_location_id'])
                ? collect($allLocations)->firstWhere('provider_location_id', $validated['dropoff_location_id'])
                : $pickupLocation;

            // Format location objects for Vue component
            $location = $pickupLocation ? [
                'id' => $pickupLocation['provider_location_id'],
                'name' => $pickupLocation['label'] ?? $pickupLocation['location'] ?? '',
                'address_city' => $pickupLocation['city'] ?? '',
                'latitude' => $pickupLocation['latitude'] ?? null,
                'longitude' => $pickupLocation['longitude'] ?? null,
            ] : null;

            $dropoffLocationData = $dropoffLocation ? [
                'id' => $dropoffLocation['provider_location_id'],
                'name' => $dropoffLocation['label'] ?? $dropoffLocation['location'] ?? '',
                'address_city' => $dropoffLocation['city'] ?? '',
                'latitude' => $dropoffLocation['latitude'] ?? null,
                'longitude' => $dropoffLocation['longitude'] ?? null,
            ] : $location;

            Log::info('Showing Locauto vehicle details', ['vehicle_id' => $id, 'location' => $location]);

            // Categorize extras into protection plans and optional extras
            $protectionCodes = ['136', '147', '145', '140', '146', '6', '43']; // Protection/insurance related
            $optionalExtraCodes = ['19', '78', '137', '138', '54', '55', '77', '89']; // Equipment/add-ons

            $allExtras = $vehicle['extras'] ?? [];

            $protectionPlans = array_filter($allExtras, function ($extra) use ($protectionCodes) {
                return in_array($extra['code'], $protectionCodes) && $extra['amount'] > 0;
            });

            $optionalExtras = array_filter($allExtras, function ($extra) use ($optionalExtraCodes) {
                return in_array($extra['code'], $optionalExtraCodes) && $extra['amount'] > 0;
            });

            return Inertia::render('LocautoRentSingle', [
                'vehicle' => $vehicle,
                'location' => $location,
                'dropoffLocation' => $dropoffLocationData,
                'protectionPlans' => array_values($protectionPlans),
                'optionalExtras' => array_values($optionalExtras),
                'filters' => $validated,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in LocautoRentController@showVehicle: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return Inertia::render('LocautoRentSingle', [
                'vehicle' => null,
                'error' => 'An error occurred while fetching vehicle details.',
            ]);
        }
    }

    /**
     * Process booking
     */
    public function processBooking(Request $request)
    {
        $validated = $request->validate([
            'pickup_location_code' => 'required|string',
            'dropoff_location_code' => 'nullable|string',
            'pickup_date' => 'required|date',
            'pickup_time' => 'required|string',
            'return_date' => 'required|date|after:pickup_date',
            'return_time' => 'required|string',
            'vehicle_code' => 'required|string',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'age' => 'required|integer|min:18|max:99',
            'selected_extras' => 'nullable|array',
            'terms_accepted' => 'required|accepted',
        ]);

        try {
            Log::info('Processing Locauto booking', $validated);

            // Since Locauto uses Pay on Arrival, we don't process payment
            // Just create the reservation

            $bookingData = [
                'pickup_location_code' => $validated['pickup_location_code'],
                'dropoff_location_code' => $validated['dropoff_location_code'] ?? $validated['pickup_location_code'],
                'pickup_date' => $validated['pickup_date'],
                'pickup_time' => $validated['pickup_time'],
                'return_date' => $validated['return_date'],
                'return_time' => $validated['return_time'],
                'vehicle_code' => $validated['vehicle_code'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'age' => $validated['age'],
                'extras' => $validated['selected_extras'] ?? [],
            ];

            $response = $this->locautoRentService->makeReservation($bookingData);

            if ($response) {
                Log::info('Locauto reservation successful');

                // Parse the response to get confirmation details
                // Since we don't have a specific parser for reservation response yet,
                // we'll create a basic confirmation object

                $confirmation = [
                    'confirmation_number' => uniqid('LOC'),
                    'pickup_location' => $validated['pickup_location_code'],
                    'dropoff_location' => $validated['dropoff_location_code'] ?? $validated['pickup_location_code'],
                    'pickup_date' => $validated['pickup_date'],
                    'return_date' => $validated['return_date'],
                    'vehicle_details' => [
                        'code' => $validated['vehicle_code'],
                    ],
                    'customer_details' => [
                        'first_name' => $validated['first_name'],
                        'last_name' => $validated['last_name'],
                        'email' => $validated['email'],
                        'phone' => $validated['phone'],
                        'age' => $validated['age'],
                    ],
                    'payment_type' => 'Pay on Arrival',
                    'status' => 'confirmed',
                ];

                return response()->json([
                    'success' => true,
                    'message' => 'Booking confirmed! Please pay on arrival at the pickup location.',
                    'confirmation' => $confirmation,
                    'redirect_url' => route('locauto-rent-booking-success', ['confirmation' => $confirmation['confirmation_number'], 'locale' => app()->getLocale()]),
                ]);
            } else {
                Log::error('Failed to create Locauto reservation');
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create reservation. Please try again.',
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error in LocautoRentController@processBooking: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your booking.',
            ], 500);
        }
    }

    /**
     * Show booking success page
     */
    public function bookingSuccess(Request $request, $locale, $confirmation)
    {
        return Inertia::render('LocautoRentBookingSuccess', [
            'confirmation' => $confirmation,
        ]);
    }

    /**
     * API endpoint to get vehicle availability
     */
    public function getVehicles(Request $request)
    {
        $validated = $request->validate([
            'pickup_location_id' => 'required|string',
            'date_from' => 'required|date',
            'start_time' => 'required|string',
            'date_to' => 'required|date|after:date_from',
            'end_time' => 'required|string',
            'age' => 'nullable|integer|min:18|max:99',
        ]);

        try {
            $response = $this->locautoRentService->getVehicles(
                $validated['pickup_location_id'],
                $validated['date_from'],
                $validated['start_time'],
                $validated['date_to'],
                $validated['end_time'],
                $validated['age'] ?? 35,
                []
            );

            if (!$response) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to fetch vehicles',
                ], 400);
            }

            $vehicles = $this->locautoRentService->parseVehicleResponse($response);

            return response()->json([
                'success' => true,
                'vehicles' => $vehicles,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in LocautoRentController@getVehicles: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching vehicles: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show booking page for a specific vehicle
     */
    public function showBookingPage(Request $request, $locale, $id)
    {
        $validated = $request->validate([
            'location_id' => 'required|string',
            'dropoff_location_id' => 'nullable|string',
            'start_date' => 'required|date',
            'start_time' => 'required|string',
            'end_date' => 'required|date|after:start_date',
            'end_time' => 'required|string',
            'age' => 'nullable|integer|min:18|max:99',
            'currency' => 'nullable|string|max:3',
        ]);

        try {
            // Get vehicle details using showVehicle method logic
            $response = $this->locautoRentService->getVehicles(
                $validated['location_id'],
                $validated['start_date'],
                $validated['start_time'],
                $validated['end_date'],
                $validated['end_time'],
                $validated['age'] ?? 35,
                []
            );

            if (!$response) {
                Log::error('No response from Locauto API');
                return Inertia::render('LocautoRentBooking', [
                    'vehicle' => null,
                    'error' => 'Unable to fetch vehicle details. Please try again.',
                ]);
            }

            $allVehicles = $this->locautoRentService->parseVehicleResponse($response);
            $vehicle = collect($allVehicles)->firstWhere('id', $id);

            if (!$vehicle) {
                Log::warning('Vehicle not found: ' . $id);
                return Inertia::render('LocautoRentBooking', [
                    'vehicle' => null,
                    'error' => 'Vehicle not found.',
                ]);
            }

            // Look up full location details from predefined locations
            $allLocations = $this->locautoRentService->parseLocationResponse();
            $pickupLocation = collect($allLocations)->firstWhere('provider_location_id', $validated['location_id']);
            $dropoffLocation = !empty($validated['dropoff_location_id'])
                ? collect($allLocations)->firstWhere('provider_location_id', $validated['dropoff_location_id'])
                : $pickupLocation;

            // Format location objects for Vue component
            $location = $pickupLocation ? [
                'id' => $pickupLocation['provider_location_id'],
                'name' => $pickupLocation['label'] ?? $pickupLocation['location'] ?? '',
                'address_city' => $pickupLocation['city'] ?? '',
                'latitude' => $pickupLocation['latitude'] ?? null,
                'longitude' => $pickupLocation['longitude'] ?? null,
            ] : null;

            // Add booking details
            $vehicle['pickup_location_id'] = $validated['location_id'];
            $vehicle['dropoff_location_id'] = $validated['dropoff_location_id'] ?? $validated['location_id'];
            $vehicle['date_from'] = $validated['start_date'];
            $vehicle['date_to'] = $validated['end_date'];
            $vehicle['start_time'] = $validated['start_time'];
            $vehicle['end_time'] = $validated['end_time'];
            $vehicle['age'] = $validated['age'] ?? 35;

            // Categorize extras
            $protectionCodes = ['136', '147', '145', '140', '146', '6', '43'];
            $optionalExtraCodes = ['19', '78', '137', '138', '54', '55', '77', '89'];
            $allExtras = $vehicle['extras'] ?? [];

            $protectionPlans = array_values(array_filter($allExtras, function ($extra) use ($protectionCodes) {
                return in_array($extra['code'], $protectionCodes) && $extra['amount'] > 0;
            }));

            $optionalExtras = array_values(array_filter($allExtras, function ($extra) use ($optionalExtraCodes) {
                return in_array($extra['code'], $optionalExtraCodes) && $extra['amount'] > 0;
            }));

            return Inertia::render('LocautoRentBooking', [
                'vehicle' => $vehicle,
                'location' => $location,
                'protectionPlans' => $protectionPlans,
                'optionalExtras' => $optionalExtras,
                'filters' => $validated,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in LocautoRentController@showBookingPage: ' . $e->getMessage());
            return Inertia::render('LocautoRentBooking', [
                'vehicle' => null,
                'error' => 'An error occurred while fetching vehicle details.',
            ]);
        }
    }

    /**
     * Check vehicle availability
     */
    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'pickup_location_id' => 'required|string',
            'dropoff_location_id' => 'nullable|string',
            'date_from' => 'required|date',
            'start_time' => 'required|string',
            'date_to' => 'required|date|after:date_from',
            'end_time' => 'required|string',
            'age' => 'nullable|integer|min:18|max:99',
            'vehicle_code' => 'required|string',
        ]);

        try {
            $response = $this->locautoRentService->getVehicles(
                $validated['pickup_location_id'],
                $validated['date_from'],
                $validated['start_time'],
                $validated['date_to'],
                $validated['end_time'],
                $validated['age'] ?? 35,
                []
            );

            if (!$response) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to check availability',
                ], 400);
            }

            $vehicles = $this->locautoRentService->parseVehicleResponse($response);
            $vehicle = collect($vehicles)->firstWhere('id', $validated['vehicle_code']);

            if (!$vehicle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehicle not available for selected dates',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'available' => true,
                'vehicle' => $vehicle,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in LocautoRentController@checkAvailability: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error checking availability: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process Stripe payment for Locauto booking
     */
    public function processStripePayment(Request $request)
    {
        Log::info('LocautoRentController::processStripePayment - Starting payment processing', $request->all());

        try {
            $validated = $request->validate([
                'customer.first_name' => 'required|string|max:100',
                'customer.last_name' => 'required|string|max:100',
                'customer.email' => 'required|email|max:255',
                'customer.phone' => 'required|string|max:20',
                'customer.driver_age' => 'required|integer|min:18|max:99',
                'pickup_location_code' => 'required|string',
                'dropoff_location_code' => 'nullable|string',
                'pickup_date' => 'required|date',
                'pickup_time' => 'required|string',
                'return_date' => 'required|date|after:pickup_date',
                'return_time' => 'required|string',
                'vehicle_code' => 'required|string',
                'vehicle_model' => 'nullable|string',
                'sipp_code' => 'nullable|string',
                'selected_protection' => 'nullable|string',
                'selected_extras' => 'nullable|array',
                'grand_total' => 'required|numeric|min:0.01',
                'currency' => 'required|string|max:3',
            ]);

            \Stripe\Stripe::setApiKey(config('stripe.secret'));

            // Store booking data in cache for retrieval after payment
            $bookingToken = \Illuminate\Support\Str::random(32);
            $bookingData = [
                'customer' => $validated['customer'],
                'pickup_location_code' => $validated['pickup_location_code'],
                'dropoff_location_code' => $validated['dropoff_location_code'] ?? $validated['pickup_location_code'],
                'pickup_date' => $validated['pickup_date'],
                'pickup_time' => $validated['pickup_time'],
                'return_date' => $validated['return_date'],
                'return_time' => $validated['return_time'],
                'vehicle_code' => $validated['vehicle_code'],
                'vehicle_model' => $validated['vehicle_model'] ?? '',
                'sipp_code' => $validated['sipp_code'] ?? '',
                'selected_protection' => $validated['selected_protection'],
                'selected_extras' => $validated['selected_extras'] ?? [],
                'grand_total' => $validated['grand_total'],
                'currency' => $validated['currency'],
            ];

            cache()->put("locauto_booking_{$bookingToken}", $bookingData, now()->addMinutes(30));

            Log::info('LocautoRentController::processStripePayment - Booking data cached', ['token' => $bookingToken]);

            // Create Stripe customer
            $stripeCustomer = \Stripe\Customer::create([
                'email' => $validated['customer']['email'],
                'name' => $validated['customer']['first_name'] . ' ' . $validated['customer']['last_name'],
                'phone' => $validated['customer']['phone'] ?? null,
            ]);

            // Create Stripe Checkout Session
            $session = \Stripe\Checkout\Session::create([
                'customer' => $stripeCustomer->id,
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => strtolower($validated['currency']),
                            'product_data' => [
                                'name' => 'Locauto Car Rental',
                                'description' => ($validated['vehicle_model'] ?? 'Vehicle') . ' - ' . $validated['pickup_date'] . ' to ' . $validated['return_date'],
                            ],
                            'unit_amount' => (int) ($validated['grand_total'] * 100), // Convert to cents
                        ],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',
                'success_url' => url(app()->getLocale() . '/locauto-rent-booking/success?session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => url(app()->getLocale() . '/locauto-rent-booking/cancel'),
                'metadata' => [
                    'booking_token' => $bookingToken,
                    'vehicle_code' => $validated['vehicle_code'],
                    'customer_email' => $validated['customer']['email'],
                ],
                'expires_at' => now()->addMinutes(30)->timestamp,
            ]);

            Log::info('LocautoRentController::processStripePayment - Checkout Session created', [
                'session_id' => $session->id
            ]);

            return response()->json([
                'sessionId' => $session->id,
            ]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('LocautoRentController::processStripePayment - Stripe API Error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            return response()->json(['error' => 'Payment processing failed: ' . $e->getMessage()], 400);

        } catch (\Exception $e) {
            Log::error('LocautoRentController::processStripePayment - Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle successful Stripe payment
     */
    public function handlePaymentSuccess(Request $request, $locale)
    {
        Log::info('LocautoRentController::handlePaymentSuccess - Processing successful payment');

        try {
            $sessionId = $request->query('session_id');

            if (!$sessionId) {
                Log::error('LocautoRentController::handlePaymentSuccess - Missing session_id');
                return redirect()->route('locauto-rent-booking-cancel', ['locale' => $locale])
                    ->with('error', 'Invalid payment session');
            }

            \Stripe\Stripe::setApiKey(config('stripe.secret'));
            $session = \Stripe\Checkout\Session::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                Log::warning('LocautoRentController::handlePaymentSuccess - Payment not completed', [
                    'session_id' => $sessionId,
                    'payment_status' => $session->payment_status
                ]);
                return redirect()->route('locauto-rent-booking-cancel', ['locale' => $locale])
                    ->with('error', 'Payment not completed');
            }

            // Get booking data from cache
            $bookingToken = $session->metadata->booking_token ?? null;
            if (!$bookingToken) {
                Log::error('LocautoRentController::handlePaymentSuccess - No booking token found');
                return redirect()->route('locauto-rent-booking-cancel', ['locale' => $locale])
                    ->with('error', 'Booking session expired');
            }

            $bookingData = cache()->get("locauto_booking_{$bookingToken}");
            if (!$bookingData) {
                Log::error('LocautoRentController::handlePaymentSuccess - No booking data found in cache');
                return redirect()->route('locauto-rent-booking-cancel', ['locale' => $locale])
                    ->with('error', 'Booking session expired');
            }

            Log::info('LocautoRentController::handlePaymentSuccess - Creating Locauto reservation');

            // Create reservation with Locauto API
            $reservationData = [
                'pickup_location_code' => $bookingData['pickup_location_code'],
                'dropoff_location_code' => $bookingData['dropoff_location_code'],
                'pickup_date' => $bookingData['pickup_date'],
                'pickup_time' => $bookingData['pickup_time'],
                'return_date' => $bookingData['return_date'],
                'return_time' => $bookingData['return_time'],
                'vehicle_code' => $bookingData['vehicle_code'],
                'first_name' => $bookingData['customer']['first_name'],
                'last_name' => $bookingData['customer']['last_name'],
                'email' => $bookingData['customer']['email'],
                'phone' => $bookingData['customer']['phone'],
                'age' => $bookingData['customer']['driver_age'],
                'extras' => $bookingData['selected_extras'] ?? [],
            ];

            $response = $this->locautoRentService->makeReservation($reservationData);

            // Clear cache
            cache()->forget("locauto_booking_{$bookingToken}");

            // Create confirmation object
            $confirmation = [
                'confirmation_number' => 'LOC-' . strtoupper(\Illuminate\Support\Str::random(8)),
                'status' => 'Confirmed',
                'pickup_location_code' => $bookingData['pickup_location_code'],
                'dropoff_location_code' => $bookingData['dropoff_location_code'],
                'pickup_date' => $bookingData['pickup_date'],
                'pickup_time' => $bookingData['pickup_time'],
                'return_date' => $bookingData['return_date'],
                'return_time' => $bookingData['return_time'],
                'vehicle_details' => [
                    'model' => $bookingData['vehicle_model'],
                    'sipp_code' => $bookingData['sipp_code'],
                ],
                'customer_details' => $bookingData['customer'],
                'selected_extras' => $bookingData['selected_extras'] ?? [],
                'total_amount' => $bookingData['grand_total'],
                'currency' => $bookingData['currency'],
                'payment_type' => 'Paid Online',
                'stripe_session_id' => $sessionId,
            ];

            Log::info('LocautoRentController::handlePaymentSuccess - Booking confirmed', [
                'confirmation_number' => $confirmation['confirmation_number']
            ]);

            return Inertia::render('LocautoRentBookingSuccess', [
                'booking' => $confirmation,
            ]);

        } catch (\Exception $e) {
            Log::error('LocautoRentController::handlePaymentSuccess - Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('locauto-rent-booking-cancel', ['locale' => $locale])
                ->with('error', 'Error processing payment: ' . $e->getMessage());
        }
    }

    /**
     * Handle cancelled payment
     */
    public function handlePaymentCancel(Request $request, $locale)
    {
        Log::info('LocautoRentController::handlePaymentCancel - Payment cancelled');

        return Inertia::render('LocautoRentBookingCancel', [
            'message' => 'Your payment was cancelled. You can try booking again.',
        ]);
    }
}