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

            // Extract group code from the ID (take first part before underscore)
            if (strpos($id, '_') !== false) {
                $parts = explode('_', $id);
                $groupCode = $parts[0]; // Use the first part as the group code (e.g., FFAR)
            } else {
                $groupCode = $id;
            }

            Log::info('Processed Wheelsys ID', ['originalId' => $id, 'groupCode' => $groupCode]);

            // Get search parameters from request and convert date format (like SearchController does)
            $pickupStation = $request->get('location_id');
            $returnStation = $request->get('dropoff_location_id', $pickupStation);
            $dateFrom = date('d/m/Y', strtotime($request->get('start_date')));
            $timeFrom = str_replace('%3A', ':', $request->get('start_time', '10:00')); // Fix URL encoding
            $dateTo = date('d/m/Y', strtotime($request->get('end_date')));
            $timeTo = str_replace('%3A', ':', $request->get('end_time', '10:00')); // Fix URL encoding
            $currency = $request->get('currency', 'USD');

            if (!$pickupStation || !$dateFrom || !$dateTo) {
                Log::error('Missing required search parameters');
                return redirect()->route('search', $locale)
                    ->with('error', 'Missing required search parameters. Please search again.');
            }

            try {
                // Get vehicle data from Wheelsys API
                $vehicles = $this->wheelsysService->getVehicles(
                    $pickupStation,
                    $returnStation,
                    $dateFrom,
                    $timeFrom,
                    $dateTo,
                    $timeTo
                );

                Log::info('Wheelsys API response received successfully', [
                    'isArray' => is_array($vehicles),
                    'keys' => $vehicles ? array_keys($vehicles) : null,
                    'hasRates' => isset($vehicles['Rates'])
                ]);

                if (!$vehicles || !isset($vehicles['Rates'])) {
                    Log::error('No vehicles found or invalid response from Wheelsys API');
                    return redirect()->route('search', $locale)
                        ->with('error', 'Vehicle not found or temporarily unavailable.');
                }

            } catch (\Exception $e) {
                // Handle the new robust error scenarios from WheelsysService
                $errorMessage = $e->getMessage();

                if (str_contains($errorMessage, 'temporarily unavailable due to repeated failures')) {
                    Log::warning('Wheelsys API circuit breaker is open - API temporarily unavailable', [
                        'pickup_station' => $pickupStation,
                        'circuit_breaker_status' => $this->wheelsysService->getCircuitBreakerStatus()
                    ]);

                    return redirect()->route('search', $locale)
                        ->with('error', 'Wheelsys service temporarily unavailable due to technical issues. Please try again later.');
                } else if (str_contains($errorMessage, 'Invalid API response structure')) {
                    Log::error('Wheelsys API response validation failed', [
                        'pickup_station' => $pickupStation,
                        'error' => $errorMessage
                    ]);

                    return redirect()->route('search', $locale)
                        ->with('error', 'Unable to process vehicle data. Please try again later.');
                } else {
                    Log::error('Wheelsys API error after retries', [
                        'pickup_station' => $pickupStation,
                        'error' => $errorMessage,
                        'circuit_breaker_status' => $this->wheelsysService->getCircuitBreakerStatus()
                    ]);

                    return redirect()->route('search', $locale)
                        ->with('error', 'Unable to connect to Wheelsys service. Please try again later.');
                }
            }

            // Find the specific vehicle (like SearchController does)
            $selectedVehicle = null;
            $wheelsysRates = collect($vehicles['Rates']);

            foreach ($wheelsysRates as $rate) {
                if (isset($rate['GroupCode']) && $rate['GroupCode'] === $groupCode) {
                    // Convert to standard vehicle format (same as SearchController)
                    $standardVehicle = $this->wheelsysService->convertToStandardVehicle(
                        $rate,
                        $pickupStation,
                        $request->get('lat'),
                        $request->get('lng'),
                        $request->get('full_vehicle_address')
                    );

                    if ($standardVehicle) {
                        $selectedVehicle = $standardVehicle;
                    }
                    break;
                }
            }

            if (!$selectedVehicle) {
                Log::error('Vehicle not found', ['groupCode' => $groupCode, 'availableCodes' => $wheelsysRates->pluck('GroupCode')->toArray()]);
                return redirect()->route('search', $locale)
                    ->with('error', 'Vehicle not found. Please search again.');
            }

            return Inertia::render('WheelsysCar/Show', [
                'vehicle' => $selectedVehicle,
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