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
use App\Services\LocautoRentService; // Import LocautoRentService
use App\Services\RenteonService; // Import RenteonService
use App\Services\FavricaService;
use App\Services\XDriveService;
use App\Services\SicilyByCarService;
use App\Services\RecordGoService;
use App\Services\SurpriceService;
use App\Services\Search\SearchOrchestratorService;
use App\Services\PriceVerificationService;
use Illuminate\Support\Facades\Log; // Import Log facade
use App\Services\Seo\SeoMetaResolver;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    protected $greenMotionService;
    protected $okMobilityService;
    protected $locationSearchService;
    protected $adobeCarService;
    protected $wheelsysService;
    protected $locautoRentService;
    protected $renteonService;
    protected $favricaService;
    protected $xdriveService;
    protected $sicilyByCarService;
    protected $recordGoService;
    protected $surpriceService;
    protected $searchOrchestratorService;
    protected $priceVerificationService;

    public function __construct(
        GreenMotionService $greenMotionService,
        OkMobilityService $okMobilityService,
        LocationSearchService $locationSearchService,
        AdobeCarService $adobeCarService,
        WheelsysService $wheelsysService,
        LocautoRentService $locautoRentService,
        RenteonService $renteonService,
        FavricaService $favricaService,
        XDriveService $xdriveService,
        SicilyByCarService $sicilyByCarService,
        RecordGoService $recordGoService,
        SurpriceService $surpriceService,
        SearchOrchestratorService $searchOrchestratorService,
        PriceVerificationService $priceVerificationService
    ) {
        $this->greenMotionService = $greenMotionService;
        $this->okMobilityService = $okMobilityService;
        $this->locationSearchService = $locationSearchService;
        $this->adobeCarService = $adobeCarService;
        $this->wheelsysService = $wheelsysService;
        $this->locautoRentService = $locautoRentService;
        $this->renteonService = $renteonService;
        $this->favricaService = $favricaService;
        $this->xdriveService = $xdriveService;
        $this->sicilyByCarService = $sicilyByCarService;
        $this->recordGoService = $recordGoService;
        $this->surpriceService = $surpriceService;
        $this->searchOrchestratorService = $searchOrchestratorService;
        $this->priceVerificationService = $priceVerificationService;
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

        $providerDefaultTime = function (string $provider): string {
            // Keep provider defaults explicit so empty/invalid times don't randomly break APIs.
            // Wheelsys expects a different default in this codebase.
            return $provider === 'wheelsys' ? '10:00' : '09:00';
        };

        $normalizeTimeToStep = function (string $time, int $stepMinutes): string {
            // Round to nearest step (e.g. 30m). Assumes valid HH:MM.
            [$h, $m] = array_map('intval', explode(':', $time));
            $total = $h * 60 + $m;
            $rounded = (int) (round($total / $stepMinutes) * $stepMinutes);
            $rounded = max(0, min(23 * 60 + 59, $rounded));
            $rh = (int) floor($rounded / 60);
            $rm = $rounded % 60;
            return sprintf('%02d:%02d', $rh, $rm);
        };

        $clampTimeToBusinessHours = function (string $time, string $fallback, string $provider): string {
            // Many providers return 0 inventory for out-of-hours times.
            // Without fetching station opening hours for every provider/location,
            // we clamp to a safe daytime window for robustness.
            // This primarily stabilizes providers like OK Mobility.
            [$h, $m] = array_map('intval', explode(':', $time));
            $total = $h * 60 + $m;

            // Default safe window: 08:00 - 20:00.
            // Wheelsys uses a different default time in this codebase.
            $min = 8 * 60;
            $max = 20 * 60;

            // Providers known to be strict about office hours.
            $strictProviders = ['okmobility', 'greenmotion', 'usave', 'locauto_rent', 'wheelsys', 'adobe', 'favrica', 'xdrive', 'sicily_by_car', 'recordgo', 'surprice'];
            if (!in_array($provider, $strictProviders, true)) {
                return $time;
            }

            if ($total < $min || $total > $max) {
                return $fallback;
            }

            return $time;
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
            ->with(['images', 'bookings', 'vendor.profile', 'vendorProfile', 'vendorProfileData', 'benefits', 'vendorPlans', 'addons']);

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
            return $data;
        })->values()->all();

        $internalVehiclesCollection = collect($internalVehiclesData);

        // --- Provider Vehicle Fetching Logic ---
        $providerVehicles = collect();
        $okMobilityVehicles = collect(); // Separate collection for OK Mobility vehicles
        $orchestratorResult = $this->searchOrchestratorService->resolveProviderEntries($validated);
        $providerName = $orchestratorResult['providerName'] ?? 'mixed';
        $matchedLocation = $orchestratorResult['matchedLocation'] ?? null;
        $allProviderEntries = $orchestratorResult['providerEntries'] ?? [];
        $locationLat = $orchestratorResult['locationLat'] ?? ($validated['latitude'] ?? null);
        $locationLng = $orchestratorResult['locationLng'] ?? ($validated['longitude'] ?? null);
        $locationAddress = $orchestratorResult['locationAddress'] ?? ($validated['where'] ?? null);
        $orchestratorErrors = $orchestratorResult['errors'] ?? [];
        $isOneWay = $orchestratorResult['isOneWay'] ?? false;

        if (!empty($orchestratorErrors)) {
            Log::warning('Search orchestrator reported errors.', [
                'errors' => $orchestratorErrors,
            ]);
            return $emptyResultsResponse('Search locations are temporarily unavailable.');
        }

        if (!empty($validated['unified_location_id']) && !$matchedLocation) {
            return $emptyResultsResponse('Selected location is not available.');
        }

        if ($matchedLocation) {
            if (empty($validated['latitude']) && $locationLat !== null) {
                $validated['latitude'] = $locationLat;
            }
            if (empty($validated['longitude']) && $locationLng !== null) {
                $validated['longitude'] = $locationLng;
            }
            if (empty($validated['city']) && !empty($matchedLocation['city'])) {
                $validated['city'] = $matchedLocation['city'];
            }
            if (empty($validated['country']) && !empty($matchedLocation['country'])) {
                $validated['country'] = $matchedLocation['country'];
            }
            $validated['location_name'] = $matchedLocation['name'] ?? $locationAddress;

            Log::info('Unified location matched', [
                'unified_id' => $matchedLocation['unified_location_id'] ?? 'N/A',
                'name' => $matchedLocation['name'] ?? 'Unknown',
                'total_provider_entries' => count($allProviderEntries),
            ]);
        }

        $providerErrors = [];
        $providerTimings = [];

        if ($providerName && $providerName !== 'internal' && !empty($validated['date_from']) && !empty($validated['date_to'])) {
            
            Log::info('Provider entries to fetch: ' . count($allProviderEntries));

            $searchOptionalExtras = []; // Initialize logic for extras
            $recordProviderError = function (string $provider, string $message) use (&$providerErrors): void {
                $providerErrors[$provider][] = $message;
            };

            $sicilyByCarLocationMap = null;
            $sicilyByCarLocationFetchFailed = false;
            $recordGoProcessedPickups = [];
            $recordGoComplementsCache = [];
            $allLocations = null; // Lazy-loaded for mixed-mode dropoff resolution

            foreach ($allProviderEntries as $providerEntry) {
                // Get the provider name, location ID and original name for this entry
                $providerToFetch = $providerEntry['provider'];
                $currentProviderLocationId = $providerEntry['pickup_id'];
                $currentProviderLocationName = $providerEntry['original_name'];
                // Per-entry coordinates override unified location's single lat/lng
                $entryLat = $providerEntry['latitude'] ?? $locationLat;
                $entryLng = $providerEntry['longitude'] ?? $locationLng;
                $providerStart = microtime(true);

                // Provider-safe times: avoid random empty/invalid times causing 0 results.
                $startTimeForProvider = $normalizeTime($validated['start_time'] ?? '', $providerDefaultTime($providerToFetch));
                $endTimeForProvider = $normalizeTime($validated['end_time'] ?? '', $providerDefaultTime($providerToFetch));
                // Most providers in this codebase operate on 30-minute increments.
                $startTimeForProvider = $normalizeTimeToStep($startTimeForProvider, 30);
                $endTimeForProvider = $normalizeTimeToStep($endTimeForProvider, 30);
                $startTimeForProvider = $clampTimeToBusinessHours($startTimeForProvider, $providerDefaultTime($providerToFetch), $providerToFetch);
                $endTimeForProvider = $clampTimeToBusinessHours($endTimeForProvider, $providerDefaultTime($providerToFetch), $providerToFetch);

                // Enforce provider dropoff compatibility.
                $providersWithDropoffList = ['greenmotion', 'usave', 'locauto_rent', 'renteon', 'sicily_by_car', 'recordgo'];
                $supportsDropoff = in_array($providerToFetch, $providersWithDropoffList, true);

                $dropoffIdForProvider = $currentProviderLocationId; // default: same location
                if ($supportsDropoff) {
                    if ($providerName === 'mixed') {
                        // In mixed mode, resolve each provider's dropoff ID from the dropoff unified location.
                        $dropoffUnifiedId = $validated['dropoff_unified_location_id'] ?? null;
                        if ($dropoffUnifiedId && $matchedLocation) {
                            $dropoffUnifiedIdStr = (string) $dropoffUnifiedId;
                            $pickupUnifiedIdStr = (string) ($matchedLocation['unified_location_id'] ?? '');
                            if ($dropoffUnifiedIdStr !== $pickupUnifiedIdStr) {
                                // Find the dropoff location in unified_locations.json and get this provider's pickup_id there
                                $allLocations ??= json_decode(\Illuminate\Support\Facades\File::get(public_path('unified_locations.json')), true);
                                foreach ($allLocations as $loc) {
                                    if ((string) ($loc['unified_location_id'] ?? '') === $dropoffUnifiedIdStr) {
                                        foreach ($loc['providers'] ?? [] as $p) {
                                            if (($p['provider'] ?? null) === $providerToFetch) {
                                                $dropoffIdForProvider = $p['pickup_id'];
                                                break 2;
                                            }
                                        }
                                        break; // Found the unified location but provider not present there
                                    }
                                }
                            }
                        }
                    } else {
                        $dropoffIdForProvider = $validated['dropoff_location_id'] ?? $currentProviderLocationId;
                    }
                }

                if ($providerToFetch === 'greenmotion' || $providerToFetch === 'usave') {
                    try {
                        $this->greenMotionService->setProvider($providerToFetch);
                        $gmOptions = [
                            'language' => $validated['language'] ?? app()->getLocale(),
                            'rentalCode' => $validated['rentalCode'] ?? '1',
                            // 'currency' => $validated['currency'] ?? null,
                            'fuel' => $validated['fuel'] ?? null,
                            'userid' => $validated['userid'] ?? null,
                            'username' => $validated['username'] ?? null,
                            'full_credit' => $validated['full_credit'] ?? null,
                            'promocode' => $validated['promocode'] ?? null,
                            'dropoff_location_id' => $dropoffIdForProvider,
                        ];

                        $gmResponse = $this->greenMotionService->getVehicles(
                            $currentProviderLocationId,
                            $validated['date_from'],
                            $startTimeForProvider,
                            $validated['date_to'],
                            $endTimeForProvider,
                            $validated['age'] ?? 35,
                            array_filter($gmOptions)
                        );

                        if ($gmResponse) {
                            libxml_use_internal_errors(true);
                            $xmlObject = simplexml_load_string($gmResponse);

                            if ($xmlObject !== false) {
                                $parsedOptionalExtras = $this->parseOptionalExtras($xmlObject);
                                if (!empty($parsedOptionalExtras)) {
                                    foreach ($parsedOptionalExtras as $extra) {
                                        $key = (string) ($extra['option_id'] ?? $extra['optionID'] ?? $extra['id'] ?? '');
                                        if ($key === '') {
                                            continue;
                                        }
                                        $searchOptionalExtras[$key] = $extra;
                                    }
                                }
                            }

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

                                    $vehicleOptions = [];
                                    if (isset($vehicle->options->option)) {
                                        foreach ($vehicle->options->option as $option) {
                                            $optionId = (string) $option->optionID;
                                            $vehicleOptions[] = [
                                                'id' => 'gm_option_' . $optionId,
                                                'option_id' => $optionId,
                                                'name' => (string) $option->Name,
                                                'description' => (string) $option->Description,
                                                'required' => (string) ($option->required ?? $option['required'] ?? ''),
                                                'numberAllowed' => (string) ($option->numberAllowed ?? ''),
                                                'prepay_available' => strtolower((string) ($option->prepay_available ?? '')),
                                                'daily_rate' => (string) $option->Daily_rate,
                                                'daily_rate_currency' => (string) $option->Daily_rate['currency'],
                                                'total_for_booking' => (string) $option->Total_for_this_booking,
                                                'total_for_booking_currency' => (string) $option->Total_for_this_booking['currency'],
                                                'prepay_total_for_booking' => (string) $option->Prepay_total_for_this_booking,
                                                'prepay_total_for_booking_currency' => (string) $option->Prepay_total_for_this_booking['currency'],
                                                'type' => 'option',
                                            ];
                                        }
                                    }

                                    $vehicleInsuranceOptions = [];
                                    if (isset($vehicle->insurance_options->option)) {
                                        foreach ($vehicle->insurance_options->option as $option) {
                                            $optionId = (string) $option->optionID;
                                            $vehicleInsuranceOptions[] = [
                                                'id' => 'gm_insurance_' . $optionId,
                                                'option_id' => $optionId,
                                                'name' => (string) $option->Name,
                                                'description' => (string) $option->Description,
                                                'required' => (string) ($option->required ?? $option['required'] ?? ''),
                                                'numberAllowed' => (string) ($option->numberAllowed ?? ''),
                                                'prepay_available' => strtolower((string) ($option->prepay_available ?? '')),
                                                'daily_rate' => (string) $option->Daily_rate,
                                                'daily_rate_currency' => (string) $option->Daily_rate['currency'],
                                                'total_for_booking' => (string) $option->Total_for_this_booking,
                                                'total_for_booking_currency' => (string) $option->Total_for_this_booking['currency'],
                                                'prepay_total_for_booking' => (string) $option->Prepay_total_for_this_booking,
                                                'prepay_total_for_booking_currency' => (string) $option->Prepay_total_for_this_booking['currency'],
                                                'type' => 'insurance',
                                            ];
                                        }
                                    }

                                    $minDriverAge = !empty($products) ? (int) ($products[0]['minage'] ?? 0) : 0;
                                    $fuelPolicy = !empty($products) ? ($products[0]['fuelpolicy'] ?? '') : '';
                                    $brandName = explode(' ', (string) $vehicle['name'])[0];

                                    // Robust SIPP/ACRISS Extraction
                                    $candidates = [
                                        (string) ($vehicle->acriss ?? ''),
                                        (string) ($vehicle->acriss_code ?? ''),
                                        (string) ($vehicle->sipp_code ?? ''),
                                        (string) ($vehicle->category ?? ''),
                                        (string) ($vehicle->groupName ?? '')
                                    ];
                                    $sippCode = '';
                                    foreach ($candidates as $candidate) {
                                        if (empty($candidate))
                                            continue;
                                        if (preg_match_all('/\b[A-Z]{4}\b/', $candidate, $matches)) {
                                            foreach ($matches[0] as $match) {
                                                if (strpos('MNEHCDIJSRFGPULWOX', $match[0]) !== false) {
                                                    $sippCode = $match;
                                                    break 2;
                                                }
                                            }
                                        }
                                    }

                                    $parsedCategory = [];
                                    if (!empty($sippCode)) {
                                        $parsedCategory = $this->parseSippCode($sippCode);
                                    }

                                    $categoryName = $parsedCategory['category'] ?? 'Unknown';

                                    // Build Features Array
                                    $features = [];
                                    $hasAC = false;

                                    // Check explicit AC attribute or SIPP
                                    $acVal = (string) ($vehicle->airConditioning ?? $vehicle->air_conditioning ?? $vehicle->airco ?? '');
                                    $acValLower = strtolower($acVal);

                                    if ($acValLower === 'true' || $acValLower === 'yes' || $acVal === '1' || !empty($parsedCategory['air_conditioning'])) {
                                        $features[] = 'Air Conditioning';
                                        $hasAC = true;
                                    }

                                    if (isset($vehicle->bluetooth) && strtolower((string) $vehicle->bluetooth) === 'true') {
                                        $features[] = 'Bluetooth';
                                    }
                                    if (isset($vehicle->gps) && strtolower((string) $vehicle->gps) === 'true') {
                                        $features[] = 'GPS';
                                    }

                                    $totalPrice = 0.0;
                                    $currency = 'EUR';

                                    if (isset($vehicle->total) && is_numeric((string) $vehicle->total)) {
                                        $totalPrice = (float) (string) $vehicle->total;
                                        $currency = trim((string) ($vehicle->total['currency'] ?? 'EUR'));
                                    } elseif (isset($vehicle->product[0]->total) && is_numeric((string) $vehicle->product[0]->total)) {
                                        $totalPrice = (float) (string) $vehicle->product[0]->total;
                                        $currency = trim((string) ($vehicle->product[0]->total['currency'] ?? 'EUR'));
                                    }

                                    $providerVehicles->push([
                                        'id' => $providerToFetch . '_' . (string) $vehicle['id'],
                                        'location_id' => $currentProviderLocationId, // Added location ID
                                        'source' => $providerToFetch,
                                        'brand' => $brandName,
                                        'model' => str_ireplace($brandName . ' ', '', (string) $vehicle['name']),
                                        'category' => $categoryName,
                                        'image' => urldecode((string) $vehicle['image']),
                                        'total_price' => $totalPrice,
                                        'price_per_day' => (float) ($totalPrice / $rentalDays),
                                        'price_per_week' => null,
                                        'price_per_month' => null,
                                        'currency' => $currency,
                                        'transmission' => (string) $vehicle->transmission,
                                        'fuel' => (string) $vehicle->fuel,
                                        'seating_capacity' => (int) $vehicle->adults + (int) $vehicle->children,
                                        'mileage' => (string) $vehicle->mpg,
                                        'co2' => (string) $vehicle->co2,
                                        'latitude' => (float) $entryLat,
                                        'longitude' => (float) $entryLng,
                                        'full_vehicle_address' => $currentProviderLocationName,
                                        'provider_pickup_id' => $currentProviderLocationId,
                                        'provider_return_id' => $dropoffIdForProvider,
                                        'ok_mobility_pickup_time' => $startTimeForProvider,
                                        'ok_mobility_dropoff_time' => $endTimeForProvider,
                                        'features' => $features,
                                        'airConditioning' => $hasAC,
                                        'sipp_code' => $sippCode,
                                        'benefits' => [
                                            'minimum_driver_age' => $minDriverAge,
                                            'fuel_policy' => $fuelPolicy,
                                        ],
                                        'luggageSmall' => (string) $vehicle->luggageSmall,
                                        'luggageMed' => (string) $vehicle->luggageMed,
                                        'luggageLarge' => (string) $vehicle->luggageLarge,
                                        'products' => $products,
                                        'quoteid' => (string) $xmlObject->response->quoteid,
                                        'rentalCode' => $validated['rentalCode'] ?? '1',
                                        'options' => $vehicleOptions,
                                        'insurance_options' => $vehicleInsuranceOptions,
                                    ]);
                                }
                            } else {
                                Log::warning("{$providerToFetch} API response for vehicles was empty or malformed for location ID: " . $currentProviderLocationId, ['response' => $gmResponse]);
                            }
                        }
                    } catch (\Exception $e) {
                        $recordProviderError($providerToFetch, $e->getMessage());
                        Log::error("Error fetching {$providerToFetch} vehicles: " . $e->getMessage());
                    }
                } elseif ($providerToFetch === 'adobe') {
                    try {
                        Log::info('Attempting to fetch Adobe vehicles for location ID: ' . $currentProviderLocationId);
                        Log::info('Search params: ', [
                            'pickup_id' => $currentProviderLocationId,
                            'date_from' => $validated['date_from'],
                            'start_time' => $startTimeForProvider,
                            'date_to' => $validated['date_to'],
                            'end_time' => $endTimeForProvider
                        ]);

                        Log::info('Fetching Adobe vehicle data from API');

                        // Prepare search parameters for Adobe API
                        $adobeSearchParams = [
                            'pickupoffice' => $currentProviderLocationId,
                            'returnoffice' => $currentProviderLocationId,
                            'startdate' => $validated['date_from'] . ' ' . $startTimeForProvider,
                            'enddate' => $validated['date_to'] . ' ' . $endTimeForProvider,
                            'promotionCode' => $validated['promocode'] ?? null
                        ];

                        // Filter out null values
                        $adobeSearchParams = array_filter($adobeSearchParams, function ($value) {
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
                                    $currentProviderLocationId,
                                    $vehicle['category'] ?? '',
                                    [
                                        'startdate' => $adobeSearchParams['startdate'],
                                        'enddate' => $adobeSearchParams['enddate']
                                    ]
                                );

                                // Calculate rental duration in days for price-per-day calculation
                                $startDate = \Carbon\Carbon::parse($adobeSearchParams['startdate']);
                                $endDate = \Carbon\Carbon::parse($adobeSearchParams['enddate']);
                                $rentalDays = $startDate->diffInDays($endDate) ?: 1; // Ensure details at least 1 day divisor

                                // Parse SIPP code for Adobe (stored in 'category')
                                $sippCode = $vehicle['category'] ?? '';
                                $parsedSipp = $this->parseSippCode($sippCode);

                                $processedVehicle = [
                                    'id' => 'adobe_' . $currentProviderLocationId . '_' . ($vehicle['category'] ?? 'unknown'),
                                    'source' => 'adobe',
                                    'brand' => $this->extractBrandFromModel($vehicle['model'] ?? ''),
                                    'model' => $vehicle['model'] ?? 'Adobe Vehicle',
                                    'category' => !empty($parsedSipp['category']) && $parsedSipp['category'] !== 'Unknown'
                                        ? $parsedSipp['category']
                                        : (isset($vehicle['type']) ? ucwords(strtolower($vehicle['type'])) : ($vehicle['category'] ?? '')),
                                    'image' => $this->getAdobeVehicleImage($vehicle['photo'] ?? ''),
                                    'price_per_day' => (float) (($vehicle['tdr'] ?? 0) / $rentalDays),
                                    'total_price' => (float) ($vehicle['tdr'] ?? 0),
                                    'price_per_week' => (float) (($vehicle['tdr'] ?? 0) / $rentalDays * 7),
                                    'price_per_month' => (float) (($vehicle['tdr'] ?? 0) / $rentalDays * 30),

                                    'currency' => 'USD', // Adobe uses USD
                                    'transmission' => ($vehicle['manual'] ?? false) ? 'manual' : 'automatic',
                                    // Adobe doesn't seem to provide fuel info, default to petrol if unknown or try to guess from type?
                                    // For now, let's leave it nullable or 'petrol' if we want to force it.
                                    // But wait, the filter requires 'petrol', 'diesel', 'electric'.
                                    // But wait, the filter requires 'petrol', 'diesel', 'electric'.
                                    // Let's check 'type' field?
                                    'fuel' => 'petrol', // Defaulting to petrol as most rental cars are, minimizing 'N/A' issues
                                    'seating_capacity' => (int) ($vehicle['passengers'] ?? 4),
                                    'mileage' => null, // Adobe doesn't provide mileage info
                                    'latitude' => (float) $entryLat,
                                    'longitude' => (float) $entryLng,
                                    'full_vehicle_address' => $currentProviderLocationName,
                                    'provider_pickup_id' => $currentProviderLocationId,
                                    'benefits' => (object) [
                                        'vehicle_type' => $vehicle['type'] ?? '',
                                        'traction' => $vehicle['traction'] ?? '',
                                        'doors' => (int) ($vehicle['doors'] ?? 4),
                                    ],
                                    // review_count and average_rating omitted - not provided by API
                                    'products' => [],
                                    'protections' => $vehicleDetails['protections'] ?? [],
                                    'extras' => $vehicleDetails['extras'] ?? [],
                                    'adobe_category' => $vehicle['category'] ?? '',
                                    // Adobe-specific fields
                                    'pli' => (float) ($vehicle['pli'] ?? 0),
                                    'ldw' => (float) ($vehicle['ldw'] ?? 0),
                                    'spp' => (float) ($vehicle['spp'] ?? 0),
                                    'tdr' => (float) ($vehicle['tdr'] ?? 0),
                                    'dro' => (float) ($vehicle['dro'] ?? 0),
                                    // Direct fields for frontend template access
                                    'type' => $vehicle['type'] ?? '',
                                    // Adobe API 'passengers' often means comfort limit (4), but we want Seats capacity (5). 
                                    // User confirmed 5/7 seats. Mapping 4 -> 5 to match 'Seats' display vs 'Passengers'.
                                    'passengers' => (isset($vehicle['passengers']) && $vehicle['passengers'] == 4) ? 5 : ($vehicle['passengers'] ?? null),
                                    'doors' => isset($vehicle['doors']) ? (int) $vehicle['doors'] : null,
                                    'manual' => (bool) ($vehicle['manual'] ?? false),
                                    'traction' => $vehicle['traction'] ?? '',
                                    'features' => (function () use ($vehicle) {
                                        $f = [];
                                        $cat = $vehicle['category'] ?? '';
                                        if (strlen($cat) === 4) {
                                            $parsed = $this->parseSippCode($cat);
                                            if (!empty($parsed['air_conditioning'])) {
                                                $f[] = 'Air Conditioning';
                                            }
                                        }
                                        return $f;
                                    })(),
                                ];

                                $processedVehicles[] = $processedVehicle;

                                // Collect protections and extras for caching
                                $allProtections = array_merge($allProtections, $vehicleDetails['protections'] ?? []);
                                $allExtras = array_merge($allExtras, $vehicleDetails['extras'] ?? []);
                            }

                            $adobeVehicles = collect($processedVehicles);
                            Log::info('Adobe vehicles processed: ' . $adobeVehicles->count());

                        } else {
                            Log::warning("Adobe API response for vehicles was empty or malformed for location ID: " . $currentProviderLocationId, [
                                'response' => $adobeResponse
                            ]);
                            $adobeVehicles = collect([]);
                        }

                        // Add Adobe vehicles to the main provider vehicles collection
                        foreach ($adobeVehicles as $adobeVehicle) {
                            $providerVehicles->push((object) $adobeVehicle);
                        }

                        Log::info('Adobe vehicles added to collection: ' . $adobeVehicles->count());

                    } catch (\Exception $e) {
                        $recordProviderError($providerToFetch, $e->getMessage());
                        Log::error("Error fetching Adobe vehicles: " . $e->getMessage(), [
                            'provider_location_id' => $currentProviderLocationId,
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } elseif ($providerToFetch === 'okmobility') {
                    static $okMobilityGroupDescriptions = null;

                    if ($okMobilityGroupDescriptions === null) {
                        $okMobilityGroupDescriptions = $this->okMobilityService->getGroupDescriptionMap();
                    }

                    try {
                        Log::info('Attempting to fetch OK Mobility vehicles for location ID: ' . $currentProviderLocationId);
                        Log::info('Search params: ', [
                            'pickup_id' => $currentProviderLocationId,
                            'date_from' => $validated['date_from'],
                            'start_time' => $validated['start_time'] ?? '09:00',
                            'date_to' => $validated['date_to'],
                            'end_time' => $validated['end_time'] ?? '09:00'
                        ]);

                        $okMobilityResponse = $this->okMobilityService->getVehicles(
                            $currentProviderLocationId,
                            $currentProviderLocationId, // OK Mobility is one-way rental only
                            $validated['date_from'],
                            $startTimeForProvider,
                            $validated['date_to'],
                            $endTimeForProvider
                        );

                        Log::info('OK Mobility API response received: ' . ($okMobilityResponse ? 'SUCCESS' : 'NULL/EMPTY'));

                        if ($okMobilityResponse) {
                            libxml_use_internal_errors(true);
                            $xmlObject = simplexml_load_string($okMobilityResponse);

                            if ($xmlObject !== false) {
                                Log::info('OK Mobility XML parsed successfully');
                                // Register namespaces
                                $xmlObject->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
                                $xmlObject->registerXPathNamespace('get', 'http://www.OKGroup.es/RentaCarWebService/getWSDL');
                                $xmlObject->registerXPathNamespace('ns', 'http://tempuri.org/');

                                // Check for error codes in the response
                                $errorCode = (string) ($xmlObject->xpath('//errorCode')[0] ?? $xmlObject->xpath('//get:errorCode')[0] ?? null);
                                if ($errorCode && $errorCode !== 'SUCCESS') {
                                    Log::warning("OK Mobility API returned error for location ID {$currentProviderLocationId}: {$errorCode}");
                                }

                                // Try different XPath patterns to find vehicles robustly
                                $vehicles = $xmlObject->xpath('//get:getMultiplePrice') ?:
                                    $xmlObject->xpath('//getMultiplePrice') ?:
                                    $xmlObject->xpath('//ns:getMultiplePrice') ?:
                                    $xmlObject->xpath('//getMultiplePricesResult/getMultiplePrice');

                                if (empty($vehicles) && (!$errorCode || $errorCode === 'SUCCESS')) {
                                    Log::info("OK Mobility: No vehicles found in XML for location ID {$currentProviderLocationId}, but no error code was present.");
                                }

                                Log::info('OK Mobility vehicles found in XML: ' . count($vehicles));

                                foreach ($vehicles as $vehicle) {
                                    $vehicleData = json_decode(json_encode($vehicle), true);

                                    // Extract vehicle data with proper fallbacks
                                    $groupFullName = trim((string) ($vehicleData['Group_Name'] ?? 'Unknown Vehicle'));
                                    if ($groupFullName === '') {
                                        $groupFullName = 'Unknown Vehicle';
                                    }
                                    $groupId = $vehicleData['GroupID']
                                        ?? $vehicleData['GroupCode']
                                        ?? $vehicleData['groupCode']
                                        ?? 'unknown';

                                    // Use the Group_Name as the vehicle name (what OK Mobility provides)
                                    $vehicleName = $groupFullName;
                                    $token = $vehicleData['token'] ?? 'unknown';

                                    // Calculate price per day and total price
                                    $totalPrice = (float) ($vehicleData['previewValue'] ?? 0);
                                    $pricePerDay = (float) ($totalPrice / $rentalDays);

                                    // Extract mileage information
                                    $mileage = 'Unknown';
                                    if (isset($vehicleData['kmsIncluded'])) {
                                        $mileage = $vehicleData['kmsIncluded'] === 'true' ? 'Unlimited' : 'Limited';
                                    }

                                    // Parse SIPP code for vehicle details
                                    $sippCode = $vehicleData['SIPP'] ?? $vehicleData['sipp'] ?? null;
                                    $parsedSipp = $this->parseSippCode($sippCode, 'okmobility');

                                    $groupKey = $groupId ? strtoupper(trim((string) $groupId)) : null;
                                    $sippKey = $sippCode ? strtoupper(trim((string) $sippCode)) : null;
                                    $groupDescription = null;
                                    $vehicleModelRaw = trim((string) ($vehicleData['VehicleModel'] ?? $vehicleData['vehicleModel'] ?? ''));
                                    if ($vehicleModelRaw === '' || $vehicleModelRaw === '-') {
                                        $vehicleModelRaw = null;
                                    }

                                    $parseExtraList = function ($value): array {
                                        if (is_array($value)) {
                                            $items = $value;
                                        } else {
                                            $items = preg_split('/\s*,\s*/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
                                        }

                                        $items = array_map(static function ($item) {
                                            return trim((string) $item);
                                        }, $items);

                                        $items = array_filter($items, static function ($item) {
                                            return $item !== '' && $item !== '-';
                                        });

                                        return array_values(array_unique($items));
                                    };

                                    $normalizeText = static function ($value): ?string {
                                        if (is_array($value)) {
                                            $value = $value[0] ?? '';
                                        }
                                        $text = trim((string) $value);
                                        if ($text === '' || $text === '-') {
                                            return null;
                                        }
                                        return $text;
                                    };

                                    $toBool = static function ($value): bool {
                                        $text = strtolower(trim((string) $value));
                                        return in_array($text, ['true', '1', 'yes', 'y'], true);
                                    };

                                    if ($groupKey && isset($okMobilityGroupDescriptions[$groupKey])) {
                                        $groupDescription = $okMobilityGroupDescriptions[$groupKey];
                                    } elseif ($sippKey && isset($okMobilityGroupDescriptions[$sippKey])) {
                                        $groupDescription = $okMobilityGroupDescriptions[$sippKey];
                                    }

                                    $extrasIncluded = $parseExtraList($vehicleData['extrasIncluded'] ?? $vehicleData['extras_included'] ?? null);
                                    $extrasRequired = $parseExtraList($vehicleData['extrasRequired'] ?? $vehicleData['extras_required'] ?? null);
                                    $extrasAvailable = $parseExtraList($vehicleData['extrasAvailable'] ?? $vehicleData['extras_available'] ?? null);

                                    $valueWithoutTax = isset($vehicleData['valueWithoutTax']) && is_numeric($vehicleData['valueWithoutTax'])
                                        ? (float) $vehicleData['valueWithoutTax']
                                        : null;
                                    $taxRate = isset($vehicleData['taxRate']) && is_numeric($vehicleData['taxRate'])
                                        ? (float) $vehicleData['taxRate']
                                        : null;
                                    $taxValue = null;
                                    if ($valueWithoutTax !== null && $totalPrice !== null) {
                                        $taxValue = $totalPrice - $valueWithoutTax;
                                    }

                                    $pickupStationName = $normalizeText($vehicleData['StationNamePick'] ?? $vehicleData['stationNamePick'] ?? $vehicleData['Station'] ?? null);
                                    $dropoffStationName = $normalizeText($vehicleData['StationNameDrop'] ?? $vehicleData['stationNameDrop'] ?? null);
                                    $pickupAddressLine = $normalizeText($vehicleData['AddressPick'] ?? $vehicleData['addressPick'] ?? null);
                                    $dropoffAddressLine = $normalizeText($vehicleData['AddressDrop'] ?? $vehicleData['addressDrop'] ?? null);
                                    $pickupCity = $normalizeText($vehicleData['CityPick'] ?? $vehicleData['cityPick'] ?? null);
                                    $dropoffCity = $normalizeText($vehicleData['CityDrop'] ?? $vehicleData['cityDrop'] ?? null);
                                    $pickupZip = $normalizeText($vehicleData['ZipCodePick'] ?? $vehicleData['zipCodePick'] ?? null);
                                    $dropoffZip = $normalizeText($vehicleData['ZipCodeDrop'] ?? $vehicleData['zipCodeDrop'] ?? null);
                                    $pickupCountry = $normalizeText($vehicleData['CountryPick'] ?? $vehicleData['countryPick'] ?? null);
                                    $dropoffCountry = $normalizeText($vehicleData['CountryDrop'] ?? $vehicleData['countryDrop'] ?? null);

                                    $pickupAddressFull = implode(', ', array_filter([
                                        $pickupStationName,
                                        $pickupAddressLine,
                                        $pickupCity,
                                        $pickupZip,
                                        $pickupCountry,
                                    ]));

                                    $dropoffAddressFull = implode(', ', array_filter([
                                        $dropoffStationName ?: $pickupStationName,
                                        $dropoffAddressLine,
                                        $dropoffCity,
                                        $dropoffZip,
                                        $dropoffCountry,
                                    ]));

                                    if (!$dropoffAddressFull && $pickupAddressFull) {
                                        $dropoffAddressFull = $pickupAddressFull;
                                    }

                                    $rateRestriction = $vehicleData['RateRestriction'] ?? $vehicleData['rateRestriction'] ?? null;
                                    $rateAttributes = [];
                                    if (is_array($rateRestriction)) {
                                        $rateAttributes = $rateRestriction['@attributes'] ?? $rateRestriction;
                                    }

                                    $cancellationAvailable = $toBool($rateAttributes['CancellationAvailable'] ?? null);
                                    $cancellationPenalty = $toBool($rateAttributes['CancellationPenaltyInd'] ?? null);
                                    $cancellationAmount = isset($rateAttributes['Amount']) && is_numeric($rateAttributes['Amount'])
                                        ? (float) $rateAttributes['Amount']
                                        : null;
                                    $cancellationCurrency = $normalizeText($rateAttributes['Currency'] ?? null);
                                    $cancellationDeadline = $normalizeText($rateAttributes['DateTime'] ?? null);

                                    $charges = $vehicleData['Charges']['Charge'] ?? $vehicleData['charges']['charge'] ?? null;
                                    if ($charges && !isset($charges[0])) {
                                        $charges = [$charges];
                                    }

                                    $fuelPolicy = null;
                                    if (is_array($charges)) {
                                        foreach ($charges as $charge) {
                                            if (!is_array($charge)) {
                                                continue;
                                            }
                                            $chargeAttributes = $charge['@attributes'] ?? $charge;
                                            $description = $normalizeText($chargeAttributes['Description'] ?? null);
                                            $purpose = $normalizeText($chargeAttributes['Purpose'] ?? null);
                                            if ($description) {
                                                $fuelPolicy = $description;
                                                if ($purpose === '116') {
                                                    break;
                                                }
                                            }
                                        }
                                    }

                                    // Extract extras if available
                                    $extras = [];
                                    if (isset($vehicleData['allExtras']['allExtra'])) {
                                        $extras = $vehicleData['allExtras']['allExtra'];
                                        if (!isset($extras[0])) {
                                            $extras = [$extras]; // Convert single extra to array
                                        }
                                    }

                                    // Build features array
                                    $features = [];
                                    if (!empty($parsedSipp['air_conditioning'])) {
                                        $features[] = 'Air Conditioning';
                                    }

                                    // Extract brand and model (prefer OK Mobility group description)
                                    $brandName = 'OK Mobility';
                                    $isLikelyCode = static function ($value): bool {
                                        $text = strtoupper(trim((string) $value));
                                        return $text !== '' && preg_match('/^[A-Z0-9]{3,5}$/', $text) === 1;
                                    };

                                    $displayName = null;
                                    if ($groupDescription && !$isLikelyCode($groupDescription)) {
                                        $displayName = $groupDescription;
                                    } elseif ($groupFullName && !$isLikelyCode($groupFullName) && $groupFullName !== 'Unknown Vehicle') {
                                        $displayName = $groupFullName;
                                    }

                                    if (!$displayName || $displayName === 'Unknown Vehicle') {
                                        $displayName = $parsedSipp['dynamic_name'] ?? null;
                                    }

                                    if (!$displayName && $vehicleModelRaw) {
                                        $displayName = $vehicleModelRaw;
                                    }

                                    if (!$displayName) {
                                        $displayName = $groupFullName;
                                    }

                                    $vehicleModel = $displayName;

                                    $okMobilityVehicles->push([
                                        'id' => 'okmobility_' . $groupId . '_' . md5($token),
                                        'source' => 'okmobility',
                                        'brand' => $brandName,
                                        'model' => $vehicleModel,
                                        'display_name' => $displayName,
                                        'group_description' => $groupDescription,
                                        'vehicle_model_raw' => $vehicleModelRaw,
                                        'sipp_code' => $sippCode,
                                        'image' => !empty($vehicleData['imageURL']) ? $vehicleData['imageURL'] : $this->getOkMobilityImageUrl($groupId),
                                        'price_per_day' => $pricePerDay,
                                        'total_price' => $totalPrice,
                                        'price_per_week' => $pricePerDay * 7, // Calculate weekly price
                                        'price_per_month' => $pricePerDay * 30, // Calculate monthly price
                                        'currency' => 'EUR', // OK Mobility uses EUR
                                        'transmission' => $parsedSipp['transmission'],
                                        'fuel' => $parsedSipp['fuel'],
                                        'seating_capacity' => $parsedSipp['seating_capacity'] ?? 4, // Use parsed seating or default
                                        'doors' => $parsedSipp['doors'] ?? 4, // Use parsed doors
                                        'category' => $parsedSipp['category'] ?? 'Unknown', // Use parsed SIPP category
                                        'mileage' => $mileage,
                                        'features' => $features, // Add features here
                                        'latitude' => (float) $entryLat,
                                        'longitude' => (float) $entryLng,
                                        'full_vehicle_address' => $pickupAddressFull ?: $currentProviderLocationName,
                                        'provider_pickup_id' => $currentProviderLocationId,
                                        'station' => $vehicleData['Station'] ?? 'OK Mobility Station',
                                        'pickup_station_name' => $pickupStationName,
                                        'dropoff_station_name' => $dropoffStationName ?: $pickupStationName,
                                        'pickup_address' => $pickupAddressFull ?: null,
                                        'dropoff_address' => $dropoffAddressFull ?: null,
                                        'week_day_open' => $vehicleData['weekDayOpen'] ?? null,
                                        'week_day_close' => $vehicleData['weekDayClose'] ?? null,
                                        'preview_value' => isset($vehicleData['previewValue']) && is_numeric($vehicleData['previewValue']) ? (float) $vehicleData['previewValue'] : null,
                                        'total_day_value_with_tax' => isset($vehicleData['totalDayValueWithTax']) && is_numeric($vehicleData['totalDayValueWithTax']) ? (float) $vehicleData['totalDayValueWithTax'] : null,
                                        'value_without_tax' => $valueWithoutTax,
                                        'tax_rate' => $taxRate,
                                        'tax_value' => $taxValue,
                                        'fuel_policy' => $fuelPolicy,
                                        'cancellation' => [
                                            'available' => $cancellationAvailable,
                                            'penalty' => $cancellationPenalty,
                                            'amount' => $cancellationAmount,
                                            'currency' => $cancellationCurrency,
                                            'deadline' => $cancellationDeadline,
                                        ],
                                        'benefits' => [
                                            // Only include mileage info - OK Mobility doesn't provide cancellation, min age, or fuel policy in getMultiplePrices
                                            'limited_km_per_day' => $vehicleData['kmsIncluded'] !== 'true',
                                        ],
                                        // Don't include review_count and average_rating as OK Mobility doesn't provide these
                                        'products' => [
                                            [
                                                'type' => 'BAS',
                                                'total' => (string) $pricePerDay,
                                                'currency' => 'EUR',
                                                'token' => $token,
                                                'group_id' => $groupId,
                                                'rate_code' => $vehicleData['rateCode'] ?? null,
                                            ]
                                        ],
                                        'extras' => $extras,
                                        'insurance_options' => [],
                                        'ok_mobility_token' => $token,
                                        'ok_mobility_group_id' => $groupId,
                                        'ok_mobility_rate_code' => $vehicleData['rateCode'] ?? null,
                                        'extras_included' => $extrasIncluded,
                                        'extras_required' => $extrasRequired,
                                        'extras_available' => $extrasAvailable,
                                    ]);
                                }
                            } else {
                                Log::warning("OK Mobility API response for vehicles was empty or malformed for location ID: " . $currentProviderLocationId, [
                                    'response' => $okMobilityResponse,
                                    'xml_errors' => libxml_get_errors()
                                ]);
                                libxml_clear_errors();
                            }
                        }

                        Log::info('OK Mobility vehicles added to collection: ' . $okMobilityVehicles->count());
                    } catch (\Exception $e) {
                        $recordProviderError($providerToFetch, $e->getMessage());
                        Log::error("Error fetching OK Mobility vehicles: " . $e->getMessage(), [
                            'provider_location_id' => $currentProviderLocationId,
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } elseif ($providerToFetch === 'favrica') {
                    try {
                        Log::info('Attempting to fetch Favrica vehicles for location ID: ' . $currentProviderLocationId);
                        $dropoffId = $dropoffIdForProvider;
                        $requestCurrency = $this->toFavricaRequestCurrency($validated['currency'] ?? 'EUR');

                        $favricaLocationMap = [];
                        try {
                            $favricaLocations = $this->favricaService->getLocations();
                            $favricaLocationMap = $this->buildFavricaLocationMap($favricaLocations);
                        } catch (\Exception $e) {
                            Log::warning('Favrica location lookup failed', [
                                'location_id' => $currentProviderLocationId,
                                'error' => $e->getMessage(),
                            ]);
                        }

                        $pickupLocationDetails = $favricaLocationMap[(string) $currentProviderLocationId] ?? null;
                        if ($pickupLocationDetails) {
                            if (empty($pickupLocationDetails['latitude']) && $entryLat !== null) {
                                $pickupLocationDetails['latitude'] = (string) $entryLat;
                            }
                            if (empty($pickupLocationDetails['longitude']) && $entryLng !== null) {
                                $pickupLocationDetails['longitude'] = (string) $entryLng;
                            }
                            if (empty($pickupLocationDetails['name']) && $currentProviderLocationName) {
                                $pickupLocationDetails['name'] = $currentProviderLocationName;
                            }
                        }

                        $dropoffLocationDetails = null;
                        if ($dropoffId) {
                            $dropoffLocationDetails = $favricaLocationMap[(string) $dropoffId] ?? null;
                        }

                        $favricaResponse = $this->favricaService->searchRez(
                            (string) $currentProviderLocationId,
                            (string) $dropoffId,
                            (string) $validated['date_from'],
                            $startTimeForProvider,
                            (string) $validated['date_to'],
                            $endTimeForProvider,
                            $requestCurrency
                        );

                        if (!empty($favricaResponse)) {
                            $startDate = \Carbon\Carbon::parse($validated['date_from']);
                            $endDate = \Carbon\Carbon::parse($validated['date_to']);
                            $rentalDays = max(1, $startDate->diffInDays($endDate));

                            foreach ($favricaResponse as $vehicle) {
                                if (!is_array($vehicle)) {
                                    continue;
                                }
                                if (isset($vehicle['success']) && strtolower((string) $vehicle['success']) === 'false') {
                                    continue;
                                }

                                $sippCode = (string) ($vehicle['sipp'] ?? '');
                                $parsedSipp = $sippCode !== '' ? $this->parseSippCode($sippCode) : [];
                                $categoryName = $parsedSipp['category'] ?? 'Unknown';
                                if ($categoryName === 'Unknown' && !empty($vehicle['group_str'])) {
                                    $categoryName = $vehicle['group_str'];
                                }

                                $totalRental = $this->parseFavricaNumber($vehicle['total_rental'] ?? null);
                                $dailyRental = $this->parseFavricaNumber($vehicle['daily_rental'] ?? null);
                                if (!$dailyRental && $totalRental) {
                                    $dailyRental = $totalRental / $rentalDays;
                                }
                                $currency = $this->normalizeFavricaCurrency($vehicle['currency'] ?? $requestCurrency);
                                $provision = $this->parseFavricaNumber($vehicle['provision'] ?? null);
                                $dropFee = $this->parseFavricaNumber($vehicle['drop'] ?? null);

                                $carName = trim((string) ($vehicle['car_name'] ?? ''));
                                $brand = trim((string) ($vehicle['brand'] ?? ''));
                                if ($brand === '' && $carName !== '') {
                                    $brand = trim(strtok($carName, ' ')) ?: 'Favrica';
                                }
                                if ($brand === '') {
                                    $brand = 'Favrica';
                                }
                                $model = $carName;
                                if ($brand !== '' && $carName !== '') {
                                    $model = trim(str_ireplace($brand, '', $carName));
                                    $model = ltrim($model, '- ');
                                }
                                if ($model === '') {
                                    $model = $vehicle['type'] ?? 'Vehicle';
                                }

                                $transmission = $this->normalizeFavricaTransmission($vehicle['transmission'] ?? null, $parsedSipp['transmission'] ?? null);
                                $fuel = $this->normalizeFavricaFuel($vehicle['fuel'] ?? null, $parsedSipp['fuel'] ?? null);

                                $chairs = $vehicle['chairs'] ?? null;
                                $seatingCapacity = $chairs !== null ? (int) $chairs : (int) ($parsedSipp['seating_capacity'] ?? 5);
                                $doors = (int) ($parsedSipp['doors'] ?? 4);

                                $kmLimit = $this->parseFavricaNumber($vehicle['km_limit'] ?? null);
                                $mileage = ($kmLimit !== null && $kmLimit > 0) ? (string) $kmLimit : 'Unlimited';

                                $smallBags = (int) ($vehicle['small_bags'] ?? 0);
                                $bigBags = (int) ($vehicle['big_bags'] ?? 0);

                                $features = [];
                                if (!empty($parsedSipp['air_conditioning'])) {
                                    $features[] = 'Air Conditioning';
                                }

                                $extras = [];
                                $insuranceOptions = [];
                                $services = $vehicle['Services'] ?? [];
                                if (is_array($services)) {
                                    foreach ($services as $service) {
                                        if (!is_array($service)) {
                                            continue;
                                        }
                                        $serviceCode = $service['service_name'] ?? null;
                                        if (!$serviceCode) {
                                            continue;
                                        }
                                        $servicePrice = $this->parseFavricaNumber($service['service_total_price'] ?? null) ?? 0.0;
                                        if ($servicePrice < 0) {
                                            continue;
                                        }
                                        $serviceTitle = $service['service_title'] ?? $serviceCode;
                                        $serviceDesc = $service['service_desc'] ?? $serviceTitle;
                                        $isInsurance = $this->isFavricaInsuranceService($serviceCode, $serviceTitle, $serviceDesc);
                                        $payload = [
                                            'id' => 'favrica_extra_' . $serviceCode,
                                            'code' => $serviceCode,
                                            'name' => $serviceTitle,
                                            'description' => $serviceDesc,
                                            'price' => $servicePrice,
                                            'daily_rate' => $rentalDays > 0 ? $servicePrice / $rentalDays : $servicePrice,
                                            'amount' => $servicePrice,
                                            'total_for_booking' => $servicePrice,
                                            'currency' => $currency,
                                            'service_id' => $serviceCode,
                                            'required' => false,
                                            'numberAllowed' => 1,
                                            'type' => $isInsurance ? 'insurance' : 'extra',
                                        ];

                                        if ($isInsurance) {
                                            $insuranceOptions[] = $payload;
                                        } else {
                                            $extras[] = $payload;
                                        }
                                    }
                                }

                                $products = [[
                                    'type' => 'BAS',
                                    'total' => $totalRental ?? 0,
                                    'currency' => $currency,
                                    'deposit' => $provision,
                                    'mileage' => $mileage,
                                ]];

                                $idSuffix = $vehicle['rez_id'] ?? ($vehicle['group_id'] ?? md5($carName . '|' . $currentProviderLocationId));

                                    $providerVehicles->push([
                                        'id' => 'favrica_' . $currentProviderLocationId . '_' . $idSuffix,
                                        'location_id' => $currentProviderLocationId,
                                        'source' => 'favrica',
                                    'brand' => $brand,
                                    'model' => $model,
                                    'category' => $categoryName,
                                    'image' => $this->resolveFavricaImageUrl($vehicle['image_path'] ?? null),
                                    'total_price' => $totalRental ?? 0,
                                    'price_per_day' => $dailyRental ?? 0,
                                    'price_per_week' => null,
                                    'price_per_month' => null,
                                    'currency' => $currency,
                                    'transmission' => $transmission,
                                    'fuel' => $fuel,
                                    'seating_capacity' => $seatingCapacity,
                                    'mileage' => $mileage,
                                        'latitude' => (float) $entryLat,
                                        'longitude' => (float) $entryLng,
                                        'full_vehicle_address' => $currentProviderLocationName,
                                        'location_details' => $pickupLocationDetails,
                                        'dropoff_location_details' => $dropoffLocationDetails,
                                        'location_instructions' => $pickupLocationDetails['collection_details'] ?? null,
                                        'provider_pickup_id' => (string) $currentProviderLocationId,
                                        'provider_return_id' => (string) $dropoffId,
                                    'features' => $features,
                                    'airConditioning' => !empty($parsedSipp['air_conditioning']),
                                    'sipp_code' => $sippCode,
                                    'bags' => $smallBags,
                                    'suitcases' => $bigBags,
                                    'doors' => $doors,
                                    'products' => $products,
                                    'insurance_options' => $insuranceOptions,
                                    'extras' => $extras,
                                    'favrica_rez_id' => $vehicle['rez_id'] ?? null,
                                    'favrica_cars_park_id' => $vehicle['cars_park_id'] ?? null,
                                    'favrica_group_id' => $vehicle['group_id'] ?? null,
                                    'favrica_car_web_id' => $vehicle['car_web_id'] ?? null,
                                    'favrica_reservation_source_id' => $vehicle['reservation_source_id'] ?? null,
                                    'favrica_reservation_source' => $vehicle['reservation_source'] ?? null,
                                    'favrica_drop_fee' => $dropFee,
                                    'favrica_provision' => $provision,
                                ]);
                            }
                        } else {
                            Log::info('Favrica API returned empty vehicle list', [
                                'location_id' => $currentProviderLocationId,
                            ]);
                        }
                    } catch (\Exception $e) {
                        $recordProviderError($providerToFetch, $e->getMessage());
                        Log::error('Error fetching Favrica vehicles: ' . $e->getMessage(), [
                            'provider_location_id' => $currentProviderLocationId,
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                } elseif ($providerToFetch === 'xdrive') {
                    try {
                        Log::info('Attempting to fetch XDrive vehicles for location ID: ' . $currentProviderLocationId);
                        $dropoffId = $dropoffIdForProvider;
                        $requestCurrency = $this->toXDriveRequestCurrency($validated['currency'] ?? 'EUR');

                        $xdriveLocationMap = [];
                        try {
                            $xdriveLocations = $this->xdriveService->getLocations();
                            $xdriveLocationMap = $this->buildXDriveLocationMap($xdriveLocations);
                        } catch (\Exception $e) {
                            Log::warning('XDrive location lookup failed', [
                                'location_id' => $currentProviderLocationId,
                                'error' => $e->getMessage(),
                            ]);
                        }

                        $pickupLocationDetails = $xdriveLocationMap[(string) $currentProviderLocationId] ?? null;
                        if ($pickupLocationDetails) {
                            if (empty($pickupLocationDetails['latitude']) && $entryLat !== null) {
                                $pickupLocationDetails['latitude'] = (string) $entryLat;
                            }
                            if (empty($pickupLocationDetails['longitude']) && $entryLng !== null) {
                                $pickupLocationDetails['longitude'] = (string) $entryLng;
                            }
                            if (empty($pickupLocationDetails['name']) && $currentProviderLocationName) {
                                $pickupLocationDetails['name'] = $currentProviderLocationName;
                            }
                        }

                        $dropoffLocationDetails = null;
                        if ($dropoffId) {
                            $dropoffLocationDetails = $xdriveLocationMap[(string) $dropoffId] ?? null;
                        }

                        $xdriveResponse = $this->xdriveService->searchRez(
                            (string) $currentProviderLocationId,
                            (string) $dropoffId,
                            (string) $validated['date_from'],
                            $startTimeForProvider,
                            (string) $validated['date_to'],
                            $endTimeForProvider,
                            $requestCurrency
                        );

                        if (empty($xdriveResponse) && strtoupper($requestCurrency) !== 'USD') {
                            Log::info('XDrive returned empty response, retrying with USD', [
                                'location_id' => $currentProviderLocationId,
                                'currency' => $requestCurrency,
                            ]);
                            $requestCurrency = 'USD';
                            $xdriveResponse = $this->xdriveService->searchRez(
                                (string) $currentProviderLocationId,
                                (string) $dropoffId,
                                (string) $validated['date_from'],
                                $startTimeForProvider,
                                (string) $validated['date_to'],
                                $endTimeForProvider,
                                $requestCurrency
                            );
                        }

                        if (!empty($xdriveResponse)) {
                            $startDate = \Carbon\Carbon::parse($validated['date_from']);
                            $endDate = \Carbon\Carbon::parse($validated['date_to']);
                            $rentalDays = max(1, $startDate->diffInDays($endDate));

                            foreach ($xdriveResponse as $vehicle) {
                                if (!is_array($vehicle)) {
                                    continue;
                                }
                                if (isset($vehicle['success']) && strtolower((string) $vehicle['success']) === 'false') {
                                    continue;
                                }

                                $sippCode = (string) ($vehicle['sipp'] ?? '');
                                $parsedSipp = $sippCode !== '' ? $this->parseSippCode($sippCode) : [];
                                $categoryName = $parsedSipp['category'] ?? 'Unknown';
                                if ($categoryName === 'Unknown' && !empty($vehicle['group_str'])) {
                                    $categoryName = $vehicle['group_str'];
                                }

                                $totalRental = $this->parseFavricaNumber($vehicle['total_rental'] ?? null);
                                $dailyRental = $this->parseFavricaNumber($vehicle['daily_rental'] ?? null);
                                if (!$dailyRental && $totalRental) {
                                    $dailyRental = $totalRental / $rentalDays;
                                }
                                $currency = $this->normalizeXDriveCurrency($vehicle['currency'] ?? $requestCurrency);
                                $provision = $this->parseFavricaNumber($vehicle['provision'] ?? null);
                                $dropFee = $this->parseFavricaNumber($vehicle['drop'] ?? null);

                                $carName = trim((string) ($vehicle['car_name'] ?? ''));
                                $brand = trim((string) ($vehicle['brand'] ?? ''));
                                if ($brand === '' && $carName !== '') {
                                    $brand = trim(strtok($carName, ' ')) ?: 'XDrive';
                                }
                                if ($brand === '') {
                                    $brand = 'XDrive';
                                }
                                $model = $carName;
                                if ($brand !== '' && $carName !== '') {
                                    $model = trim(str_ireplace($brand, '', $carName));
                                    $model = ltrim($model, '- ');
                                }
                                if ($model === '') {
                                    $model = $vehicle['type'] ?? 'Vehicle';
                                }

                                $transmission = $this->normalizeFavricaTransmission($vehicle['transmission'] ?? null, $parsedSipp['transmission'] ?? null);
                                $fuel = $this->normalizeFavricaFuel($vehicle['fuel'] ?? null, $parsedSipp['fuel'] ?? null);

                                $chairs = $vehicle['chairs'] ?? null;
                                $seatingCapacity = $chairs !== null ? (int) $chairs : (int) ($parsedSipp['seating_capacity'] ?? 5);
                                $doors = (int) ($parsedSipp['doors'] ?? 4);

                                $kmLimit = $this->parseFavricaNumber($vehicle['km_limit'] ?? null);
                                $mileage = ($kmLimit !== null && $kmLimit > 0) ? (string) $kmLimit : 'Unlimited';

                                $smallBags = (int) ($vehicle['small_bags'] ?? 0);
                                $bigBags = (int) ($vehicle['big_bags'] ?? 0);

                                $features = [];
                                if (!empty($parsedSipp['air_conditioning'])) {
                                    $features[] = 'Air Conditioning';
                                }

                                $extras = [];
                                $insuranceOptions = [];
                                $services = $vehicle['Services'] ?? [];
                                if (is_array($services)) {
                                    foreach ($services as $service) {
                                        if (!is_array($service)) {
                                            continue;
                                        }
                                        $serviceCode = $service['service_name'] ?? null;
                                        if (!$serviceCode) {
                                            continue;
                                        }
                                        $servicePrice = $this->parseFavricaNumber($service['service_total_price'] ?? null) ?? 0.0;
                                        if ($servicePrice < 0) {
                                            continue;
                                        }
                                        $serviceTitle = $service['service_title'] ?? $serviceCode;
                                        $serviceDesc = $service['service_desc'] ?? $serviceTitle;
                                        $isInsurance = $this->isFavricaInsuranceService($serviceCode, $serviceTitle, $serviceDesc);
                                        $payload = [
                                            'id' => 'xdrive_extra_' . $serviceCode,
                                            'code' => $serviceCode,
                                            'name' => $serviceTitle,
                                            'description' => $serviceDesc,
                                            'price' => $servicePrice,
                                            'daily_rate' => $rentalDays > 0 ? $servicePrice / $rentalDays : $servicePrice,
                                            'amount' => $servicePrice,
                                            'total_for_booking' => $servicePrice,
                                            'currency' => $currency,
                                            'service_id' => $serviceCode,
                                            'required' => false,
                                            'numberAllowed' => 1,
                                            'type' => $isInsurance ? 'insurance' : 'extra',
                                        ];

                                        if ($isInsurance) {
                                            $insuranceOptions[] = $payload;
                                        } else {
                                            $extras[] = $payload;
                                        }
                                    }
                                }

                                $products = [[
                                    'type' => 'BAS',
                                    'total' => $totalRental ?? 0,
                                    'currency' => $currency,
                                    'deposit' => $provision,
                                    'mileage' => $mileage,
                                ]];

                                $idSuffix = $vehicle['rez_id'] ?? ($vehicle['group_id'] ?? md5($carName . '|' . $currentProviderLocationId));

                                $providerVehicles->push([
                                    'id' => 'xdrive_' . $currentProviderLocationId . '_' . $idSuffix,
                                    'location_id' => $currentProviderLocationId,
                                    'source' => 'xdrive',
                                    'brand' => $brand,
                                    'model' => $model,
                                    'category' => $categoryName,
                                    'image' => $this->resolveXDriveImageUrl($vehicle['image_path'] ?? $vehicle['image'] ?? $vehicle['image_url'] ?? $vehicle['imagepath'] ?? null),
                                    'total_price' => $totalRental ?? 0,
                                    'price_per_day' => $dailyRental ?? 0,
                                    'price_per_week' => null,
                                    'price_per_month' => null,
                                    'currency' => $currency,
                                    'transmission' => $transmission,
                                    'fuel' => $fuel,
                                    'seating_capacity' => $seatingCapacity,
                                    'mileage' => $mileage,
                                    'latitude' => (float) $entryLat,
                                    'longitude' => (float) $entryLng,
                                    'full_vehicle_address' => $currentProviderLocationName,
                                    'location_details' => $pickupLocationDetails,
                                    'dropoff_location_details' => $dropoffLocationDetails,
                                    'location_instructions' => $pickupLocationDetails['collection_details'] ?? null,
                                    'provider_pickup_id' => (string) $currentProviderLocationId,
                                    'provider_return_id' => (string) $dropoffId,
                                    'features' => $features,
                                    'airConditioning' => !empty($parsedSipp['air_conditioning']),
                                    'sipp_code' => $sippCode,
                                    'bags' => $smallBags,
                                    'suitcases' => $bigBags,
                                    'doors' => $doors,
                                    'products' => $products,
                                    'insurance_options' => $insuranceOptions,
                                    'extras' => $extras,
                                    'xdrive_rez_id' => $vehicle['rez_id'] ?? null,
                                    'xdrive_cars_park_id' => $vehicle['cars_park_id'] ?? null,
                                    'xdrive_group_id' => $vehicle['group_id'] ?? null,
                                    'xdrive_car_web_id' => $vehicle['car_web_id'] ?? null,
                                    'xdrive_reservation_source_id' => $vehicle['reservation_source_id'] ?? null,
                                    'xdrive_reservation_source' => $vehicle['reservation_source'] ?? null,
                                    'xdrive_drop_fee' => $dropFee,
                                    'xdrive_provision' => $provision,
                                ]);
                            }
                        } else {
                            Log::info('XDrive API returned empty vehicle list', [
                                'location_id' => $currentProviderLocationId,
                            ]);
                        }
                    } catch (\Exception $e) {
                        $recordProviderError($providerToFetch, $e->getMessage());
                        Log::error('Error fetching XDrive vehicles: ' . $e->getMessage(), [
                            'provider_location_id' => $currentProviderLocationId,
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                } elseif ($providerToFetch === 'sicily_by_car') {
                    try {
                        if ($sicilyByCarLocationMap === null && !$sicilyByCarLocationFetchFailed) {
                            try {
                                $sbcLocations = $this->sicilyByCarService->listLocations();
                                $sicilyByCarLocationMap = $this->buildSicilyByCarLocationMap($sbcLocations);
                            } catch (\Exception $e) {
                                $sicilyByCarLocationFetchFailed = true;
                                $sicilyByCarLocationMap = [];
                                Log::warning('Sicily By Car location lookup failed', [
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }

                        $pickupLocationDetails = $sicilyByCarLocationMap[(string) $currentProviderLocationId] ?? null;
                        if ($pickupLocationDetails) {
                            if (empty($pickupLocationDetails['latitude']) && $entryLat !== null) {
                                $pickupLocationDetails['latitude'] = (string) $entryLat;
                            }
                            if (empty($pickupLocationDetails['longitude']) && $entryLng !== null) {
                                $pickupLocationDetails['longitude'] = (string) $entryLng;
                            }
                            if (empty($pickupLocationDetails['name']) && $currentProviderLocationName) {
                                $pickupLocationDetails['name'] = $currentProviderLocationName;
                            }
                        }

                        $dropoffLocationDetails = null;
                        if ($dropoffIdForProvider) {
                            $dropoffLocationDetails = $sicilyByCarLocationMap[(string) $dropoffIdForProvider] ?? null;
                        }

                        $pickupDateTime = $validated['date_from'] . 'T' . $startTimeForProvider;
                        $dropoffDateTime = $validated['date_to'] . 'T' . $endTimeForProvider;

                        $dropoffDifferent = $dropoffIdForProvider && $dropoffIdForProvider !== $currentProviderLocationId;

                        $posCountry = null;
                        if (!empty($validated['country']) && is_string($validated['country'])) {
                            $countryValue = strtoupper(trim($validated['country']));
                            if (strlen($countryValue) === 2) {
                                $posCountry = $countryValue;
                            }
                        }

                        $availabilityPayload = [
                            'pickupLocation' => $currentProviderLocationId,
                            'pickupDateTime' => $pickupDateTime,
                            'dropoffAtDifferentLocation' => (bool) $dropoffDifferent,
                            'dropoffLocation' => $dropoffIdForProvider,
                            'dropoffDateTime' => $dropoffDateTime,
                            'posCountry' => $posCountry,
                            'driverAge' => (int) ($validated['age'] ?? 35),
                        ];

                        $availabilityPayload = array_filter(
                            $availabilityPayload,
                            static fn($value) => $value !== null && $value !== ''
                        );

                        $sbcResponse = $this->sicilyByCarService->offersAvailability($availabilityPayload);
                        if (!($sbcResponse['ok'] ?? false)) {
                            $recordProviderError($providerToFetch, 'Availability request failed');
                            Log::warning('Sicily By Car availability error', [
                                'location_id' => $currentProviderLocationId,
                                'errors' => $sbcResponse['errors'] ?? null,
                            ]);
                        } else {
                            $payload = $sbcResponse['data'] ?? [];
                            $offers = $payload['offers'] ?? [];
                            $availabilityId = $payload['availabilityId'] ?? null;
                            $availabilityExpiration = $payload['availabilityExpiration'] ?? null;

                            foreach ($offers as $offer) {
                                if (!is_array($offer)) {
                                    continue;
                                }

                                $vehicleInfo = $offer['vehicle'] ?? [];
                                $rateInfo = $offer['rate'] ?? [];
                                $totalPrices = $offer['totalPrices'] ?? [];

                                $vehicleId = (string) ($vehicleInfo['id'] ?? '');
                                $rateId = (string) ($rateInfo['id'] ?? '');
                                if ($vehicleId === '' || $rateId === '') {
                                    continue;
                                }

                                $sippCode = (string) ($vehicleInfo['sipp'] ?? '');
                                $parsedSipp = $sippCode !== '' ? $this->parseSippCode($sippCode) : [];

                                $seatingCapacity = $vehicleInfo['numberOfPassengers'] ?? ($parsedSipp['seating_capacity'] ?? null);
                                $doors = $vehicleInfo['numberOfDoors'] ?? ($parsedSipp['doors'] ?? null);

                                $totalAmount = (float) ($totalPrices['total'] ?? 0);
                                $currency = (string) ($offer['currency'] ?? 'EUR');
                                $pricePerDay = $rentalDays > 0 ? $totalAmount / $rentalDays : $totalAmount;

                                $distanceInfo = $rateInfo['distance'] ?? [];
                                $isUnlimited = is_array($distanceInfo) && !empty($distanceInfo['unlimited']);

                                $providerVehicles->push((object) [
                                    'id' => 'sicily_by_car_' . $currentProviderLocationId . '_' . $vehicleId . '_' . $rateId,
                                    'source' => 'sicily_by_car',
                                    'provider_vehicle_id' => $vehicleId,
                                    'brand' => null,
                                    'model' => $vehicleInfo['description'] ?? 'Vehicle',
                                    'image' => $vehicleInfo['imageUrl'] ?? '/images/default-car.jpg',
                                    'price_per_day' => (float) $pricePerDay,
                                    'total_price' => (float) $totalAmount,
                                    'price_per_week' => null,
                                    'price_per_month' => null,
                                    'currency' => $currency,
                                    'transmission' => $parsedSipp['transmission'] ?? 'manual',
                                    'fuel' => $parsedSipp['fuel'] ?? 'petrol',
                                    'category' => $parsedSipp['category'] ?? 'Unknown',
                                    'seating_capacity' => $seatingCapacity,
                                    'doors' => $doors,
                                    'bags' => $vehicleInfo['numberOfLargeBags'] ?? null,
                                    'suitcases' => $vehicleInfo['numberOfSmallBags'] ?? null,
                                    'mileage' => $isUnlimited ? 'Unlimited' : null,
                                    'latitude' => (float) $entryLat,
                                    'longitude' => (float) $entryLng,
                                    'full_vehicle_address' => $currentProviderLocationName,
                                    'location_details' => $pickupLocationDetails,
                                    'dropoff_location_details' => $dropoffLocationDetails,
                                    'location_instructions' => $pickupLocationDetails['collection_details'] ?? null,
                                    'provider_pickup_id' => $currentProviderLocationId,
                                    'provider_dropoff_id' => $dropoffIdForProvider,
                                    'provider_return_id' => $dropoffIdForProvider,
                                    'sipp_code' => $sippCode,
                                    'availability' => $offer['availability'] ?? null,
                                    'rate_id' => $rateId,
                                    'rate_name' => $rateInfo['description'] ?? null,
                                    'payment_type' => $rateInfo['payment'] ?? null,
                                    'deposit' => $offer['deposit'] ?? null,
                                    'availability_id' => $availabilityId,
                                    'availability_expiration' => $availabilityExpiration,
                                    'extras' => $offer['services'] ?? [],
                                    'benefits' => [
                                        'pay_on_arrival' => ($rateInfo['payment'] ?? '') === 'PayOnArrival',
                                        'limited_km_per_day' => $isUnlimited ? false : null,
                                    ],
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        $recordProviderError($providerToFetch, $e->getMessage());
                        Log::error('Error fetching Sicily By Car vehicles: ' . $e->getMessage(), [
                            'provider_location_id' => $currentProviderLocationId,
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                } elseif ($providerToFetch === 'recordgo') {
                    try {
                        if (isset($recordGoProcessedPickups[$currentProviderLocationId])) {
                            continue;
                        }
                        $recordGoProcessedPickups[$currentProviderLocationId] = true;

                        $countryRaw = strtoupper(trim((string) ($validated['country'] ?? '')));
                        // Convert full country names to ISO 2-letter codes for RecordGo API
                        $countryNameToCode = [
                            'GREECE' => 'GR',
                            'ITALY' => 'IT',
                            'PORTUGAL' => 'PT',
                            'SPAIN' => 'ES',
                            'CANARY ISLANDS' => 'IC',
                        ];
                        $countryCode = (strlen($countryRaw) === 2) ? $countryRaw : ($countryNameToCode[$countryRaw] ?? $countryRaw);
                        if ($countryCode === '') {
                            $countryCode = 'IT';
                        }

                        $sellCode = $this->recordGoService->resolveSellCode($countryCode);
                        if (!$sellCode) {
                            $recordProviderError($providerToFetch, 'Missing sellCode for Record Go');
                            Log::warning('Record Go sellCode missing', ['country' => $countryCode]);
                            continue;
                        }

                        $formatDateTime = static function (string $date, string $time): string {
                            $time = trim($time);
                            if ($time === '') {
                                $time = '09:00';
                            }
                            if (strlen($time) === 5) {
                                $time .= ':00';
                            }
                            return $date . 'T' . $time;
                        };

                        $pickupDateTime = $formatDateTime($validated['date_from'], $startTimeForProvider);
                        $dropoffDateTime = $formatDateTime($validated['date_to'], $endTimeForProvider);

                        $language = strtoupper((string) ($validated['language'] ?? 'EN'));
                        if (strlen($language) !== 2) {
                            $language = 'EN';
                        }

                        $availabilityPayload = [
                            'partnerUser' => $this->recordGoService->getPartnerUser(),
                            'country' => $countryCode,
                            'sellCode' => $sellCode,
                            'pickupBranch' => (int) $currentProviderLocationId,
                            'dropoffBranch' => (int) $dropoffIdForProvider,
                            'pickupDateTime' => $pickupDateTime,
                            'dropoffDateTime' => $dropoffDateTime,
                            'driverAge' => isset($validated['age']) ? (int) $validated['age'] : null,
                            'language' => $language,
                        ];

                        $availabilityPayload = array_filter(
                            $availabilityPayload,
                            static fn($value) => $value !== null && $value !== ''
                        );

                        $recordGoResponse = $this->recordGoService->getAvailability($availabilityPayload);
                        if (!($recordGoResponse['ok'] ?? false)) {
                            $recordProviderError($providerToFetch, 'Availability request failed');
                            Log::warning('Record Go availability error', [
                                'location_id' => $currentProviderLocationId,
                                'errors' => $recordGoResponse['errors'] ?? null,
                            ]);
                        } else {
                            $payload = $recordGoResponse['data'] ?? [];
                            $sellCodeVer = $payload['sellCodeVer'] ?? null;
                            $acrissList = $payload['acriss'] ?? [];

                            $currency = in_array($countryCode, ['IC', 'IT', 'PT', 'GR'], true) ? 'EUR' : 'EUR';

                            foreach ($acrissList as $acriss) {
                                if (!is_array($acriss)) {
                                    continue;
                                }
                                if (isset($acriss['available']) && !$acriss['available']) {
                                    continue;
                                }

                                $acrissCode = (string) ($acriss['acrissCode'] ?? '');
                                $acrissId = $acriss['acrissId'] ?? null;
                                $images = $acriss['imagesArray'] ?? [];
                                $defaultImage = null;
                                $displayName = null;
                                if (is_array($images)) {
                                    foreach ($images as $img) {
                                        if (!is_array($img)) {
                                            continue;
                                        }
                                        if (!empty($img['isDefault'])) {
                                            $defaultImage = $img['acrissImgUrl'] ?? null;
                                            $displayName = $img['acrissDisplayName'] ?? null;
                                            break;
                                        }
                                    }
                                    if (!$defaultImage && isset($images[0]['acrissImgUrl'])) {
                                        $defaultImage = $images[0]['acrissImgUrl'];
                                        $displayName = $images[0]['acrissDisplayName'] ?? null;
                                    }
                                }

                                $products = $acriss['products'] ?? [];
                                if (!is_array($products) || empty($products)) {
                                    continue;
                                }

                                $recordGoProducts = [];
                                $minTotal = null;
                                $minDaily = null;
                                $primaryMileage = null;

                                $extractPreauthExcess = static function (array $included) {
                                    $deposit = null;
                                    $excess = null;
                                    $excessLow = null;
                                    foreach ($included as $comp) {
                                        if (!is_array($comp)) {
                                            continue;
                                        }
                                        $entries = $comp['preauth&Excess'] ?? $comp['preauthExcess'] ?? [];
                                        if (!is_array($entries)) {
                                            continue;
                                        }
                                        foreach ($entries as $entry) {
                                            if (!is_array($entry)) {
                                                continue;
                                            }
                                            $type = strtolower((string) ($entry['type'] ?? ''));
                                            $value = isset($entry['value']) ? (float) $entry['value'] : null;
                                            if ($value === null) {
                                                continue;
                                            }
                                            if (str_contains($type, 'preauth')) {
                                                $deposit = $deposit === null ? $value : max($deposit, $value);
                                            } elseif (str_contains($type, 'excesslow')) {
                                                $excessLow = $excessLow === null ? $value : max($excessLow, $value);
                                            } elseif (str_contains($type, 'excess')) {
                                                $excess = $excess === null ? $value : max($excess, $value);
                                            }
                                        }
                                    }
                                    return [
                                        'deposit' => $deposit,
                                        'excess' => $excess,
                                        'excess_low' => $excessLow,
                                    ];
                                };

                                foreach ($products as $productData) {
                                    if (!is_array($productData)) {
                                        continue;
                                    }
                                    $product = $productData['product'] ?? [];
                                    $productId = $product['productId'] ?? null;
                                    $productVer = $product['productVer'] ?? null;
                                    $rateProdVer = $productData['rateProdVer'] ?? null;

                                    $pricePerDay = $productData['priceTaxIncDayDiscount']
                                        ?? $productData['priceTaxIncDay']
                                        ?? null;
                                    $totalPrice = $productData['priceTaxIncBookingDiscount']
                                        ?? $productData['priceTaxIncBooking']
                                        ?? null;

                                    if ($totalPrice === null && $pricePerDay !== null) {
                                        $totalPrice = $rentalDays > 0 ? ((float) $pricePerDay * $rentalDays) : (float) $pricePerDay;
                                    }

                                    $productMileage = $product['kmPolicyCommercial']['kmPolicyTransName'] ?? null;
                                    if (!$primaryMileage && $productMileage) {
                                        $primaryMileage = $productMileage;
                                    }

                                    $includedComplements = $product['productComplementsIncluded'] ?? [];
                                    $automaticComplements = $product['productComplementsAutom'] ?? [];
                                    $preauthExcess = $extractPreauthExcess(is_array($includedComplements) ? $includedComplements : []);

                                    $cacheKey = implode('|', [
                                        $productId,
                                        $acrissCode,
                                        $currentProviderLocationId,
                                        $dropoffIdForProvider,
                                        $pickupDateTime,
                                        $dropoffDateTime,
                                        $language,
                                    ]);

                                    if (!isset($recordGoComplementsCache[$cacheKey])) {
                                        $associatedPayload = [
                                            'partnerUser' => $this->recordGoService->getPartnerUser(),
                                            'country' => $countryCode,
                                            'sellCode' => $sellCode,
                                            'pickupBranch' => (int) $currentProviderLocationId,
                                            'dropoffBranch' => (int) $dropoffIdForProvider,
                                            'pickupDateTime' => $pickupDateTime,
                                            'dropoffDateTime' => $dropoffDateTime,
                                            'driverAge' => isset($validated['age']) ? (int) $validated['age'] : null,
                                            'productId' => $productId,
                                            'acrissCode' => $acrissCode,
                                            'language' => $language,
                                        ];
                                        $associatedPayload = array_filter(
                                            $associatedPayload,
                                            static fn($value) => $value !== null && $value !== ''
                                        );

                                        $assocResponse = $this->recordGoService->getAssociatedComplements($associatedPayload);
                                        if (!($assocResponse['ok'] ?? false)) {
                                            Log::warning('Record Go complements error', [
                                                'product_id' => $productId,
                                                'acriss' => $acrissCode,
                                                'errors' => $assocResponse['errors'] ?? null,
                                            ]);
                                            $recordGoComplementsCache[$cacheKey] = [
                                                'associated' => [],
                                                'automatic' => [],
                                            ];
                                        } else {
                                            $assocData = $assocResponse['data'] ?? [];
                                            $recordGoComplementsCache[$cacheKey] = [
                                                'associated' => $assocData['productAssociatedComplements'] ?? [],
                                                'automatic' => $assocData['productAutomaticComplements'] ?? [],
                                            ];
                                        }
                                    }

                                    $cachedComplements = $recordGoComplementsCache[$cacheKey] ?? ['associated' => [], 'automatic' => []];

                                    $recordGoProducts[] = [
                                        'type' => 'RG_' . ($productId ?? 'prod') . '_' . ($rateProdVer ?? 'rate'),
                                        'name' => $product['productName'] ?? 'Record Go',
                                        'subtitle' => $product['productSubtitle'] ?? null,
                                        'description' => $product['productDescription'] ?? null,
                                        'total' => $totalPrice !== null ? (float) $totalPrice : null,
                                        'price_per_day' => $pricePerDay !== null ? (float) $pricePerDay : null,
                                        'product_id' => $productId,
                                        'product_ver' => $productVer,
                                        'rate_prod_ver' => $rateProdVer,
                                        'deposit' => $preauthExcess['deposit'] ?? null,
                                        'excess' => $preauthExcess['excess'] ?? null,
                                        'excess_low' => $preauthExcess['excess_low'] ?? null,
                                        'complements_autom' => $automaticComplements,
                                        'complements_included' => $includedComplements,
                                        'complements_associated' => $cachedComplements['associated'] ?? [],
                                        'complements_automatic' => $cachedComplements['automatic'] ?? [],
                                        'refuel_policy' => $product['refuelPolicyCommercial'] ?? null,
                                        'km_policy' => $product['kmPolicyCommercial'] ?? null,
                                    ];

                                    if ($totalPrice !== null) {
                                        $minTotal = $minTotal === null ? (float) $totalPrice : min($minTotal, (float) $totalPrice);
                                    }
                                    if ($pricePerDay !== null) {
                                        $minDaily = $minDaily === null ? (float) $pricePerDay : min($minDaily, (float) $pricePerDay);
                                    }
                                }

                                if (empty($recordGoProducts)) {
                                    continue;
                                }

                                $vehicleId = 'recordgo_' . $currentProviderLocationId . '_' . ($acrissCode ?: 'acriss');

                                $providerVehicles->push((object) [
                                    'id' => $vehicleId,
                                    'source' => 'recordgo',
                                    'brand' => null,
                                    'model' => $displayName ?: ($acrissCode ?: 'Record Go Vehicle'),
                                    'image' => $defaultImage ?: '/images/default-car.jpg',
                                    'price_per_day' => $minDaily,
                                    'total_price' => $minTotal,
                                    'price_per_week' => null,
                                    'price_per_month' => null,
                                    'currency' => $currency,
                                    'transmission' => strtolower((string) ($acriss['gearboxType'] ?? 'manual')),
                                    'fuel' => null,
                                    'category' => $acrissCode ?: 'Unknown',
                                    'seating_capacity' => $acriss['acrissSeats'] ?? null,
                                    'doors' => $acriss['acrissDoors'] ?? null,
                                    'bags' => $acriss['acrissSuitcase'] ?? null,
                                    'suitcases' => $acriss['acrissSuitcase'] ?? null,
                                    'mileage' => $primaryMileage,
                                    'latitude' => (float) $entryLat,
                                    'longitude' => (float) $entryLng,
                                    'full_vehicle_address' => $currentProviderLocationName,
                                    'location_details' => [
                                        'name' => $currentProviderLocationName,
                                        'address_city' => $validated['city'] ?? null,
                                        'address_country' => $countryCode,
                                        'telephone' => null,
                                        'email' => null,
                                    ],
                                    'provider_pickup_id' => $currentProviderLocationId,
                                    'provider_dropoff_id' => $dropoffIdForProvider,
                                    'provider_return_id' => $dropoffIdForProvider,
                                    'sipp_code' => $acrissCode,
                                    'recordgo_acriss_id' => $acrissId,
                                    'recordgo_sellcode_ver' => $sellCodeVer,
                                    'recordgo_country' => $countryCode,
                                    'recordgo_partner_user' => $this->recordGoService->getPartnerUser(),
                                    'recordgo_products' => $recordGoProducts,
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        $recordProviderError($providerToFetch, $e->getMessage());
                        Log::error('Error fetching Record Go vehicles: ' . $e->getMessage(), [
                            'provider_location_id' => $currentProviderLocationId,
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                } elseif ($providerToFetch === 'wheelsys') {
                    Log::info('Attempting to fetch Wheelsys vehicles for location ID: ' . $currentProviderLocationId);
                        Log::info('Search params: ', [
                            'pickup_id' => $currentProviderLocationId,
                            'date_from' => $validated['date_from'],
                            'start_time' => $startTimeForProvider,
                            'date_to' => $validated['date_to'],
                            'end_time' => $endTimeForProvider
                        ]);

                    // Convert date format from YYYY-MM-DD to DD/MM/YYYY for Wheelsys
                    $dateFromFormatted = date('d/m/Y', strtotime($validated['date_from']));
                    $dateToFormatted = date('d/m/Y', strtotime($validated['date_to']));

                    try {
                        $wheelsysResponse = $this->wheelsysService->getVehicles(
                            $currentProviderLocationId,
                            $dropoffIdForProvider, // Wheelsys does not support one-way rentals - will always equal pickup
                            $dateFromFormatted,
                            $startTimeForProvider,
                            $dateToFormatted,
                            $endTimeForProvider
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
                                    $currentProviderLocationId,
                                    $entryLat,
                                    $entryLng,
                                    $locationAddress
                                );

                                if ($standardVehicle) {
                                    $standardVehicle['total_price'] = $standardVehicle['price_per_day']; // Service sets price_per_day to TotalRate
                                    $standardVehicle['price_per_day'] = (float) ($standardVehicle['total_price'] / $rentalDays);
                                    $providerVehicles->push($standardVehicle);
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
                            Log::warning("Wheelsys API response for vehicles was empty or no rates found for location ID: " . $currentProviderLocationId, [
                                'has_response' => !empty($wheelsysResponse),
                                'response_keys' => $wheelsysResponse ? array_keys($wheelsysResponse) : [],
                                'has_rates' => isset($ratesData) && !empty($ratesData)
                            ]);
                        }

                    } catch (\Exception $e) {
                        // Handle the new robust error scenarios from WheelsysService
                        $errorMessage = $e->getMessage();
                        $recordProviderError($providerToFetch, $errorMessage);

                        if (str_contains($errorMessage, 'temporarily unavailable due to repeated failures')) {
                            Log::warning('Wheelsys API circuit breaker is open - API temporarily unavailable', [
                                'provider_location_id' => $currentProviderLocationId,
                                'circuit_breaker_status' => $this->wheelsysService->getCircuitBreakerStatus()
                            ]);
                        } else if (str_contains($errorMessage, 'Invalid API response structure')) {
                            Log::error('Wheelsys API response validation failed', [
                                'provider_location_id' => $currentProviderLocationId,
                                'error' => $errorMessage
                            ]);
                        } else {
                            Log::error('Wheelsys API error after retries', [
                                'provider_location_id' => $currentProviderLocationId,
                                'error' => $errorMessage,
                                'circuit_breaker_status' => $this->wheelsysService->getCircuitBreakerStatus()
                            ]);
                        }

                        // Continue with other providers - don't fail the entire search
                        Log::info('Continuing search with other providers after Wheelsys failure');
                    }
                } // Close Wheelsys elseif
                elseif ($providerToFetch === 'locauto_rent') {
                    try {
                        Log::info('Attempting to fetch Locauto vehicles for location ID: ' . $currentProviderLocationId);
                        Log::info('Search params: ', [
                            'pickup_id' => $currentProviderLocationId,
                            'date_from' => $validated['date_from'],
                            'start_time' => $startTimeForProvider,
                            'date_to' => $validated['date_to'],
                            'end_time' => $endTimeForProvider,
                            'age' => $validated['age'] ?? 35
                        ]);

                        $locautoResponse = $this->locautoRentService->getVehicles(
                            $currentProviderLocationId,
                            $validated['date_from'],
                            $startTimeForProvider,
                            $validated['date_to'],
                            $endTimeForProvider,
                            $validated['age'] ?? 35,
                            ['return_location_code' => $dropoffIdForProvider]
                        );

                        if ($locautoResponse) {
                            Log::info('LocautoRent API response received successfully');

                            // Parse the vehicle response
                            $locautoVehicles = $this->locautoRentService->parseVehicleResponse($locautoResponse);

                            if (!empty($locautoVehicles)) {
                                Log::info('Parsed ' . count($locautoVehicles) . ' Locauto vehicles');

                                foreach ($locautoVehicles as $vehicle) {
                                    // Extract brand from model or use a default
                                    $brandName = 'Locauto';
                                    if (!empty($vehicle['brand'])) {
                                        $brandName = $vehicle['brand'];
                                    } elseif (!empty($vehicle['model'])) {
                                        $brandName = explode(' ', $vehicle['model'])[0];
                                    }

                                    // Convert total amount to per day if needed
                                    $totalPrice = $vehicle['total_amount'] ?? 0;

                                    // Parse SIPP if available to fill holes
                                    $sippCode = $vehicle['sipp_code'] ?? '';
                                    $parsedSipp = $this->parseSippCode($sippCode);

                                    $transmission = $vehicle['transmission'] ?? $parsedSipp['transmission'];
                                    $fuel = $vehicle['fuel'] ?? $parsedSipp['fuel'];
                                    // Ensure lowercase
                                    $transmission = strtolower($transmission ?? 'manual');
                                    $fuel = strtolower($fuel ?? 'petrol');

                                    // Build features array
                                    $features = [];
                                    if (!empty($parsedSipp['air_conditioning'])) {
                                        $features[] = 'Air Conditioning';
                                    }

                                    $assignedCategory = $parsedSipp['category'] ?? ($vehicle['category'] ?? 'Unknown');

                                    // Get extras from LocautoRent response (protection plans + optional extras)
                                    $allExtras = $vehicle['extras'] ?? [];

                                    $providerVehicles->push((object) [
                                        'id' => 'locauto_' . $vehicle['id'], // Prefix to ensure uniqueness and stability
                                        'source' => 'locauto_rent',
                                        'brand' => $brandName,
                                        'model' => $vehicle['model'] ?? 'Locauto Vehicle',
                                        'image' => $vehicle['image'] ?? '/images/default-car.jpg',
                                        'price_per_day' => (float) ($totalPrice / $rentalDays),
                                        'total_price' => (float) $totalPrice,
                                        'price_per_week' => null,
                                        'price_per_month' => null,
                                        'currency' => $vehicle['currency'] ?? 'EUR',
                                        'transmission' => $transmission,
                                        'fuel' => $fuel,
                                        'category' => $assignedCategory, // Use the verified category
                                        'seating_capacity' => isset($vehicle['seating_capacity']) ? (int) $vehicle['seating_capacity'] : null,
                                        'mileage' => $vehicle['mileage'] ?? null, // Only show if provided by API
                                        'features' => $features, // Add mapped features
                                        'latitude' => (float) $entryLat,
                                        'longitude' => (float) $entryLng,
                                        'full_vehicle_address' => $currentProviderLocationName,
                                        'provider_pickup_id' => $currentProviderLocationId,
                                        'provider_return_id' => $dropoffIdForProvider,
                                        'sipp_code' => $sippCode,
                                        'benefits' => [
                                            'minimum_driver_age' => (int) ($validated['age'] ?? 21),
                                            'pay_on_arrival' => true,
                                            'luggage' => $vehicle['luggage'] ?? 0,
                                        ],
                                        // Map luggage directly for the card
                                        'luggage' => $vehicle['luggage'] ?? 0,
                                        // Pass all extras for LocautoRent (protection plans + optional extras)
                                        'extras' => $allExtras,
                                        // review_count and average_rating omitted - not provided by API
                                        'products' => [
                                            [
                                                'type' => 'POA',
                                                'total' => (string) ($totalPrice / $rentalDays),
                                                'currency' => $vehicle['currency'] ?? 'EUR',
                                                'deposit' => (string) ($vehicle['deposit_amount'] ?? 0),
                                                'payment_type' => 'POA',
                                            ]
                                        ],
                                        'options' => [],
                                        'insurance_options' => [],
                                        'availability' => true,
                                    ]);
                                }
                            } else {
                                Log::warning("LocautoRent API response for vehicles was empty or malformed for location ID: " . $currentProviderLocationId, ['response' => $locautoResponse]);
                            }
                        }
                    } catch (\Exception $e) {
                        $recordProviderError($providerToFetch, $e->getMessage());
                        Log::error("Error fetching LocautoRent vehicles: " . $e->getMessage(), [
                            'provider_location_id' => $currentProviderLocationId,
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } // Close LocautoRent elseif
                elseif ($providerToFetch === 'renteon') {
                    try {
                        $allowedProviders = config('services.renteon.allowed_providers');

                        Log::info('Attempting to fetch Renteon vehicles for location ID: ' . $currentProviderLocationId, [
                            'pickup_id' => $currentProviderLocationId,
                            'date_from' => $validated['date_from'],
                            'start_time' => $startTimeForProvider,
                            'date_to' => $validated['date_to'],
                            'end_time' => $endTimeForProvider,
                            'mode' => !empty($allowedProviders) ? 'multi-provider' : 'single-provider',
                        ]);

                        if (!empty($allowedProviders)) {
                            // Multi-provider: search all allowlisted providers simultaneously
                            $renteonVehicles = $this->renteonService->getTransformedVehiclesFromAllProviders(
                                $currentProviderLocationId,
                                $dropoffIdForProvider,
                                $validated['date_from'],
                                $startTimeForProvider,
                                $validated['date_to'],
                                $endTimeForProvider,
                                [
                                    'driver_age' => $validated['age'] ?? 35,
                                    'currency' => 'EUR',
                                    'prepaid' => false,
                                    'include_on_request' => true,
                                ],
                                [],
                                $entryLat,
                                $entryLng,
                                $currentProviderLocationName,
                                $rentalDays
                            );

                            Log::info('Renteon vehicles processed from all providers: ' . count($renteonVehicles));
                        } else {
                            // Single-provider fallback (no allowlist configured)
                            $renteonVehicles = $this->renteonService->getTransformedVehicles(
                                $currentProviderLocationId,
                                $dropoffIdForProvider,
                                $validated['date_from'],
                                $startTimeForProvider,
                                $validated['date_to'],
                                $endTimeForProvider,
                                [
                                    'driver_age' => $validated['age'] ?? 35,
                                    'currency' => 'EUR',
                                    'prepaid' => false,
                                    'include_on_request' => true,
                                ],
                                $entryLat,
                                $entryLng,
                                $currentProviderLocationName,
                                $rentalDays
                            );

                            Log::info('Renteon vehicles processed from default provider: ' . count($renteonVehicles));
                        }

                        foreach ($renteonVehicles as $renteonVehicle) {
                            $providerVehicles->push((object) $renteonVehicle);
                        }

                        Log::info('Renteon vehicles added to collection: ' . count($renteonVehicles));

                    } catch (\Exception $e) {
                        $recordProviderError($providerToFetch, $e->getMessage());
                        Log::error("Error fetching Renteon vehicles: " . $e->getMessage(), [
                            'provider_location_id' => $currentProviderLocationId,
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } // Close Renteon elseif
                elseif ($providerToFetch === 'surprice') {
                    try {
                        Log::info('Attempting to fetch Surprice vehicles for location', [
                            'pickup_id' => $currentProviderLocationId,
                            'date_from' => $validated['date_from'],
                            'date_to' => $validated['date_to'],
                        ]);

                        $formatDateTime = static function (string $date, string $time): string {
                            $time = trim($time);
                            if ($time === '') {
                                $time = '09:00';
                            }
                            if (strlen($time) === 5) {
                                $time .= ':00';
                            }
                            return $date . 'T' . $time;
                        };

                        $pickupDateTime = $formatDateTime($validated['date_from'], $startTimeForProvider);
                        $dropoffDateTime = $formatDateTime($validated['date_to'], $endTimeForProvider);

                        // Surprice uses locationCode + extendedLocationCode
                        // The provider_location_id stores locationCode, extended code stored separately in unified_locations
                        $pickupExtCode = $providerEntry['extended_location_code'] ?? $currentProviderLocationId;
                        $dropoffExtCode = $providerEntry['extended_dropoff_code'] ?? $pickupExtCode;

                        $surpriceVehicles = $this->surpriceService->getTransformedVehicles(
                            $currentProviderLocationId,
                            $pickupExtCode,
                            $dropoffIdForProvider ?? $currentProviderLocationId,
                            $dropoffExtCode,
                            $pickupDateTime,
                            $dropoffDateTime,
                            [
                                'driver_age' => $validated['age'] ?? 35,
                            ],
                            $entryLat,
                            $entryLng,
                            $currentProviderLocationName,
                            $rentalDays
                        );

                        foreach ($surpriceVehicles as $surpriceVehicle) {
                            $providerVehicles->push((object) $surpriceVehicle);
                        }

                        Log::info('Surprice vehicles added to collection: ' . count($surpriceVehicles));

                    } catch (\Exception $e) {
                        $recordProviderError($providerToFetch, $e->getMessage());
                        Log::error("Error fetching Surprice vehicles: " . $e->getMessage(), [
                            'provider_location_id' => $currentProviderLocationId,
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } // Close Surprice elseif

                $providerTimings[$providerToFetch][] = (int) round((microtime(true) - $providerStart) * 1000);
            } // Close foreach $allProviderEntries
        }

        $filteredProviderVehicles = $providerVehicles->filter(function ($vehicle) use ($validated) {
            // Convert to array if it's an object (for consistency)
            $v = is_array($vehicle) ? $vehicle : (array) $vehicle;

            if (!empty($validated['seating_capacity']) && ($v['seating_capacity'] ?? null) != $validated['seating_capacity'])
                return false;
            if (!empty($validated['brand']) && strcasecmp($v['brand'] ?? '', $validated['brand']) != 0)
                return false;
            if (!empty($validated['transmission']) && strcasecmp($v['transmission'] ?? '', $validated['transmission']) != 0)
                return false;
            if (!empty($validated['fuel']) && strcasecmp($v['fuel'] ?? '', $validated['fuel']) != 0)
                return false;
            if (!empty($validated['category_id'])) {
                if (is_numeric($validated['category_id'])) {
                    return false; // Metric ID usually implies internal category
                } else {
                    if (strcasecmp($v['category'] ?? '', $validated['category_id']) != 0) {
                        return false;
                    }
                }
            }
            // Note: price_range filtering removed from backend - handled on frontend with currency conversion
            // if (!empty($validated['price_range'])) {
            //     $range = explode('-', $validated['price_range']);
            //     $price = (float) $v['price_per_day'];
            //     if ($price < (float) $range[0] || $price > (float) $range[1])
            //         return false;
            // }
            if (!empty($validated['mileage'])) {
                if (($v['mileage'] ?? null) === null || ($v['mileage'] ?? '') === 'Unknown')
                    return true;
                $range = explode('-', $validated['mileage']);
                $mileageValue = (int) ($v['mileage'] ?? 0);
                if ($mileageValue < (int) $range[0] || $mileageValue > (int) $range[1])
                    return false;
            }
            return true;
        });

        // Filter OK Mobility vehicles separately
        $filteredOkMobilityVehicles = $okMobilityVehicles->filter(function ($vehicle) use ($validated) {
            if (!empty($validated['seating_capacity']) && $vehicle->seating_capacity != $validated['seating_capacity'])
                return false;
            if (!empty($validated['brand']) && strcasecmp($vehicle->brand, $validated['brand']) != 0)
                return false;
            if (!empty($validated['transmission']) && strcasecmp($vehicle->transmission, $validated['transmission']) != 0)
                return false;
            if (!empty($validated['fuel']) && strcasecmp($vehicle->fuel, $validated['fuel']) != 0)
                return false;
            if (!empty($validated['category_id'])) {
                if (is_numeric($validated['category_id'])) {
                    return false; // Metric ID usually implies internal category
                } else {
                    if (strcasecmp($vehicle->category ?? '', $validated['category_id']) != 0) {
                        return false;
                    }
                }
            }
            // Note: price_range filtering removed from backend - handled on frontend with currency conversion
            // if (!empty($validated['price_range'])) {
            //     $range = explode('-', $validated['price_range']);
            //     $price = (float) $vehicle->price_per_day;
            //     if ($price < (float) $range[0] || $price > (float) $range[1])
            //         return false;
            // }
            if (!empty($validated['mileage'])) {
                if ($vehicle->mileage === null || $vehicle->mileage === 'Unknown')
                    return true;
                $range = explode('-', $validated['mileage']);
                $mileageValue = (int) $vehicle->mileage;
                if ($mileageValue < (int) $range[0] || $mileageValue > (int) $range[1])
                    return false;
            }
            return true;
        });

        // Skip internal vehicles for one-way rentals (internal doesn't support different dropoff)
        $internalForMerge = $isOneWay ? collect() : $internalVehiclesCollection;
        $combinedVehicles = $internalForMerge->merge($filteredProviderVehicles)->merge($filteredOkMobilityVehicles);
        $perPage = 500;
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

        $categoriesFromOptions = [];
        if ($internalVehiclesCollection->isNotEmpty()) {
            $categoryIds = $internalVehiclesCollection->pluck('category_id')->unique()->filter();
            if ($categoryIds->isNotEmpty()) {
                $categoriesFromOptions = VehicleCategory::whereIn('id', $categoryIds)->select('id', 'name')->get()->toArray();
            }
        }

        // Add Provider Categories to Options
        $providerCategories = $providerVehicles->pluck('category')->merge($okMobilityVehicles->pluck('category'))
            ->unique()
            ->filter()
            ->map(function ($cat) {
                // Defensive check: ensure $cat is a string before calling ucfirst
                $catStr = is_string($cat) ? $cat : (is_array($cat) ? ($cat['name'] ?? 'Unknown') : (string) $cat);
                return ['id' => $cat, 'name' => ucfirst($catStr)];
            })
            ->values()
            ->all();

        $categoriesFromOptions = array_merge($categoriesFromOptions, $providerCategories);
        // Ensure unique
        $categoriesFromOptions = collect($categoriesFromOptions)->unique('id')->values()->all();

        $seo = app(SeoMetaResolver::class)->resolveForRoute(
            'search',
            [],
            App::getLocale(),
            route('search', ['locale' => App::getLocale()]),
            'noindex,follow'
        )->toArray();

        // Create paginated OK Mobility vehicles for the frontend
        $okMobilityVehiclesPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $filteredOkMobilityVehicles->values(),
            $filteredOkMobilityVehicles->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Create paginated Renteon vehicles for the frontend
        $renteonVehiclesCollection = $providerVehicles->filter(function ($vehicle) {
            return ($vehicle->source ?? '') === 'renteon';
        });
        $renteonVehiclesPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $renteonVehiclesCollection->values(),
            $renteonVehiclesCollection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $providerNames = collect($allProviderEntries ?? [])->pluck('provider')->filter()->unique()->values();

        // SECURITY: Store original prices for verification during checkout
        $searchSessionId = 'search_' . session()->getId() . '_' . now()->timestamp;
        $allVehicles = [];

        // Process internal vehicles
        $vehiclesCollection = $vehicles instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $vehicles->getCollection()
            : collect($vehicles);
        foreach ($vehiclesCollection as $vehicle) {
            $vehicleArray = is_array($vehicle) ? $vehicle : (method_exists($vehicle, 'toArray') ? $vehicle->toArray() : (array) $vehicle);
            $vehicleArray['source'] = 'internal';
            $allVehicles[] = $vehicleArray;
        }

        // Process provider vehicles
        foreach ($providerVehicles as $vehicle) {
            $vehicleArray = is_array($vehicle) ? $vehicle : (method_exists($vehicle, 'toArray') ? $vehicle->toArray() : (array) $vehicle);
            $allVehicles[] = $vehicleArray;
        }

        // Process OK Mobility vehicles
        foreach ($okMobilityVehicles as $vehicle) {
            $vehicleArray = is_array($vehicle) ? $vehicle : (method_exists($vehicle, 'toArray') ? $vehicle->toArray() : (array) $vehicle);
            $allVehicles[] = $vehicleArray;
        }

        // Store original prices and get price hashes
        $priceMap = $this->priceVerificationService->storeOriginalPrices($searchSessionId, $allVehicles);

        // Attach price_hash to vehicles
        $vehicles = $vehicles instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? new \Illuminate\Pagination\LengthAwarePaginator(
                $vehicles->getCollection()->map(function ($vehicle) use ($priceMap) {
                    if (is_array($vehicle)) {
                        $vid = $vehicle['id'] ?? null;
                    } else {
                        $vid = $vehicle->id ?? null;
                    }
                    if ($vid && isset($priceMap[$vid])) {
                        if (is_array($vehicle)) {
                            $vehicle['price_hash'] = $priceMap[$vid]['price_hash'];
                        } else {
                            $vehicle->price_hash = $priceMap[$vid]['price_hash'];
                        }
                    }
                    return $vehicle;
                }),
                $vehicles->total(),
                $vehicles->perPage(),
                $vehicles->currentPage(),
                ['path' => $vehicles->path()]
            )
            : $vehicles;

        $providerStatus = $providerNames->map(function ($provider) use ($providerVehicles, $okMobilityVehicles, $providerErrors, $providerTimings) {
            $providerKey = (string) $provider;
            $count = $providerKey === 'okmobility'
                ? $okMobilityVehicles->count()
                : $providerVehicles->filter(function ($vehicle) use ($providerKey) {
                    $source = is_array($vehicle) ? ($vehicle['source'] ?? null) : ($vehicle->source ?? null);
                    return $source === $providerKey;
                })->count();

            $errors = $providerErrors[$providerKey] ?? [];
            $timings = $providerTimings[$providerKey] ?? [];

            return [
                'provider' => $providerKey,
                'status' => empty($errors) ? 'ok' : 'error',
                'vehicles' => $count,
                'ms' => empty($timings) ? null : (int) round(array_sum($timings) / count($timings)),
                'errors' => array_values($errors),
            ];
        })->values()->all();

        return Inertia::render('SearchResults', [
            'vehicles' => $vehicles,
            'okMobilityVehicles' => $okMobilityVehiclesPaginated,
            'renteonVehicles' => $renteonVehiclesPaginated,
            'providerStatus' => $providerStatus,
            'searchError' => null,
            'filters' => $validated,
            'pagination_links' => $vehicles->links('pagination::tailwind')->toHtml(),
            'brands' => $combinedBrands,
            'colors' => $colors,
            'seatingCapacities' => $combinedSeatingCapacities,
            'transmissions' => $combinedTransmissions,
            'fuels' => $combinedFuels,
            'mileages' => $mileages,
            'categories' => $categoriesFromOptions,
            'schema' => $vehicleListSchema,
            'seo' => $seo,
            'locale' => App::getLocale(),
            'optionalExtras' => array_values($searchOptionalExtras ?? []), // Pass extras
            'locationName' => $validated['location_name'] ?? 'Selected Location', // Pass location name
            'search_session_id' => $searchSessionId, // For price verification
            'price_map' => $priceMap, // Price hashes for all vehicles
        ]);
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

    private function normalizeFavricaCurrency(?string $currency): string
    {
        $value = strtoupper(trim((string) $currency));
        if ($value === '') {
            return 'EUR';
        }
        if ($value === 'EURO') {
            return 'EUR';
        }
        if ($value === 'TL') {
            return 'TRY';
        }
        return $value;
    }

    private function normalizeXDriveCurrency(?string $currency): string
    {
        return $this->normalizeFavricaCurrency($currency);
    }

    private function toFavricaRequestCurrency(?string $currency): string
    {
        $value = strtoupper(trim((string) $currency));
        if ($value === '') {
            return 'EURO';
        }
        if ($value === 'EUR') {
            return 'EURO';
        }
        if ($value === 'TRY') {
            return 'TL';
        }
        return $value;
    }

    private function toXDriveRequestCurrency(?string $currency): string
    {
        return $this->toFavricaRequestCurrency($currency);
    }

    private function parseFavricaNumber($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $hasComma = str_contains($raw, ',');
        $hasDot = str_contains($raw, '.');

        if ($hasComma && !$hasDot) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } elseif ($hasComma && $hasDot) {
            $lastComma = strrpos($raw, ',');
            $lastDot = strrpos($raw, '.');
            if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
                $raw = str_replace('.', '', $raw);
                $raw = str_replace(',', '.', $raw);
            } else {
                $raw = str_replace(',', '', $raw);
            }
        }

        $raw = preg_replace('/[^0-9.\-]/', '', $raw);
        if ($raw === '' || $raw === '-' || $raw === '.') {
            return null;
        }

        return is_numeric($raw) ? (float) $raw : null;
    }

    private function buildFavricaLocationMap($locations): array
    {
        if (!is_array($locations)) {
            return [];
        }

        $map = [];
        foreach ($locations as $location) {
            if (!is_array($location)) {
                continue;
            }

            $locationId = (string) ($location['location_id'] ?? $location['locationID'] ?? $location['id'] ?? '');
            if ($locationId === '') {
                continue;
            }

            $map[$locationId] = $this->formatFavricaLocationDetails($location);
        }

        return $map;
    }

    private function buildXDriveLocationMap($locations): array
    {
        if (!is_array($locations)) {
            return [];
        }

        $map = [];
        foreach ($locations as $location) {
            if (!is_array($location)) {
                continue;
            }

            $locationId = (string) ($location['location_id'] ?? $location['locationID'] ?? $location['id'] ?? '');
            if ($locationId === '') {
                continue;
            }

            $map[$locationId] = $this->formatFavricaLocationDetails($location);
        }

        return $map;
    }

    private function buildSicilyByCarLocationMap($locations): array
    {
        if (!is_array($locations)) {
            return [];
        }

        $map = [];
        foreach ($locations as $location) {
            if (!is_array($location)) {
                continue;
            }

            $locationId = (string) ($location['id'] ?? '');
            if ($locationId === '') {
                continue;
            }

            $map[$locationId] = $this->formatSicilyByCarLocationDetails($location);
        }

        return $map;
    }

    private function formatSicilyByCarLocationDetails(array $location): array
    {
        $address = $location['address'] ?? [];
        $coords = $location['coordinates'] ?? [];

        return [
            'id' => (string) ($location['id'] ?? ''),
            'name' => (string) ($location['name'] ?? ''),
            'address_1' => is_array($address) ? ($address['addressLineOne'] ?? null) : null,
            'address_2' => is_array($address) ? ($address['addressLineTwo'] ?? null) : null,
            'address_3' => null,
            'address_city' => is_array($address) ? ($address['city'] ?? null) : null,
            'address_county' => is_array($address) ? ($address['province'] ?? $address['region'] ?? null) : null,
            'address_postcode' => is_array($address) ? ($address['postalCode'] ?? null) : null,
            'address_country' => is_array($address) ? ($address['country'] ?? null) : null,
            'telephone' => $location['phone'] ?? $location['mobile'] ?? null,
            'email' => $location['email'] ?? null,
            'iata' => $location['airportCode'] ?? null,
            'latitude' => is_array($coords) ? (string) ($coords['latitude'] ?? '') : null,
            'longitude' => is_array($coords) ? (string) ($coords['longitude'] ?? '') : null,
            'collection_details' => null,
        ];
    }

    private function formatFavricaLocationDetails(array $location): array
    {
        $locationId = (string) ($location['location_id'] ?? $location['locationID'] ?? $location['id'] ?? '');
        $name = $this->pickFavricaValue($location, ['location_name', 'name', 'location', 'title']);
        $addressRaw = $this->pickFavricaValue($location, ['address', 'address_1', 'address1', 'addressline']);
        $addressLines = $this->splitFavricaAddress($addressRaw);

        $lat = $this->parseFavricaNumber($this->pickFavricaValue($location, ['latitude', 'lat']));
        $lng = $this->parseFavricaNumber($this->pickFavricaValue($location, ['longitude', 'lng', 'lon']));
        if ($lat === null || $lng === null) {
            [$parsedLat, $parsedLng] = $this->parseFavricaMapsPoint($this->pickFavricaValue($location, ['maps_point', 'map_point']));
            $lat = $lat ?? $parsedLat;
            $lng = $lng ?? $parsedLng;
        }

        return [
            'id' => $locationId,
            'name' => $name,
            'address_1' => $addressLines[0] ?? null,
            'address_2' => $addressLines[1] ?? null,
            'address_3' => $addressLines[2] ?? null,
            'address_city' => $this->pickFavricaValue($location, ['city', 'town', 'address_city']),
            'address_county' => $this->pickFavricaValue($location, ['state', 'province', 'region', 'address_county']),
            'address_postcode' => $this->pickFavricaValue($location, ['postcode', 'postal_code', 'zip', 'zipcode']),
            'telephone' => $this->pickFavricaValue($location, ['telephone', 'phone', 'tel', 'gsm', 'mobile', 'phone1', 'phone2']),
            'fax' => $this->pickFavricaValue($location, ['fax']),
            'email' => $this->pickFavricaValue($location, ['email', 'mail', 'mail_adress', 'mail_address']),
            'whatsapp' => $this->pickFavricaValue($location, ['whatsapp', 'whatsapp_phone', 'whatsapp_number', 'wa_phone']),
            'latitude' => $lat !== null ? (string) $lat : null,
            'longitude' => $lng !== null ? (string) $lng : null,
            'iata' => $this->pickFavricaValue($location, ['iata', 'airport_code']),
            'collection_details' => $this->pickFavricaValue($location, ['collection_details', 'collectiondetails', 'instructions', 'pickup_instructions']),
        ];
    }

    private function pickFavricaValue(array $location, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $location)) {
                continue;
            }
            $value = trim((string) $location[$key]);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function splitFavricaAddress(?string $address): array
    {
        $address = trim((string) $address);
        if ($address === '') {
            return [];
        }

        $address = str_replace(["\r\n", "\r"], "\n", $address);
        $parts = preg_split('/\n|,/', $address);
        if (!is_array($parts)) {
            return [$address];
        }

        $lines = array_values(array_filter(array_map('trim', $parts), static fn($part) => $part !== ''));
        return array_slice($lines, 0, 3);
    }

    private function parseFavricaMapsPoint(?string $mapsPoint): array
    {
        $mapsPoint = trim((string) $mapsPoint);
        if ($mapsPoint === '') {
            return [null, null];
        }

        // DMS format (e.g. "25°15'16\"N 55°21'23\"E")
        $dmsPattern = '/(\d+(?:\.\d+)?)\s*[°º]\s*(\d+(?:\.\d+)?)\s*[\'’]\s*(\d+(?:\.\d+)?)\s*(?:\"|″)?\s*([NSEW])/iu';
        if (preg_match_all($dmsPattern, $mapsPoint, $dmsMatches, PREG_SET_ORDER) && count($dmsMatches) >= 2) {
            $coords = [];
            foreach ($dmsMatches as $m) {
                $deg = (float) $m[1];
                $min = (float) $m[2];
                $sec = (float) $m[3];
                $hem = strtoupper((string) $m[4]);
                $decimal = $deg + ($min / 60.0) + ($sec / 3600.0);
                if ($hem === 'S' || $hem === 'W') {
                    $decimal *= -1;
                }
                $coords[$hem] = $decimal;
            }

            $lat = $coords['N'] ?? $coords['S'] ?? null;
            $lng = $coords['E'] ?? $coords['W'] ?? null;
            if ($lat !== null && $lng !== null) {
                return [$lat, $lng];
            }
        }

        preg_match_all('/-?\d+(?:\.\d+)?/', $mapsPoint, $matches);
        $numbers = $matches[0] ?? [];
        if (count($numbers) >= 2) {
            $a = (float) $numbers[count($numbers) - 2];
            $b = (float) $numbers[count($numbers) - 1];

            if (stripos($mapsPoint, 'point') !== false) {
                return [$b, $a];
            }

            if (abs($a) > 90 && abs($b) <= 90) {
                return [$b, $a];
            }

            return [$a, $b];
        }

        return [null, null];
    }

    private function resolveFavricaImageUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $base = trim((string) config('services.favrica.image_base_url', ''));
        if ($base === '') {
            return null;
        }

        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    private function resolveXDriveImageUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $base = trim((string) config('services.xdrive.image_base_url', ''));
        if ($base === '') {
            $base = trim((string) config('services.xdrive.base_url', ''));
        }
        if ($base === '') {
            return null;
        }

        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    private function normalizeFavricaTransmission(?string $value, ?string $fallback): string
    {
        $val = strtolower(trim((string) $value));
        if ($val === '') {
            return $fallback ?: 'manual';
        }
        if (str_contains($val, 'auto') || str_contains($val, 'otomat')) {
            return 'automatic';
        }
        if (str_contains($val, 'man')) {
            return 'manual';
        }
        return $fallback ?: $val;
    }

    private function normalizeFavricaFuel(?string $value, ?string $fallback): string
    {
        $val = strtolower(trim((string) $value));
        if ($val === '') {
            return $fallback ?: 'petrol';
        }
        if (str_contains($val, 'diesel') || str_contains($val, 'dizel')) {
            return 'diesel';
        }
        if (str_contains($val, 'hybrid')) {
            return 'hybrid';
        }
        if (str_contains($val, 'electric') || str_contains($val, 'elektrik')) {
            return 'electric';
        }
        if (str_contains($val, 'petrol') || str_contains($val, 'benzin') || str_contains($val, 'gasoline')) {
            return 'petrol';
        }
        return $fallback ?: $val;
    }

    private function isFavricaInsuranceService(?string $code, ?string $title, ?string $description): bool
    {
        $codeValue = strtoupper(trim((string) $code));
        if (in_array($codeValue, ['CDW', 'SCDW', 'LCF', 'PAI'], true)) {
            return true;
        }

        $text = strtolower(trim((string) ($title . ' ' . $description)));
        if ($text === '') {
            return false;
        }

        $keywords = ['insurance', 'damage', 'waiver', 'glass', 'tire', 'tyre', 'headlight', 'fuse'];
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse SIPP/ACRISS code to get vehicle details
     */
    private function parseSippCode($sipp, $provider = null)
    {
        $sipp = strtoupper($sipp ?? '');
        $data = [
            'transmission' => 'manual',
            'fuel' => 'petrol',
            'seating_capacity' => 5,
            'doors' => 4,
            'category' => 'Unknown',
            'air_conditioning' => false,
            'dynamic_name' => 'Vehicle'
        ];

        if (strlen($sipp) < 4) {
            return $data;
        }

        $sippJsonPath = base_path('database/sipp_codes.json');
        if (!file_exists($sippJsonPath)) {
            Log::error("SIPP codes JSON not found at: {$sippJsonPath}");
            return $data;
        }

        $sippCodes = json_decode(file_get_contents($sippJsonPath), true);

        // 1. Category (1st char)
        $char1 = $sipp[0];
        if (isset($sippCodes['category'][$char1])) {
            $catData = $sippCodes['category'][$char1];
            $data['category'] = is_array($catData) ? ($catData['name'] ?? 'Unknown') : $catData;

            // Default seat counts based on ACRISS category
            $standardSeats = [
                'M' => 4,
                'N' => 4, // Mini
                'E' => 5,
                'H' => 5, // Economy
                'C' => 5,
                'D' => 5, // Compact
                'I' => 5,
                'J' => 5, // Intermediate
                'S' => 5,
                'R' => 5, // Standard
                'F' => 5,
                'G' => 5, // Fullsize
                'P' => 5,
                'U' => 5, // Premium
                'L' => 5,
                'W' => 5, // Luxury
            ];
            $data['seating_capacity'] = is_array($catData) ? ($catData['seats'] ?? 5) : ($standardSeats[$char1] ?? 5);
        }

        // 2. Type (2nd char)
        $char2 = $sipp[1];
        if (isset($sippCodes['type'][$char2])) {
            $typeData = $sippCodes['type'][$char2];
            $data['type_name'] = is_array($typeData) ? ($typeData['name'] ?? 'Vehicle') : $typeData;

            // Default door counts based on ACRISS type
            $standardDoors = [
                'T' => 2, // Convertible
                'E' => 2, // Coupe
                'S' => 2, // Sport
                'M' => 5, // Monospace
                'V' => 5, // Passenger Van
                'F' => 5, // SUV
                'G' => 5, // Crossover
                'W' => 5, // Wagon/Estate
                'K' => 2, // Commercial Van/Truck
            ];
            $data['doors'] = is_array($typeData) ? ($typeData['doors'] ?? 4) : ($standardDoors[$char2] ?? 4);

            // Map doors from numeric string if it's there (e.g. "2-3 Door" -> 3)
            if (!is_array($typeData) && is_string($typeData)) {
                if (preg_match('/(\d+)-(\d+)/', $typeData, $matches)) {
                    $data['doors'] = (int) $matches[2]; // Use upper bound
                } elseif (preg_match('/(\d+)/', $typeData, $matches)) {
                    $data['doors'] = (int) $matches[1];
                }
            }
        }

        // 2b. Special Seating Overrides (Standard ACRISS Van Pseudo-codes & others)
        $prefix = substr($sipp, 0, 2);
        $vanSeatingMap = [
            'IV' => 6,
            'JV' => 6, // 6 Seats
            'SV' => 7,
            'RV' => 7,
            'FV' => 7,
            'GV' => 7, // 7 Seats
            'PV' => 8,
            'UV' => 8, // 8 Seats
            'LV' => 9,
            'WV' => 9,
            'XV' => 9,
            'OV' => 9, // 9 Seats +
        ];

        if (isset($sippCodes['van_seating'][$prefix])) {
            $data['seating_capacity'] = $sippCodes['van_seating'][$prefix];
        } elseif (isset($vanSeatingMap[$prefix])) {
            $data['seating_capacity'] = $vanSeatingMap[$prefix];
        }

        // 3. Transmission (3rd char)
        $char3 = $sipp[2];
        if (isset($sippCodes['transmission'][$char3])) {
            $transDesc = is_array($sippCodes['transmission'][$char3]) ? ($sippCodes['transmission'][$char3]['name'] ?? '') : $sippCodes['transmission'][$char3];
            $data['transmission'] = str_contains(strtolower($transDesc), 'automatic') ? 'automatic' : 'manual';
            $data['transmission_description'] = $transDesc;
        }

        // 4. Fuel & AC (4th char)
        $char4 = $sipp[3];

        // Provider-specific overrides
        if ($provider === 'okmobility') {
            if ($char4 === 'S') {
                $data['fuel'] = 'petrol';
                $data['air_conditioning'] = false;
            }

            // Seating Overrides for OkMobility
            if ($prefix === 'VM' || $prefix === 'SM' || $prefix === 'FM') {
                // User confirmed "some have 5 seats and it says 7", so we default all these Monospaces to 5.
                $data['seating_capacity'] = 5;
            }

            // Handle case where T is used as category (Convertible)
            if ($char1 === 'T') {
                $data['category'] = 'Convertible';
                // Specific for TSMS (Fiat 500 Cabrio) - OkMobility says 4 seats / 3 doors
                if ($sipp === 'TSMS') {
                    $data['seating_capacity'] = 4;
                    $data['doors'] = 3;
                } else {
                    $data['seating_capacity'] = 2;
                    $data['doors'] = 2;
                }
            }

            // Door Overrides for OkMobility (if not already handled by TSMS)
            if ($char1 !== 'T') {
                if ($char2 === 'S') {
                    // MSMS (Mini Sport) -> 3 doors, others (like SSAS Standard Sport) -> 4 doors
                    // User confirmed: count only side doors. SSAS (SUV/Sport) -> 4.
                    $data['doors'] = ($char1 === 'M' || $char1 === 'N') ? 3 : 4;
                } elseif (in_array($char2, ['M', 'V', 'F', 'G', 'W', 'D'])) {
                    // Monospace, Van, SUV, Crossover, Wagon, 4-5 Door types -> 4 doors (side doors)
                    $data['doors'] = 4;
                }
            }
        } else if (isset($sippCodes['fuel_ac'][$char4])) {
            $fuelAC = $sippCodes['fuel_ac'][$char4];
            $data['fuel'] = strtolower($fuelAC['fuel'] ?? '');
            $data['air_conditioning'] = $fuelAC['ac'] ?? false;
        }

        // Generate a dynamic name like "Mini 2-3 Door"
        $nameParts = [];
        if (isset($data['category']) && $data['category'] !== 'Unknown')
            $nameParts[] = $data['category'];
        if (isset($data['type_name']))
            $nameParts[] = $data['type_name'];

        $data['dynamic_name'] = !empty($nameParts) ? implode(' ', $nameParts) : 'Standard Vehicle';

        return $data;
    }

    private function parseOptionalExtras($xmlObject)
    {
        $optionalExtras = [];
        if (isset($xmlObject->response->optionalextras->extra)) {
            foreach ($xmlObject->response->optionalextras->extra as $extra) {
                $required = (string) $extra['required'];
                $numberAllowed = (string) $extra['numberAllowed'];

                if (isset($extra->options->option)) {
                    foreach ($extra->options->option as $option) {
                        $optionId = (string) $option->optionID;
                        if ($optionId === '') {
                            continue;
                        }

                        $maxQty = $this->resolveOptionalExtraMaxQuantity(
                            (string) $option->Choices,
                            $numberAllowed
                        );

                        $optionalExtras[] = [
                            'id' => 'gm_optional_' . $optionId,
                            'option_id' => $optionId,
                            'optionID' => $optionId,
                            'name' => (string) ($option->Name ?: $extra->Name),
                            'description' => (string) ($option->Description ?: $extra->Description),
                            'required' => $required,
                            'numberAllowed' => $maxQty,
                            'prepay_available' => (string) $option->Prepay_available,
                            'daily_rate' => (string) $option->Daily_rate,
                            'daily_rate_currency' => (string) $option->Daily_rate['currency'],
                            'total_for_booking' => (string) $option->Total_for_this_booking,
                            'total_for_booking_currency' => (string) $option->Total_for_this_booking['currency'],
                            'code' => (string) $option->code,
                            'type' => 'optional',
                        ];
                    }
                    continue;
                }

                $optionId = (string) $extra->optionID;
                if ($optionId === '') {
                    continue;
                }

                $maxQty = $this->resolveOptionalExtraMaxQuantity(
                    (string) $extra->Choices,
                    $numberAllowed
                );

                $optionalExtras[] = [
                    'id' => 'gm_optional_' . $optionId,
                    'option_id' => $optionId,
                    'optionID' => $optionId,
                    'name' => (string) $extra->Name,
                    'description' => (string) $extra->Description,
                    'required' => $required,
                    'numberAllowed' => $maxQty,
                    'prepay_available' => (string) $extra->Prepay_available,
                    'daily_rate' => (string) $extra->Daily_rate,
                    'daily_rate_currency' => (string) $extra->Daily_rate['currency'],
                    'total_for_booking' => (string) $extra->Total_for_this_booking,
                    'total_for_booking_currency' => (string) $extra->Total_for_this_booking['currency'],
                    'code' => (string) $extra->code,
                    'type' => 'optional',
                ];
            }
        }

        return $optionalExtras;
    }

    private function resolveOptionalExtraMaxQuantity(string $choices, string $numberAllowed): ?int
    {
        $numberAllowed = trim($numberAllowed);
        if ($numberAllowed !== '') {
            $value = (int) $numberAllowed;
            if ($value > 0) {
                return $value;
            }
        }

        $choices = trim($choices);
        if ($choices === '') {
            return null;
        }

        $parts = array_map('trim', explode(',', $choices));
        $numeric = [];
        foreach ($parts as $part) {
            if (is_numeric($part)) {
                $numeric[] = (int) $part;
            }
        }

        if (!empty($numeric)) {
            return max($numeric);
        }

        $upper = strtoupper(implode(',', $parts));
        if (str_contains($upper, 'YES')) {
            return 1;
        }

        return null;
    }
}
