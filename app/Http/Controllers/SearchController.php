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
use Illuminate\Support\Facades\Log; // Import Log facade

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

    public function __construct(
        GreenMotionService $greenMotionService,
        OkMobilityService $okMobilityService,
        LocationSearchService $locationSearchService,
        AdobeCarService $adobeCarService,
        WheelsysService $wheelsysService,
        LocautoRentService $locautoRentService,
        RenteonService $renteonService,
        FavricaService $favricaService,
        XDriveService $xdriveService
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
            $strictProviders = ['okmobility', 'greenmotion', 'usave', 'locauto_rent', 'wheelsys', 'adobe', 'favrica', 'xdrive'];
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
            'category_id' => 'nullable|string', // Allow both numeric IDs and string category names (for providers)
            'matched_field' => 'nullable|string|in:location,city,state,country',
            'provider' => 'nullable|string', // Replaces 'source'
            'provider_pickup_id' => 'nullable|string', // Replaces 'greenmotion_location_id'
            'unified_location_id' => 'nullable|integer', // Unified location ID for multi-provider search
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

        if (!$hasLocation) {
            Log::info('No location provided - returning empty vehicle results');
            $emptyVehicles = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                500,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return Inertia::render('SearchResults', [
                'vehicles' => $emptyVehicles,
                'okMobilityVehicles' => $emptyVehicles,
                'renteonVehicles' => $emptyVehicles,
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
                'seoMeta' => \App\Models\SeoMeta::with('translations')->where('url_slug', '/s')->first(),
                'locale' => \Illuminate\Support\Facades\App::getLocale(),
                'optionalExtras' => [],
                'locationName' => null,
            ]);
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
        $providerName = $validated['provider'] ?? null;
        $currentProviderLocationId = $validated['provider_pickup_id'] ?? null;
        $locationLat = $validated['latitude'] ?? null;
        $locationLng = $validated['longitude'] ?? null;
        $locationAddress = $validated['where'] ?? null;

        // Fallback: If no provider ID, try to resolve by provider + location name
        if (!$currentProviderLocationId && $providerName === 'renteon' && !empty($validated['where'])) {
            $allLocations = json_decode(file_get_contents(public_path('unified_locations.json')), true);
            $searchParam = trim((string) $validated['where']);
            $matchedLocation = collect($allLocations)->first(function ($loc) use ($searchParam) {
                $name = strtolower((string) ($loc['name'] ?? ''));
                $city = strtolower((string) ($loc['city'] ?? ''));
                $country = strtolower((string) ($loc['country'] ?? ''));
                $needle = strtolower($searchParam);
                return $needle !== '' && ($name === $needle || str_contains($name, $needle) || str_contains($city, $needle) || str_contains($country, $needle));
            });

            if ($matchedLocation && !empty($matchedLocation['providers'])) {
                $renteonEntry = collect($matchedLocation['providers'])->first(function ($provider) {
                    return ($provider['provider'] ?? null) === 'renteon';
                });

                if ($renteonEntry) {
                    $currentProviderLocationId = $renteonEntry['pickup_id'] ?? null;
                    $locationLat = $matchedLocation['latitude'] ?? $locationLat;
                    $locationLng = $matchedLocation['longitude'] ?? $locationLng;
                    $locationAddress = $matchedLocation['name'] ?? $locationAddress;
                }
            }
        }

        // Fallback: If no provider ID, try to find one from the 'where' text
        if (!$currentProviderLocationId && !empty($validated['where'])) {
            $searchParam = $validated['where'];
            $unifiedLocations = $this->locationSearchService->searchLocations($searchParam);

            $matchedLocation = collect($unifiedLocations)->first(function ($loc) {
                return isset($loc['providers']) && count($loc['providers']) > 0;
            });

            if ($matchedLocation && isset($matchedLocation['providers'][0])) {
                $providerName = $matchedLocation['providers'][0]['provider'];
                $currentProviderLocationId = $matchedLocation['providers'][0]['pickup_id'];
                $locationLat = $matchedLocation['latitude'];
                $locationLng = $matchedLocation['longitude'];
                $locationAddress = $matchedLocation['name'];
            }
        }

        if ($providerName && $providerName !== 'internal' && $currentProviderLocationId && !empty($validated['date_from']) && !empty($validated['date_to'])) {
            // allProviderEntries will store all provider locations to fetch from
            $allProviderEntries = [];

            // Load unified locations
            $allLocations = json_decode(file_get_contents(public_path('unified_locations.json')), true);

            // Find the unified location - prefer by unified_location_id, fallback to pickup_id
            $matchedLocation = null;
            $unifiedLocationId = $validated['unified_location_id'] ?? null;

            if ($unifiedLocationId) {
                // Find by unified_location_id (most reliable)
                $matchedLocation = collect($allLocations)->first(function ($location) use ($unifiedLocationId) {
                    return ($location['unified_location_id'] ?? null) == $unifiedLocationId;
                });
            }

            if (!$matchedLocation) {
                // Fallback: Find by any provider's pickup_id
                $matchedLocation = collect($allLocations)->first(function ($location) use ($currentProviderLocationId) {
                    if (isset($location['providers'])) {
                        foreach ($location['providers'] as $provider) {
                            if ($provider['pickup_id'] == $currentProviderLocationId) {
                                return true;
                            }
                        }
                    }
                    return false;
                });
            }

            if ($matchedLocation && !empty($matchedLocation['providers'])) {
                // Fetch from ALL providers only when explicitly mixed.
                // Otherwise, restrict to the requested provider.
                $providerEntriesSource = $matchedLocation['providers'];

                if ($providerName !== 'mixed') {
                    $providerEntriesSource = array_values(array_filter($providerEntriesSource, function ($provider) use ($providerName) {
                        return ($provider['provider'] ?? null) === $providerName;
                    }));

                    if (empty($providerEntriesSource)) {
                        $providerEntriesSource = [[
                            'provider' => $providerName,
                            'pickup_id' => $currentProviderLocationId,
                            'original_name' => $locationAddress,
                        ]];
                    }
                }

                $allProviderEntries = []; // Store all entries, not just one per provider

                foreach ($providerEntriesSource as $provider) {
                    $allProviderEntries[] = [
                        'provider' => $provider['provider'],
                        'pickup_id' => $provider['pickup_id'],
                        'original_name' => $provider['original_name'] ?? $matchedLocation['name'] ?? $locationAddress,
                    ];
                }

                // Update location info from unified location
                $locationLat = $matchedLocation['latitude'] ?? $locationLat;
                $locationLng = $matchedLocation['longitude'] ?? $locationLng;
                $locationAddress = $matchedLocation['name'] ?? $locationAddress;

                Log::info('Unified location matched', [
                    'unified_id' => $matchedLocation['unified_location_id'] ?? 'N/A',
                    'name' => $matchedLocation['name'] ?? 'Unknown',
                    'total_provider_entries' => count($allProviderEntries),
                ]);
            } elseif ($providerName !== 'mixed') {
                // Single provider fallback
                $allProviderEntries = [
                    [
                        'provider' => $providerName,
                        'pickup_id' => $currentProviderLocationId,
                        'original_name' => $locationAddress,
                    ]
                ];
            } else {
                $allProviderEntries = [];
            }

            Log::info('Provider entries to fetch: ' . count($allProviderEntries));

            $searchOptionalExtras = []; // Initialize logic for extras

            foreach ($allProviderEntries as $providerEntry) {
                // Get the provider name, location ID and original name for this entry
                $providerToFetch = $providerEntry['provider'];
                $currentProviderLocationId = $providerEntry['pickup_id'];
                $currentProviderLocationName = $providerEntry['original_name'];

                // Provider-safe times: avoid random empty/invalid times causing 0 results.
                $startTimeForProvider = $normalizeTime($validated['start_time'] ?? '', $providerDefaultTime($providerToFetch));
                $endTimeForProvider = $normalizeTime($validated['end_time'] ?? '', $providerDefaultTime($providerToFetch));
                // Most providers in this codebase operate on 30-minute increments.
                $startTimeForProvider = $normalizeTimeToStep($startTimeForProvider, 30);
                $endTimeForProvider = $normalizeTimeToStep($endTimeForProvider, 30);
                $startTimeForProvider = $clampTimeToBusinessHours($startTimeForProvider, $providerDefaultTime($providerToFetch), $providerToFetch);
                $endTimeForProvider = $clampTimeToBusinessHours($endTimeForProvider, $providerDefaultTime($providerToFetch), $providerToFetch);

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
                            'dropoff_location_id' => $validated['dropoff_location_id'] ?? null,
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
                                        'latitude' => (float) $locationLat,
                                        'longitude' => (float) $locationLng,
                                        'full_vehicle_address' => $currentProviderLocationName,
                                        'provider_pickup_id' => $currentProviderLocationId,
                                        'provider_return_id' => $validated['dropoff_location_id'] ?? $currentProviderLocationId,
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
                                    'latitude' => (float) $locationLat,
                                    'longitude' => (float) $locationLng,
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
                        Log::error("Error fetching Adobe vehicles: " . $e->getMessage(), [
                            'provider_location_id' => $currentProviderLocationId,
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } elseif ($providerToFetch === 'okmobility') {
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
                                    $groupFullName = $vehicleData['Group_Name'] ?? 'Unknown Vehicle';
                                    $groupId = $vehicleData['GroupID'] ?? 'unknown';

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
                                    $sippCode = $vehicleData['SIPP'] ?? null;
                                    $parsedSipp = $this->parseSippCode($sippCode, 'okmobility');

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

                                    // Extract brand and model with SIPP fallback
                                    $brandName = 'OK Mobility';
                                    $vehicleModel = $groupFullName;

                                    if ($vehicleModel === $sippCode || empty($vehicleModel)) {
                                        $vehicleModel = $parsedSipp['dynamic_name'] ?? $groupFullName;
                                    }

                                    $okMobilityVehicles->push([
                                        'id' => 'okmobility_' . $groupId . '_' . md5($token),
                                        'source' => 'okmobility',
                                        'brand' => $brandName,
                                        'model' => $vehicleModel,
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
                                        'latitude' => (float) $locationLat,
                                        'longitude' => (float) $locationLng,
                                        'full_vehicle_address' => $currentProviderLocationName,
                                        'provider_pickup_id' => $currentProviderLocationId,
                                        'station' => $vehicleData['Station'] ?? 'OK Mobility Station',
                                        'week_day_open' => $vehicleData['weekDayOpen'] ?? null,
                                        'week_day_close' => $vehicleData['weekDayClose'] ?? null,
                                        'preview_value' => isset($vehicleData['previewValue']) && is_numeric($vehicleData['previewValue']) ? (float) $vehicleData['previewValue'] : null,
                                        'total_day_value_with_tax' => isset($vehicleData['totalDayValueWithTax']) && is_numeric($vehicleData['totalDayValueWithTax']) ? (float) $vehicleData['totalDayValueWithTax'] : null,
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
                                        'extras_required' => !empty($vehicleData['extrasRequired']) ? explode(',', $vehicleData['extrasRequired']) : [],
                                        'extras_available' => !empty($vehicleData['extrasAvailable']) ? explode(',', $vehicleData['extrasAvailable']) : [],
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
                        Log::error("Error fetching OK Mobility vehicles: " . $e->getMessage(), [
                            'provider_location_id' => $currentProviderLocationId,
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } elseif ($providerToFetch === 'favrica') {
                    try {
                        Log::info('Attempting to fetch Favrica vehicles for location ID: ' . $currentProviderLocationId);
                        $dropoffId = $validated['dropoff_location_id'] ?? $currentProviderLocationId;
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
                            if (empty($pickupLocationDetails['latitude']) && $locationLat !== null) {
                                $pickupLocationDetails['latitude'] = (string) $locationLat;
                            }
                            if (empty($pickupLocationDetails['longitude']) && $locationLng !== null) {
                                $pickupLocationDetails['longitude'] = (string) $locationLng;
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
                                        'latitude' => (float) $locationLat,
                                        'longitude' => (float) $locationLng,
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
                        Log::error('Error fetching Favrica vehicles: ' . $e->getMessage(), [
                            'provider_location_id' => $currentProviderLocationId,
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                } elseif ($providerToFetch === 'xdrive') {
                    try {
                        Log::info('Attempting to fetch XDrive vehicles for location ID: ' . $currentProviderLocationId);
                        $dropoffId = $validated['dropoff_location_id'] ?? $currentProviderLocationId;
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
                            if (empty($pickupLocationDetails['latitude']) && $locationLat !== null) {
                                $pickupLocationDetails['latitude'] = (string) $locationLat;
                            }
                            if (empty($pickupLocationDetails['longitude']) && $locationLng !== null) {
                                $pickupLocationDetails['longitude'] = (string) $locationLng;
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
                                    'image' => $this->resolveXDriveImageUrl($vehicle['image_path'] ?? null),
                                    'total_price' => $totalRental ?? 0,
                                    'price_per_day' => $dailyRental ?? 0,
                                    'price_per_week' => null,
                                    'price_per_month' => null,
                                    'currency' => $currency,
                                    'transmission' => $transmission,
                                    'fuel' => $fuel,
                                    'seating_capacity' => $seatingCapacity,
                                    'mileage' => $mileage,
                                    'latitude' => (float) $locationLat,
                                    'longitude' => (float) $locationLng,
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
                        Log::error('Error fetching XDrive vehicles: ' . $e->getMessage(), [
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
                            $validated['dropoff_location_id'] ?? $currentProviderLocationId,
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
                                    $locationLat,
                                    $locationLng,
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
                            []
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
                                        'latitude' => (float) $locationLat,
                                        'longitude' => (float) $locationLng,
                                        'full_vehicle_address' => $currentProviderLocationName,
                                        'provider_pickup_id' => $currentProviderLocationId,
                                        'sipp_code' => $sippCode,
                                        'benefits' => [
                                            'minimum_driver_age' => (int) ($validated['age'] ?? 21),
                                            'pay_on_arrival' => true,
                                            'luggage' => $vehicle['luggage'] ?? 0, // Add logic to pass luggage data
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
                        Log::error("Error fetching LocautoRent vehicles: " . $e->getMessage(), [
                            'provider_location_id' => $currentProviderLocationId,
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } // Close LocautoRent elseif
                elseif ($providerToFetch === 'renteon') {
                    try {
                        Log::info('Attempting to fetch Renteon vehicles from default provider for location ID: ' . $currentProviderLocationId);
                        Log::info('Search params: ', [
                            'pickup_id' => $currentProviderLocationId,
                            'date_from' => $validated['date_from'],
                            'start_time' => $startTimeForProvider,
                            'date_to' => $validated['date_to'],
                            'end_time' => $endTimeForProvider
                        ]);

                        $renteonVehicles = $this->renteonService->getTransformedVehicles(
                            $currentProviderLocationId,
                            $currentProviderLocationId,
                            $validated['date_from'],
                            $startTimeForProvider,
                            $validated['date_to'],
                            $endTimeForProvider,
                            [
                                'driver_age' => $validated['age'] ?? 35,
                                'currency' => $validated['currency'] ?? 'EUR',
                                'prepaid' => true,
                            ],
                            $locationLat,
                            $locationLng,
                            $currentProviderLocationName,
                            $rentalDays
                        );

                        Log::info('Renteon vehicles processed from default provider: ' . count($renteonVehicles));

                        if (empty($renteonVehicles)) {
                            Log::info('Renteon: No vehicles from default provider, trying all providers', [
                                'pickup_id' => $currentProviderLocationId,
                                'date_from' => $validated['date_from'],
                                'date_to' => $validated['date_to'],
                            ]);

                            $renteonVehicles = $this->renteonService->getTransformedVehiclesFromAllProviders(
                                $currentProviderLocationId,
                                $currentProviderLocationId,
                                $validated['date_from'],
                                $startTimeForProvider,
                                $validated['date_to'],
                                $endTimeForProvider,
                                [
                                    'driver_age' => $validated['age'] ?? 35,
                                    'currency' => $validated['currency'] ?? 'EUR',
                                    'prepaid' => true,
                                ],
                                [],
                                $locationLat,
                                $locationLng,
                                $currentProviderLocationName,
                                $rentalDays
                            );

                            Log::info('Renteon vehicles processed from all providers: ' . count($renteonVehicles));
                        }

                        foreach ($renteonVehicles as $renteonVehicle) {
                            $providerVehicles->push((object) $renteonVehicle);
                        }

                        Log::info('Renteon vehicles added to collection: ' . count($renteonVehicles));

                    } catch (\Exception $e) {
                        Log::error("Error fetching Renteon vehicles: " . $e->getMessage(), [
                            'provider_location_id' => $currentProviderLocationId,
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } // Close Renteon elseif
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

        $combinedVehicles = $internalVehiclesCollection->merge($filteredProviderVehicles)->merge($filteredOkMobilityVehicles);
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

        $seoMeta = \App\Models\SeoMeta::with('translations')->where('url_slug', '/s')->first();

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

        return Inertia::render('SearchResults', [
            'vehicles' => $vehicles,
            'okMobilityVehicles' => $okMobilityVehiclesPaginated,
            'renteonVehicles' => $renteonVehiclesPaginated,
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
            'seoMeta' => $seoMeta,
            'locale' => \Illuminate\Support\Facades\App::getLocale(),
            'optionalExtras' => array_values($searchOptionalExtras ?? []), // Pass extras
            'locationName' => $validated['location_name'] ?? 'Selected Location', // Pass location name
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

        preg_match_all('/-?\d+(?:\.\d+)?/', $mapsPoint, $matches);
        if (!empty($matches[0]) && count($matches[0]) >= 2) {
            return [(float) $matches[0][0], (float) $matches[0][1]];
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
