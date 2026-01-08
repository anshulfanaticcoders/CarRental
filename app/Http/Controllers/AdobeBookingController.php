<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use App\Services\AdobeCarService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

            // 1. Get or create Customer
            $customer = Customer::where('email', $validatedData['customer']['email'])->first();
            if (!$customer) {
                $customer = Customer::create([
                    'user_id' => Auth::id(),
                    'first_name' => $validatedData['customer']['name'],
                    'last_name' => $validatedData['customer']['last_name'],
                    'email' => $validatedData['customer']['email'],
                    'phone' => $validatedData['customer']['phone'],
                    'driver_age' => 25, // Default or extract if available
                ]);
            }

            // 2. Construct comment for Adobe including extras/protections
            $commentParts = [];
            if (!empty($validatedData['customer_comment'])) {
                $commentParts[] = "Customer Notes: " . $validatedData['customer_comment'];
            }
            
            $adobeComment = implode(" | ", $commentParts);
            if (strlen($adobeComment) > 1000) {
                $adobeComment = substr($adobeComment, 0, 997) . "...";
            }

            // 3. Fetch protections and extras for the booking items array (MANDATORY)
            $pickupOffice = $validatedData['pickup_location_id'];
            $returnOffice = $validatedData['dropoff_location_id'] ?? $pickupOffice;
            $startDate = $validatedData['pickup_datetime'];
            $endDate = $validatedData['dropoff_datetime'];
            $category = $validatedData['vehicle_category'];

            $categoryItems = $this->adobeCarService->getProtectionsAndExtras(
                $pickupOffice,
                $category,
                ['startdate' => $startDate, 'enddate' => $endDate]
            );

            // Build items array from API response, marking user-selected ones
            $bookingItems = [];
            $allItems = array_merge($categoryItems['protections'] ?? [], $categoryItems['extras'] ?? []);

            $selectedProtectionCodes = array_map(fn($p) => $p['code'] ?? '', $validatedData['selected_protections'] ?? []);
            $selectedExtrasByCode = [];
            foreach ($validatedData['selected_extras'] ?? [] as $extra) {
                $code = $extra['code'] ?? '';
                if ($code) {
                    $selectedExtrasByCode[$code] = $extra['quantity'] ?? 1;
                }
            }

            // IMPORTANT: Only send items that should be INCLUDED (minimal items approach)
            // Adobe ignores items with included=false
            foreach ($allItems as $item) {
                $code = $item['code'] ?? '';
                $isRequired = $item['required'] ?? false;
                $isProtection = ($item['type'] ?? '') === 'Proteccion';
                $isExtra = ($item['type'] ?? '') === 'Adicionales';

                // Determine if this item should be included
                $shouldInclude = false;
                $quantity = 0;
                
                if ($isRequired) {
                    $shouldInclude = true;
                    $quantity = 1;
                } elseif ($isProtection && in_array($code, $selectedProtectionCodes)) {
                    $shouldInclude = true;
                    $quantity = 1;
                } elseif ($isExtra && isset($selectedExtrasByCode[$code])) {
                    $shouldInclude = true;
                    $quantity = $selectedExtrasByCode[$code];
                }

                // Only add items that should be included
                if ($shouldInclude) {
                    $bookingItems[] = [
                        'code' => $code,
                        'quantity' => $quantity,
                        'total' => $item['total'] ?? 0,
                        'order' => $item['order'] ?? 0,
                        'type' => $item['type'] ?? '',
                        'included' => true, // MUST be true for Adobe to charge it
                        'description' => $item['description'] ?? '',
                        'information' => $item['information'] ?? '',
                        'name' => $item['name'] ?? '',
                        'required' => $isRequired
                    ];
                }
            }

            Log::info('Adobe: Built items array (minimal)', [
                'items_count' => count($bookingItems),
                'items' => array_map(fn($i) => $i['code'] . ':qty=' . $i['quantity'], $bookingItems)
            ]);


            // 4. Create Adobe Reservation via API with CORRECT Swagger schema field names
            $adobeParams = [
                'bookingNumber' => 0,
                'category' => $category,
                'startdate' => $startDate, // lowercase!
                'pickupoffice' => $pickupOffice, // lowercase!
                'enddate' => $endDate, // lowercase!
                'returnoffice' => $returnOffice, // lowercase!
                'customerCode' => $this->adobeCarService->getCustomerCode(),
                'name' => $validatedData['customer']['name'],
                'lastName' => $validatedData['customer']['last_name'],
                'fullName' => $validatedData['customer']['name'] . ' ' . $validatedData['customer']['last_name'],
                'email' => $validatedData['customer']['email'] ?? '',
                'phone' => $validatedData['customer']['phone'] ?? '',
                'country' => $validatedData['customer']['country'] ?? 'CR',
                'language' => 'en',
                'flightNumber' => $validatedData['flight_number'] ?? '',
                'customerComment' => $adobeComment,
                'items' => $bookingItems // MANDATORY items array
            ];

            Log::info('Creating actual Adobe reservation', ['params' => $adobeParams]);
            $adobeApiResult = $this->adobeCarService->createBooking($adobeParams);


            if (!$adobeApiResult || !isset($adobeApiResult['result']) || !$adobeApiResult['result']) {
                Log::error('Adobe API Reservation failed', ['result' => $adobeApiResult]);
                return response()->json([
                    'success' => false,
                    'error' => 'Vehicle could not be reserved with Adobe: ' . ($adobeApiResult['error'] ?? 'Unknown error')
                ], 400);
            }

            $adobeBookingNumber = $adobeApiResult['data']['bookingNumber'] ?? null;
            Log::info('Adobe reservation created successfully', ['bookingNumber' => $adobeBookingNumber]);

            // 3.5 Attempt to get vehicle image from cache for the unified record
            $vehicleImage = null;
            try {
                $cachedData = $this->adobeCarService->getCachedVehicleData($validatedData['pickup_location_id'], 120);
                if ($cachedData && !empty($cachedData['vehicles'])) {
                    $selected = collect($cachedData['vehicles'])->firstWhere('category', $validatedData['vehicle_category']);
                    if ($selected && isset($selected['photo'])) {
                        $vehicleImage = $this->getAdobeVehicleImage($selected['photo']);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch vehicle image from cache for Adobe booking record', ['error' => $e->getMessage()]);
            }

            if (!$vehicleImage) {
                // Fallback to a generic image based on category if photo not found
                $vehicleImage = $this->getAdobeVehicleImage($validatedData['vehicle_category']);
            }

            // 4. Create the unified Booking record
            $booking = Booking::create([
                'booking_number' => Booking::generateBookingNumber(),
                'customer_id' => $customer->id,
                'provider_source' => 'adobe',
                'provider_vehicle_id' => $validatedData['vehicle_category'],
                'provider_booking_ref' => $adobeBookingNumber,
                'vehicle_name' => $validatedData['vehicle_model'],
                'vehicle_image' => $vehicleImage,
                'pickup_date' => $validatedData['date_from'],
                'return_date' => $validatedData['date_to'],
                'pickup_time' => $validatedData['time_from'],
                'return_time' => $validatedData['time_to'],
                'pickup_location' => $validatedData['pickup_location_id'],
                'return_location' => $validatedData['dropoff_location_id'] ?? $validatedData['pickup_location_id'],
                'total_days' => 1, // Will be calculated by frontend or we can calculate here. 
                                  // For now using 1 as placeholder if missing, but grand_total is correct.
                'base_price' => $validatedData['base_rate'],
                'extra_charges' => $validatedData['grand_total'] - $validatedData['base_rate'],
                'tax_amount' => 0,
                'total_amount' => $validatedData['grand_total'],
                'amount_paid' => $validatedData['grand_total'],
                'pending_amount' => 0,
                'booking_currency' => strtoupper($validatedData['currency']),
                'payment_status' => 'pending',
                'booking_status' => 'pending',
                'notes' => $adobeComment,
            ]);

            Log::info('Unified booking record created for Adobe', ['booking_id' => $booking->id]);

            // 5. Create Stripe checkout session
            Stripe::setApiKey(config('stripe.secret'));

            $session = Session::create([
                'customer_email' => $customer->email,
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => strtolower($booking->booking_currency),
                            'product_data' => [
                                'name' => $booking->vehicle_name,
                                'description' => "Adobe Car Rental - {$booking->provider_vehicle_id}",
                                'images' => [$booking->vehicle_image],
                            ],
                            'unit_amount' => (int)($booking->total_amount * 100), // Convert to cents
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
            $booking->stripe_session_id = $session->id;
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
            $booking = Booking::where('stripe_session_id', $sessionId)->first();

            if (!$booking) {
                return redirect()->route('search')
                    ->with('error', 'Booking not found.');
            }

            if ($session->payment_status === 'paid') {
                // Update booking status
                $booking->payment_status = 'paid';
                //$booking->payment_completed_at = now(); // Booking model doesn't have this but we can use updated_at or add if needed
                $booking->booking_status = 'confirmed';
                $booking->stripe_payment_intent_id = $session->payment_intent;
                $booking->save();

                Log::info('Adobe booking payment completed successfully', [
                    'booking_id' => $booking->id,
                    'session_id' => $sessionId
                ]);

                // Prepare data for the success view
                $vehicleData = [
                    'brand' => $this->extractBrandFromModel($booking->vehicle_name),
                    'model' => $booking->vehicle_name,
                    'image' => $booking->vehicle_image,
                ];

                $paymentData = [
                    'amount' => $booking->total_amount,
                    'currency' => $booking->booking_currency,
                    'status' => 'paid',
                ];

                return Inertia::render('Booking/Success', [
                    'booking' => $booking,
                    'vehicle' => $vehicleData,
                    'payment' => $paymentData,
                    'provider' => 'Adobe Car Rental',
                    'message' => 'Your Adobe car rental has been confirmed successfully!',
                    'locale' => $request->get('locale', app()->getLocale()),
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
                $booking = Booking::where('stripe_session_id', $sessionId)->first();

                if ($booking) {
                    $booking->payment_status = 'failed';
                    $booking->booking_status = 'cancelled';
                    //$booking->cancelled_at = now(); // Not in unified model
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

            // Find customer link
            $customer = Customer::where('user_id', $user->id)->first();
            if (!$customer) {
                return response()->json(['success' => true, 'bookings' => []]);
            }

            $bookings = Booking::where('customer_id', $customer->id)
                ->where('provider_source', 'adobe')
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
