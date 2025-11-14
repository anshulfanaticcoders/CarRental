<?php

namespace App\Http\Controllers;

use App\Services\AdobeCarService;
use App\Services\LocationSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class AdobeCarController extends Controller
{
    private $adobeCarService;
    private $locationSearchService;

    public function __construct(AdobeCarService $adobeCarService, LocationSearchService $locationSearchService)
    {
        $this->adobeCarService = $adobeCarService;
        $this->locationSearchService = $locationSearchService;
    }

    /**
     * Display the specified Adobe vehicle details.
     */
    public function show(Request $request, $locale, $id)
    {
        try {
            Log::info('AdobeCarController::show called', [
                'locale' => $locale,
                'id' => $id,
                'all_params' => $request->all()
            ]);

            // Parse the Adobe vehicle ID to extract location and category
            // Expected format: adobe_{locationId}_{category} (from SearchController)
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

            Log::info('Parsed Adobe vehicle data', [
                'locationId' => $locationId,
                'vehicleCategory' => $vehicleCategory
            ]);

            // Get search parameters from request
            $pickupLocationId = $request->get('location_id', $locationId);
            $dropoffLocationId = $request->get('dropoff_location_id', $pickupLocationId);
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

            // Format dates for Adobe API (YYYY-MM-DD HH:MM)
            $pickupDateTime = $dateFrom . ' ' . $timeFrom;
            $dropoffDateTime = $dateTo . ' ' . $timeTo;

            try {
                // First, check if we have cached vehicle data
                $cachedAdobeData = $this->adobeCarService->getCachedVehicleData($pickupLocationId, 60);

                if ($cachedAdobeData && !empty($cachedAdobeData['vehicles'])) {
                    Log::info('Using cached Adobe vehicle data for location: ' . $pickupLocationId);
                    $allVehicles = collect($cachedAdobeData['vehicles']);
                } else {
                    Log::info('Fetching fresh Adobe vehicle data from API');

                    // Fetch fresh vehicle data from Adobe API
                    $searchParams = [
                        'pickupoffice' => $pickupLocationId,
                        'returnoffice' => $dropoffLocationId,
                        'startdate' => $pickupDateTime,
                        'enddate' => $dropoffDateTime,
                        'promotionCode' => $request->get('promocode')
                    ];

                    // Filter out null values
                    $searchParams = array_filter($searchParams, function($value) {
                        return $value !== null && $value !== '';
                    });

                    $adobeResponse = $this->adobeCarService->getAvailableVehicles($searchParams);

                    if (!$adobeResponse || !isset($adobeResponse['result']) || !$adobeResponse['result'] || empty($adobeResponse['data'])) {
                        Log::error('No vehicles found or invalid response from Adobe API', [
                            'locationId' => $pickupLocationId,
                            'response' => $adobeResponse
                        ]);
                        return redirect()->route('search', $locale)
                            ->with('error', 'Vehicle not found or temporarily unavailable.');
                    }

                    $allVehicles = collect($adobeResponse['data']);
                }

                // Find the specific vehicle by category
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

                // Process vehicle data with all details
                $processedVehicle = [
                    'id' => $id,
                    'source' => 'adobe',
                    'category' => $vehicleCategory,
                    'model' => $selectedVehicle['model'] ?? 'Adobe Vehicle',
                    'brand' => $this->extractBrandFromModel($selectedVehicle['model'] ?? ''),
                    'image' => $this->getAdobeVehicleImage($selectedVehicle['photo'] ?? ''),
                    'price_per_day' => (float) ($selectedVehicle['pli'] ?? 0),
                    'price_per_week' => (float) (($selectedVehicle['pli'] ?? 0) * 7),
                    'price_per_month' => (float) (($selectedVehicle['pli'] ?? 0) * 30),
                    'currency' => 'USD',
                    'transmission' => ($selectedVehicle['manual'] ?? false) ? 'manual' : 'automatic',
                    'fuel' => 'petrol',
                    'seating_capacity' => (int) ($selectedVehicle['passengers'] ?? 4),
                    'mileage' => 'unlimited',
                    'latitude' => (float) $request->get('lat', 0),
                    'longitude' => (float) $request->get('lng', 0),
                    'full_vehicle_address' => $request->get('full_vehicle_address', 'Adobe Location'),
                    'provider_pickup_id' => $pickupLocationId,
                    'benefits' => (object) [
                        'cancellation_available_per_day' => true,
                        'limited_km_per_day' => false,
                        'minimum_driver_age' => 21,
                        'fuel_policy' => 'full_to_full',
                        'vehicle_type' => $selectedVehicle['type'] ?? '',
                        'traction' => $selectedVehicle['traction'] ?? '',
                        'doors' => (int) ($selectedVehicle['doors'] ?? 4),
                    ],
                    'protections' => $vehicleDetails['protections'] ?? [],
                    'extras' => $vehicleDetails['extras'] ?? [],
                    // Adobe-specific fields
                    'pli' => (float) ($selectedVehicle['pli'] ?? 0),
                    'ldw' => (float) ($selectedVehicle['ldw'] ?? 0),
                    'spp' => (float) ($selectedVehicle['spp'] ?? 0),
                    'tdr' => (float) ($selectedVehicle['tdr'] ?? 0),
                    'dro' => (float) ($selectedVehicle['dro'] ?? 0),
                ];

                // Get location information
                $locationInfo = $this->locationSearchService->getLocationByProviderId($pickupLocationId, 'adobe');

                return Inertia::render('AdobeSingleCar', [
                    'vehicle' => (object) $processedVehicle,
                    'locationInfo' => $locationInfo,
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
                Log::error('Adobe API error', [
                    'pickup_location_id' => $pickupLocationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return redirect()->route('search', $locale)
                    ->with('error', 'Unable to connect to Adobe service. Please try again later.');
            }

        } catch (\Exception $e) {
            Log::error('Error in AdobeCarController::show', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('search', $locale)
                ->with('error', 'An error occurred while loading vehicle details. Please try again.');
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

        // Common car brands to extract from model names
        $brands = ['Suzuki', 'Toyota', 'Nissan', 'Hyundai', 'Honda', 'Ford', 'Chevrolet', 'Mitsubishi', 'Mazda', 'Volkswagen', 'Kia', 'Geely'];

        foreach ($brands as $brand) {
            if (stripos($model, $brand) !== false) {
                return $brand;
            }
        }

        // If no brand found, use first word as brand
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

        // If photo is just filename, construct full URL
        if (strpos($photo, 'http') === false) {
            return "https://adobecar.cr/images/vehicles/{$photo}";
        }

        return $photo;
    }
}
