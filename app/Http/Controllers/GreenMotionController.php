<?php

namespace App\Http\Controllers;

use App\Services\GreenMotionService;
use Illuminate\Http\Request;
use SimpleXMLElement;

class GreenMotionController extends Controller
{
    public function __construct(private GreenMotionService $greenMotionService)
    {
    }

    public function getGreenMotionVehicles()
    {
        $xml = $this->greenMotionService->getVehicles(61627, '2032-01-06', '09:00', '2032-01-08', '09:00', 1, 35);

        // Add robust XML parsing and error handling
        if (is_null($xml) || empty($xml)) {
            \Log::error('GreenMotion API returned null or empty XML response.');
            return response()->json(['error' => 'Failed to retrieve vehicle data. API returned empty response.'], 500);
        }

        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xml);
        
        if ($xmlObject === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                \Log::error('XML Parsing Error: ' . $error->message);
            }
            libxml_clear_errors();
            return response()->json(['error' => 'Failed to parse XML response from API.'], 500);
        }
        // End of robust XML parsing and error handling

        $vehicles = [];
        // Check if the expected path exists before iterating
        if (isset($xmlObject->response->vehicles->vehicle)) {
            foreach ($xmlObject->response->vehicles->vehicle as $vehicle) {
                $products = [];
                foreach ($vehicle->product as $product) {
                    $products[] = [
                        'type' => (string) $product['type'],
                        'total' => (string) $product->total,
                        'currency' => (string) $product->total['currency'],
                        'deposit' => (string) $product->deposit,
                        'excess' => (string) $product->excess,
                        'fuelpolicy' => (string) $product->fuelpolicy,
                        'purpose' => (string) $product->purpose,
                        'mileage' => (string) $product->mileage,
                        'costperextradistance' => (string) $product->costperextradistance,
                        'minage' => (string) $product->minage,
                        'excludedextras' => (string) $product->excludedextras,
                    ];
                }

                $vehicles[] = [
                    'name' => (string) $vehicle['name'],
                    'id' => (string) $vehicle['id'],
                    'image' => urldecode((string) $vehicle['image']),
                    'products' => $products,
                    'groupName' => (string) $vehicle->groupName,
                    'adults' => (string) $vehicle->adults,
                    'children' => (string) $vehicle->children,
                    'luggageSmall' => (string) $vehicle->luggageSmall,
                    'luggageMed' => (string) $vehicle->luggageMed,
                    'luggageLarge' => (string) $vehicle->luggageLarge,
                    'fuel' => (string) $vehicle->fuel,
                    'mpg' => (string) $vehicle->mpg,
                    'acriss' => (string) $vehicle->acriss,
                    'co2' => (string) $vehicle->co2,
                    'carorvan' => (string) $vehicle->carorvan,
                    'airConditioning' => (string) $vehicle->airConditioning,
                    'refrigerated' => (string) $vehicle->refrigerated,
                    'keyngo' => (string) $vehicle->keyngo,
                    'transmission' => (string) $vehicle->transmission,
                    'paymentURL' => (string) $vehicle->paymentURL,
                    'driveandgo' => (string) $vehicle->driveandgo,
                ];
            }
        }
        return response()->json($vehicles);
    }

    public function getGreenMotionCountries()
    {
        $xml = $this->greenMotionService->getCountryList();

        if (is_null($xml) || empty($xml)) {
            \Log::error('GreenMotion API returned null or empty XML response for GetCountryList.');
            return response()->json(['error' => 'Failed to retrieve country data. API returned empty response.'], 500);
        }

        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xml);

        if ($xmlObject === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                \Log::error('XML Parsing Error (GetCountryList): ' . $error->message);
            }
            libxml_clear_errors();
            return response()->json(['error' => 'Failed to parse XML response for country list from API.'], 500);
        }

        $countries = [];
        if (isset($xmlObject->response->countries->country)) {
            foreach ($xmlObject->response->countries->country as $country) {
                $countries[] = [
                    'id' => (string) $country['id'],
                    'name' => (string) $country['name'],
                ];
            }
        }
        return response()->json($countries);
    }

    public function getGreenMotionLocations(Request $request)
    {
        $countryId = $request->input('country_id');

        if (empty($countryId)) {
            return response()->json(['error' => 'Country ID is required to get locations.'], 400);
        }

        $xml = $this->greenMotionService->getLocationList($countryId);

        if (is_null($xml) || empty($xml)) {
            \Log::error('GreenMotion API returned null or empty XML response for GetLocationList.');
            return response()->json(['error' => 'Failed to retrieve location data. API returned empty response.'], 500);
        }

        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xml);

        if ($xmlObject === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                \Log::error('XML Parsing Error (GetLocationList): ' . $error->message);
            }
            libxml_clear_errors();
            return response()->json(['error' => 'Failed to parse XML response for location list from API.'], 500);
        }

        $locations = [];
        if (isset($xmlObject->response->locations->location)) {
            foreach ($xmlObject->response->locations->location as $location) {
                $locations[] = [
                    'id' => (string) $location['id'],
                    'name' => (string) $location['name'],
                    'address' => (string) $location->address,
                    'phone' => (string) $location->phone,
                    'email' => (string) $location->email,
                    'latitude' => (string) $location->latitude,
                    'longitude' => (string) $location->longitude,
                ];
            }
        }
        return response()->json($locations);
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
            \Log::error('GreenMotion API returned null or empty XML response for GetTermsAndConditions.');
            return response()->json(['error' => 'Failed to retrieve terms and conditions data. API returned empty response.'], 500);
        }

        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xml);

        if ($xmlObject === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                \Log::error('XML Parsing Error (GetTermsAndConditions): ' . $error->message);
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
            \Log::error('GreenMotion API returned null or empty XML response for GetRegionList.');
            return response()->json(['error' => 'Failed to retrieve region data. API returned empty response.'], 500);
        }

        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xml);

        if ($xmlObject === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                \Log::error('XML Parsing Error (GetRegionList): ' . $error->message);
            }
            libxml_clear_errors();
            return response()->json(['error' => 'Failed to parse XML response for region list from API.'], 500);
        }

        $regions = [];
        if (isset($xmlObject->response->region)) {
            foreach ($xmlObject->response->region as $region) {
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
            \Log::error('GreenMotion API returned null or empty XML response for GetServiceAreas.');
            return response()->json(['error' => 'Failed to retrieve service area data. API returned empty response.'], 500);
        }

        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xml);

        if ($xmlObject === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                \Log::error('XML Parsing Error (GetServiceAreas): ' . $error->message);
            }
            libxml_clear_errors();
            return response()->json(['error' => 'Failed to parse XML response for service areas from API.'], 500);
        }

        $serviceAreas = [];
        if (isset($xmlObject->response->servicearea)) {
            foreach ($xmlObject->response->servicearea as $servicearea) {
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
            $validatedData['rentalCode'],
            $validatedData['age'],
            $customerDetails,
            $validatedData['extras'],
            $validatedData['dropoff_location_id'] ?? null
        );

        if (is_null($xmlResponse) || empty($xmlResponse)) {
            \Log::error('GreenMotion API returned null or empty XML response for MakeReservation.');
            return response()->json(['error' => 'Failed to submit booking. API returned empty response.'], 500);
        }

        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xmlResponse);

        if ($xmlObject === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                \Log::error('XML Parsing Error (MakeReservation): ' . $error->message);
            }
            libxml_clear_errors();
            return response()->json(['error' => 'Failed to parse XML response for booking submission from API.'], 500);
        }

        // Assuming the API response for MakeReservation contains a booking reference
        $bookingReference = (string) $xmlObject->response->booking_reference ?? null;
        $status = (string) $xmlObject->response->status ?? 'unknown'; // Adjust based on actual API response

        if ($bookingReference) {
            return response()->json([
                'message' => 'Booking submitted successfully.',
                'booking_reference' => $bookingReference,
                'status' => $status,
                'api_response' => $xmlResponse, // Include raw XML for debugging if needed
            ]);
        } else {
            \Log::error('GreenMotion API did not return a booking reference for MakeReservation.', ['response' => $xmlResponse]);
            return response()->json(['error' => 'Booking submitted, but no reference received. Please check logs.'], 500);
        }
    }
}
