<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;
use App\Helpers\SchemaBuilder;
use App\Services\GreenMotionService; // Import GreenMotionService
use App\Services\OkMobilityService;
use App\Services\LocationSearchService; // Import LocationSearchService
use App\Services\AdobeCarService; // Import AdobeCarService
use App\Services\WheelsysService; // Import WheelsysService
use Illuminate\Support\Facades\Log; // Import Log facade

class SearchController extends Controller
{
    protected $greenMotionService;
    protected $okMobilityService;
    protected $locationSearchService;
    protected $adobeCarService;
    protected $wheelsysService;

    public function __construct(GreenMotionService $greenMotionService, OkMobilityService $okMobilityService, LocationSearchService $locationSearchService, AdobeCarService $adobeCarService, WheelsysService $wheelsysService)
    {
        $this->greenMotionService = $greenMotionService;
        $this->okMobilityService = $okMobilityService;
        $this->locationSearchService = $locationSearchService;
        $this->adobeCarService = $adobeCarService;
        $this->wheelsysService = $wheelsysService;
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'seating_capacity' => 'nullable|integer',
            'brand' => 'nullable|string',
            'transmission' => 'nullable|string|in:automatic,manual',
            'price_range' => 'nullable|string',
            'color' => 'nullable|string',
            'mileage' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after:date_from',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
            'age' => 'nullable|integer',
            'rentalCode' => 'nullable|string',
            'currency' => 'nullable|string|max:3',
            'fuel' => 'nullable|string',
            'userid' => 'nullable|string',
            'username' => 'nullable|string',
            'language' => 'nullable|string',
            'full_credit' => 'nullable|string',
            'promocode' => 'nullable|string',
            'dropoff_location_id' => 'nullable|string',
            'dropoff_where' => 'nullable|string',
            'where' => 'nullable|string',
            'location' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'radius' => 'nullable|numeric',
            'package_type' => 'nullable|string|in:day,week,month',
            'category_id' => 'nullable|exists:vehicle_categories,id',
            'matched_field' => 'nullable|string|in:location,city,state,country',
            'provider' => 'nullable|string', // Replaces 'source'
            'provider_pickup_id' => 'nullable|string', // Replaces 'greenmotion_location_id'
        ]);

        $internalVehiclesQuery = Vehicle::query()->whereIn('status', ['available', 'rented'])
            ->with('images', 'bookings', 'vendorProfile', 'benefits');

        // Apply date filters to internal vehicles
        if (!empty($validated['date_from']) && !empty($validated['date_to'])) {
            $internalVehiclesQuery->whereDoesntHave('bookings', function ($q) use ($validated) {
                $q->where(function ($query) use ($validated) {
                    $query->whereBetween('bookings.pickup_date', [$validated['date_from'], $validated['date_to']])
                        ->orWhereBetween('bookings.return_date', [$validated['date_from'], $validated['date_to']])
                        ->orWhere(function ($q) use ($validated) {
                            $q->where('bookings.pickup_date', '<=', $validated['date_from'])
                                ->where('bookings.return_date', '>=', $validated['date_to']);
                        });
                });
            });

            $internalVehiclesQuery->whereDoesntHave('blockings', function ($q) use ($validated) {
                $q->where(function ($query) use ($validated) {
                    $query->whereBetween('blocking_start_date', [$validated['date_from'], $validated['date_to']])
                        ->orWhereBetween('blocking_end_date', [$validated['date_from'], $validated['date_to']])
                        ->orWhere(function ($q) use ($validated) {
                            $q->where('blocking_start_date', '<=', $validated['date_from'])
                                ->where('blocking_end_date', '>=', $validated['date_to']);
                        });
                });
            });
        }

        // Apply location filters for internal vehicles based on search parameters
        if (!empty($validated['provider_pickup_id']) && (!isset($validated['provider']) || $validated['provider'] !== 'internal')) {
            if (!empty($validated['latitude']) && !empty($validated['longitude']) && !empty($validated['radius'])) {
                $lat = $validated['latitude'];
                $lon = $validated['longitude'];
                $radius = $validated['radius'] / 1000;

                $internalVehiclesQuery->whereRaw("
                    6371 * acos(
                        cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) +
                        sin(radians(?)) * sin(radians(latitude))
                    ) <= ?
                ", [$lat, $lon, $lat, $radius]);
            } else {
                $internalVehiclesQuery->whereRaw('1 = 0');
            }
        } elseif (!empty($validated['provider'])) {
            if ($validated['provider'] === 'internal') {
                if (!empty($validated['matched_field'])) {
                    $fieldToQuery = null;
                    $valueToQuery = null;

                    switch ($validated['matched_field']) {
                        case 'location':
                            if (!empty($validated['location'])) {
                                $fieldToQuery = 'location';
                                $valueToQuery = $validated['location'];
                            }
                            break;
                        case 'city':
                            if (!empty($validated['city'])) {
                                $fieldToQuery = 'city';
                                $valueToQuery = $validated['city'];
                            }
                            break;
                        case 'state':
                            if (!empty($validated['state'])) {
                                $fieldToQuery = 'state';
                                $valueToQuery = $validated['state'];
                            }
                            break;
                        case 'country':
                            if (!empty($validated['country'])) {
                                $fieldToQuery = 'country';
                                $valueToQuery = $validated['country'];
                            }
                            break;
                    }
                    if ($fieldToQuery && $valueToQuery) {
                        $internalVehiclesQuery->where($fieldToQuery, $valueToQuery);
                    }
                } elseif (!empty($validated['latitude']) && !empty($validated['longitude']) && !empty($validated['radius'])) {
                    $lat = $validated['latitude'];
                    $lon = $validated['longitude'];
                    $radius = $validated['radius'] / 1000;

                    $internalVehiclesQuery->whereRaw("
                        6371 * acos(
                            cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) +
                            sin(radians(?)) * sin(radians(latitude))
                        ) <= ?
                    ", [$lat, $lon, $lat, $radius]);
                } elseif (!empty($validated['where'])) {
                    $searchTerm = $validated['where'];
                    $internalVehiclesQuery->where(function ($q) use ($searchTerm) {
                        $q->where('location', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('city', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('state', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('country', 'LIKE', "%{$searchTerm}%");
                    });
                }
            } else {
                if (!empty($validated['latitude']) && !empty($validated['longitude']) && !empty($validated['radius'])) {
                    $lat = $validated['latitude'];
                    $lon = $validated['longitude'];
                    $radius = $validated['radius'] / 1000;

                    $internalVehiclesQuery->whereRaw("
                        6371 * acos(
                            cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) +
                            sin(radians(?)) * sin(radians(latitude))
                        ) <= ?
                    ", [$lat, $lon, $lat, $radius]);
                } else {
                    $internalVehiclesQuery->whereRaw('1 = 0');
                }
            }
        } else { // If no provider is specified, apply broad internal filters
            if (!empty($validated['matched_field'])) {
                $fieldToQuery = null;
                $valueToQuery = null;

                switch ($validated['matched_field']) {
                    case 'location':
                        if (!empty($validated['location'])) {
                            $fieldToQuery = 'location';
                            $valueToQuery = $validated['location'];
                        }
                        break;
                    case 'city':
                        if (!empty($validated['city'])) {
                            $fieldToQuery = 'city';
                            $valueToQuery = $validated['city'];
                        }
                        break;
                    case 'state':
                        if (!empty($validated['state'])) {
                            $fieldToQuery = 'state';
                            $valueToQuery = $validated['state'];
                        }
                        break;
                    case 'country':
                        if (!empty($validated['country'])) {
                            $fieldToQuery = 'country';
                            $valueToQuery = $validated['country'];
                        }
                        break;
                }
                if ($fieldToQuery && $valueToQuery) {
                    $internalVehiclesQuery->where($fieldToQuery, $valueToQuery);
                }
            } elseif (!empty($validated['latitude']) && !empty($validated['longitude']) && !empty($validated['radius'])) {
                $lat = $validated['latitude'];
                $lon = $validated['longitude'];
                $radius = $validated['radius'] / 1000;

                $internalVehiclesQuery->whereRaw("
                    6371 * acos(
                        cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) +
                        sin(radians(?)) * sin(radians(latitude))
                    ) <= ?
                ", [$lat, $lon, $lat, $radius]);
            } elseif (!empty($validated['where'])) {
                $searchTerm = $validated['where'];
                $internalVehiclesQuery->where(function ($q) use ($searchTerm) {
                    $q->where('location', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('city', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('state', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('country', 'LIKE', "%{$searchTerm}%");
                });
            }
        }


        $queryForOptions = clone $internalVehiclesQuery;
        $potentialVehiclesForOptions = $queryForOptions->get();

        $brands = $potentialVehiclesForOptions->pluck('brand')->unique()->filter()->values()->all();
        $colors = $potentialVehiclesForOptions->pluck('color')->unique()->filter()->values()->all();
        $seatingCapacities = $potentialVehiclesForOptions->pluck('seating_capacity')->unique()->filter()->values()->all();
        $transmissions = $potentialVehiclesForOptions->pluck('transmission')->unique()->filter()->values()->all();
        $fuels = $potentialVehiclesForOptions->pluck('fuel')->unique()->filter()->values()->all();
        $mileages = $potentialVehiclesForOptions->pluck('mileage')->unique()->filter()->map(function ($mileage) {
            if ($mileage >= 0 && $mileage <= 25) return '0-25';
            if ($mileage > 25 && $mileage <= 50) return '25-50';
            if ($mileage > 50 && $mileage <= 75) return '50-75';
            if ($mileage > 75 && $mileage <= 100) return '75-100';
            if ($mileage > 100 && $mileage <= 120) return '100-120';
            return null;
        })->filter()->unique()->values()->all();
        $categoriesFromOptions = [];
        $categoryIds = $potentialVehiclesForOptions->pluck('category_id')->unique()->filter();
        if ($categoryIds->isNotEmpty()) {
            $categoriesFromOptions = VehicleCategory::whereIn('id', $categoryIds)->select('id', 'name')->get()->toArray();
        }

        if (!empty($validated['seating_capacity'])) {
            $internalVehiclesQuery->where('seating_capacity', $validated['seating_capacity']);
        }
        if (!empty($validated['brand'])) {
            $internalVehiclesQuery->where('brand', $validated['brand']);
        }
        if (!empty($validated['transmission'])) {
            $internalVehiclesQuery->where('transmission', $validated['transmission']);
        }
        if (!empty($validated['fuel'])) {
            $internalVehiclesQuery->where('fuel', $validated['fuel']);
        }
        if (!empty($validated['price_range'])) {
            $range = explode('-', $validated['price_range']);
            $internalVehiclesQuery->whereBetween('price_per_day', [(int) $range[0], (int) $range[1]]);
        }
        if (!empty($validated['color'])) {
            $internalVehiclesQuery->where('color', $validated['color']);
        }
        if (!empty($validated['mileage'])) {
            $range = explode('-', $validated['mileage']);
            $internalVehiclesQuery->whereBetween('mileage', [(int) $range[0], (int) $range[1]]);
        }
        if (!empty($validated['category_id'])) {
            $internalVehiclesQuery->where('category_id', $validated['category_id']);
        }

        if (!empty($validated['package_type'])) {
            switch ($validated['package_type']) {
                case 'week':
                    $internalVehiclesQuery->whereNotNull('price_per_week')->orderBy('price_per_week');
                    break;
                case 'month':
                    $internalVehiclesQuery->whereNotNull('price_per_month')->orderBy('price_per_month');
                    break;
                default:
                    $internalVehiclesQuery->whereNotNull('price_per_day')->orderBy('price_per_day');
                    break;
            }
        }

        $internalVehiclesEloquent = $internalVehiclesQuery->get();
        $vehicleListSchema = SchemaBuilder::vehicleList($internalVehiclesEloquent, 'Vehicle Search Results', $validated);
        $internalVehiclesData = $internalVehiclesEloquent->map(function ($vehicle) {
            $reviews = Review::where('vehicle_id', $vehicle->id)
                ->where('status', 'approved')
                ->get();
            $vehicle->review_count = $reviews->count();
            $vehicle->average_rating = $reviews->avg('rating') ?? 0;
            $vehicle->source = 'internal';
            return $vehicle->toArray();
        })->values()->all();

        $internalVehiclesCollection = collect($internalVehiclesData);

        // --- Provider Vehicle Fetching Logic ---
        $providerVehicles = collect();
        $okMobilityVehicles = collect(); // Separate collection for OK Mobility vehicles
        $providerName = $validated['provider'] ?? null;
        $providerLocationId = $validated['provider_pickup_id'] ?? null;
        $locationLat = $validated['latitude'] ?? null;
        $locationLng = $validated['longitude'] ?? null;
        $locationAddress = $validated['where'] ?? null;

        // Fallback: If no provider ID, try to find one from the 'where' text
        if (!$providerLocationId && !empty($validated['where'])) {
            $searchParam = $validated['where'];
            $unifiedLocations = $this->locationSearchService->searchLocations($searchParam);
            
            $matchedLocation = collect($unifiedLocations)->first(function($loc) {
                return isset($loc['providers']) && count($loc['providers']) > 0;
            });

            if ($matchedLocation && isset($matchedLocation['providers'][0])) {
                $providerName = $matchedLocation['providers'][0]['provider'];
                $providerLocationId = $matchedLocation['providers'][0]['pickup_id'];
                $locationLat = $matchedLocation['latitude'];
                $locationLng = $matchedLocation['longitude'];
                $locationAddress = $matchedLocation['name'];
            }
        }

        if ($providerName && $providerName !== 'internal' && $providerLocationId && !empty($validated['date_from']) && !empty($validated['date_to'])) {
            $providersToFetch = [];
            if ($providerName === 'mixed') {
                $allLocations = json_decode(file_get_contents(public_path('unified_locations.json')), true);
                $matchedLocation = collect($allLocations)->first(function ($location) use ($providerLocationId) {
                    if (isset($location['providers'])) {
                        foreach ($location['providers'] as $provider) {
                            if ($provider['pickup_id'] == $providerLocationId) {
                                return true;
                            }
                        }
                    }
                    return false;
                });

                if ($matchedLocation && !empty($matchedLocation['providers'])) {
                    foreach ($matchedLocation['providers'] as $provider) {
                        $providersToFetch[] = $provider['provider'];
                    }
                }
            } else {
                $providersToFetch[] = $providerName;
            }

            Log::info('Providers to fetch: ' . json_encode($providersToFetch));

            foreach ($providersToFetch as $providerToFetch) {
                if ($providerToFetch === 'greenmotion' || $providerToFetch === 'usave') {
                    try {
                        $this->greenMotionService->setProvider($providerToFetch);
                        $gmOptions = [
                            'language' => $validated['language'] ?? app()->getLocale(),
                            'rentalCode' => $validated['rentalCode'] ?? '1',
                            'currency' => $validated['currency'] ?? null,
                            'fuel' => $validated['fuel'] ?? null,
                            'userid' => $validated['userid'] ?? null,
                            'username' => $validated['username'] ?? null,
                            'full_credit' => $validated['full_credit'] ?? null,
                            'promocode' => $validated['promocode'] ?? null,
                            'dropoff_location_id' => $validated['dropoff_location_id'] ?? null,
                        ];

                        $gmResponse = $this->greenMotionService->getVehicles(
                            $providerLocationId,
                            $validated['date_from'],
                            $validated['start_time'] ?? '09:00',
                            $validated['date_to'],
                            $validated['end_time'] ?? '09:00',
                            $validated['age'] ?? 35,
                            array_filter($gmOptions)
                        );

                        if ($gmResponse) {
                            libxml_use_internal_errors(true);
                            $xmlObject = simplexml_load_string($gmResponse);
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

                                    $minDriverAge = !empty($products) ? (int)($products[0]['minage'] ?? 0) : 0;
                                    $fuelPolicy = !empty($products) ? ($products[0]['fuelpolicy'] ?? '') : '';
                                    $brandName = explode(' ', (string) $vehicle['name'])[0];

                                    $providerVehicles->push((object) [
                                        'id' => $providerToFetch . '_' . (string) $vehicle['id'],
                                        'source' => $providerToFetch,
                                        'brand' => $brandName,
                                        'model' => (string) $vehicle['name'],
                                        'image' => urldecode((string) $vehicle['image']),
                                        'price_per_day' => (isset($vehicle->total) && is_numeric((string)$vehicle->total)) ? (float) (string)$vehicle->total : 0.0,
                                        'price_per_week' => null,
                                        'price_per_month' => null,
                                        'currency' => (isset($vehicle->total['currency']) && !empty((string)$vehicle->total['currency'])) ? (string) $vehicle->total['currency'] : 'EUR',
                                        'transmission' => (string) $vehicle->transmission,
                                        'fuel' => (string) $vehicle->fuel,
                                        'seating_capacity' => (int) $vehicle->adults + (int) $vehicle->children,
                                        'mileage' => (string) $vehicle->mpg,
                                        'latitude' => (float) $locationLat,
                                        'longitude' => (float) $locationLng,
                                        'full_vehicle_address' => $locationAddress,
                                        'provider_pickup_id' => $providerLocationId,
                                        'benefits' => (object) [
                                            'cancellation_available_per_day' => true,
                                            'limited_km_per_day' => false,
                                            'minimum_driver_age' => $minDriverAge,
                                            'fuel_policy' => $fuelPolicy,
                                        ],
                                        'review_count' => 0,
                                        'average_rating' => 0,
                                        'products' => $products,
                                        'options' => [],
                                        'insurance_options' => [],
                                    ]);
                                }
                            } else {
                                Log::warning("{$providerToFetch} API response for vehicles was empty or malformed for location ID: " . $providerLocationId, ['response' => $gmResponse]);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("Error fetching {$providerToFetch} vehicles: " . $e->getMessage());
                    }
                } elseif ($providerToFetch === 'adobe') {
                    try {
                        Log::info('Attempting to fetch Adobe vehicles for location ID: ' . $providerLocationId);
                        Log::info('Search params: ', [
                            'pickup_id' => $providerLocationId,
                            'date_from' => $validated['date_from'],
                            'start_time' => $validated['start_time'] ?? '09:00',
                            'date_to' => $validated['date_to'],
                            'end_time' => $validated['end_time'] ?? '09:00'
                        ]);

                        // First, check if we have cached data
                        $cachedAdobeData = $this->adobeCarService->getCachedVehicleData($providerLocationId, 60); // 60 minutes cache

                        if ($cachedAdobeData && !empty($cachedAdobeData['vehicles'])) {
                            Log::info('Using cached Adobe vehicle data for location: ' . $providerLocationId);
                            $adobeVehicles = collect($cachedAdobeData['vehicles']);
                        } else {
                            Log::info('Fetching fresh Adobe vehicle data from API');

                            // Prepare search parameters for Adobe API
                            $adobeSearchParams = [
                                'pickupoffice' => $providerLocationId,
                                'returnoffice' => $providerLocationId,
                                'startdate' => $validated['date_from'] . ' ' . ($validated['start_time'] ?? '09:00'),
                                'enddate' => $validated['date_to'] . ' ' . ($validated['end_time'] ?? '09:00'),
                                'promotionCode' => $validated['promocode'] ?? null
                            ];

                            // Filter out null values
                            $adobeSearchParams = array_filter($adobeSearchParams, function($value) {
                                return $value !== null && $value !== '';
                            });

                            $adobeResponse = $this->adobeCarService->getAvailableVehicles($adobeSearchParams);

                            Log::info('Adobe API response received: ' . ($adobeResponse ? 'SUCCESS' : 'NULL/EMPTY'));

                            if ($adobeResponse && isset($adobeResponse['result']) && $adobeResponse['result'] && !empty($adobeResponse['data'])) {
                                $adobeVehicles = collect($adobeResponse['data']);

                                // Process vehicles to get detailed information including protections and extras
                                $processedVehicles = [];
                                $allProtections = [];
                                $allExtras = [];

                                foreach ($adobeVehicles as $vehicle) {
                                    // Get detailed vehicle information including protections and extras
                                    $vehicleDetails = $this->adobeCarService->getProtectionsAndExtras(
                                        $providerLocationId,
                                        $vehicle['category'] ?? '',
                                        [
                                            'startdate' => $adobeSearchParams['startdate'],
                                            'enddate' => $adobeSearchParams['enddate']
                                        ]
                                    );

                                    $processedVehicle = [
                                        'id' => 'adobe_' . $providerLocationId . '_' . ($vehicle['category'] ?? 'unknown'),
                                        'source' => 'adobe',
                                        'brand' => $this->extractBrandFromModel($vehicle['model'] ?? ''),
                                        'model' => $vehicle['model'] ?? 'Adobe Vehicle',
                                        'category' => $vehicle['category'] ?? '',
                                        'image' => $this->getAdobeVehicleImage($vehicle['photo'] ?? ''),
                                        'price_per_day' => (float) ($vehicle['pli'] ?? 0), // pli = base daily rate
                                        'price_per_week' => (float) (($vehicle['pli'] ?? 0) * 7),
                                        'price_per_month' => (float) (($vehicle['pli'] ?? 0) * 30),
                                        'currency' => 'USD', // Adobe uses USD
                                        'transmission' => ($vehicle['manual'] ?? false) ? 'manual' : 'automatic',
                                        'fuel' => 'petrol', // Default fuel type
                                        'seating_capacity' => (int) ($vehicle['passengers'] ?? 4),
                                        'mileage' => 'unlimited', // Default mileage
                                        'latitude' => (float) $locationLat,
                                        'longitude' => (float) $locationLng,
                                        'full_vehicle_address' => $locationAddress,
                                        'provider_pickup_id' => $providerLocationId,
                                        'benefits' => (object) [
                                            'cancellation_available_per_day' => true,
                                            'limited_km_per_day' => false,
                                            'minimum_driver_age' => 21,
                                            'fuel_policy' => 'full_to_full',
                                            'vehicle_type' => $vehicle['type'] ?? '',
                                            'traction' => $vehicle['traction'] ?? '',
                                            'doors' => (int) ($vehicle['doors'] ?? 4),
                                        ],
                                        'review_count' => 0,
                                        'average_rating' => 0,
                                        'products' => [],
                                        'protections' => $vehicleDetails['protections'] ?? [],
                                        'extras' => $vehicleDetails['extras'] ?? [],
                                        'adobe_category' => $vehicle['category'] ?? '',
                                        // Adobe-specific fields
                                        'adobe_pli' => (float) ($vehicle['pli'] ?? 0),
                                        'adobe_ldw' => (float) ($vehicle['ldw'] ?? 0),
                                        'adobe_spp' => (float) ($vehicle['spp'] ?? 0),
                                        'adobe_tdr' => (float) ($vehicle['tdr'] ?? 0),
                                        'adobe_dro' => (float) ($vehicle['dro'] ?? 0),
                                    ];

                                    $processedVehicles[] = (object) $processedVehicle;

                                    // Collect protections and extras for caching
                                    $allProtections = array_merge($allProtections, $vehicleDetails['protections'] ?? []);
                                    $allExtras = array_merge($allExtras, $vehicleDetails['extras'] ?? []);
                                }

                                // Update the JSON cache with fresh data
                                $this->adobeCarService->updateAdobeDetailsJson($providerLocationId, [
                                    'vehicles' => $processedVehicles,
                                    'protections' => $allProtections,
                                    'extras' => $allExtras
                                ]);

                                $adobeVehicles = collect($processedVehicles);
                                Log::info('Adobe vehicles processed and cached: ' . $adobeVehicles->count());

                            } else {
                                Log::warning("Adobe API response for vehicles was empty or malformed for location ID: " . $providerLocationId, [
                                    'response' => $adobeResponse
                                ]);
                                $adobeVehicles = collect([]);
                            }
                        }

                        // Add Adobe vehicles to the main provider vehicles collection
                        foreach ($adobeVehicles as $adobeVehicle) {
                            $providerVehicles->push($adobeVehicle);
                        }

                        Log::info('Adobe vehicles added to collection: ' . $adobeVehicles->count());

                    } catch (\Exception $e) {
                        Log::error("Error fetching Adobe vehicles: " . $e->getMessage(), [
                            'provider_location_id' => $providerLocationId,
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } elseif ($providerToFetch === 'okmobility') {
                    try {
                        Log::info('Attempting to fetch OK Mobility vehicles for location ID: ' . $providerLocationId);
                        Log::info('Search params: ', [
                            'pickup_id' => $providerLocationId,
                            'date_from' => $validated['date_from'],
                            'start_time' => $validated['start_time'] ?? '09:00',
                            'date_to' => $validated['date_to'],
                            'end_time' => $validated['end_time'] ?? '09:00'
                        ]);

                        $okMobilityResponse = $this->okMobilityService->getVehicles(
                            $providerLocationId,
                            $providerLocationId, // OK Mobility is one-way rental only
                            $validated['date_from'],
                            $validated['start_time'] ?? '09:00',
                            $validated['date_to'],
                            $validated['end_time'] ?? '09:00'
                        );

                        Log::info('OK Mobility API response received: ' . ($okMobilityResponse ? 'SUCCESS' : 'NULL/EMPTY'));

                        if ($okMobilityResponse) {
                            libxml_use_internal_errors(true);
                            $xmlObject = simplexml_load_string($okMobilityResponse);

                            if ($xmlObject !== false) {
                                Log::info('OK Mobility XML parsed successfully');
                                // Register the correct namespace for OK Mobility
                                $xmlObject->registerXPathNamespace('ns', 'http://tempuri.org/');

                                // Try both possible XPath patterns
                                $vehicles = $xmlObject->xpath('//get:getMultiplePrice') ?:
                                          $xmlObject->xpath('//getMultiplePricesResult/getMultiplePrice') ?:
                                          $xmlObject->xpath('//ns:getMultiplePrice');

                                Log::info('OK Mobility vehicles found in XML: ' . count($vehicles));

                                foreach ($vehicles as $vehicle) {
                                    $vehicleData = json_decode(json_encode($vehicle), true);

                                    // Extract vehicle data with proper fallbacks
                                    $groupFullName = $vehicleData['Group_Name'] ?? 'Unknown Vehicle';
                                    $groupId = $vehicleData['GroupID'] ?? 'unknown';

                                    // Use the Group_Name as the vehicle name (what OK Mobility provides)
                                    $vehicleName = $groupFullName;
                                    $token = $vehicleData['token'] ?? 'unknown';

                                    // Calculate price per day using OK Mobility's dayValue field
                                    $pricePerDay = 0;
                                    if (isset($vehicleData['dayValue']) && is_numeric($vehicleData['dayValue'])) {
                                        $pricePerDay = (float) $vehicleData['dayValue'];
                                    }

                                    // Extract mileage information
                                    $mileage = 'Unknown';
                                    if (isset($vehicleData['kmsIncluded'])) {
                                        $mileage = $vehicleData['kmsIncluded'] === 'true' ? 'Unlimited' : 'Limited';
                                    }

                                    // Extract extras if available
                                    $extras = [];
                                    if (isset($vehicleData['allExtras']['allExtra'])) {
                                        $extras = $vehicleData['allExtras']['allExtra'];
                                        if (!isset($extras[0])) {
                                            $extras = [$extras]; // Convert single extra to array
                                        }
                                    }

                                    $okMobilityVehicles->push((object) [
                                        'id' => 'okmobility_' . $groupId . '_' . md5($token),
                                        'source' => 'okmobility',
                                        'brand' => $vehicleName, // Use the full Group_Name as provided by API
                                        'model' => $vehicleName, // Same as brand since OK Mobility provides full descriptive name
                                        'sipp_code' => $vehicleData['SIPP'] ?? null,
                                        'image' => !empty($vehicleData['imageURL']) ? $vehicleData['imageURL'] : $this->getOkMobilityImageUrl($groupId),
                                        'price_per_day' => $pricePerDay,
                                        'price_per_week' => $pricePerDay * 7, // Calculate weekly price
                                        'price_per_month' => $pricePerDay * 30, // Calculate monthly price
                                        'currency' => 'EUR', // OK Mobility uses EUR
                                        // Don't include transmission, fuel, seating_capacity as OK Mobility doesn't provide these
                                        'mileage' => $mileage,
                                        'latitude' => (float) $locationLat,
                                        'longitude' => (float) $locationLng,
                                        'full_vehicle_address' => $locationAddress,
                                        'provider_pickup_id' => $providerLocationId,
                                        'station' => $vehicleData['Station'] ?? 'OK Mobility Station',
                                        'week_day_open' => $vehicleData['weekDayOpen'] ?? null,
                                        'week_day_close' => $vehicleData['weekDayClose'] ?? null,
                                        'preview_value' => isset($vehicleData['previewValue']) && is_numeric($vehicleData['previewValue']) ? (float) $vehicleData['previewValue'] : null,
                                        'total_day_value_with_tax' => isset($vehicleData['totalDayValueWithTax']) && is_numeric($vehicleData['totalDayValueWithTax']) ? (float) $vehicleData['totalDayValueWithTax'] : null,
                                        'benefits' => (object) [
                                            // Only include mileage info - OK Mobility doesn't provide cancellation, min age, or fuel policy in getMultiplePrices
                                            'limited_km_per_day' => $vehicleData['kmsIncluded'] !== 'true',
                                        ],
                                        // Don't include review_count and average_rating as OK Mobility doesn't provide these
                                        'products' => [[
                                            'type' => 'BAS',
                                            'total' => (string) $pricePerDay,
                                            'currency' => 'EUR',
                                            'token' => $token,
                                            'group_id' => $groupId,
                                        ]],
                                        'extras' => $extras,
                                        'insurance_options' => [],
                                        'ok_mobility_token' => $token,
                                        'ok_mobility_group_id' => $groupId,
                                        'extras_required' => !empty($vehicleData['extrasRequired']) ? explode(',', $vehicleData['extrasRequired']) : [],
                                        'extras_available' => !empty($vehicleData['extrasAvailable']) ? explode(',', $vehicleData['extrasAvailable']) : [],
                                    ]);
                                }
                            } else {
                                Log::warning("OK Mobility API response for vehicles was empty or malformed for location ID: " . $providerLocationId, [
                                    'response' => $okMobilityResponse,
                                    'xml_errors' => libxml_get_errors()
                                ]);
                                libxml_clear_errors();
                            }
                        }

                        Log::info('OK Mobility vehicles added to collection: ' . $okMobilityVehicles->count());
                    } catch (\Exception $e) {
                        Log::error("Error fetching OK Mobility vehicles: " . $e->getMessage(), [
                            'provider_location_id' => $providerLocationId,
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } elseif ($providerToFetch === 'wheelsys') {
                        Log::info('Attempting to fetch Wheelsys vehicles for location ID: ' . $providerLocationId);
                        Log::info('Search params: ', [
                            'pickup_id' => $providerLocationId,
                            'date_from' => $validated['date_from'],
                            'start_time' => $validated['start_time'] ?? '10:00',
                            'date_to' => $validated['date_to'],
                            'end_time' => $validated['end_time'] ?? '10:00'
                        ]);

                        // Convert date format from YYYY-MM-DD to DD/MM/YYYY for Wheelsys
                        $dateFromFormatted = date('d/m/Y', strtotime($validated['date_from']));
                        $dateToFormatted = date('d/m/Y', strtotime($validated['date_to']));

                        try {
                            $wheelsysResponse = $this->wheelsysService->getVehicles(
                                $providerLocationId,
                                $validated['dropoff_location_id'] ?? $providerLocationId,
                                $dateFromFormatted,
                                $validated['start_time'] ?? '10:00',
                                $dateToFormatted,
                                $validated['end_time'] ?? '10:00'
                            );

                            Log::info('Wheelsys API response received successfully');

                            // Handle different response structures
                            $ratesData = null;
                            if (isset($wheelsysResponse['vehicles']['Rates'])) {
                                $ratesData = $wheelsysResponse['vehicles']['Rates'];
                            } elseif (isset($wheelsysResponse['Rates'])) {
                                $ratesData = $wheelsysResponse['Rates'];
                            }

                            if ($ratesData && !empty($ratesData)) {
                                $wheelsysRates = collect($ratesData);

                                // Process each rate and convert to standard vehicle format
                                foreach ($wheelsysRates as $rate) {
                                    $standardVehicle = $this->wheelsysService->convertToStandardVehicle(
                                        $rate,
                                        $providerLocationId,
                                        $locationLat,
                                        $locationLng,
                                        $locationAddress
                                    );

                                    if ($standardVehicle) {
                                        $providerVehicles->push((object) $standardVehicle);
                                    }
                                }

                                Log::info('Wheelsys vehicles processed and added: ' . $providerVehicles->count());

                                /// Debug: Log the first few vehicles to see their structure
                                $wheelsysVehiclesDebug = $providerVehicles->where('source', 'wheelsys')->take(3);
                                foreach ($wheelsysVehiclesDebug as $debugVehicle) {
                                    Log::info('Wheelsys Vehicle Debug', [
                                        'id' => $debugVehicle->id,
                                        'source' => $debugVehicle->source,
                                        'brand' => $debugVehicle->brand,
                                        'model' => $debugVehicle->model,
                                        'price_per_day' => $debugVehicle->price_per_day,
                                        'image' => $debugVehicle->image,
                                        'availability' => $debugVehicle->availability ?? 'N/A'
                                    ]);
                                }

                            } else {
                            Log::warning("Wheelsys API response for vehicles was empty or no rates found for location ID: " . $providerLocationId, [
                                'has_response' => !empty($wheelsysResponse),
                                'response_keys' => $wheelsysResponse ? array_keys($wheelsysResponse) : [],
                                'has_rates' => isset($ratesData) && !empty($ratesData)
                            ]);
                        }

                    } catch (\Exception $e) {
                        // Handle the new robust error scenarios from WheelsysService
                        $errorMessage = $e->getMessage();

                        if (str_contains($errorMessage, 'temporarily unavailable due to repeated failures')) {
                            Log::warning('Wheelsys API circuit breaker is open - API temporarily unavailable', [
                                'provider_location_id' => $providerLocationId,
                                'circuit_breaker_status' => $this->wheelsysService->getCircuitBreakerStatus()
                            ]);
                        } else if (str_contains($errorMessage, 'Invalid API response structure')) {
                            Log::error('Wheelsys API response validation failed', [
                                'provider_location_id' => $providerLocationId,
                                'error' => $errorMessage
                            ]);
                        } else {
                            Log::error('Wheelsys API error after retries', [
                                'provider_location_id' => $providerLocationId,
                                'error' => $errorMessage,
                                'circuit_breaker_status' => $this->wheelsysService->getCircuitBreakerStatus()
                            ]);
                        }

                        // Continue with other providers - don't fail the entire search
                        Log::info('Continuing search with other providers after Wheelsys failure');
                    }
                } // Close Wheelsys elseif
            } // Close foreach $providersToFetch
        }

        $filteredProviderVehicles = $providerVehicles->filter(function ($vehicle) use ($validated) {
            if (!empty($validated['seating_capacity']) && $vehicle->seating_capacity != $validated['seating_capacity']) return false;
            if (!empty($validated['brand']) && strcasecmp($vehicle->brand, $validated['brand']) != 0) return false;
            if (!empty($validated['transmission']) && strcasecmp($vehicle->transmission, $validated['transmission']) != 0) return false;
            if (!empty($validated['fuel']) && strcasecmp($vehicle->fuel, $validated['fuel']) != 0) return false;
            if (!empty($validated['price_range'])) {
                $range = explode('-', $validated['price_range']);
                if ($vehicle->price_per_day < (int) $range[0] || $vehicle->price_per_day > (int) $range[1]) return false;
            }
            if (!empty($validated['mileage'])) {
                $range = explode('-', $validated['mileage']);
                $mileageValue = (int) $vehicle->mileage;
                if ($mileageValue < (int) $range[0] || $mileageValue > (int) $range[1]) return false;
            }
            return true;
        });

        // Filter OK Mobility vehicles separately
        $filteredOkMobilityVehicles = $okMobilityVehicles->filter(function ($vehicle) use ($validated) {
            if (!empty($validated['seating_capacity']) && $vehicle->seating_capacity != $validated['seating_capacity']) return false;
            if (!empty($validated['brand']) && strcasecmp($vehicle->brand, $validated['brand']) != 0) return false;
            if (!empty($validated['transmission']) && strcasecmp($vehicle->transmission, $validated['transmission']) != 0) return false;
            if (!empty($validated['fuel']) && strcasecmp($vehicle->fuel, $validated['fuel']) != 0) return false;
            if (!empty($validated['price_range'])) {
                $range = explode('-', $validated['price_range']);
                if ($vehicle->price_per_day < (int) $range[0] || $vehicle->price_per_day > (int) $range[1]) return false;
            }
            if (!empty($validated['mileage'])) {
                $range = explode('-', $validated['mileage']);
                $mileageValue = (int) $vehicle->mileage;
                if ($mileageValue < (int) $range[0] || $mileageValue > (int) $range[1]) return false;
            }
            return true;
        });

        $combinedVehicles = $internalVehiclesCollection->merge($filteredProviderVehicles)->merge($filteredOkMobilityVehicles);
        $perPage = 10;
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $combinedVehicles->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $vehicles = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $combinedVehicles->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $allProviderBrands = $providerVehicles->pluck('brand')->merge($okMobilityVehicles->pluck('brand'))->unique()->filter()->values()->all();
        $allProviderSeatingCapacities = $providerVehicles->pluck('seating_capacity')->merge($okMobilityVehicles->pluck('seating_capacity'))->unique()->filter()->values()->all();
        $allProviderTransmissions = $providerVehicles->pluck('transmission')->merge($okMobilityVehicles->pluck('transmission'))->unique()->filter()->values()->all();
        $allProviderFuels = $providerVehicles->pluck('fuel')->merge($okMobilityVehicles->pluck('fuel'))->unique()->filter()->values()->all();

        $combinedBrands = collect($brands)->merge($allProviderBrands)->map(fn($val) => trim($val))->unique(fn($val) => strtolower($val))->sort()->values()->all();
        $combinedSeatingCapacities = collect($seatingCapacities)->merge($allProviderSeatingCapacities)->unique()->sort()->values()->all();
        $combinedTransmissions = collect($transmissions)->merge($allProviderTransmissions)->map(fn($val) => trim($val))->unique(fn($val) => strtolower($val))->sort()->values()->all();
        $combinedFuels = collect($fuels)->merge($allProviderFuels)->map(fn($val) => trim($val))->unique(fn($val) => strtolower($val))->sort()->values()->all();

        $seoMeta = \App\Models\SeoMeta::with('translations')->where('url_slug', '/s')->first();

        // Create paginated OK Mobility vehicles for the frontend
        $okMobilityVehiclesPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $filteredOkMobilityVehicles->values(),
            $filteredOkMobilityVehicles->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('SearchResults', [
            'vehicles' => $vehicles,
            'okMobilityVehicles' => $okMobilityVehiclesPaginated,
            'filters' => $validated,
            'pagination_links' => $vehicles->links()->toHtml(),
            'brands' => $combinedBrands,
            'colors' => $colors,
            'seatingCapacities' => $combinedSeatingCapacities,
            'transmissions' => $combinedTransmissions,
            'fuels' => $combinedFuels,
            'mileages' => $mileages,
            'categories' => $categoriesFromOptions,
            'schema' => $vehicleListSchema,
            'seoMeta' => $seoMeta,
            'locale' => \Illuminate\Support\Facades\App::getLocale(),
        ]);
    }

    public function searchByCategory(Request $request, $locale, $slug = null)
    {
        $validated = $request->validate([
            'seating_capacity' => 'nullable|integer',
            'brand' => 'nullable|string',
            'transmission' => 'nullable|string|in:automatic,manual',
            'fuel' => 'nullable|string|in:petrol,diesel,electric',
            'price_range' => 'nullable|string',
            'color' => 'nullable|string',
            'mileage' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after:date_from',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
            'age' => 'nullable|integer',
            'rentalCode' => 'nullable|string',
            'currency' => 'nullable|string|max:3',
            'userid' => 'nullable|string',
            'username' => 'nullable|string',
            'language' => 'nullable|string',
            'full_credit' => 'nullable|string',
            'promocode' => 'nullable|string',
            'dropoff_location_id' => 'nullable|string',
            'dropoff_where' => 'nullable|string',
            'where' => 'nullable|string',
            'location' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'radius' => 'nullable|numeric',
            'package_type' => 'nullable|string|in:day,week,month',
            'matched_field' => 'nullable|string|in:location,city,state,country',
            'source' => 'nullable|string|in:internal,greenmotion', // New: Source of the location
            'greenmotion_location_id' => 'nullable|string', // New: GreenMotion specific location ID
        ]);

        $validated['category_slug'] = $slug;

        $internalVehiclesQuery = Vehicle::query();

        if ($slug) {
            $category = VehicleCategory::where('slug', $slug)->first();
            if ($category) {
                $internalVehiclesQuery->where('category_id', $category->id);
            } else {
                $internalVehiclesQuery->whereRaw('1 = 0');
            }
        }

        $internalVehiclesQuery->whereIn('status', ['available', 'rented'])
            ->with('images', 'bookings', 'vendorProfile', 'benefits');

        if (!empty($validated['date_from']) && !empty($validated['date_to'])) {
            $internalVehiclesQuery->whereDoesntHave('bookings', function ($q) use ($validated) {
                $q->where(function ($query) use ($validated) {
                    $query->whereBetween('bookings.pickup_date', [$validated['date_from'], $validated['date_to']])
                        ->orWhereBetween('bookings.return_date', [$validated['date_from'], $validated['date_to']])
                        ->orWhere(function ($q) use ($validated) {
                            $q->where('bookings.pickup_date', '<=', $validated['date_from'])
                                ->where('bookings.return_date', '>=', $validated['date_to']);
                        });
                });
            });

            $internalVehiclesQuery->whereDoesntHave('blockings', function ($q) use ($validated) {
                $q->where(function ($query) use ($validated) {
                    $query->whereBetween('blocking_start_date', [$validated['date_from'], $validated['date_to']])
                        ->orWhereBetween('blocking_end_date', [$validated['date_from'], $validated['date_to']])
                        ->orWhere(function ($q) use ($validated) {
                            $q->where('blocking_start_date', '<=', $validated['date_from'])
                                ->where('blocking_end_date', '>=', $validated['date_to']);
                        });
                });
            });
        }

        if (!empty($validated['source'])) {
            if ($validated['source'] === 'internal') {
                if (!empty($validated['matched_field'])) {
                    $fieldToQuery = null;
                    $valueToQuery = null;

                    switch ($validated['matched_field']) {
                        case 'location':
                            if (!empty($validated['location'])) {
                                $fieldToQuery = 'location';
                                $valueToQuery = $validated['location'];
                            }
                            break;
                        case 'city':
                            if (!empty($validated['city'])) {
                                $fieldToQuery = 'city';
                                $valueToQuery = $validated['city'];
                            }
                            break;
                        case 'state':
                            if (!empty($validated['state'])) {
                                $fieldToQuery = 'state';
                                $valueToQuery = $validated['state'];
                            }
                            break;
                        case 'country':
                            if (!empty($validated['country'])) {
                                $fieldToQuery = 'country';
                                $valueToQuery = $validated['country'];
                            }
                            break;
                    }
                    if ($fieldToQuery && $valueToQuery) {
                        $internalVehiclesQuery->where($fieldToQuery, $valueToQuery);
                    }
                } elseif (!empty($validated['latitude']) && !empty($validated['longitude']) && !empty($validated['radius'])) {
                    $lat = $validated['latitude'];
                    $lon = $validated['longitude'];
                    $radiusInKm = $validated['radius'] / 1000;

                    $internalVehiclesQuery->selectRaw('*, ( 6371 * acos(
                        cos(radians(?)) * cos(radians(latitude)) *
                        cos(radians(longitude) - radians(?)) +
                        sin(radians(?)) * sin(radians(latitude))
                    ) ) AS distance_in_km', [
                        $lat,
                        $lon,
                        $lat
                    ])
                        ->havingRaw('distance_in_km <= ?', [$radiusInKm])
                        ->orderBy('distance_in_km');
                } elseif (!empty($validated['where'])) {
                    $searchTerm = $validated['where'];
                    $internalVehiclesQuery->where(function ($q) use ($searchTerm) {
                        $q->where('location', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('city', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('state', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('country', 'LIKE', "%{$searchTerm}%");
                    });
                }
            } elseif ($validated['source'] === 'greenmotion') {
                $internalVehiclesQuery->whereRaw('1 = 0');
            }
        } else { 
            if (!empty($validated['matched_field'])) {
                $fieldToQuery = null;
                $valueToQuery = null;

                switch ($validated['matched_field']) {
                    case 'location':
                        if (!empty($validated['location'])) {
                            $fieldToQuery = 'location';
                            $valueToQuery = $validated['location'];
                        }
                        break;
                    case 'city':
                        if (!empty($validated['city'])) {
                            $fieldToQuery = 'city';
                            $valueToQuery = $validated['city'];
                        }
                        break;
                    case 'state':
                        if (!empty($validated['state'])) {
                            $fieldToQuery = 'state';
                            $valueToQuery = $validated['state'];
                        }
                        break;
                    case 'country':
                        if (!empty($validated['country'])) {
                            $fieldToQuery = 'country';
                            $valueToQuery = $validated['country'];
                        }
                        break;
                }
                if ($fieldToQuery && $valueToQuery) {
                    $internalVehiclesQuery->where($fieldToQuery, $valueToQuery);
                }
            } elseif (!empty($validated['latitude']) && !empty($validated['longitude']) && !empty($validated['radius'])) {
                $lat = $validated['latitude'];
                $lon = $validated['longitude'];
                $radiusInKm = $validated['radius'] / 1000;

                $internalVehiclesQuery->selectRaw('*, ( 6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                ) ) AS distance_in_km', [
                    $lat,
                    $lon,
                    $lat
                ])
                    ->havingRaw('distance_in_km <= ?', [$radiusInKm])
                    ->orderBy('distance_in_km');
            } elseif (!empty($validated['where'])) {
                $searchTerm = $validated['where'];
                $internalVehiclesQuery->where(function ($q) use ($searchTerm) {
                    $q->where('location', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('city', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('state', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('country', 'LIKE', "%{$searchTerm}%");
                });
            }
        }

        $queryForOptionsCategory = clone $internalVehiclesQuery;
        $potentialVehiclesForOptionsCategory = $queryForOptionsCategory->get();

        $brands = $potentialVehiclesForOptionsCategory->pluck('brand')->unique()->filter()->values()->all();
        $colors = $potentialVehiclesForOptionsCategory->pluck('color')->unique()->filter()->values()->all();
        $seatingCapacities = $potentialVehiclesForOptionsCategory->pluck('seating_capacity')->unique()->filter()->values()->all();
        $transmissions = $potentialVehiclesForOptionsCategory->pluck('transmission')->unique()->filter()->values()->all();
        $fuels = $potentialVehiclesForOptionsCategory->pluck('fuel')->unique()->filter()->values()->all();
        $mileages = $potentialVehiclesForOptionsCategory->pluck('mileage')->unique()->filter()->map(function ($mileage) {
            if ($mileage >= 0 && $mileage <= 25) return '0-25';
            if ($mileage > 25 && $mileage <= 50) return '25-50';
            if ($mileage > 50 && $mileage <= 75) return '50-75';
            if ($mileage > 75 && $mileage <= 100) return '75-100';
            if ($mileage > 100 && $mileage <= 120) return '100-120';
            return null;
        })->filter()->unique()->values()->all();
        $categoriesFromOptions = [];
        $categoryIds = $potentialVehiclesForOptionsCategory->pluck('category_id')->unique()->filter();
        if ($categoryIds->isNotEmpty()) {
            $categoriesFromOptions = VehicleCategory::whereIn('id', $categoryIds)->select('id', 'name')->get()->toArray();
        }
        $allCategoriesForPage = VehicleCategory::select('id', 'name', 'slug')->get()->toArray();


        if (!empty($validated['seating_capacity'])) {
            $internalVehiclesQuery->where('seating_capacity', $validated['seating_capacity']);
        }
        if (!empty($validated['brand'])) {
            $internalVehiclesQuery->where('brand', $validated['brand']);
        }
        if (!empty($validated['transmission'])) {
            $internalVehiclesQuery->where('transmission', $validated['transmission']);
        }
        if (!empty($validated['fuel'])) {
            $internalVehiclesQuery->where('fuel', $validated['fuel']);
        }
        if (!empty($validated['price_range'])) {
            $range = explode('-', $validated['price_range']);
            $internalVehiclesQuery->whereBetween('price_per_day', [(int) $range[0], (int) $range[1]]);
        }
        if (!empty($validated['color'])) {
            $internalVehiclesQuery->where('color', $validated['color']);
        }
        if (!empty($validated['mileage'])) {
            $range = explode('-', $validated['mileage']);
            $internalVehiclesQuery->whereBetween('mileage', [(int) $range[0], (int) $range[1]]);
        }

        if (!empty($validated['package_type'])) {
            switch ($validated['package_type']) {
                case 'week':
                    $internalVehiclesQuery->whereNotNull('price_per_week')->orderBy('price_per_week');
                    break;
                case 'month':
                    $internalVehiclesQuery->whereNotNull('price_per_month')->orderBy('price_per_month');
                    break;
                default:
                    $internalVehiclesQuery->whereNotNull('price_per_day')->orderBy('price_per_day');
                    break;
            }
        }

        $internalVehiclesEloquent = $internalVehiclesQuery->get();
        $vehicleListSchema = SchemaBuilder::vehicleList($internalVehiclesEloquent, 'Vehicle Search Results', $validated);
        $internalVehiclesData = $internalVehiclesEloquent->map(function ($vehicle) {
            $reviews = Review::where('vehicle_id', $vehicle->id)
                ->where('status', 'approved')
                ->get();
            $vehicle->review_count = $reviews->count();
            $vehicle->average_rating = $reviews->avg('rating') ?? 0;

            if (isset($vehicle->distance_in_km)) {
                $vehicle->distance_in_km = intval($vehicle->distance_in_km);
            }
            $vehicle->source = 'internal';

            return (object) $vehicle->toArray();
        });

        $greenMotionVehicles = collect();
        if ((isset($validated['source']) && $validated['source'] === 'greenmotion') || empty($validated['source'])) {
            if (!empty($validated['date_from']) && !empty($validated['date_to'])) {
                $gmLocationId = $validated['greenmotion_location_id'] ?? null;
                $gmLocationLat = null;
                $gmLocationLng = null;
                $gmLocationAddress = null;

                if (!$gmLocationId && (!empty($validated['where']) || (!empty($validated['latitude']) && !empty($validated['longitude'])))) {
                    $searchParam = $validated['where'] ?? ($validated['latitude'] . ',' . $validated['longitude']);
                    $unifiedLocations = $this->locationSearchService->searchLocations($searchParam);
                    $matchedGmLocation = collect($unifiedLocations)->first(function($loc) {
                        return $loc['source'] === 'greenmotion' && !empty($loc['greenmotion_location_id']);
                    });
                    if ($matchedGmLocation) {
                        $gmLocationId = $matchedGmLocation['greenmotion_location_id'];
                        $gmLocationLat = $matchedGmLocation['latitude'];
                        $gmLocationLng = $matchedGmLocation['longitude'];
                        $gmLocationAddress = $matchedGmLocation['label'] . ', ' . $matchedGmLocation['below_label'];
                    }
                } elseif ($gmLocationId) {
                    $allUnifiedLocations = $this->locationSearchService->getAllLocations();
                    $specificGmLocation = collect($allUnifiedLocations)->firstWhere('greenmotion_location_id', $gmLocationId);
                    if ($specificGmLocation) {
                        $gmLocationLat = $specificGmLocation['latitude'];
                        $gmLocationLng = $specificGmLocation['longitude'];
                        $gmLocationAddress = $specificGmLocation['label'] . ', ' . $specificGmLocation['below_label'];
                    }
                }

                if ($gmLocationId && $gmLocationLat !== null && $gmLocationLng !== null) {
                    try {
                        $gmOptions = [
                            'language' => $validated['language'] ?? app()->getLocale(),
                            'rentalCode' => $validated['rentalCode'] ?? '1',
                            'currency' => $validated['currency'] ?? null,
                            'fuel' => $validated['fuel'] ?? null,
                            'userid' => $validated['userid'] ?? null,
                            'username' => $validated['username'] ?? null,
                            'full_credit' => $validated['full_credit'] ?? null,
                            'promocode' => $validated['promocode'] ?? null,
                            'dropoff_location_id' => $validated['dropoff_location_id'] ?? null,
                        ];

                        $gmResponse = $this->greenMotionService->getVehicles(
                            $gmLocationId,
                            $validated['date_from'],
                            $validated['start_time'] ?? '09:00',
                            $validated['date_to'],
                            $validated['end_time'] ?? '09:00',
                            $validated['age'] ?? 35,
                            array_filter($gmOptions)
                        );

                        if ($gmResponse) {
                            libxml_use_internal_errors(true);
                            $xmlObject = simplexml_load_string($gmResponse);
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

                                    $minDriverAge = 0;
                                    $fuelPolicy = '';
                                    if (!empty($products)) {
                                        $minDriverAge = (int) ($products[0]['minage'] ?? 0);
                                        $fuelPolicy = $products[0]['fuelpolicy'] ?? '';
                                    }

                                    $brandName = explode(' ', (string) $vehicle['name'])[0];
                                    $greenMotionVehicles->push((object) [
                                        'id' => 'gm_' . (string) $vehicle['id'],
                                        'source' => 'greenmotion',
                                        'brand' => $brandName,
                                        'model' => (string) $vehicle['name'],
                                        'image' => urldecode((string) $vehicle['image']),
                                        'price_per_day' => (isset($vehicle->total) && is_numeric((string)$vehicle->total)) ? (float) (string)$vehicle->total : 0.0,
                                        'price_per_week' => null,
                                        'price_per_month' => null,
                                        'currency' => (isset($vehicle->total['currency']) && !empty((string)$vehicle->total['currency'])) ? (string) $vehicle->total['currency'] : 'EUR',
                                        'transmission' => (string) $vehicle->transmission,
                                        'fuel' => (string) $vehicle->fuel,
                                        'seating_capacity' => (int) $vehicle->adults + (int) $vehicle->children,
                                        'mileage' => (string) $vehicle->mpg,
                                        'latitude' => (float) $gmLocationLat,
                                        'longitude' => (float) $gmLocationLng,
                                        'full_vehicle_address' => $gmLocationAddress,
                                        'greenmotion_location_id' => $gmLocationId,
                                        'benefits' => (object) [
                                            'cancellation_available_per_day' => true,
                                            'limited_km_per_day' => false,
                                            'minimum_driver_age' => $minDriverAge,
                                            'fuel_policy' => $fuelPolicy,
                                        ],
                                        'review_count' => 0,
                                        'average_rating' => 0,
                                        'products' => $products,
                                        'options' => [],
                                        'insurance_options' => [],
                                    ]);
                                }
                            } else {
                                Log::warning('GreenMotion API response for vehicles was empty or malformed for location ID: ' . $gmLocationId);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('Error fetching GreenMotion vehicles: ' . $e->getMessage());
                    }
                }
            }
        }

        $filteredGreenMotionVehicles = $greenMotionVehicles->filter(function ($vehicle) use ($validated) {
            if (!empty($validated['seating_capacity']) && $vehicle->seating_capacity != $validated['seating_capacity']) return false;
            if (!empty($validated['brand']) && strcasecmp($vehicle->brand, $validated['brand']) != 0) return false;
            if (!empty($validated['transmission']) && strcasecmp($vehicle->transmission, $validated['transmission']) != 0) return false;
            if (!empty($validated['fuel']) && strcasecmp($vehicle->fuel, $validated['fuel']) != 0) return false;
            if (!empty($validated['price_range'])) {
                $range = explode('-', $validated['price_range']);
                if ($vehicle->price_per_day < (int) $range[0] || $vehicle->price_per_day > (int) $range[1]) return false;
            }
            if (!empty($validated['mileage'])) {
                $range = explode('-', $validated['mileage']);
                $mileageValue = (int) $vehicle->mileage;
                if ($mileageValue < (int) $range[0] || $mileageValue > (int) $range[1]) return false;
            }
            return true;
        });

        $combinedVehicles = $internalVehiclesData->merge($filteredGreenMotionVehicles);
        $perPage = 10;
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $combinedVehicles->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $vehicles = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $combinedVehicles->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $gmBrands = $greenMotionVehicles->pluck('brand')->unique()->filter()->values()->all();
        $gmSeatingCapacities = $greenMotionVehicles->pluck('seating_capacity')->unique()->filter()->values()->all();
        $gmTransmissions = $greenMotionVehicles->pluck('transmission')->unique()->filter()->values()->all();
        $gmFuels = $greenMotionVehicles->pluck('fuel')->unique()->filter()->values()->all();

        $combinedBrands = collect($brands)->merge($gmBrands)->map(fn($val) => trim($val))->unique(fn($val) => strtolower($val))->sort()->values()->all();
        $combinedSeatingCapacities = collect($seatingCapacities)->merge($gmSeatingCapacities)->unique()->sort()->values()->all();
        $combinedTransmissions = collect($transmissions)->merge($gmTransmissions)->map(fn($val) => trim($val))->unique(fn($val) => strtolower($val))->sort()->values()->all();
        $combinedFuels = collect($fuels)->merge($gmFuels)->map(fn($val) => trim($val))->unique(fn($val) => strtolower($val))->sort()->values()->all();

        $seoMeta = \App\Models\SeoMeta::with('translations')->where('url_slug', '/s')->first();

        return Inertia::render('SearchResults', [
            'vehicles' => $vehicles,
            'filters' => $validated,
            'pagination_links' => $vehicles->links()->toHtml(),
            'brands' => $combinedBrands,
            'colors' => $colors,
            'seatingCapacities' => $combinedSeatingCapacities,
            'transmissions' => $combinedTransmissions,
            'fuels' => $combinedFuels,
            'mileages' => $mileages,
            'categories' => $categoriesFromOptions,
            'schema' => $vehicleListSchema,
            'seoMeta' => $seoMeta,
            'locale' => \Illuminate\Support\Facades\App::getLocale(),
        ]);
    }

    public function searchUnifiedLocations(Request $request)
    {
        $validated = $request->validate([
            'search_term' => 'sometimes|string|min:2',
        ]);

        $searchTerm = $validated['search_term'] ?? null;

        if ($searchTerm) {
            $locations = $this->locationSearchService->searchLocations($searchTerm);
        } else {
            $locations = $this->locationSearchService->getAllLocations();
        }

        return response()->json($locations);
    }

    /**
     * Generate OK Mobility vehicle image URL based on GroupID
     * OK Mobility uses pattern: /alquilar/images/grupos/{groupid}.png
     */
    private function getOkMobilityImageUrl($groupId): string
    {
        // Use the actual OK Mobility image pattern from their website
        return "https://www.okmobility.com/alquilar/images/grupos/{$groupId}.png";
    }

    /**
     * Extract brand name from vehicle model
     */
    private function extractBrandFromModel($model): string
    {
        if (empty($model)) {
            return 'Adobe';
        }

        // Common car brands to extract from model names
        $brands = ['Suzuki', 'Toyota', 'Nissan', 'Hyundai', 'Honda', 'Ford', 'Chevrolet', 'Mitsubishi', 'Mazda', 'Volkswagen'];

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
     * Get Adobe vehicle image URL
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
