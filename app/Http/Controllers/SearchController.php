<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VendorLocation;
use App\Services\LocationSearchService;
use App\Services\Search\GatewaySearchService;
use App\Services\Search\InternalSearchVehicleFactory; // Import LocationSearchService
use App\Services\Seo\SeoMetaResolver;
use App\Services\Vehicles\GatewayVehicleTransformer;
use App\Services\Vehicles\InternalVehicleAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App; // Import Log facade
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SearchController extends Controller
{
    protected $locationSearchService;

    protected $gatewaySearchService;

    protected $gatewayVehicleTransformer;

    protected $internalSearchVehicleFactory;

    protected $internalVehicleAvailabilityService;

    public function __construct(
        LocationSearchService $locationSearchService,
        GatewaySearchService $gatewaySearchService,
        GatewayVehicleTransformer $gatewayVehicleTransformer,
        InternalSearchVehicleFactory $internalSearchVehicleFactory,
        InternalVehicleAvailabilityService $internalVehicleAvailabilityService
    ) {
        $this->locationSearchService = $locationSearchService;
        $this->gatewaySearchService = $gatewaySearchService;
        $this->gatewayVehicleTransformer = $gatewayVehicleTransformer;
        $this->internalSearchVehicleFactory = $internalSearchVehicleFactory;
        $this->internalVehicleAvailabilityService = $internalVehicleAvailabilityService;
    }

    public function search(Request $request)
    {
        $normalizeTime = function ($value, string $default): string {
            $value = is_string($value) ? trim($value) : '';
            if ($value === '') {
                return $default;
            }

            // Accept HH:MM (24h). Fallback to default for anything else.
            if (! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
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
        $dtStart = \Carbon\Carbon::parse(($validated['date_from'] ?? now()->addDays(1)->format('Y-m-d')).' '.($validated['start_time'] ?? '09:00'));
        $dtEnd = \Carbon\Carbon::parse(($validated['date_to'] ?? now()->addDays(2)->format('Y-m-d')).' '.($validated['end_time'] ?? '09:00'));
        $rentalDays = max(1, (int) ceil($dtStart->diffInMinutes($dtEnd) / 1440));
        Log::info("Global rental duration: {$rentalDays} days.");

        // Check if any location parameter is provided - if not, return empty results
        $hasLocation = ! empty($validated['where']) ||
                       ! empty($validated['location']) ||
                       ! empty($validated['city']) ||
                       ! empty($validated['state']) ||
                       ! empty($validated['country']) ||
                       ! empty($validated['latitude']) ||
                       ! empty($validated['provider_pickup_id']) ||
                       ! empty($validated['unified_location_id']);

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

            if (empty($seo['title']) || $seo['title'] === config('app.name')) {
                $locationLabel = $validated['where'] ?? null;
                $seo['title'] = $locationLabel
                    ? "Car Rentals in {$locationLabel} — Compare & Book | Vrooem"
                    : 'Search Car Rentals — Compare Deals Worldwide | Vrooem';
            }

            return Inertia::render('SearchResults', [
                'vehicles' => $emptyVehicles,
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
                'recommendedLocations' => $this->locationSearchService->nearbyLocations($validated),
            ]);
        };

        if (! $hasLocation) {
            Log::info('No location provided - returning empty vehicle results');

            return $emptyResultsResponse();
        }

        $originalUnifiedLocationId = (int) ($validated['unified_location_id'] ?? 0);
        try {
            $resolvedSearchLocation = $this->locationSearchService->resolveSearchLocation($validated);
        } catch (\Throwable $exception) {
            Log::error('Search location resolution failed', [
                'requested_unified_location_id' => $originalUnifiedLocationId,
                'provider' => $validated['provider'] ?? null,
                'provider_pickup_id' => $validated['provider_pickup_id'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            return $emptyResultsResponse('We could not verify this pickup location. Please choose a location from the search suggestions.');
        }
        $requestedProvider = strtolower(trim((string) ($validated['provider'] ?? '')));
        $blockInternalProviderForResolvedLocation = false;
        $hasResolvedSearchLocation = false;
        $internalProviderPickupId = null;

        if (empty($resolvedSearchLocation['unified_location_id'])) {
            Log::warning('Search location could not be verified; returning exact empty result set.', [
                'requested_unified_location_id' => $originalUnifiedLocationId,
                'provider' => $validated['provider'] ?? null,
                'provider_pickup_id' => $validated['provider_pickup_id'] ?? null,
                'where' => $validated['where'] ?? null,
            ]);

            return $emptyResultsResponse('We could not verify this pickup location. Please choose a location from the search suggestions.');
        }

        if (! empty($resolvedSearchLocation['unified_location_id'])) {
            $hasResolvedSearchLocation = true;
            $resolvedUnifiedLocationId = (int) $resolvedSearchLocation['unified_location_id'];

            if ($originalUnifiedLocationId > 0 && $originalUnifiedLocationId !== $resolvedUnifiedLocationId) {
                Log::info('Search location id normalized from stale request data.', [
                    'requested_unified_location_id' => $originalUnifiedLocationId,
                    'resolved_unified_location_id' => $resolvedUnifiedLocationId,
                    'where' => $validated['where'] ?? null,
                ]);
            }

            $validated['unified_location_id'] = $resolvedUnifiedLocationId;

            if (
                ! empty($validated['dropoff_unified_location_id'])
                && (int) $validated['dropoff_unified_location_id'] === $originalUnifiedLocationId
            ) {
                $validated['dropoff_unified_location_id'] = $resolvedUnifiedLocationId;
            }

            foreach ([
                'city' => 'city',
                'country' => 'country',
                'latitude' => 'latitude',
                'longitude' => 'longitude',
            ] as $filterKey => $locationKey) {
                if (empty($validated[$filterKey]) && ! empty($resolvedSearchLocation[$locationKey])) {
                    $validated[$filterKey] = $resolvedSearchLocation[$locationKey];
                }
            }

            $internalProviderPickupId = $this->internalProviderPickupId($resolvedSearchLocation);
            if ($requestedProvider === 'internal') {
                if ($internalProviderPickupId !== null) {
                    $validated['provider_pickup_id'] = $internalProviderPickupId;
                } else {
                    $blockInternalProviderForResolvedLocation = true;
                    unset($validated['provider_pickup_id']);
                }
            }
        }

        $internalVehiclesQuery = Vehicle::query()
            ->with(['images', 'bookings', 'vendor.profile', 'vendorProfile', 'vendorProfileData', 'benefits', 'vendorPlans', 'addons', 'operatingHours']);

        if (! empty($validated['date_from']) && ! empty($validated['date_to'])) {
            $this->internalVehicleAvailabilityService->apply($internalVehiclesQuery, [
                'pickup_date' => $validated['date_from'],
                'pickup_time' => $validated['start_time'] ?? null,
                'dropoff_date' => $validated['date_to'],
                'dropoff_time' => $validated['end_time'] ?? null,
            ]);
        } else {
            $internalVehiclesQuery->whereIn('status', Vehicle::searchableStatuses());
        }

        // Apply location filters for internal vehicles based on search parameters
        if ($hasResolvedSearchLocation && $requestedProvider === 'mixed') {
            if ($internalProviderPickupId !== null) {
                $this->applyInternalGroupedLocationFilter($internalVehiclesQuery, $internalProviderPickupId);
            } else {
                $internalVehiclesQuery->whereRaw('1 = 0');
            }
        } elseif (! empty($validated['provider_pickup_id']) && (! isset($validated['provider']) || $validated['provider'] !== 'internal')) {
            $internalVehiclesQuery->whereRaw('1 = 0');
        } elseif (! empty($validated['provider'])) {
            if ($validated['provider'] === 'internal') {
                $appliedExactInternalLocationFilter = false;

                if ($blockInternalProviderForResolvedLocation) {
                    $internalVehiclesQuery->whereRaw('1 = 0');
                    $appliedExactInternalLocationFilter = true;
                } elseif (! empty($validated['provider_pickup_id'])) {
                    $appliedExactInternalLocationFilter = $this->applyInternalGroupedLocationFilter(
                        $internalVehiclesQuery,
                        $validated['provider_pickup_id']
                    );
                }

                if (! $appliedExactInternalLocationFilter) {
                    $internalVehiclesQuery->whereRaw('1 = 0');
                }
            } else {
                $internalVehiclesQuery->whereRaw('1 = 0');
            }
        } else {
            $appliedExactInternalLocationFilter = false;
            if ($hasResolvedSearchLocation && $internalProviderPickupId !== null) {
                $appliedExactInternalLocationFilter = $this->applyInternalGroupedLocationFilter(
                    $internalVehiclesQuery,
                    $internalProviderPickupId
                );
            }

            if (! $appliedExactInternalLocationFilter) {
                $internalVehiclesQuery->whereRaw('1 = 0');
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
            if ($mileage >= 0 && $mileage <= 25) {
                return '0-25';
            }
            if ($mileage > 25 && $mileage <= 50) {
                return '25-50';
            }
            if ($mileage > 50 && $mileage <= 75) {
                return '50-75';
            }
            if ($mileage > 75 && $mileage <= 100) {
                return '75-100';
            }
            if ($mileage > 100 && $mileage <= 120) {
                return '100-120';
            }

            return null;
        })->filter()->unique()->values()->all();
        $categoriesFromOptions = [];
        $categoryIds = $potentialVehiclesForOptions->pluck('category_id')->unique()->filter();
        if ($categoryIds->isNotEmpty()) {
            $categoriesFromOptions = VehicleCategory::whereIn('id', $categoryIds)->select('id', 'name')->get()->toArray();
        }

        if (! empty($validated['seating_capacity'])) {
            $internalVehiclesQuery->where('seating_capacity', $validated['seating_capacity']);
        }
        if (! empty($validated['brand'])) {
            $internalVehiclesQuery->where('brand', $validated['brand']);
        }
        if (! empty($validated['transmission'])) {
            $internalVehiclesQuery->where('transmission', $validated['transmission']);
        }
        if (! empty($validated['fuel'])) {
            $internalVehiclesQuery->where('fuel', $validated['fuel']);
        }
        // Note: price_range filtering removed from backend - handled on frontend with currency conversion
        // if (!empty($validated['price_range'])) {
        //     $range = explode('-', $validated['price_range']);
        //     $internalVehiclesQuery->whereBetween('price_per_day', [(int) $range[0], (int) $range[1]]);
        // }
        if (! empty($validated['color'])) {
            $internalVehiclesQuery->where('color', $validated['color']);
        }
        if (! empty($validated['mileage'])) {
            $range = explode('-', $validated['mileage']);
            $internalVehiclesQuery->whereBetween('mileage', [(int) $range[0], (int) $range[1]]);
        }
        if (! empty($validated['category_id'])) {
            $internalVehiclesQuery->where('category_id', $validated['category_id']);
        }

        if (! empty($validated['package_type'])) {
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
        $vehicleListSchema = null;
        $internalVehiclesData = $internalVehiclesEloquent->map(function ($vehicle) use ($rentalDays) {
            $reviews = Review::where('vehicle_id', $vehicle->id)
                ->where('status', 'approved')
                ->get();
            $vehicle->review_count = $reviews->count();
            $vehicle->average_rating = $reviews->avg('rating') ?? 0;
            $data = $vehicle->toArray();
            $data['category_name'] = $vehicle->category->name ?? null;
            $data['average_rating'] = $vehicle->average_rating;
            $data['review_count'] = $vehicle->review_count;
            $data['operating_hours'] = $vehicle->operatingHours->map(function ($h) {
                return [
                    'day' => $h->day_of_week,
                    'day_name' => $h->dayName(),
                    'is_open' => $h->is_open,
                    'open_time' => $h->open_time,
                    'close_time' => $h->close_time,
                ];
            })->values()->toArray();

            return $this->internalSearchVehicleFactory->make($data, $rentalDays, [
                'pickup_location_id' => isset($data['vendor_location_id']) ? (string) $data['vendor_location_id'] : (isset($data['id']) ? (string) $data['id'] : null),
                'dropoff_location_id' => isset($data['vendor_location_id']) ? (string) $data['vendor_location_id'] : (isset($data['id']) ? (string) $data['id'] : null),
            ]);
        })->values()->all();

        $internalVehiclesCollection = collect($internalVehiclesData);

        return $this->searchViaGateway(
            $request,
            $validated,
            $rentalDays,
            $internalVehiclesCollection,
            $brands,
            $colors,
            $seatingCapacities,
            $transmissions,
            $fuels,
            $mileages,
            $categoriesFromOptions,
            $vehicleListSchema
        );
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

        // Sensible branded fallback when no DB SEO row exists for the search
        // page. Prevents the title showing as bare "Laravel" / APP_NAME when
        // the admin hasn't configured SEO for this route yet.
        if (empty($seo['title']) || $seo['title'] === config('app.name')) {
            $locationLabel = $validated['where'] ?? null;
            $seo['title'] = $locationLabel
                ? "Car Rentals in {$locationLabel} — Compare & Book | Vrooem"
                : 'Search Car Rentals — Compare Deals Worldwide | Vrooem';
        }

        try {
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
        } catch (\Throwable $exception) {
            Log::error('Search gateway render failed', [
                'requested_unified_location_id' => $validated['unified_location_id'] ?? null,
                'provider' => $validated['provider'] ?? null,
                'provider_pickup_id' => $validated['provider_pickup_id'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            $emptyVehicles = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                500,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            $props = [
                'vehicles' => $emptyVehicles,
                'providerStatus' => [],
                'searchError' => 'We could not load live supplier availability for this search. Please adjust the search and try again.',
                'filters' => $validated,
                'pagination_links' => '',
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
                'optionalExtras' => [],
                'locationName' => $validated['where'] ?? 'Selected Location',
                'recommendedLocations' => [],
            ];
        }

        return Inertia::render('SearchResults', $props);
    }

    private function applyInternalGroupedLocationFilter($query, mixed $referenceVehicleId): bool
    {
        if (blank($referenceVehicleId)) {
            return false;
        }

        $referenceLocation = VendorLocation::query()
            ->whereKey($referenceVehicleId)
            ->where('is_active', true)
            ->first();

        if ($referenceLocation) {
            $query->where('vendor_location_id', $referenceLocation->id);

            return true;
        }

        $referenceVehicle = Vehicle::query()
            ->whereKey($referenceVehicleId)
            ->select(['id', 'location', 'location_type', 'city', 'state', 'country'])
            ->first();

        if (! $referenceVehicle) {
            return false;
        }

        $query
            ->where('location', $referenceVehicle->location)
            ->where('location_type', $referenceVehicle->location_type)
            ->where('city', $referenceVehicle->city)
            ->where('country', $referenceVehicle->country)
            ->when(
                $referenceVehicle->state === null,
                fn ($builder) => $builder->whereNull('state'),
                fn ($builder) => $builder->where('state', $referenceVehicle->state)
            );

        return true;
    }

    private function internalProviderPickupId(?array $location): ?string
    {
        foreach (($location['providers'] ?? []) as $provider) {
            if (! is_array($provider)) {
                continue;
            }

            if (strtolower(trim((string) ($provider['provider'] ?? ''))) !== 'internal') {
                continue;
            }

            $pickupId = trim((string) ($provider['pickup_id'] ?? ''));

            return $pickupId !== '' ? $pickupId : null;
        }

        return null;
    }

    public function searchUnifiedLocations(Request $request)
    {
        try {
            $validated = $request->validate([
                'search_term' => 'sometimes|string|min:2',
                'limit' => 'sometimes|integer|min:1|max:50',
                'unified_location_id' => 'sometimes|integer',
            ]);

            // Direct lookup by ID
            if (! empty($validated['unified_location_id'])) {
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
        } catch (\Throwable $exception) {
            Log::error('Unified location lookup failed', [
                'search_term' => $request->query('search_term'),
                'unified_location_id' => $request->query('unified_location_id'),
                'error' => $exception->getMessage(),
            ]);

            return response()->json([]);
        }
    }
}
