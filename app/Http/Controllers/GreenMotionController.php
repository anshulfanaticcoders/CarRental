<?php

namespace App\Http\Controllers;

use App\Services\GreenMotionService;
use Illuminate\Http\Request;
use SimpleXMLElement;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File; // Import File facade
use App\Models\GreenMotionBooking; // Correctly placed import
use App\Models\User;
use App\Notifications\GreenMotionBooking\BookingCreatedAdminNotification;
use App\Notifications\GreenMotionBooking\BookingCreatedCustomerNotification;
use App\Services\Affiliate\AffiliateQrCodeService;

class GreenMotionController extends Controller
{
    public function __construct(private GreenMotionService $greenMotionService)
    {
    }

    private function parseVehicles(SimpleXMLElement $xmlObject): array
    {
        $vehicles = [];
        if (isset($xmlObject->response->vehicles->vehicle)) {
            foreach ($xmlObject->response->vehicles->vehicle as $vehicle) {
                $products = [];
                if (isset($vehicle->product)) {
                    foreach ($vehicle->product as $product) {
                        $productType = (string) $product['type'];
                        $benefits = [];

                        switch ($productType) {
                            case 'BAS':
                                $benefits = [
                                    'Excess liability' => true,
                                    'Security deposit' => true,
                                    'Fuel policy Like for Like' => true,
                                    '100 free miles per rental' => true,
                                    'Non refundable' => false,
                                    'Non amendable' => false,
                                ];
                                break;
                            case 'PLU':
                                $benefits = [
                                    'Excess liability' => true,
                                    'Security deposit' => true,
                                    'Fuel policy Like for Like' => true,
                                    '100 free miles per rental' => true,
                                    'Cancellation in line with T&Cs' => true,
                                ];
                                break;
                            case 'PRE':
                                $benefits = [
                                    'Excess liability' => true,
                                    'Security deposit' => true,
                                    'Fuel policy Like for Like' => true,
                                    'Unlimited mileage' => true,
                                    'Cancellation in line with T&Cs' => true,
                                ];
                                break;
                            case 'PMP':
                                $benefits = [
                                    'Excess liability $0' => true,
                                    'Security deposit' => true,
                                    'Fuel policy Like for Like' => true,
                                    'Unlimited mileage' => true,
                                    'Cancellation in line with T&Cs' => true,
                                    'Glass and tyres covered' => true,
                                    'Debit Card Accepted for Deposits' => true,
                                    'Two free extras on collection' => true,
                                ];
                                break;
                        }

                        $products[] = [
                            'type' => $productType,
                            'benefits' => $benefits,
                            'total' => (string) $product->total,
                            'currency' => (string) $product->total['currency'],
                            'deposit' => (string) $product->deposit,
                            'excess' => (string) $product->excess,
                            'fuelpolicy' => (string) $product->fuelpolicy,
                            'mileage' => (string) $product->mileage,
                            'costperextradistance' => (string) $product->costperextradistance,
                            'minage' => (string) $product->minage,
                            'excludedextras' => (string) $product->excludedextras,
                            'fasttrack' => (string) $product->fasttrack,
                            'oneway' => (string) $product->oneway,
                            'oneway_fee' => (string) $product->oneway_fee,
                            'cancellation_rules' => json_decode(json_encode($product->CancellationRules), true),
                            // Removed quoteid from here as it's at the response level
                        ];
                    }
                }

                $vehicleOptionsData = [];
                if (isset($vehicle->options->option)) {
                    foreach ($vehicle->options->option as $option) {
                        $vehicleOptionsData[] = [
                            'optionID' => (string) $option->optionID,
                            'Name' => (string) $option->Name,
                            'code' => (string) $option->code,
                            'Description' => (string) $option->Description,
                            'Daily_rate' => (string) $option->Daily_rate,
                            'Daily_rate_currency' => (string) $option->Daily_rate['currency'],
                            'Total_for_this_booking' => (string) $option->Total_for_this_booking,
                            'Total_for_this_booking_currency' => (string) $option->Total_for_this_booking['currency'],
                            'Prepay_available' => (string) $option->Prepay_available,
                            'Prepay_daily_rate' => (string) $option->Prepay_daily_rate,
                            'Prepay_daily_rate_currency' => (string) $option->Prepay_daily_rate['currency'],
                            'Prepay_total_for_this_booking' => (string) $option->Prepay_total_for_this_booking,
                            'Prepay_total_for_this_booking_currency' => (string) $option->Prepay_total_for_this_booking['currency'],
                            'Choices' => (string) $option->Choices,
                        ];
                    }
                }

                $insuranceOptionsData = [];
                if (isset($vehicle->insurance_options->option)) {
                    foreach ($vehicle->insurance_options->option as $option) {
                        $insuranceOptionsData[] = [
                            'optionID' => (string) $option->optionID,
                            'Name' => (string) $option->Name,
                            'code' => (string) $option->code,
                            'Description' => (string) $option->Description,
                            'Daily_rate' => (string) $option->Daily_rate,
                            'Daily_rate_currency' => (string) $option->Daily_rate['currency'],
                            'Total_for_this_booking' => (string) $option->Total_for_this_booking,
                            'Total_for_this_booking_currency' => (string) $option->Total_for_this_booking['currency'],
                            'Prepay_available' => (string) $option->Prepay_available,
                            'Prepay_daily_rate' => (string) $option->Prepay_daily_rate,
                            'Prepay_daily_rate_currency' => (string) $option->Prepay_daily_rate['currency'],
                            'Prepay_total_for_this_booking' => (string) $option->Prepay_total_for_this_booking,
                            'Prepay_total_for_this_booking_currency' => (string) $option->Prepay_total_for_this_booking['currency'],
                            'Choices' => (string) $option->Choices,
                            'Damage_excess' => (string) $option->Damage_excess,
                            'Deposit' => (string) $option->Deposit,
                        ];
                    }
                }

                $vehicles[] = [
                    'name' => (string) $vehicle['name'],
                    'id' => (string) $vehicle['id'],
                    'image' => urldecode((string) $vehicle['image']),
                    'total' => (string) $vehicle->total,
                    'total_currency' => (string) $vehicle->total['currency'],
                    'groupName' => (string) $vehicle->groupName,
                    'adults' => (string) $vehicle->adults,
                    'children' => (string) $vehicle->children,
                    'luggageSmall' => (string) $vehicle->luggageSmall,
                    'luggageMed' => (string) $vehicle->luggageMed,
                    'luggageLarge' => (string) $vehicle->luggageLarge,
                    'smallImage' => (string) $vehicle->smallImage,
                    'largeImage' => (string) $vehicle->largeImage,
                    'fuel' => (string) $vehicle->fuel,
                    'mpg' => (string) $vehicle->mpg,
                    'co2' => (string) $vehicle->co2,
                    'carorvan' => (string) $vehicle->carorvan,
                    'airConditioning' => (string) $vehicle->airConditioning,
                    'transmission' => (string) $vehicle->transmission,
                    'paymentURL' => (string) $vehicle->paymentURL,
                    'loyalty' => (string) $vehicle->loyalty,
                    'originalPrice' => (string) $vehicle->originalPrice,
                    'originalPrice_currency' => (string) $vehicle->originalPrice['currency'],
                    'driveandgo' => (string) $vehicle->driveandgo,
                    'products' => $products,
                    'options' => $vehicleOptionsData,
                    'insurance_options' => $insuranceOptionsData,
                ];
            }
        }
        return $vehicles;
    }

    private function parseOptionalExtras(SimpleXMLElement $xmlObject): array
    {
        $optionalExtras = [];
        if (isset($xmlObject->response->optionalextras->extra)) {
            foreach ($xmlObject->response->optionalextras->extra as $extra) {
                $extraOptions = [];
                if (isset($extra->options->option)) {
                    foreach ($extra->options->option as $option) {
                        $extraOptions[] = [
                            'optionID' => (string) $option->optionID,
                            'Name' => (string) $option->Name,
                            'code' => (string) $option->code,
                            'Description' => (string) $option->Description,
                            'Daily_rate' => (string) $option->Daily_rate,
                            'Daily_rate_currency' => (string) $option->Daily_rate['currency'],
                            'Total_for_this_booking' => (string) $option->Total_for_this_booking,
                            'Total_for_this_booking_currency' => (string) $option->Total_for_this_booking['currency'],
                            'Prepay_available' => (string) $option->Prepay_available,
                            'Prepay_daily_rate' => (string) $option->Prepay_daily_rate,
                            'Prepay_daily_rate_currency' => (string) $option->Prepay_daily_rate['currency'],
                            'Prepay_total_for_this_booking' => (string) $option->Prepay_total_for_this_booking,
                            'Prepay_total_for_this_booking_currency' => (string) $option->Prepay_total_for_this_booking['currency'],
                            'Choices' => (string) $option->Choices,
                        ];
                    }
                } else {
                    $optionalExtras[] = [
                        'optionID' => (string) $extra->optionID,
                        'Name' => (string) $extra->Name,
                        'code' => (string) $extra->code,
                        'Description' => (string) $extra->Description,
                        'Daily_rate' => (string) $extra->Daily_rate,
                        'Daily_rate_currency' => (string) $extra->Daily_rate['currency'],
                        'Total_for_this_booking' => (string) $extra->Total_for_this_booking,
                        'Total_for_this_booking_currency' => (string) $extra->Total_for_this_booking['currency'],
                        'Prepay_available' => (string) $extra->Prepay_available,
                        'Prepay_daily_rate' => (string) $extra->Prepay_daily_rate,
                        'Prepay_daily_rate_currency' => (string) $extra->Prepay_daily_rate['currency'],
                        'Prepay_total_for_this_booking' => (string) $extra->Prepay_total_for_this_booking,
                        'Prepay_total_for_this_booking_currency' => (string) $extra->Prepay_total_for_this_booking['currency'],
                        'Choices' => (string) $extra->Choices,
                        'required' => (string) $extra['required'],
                        'numberAllowed' => (string) $extra['numberAllowed'],
                    ];
                    continue;
                }

                $optionalExtras[] = [
                    'Name' => (string) $extra->Name,
                    'Description' => (string) $extra->Description,
                    'required' => (string) $extra['required'],
                    'numberAllowed' => (string) $extra['numberAllowed'],
                    'options' => $extraOptions,
                ];
            }
        }
        return $optionalExtras;
    }

    private function parseLocation(SimpleXMLElement $xmlObject, string $locationId): ?array
    {
        if (isset($xmlObject->response->location_info)) {
            $loc = $xmlObject->response->location_info;
            $openingHours = [];
            if (isset($loc->opening_hours->day)) {
                foreach ($loc->opening_hours->day as $day) {
                    $openingHours[] = [
                        'name' => (string) $day['name'],
                        'is24hrs' => (string) $day['is24hrs'],
                        'open' => (string) $day['open'],
                        'close' => (string) $day['close'],
                    ];
                }
            }
            $officeOpeningHours = [];
            if (isset($loc->office_opening_hours->day)) {
                foreach ($loc->office_opening_hours->day as $day) {
                    $officeOpeningHours[] = [
                        'name' => (string) $day['name'],
                        'is24hrs' => (string) $day['is24hrs'],
                        'open' => (string) $day['open'],
                        'close' => (string) $day['close'],
                    ];
                }
            }
            $outOfHours = [];
            if (isset($loc->out_of_hours->day)) {
                foreach ($loc->out_of_hours->day as $day) {
                    $outOfHours[] = [
                        'name' => (string) $day['name'],
                        'open' => (string) $day['open'],
                        'close' => (string) $day['close'],
                    ];
                }
            }
            $outOfHoursDropoff = [];
            if (isset($loc->out_of_hours_dropoff->day)) {
                foreach ($loc->out_of_hours_dropoff->day as $day) {
                    $outOfHoursDropoff[] = [
                        'name' => (string) $day['name'],
                        'start' => (string) $day['start'],
                        'end' => (string) $day['end'],
                        'start2' => (string) $day['start2'],
                        'end2' => (string) $day['end2'],
                    ];
                }
            }
            $daytimeClosuresHours = [];
            if (isset($loc->daytime_closures_hours->day)) {
                foreach ($loc->daytime_closures_hours->day as $day) {
                    $daytimeClosuresHours[] = [
                        'name' => (string) $day['name'],
                        'start' => (string) $day['start'],
                        'end' => (string) $day['end'],
                    ];
                }
            }

            return [
                'id' => $locationId,
                'name' => (string) $loc->location_name,
                'address_1' => (string) $loc->address_1,
                'address_2' => (string) $loc->address_2,
                'address_3' => (string) $loc->address_3,
                'address_city' => (string) $loc->address_city,
                'address_county' => (string) $loc->address_county,
                'address_postcode' => (string) $loc->address_postcode,
                'telephone' => (string) $loc->telephone,
                'fax' => (string) $loc->fax,
                'email' => (string) $loc->email,
                'latitude' => (string) $loc->latitude,
                'longitude' => (string) $loc->longitude,
                'iata' => (string) $loc->iata,
                'opening_hours' => $openingHours,
                'office_opening_hours' => $officeOpeningHours,
                'out_of_hours' => $outOfHours,
                'out_of_hours_dropoff' => $outOfHoursDropoff,
                'daytime_closures_hours' => $daytimeClosuresHours,
                'out_of_hours_charge' => (string) $loc->out_of_hours_charge,
                'charge_both_ways' => (string) $loc->charge_both_ways,
                'extra' => (string) $loc->extra,
            ];
        }
        return null;
    }

    public function getGreenMotionVehicles(Request $request)
    {
        $locationId = $request->input('location_id', 61627);
        $startDate = $request->input('start_date', '2032-01-06');
        $startTime = $request->input('start_time', '09:00');
        $endDate = $request->input('end_date', '2032-01-08');
        $endTime = $request->input('end_time', '09:00');
        $age = $request->input('age', 35);
        $rentalCode = $request->input('rentalCode', null);

        $options = [
            'rentalCode' => $rentalCode,
            'currency' => $request->input('currency'),
            'fuel' => $request->input('fuel'),
            'userid' => $request->input('userid'),
            'username' => $request->input('username'),
            'language' => $request->input('language'),
            'full_credit' => $request->input('full_credit'),
            'promocode' => $request->input('promocode'),
            'dropoff_location_id' => $request->input('dropoff_location_id'),
        ];

        $xml = $this->greenMotionService->getVehicles(
            $locationId,
            $startDate,
            $startTime,
            $endDate,
            $endTime,
            $age,
            $options
        );

        if (is_null($xml) || empty($xml)) {
            Log::error('GreenMotion API returned null or empty XML response for GetVehicles.');
            return response()->json(['error' => 'Failed to retrieve vehicle data. API returned empty response.'], 500);
        }

        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xml);

        if ($xmlObject === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                Log::error('XML Parsing Error (GetVehicles): ' . $error->message);
            }
            libxml_clear_errors();
            Log::error('Raw XML response for GetVehicles that failed parsing: ' . $xml); // Log raw XML if parsing fails
            return response()->json(['error' => 'Failed to parse XML response from API.'], 500);
        }

        Log::info('Full XML Object from GetVehicles: ' . json_encode($xmlObject)); // Log the full XML object

        $vehicles = $this->parseVehicles($xmlObject);
        $optionalExtras = $this->parseOptionalExtras($xmlObject);

        return response()->json([
            'vehicles' => $vehicles,
            'optionalExtras' => $optionalExtras,
        ]);
    }

    public function showGreenMotionCars(Request $request)
    {
        $locationId = $request->input('location_id', 61627);
        $startDate = $request->input('start_date', '2032-01-06');
        $startTime = $request->input('start_time', '09:00');
        $endDate = $request->input('end_date', '2032-01-08');
        $endTime = $request->input('end_time', '09:00');
        $age = $request->input('age', 35);
        $rentalCode = $request->input('rentalCode', null);

        $vehicleOptions = [
            'rentalCode' => $rentalCode,
            'currency' => $request->input('currency'),
            'fuel' => $request->input('fuel'),
            'userid' => $request->input('userid'),
            'username' => $request->input('username'),
            'language' => $request->input('language'),
            'full_credit' => $request->input('full_credit'),
            'promocode' => $request->input('promocode'),
            'dropoff_location_id' => $request->input('dropoff_location_id'),
        ];

        $xmlVehicles = $this->greenMotionService->getVehicles(
            $locationId,
            $startDate,
            $startTime,
            $endDate,
            $endTime,
            $age,
            $vehicleOptions
        );

        $vehicles = [];
        $optionalExtras = [];
        $quoteId = null; // Initialize quoteId

        if (!is_null($xmlVehicles) && !empty($xmlVehicles)) {
            libxml_use_internal_errors(true);
            $xmlObject = simplexml_load_string($xmlVehicles);

            if ($xmlObject !== false) {
                $vehicles = $this->parseVehicles($xmlObject);
                $optionalExtras = $this->parseOptionalExtras($xmlObject);
                $quoteId = (string) $xmlObject->response->quoteid ?? null; // Extract quoteid from response
            } else {
                Log::error('XML Parsing Error (showGreenMotionCars): Failed to parse XML or no vehicles found.');
                libxml_clear_errors();
            }
        } else {
            Log::error('GreenMotion API returned null or empty XML response for showGreenMotionCars.');
        }

        $location = null;
        $xmlLocationInfo = $this->greenMotionService->getLocationInfo($locationId);
        if (!is_null($xmlLocationInfo) && !empty($xmlLocationInfo)) {
            libxml_use_internal_errors(true);
            $xmlObject = simplexml_load_string($xmlLocationInfo);
            if ($xmlObject !== false) {
                $location = $this->parseLocation($xmlObject, $locationId);
            }
            libxml_clear_errors();
        } else {
            Log::error('GreenMotion API returned null or empty XML response for getLocationInfo for ID: ' . $locationId);
        }

        return Inertia::render('GreenMotionCars', [
            'vehicles' => [
                'data' => $vehicles,
                'links' => [],
            ],
            'locations' => $location ? [$location] : [],
            'optionalExtras' => $optionalExtras,
            'filters' => array_merge($request->all(), ['quoteid' => $quoteId]), // Pass quoteId in filters
            'pagination_links' => '',
            'locale' => app()->getLocale(),
        ]);
    }

    public function showGreenMotionCar(Request $request, $locale, $id)
    {
        $provider = $request->input('provider', 'greenmotion');
        try {
            $this->greenMotionService->setProvider($provider);
        } catch (\Exception $e) {
            return Inertia::render('GreenMotionSingle', [
                'error' => "Invalid provider specified: {$provider}",
                'locale' => $locale,
            ]);
        }
        $locationId = $request->input('location_id', 61627);
        $startDate = $request->input('start_date');
        $startTime = $request->input('start_time', '09:00');
        $endDate = $request->input('end_date');
        $endTime = $request->input('end_time', '09:00');

        // Validate required parameters
        if (!$startDate || !$endDate) {
            return redirect()->route('search', $locale)
                ->with('error', 'Pickup and return dates are required. Please search again.');
        }
        $age = $request->input('age', 35);
        $rentalCode = $request->input('rentalCode', null);

        $vehicleOptions = [
            'rentalCode' => $rentalCode,
            'currency' => $request->input('currency'),
            'fuel' => $request->input('fuel'),
            'userid' => $request->input('userid'),
            'username' => $request->input('username'),
            'language' => $request->input('language'),
            'full_credit' => $request->input('full_credit'),
            'promocode' => $request->input('promocode'),
            'dropoff_location_id' => $request->input('dropoff_location_id'),
        ];

        $xmlVehicles = $this->greenMotionService->getVehicles(
            $locationId,
            $startDate,
            $startTime,
            $endDate,
            $endTime,
            $age,
            $vehicleOptions
        );

        $vehicle = null;
        $optionalExtras = [];
        $quoteId = null; // Initialize quoteId

        Log::info("showGreenMotionCar called with ID: {$id}, Location ID: {$locationId}, Locale: {$locale}");

        if (!is_null($xmlVehicles) && !empty($xmlVehicles)) {
            libxml_use_internal_errors(true);
            $xmlObject = simplexml_load_string($xmlVehicles);

            if ($xmlObject !== false) {
                $vehicles = $this->parseVehicles($xmlObject);
                Log::info('Parsed Vehicles in showGreenMotionCar: ' . json_encode(collect($vehicles)->pluck('id')));

                // Strip provider prefix from ID for matching
                $actualVehicleId = $id;
                if (strpos($id, 'greenmotion_') === 0) {
                    $actualVehicleId = substr($id, strlen('greenmotion_'));
                    Log::info("Stripped greenmotion_ prefix, searching for vehicle ID: {$actualVehicleId}");
                } elseif (strpos($id, 'usave_') === 0) {
                    $actualVehicleId = substr($id, strlen('usave_'));
                    Log::info("Stripped usave_ prefix, searching for vehicle ID: {$actualVehicleId}");
                }

                $vehicle = collect($vehicles)->firstWhere('id', $actualVehicleId);
                $optionalExtras = $this->parseOptionalExtras($xmlObject);
                $quoteId = (string) $xmlObject->response->quoteid ?? null; // Extract quoteid from response

                if ($vehicle && isset($vehicle['products'])) {
                    Log::info('Products for selected vehicle in showGreenMotionCar: ' . json_encode($vehicle['products']));
                }

            } else {
                Log::error('XML Parsing Error (showGreenMotionCar): Failed to parse XML or no vehicles found.');
                libxml_clear_errors();
            }
        } else {
            Log::error('GreenMotion API returned null or empty XML response for showGreenMotionCar.');
        }

        if (!$vehicle) {
            Log::error("Vehicle with ID {$id} not found for location ID {$locationId}. Available vehicles: " . json_encode(collect($vehicles)->pluck('id')));
            return Inertia::render('GreenMotionSingle', [
                'error' => 'Vehicle not found.',
                'locale' => $locale, // Ensure locale is passed correctly
            ]);
        }

        $location = null;
        $xmlLocationInfo = $this->greenMotionService->getLocationInfo($locationId);
        if (!is_null($xmlLocationInfo) && !empty($xmlLocationInfo)) {
            libxml_use_internal_errors(true);
            $xmlObject = simplexml_load_string($xmlLocationInfo);
            if ($xmlObject !== false) {
                $location = $this->parseLocation($xmlObject, $locationId);
            }
            libxml_clear_errors();
        } else {
            Log::error('GreenMotion API returned null or empty XML response for getLocationInfo for ID: ' . $locationId);
        }

        $dropoffLocation = null;
        if ($request->has('dropoff_location_id')) {
            $dropoffLocationId = $request->input('dropoff_location_id');
            $xmlDropoffLocationInfo = $this->greenMotionService->getLocationInfo($dropoffLocationId);
            if (!is_null($xmlDropoffLocationInfo) && !empty($xmlDropoffLocationInfo)) {
                libxml_use_internal_errors(true);
                $xmlObject = simplexml_load_string($xmlDropoffLocationInfo);
                if ($xmlObject !== false) {
                    $dropoffLocation = $this->parseLocation($xmlObject, $dropoffLocationId);
                }
                libxml_clear_errors();
            } else {
                Log::error('GreenMotion API returned null or empty XML response for dropoff getLocationInfo for ID: ' . $dropoffLocationId);
            }
        }

        // Get affiliate data if available
        $affiliateService = new AffiliateQrCodeService();
        $affiliateData = $affiliateService->getAffiliateSessionData();

        return Inertia::render('GreenMotionSingle', [
            'vehicle' => $vehicle,
            'location' => $location,
            'dropoffLocation' => $dropoffLocation,
            'optionalExtras' => $optionalExtras,
            'affiliate_data' => $affiliateData, // Pass affiliate data to the view
            'filters' => array_merge($request->all(), ['quoteid' => $quoteId]), // Pass quoteId in filters
            'locale' => $locale, // Use the $locale parameter from the route
            'seoMeta' => [
                'seo_title' => $vehicle['name'] . ' - GreenMotion Rental',
                'meta_description' => 'Rent the ' . $vehicle['name'] . ' with GreenMotion. Check availability, features, and book your vehicle now.',
                'keywords' => 'GreenMotion, car rental, ' . $vehicle['name'] . ', vehicle hire',
                'url_slug' => 'green-motion-car/' . $id,
                'seo_image_url' => $vehicle['image'] ?? '/default-image.png',
                'canonical_url' => url($locale . '/green-motion-car/' . $id), // Use $locale for canonical URL
                'translations' => [
                    ['locale' => $locale, 'seo_title' => $vehicle['name'] . ' - GreenMotion Rental']
                ],
            ],
        ]);
    }

    public function showGreenMotionBookingPage(Request $request, $locale, $id)
    {
        $provider = $request->input('provider', 'greenmotion');
        try {
            $this->greenMotionService->setProvider($provider);
        } catch (\Exception $e) {
            return Inertia::render('GreenMotionBooking', [
                'error' => "Invalid provider specified: {$provider}",
                'locale' => $locale,
            ]);
        }
        $locationId = $request->input('location_id', 61627);
        $startDate = $request->input('start_date', '2032-01-06');
        $startTime = $request->input('start_time', '09:00');
        $endDate = $request->input('end_date', '2032-01-08');
        $endTime = $request->input('end_time', '09:00');
        $age = $request->input('age', 35);
        $rentalCode = $request->input('rentalCode', null);

        $vehicleOptions = [
            'rentalCode' => $rentalCode,
            'currency' => $request->input('currency'),
            'fuel' => $request->input('fuel'),
            'userid' => $request->input('userid'),
            'username' => $request->input('username'),
            'language' => $request->input('language'),
            'full_credit' => $request->input('full_credit'),
            'promocode' => $request->input('promocode'),
            'dropoff_location_id' => $request->input('dropoff_location_id'),
        ];

        $xmlVehicles = $this->greenMotionService->getVehicles(
            $locationId,
            $startDate,
            $startTime,
            $endDate,
            $endTime,
            $age,
            $vehicleOptions
        );

        $vehicles = [];
        $optionalExtras = [];
        $quoteId = null; // Initialize quoteId

        if (!is_null($xmlVehicles) && !empty($xmlVehicles)) {
            libxml_use_internal_errors(true);
            $xmlObject = simplexml_load_string($xmlVehicles);

            if ($xmlObject !== false) {
                $vehicles = $this->parseVehicles($xmlObject);
                $vehicle = collect($vehicles)->firstWhere('id', $id);
                $optionalExtras = $this->parseOptionalExtras($xmlObject);
                $quoteId = (string) $xmlObject->response->quoteid ?? null; // Extract quoteid from response

                if ($vehicle && isset($vehicle['products'])) {
                    Log::info('Products for selected vehicle in showGreenMotionBookingPage: ' . json_encode($vehicle['products']));
                }

            } else {
                Log::error('XML Parsing Error (showGreenMotionBookingPage): Failed to parse XML or no vehicles found.');
                libxml_clear_errors();
            }
        } else {
            Log::error('GreenMotion API returned null or empty XML response for showGreenMotionBookingPage.');
        }

        if (!$vehicle) {
            return Inertia::render('GreenMotionBooking', [
                'error' => 'Vehicle not found for booking.',
                'locale' => $locale,
            ]);
        }

        $location = null;
        $xmlLocationInfo = $this->greenMotionService->getLocationInfo($locationId);
        if (!is_null($xmlLocationInfo) && !empty($xmlLocationInfo)) {
            libxml_use_internal_errors(true);
            $xmlObject = simplexml_load_string($xmlLocationInfo);
            if ($xmlObject !== false) {
                $location = $this->parseLocation($xmlObject, $locationId);
            }
            libxml_clear_errors();
        } else {
            Log::error('GreenMotion API returned null or empty XML response for getLocationInfo for ID: ' . $locationId);
        }

        $filters = array_merge($request->all(), ['quoteid' => $quoteId]);
        Log::info('Filters passed to GreenMotionBooking.vue: ' . json_encode($filters));

        $dropoffLocation = null;
        if ($request->has('dropoff_location_id')) {
            $dropoffLocationId = $request->input('dropoff_location_id');
            $xmlDropoffLocationInfo = $this->greenMotionService->getLocationInfo($dropoffLocationId);
            if (!is_null($xmlDropoffLocationInfo) && !empty($xmlDropoffLocationInfo)) {
                libxml_use_internal_errors(true);
                $xmlObject = simplexml_load_string($xmlDropoffLocationInfo);
                if ($xmlObject !== false) {
                    $dropoffLocation = $this->parseLocation($xmlObject, $dropoffLocationId);
                }
                libxml_clear_errors();
            } else {
                Log::error('GreenMotion API returned null or empty XML response for dropoff getLocationInfo for ID: ' . $dropoffLocationId);
            }
        }

        // Get affiliate data if available
        $affiliateService = new AffiliateQrCodeService();
        $affiliateData = $affiliateService->getAffiliateSessionData();

        return Inertia::render('GreenMotionBooking', [
            'vehicle' => $vehicle,
            'location' => $location,
            'dropoffLocation' => $dropoffLocation,
            'optionalExtras' => $optionalExtras,
            'affiliate_data' => $affiliateData, // Pass affiliate data to the view
            'filters' => $filters,
            'locale' => $locale,
        ]);
    }

    public function getGreenMotionCountries()
    {
        $xml = $this->greenMotionService->getCountryList();

        if (is_null($xml) || empty($xml)) {
            Log::error('GreenMotion API returned null or empty XML response for GetCountryList.');
            return response()->json(['error' => 'Failed to retrieve country data. API returned empty response.'], 500);
        }

        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xml);

        if ($xmlObject === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                Log::error('XML Parsing Error (GetCountryList): ' . $error->message);
            }
            libxml_clear_errors();
            return response()->json(['error' => 'Failed to parse XML response for country list from API.'], 500);
        }

        $countries = [];
        if (isset($xmlObject->response->CountryList->country)) {
            foreach ($xmlObject->response->CountryList->country as $country) {
                $countries[] = [
                    'countryID' => (string) $country->countryID,
                    'countryName' => (string) $country->countryName,
                    'countryURL' => (string) $country->countryURL,
                    'breakdownInstructions' => (string) $country->breakdownInstructions,
                    'isUSAState' => (string) $country->isUSAState,
                    'driver_requirements' => json_decode(json_encode($country->driver_requirements), true),
                ];
            }
        }
        return response()->json($countries);
    }

    public function getGreenMotionLocations(Request $request)
    {
        $locationId = $request->input('location_id');

        if (empty($locationId)) {
            return response()->json(['error' => 'Location ID is required to get location info.'], 400);
        }

        $xml = $this->greenMotionService->getLocationInfo($locationId);

        if (is_null($xml) || empty($xml)) {
            Log::error('GreenMotion API returned null or empty XML response for GetLocationInfo.');
            return response()->json(['error' => 'Failed to retrieve location data. API returned empty response.'], 500);
        }

        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xml);

        if ($xmlObject === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                Log::error('XML Parsing Error (GetLocationInfo): ' . $error->message);
            }
            libxml_clear_errors();
            return response()->json(['error' => 'Failed to parse XML response for location info from API.'], 500);
        }

        $locationInfo = $this->parseLocation($xmlObject, $locationId);
        return response()->json($locationInfo);
    }

    public function getGreenMotionTermsAndConditions(Request $request)
    {
        $countryId = $request->input('country_id', 1);
        $language = $request->input('language');
        $plaintext = $request->input('plaintext');

        if (empty($countryId)) {
            return response()->json(['error' => 'Country ID is required to get terms and conditions.'], 400);
        }

        $xml = $this->greenMotionService->getTermsAndConditions($countryId, $language, $plaintext);

        if (is_null($xml) || empty($xml)) {
            Log::error('GreenMotion API returned null or empty XML response for GetTermsAndConditions.');
            return response()->json(['error' => 'Failed to retrieve terms and conditions data. API returned empty response.'], 500);
        }

        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xml);

        if ($xmlObject === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                Log::error('XML Parsing Error (GetTermsAndConditions): ' . $error->message);
            }
            libxml_clear_errors();
            return response()->json(['error' => 'Failed to parse XML response for terms and conditions from API.'], 500);
        }

        $categories = [];
        if (isset($xmlObject->response->category)) {
            foreach ($xmlObject->response->category as $category) {
                $conditions = [];
                foreach ($category->condition as $condition) {
                    $conditions[] = (string) $condition;
                }
                $categories[] = [
                    'name' => (string) $category['name'],
                    'conditions' => $conditions,
                ];
            }
        }
        return response()->json($categories);
    }

    public function getGreenMotionRegions(Request $request)
    {
        $countryId = $request->input('country_id', 1);

        if (empty($countryId)) {
            return response()->json(['error' => 'Country ID is required to get regions.'], 400);
        }

        $xml = $this->greenMotionService->getRegionList($countryId);

        if (is_null($xml) || empty($xml)) {
            Log::error('GreenMotion API returned null or empty XML response for GetRegionList.');
            return response()->json(['error' => 'Failed to retrieve region data. API returned empty response.'], 500);
        }

        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xml);

        if ($xmlObject === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                Log::error('XML Parsing Error (GetRegionList): ' . $error->message);
            }
            libxml_clear_errors();
            return response()->json(['error' => 'Failed to parse XML response for region list from API.'], 500);
        }

        $regions = [];
        if (isset($xmlObject->response->RegionList->region)) {
            foreach ($xmlObject->response->RegionList->region as $region) {
                $regions[] = [
                    'name' => (string) $region->name,
                ];
            }
        }
        return response()->json($regions);
    }

    public function getGreenMotionServiceAreas(Request $request)
    {
        $countryId = $request->input('country_id', 1);
        $language = $request->input('language');

        if (empty($countryId)) {
            return response()->json(['error' => 'Country ID is required to get service areas.'], 400);
        }

        $xml = $this->greenMotionService->getServiceAreas($countryId, $language);

        if (is_null($xml) || empty($xml)) {
            Log::error('GreenMotion API returned null or empty XML response for GetServiceAreas.');
            return response()->json(['error' => 'Failed to retrieve service area data. API returned empty response.'], 500);
        }

        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xml);

        if ($xmlObject === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                Log::error('XML Parsing Error (GetServiceAreas): ' . $error->message);
            }
            libxml_clear_errors();
            return response()->json(['error' => 'Failed to parse XML response for service areas from API.'], 500);
        }

        $serviceAreas = [];
        if (isset($xmlObject->response->ServiceAreas->servicearea)) {
            foreach ($xmlObject->response->ServiceAreas->servicearea as $servicearea) {
                $serviceAreas[] = [
                    'locationID' => (string) $servicearea->locationID,
                    'name' => (string) $servicearea->name,
                ];
            }
        }
        return response()->json($serviceAreas);
    }

    /**
     * Get GreenMotion locations for autocomplete based on search term.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGreenMotionLocationsForAutocomplete(Request $request)
    {
        $searchTerm = $request->input('search_term', '');

        $locationsFilePath = public_path('greenmotion_locations.json');

        if (!File::exists($locationsFilePath)) {
            Log::error('GreenMotionLocationsUpdateCommand: greenmotion_locations.json file not found.');
            return response()->json(['error' => 'Location data not available. Please run the update command.'], 500);
        }

        $jsonContent = File::get($locationsFilePath);
        $allLocations = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('GreenMotionLocationsUpdateCommand: Error decoding greenmotion_locations.json: ' . json_last_error_msg());
            return response()->json(['error' => 'Failed to parse location data.'], 500);
        }

        // Filter locations by search term
        $filteredLocations = collect($allLocations)->filter(function ($location) use ($searchTerm) {
            $locationName = (string) $location['name'];
            $match = stripos($locationName, $searchTerm) !== false;
            return empty($searchTerm) || $match;
        })->values()->all(); // Re-index the array

        return response()->json($filteredLocations);
    }

    public function getDropoffLocations(Request $request, $location_id)
    {
        $xml = $this->greenMotionService->getLocationInfo($location_id);

        if (is_null($xml) || empty($xml)) {
            Log::error('GreenMotion API returned null or empty XML response for GetLocationInfo.');
            return response()->json(['error' => 'Failed to retrieve location data. API returned empty response.'], 500);
        }

        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xml);

        if ($xmlObject === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                Log::error('XML Parsing Error (GetLocationInfo for dropoff): ' . $error->message);
            }
            libxml_clear_errors();
            return response()->json(['error' => 'Failed to parse XML response for location info from API.'], 500);
        }

        $dropoffLocationIds = [];
        if (isset($xmlObject->response->location_info->oneway->location_id)) {
            foreach ($xmlObject->response->location_info->oneway->location_id as $id) {
                $dropoffLocationIds[] = (string) $id;
            }
        }

        $locationsFilePath = public_path('unified_locations.json');
        if (!File::exists($locationsFilePath)) {
            return response()->json(['error' => 'Unified locations file not found.'], 500);
        }

        $allLocations = json_decode(File::get($locationsFilePath), true);
        $dropoffLocations = collect($allLocations)->whereIn('greenmotion_location_id', $dropoffLocationIds)->values()->all();

        return response()->json([
            'locations' => $dropoffLocations
        ]);
    }

    public function getDropoffLocationsForProvider(Request $request, $provider, $location_id)
    {
        // Handle OK Mobility - it's one-way rental only, no dropoff locations
        if ($provider === 'okmobility') {
            return response()->json([
                'locations' => [],
                'message' => 'OK Mobility is one-way rental only. Dropoff location is the same as pickup location.'
            ]);
        }

        try {
            $this->greenMotionService->setProvider($provider);
        } catch (\Exception $e) {
            return response()->json(['error' => "Invalid provider: {$provider}"], 400);
        }

        $xml = $this->greenMotionService->getLocationInfo($location_id);

        if (is_null($xml) || empty($xml)) {
            Log::error("{$provider} API returned null or empty XML response for GetLocationInfo.");
            return response()->json(['error' => 'Failed to retrieve location data. API returned empty response.'], 500);
        }

        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xml);

        if ($xmlObject === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                Log::error("XML Parsing Error (GetLocationInfo for dropoff - {$provider}): " . $error->message);
            }
            libxml_clear_errors();
            return response()->json(['error' => 'Failed to parse XML response for location info from API.'], 500);
        }

        $dropoffLocationIds = [];
        if (isset($xmlObject->response->location_info->oneway->location_id)) {
            foreach ($xmlObject->response->location_info->oneway->location_id as $id) {
                $dropoffLocationIds[] = (string) $id;
            }
        }

        $locationsFilePath = public_path('unified_locations.json');
        if (!File::exists($locationsFilePath)) {
            return response()->json(['error' => 'Unified locations file not found.'], 500);
        }

        $allLocations = json_decode(File::get($locationsFilePath), true);

        $dropoffLocations = collect($allLocations)->filter(function ($location) use ($dropoffLocationIds, $provider) {
            if (isset($location['providers'])) {
                foreach ($location['providers'] as $p) {
                    if ($p['provider'] === $provider && in_array($p['pickup_id'], $dropoffLocationIds)) {
                        return true;
                    }
                }
            }
            return false;
        })->values()->all();


        return response()->json([
            'locations' => $dropoffLocations
        ]);
    }

    public function checkAvailability(Request $request)
    {
        $validatedData = $request->validate([
            'location_id' => 'required|string',
            'start_date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_date' => 'required|date_format:Y-m-d',
            'end_time' => 'required|date_format:H:i',
            'age' => 'required|integer',
            'vehicle_id' => 'required|string',
        ]);

        $xml = $this->greenMotionService->getVehicles(
            $validatedData['location_id'],
            $validatedData['start_date'],
            $validatedData['start_time'],
            $validatedData['end_date'],
            $validatedData['end_time'],
            $validatedData['age']
        );

        if (is_null($xml) || empty($xml)) {
            return response()->json(['available' => false, 'error' => 'Failed to retrieve vehicle data.'], 500);
        }

        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xml);

        if ($xmlObject === false) {
            return response()->json(['available' => false, 'error' => 'Failed to parse XML response.'], 500);
        }

        $vehicles = $this->parseVehicles($xmlObject);
        $isAvailable = collect($vehicles)->contains('id', $validatedData['vehicle_id']);

        return response()->json(['available' => $isAvailable]);
    }

    public function makeGreenMotionBooking(Request $request)
    {
        $validatedData = $request->validate([
            'location_id' => 'required|string',
            'dropoff_location_id' => 'nullable|string',
            'start_date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_date' => 'required|date_format:Y-m-d',
            'end_time' => 'required|date_format:H:i',
            'rentalCode' => 'required|string',
            'age' => 'required|integer|min:18',
            'customer.title' => 'nullable|string',
            'customer.firstname' => 'required|string|max:255',
            'customer.surname' => 'required|string|max:255',
            'customer.email' => 'required|email|max:255',
            'customer.phone' => 'required|string|max:20',
            'customer.address1' => 'required|string|max:255',
            'customer.address2' => 'nullable|string|max:255',
            'customer.address3' => 'nullable|string|max:255',
            'customer.town' => 'required|string|max:255',
            'customer.postcode' => 'required|string|max:20',
            'customer.country' => 'required|string|max:100',
            'customer.driver_licence_number' => 'required|string|max:50',
            'customer.flight_number' => 'nullable|string|max:50',
            'customer.comments' => 'nullable|string|max:1000',
            'extras' => 'array',
            'extras.*.id' => 'required|string',
            'extras.*.option_qty' => 'required|integer|min:0',
            'extras.*.option_total' => 'required|numeric|min:0',
            'extras.*.pre_pay' => 'nullable|string|in:Yes,No',
            'vehicle_id' => 'required|string',
            'vehicle_total' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'grand_total' => 'required|numeric|min:0',
            'paymentHandlerRef' => 'nullable|string|max:255',
            'quoteid' => 'required|string|max:255',
            'payment_type' => 'nullable|string|in:POA,PREPAID',
            'customer.bplace' => 'nullable|string|max:255',
            'customer.bdate' => 'nullable|date_format:Y-m-d',
            'customer.idno' => 'nullable|string|max:50',
            'customer.idplace' => 'nullable|string|max:255',
            'customer.idissue' => 'nullable|date_format:Y-m-d',
            'customer.idexp' => 'nullable|date_format:Y-m-d',
            'customer.licissue' => 'nullable|date_format:Y-m-d',
            'customer.licplace' => 'nullable|string|max:255',
            'customer.licexp' => 'nullable|date_format:Y-m-d',
            'customer.idurl' => 'nullable|url',
            'customer.id_rear_url' => 'nullable|url',
            'customer.licurl' => 'nullable|url',
            'customer.lic_rear_url' => 'nullable|url',
            'customer.verification_response' => 'nullable|string|max:1000',
            'customer.custimage' => 'nullable|url',
            'customer.dvlacheckcode' => 'nullable|string|max:50',
            'remarks' => 'nullable|string|max:1000',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $customerDetails = $validatedData['customer'];
        $customerDetails['comments'] = 'Test Booking - ' . ($customerDetails['comments'] ?? '');

        // Determine if a dropoff_location_id should be sent
        $dropoffLocationId = $validatedData['dropoff_location_id'] ?? null;
        if ($dropoffLocationId && $dropoffLocationId === $validatedData['location_id']) {
            $dropoffLocationId = null; // It's not a one-way rental, so don't send the ID
        }

        $xmlResponse = $this->greenMotionService->makeReservation(
            $validatedData['location_id'],
            $validatedData['start_date'],
            $validatedData['start_time'],
            $validatedData['end_date'],
            $validatedData['end_time'],
            $validatedData['age'],
            $customerDetails,
            $validatedData['vehicle_id'],
            $validatedData['vehicle_total'],
            $validatedData['currency'],
            $validatedData['grand_total'],
            $validatedData['paymentHandlerRef'] ?? null,
            $validatedData['quoteid'],
            $validatedData['extras'] ?? [],
            $dropoffLocationId,
            $validatedData['payment_type'] ?? 'POA',
            $validatedData['rentalCode'],
            $validatedData['remarks'] ?? null
        );

        if (is_null($xmlResponse) || empty($xmlResponse)) {
            Log::error('GreenMotion API returned null or empty XML response for MakeReservation.');
            return response()->json(['error' => 'Failed to submit booking. API returned empty response.'], 500);
        }

        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xmlResponse);

        if ($xmlObject === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                Log::error('XML Parsing Error (MakeReservation): ' . $error->message);
            }
            libxml_clear_errors();
            return response()->json(['error' => 'Failed to parse XML response for booking submission from API.'], 500);
        }

        $bookingReference = (string) $xmlObject->response->booking_ref ?? null;
        $status = (string) $xmlObject->response->status ?? 'unknown';

        // Save booking to database
        try {
            $booking = GreenMotionBooking::create([ // Use full namespace
                'greenmotion_booking_ref' => $bookingReference,
                'user_id' => $validatedData['user_id'] ?? null,
                'vehicle_id' => $validatedData['vehicle_id'],
                'location_id' => $validatedData['location_id'],
                'start_date' => $validatedData['start_date'],
                'start_time' => $validatedData['start_time'],
                'end_date' => $validatedData['end_date'],
                'end_time' => $validatedData['end_time'],
                'age' => $validatedData['age'],
                'rental_code' => $validatedData['rentalCode'],
                'customer_details' => $customerDetails,
                'selected_extras' => $validatedData['extras'] ?? [],
                'vehicle_total' => $validatedData['vehicle_total'],
                'currency' => $validatedData['currency'],
                'grand_total' => $validatedData['grand_total'],
                'payment_handler_ref' => $validatedData['paymentHandlerRef'] ?? null,
                'quote_id' => $validatedData['quoteid'],
                'payment_type' => $validatedData['payment_type'] ?? 'POA',
                'dropoff_location_id' => $validatedData['dropoff_location_id'] ?? null,
                'remarks' => $validatedData['remarks'] ?? null,
                'booking_status' => $status,
                'api_response' => json_decode(json_encode($xmlObject), true), // Store full XML response as JSON
            ]);
            Log::info('GreenMotion booking saved to database successfully.');

            // Send notifications
            if ($bookingReference && isset($validatedData['user_id'])) {
                $customer = User::find($validatedData['user_id']);
                $adminEmail = env('VITE_ADMIN_EMAIL', 'default@admin.com');
                $admin = User::where('email', $adminEmail)->first();

                if ($customer) {
                    $customer->notify(new BookingCreatedCustomerNotification($booking, $customer));
                }
                if ($admin) {
                    $admin->notify(new BookingCreatedAdminNotification($booking, $customer));
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to save GreenMotion booking to database: ' . $e->getMessage(), [
                'booking_data' => $validatedData,
                'api_response' => $xmlResponse,
            ]);
            // Continue to return API response even if DB save fails
        }

        if ($bookingReference) {
            return response()->json([
                'message' => 'Booking submitted successfully.',
                'booking_reference' => $bookingReference,
                'status' => $status,
                'api_response' => $xmlResponse,
            ]);
        } else {
            Log::error('GreenMotion API did not return a booking reference for MakeReservation.', ['response' => $xmlResponse]);
            return response()->json(['error' => 'Booking submitted, but no reference received. Please check logs.'], 500);
        }
    }
}
