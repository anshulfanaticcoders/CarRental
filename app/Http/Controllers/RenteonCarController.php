<?php

namespace App\Http\Controllers;

use App\Services\RenteonService;
use App\Services\LocationSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class RenteonCarController extends Controller
{
    private $renteonService;
    private $locationSearchService;

    public function __construct(RenteonService $renteonService, LocationSearchService $locationSearchService)
    {
        $this->renteonService = $renteonService;
        $this->locationSearchService = $locationSearchService;
    }

    /**
     * Display the specified Renteon vehicle details.
     */
    public function show(Request $request, $locale, $id)
    {
        try {
            Log::info('RenteonCarController::show called', [
                'locale' => $locale,
                'id' => $id,
                'all_params' => $request->all()
            ]);

            // Parse the Renteon vehicle ID
            // Expected format: renteon_{pickupCode}_{hash} or renteon_{sippCode}
            if (strpos($id, '_') !== false) {
                $parts = explode('_', $id);
                array_shift($parts); // Remove 'renteon' prefix
                $pickupCode = implode('_', $parts); // Rejoin in case code has underscores
            } else {
                Log::error('Invalid Renteon vehicle ID format', ['id' => $id]);
                return redirect()->route('search', $locale)
                    ->with('error', 'Invalid vehicle identifier. Please search again.');
            }

            // Get search parameters from request
            $pickupLocationId = $request->get('location_id');
            $dropoffLocationId = $pickupLocationId;
            $dateFrom = $request->get('start_date');
            $timeFrom = $request->get('start_time', '09:00');
            $dateTo = $request->get('end_date');
            $timeTo = $request->get('end_time', '09:00');

            // Validate required parameters
            if (!$dateFrom || !$dateTo) {
                Log::error('Missing required date parameters');
                return redirect()->route('search', $locale)
                    ->with('error', 'Missing rental dates. Please search again.');
            }

            // Calculate rental days
            $startDate = \Carbon\Carbon::parse($dateFrom);
            $endDate = \Carbon\Carbon::parse($dateTo);
            $rentalDays = max(1, $startDate->diffInDays($endDate));

            try {
                // Fetch vehicles from Renteon API
                $renteonVehicles = $this->renteonService->getTransformedVehicles(
                    $pickupLocationId ?? $pickupCode,
                    $dropoffLocationId ?? $pickupLocationId ?? $pickupCode,
                    $dateFrom,
                    $timeFrom,
                    $dateTo,
                    $timeTo,
                    [
                        'driver_age' => (int) $request->get('age', 35),
                        'currency' => $request->get('currency', 'EUR'),
                        'prepaid' => true,
                    ],
                    (float) $request->get('lat', 0),
                    (float) $request->get('lng', 0),
                    $request->get('full_vehicle_address', 'Renteon Location'),
                    $rentalDays
                );

                if (empty($renteonVehicles)) {
                    Log::warning('No vehicles found from Renteon API', [
                        'pickupCode' => $pickupCode,
                        'pickupLocationId' => $pickupLocationId
                    ]);
                    return redirect()->route('search', $locale)
                        ->with('error', 'Vehicle not found or temporarily unavailable.');
                }

                // Find the specific vehicle by ID
                $selectedVehicle = collect($renteonVehicles)->firstWhere('id', $id);

                if (!$selectedVehicle) {
                    // Try to find by pickup code if exact ID match fails
                    $selectedVehicle = collect($renteonVehicles)->first();
                }

                if (!$selectedVehicle) {
                    Log::error('Vehicle not found in Renteon results', [
                        'id' => $id,
                        'available_ids' => collect($renteonVehicles)->pluck('id')->toArray()
                    ]);
                    return redirect()->route('search', $locale)
                        ->with('error', 'Vehicle not found. Please search again.');
                }

                // Ensure selectedVehicle is an object/array
                $selectedVehicle = (object) $selectedVehicle;

                // Get location information
                $locationInfo = $this->locationSearchService->getLocationByProviderId(
                    $selectedVehicle->provider_pickup_id ?? $pickupCode,
                    'renteon'
                );

                return Inertia::render('RenteonSingleCar', [
                    'vehicle' => $selectedVehicle,
                    'locationInfo' => $locationInfo,
                    'searchParams' => [
                        'pickup_location_id' => $pickupLocationId ?? $pickupCode,
                        'dropoff_location_id' => $pickupLocationId ?? $pickupCode,
                        'pickup_datetime' => $dateFrom . ' ' . $timeFrom,
                        'dropoff_datetime' => $dateTo . ' ' . $timeTo,
                        'date_from' => $dateFrom,
                        'time_from' => $timeFrom,
                        'date_to' => $dateTo,
                        'time_to' => $timeTo,
                        'currency' => $request->get('currency', 'EUR'),
                    ],
                    'locale' => $locale,
                ]);

            } catch (\Exception $e) {
                Log::error('Renteon API error', [
                    'pickup_code' => $pickupCode,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return redirect()->route('search', $locale)
                    ->with('error', 'Unable to connect to Renteon service. Please try again later.');
            }

        } catch (\Exception $e) {
            Log::error('Error in RenteonCarController::show', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('search', $locale)
                ->with('error', 'An error occurred while loading vehicle details. Please try again.');
        }
    }
}
