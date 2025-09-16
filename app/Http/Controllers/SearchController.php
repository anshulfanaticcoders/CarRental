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
use App\Services\LocationSearchService; // Import LocationSearchService
use Illuminate\Support\Facades\Log; // Import Log facade

class SearchController extends Controller
{
    protected $greenMotionService;
    protected $locationSearchService;

    public function __construct(GreenMotionService $greenMotionService, LocationSearchService $locationSearchService)
    {
        $this->greenMotionService = $greenMotionService;
        $this->locationSearchService = $locationSearchService;
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

            foreach ($providersToFetch as $providerToFetch) {
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
            }
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

        $combinedVehicles = $internalVehiclesCollection->merge($filteredProviderVehicles);
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

        $gmBrands = $providerVehicles->pluck('brand')->unique()->filter()->values()->all();
        $gmSeatingCapacities = $providerVehicles->pluck('seating_capacity')->unique()->filter()->values()->all();
        $gmTransmissions = $providerVehicles->pluck('transmission')->unique()->filter()->values()->all();
        $gmFuels = $providerVehicles->pluck('fuel')->unique()->filter()->values()->all();

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
}
