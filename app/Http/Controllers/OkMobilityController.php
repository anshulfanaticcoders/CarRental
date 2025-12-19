<?php

namespace App\Http\Controllers;

use App\Services\OkMobilityService;
use Illuminate\Http\Request;
use SimpleXMLElement;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Models\OkMobilityBooking;
use App\Models\User;
use App\Notifications\OkMobilityBooking\BookingCreatedAdminNotification;
use App\Notifications\OkMobilityBooking\BookingCreatedCustomerNotification;
use App\Services\Affiliate\AffiliateQrCodeService;

class OkMobilityController extends Controller
{
    public function __construct(private OkMobilityService $okMobilityService)
    {
    }

    private function parseVehicles(SimpleXMLElement $xmlObject): array
    {
        $vehicles = [];

        // Try different namespace patterns
        $xmlObject->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xmlObject->registerXPathNamespace('get', 'http://www.OKGroup.es/RentaCarWebService/getWSDL');
        $xmlObject->registerXPathNamespace('ns', 'http://tempuri.org/');

        // Check for error codes in the response
        $errorCode = (string) ($xmlObject->xpath('//errorCode')[0] ?? $xmlObject->xpath('//get:errorCode')[0] ?? null);
        if ($errorCode && $errorCode !== 'SUCCESS') {
            Log::warning("OK Mobility API returned error in parseVehicles: {$errorCode}");
        }

        $vehicleNodes = $xmlObject->xpath('//get:getMultiplePrice') ?:
            $xmlObject->xpath('//ns:getMultiplePrice') ?:
            $xmlObject->xpath('//getMultiplePrice') ?:
            $xmlObject->xpath('//getMultiplePricesResult/getMultiplePrice');

        if (empty($vehicleNodes) && (!$errorCode || $errorCode === 'SUCCESS')) {
            Log::info("OK Mobility: No vehicles found in XML in parseVehicles, but no error code was present.");
        }

        if ($vehicleNodes) {
            foreach ($vehicleNodes as $vehicle) {
                $vehicleData = json_decode(json_encode($vehicle), true);

                // Parse pricing from OK Mobility format
                $previewValue = $vehicleData['previewValue'] ?? '0.00';
                $valueWithoutTax = $vehicleData['valueWithoutTax'] ?? '0.00';
                $taxRate = $vehicleData['taxRate'] ?? 21;
                $currency = 'EUR'; // OK Mobility uses EUR

                // Parse extras from OK Mobility format
                $extras = [];
                if (isset($vehicleData['allExtras']['allExtra'])) {
                    $extrasData = $vehicleData['allExtras']['allExtra'];
                    if (!isset($extrasData[0])) {
                        $extrasData = [$extrasData];
                    }

                    foreach ($extrasData as $extra) {
                        $extras[] = [
                            'id' => $extra['extraID'] ?? '',
                            'name' => $extra['extra'] ?? '',
                            'description' => $extra['description'] ?? '',
                            'price' => $extra['value'] ?? '0.00',
                            'priceWithTax' => $extra['valueWithTax'] ?? '0.00',
                            'taxRate' => $extra['taxRate'] ?? '21',
                            'included' => ($extra['extra_Included'] ?? 'false') === 'true',
                            'required' => ($extra['extra_Required'] ?? 'false') === 'true',
                            'accepted' => ($extra['extra_Accepted'] ?? 'false') === 'true',
                            'insurance' => ($extra['insurance'] ?? 'false') === 'true',
                            'excess' => $extra['excess'] ?? '0',
                            'pricePerContract' => ($extra['pricePerContract'] ?? 'false') === 'true',
                            'accept_quantity' => ($extra['accept_quantity'] ?? 'false') === 'true',
                        ];
                    }
                }

                // Determine mileage policy
                $kmsIncluded = ($vehicleData['kmsIncluded'] ?? 'false') === 'true';
                $mileagePolicy = $kmsIncluded ? 'Unlimited' : 'Limited';

                $vehicles[] = [
                    'id' => 'okmobility_' . ($vehicleData['GroupID'] ?? '') . '_' . md5($vehicleData['token'] ?? ''),
                    'source' => 'okmobility',
                    'name' => $vehicleData['Group_Name'] ?? 'Unknown Vehicle',
                    'brand' => 'OK Mobility',
                    'model' => $vehicleData['Group_Name'] ?? 'Unknown Vehicle',
                    'image' => $vehicleData['imageURL'] ?? '/default-image.png',
                    'category' => $vehicleData['Group_Name'] ?? 'Unknown',
                    'transmission' => 'Manual', // OK Mobility doesn't provide this in getMultiplePrices
                    'fuel' => 'Petrol', // OK Mobility doesn't provide this in getMultiplePrices
                    'seating_capacity' => 5, // Default for OK Mobility
                    'doors' => 3, // Default for OK Mobility
                    'air_conditioning' => true, // Assume OK Mobility vehicles have AC
                    'mileage' => $mileagePolicy,
                    'fuel_policy' => 'Full-to-Full', // Default for OK Mobility
                    'min_age' => 21, // Default minimum age
                    'price_per_day' => (float) ($vehicleData['dayValue'] ?? 0),
                    'price_per_week' => null, // Calculate later if needed
                    'price_per_month' => null, // Calculate later if needed
                    'currency' => $currency,
                    'token' => $vehicleData['token'] ?? '',
                    'group_id' => $vehicleData['GroupID'] ?? '',
                    'sipp' => $vehicleData['SIPP'] ?? '',
                    'preview_value' => (float) $previewValue,
                    'value_without_tax' => (float) $valueWithoutTax,
                    'tax_rate' => intval($taxRate),
                    'tax_value' => (float) $previewValue - (float) $valueWithoutTax,
                    'extras' => $extras,
                    'extras_included' => $vehicleData['extrasIncluded'] ?? '',
                    'extras_required' => $vehicleData['extrasRequired'] ?? '',
                    'extras_accepted' => $vehicleData['extrasAccepted'] ?? '',
                    'extras_available' => $vehicleData['extrasAvailable'] ?? '',
                    'dynamic_rate' => ($vehicleData['dynamicRate'] ?? 'false') === 'true',
                    'station_id' => $vehicleData['stationID'] ?? '',
                    'station' => $vehicleData['Station'] ?? '',
                ];
            }
        }

        return $vehicles;
    }

    public function showOkMobilityCar(Request $request, $locale, $id)
    {
        try {
            $validated = $request->validate([
                'where' => 'nullable|string',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'start_date' => 'required|date',
                'start_time' => 'required|date_format:H:i',
                'end_date' => 'required|date|after:start_date',
                'end_time' => 'required|date_format:H:i',
                'age' => 'nullable|integer|min:21',
                'location_id' => 'required|string',
                'currency' => 'nullable|string|max:3',
            ]);

            // Map parameters for consistency with getOkMobilityVehicles
            $validated['where'] = $validated['where'] ?? 'Unknown';
            $validated['age'] = $validated['age'] ?? 35;
            $validated['provider_pickup_id'] = $validated['location_id'];
            $validated['date_from'] = $validated['start_date'];
            $validated['date_to'] = $validated['end_date'];

            $vehicles = $this->getOkMobilityVehicles($validated);
            $vehicle = collect($vehicles)->firstWhere('id', $id);

            if (!$vehicle) {
                return redirect()->route('search', [$locale])->with('error', 'Vehicle not found.');
            }

            // Check availability
            $availability = $this->checkVehicleAvailability($validated, $vehicle);

            return Inertia::render('OkMobilitySingle', [
                'vehicle' => $vehicle,
                'search_data' => $validated,
                'availability' => $availability,
                'locale' => $locale,
            ]);

        } catch (\Exception $e) {
            Log::error('OK Mobility Car Details Error: ' . $e->getMessage(), [
                'vehicle_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('search', [$locale])->with('error', 'Unable to fetch vehicle details. Please try again.');
        }
    }

    public function showOkMobilityBookingPage(Request $request, $locale, $id)
    {
        try {
            $validated = $request->validate([
                'where' => 'nullable|string',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'start_date' => 'required|date',
                'start_time' => 'required|date_format:H:i',
                'end_date' => 'required|date|after:start_date',
                'end_time' => 'required|date_format:H:i',
                'age' => 'nullable|integer|min:21',
                'location_id' => 'required|string',
                'currency' => 'nullable|string|max:3',
            ]);

            // Map parameters for consistency
            $validated['where'] = $validated['where'] ?? 'Unknown';
            $validated['age'] = $validated['age'] ?? 35;
            $validated['provider_pickup_id'] = $validated['location_id'];
            $validated['date_from'] = $validated['start_date'];
            $validated['date_to'] = $validated['end_date'];

            $vehicles = $this->getOkMobilityVehicles($validated);
            $vehicle = collect($vehicles)->firstWhere('id', $id);

            if (!$vehicle) {
                return redirect()->route('search', [$locale])->with('error', 'Vehicle not found.');
            }

            return Inertia::render('OkMobilityBooking', [
                'vehicle' => $vehicle,
                'search_data' => $validated,
                'locale' => $locale,
            ]);

        } catch (\Exception $e) {
            Log::error('OK Mobility Booking Page Error: ' . $e->getMessage(), [
                'vehicle_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('search', [$locale])->with('error', 'Unable to load booking page. Please try again.');
        }
    }

    public function checkAvailability(Request $request)
    {
        try {
            $validated = $request->validate([
                'location_id' => 'required|string',
                'start_date' => 'required|date',
                'start_time' => 'required|date_format:H:i',
                'end_date' => 'required|date|after:start_date',
                'end_time' => 'required|date_format:H:i',
                'age' => 'nullable|integer|min:21',
                'token' => 'required|string',
                'group_id' => 'required|string',
            ]);

            $response = $this->okMobilityService->getVehicles(
                $validated['location_id'],
                $validated['location_id'], // OK Mobility is one-way only
                $validated['start_date'],
                $validated['start_time'],
                $validated['end_date'],
                $validated['end_time'],
                [],
                $validated['group_id']
            );

            if ($response) {
                $xmlObject = simplexml_load_string($response);
                if ($xmlObject) {
                    $vehicles = $this->parseVehicles($xmlObject);
                    $vehicle = collect($vehicles)->firstWhere('token', $validated['token']);

                    return response()->json([
                        'available' => !empty($vehicle),
                        'vehicle' => $vehicle ?? null,
                        'message' => !empty($vehicle) ? 'Vehicle available' : 'Vehicle not available for selected dates'
                    ]);
                }
            }

            return response()->json([
                'available' => false,
                'vehicle' => null,
                'message' => 'Unable to check availability'
            ]);

        } catch (\Exception $e) {
            Log::error('OK Mobility Availability Check Error: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'available' => false,
                'vehicle' => null,
                'message' => 'Error checking availability'
            ]);
        }
    }

    private function getOkMobilityVehicles($searchParams): array
    {
        try {
            $response = $this->okMobilityService->getVehicles(
                $searchParams['provider_pickup_id'],
                $searchParams['provider_pickup_id'], // OK Mobility is one-way only
                $searchParams['date_from'],
                $searchParams['start_time'],
                $searchParams['date_to'],
                $searchParams['end_time']
            );

            if ($response) {
                $xmlObject = simplexml_load_string($response);
                if ($xmlObject) {
                    $vehicles = $this->parseVehicles($xmlObject);
                    // Add location data to vehicles
                    foreach ($vehicles as &$vehicle) {
                        $vehicle['latitude'] = $searchParams['latitude'] ?? 0;
                        $vehicle['longitude'] = $searchParams['longitude'] ?? 0;
                        $vehicle['location'] = $searchParams['where'];
                        $vehicle['provider_pickup_id'] = $searchParams['provider_pickup_id'];
                    }
                    return $vehicles;
                }
            }

            Log::warning('OK Mobility API returned empty or invalid response');
            return [];

        } catch (\Exception $e) {
            Log::error('OK Mobility getVehicles Error: ' . $e->getMessage(), [
                'search_params' => $searchParams,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    private function checkVehicleAvailability($searchParams, $vehicle): array
    {
        try {
            $response = $this->okMobilityService->getVehicles(
                $searchParams['provider_pickup_id'],
                $searchParams['provider_pickup_id'], // OK Mobility is one-way only
                $searchParams['date_from'],
                $searchParams['start_time'],
                $searchParams['date_to'],
                $searchParams['end_time'],
                [],
                $vehicle['group_id']
            );

            if ($response) {
                $xmlObject = simplexml_load_string($response);
                if ($xmlObject) {
                    $vehicles = $this->parseVehicles($xmlObject);
                    $availableVehicle = collect($vehicles)->firstWhere('token', $vehicle['token']);

                    return [
                        'available' => !empty($availableVehicle),
                        'message' => !empty($availableVehicle) ? 'Vehicle available' : 'Vehicle not available for selected dates',
                        'vehicle' => $availableVehicle ?? null
                    ];
                }
            }

            return ['available' => false, 'message' => 'Unable to check availability', 'vehicle' => null];

        } catch (\Exception $e) {
            Log::error('OK Mobility Availability Check Error: ' . $e->getMessage());
            return ['available' => false, 'message' => 'Error checking availability', 'vehicle' => null];
        }
    }
}