<?php

namespace App\Http\Controllers;

use App\Services\GreenMotionService;
use Illuminate\Http\Request;
use SimpleXMLElement;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log; // Import Log facade

class GreenMotionController extends Controller
{
    public function __construct(private GreenMotionService $greenMotionService)
    {
    }

    public function getGreenMotionVehicles(Request $request)
    {
        $locationId = $request->input('location_id', 61627); // Default location ID for testing
        $startDate = $request->input('start_date', '2032-01-06');
        $startTime = $request->input('start_time', '09:00');
        $endDate = $request->input('end_date', '2032-01-08');
        $endTime = $request->input('end_time', '09:00');
        $age = $request->input('age', 35);
        $rentalCode = $request->input('rentalCode', null); // Make rentalCode optional for getVehicles

        $options = [
            'rentalCode' => $rentalCode,
            // Add other optional parameters as needed from the request
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
            return response()->json(['error' => 'Failed to parse XML response from API.'], 500);
        }

        $vehicles = [];
        $optionalExtras = [];

        if (isset($xmlObject->response->vehicles->vehicle)) {
            foreach ($xmlObject->response->vehicles->vehicle as $vehicle) {
                $products = [];
                if (isset($vehicle->product)) {
                    foreach ($vehicle->product as $product) {
                        $products[] = [
                            'type' => (string) $product['type'],
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
                            'cancellation_rules' => json_decode(json_encode($product->CancellationRules), true), // Convert SimpleXMLElement to array
                        ];
                    }
                }

                $vehicleOptionsData = []; // Initialize here
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

                $insuranceOptionsData = []; // Initialize here
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

        if (isset($xmlObject->response->optionalextras->extra)) {
            foreach ($xmlObject->response->optionalextras->extra as $extra) {
                $extraOptions = [];
                if (isset($extra->options->option)) { // For advanced extras
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
                } else { // For simple extras
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
                    continue; // Skip to next extra if it's a simple one
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

        return response()->json([
            'vehicles' => $vehicles,
            'optionalExtras' => $optionalExtras,
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
        if (isset($xmlObject->response->CountryList->country)) { // Corrected path
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
        $locationId = $request->input('location_id'); // Changed from country_id to location_id

        if (empty($locationId)) {
            return response()->json(['error' => 'Location ID is required to get location info.'], 400);
        }

        $xml = $this->greenMotionService->getLocationInfo($locationId); // Changed to getLocationInfo

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

        $locationInfo = null;
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

            $locationInfo = [
                'location_name' => (string) $loc->location_name,
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
        return response()->json($locationInfo);
    }

    public function getGreenMotionTermsAndConditions(Request $request)
    {
        $countryId = $request->input('country_id', 1); // Default to 1 for testing
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
        $countryId = $request->input('country_id', 1); // Default to 1 for testing

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
        if (isset($xmlObject->response->RegionList->region)) { // Corrected path
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
        $countryId = $request->input('country_id', 1); // Default to 1 for testing
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
        if (isset($xmlObject->response->ServiceAreas->servicearea)) { // Corrected path
            foreach ($xmlObject->response->ServiceAreas->servicearea as $servicearea) {
                $serviceAreas[] = [
                    'locationID' => (string) $servicearea->locationID,
                    'name' => (string) $servicearea->name,
                ];
            }
        }
        return response()->json($serviceAreas);
    }

    public function makeGreenMotionBooking(Request $request)
    {
        $validatedData = $request->validate([
            'location_id' => 'required|string',
            'start_date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_date' => 'required|date_format:Y-m-d',
            'end_time' => 'required|date_format:H:i',
            'rentalCode' => 'required|string',
            'age' => 'required|integer',
            'dropoff_location_id' => 'nullable|string',
            'customer.title' => 'nullable|string',
            'customer.firstname' => 'required|string',
            'customer.surname' => 'required|string',
            'customer.email' => 'required|email',
            'customer.phone' => 'required|string',
            'customer.address1' => 'required|string',
            'customer.address2' => 'nullable|string',
            'customer.address3' => 'nullable|string',
            'customer.town' => 'required|string',
            'customer.postcode' => 'required|string',
            'customer.country' => 'required|string',
            'customer.driver_licence_number' => 'required|string',
            'customer.flight_number' => 'nullable|string',
            'customer.comments' => 'nullable|string',
            'extras' => 'array',
            'extras.*.id' => 'required|string',
            'extras.*.option_qty' => 'required|integer',
            'extras.*.option_total' => 'required|numeric',
            'extras.*.pre_pay' => 'nullable|string',
            'vehicle_id' => 'required|string',
            'vehicle_total' => 'required|numeric',
            'currency' => 'required|string',
            'grand_total' => 'required|numeric',
            'paymentHandlerRef' => 'nullable|string',
            'quoteid' => 'required|string',
            'payment_type' => 'nullable|string',
            'customer.bplace' => 'nullable|string',
            'customer.bdate' => 'nullable|date_format:Y-m-d',
            'customer.idno' => 'nullable|string',
            'customer.idplace' => 'nullable|string',
            'customer.idissue' => 'nullable|date_format:Y-m-d',
            'customer.idexp' => 'nullable|date_format:Y-m-d',
            'customer.licissue' => 'nullable|date_format:Y-m-d',
            'customer.licplace' => 'nullable|string',
            'customer.licexp' => 'nullable|date_format:Y-m-d',
            'customer.idurl' => 'nullable|url',
            'customer.id_rear_url' => 'nullable|url',
            'customer.licurl' => 'nullable|url',
            'customer.lic_rear_url' => 'nullable|url',
            'customer.verification_response' => 'nullable|string',
            'customer.custimage' => 'nullable|url',
            'customer.dvlacheckcode' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        $customerDetails = $validatedData['customer'];
        // Add "Test Booking - " prefix to comments
        $customerDetails['comments'] = 'Test Booking - ' . ($customerDetails['comments'] ?? '');

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
            $validatedData['dropoff_location_id'] ?? null,
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

        // Assuming the API response for MakeReservation contains a booking reference
        $bookingReference = (string) $xmlObject->response->booking_ref ?? null; // Corrected to booking_ref
        $status = (string) $xmlObject->response->status ?? 'unknown'; // Adjust based on actual API response

        if ($bookingReference) {
            return response()->json([
                'message' => 'Booking submitted successfully.',
                'booking_reference' => $bookingReference,
                'status' => $status,
                'api_response' => $xmlResponse, // Include raw XML for debugging if needed
            ]);
        } else {
            Log::error('GreenMotion API did not return a booking reference for MakeReservation.', ['response' => $xmlResponse]);
            return response()->json(['error' => 'Booking submitted, but no reference received. Please check logs.'], 500);
        }
    }

    public function showGreenMotionCars(Request $request)
    {
        $locationId = $request->input('location_id', 61627); // Default location ID for testing
        $startDate = $request->input('start_date', '2032-01-06');
        $startTime = $request->input('start_time', '09:00');
        $endDate = $request->input('end_date', '2032-01-08');
        $endTime = $request->input('end_time', '09:00');
        $age = $request->input('age', 35);
        $rentalCode = $request->input('rentalCode', null); // Make rentalCode optional for getVehicles

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

        if (!is_null($xmlVehicles) && !empty($xmlVehicles)) {
            libxml_use_internal_errors(true);
            $xmlObject = simplexml_load_string($xmlVehicles);

            if ($xmlObject !== false && isset($xmlObject->response->vehicles->vehicle)) {
                foreach ($xmlObject->response->vehicles->vehicle as $vehicle) {
                    $products = [];
                    if (isset($vehicle->product)) {
                        foreach ($vehicle->product as $product) {
                            $products[] = [
                                'type' => (string) $product['type'],
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
                            ];
                        }
                    }

                    $vehicleOptionsData = []; // Initialize here
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

                    $insuranceOptionsData = []; // Initialize here
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
            } else {
                Log::error('XML Parsing Error (showGreenMotionCars): Failed to parse XML or no vehicles found.');
            }
            libxml_clear_errors();
        } else {
            Log::error('GreenMotion API returned null or empty XML response for showGreenMotionCars.');
        }

        // Fetch single location info for the map
        $location = null;
        $xmlLocationInfo = $this->greenMotionService->getLocationInfo($locationId);
        if (!is_null($xmlLocationInfo) && !empty($xmlLocationInfo)) {
            libxml_use_internal_errors(true);
            $xmlObject = simplexml_load_string($xmlLocationInfo);
            if ($xmlObject !== false && isset($xmlObject->response->location_info)) {
                $loc = $xmlObject->response->location_info;
                $location = [
                    'id' => $locationId, // Use the requested locationId
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
                    'opening_hours' => json_decode(json_encode($loc->opening_hours->day), true),
                    'office_opening_hours' => json_decode(json_encode($loc->office_opening_hours->day), true),
                    'out_of_hours' => json_decode(json_encode($loc->out_of_hours->day), true),
                    'out_of_hours_dropoff' => json_decode(json_encode($loc->out_of_hours_dropoff->day), true),
                    'daytime_closures_hours' => json_decode(json_encode($loc->daytime_closures_hours->day), true),
                    'out_of_hours_charge' => (string) $loc->out_of_hours_charge,
                    'charge_both_ways' => (string) $loc->charge_both_ways,
                    'extra' => (string) $loc->extra,
                ];
            }
            libxml_clear_errors();
        } else {
            Log::error('GreenMotion API returned null or empty XML response for getLocationInfo for ID: ' . $locationId);
        }

        return Inertia::render('GreenMotionCars', [
            'vehicles' => [
                'data' => $vehicles,
                'links' => [], // No pagination for now
            ],
            'locations' => $location ? [$location] : [], // Pass as an array containing the single location or empty
            'optionalExtras' => $optionalExtras, // Pass optional extras to frontend
            'filters' => $request->all(),
            'pagination_links' => '', // No pagination for now
            'locale' => app()->getLocale(),
        ]);
    }
}
