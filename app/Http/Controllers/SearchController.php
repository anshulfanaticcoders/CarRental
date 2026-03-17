<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Helpers\SchemaBuilder;
use App\Services\LocationSearchService; // Import LocationSearchService
use App\Services\Search\GatewaySearchService;
use App\Services\Search\LegacyProviderSearchService;
use App\Services\Vehicles\GatewayVehicleTransformer;
use Illuminate\Support\Facades\Log; // Import Log facade
use App\Services\Seo\SeoMetaResolver;
use Illuminate\Support\Facades\App;

class SearchController extends Controller
{
    protected $locationSearchService;
    protected $gatewaySearchService;
    protected $gatewayVehicleTransformer;
    protected $legacyProviderSearchService;

    public function __construct(
        LocationSearchService $locationSearchService,
        GatewaySearchService $gatewaySearchService,
        GatewayVehicleTransformer $gatewayVehicleTransformer,
        LegacyProviderSearchService $legacyProviderSearchService
    ) {
        $this->locationSearchService = $locationSearchService;
        $this->gatewaySearchService = $gatewaySearchService;
        $this->gatewayVehicleTransformer = $gatewayVehicleTransformer;
        $this->legacyProviderSearchService = $legacyProviderSearchService;
    }

    public function search(Request $request)
    {
        $normalizeTime = function ($value, string $default): string {
            $value = is_string($value) ? trim($value) : '';
            if ($value === '') {
                return $default;
            }

            // Accept HH:MM (24h). Fallback to default for anything else.
            if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
                return $default;
            }

            return $value;
        };

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
            'dropoff_unified_location_id' => 'nullable|string',
            'dropoff_where' => 'nullable|string',
            'dropoff_latitude' => 'nullable|numeric',
            'dropoff_longitude' => 'nullable|numeric',
            'where' => 'nullable|string',
            'location' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'radius' => 'nullable|numeric',
            'package_type' => 'nullable|string|in:day,week,month',
            'category_id' => 'nullable|string', // Allow both numeric IDs and string category names (for providers)
            'matched_field' => 'nullable|string|in:location,city,state,country',
            'provider' => 'nullable|string', // Replaces 'source'
            'provider_pickup_id' => 'nullable|string', // Replaces 'greenmotion_location_id'
            'unified_location_id' => 'required|integer', // Unified location ID for multi-provider search
        ]);

        // Normalize incoming times early (handles empty string case).
        $validated['start_time'] = $normalizeTime($validated['start_time'] ?? '', '09:00');
        $validated['end_time'] = $normalizeTime($validated['end_time'] ?? '', '09:00');

        // Calculate rental duration in days for price-per-day normalization
        $dtStart = \Carbon\Carbon::parse(($validated['date_from'] ?? now()->addDays(1)->format('Y-m-d')) . ' ' . ($validated['start_time'] ?? '09:00'));
        $dtEnd = \Carbon\Carbon::parse(($validated['date_to'] ?? now()->addDays(2)->format('Y-m-d')) . ' ' . ($validated['end_time'] ?? '09:00'));
        $rentalDays = max(1, (int) ceil($dtStart->diffInMinutes($dtEnd) / 1440));
        Log::info("Global rental duration: {$rentalDays} days.");

        // Check if any location parameter is provided - if not, return empty results
        $hasLocation = !empty($validated['where']) ||
                       !empty($validated['location']) ||
                       !empty($validated['city']) ||
                       !empty($validated['state']) ||
                       !empty($validated['country']) ||
                       !empty($validated['latitude']) ||
                       !empty($validated['provider_pickup_id']) ||
                       !empty($validated['unified_location_id']);

        $emptyResultsResponse = function (?string $searchError = null) use ($request, $validated) {
            $emptyVehicles = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                500,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            $seo = app(SeoMetaResolver::class)->resolveForRoute(
                'search',
                [],
                App::getLocale(),
                route('search', ['locale' => App::getLocale()]),
                'noindex,follow'
            )->toArray();

            return Inertia::render('SearchResults', [
                'vehicles' => $emptyVehicles,
                'okMobilityVehicles' => $emptyVehicles,
                'renteonVehicles' => $emptyVehicles,
                'providerStatus' => [],
                'searchError' => $searchError,
                'filters' => $validated,
                'pagination_links' => '',
                'brands' => [],
                'colors' => [],
                'seatingCapacities' => [],
                'transmissions' => [],
                'fuels' => [],
                'mileages' => [],
                'categories' => [],
                'schema' => null,
                'seo' => $seo,
                'locale' => App::getLocale(),
                'optionalExtras' => [],
                'locationName' => null,
            ]);
        };

        if (!$hasLocation) {
            Log::info('No location provided - returning empty vehicle results');
            return $emptyResultsResponse();
        }

        $internalVehiclesQuery = Vehicle::query()->whereIn('status', ['available', 'rented'])
            ->with(['images', 'bookings', 'vendor.profile', 'vendorProfile', 'vendorProfileData', 'benefits', 'vendorPlans', 'addons', 'operatingHours']);

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

        // Filter by operating hours: exclude vehicles closed at pickup/return time
        $startTime = $validated['start_time'] ?? null;
        $endTime = $validated['end_time'] ?? null;
        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        if ($startTime && $dateFrom) {
            // Carbon dayOfWeekIso: 1=Monday ... 7=Sunday → convert to 0=Monday ... 6=Sunday
            $pickupDay = \Carbon\Carbon::parse($dateFrom)->dayOfWeekIso - 1;
            $internalVehiclesQuery->whereHas('operatingHours', function ($q) use ($pickupDay, $startTime) {
                $q->where('day_of_week', $pickupDay)
                  ->where('is_open', true)
                  ->where('open_time', '<=', $startTime)
                  ->where('close_time', '>=', $startTime);
            });
        }

        if ($endTime && $dateTo) {
            $returnDay = \Carbon\Carbon::parse($dateTo)->dayOfWeekIso - 1;
            $internalVehiclesQuery->whereHas('operatingHours', function ($q) use ($returnDay, $endTime) {
                $q->where('day_of_week', $returnDay)
                  ->where('is_open', true)
                  ->where('open_time', '<=', $endTime)
                  ->where('close_time', '>=', $endTime);
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
            if ($mileage >= 0 && $mileage <= 25)
                return '0-25';
            if ($mileage > 25 && $mileage <= 50)
                return '25-50';
            if ($mileage > 50 && $mileage <= 75)
                return '50-75';
            if ($mileage > 75 && $mileage <= 100)
                return '75-100';
            if ($mileage > 100 && $mileage <= 120)
                return '100-120';
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
        // Note: price_range filtering removed from backend - handled on frontend with currency conversion
        // if (!empty($validated['price_range'])) {
        //     $range = explode('-', $validated['price_range']);
        //     $internalVehiclesQuery->whereBetween('price_per_day', [(int) $range[0], (int) $range[1]]);
        // }
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

        $internalVehiclesEloquent = $internalVehiclesQuery->with('category')->get();
        $vehicleListSchema = SchemaBuilder::vehicleList($internalVehiclesEloquent, 'Vehicle Search Results', $validated);
        $internalVehiclesData = $internalVehiclesEloquent->map(function ($vehicle) use ($rentalDays) {
            $reviews = Review::where('vehicle_id', $vehicle->id)
                ->where('status', 'approved')
                ->get();
            $vehicle->review_count = $reviews->count();
            $vehicle->average_rating = $reviews->avg('rating') ?? 0;
            $vehicle->source = 'internal';

            $data = $vehicle->toArray();
            $data['category'] = $vehicle->category->name ?? 'Unknown';
            // Normalize pricing and currency for internal vehicles
            $rawCurrency = $vehicle->vendorProfile->currency ?? 'USD';
            $currencyMap = [
                '€' => 'EUR',
                '€' => 'EUR', // Handling potential different encodings if necessary
                '$' => 'USD',
                '₹' => 'INR',
                '₽' => 'RUB',
                'A$' => 'AUD',
                '£' => 'GBP',
            ];
            $data['currency'] = $currencyMap[$rawCurrency] ?? $rawCurrency;
            
            $data['total_price'] = (float) ($vehicle->price_per_day * $rentalDays);
            $data['operating_hours'] = $vehicle->operatingHours->map(function ($h) {
                return [
                    'day' => $h->day_of_week,
                    'day_name' => $h->dayName(),
                    'is_open' => $h->is_open,
                    'open_time' => $h->open_time,
                    'close_time' => $h->close_time,
                ];
            })->values()->toArray();
            return $data;
        })->values()->all();

        $internalVehiclesCollection = collect($internalVehiclesData);

        $legacySeo = app(SeoMetaResolver::class)->resolveForRoute(
            'search',
            [],
            App::getLocale(),
            route('search', ['locale' => App::getLocale()]),
            'noindex,follow'
        )->toArray();

        // --- Gateway-based provider search (replaces all provider-specific code below) ---
        if (config('vrooem.enabled')) {
            return $this->searchViaGateway(
                $request, $validated, $rentalDays,
                $internalVehiclesCollection,
                $brands, $colors, $seatingCapacities, $transmissions, $fuels, $mileages,
                $categoriesFromOptions, $vehicleListSchema
            );
        }

        return Inertia::render('SearchResults', $this->legacyProviderSearchService->buildPageProps(
            $request,
            $validated,
            $rentalDays,
            $internalVehiclesCollection,
            [
                'brands' => $brands,
                'colors' => $colors,
                'seatingCapacities' => $seatingCapacities,
                'transmissions' => $transmissions,
                'fuels' => $fuels,
                'mileages' => $mileages,
                'categories' => $categoriesFromOptions,
                'schema' => $vehicleListSchema,
                'seo' => $legacySeo,
                'locale' => App::getLocale(),
            ]
        ));
    }


    /**
     * Gateway-based search: replaces all provider-specific fetching with a single gateway call.
     * Internal vehicles are still fetched from the local DB.
     */
    private function searchViaGateway(
        Request $request,
        array $validated,
        int $rentalDays,
        $internalVehiclesCollection,
        array $brands,
        array $colors,
        array $seatingCapacities,
        array $transmissions,
        array $fuels,
        array $mileages,
        array $categoriesFromOptions,
        $vehicleListSchema
    ) {
        $seo = app(SeoMetaResolver::class)->resolveForRoute(
            'search',
            [],
            App::getLocale(),
            route('search', ['locale' => App::getLocale()]),
            'noindex,follow'
        )->toArray();

        $props = $this->gatewaySearchService->buildPageProps(
            $request,
            $validated,
            $rentalDays,
            collect($internalVehiclesCollection),
            [
                'brands' => $brands,
                'colors' => $colors,
                'seatingCapacities' => $seatingCapacities,
                'transmissions' => $transmissions,
                'fuels' => $fuels,
                'mileages' => $mileages,
                'categories' => $categoriesFromOptions,
                'schema' => $vehicleListSchema,
                'seo' => $seo,
                'locale' => App::getLocale(),
            ],
            fn (array $gatewayVehicle, int $days): array => $this->gatewayVehicleTransformer->transform($gatewayVehicle, $days),
            fn (string $supplierId): string => $this->gatewayVehicleTransformer->normalizeSupplierId($supplierId)
        );

        return Inertia::render('SearchResults', $props);
    }

    public function searchUnifiedLocations(Request $request)
    {
        $validated = $request->validate([
            'search_term' => 'sometimes|string|min:2',
            'limit' => 'sometimes|integer|min:1|max:50',
            'unified_location_id' => 'sometimes|integer',
        ]);

        // Direct lookup by ID
        if (!empty($validated['unified_location_id'])) {
            $location = $this->locationSearchService->getLocationByUnifiedId($validated['unified_location_id']);
            return response()->json($location ? [$location] : []);
        }

        $searchTerm = $validated['search_term'] ?? null;
        $limit = $validated['limit'] ?? 20;

        if ($searchTerm) {
            $locations = $this->locationSearchService->searchLocations($searchTerm, $limit);
        } else {
            $locations = $this->locationSearchService->getAllLocations();
        }

        return response()->json($locations);
    }

}
