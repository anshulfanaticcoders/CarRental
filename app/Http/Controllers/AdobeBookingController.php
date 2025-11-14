<?php

namespace App\Http\Controllers;

use App\Models\AdobeBooking;
use App\Services\AdobeCarService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdobeBookingController extends Controller
{
    public function __construct(private AdobeCarService $adobeCarService)
    {
    }

    /**
     * Show the booking creation form for a specific Adobe vehicle.
     */
    public function create(Request $request, $locale, $id)
    {
        try {
            Log::info('AdobeBookingController::create called', [
                'locale' => $locale,
                'id' => $id,
                'search_params' => $request->all()
            ]);

            // Parse the Adobe vehicle ID to extract location and category
            if (strpos($id, '_') !== false) {
                $parts = explode('_', $id);
                $locationId = $parts[1] ?? null;
                $vehicleCategory = $parts[2] ?? null;
            } else {
                Log::error('Invalid Adobe vehicle ID format', ['id' => $id]);
                return redirect()->route('search', $locale)
                    ->with('error', 'Invalid vehicle identifier. Please search again.');
            }

            if (!$locationId || !$vehicleCategory) {
                Log::error('Missing location or category from Adobe vehicle ID', [
                    'id' => $id,
                    'locationId' => $locationId,
                    'vehicleCategory' => $vehicleCategory
                ]);
                return redirect()->route('search', $locale)
                    ->with('error', 'Invalid vehicle information. Please search again.');
            }

            // Get search parameters from request
            $pickupLocationId = $request->get('location_id', $locationId);
            $dropoffLocationId = $request->get('dropoff_location_id', $pickupLocationId);
            $dateFrom = $request->get('date_from');
            $timeFrom = $request->get('time_from', '09:00');
            $dateTo = $request->get('date_to');
            $timeTo = $request->get('time_to', '09:00');

            // Validate required parameters
            if (!$dateFrom || !$dateTo) {
                Log::error('Missing required date parameters');
                return redirect()->route('search', $locale)
                    ->with('error', 'Missing rental dates. Please search again.');
            }

            // Format dates for Adobe API
            $pickupDateTime = $dateFrom . ' ' . $timeFrom;
            $dropoffDateTime = $dateTo . ' ' . $timeTo;

            try {
                // Get vehicle data
                $cachedAdobeData = $this->adobeCarService->getCachedVehicleData($pickupLocationId, 60);

                if ($cachedAdobeData && !empty($cachedAdobeData['vehicles'])) {
                    Log::info('Using cached Adobe vehicle data for location: ' . $pickupLocationId);
                    $allVehicles = collect($cachedAdobeData['vehicles']);
                } else {
                    Log::info('Fetching fresh Adobe vehicle data from API');

                    $searchParams = [
                        'pickupoffice' => $pickupLocationId,
                        'returnoffice' => $dropoffLocationId,
                        'startdate' => $pickupDateTime,
                        'enddate' => $dropoffDateTime,
                        'promotionCode' => $request->get('promocode')
                    ];

                    $searchParams = array_filter($searchParams, function($value) {
                        return $value !== null && $value !== '';
                    });

                    $adobeResponse = $this->adobeCarService->getAvailableVehicles($searchParams);

                    if (!$adobeResponse || !isset($adobeResponse['result']) || !$adobeResponse['result'] || empty($adobeResponse['data'])) {
                        Log::error('No vehicles found or invalid response from Adobe API');
                        return redirect()->route('search', $locale)
                            ->with('error', 'Vehicle not found or temporarily unavailable.');
                    }

                    $allVehicles = collect($adobeResponse['data']);
                }

                // Find the specific vehicle
                $selectedVehicle = $allVehicles->firstWhere('category', $vehicleCategory);

                if (!$selectedVehicle) {
                    Log::error('Vehicle category not found', [
                        'category' => $vehicleCategory,
                        'availableCategories' => $allVehicles->pluck('category')->toArray()
                    ]);
                    return redirect()->route('search', $locale)
                        ->with('error', 'Vehicle not found. Please search again.');
                }

                // Get detailed vehicle information including protections and extras
                $vehicleDetails = $this->adobeCarService->getProtectionsAndExtras(
                    $pickupLocationId,
                    $vehicleCategory,
                    [
                        'startdate' => $pickupDateTime,
                        'enddate' => $dropoffDateTime
                    ]
                );

                // Process vehicle data
                $processedVehicle = [
                    'id' => $id,
                    'category' => $vehicleCategory,
                    'model' => $selectedVehicle['model'] ?? 'Adobe Vehicle',
                    'brand' => $this->extractBrandFromModel($selectedVehicle['model'] ?? ''),
                    'image' => $this->getAdobeVehicleImage($selectedVehicle['photo'] ?? ''),
                    'price_per_day' => (float) ($selectedVehicle['pli'] ?? 0),
                    'currency' => 'USD',
                    'seating_capacity' => (int) ($selectedVehicle['passengers'] ?? 4),
                    'transmission' => ($selectedVehicle['manual'] ?? false) ? 'manual' : 'automatic',
                    'protections' => $vehicleDetails['protections'] ?? [],
                    'extras' => $vehicleDetails['extras'] ?? [],
                    // Adobe-specific fields
                    'pli' => (float) ($selectedVehicle['pli'] ?? 0),
                    'ldw' => (float) ($selectedVehicle['ldw'] ?? 0),
                    'spp' => (float) ($selectedVehicle['spp'] ?? 0),
                    'tdr' => (float) ($selectedVehicle['tdr'] ?? 0),
                    'dro' => (float) ($selectedVehicle['dro'] ?? 0),
                ];

                return Inertia::render('AdobeBooking', [
                    'vehicle' => (object) $processedVehicle,
                    'searchParams' => [
                        'pickup_location_id' => $pickupLocationId,
                        'dropoff_location_id' => $dropoffLocationId,
                        'pickup_datetime' => $pickupDateTime,
                        'dropoff_datetime' => $dropoffDateTime,
                        'date_from' => $dateFrom,
                        'time_from' => $timeFrom,
                        'date_to' => $dateTo,
                        'time_to' => $timeTo,
                        'currency' => $request->get('currency', 'USD'),
                        'promocode' => $request->get('promocode'),
                    ],
                    'locale' => $locale,
                ]);

            } catch (\Exception $e) {
                Log::error('Adobe API error in booking create', [
                    'pickup_location_id' => $pickupLocationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return redirect()->route('search', $locale)
                    ->with('error', 'Unable to connect to Adobe service. Please try again later.');
            }

        } catch (\Exception $e) {
            Log::error('Error in AdobeBookingController::create', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('search', $locale)
                ->with('error', 'An error occurred while loading booking form. Please try again.');
        }
    }

    /**
     * Process Adobe booking payment via Stripe.
     */
    public function processAdobeBookingPayment(Request $request)
    {
        try {
            Log::info('AdobeBookingController::processAdobeBookingPayment called', $request->all());

            $validatedData = $request->validate([
                // Vehicle Information
                'vehicle_id' => 'required|string',
                'vehicle_category' => 'required|string',
                'vehicle_model' => 'required|string',
                'pickup_location_id' => 'required|string',
                'dropoff_location_id' => 'nullable|string',

                // Rental Dates and Times
                'pickup_datetime' => 'required|string',
                'dropoff_datetime' => 'required|string',
                'date_from' => 'required|date_format:Y-m-d',
                'time_from' => 'required|string',
                'date_to' => 'required|date_format:Y-m-d',
                'time_to' => 'required|string',

                // Customer Information
                'customer.name' => 'required|string|max:255',
                'customer.last_name' => 'required|string|max:255',
                'customer.email' => 'required|email|max:255',
                'customer.phone' => 'required|string|max:20',
                'customer.country' => 'required|string|max:100',

                // Adobe Pricing Components
                'tdr_total' => 'required|numeric|min:0',
                'pli_total' => 'required|numeric|min:0',
                'ldw_total' => 'nullable|numeric|min:0',
                'spp_total' => 'nullable|numeric|min:0',
                'dro_total' => 'nullable|numeric|min:0',
                'base_rate' => 'required|numeric|min:0',

                // Totals
                'vehicle_total' => 'required|numeric|min:0',
                'grand_total' => 'required|numeric|min:0',
                'currency' => 'required|string|max:3',

                // Protections and Extras
                'selected_protections' => 'nullable|array',
                'selected_extras' => 'nullable|array',

                // Additional Information
                'customer_comment' => 'nullable|string|max:1000',
                'reference' => 'nullable|string|max:255',
                'flight_number' => 'nullable|string|max:50',
                'language' => 'nullable|string|max:10',
                'promocode' => 'nullable|string|max:100',
            ]);

            // Create the Adobe booking record
            $booking = AdobeBooking::create([
                'vehicle_category' => $validatedData['vehicle_category'],
                'vehicle_model' => $validatedData['vehicle_model'],
                'pickup_location_id' => $validatedData['pickup_location_id'],
                'dropoff_location_id' => $validatedData['dropoff_location_id'],
                'start_date' => $validatedData['date_from'],
                'start_time' => $validatedData['time_from'],
                'end_date' => $validatedData['date_to'],
                'end_time' => $validatedData['time_to'],
                'customer_details' => [
                    'name' => $validatedData['customer']['name'],
                    'last_name' => $validatedData['customer']['last_name'],
                    'email' => $validatedData['customer']['email'],
                    'phone' => $validatedData['customer']['phone'],
                    'country' => $validatedData['customer']['country'],
                ],
                'tdr_total' => $validatedData['tdr_total'],
                'pli_total' => $validatedData['pli_total'],
                'ldw_total' => $validatedData['ldw_total'] ?? 0,
                'spp_total' => $validatedData['spp_total'] ?? 0,
                'dro_total' => $validatedData['dro_total'] ?? 0,
                'base_rate' => $validatedData['base_rate'],
                'vehicle_total' => $validatedData['vehicle_total'],
                'grand_total' => $validatedData['grand_total'],
                'currency' => $validatedData['currency'],
                'selected_protections' => $validatedData['selected_protections'] ?? [],
                'selected_extras' => $validatedData['selected_extras'] ?? [],
                'booking_status' => 'pending',
                'payment_status' => 'pending',
                'payment_type' => 'PREPAID',
                'customer_comment' => $validatedData['customer_comment'],
                'reference' => $validatedData['reference'],
                'flight_number' => $validatedData['flight_number'],
                'language' => $validatedData['language'] ?? 'en',
                'user_id' => Auth::id(),
            ]);

            Log::info('Adobe booking created successfully', ['booking_id' => $booking->id]);

            // Create Stripe checkout session
            Stripe::setApiKey(config('stripe.secret'));

            $session = Session::create([
                'customer_email' => $booking->customer_details['email'] ?? null,
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => strtolower($booking->currency),
                            'product_data' => [
                                'name' => $booking->vehicle_model,
                                'description' => "Adobe Car Rental - {$booking->vehicle_category}",
                                'images' => [$this->getAdobeVehicleImage($selectedVehicle['photo'] ?? '')],
                            ],
                            'unit_amount' => (int)($booking->grand_total * 100), // Convert to cents
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => route('adobe.booking.success', ['locale' => app()->getLocale()]) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('adobe.booking.cancel', ['locale' => app()->getLocale()]) . '?session_id={CHECKOUT_SESSION_ID}',
                'metadata' => [
                    'booking_id' => $booking->id,
                    'booking_type' => 'adobe',
                ],
                'billing_address_collection' => 'required',
            ]);

            // Update booking with Stripe session ID
            $booking->stripe_checkout_session_id = $session->id;
            $booking->save();

            Log::info('Adobe Stripe checkout session created', [
                'booking_id' => $booking->id,
                'session_id' => $session->id
            ]);

            return response()->json([
                'success' => true,
                'session_id' => $session->id,
                'checkout_url' => $session->url,
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing Adobe booking payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to process payment. Please try again.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle successful Adobe booking payment.
     */
    public function adobeBookingSuccess(Request $request)
    {
        try {
            $sessionId = $request->get('session_id');

            if (!$sessionId) {
                return redirect()->route('search')
                    ->with('error', 'Payment session not found.');
            }

            // Retrieve the Stripe session
            Stripe::setApiKey(config('stripe.secret'));
            $session = Session::retrieve($sessionId);

            if (!$session) {
                return redirect()->route('search')
                    ->with('error', 'Payment session not found.');
            }

            // Find the booking
            $booking = AdobeBooking::where('stripe_checkout_session_id', $sessionId)->first();

            if (!$booking) {
                return redirect()->route('search')
                    ->with('error', 'Booking not found.');
            }

            if ($session->payment_status === 'paid') {
                // Update booking status
                $booking->payment_status = 'paid';
                $booking->payment_completed_at = now();
                $booking->booking_status = 'confirmed';
                $booking->payment_handler_ref = $session->payment_intent;
                $booking->save();

                Log::info('Adobe booking payment completed successfully', [
                    'booking_id' => $booking->id,
                    'session_id' => $sessionId
                ]);

                return Inertia::render('BookingSuccess', [
                    'booking' => $booking,
                    'provider' => 'Adobe Car Rental',
                    'message' => 'Your Adobe car rental has been confirmed successfully!',
                ]);
            } else {
                return redirect()->route('search')
                    ->with('error', 'Payment was not successful.');
            }

        } catch (\Exception $e) {
            Log::error('Error handling Adobe booking success', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('search')
                ->with('error', 'An error occurred while processing your booking confirmation.');
        }
    }

    /**
     * Handle cancelled Adobe booking.
     */
    public function adobeBookingCancel(Request $request)
    {
        try {
            $sessionId = $request->get('session_id');

            if ($sessionId) {
                // Find and update the booking
                $booking = AdobeBooking::where('stripe_checkout_session_id', $sessionId)->first();

                if ($booking) {
                    $booking->payment_status = 'failed';
                    $booking->booking_status = 'cancelled';
                    $booking->cancelled_at = now();
                    $booking->save();

                    Log::info('Adobe booking cancelled', [
                        'booking_id' => $booking->id,
                        'session_id' => $sessionId
                    ]);
                }
            }

            return redirect()->route('search')
                ->with('info', 'Booking was cancelled. You can try again whenever you\'re ready.');

        } catch (\Exception $e) {
            Log::error('Error handling Adobe booking cancellation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('search')
                ->with('error', 'An error occurred during cancellation.');
        }
    }

    /**
     * Get customer Adobe bookings.
     */
    public function getCustomerAdobeBookings()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $bookings = AdobeBooking::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'bookings' => $bookings
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting customer Adobe bookings', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to retrieve bookings.'
            ], 500);
        }
    }

    /**
     * Extract brand name from vehicle model.
     */
    private function extractBrandFromModel($model): string
    {
        if (empty($model)) {
            return 'Adobe';
        }

        $brands = ['Suzuki', 'Toyota', 'Nissan', 'Hyundai', 'Honda', 'Ford', 'Chevrolet', 'Mitsubishi', 'Mazda', 'Volkswagen', 'Kia', 'Geely'];

        foreach ($brands as $brand) {
            if (stripos($model, $brand) !== false) {
                return $brand;
            }
        }

        $words = explode(' ', $model);
        return $words[0] ?? 'Adobe';
    }

    /**
     * Get Adobe vehicle image URL.
     */
    private function getAdobeVehicleImage($photo): string
    {
        if (empty($photo)) {
            return '/images/adobe-placeholder.jpg';
        }

        if (strpos($photo, 'http') === false) {
            return "https://adobecar.cr/images/vehicles/{$photo}";
        }

        return $photo;
    }
}
