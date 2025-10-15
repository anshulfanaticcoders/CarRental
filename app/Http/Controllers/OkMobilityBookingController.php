<?php

namespace App\Http\Controllers;

use App\Models\OkMobilityBooking;
use App\Services\OkMobilityService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class OkMobilityBookingController extends Controller
{
    public function __construct(private OkMobilityService $okMobilityService)
    {
    }

    /**
     * Show the vehicle details page for a specific OK Mobility vehicle.
     */
    public function showVehicleDetails(Request $request, $locale, $id)
    {
        $validated = $request->validate([
            'pickup_station_id' => 'required|string',
            'dropoff_station_id' => 'required|string',
            'pickup_date' => 'required|date',
            'pickup_time' => 'required|string',
            'dropoff_date' => 'required|date',
            'dropoff_time' => 'required|string',
        ]);

        // The ID from the route is expected to be in the format 'okmobility_{GroupID}_{token_hash}'
        $parts = explode('_', $id);
        $groupId = $parts[1] ?? null;

        if (!$groupId) {
            abort(404, 'Vehicle group ID not found.');
        }

        try {
            $okMobilityResponse = $this->okMobilityService->getVehicles(
                $validated['pickup_station_id'],
                $validated['dropoff_station_id'],
                $validated['pickup_date'],
                $validated['pickup_time'],
                $validated['dropoff_date'],
                $validated['dropoff_time'],
                [],
                $groupId
            );

            if (!$okMobilityResponse) {
                abort(500, 'Failed to retrieve vehicle details from OK Mobility.');
            }

            libxml_use_internal_errors(true);
            $xmlObject = simplexml_load_string($okMobilityResponse);

            if ($xmlObject === false) {
                abort(500, 'Failed to parse XML response from OK Mobility.');
            }

            $xmlObject->registerXPathNamespace('get', 'http://www.OKGroup.es/RentaCarWebService/getWSDL');
            $vehicleNode = $xmlObject->xpath('//get:getMultiplePrice[get:GroupID="' . $groupId . '"]');

            if (empty($vehicleNode)) {
                abort(404, 'Vehicle not found.');
            }

            $vehicleData = json_decode(json_encode($vehicleNode[0]), true);
            $brandName = explode(' ', $vehicleData['Group_Name'])[0] ?? 'Unknown';

            $vehicle = (object) [
                'id' => 'okmobility_' . $vehicleData['GroupID'] . '_' . md5($vehicleData['token']),
                'source' => 'okmobility',
                'brand' => $brandName,
                'model' => $vehicleData['Group_Name'],
                'image' => $vehicleData['imageURL'] ?? null,
                'price_per_day' => (float) $vehicleData['totalDayValueWithTax'],
                'currency' => 'EUR',
                'transmission' => 'Unknown',
                'fuel' => 'Unknown',
                'seating_capacity' => 4,
                'mileage' => $vehicleData['kmsIncluded'] === 'true' ? 'Unlimited' : 'Limited',
                'benefits' => (object) [
                    'cancellation_available_per_day' => true,
                    'limited_km_per_day' => $vehicleData['kmsIncluded'] !== 'true',
                    'minimum_driver_age' => 21,
                    'fuel_policy' => 'Unknown',
                ],
                'extras' => $vehicleData['allExtras']['allExtra'] ?? [],
                'ok_mobility_token' => $vehicleData['token'],
            ];

            return Inertia::render('OkMobilitySingle', [
                'vehicle' => $vehicle,
                'searchParams' => $validated,
            ]);

        } catch (\Exception $e) {
            Log::error("Error fetching OK Mobility vehicle details: " . $e->getMessage());
            abort(500, 'An error occurred while fetching vehicle details.');
        }
    }

    /**
     * Show the booking page for a specific OK Mobility vehicle.
     */
    public function showBookingPage(Request $request)
    {
        $validated = $request->validate([
            'pickup_station_id' => 'required|string',
            'dropoff_station_id' => 'required|string',
            'pickup_date' => 'required|date',
            'pickup_time' => 'required|string',
            'dropoff_date' => 'required|date',
            'dropoff_time' => 'required|string',
            'vehicle' => 'required|json',
        ]);

        $vehicle = json_decode($validated['vehicle']);

        return Inertia::render('OkMobilityBooking', [
            'vehicle' => $vehicle,
            'searchParams' => $validated,
        ]);
    }

    /**
     * Process the booking and payment.
     */
    public function processBookingPayment(Request $request)
    {
        $validated = $request->validate([
            'pickup_station_id' => 'required|string',
            'dropoff_station_id' => 'required|string',
            'pickup_date' => 'required|date',
            'pickup_time' => 'required|string',
            'dropoff_date' => 'required|date',
            'dropoff_time' => 'required|string',
            'vehicle' => 'required|json',
            'customer_details' => 'required|array',
        ]);

        $vehicle = json_decode($validated['vehicle'], true);
        $customerDetails = $validated['customer_details'];

        $parts = explode('_', $vehicle['id']);
        $groupId = $parts[1] ?? null;

        $reservationData = [
            'group_code' => $groupId,
            'token' => $vehicle['ok_mobility_token'],
            'pickup_date' => $validated['pickup_date'],
            'pickup_time' => $validated['pickup_time'],
            'pickup_station_id' => $validated['pickup_station_id'],
            'dropoff_date' => $validated['dropoff_date'],
            'dropoff_time' => $validated['dropoff_time'],
            'dropoff_station_id' => $validated['dropoff_station_id'],
            'driver_name' => $customerDetails['name'],
            'driver_email' => $customerDetails['email'],
            'driver_phone' => $customerDetails['phone'],
        ];

        try {
            $responseXml = $this->okMobilityService->makeReservation($reservationData);

            if (!$responseXml) {
                return redirect()->route('okmobility.booking.cancel', ['locale' => app()->getLocale()]);
            }

            libxml_use_internal_errors(true);
            $xmlObject = simplexml_load_string($responseXml);

            if ($xmlObject === false) {
                return redirect()->route('okmobility.booking.cancel', ['locale' => app()->getLocale()]);
            }
            
            $status = (string)$xmlObject->Body->createReservationResponse->createReservationResult->Status;

            if ($status === 'C') {
                // Booking confirmed, save to database and redirect to success
                $booking = OkMobilityBooking::create([
                    'user_id' => auth()->id(),
                    'ok_mobility_booking_ref' => (string)$xmlObject->Body->createReservationResponse->createReservationResult->Reservation_Nr,
                    'vehicle_id' => $vehicle['id'],
                    'start_date' => $validated['pickup_date'],
                    'start_time' => $validated['pickup_time'],
                    'end_date' => $validated['dropoff_date'],
                    'end_time' => $validated['dropoff_time'],
                    'customer_details' => $customerDetails,
                    'vehicle_total' => $vehicle['price_per_day'],
                    'currency' => $vehicle['currency'],
                    'grand_total' => $vehicle['price_per_day'], // This should be recalculated with extras
                    'booking_status' => 'confirmed',
                    'api_response' => json_decode(json_encode($xmlObject), true),
                ]);

                return redirect()->route('okmobility.booking.success', ['locale' => app()->getLocale(), 'booking_id' => $booking->id]);
            } else {
                return redirect()->route('okmobility.booking.cancel', ['locale' => app()->getLocale()]);
            }

        } catch (\Exception $e) {
            Log::error("Error processing OK Mobility booking: " . $e->getMessage());
            return redirect()->route('okmobility.booking.cancel', ['locale' => app()->getLocale()]);
        }
    }

    /**
     * Handle the successful payment callback.
     */
    public function bookingSuccess(Request $request)
    {
        $booking = OkMobilityBooking::findOrFail($request->query('booking_id'));

        return Inertia::render('OkMobilitySuccess', [
            'booking' => $booking,
        ]);
    }

    /**
     * Handle the cancelled payment callback.
     */
    public function bookingCancel(Request $request)
    {
        return Inertia::render('OkMobilityCancel');
    }
}
