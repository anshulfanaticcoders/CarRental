<?php

namespace App\Http\Controllers;

use App\Services\WheelsysService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class WheelsysCarController extends Controller
{
    private $wheelsysService;

    public function __construct(WheelsysService $wheelsysService)
    {
        $this->wheelsysService = $wheelsysService;
    }

    /**
     * Display the specified Wheelsys vehicle details.
     */
    public function show(Request $request, $locale, $id)
    {
        try {
            Log::info('WheelsysCarController::show called', [
                'locale' => $locale,
                'id' => $id,
                'all_params' => $request->all()
            ]);

            // Extract Wheelsys vehicle ID (remove wheelsys_ prefix if present)
            $wheelsysId = $id;
            if (strpos($id, 'wheelsys_') === 0) {
                $wheelsysId = substr($id, 9); // Remove 'wheelsys_' prefix
            }

            // Extract the unique ID part if present (after last underscore)
            if (strpos($wheelsysId, '_') !== false) {
                $parts = explode('_', $wheelsysId);
                $wheelsysId = $parts[0]; // Use the first part as the group code
            }

            Log::info('Processed Wheelsys ID', ['wheelsysId' => $wheelsysId]);

            // Get search parameters from request
            $pickupStation = $request->get('location_id');
            $returnStation = $request->get('dropoff_location_id', $pickupStation);
            $dateFrom = $request->get('start_date');
            $timeFrom = $request->get('start_time', '10:00');
            $dateTo = $request->get('end_date');
            $timeTo = $request->get('end_time', '10:00');
            $currency = $request->get('currency', 'USD');

            Log::info('Search parameters', [
                'pickupStation' => $pickupStation,
                'returnStation' => $returnStation,
                'dateFrom' => $dateFrom,
                'timeFrom' => $timeFrom,
                'dateTo' => $dateTo,
                'timeTo' => $timeTo
            ]);

            if (!$pickupStation || !$dateFrom || !$dateTo) {
                Log::error('Missing required search parameters');
                return redirect()->route('search', $locale)
                    ->with('error', 'Missing required search parameters. Please search again.');
            }

            // Get vehicle data from Wheelsys API
            $vehicles = $this->wheelsysService->getVehicles(
                $pickupStation,
                $returnStation,
                $dateFrom,
                $timeFrom,
                $dateTo,
                $timeTo
            );

            if (!$vehicles || !isset($vehicles['RATES'])) {
                Log::error('No vehicles found or invalid response from Wheelsys API');
                return redirect()->route('search', $locale)
                    ->with('error', 'Vehicle not found or temporarily unavailable.');
            }

            Log::info('Wheelsys API response', ['vehicle_count' => count($vehicles['RATES'])]);

            // Find the specific vehicle
            $selectedVehicle = null;
            foreach ($vehicles['RATES'] as $rate) {
                if (isset($rate['GroupCode']) && $rate['GroupCode'] === $wheelsysId) {
                    // Convert to standard vehicle format
                    $selectedVehicle = $this->wheelsysService->convertToStandardVehicle(
                        $rate,
                        $pickupStation,
                        $request->get('lat'),
                        $request->get('lng'),
                        $request->get('full_vehicle_address')
                    );
                    break;
                }
            }

            if (!$selectedVehicle) {
                Log::error('Vehicle not found', ['wheelsysId' => $wheelsysId]);
                return redirect()->route('search', $locale)
                    ->with('error', 'Vehicle not found. Please search again.');
            }

            // Get vehicle groups for additional information
            $vehicleGroups = $this->wheelsysService->getVehicleGroups();
            $groupInfo = null;
            if ($vehicleGroups && isset($vehicleGroups['GROUPS'])) {
                foreach ($vehicleGroups['GROUPS'] as $group) {
                    if (isset($group['CODE']) && $group['CODE'] === $selectedVehicle['group_code']) {
                        $groupInfo = $group;
                        break;
                    }
                }
            }

            // Get options/extras
            $options = $this->wheelsysService->getOptions();

            Log::info('Vehicle found', [
                'vehicle_id' => $selectedVehicle['id'],
                'brand' => $selectedVehicle['brand'],
                'model' => $selectedVehicle['model'],
                'price_per_day' => $selectedVehicle['price_per_day']
            ]);

            return Inertia::render('WheelsysCar/Show', [
                'vehicle' => $selectedVehicle,
                'groupInfo' => $groupInfo,
                'options' => $options,
                'searchParams' => [
                    'pickup_station' => $pickupStation,
                    'return_station' => $returnStation,
                    'date_from' => $dateFrom,
                    'time_from' => $timeFrom,
                    'date_to' => $dateTo,
                    'time_to' => $timeTo,
                    'currency' => $currency,
                ],
                'locale' => $locale,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in WheelsysCarController::show', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('search', $locale)
                ->with('error', 'An error occurred while loading vehicle details. Please try again.');
        }
    }
}