<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;
use App\Helpers\SchemaBuilder; // Import SchemaBuilder

class SearchController extends Controller
{
    public function search(Request $request)
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
    ]);

    // Base query
    $query = Vehicle::query()->whereIn('status', ['available', 'rented'])
        ->with('images', 'bookings', 'vendorProfile', 'benefits');

    // Apply date filters first
    if (!empty($validated['date_from']) && !empty($validated['date_to'])) {
        $query->whereDoesntHave('bookings', function ($q) use ($validated) {
            $q->where(function ($query) use ($validated) {
                $query->whereBetween('bookings.pickup_date', [$validated['date_from'], $validated['date_to']])
                    ->orWhereBetween('bookings.return_date', [$validated['date_from'], $validated['date_to']])
                    ->orWhere(function ($q) use ($validated) {
                        $q->where('bookings.pickup_date', '<=', $validated['date_from'])
                            ->where('bookings.return_date', '>=', $validated['date_to']);
                    });
            });
        });

        $query->whereDoesntHave('blockings', function ($q) use ($validated) {
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

    // Apply primary location filters
    if (!empty($validated['matched_field'])) {
        $fieldToQuery = null;
        $valueToQuery = null;

        switch ($validated['matched_field']) {
            case 'location':
                if (!empty($validated['location'])) {
                    $fieldToQuery = 'location';
                    $valueToQuery = $validated['location']; // Use the specific location name
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
            $query->where($fieldToQuery, $valueToQuery);
        }
    } elseif (!empty($validated['latitude']) && !empty($validated['longitude']) && !empty($validated['radius'])) {
        // Radius-based filtering for "Around Me"
        $lat = $validated['latitude'];
        $lon = $validated['longitude'];
        $radius = $validated['radius'] / 1000; // Convert meters to kilometers

        // Haversine formula to calculate distance
        $query->whereRaw("
            6371 * acos(
                cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) +
                sin(radians(?)) * sin(radians(latitude))
            ) <= ?
        ", [$lat, $lon, $lat, $radius]);
    } elseif (!empty($validated['where'])) {
        $searchTerm = $validated['where'];
        $query->where(function ($q) use ($searchTerm) {
            $q->where('location', 'LIKE', "%{$searchTerm}%")
              ->orWhere('city', 'LIKE', "%{$searchTerm}%")
              ->orWhere('state', 'LIKE', "%{$searchTerm}%")
              ->orWhere('country', 'LIKE', "%{$searchTerm}%");
        });
    }

    // --- At this point, primary date and location filters are applied to $query ---
    // --- Clone for fetching broad filter options BEFORE specific attribute filters ---
    $queryForOptions = clone $query;
    $potentialVehiclesForOptions = $queryForOptions->get();

    $brands = $potentialVehiclesForOptions->pluck('brand')->unique()->filter()->values()->all();
    $colors = $potentialVehiclesForOptions->pluck('color')->unique()->filter()->values()->all();
    $seatingCapacities = $potentialVehiclesForOptions->pluck('seating_capacity')->unique()->filter()->values()->all();
    $transmissions = $potentialVehiclesForOptions->pluck('transmission')->unique()->filter()->values()->all();
    $fuels = $potentialVehiclesForOptions->pluck('fuel')->unique()->filter()->values()->all();
    $mileages = $potentialVehiclesForOptions->pluck('mileage')->unique()->filter()->map(function ($mileage) {
        if ($mileage >= 0 && $mileage <= 10) return '0-10';
        if ($mileage > 10 && $mileage <= 20) return '10-20';
        if ($mileage > 20 && $mileage <= 30) return '20-30';
        if ($mileage > 30 && $mileage <= 40) return '30-40';
        return null;
    })->filter()->unique()->values()->all();
    // Ensure categories are also fetched based on this broader set
    $categoriesFromOptions = VehicleCategory::whereIn('id', $potentialVehiclesForOptions->pluck('category_id')->unique()->filter())->select('id', 'name')->get()->toArray();

    // --- Now apply secondary attribute filters to the main $query ---
    if (!empty($validated['seating_capacity'])) {
        $query->where('seating_capacity', $validated['seating_capacity']);
    }
    if (!empty($validated['brand'])) {
        $query->where('brand', $validated['brand']);
    }
    if (!empty($validated['transmission'])) {
        $query->where('transmission', $validated['transmission']);
    }
    if (!empty($validated['fuel'])) {
        $query->where('fuel', $validated['fuel']);
    }
    if (!empty($validated['price_range'])) {
        $range = explode('-', $validated['price_range']);
        $query->whereBetween('price_per_day', [(int) $range[0], (int) $range[1]]);
    }
    if (!empty($validated['color'])) {
        $query->where('color', $validated['color']);
    }
    if (!empty($validated['mileage'])) {
        $range = explode('-', $validated['mileage']);
        $query->whereBetween('mileage', [(int) $range[0], (int) $range[1]]);
    }
    if (!empty($validated['category_id'])) {
        $query->where('category_id', $validated['category_id']);
    }
    
    // Package type filter (applied to the main query for results)
    if (!empty($validated['package_type'])) {
        switch ($validated['package_type']) {
            case 'week':
                $query->whereNotNull('price_per_week')->orderBy('price_per_week');
                break;
            case 'month':
                $query->whereNotNull('price_per_month')->orderBy('price_per_month');
                break;
            default:
                $query->whereNotNull('price_per_day')->orderBy('price_per_day');
                break;
        }
    }

    // Paginate the main query to get the vehicles for display
    $vehicles = $query->paginate(20)->withQueryString();

    // Transform the paginated collection to include review data
    $vehicles->getCollection()->transform(function ($vehicle) {
        $reviews = Review::where('vehicle_id', $vehicle->id)
            ->where('status', 'approved')
            ->get();
        $vehicle->review_count = $reviews->count();
        $vehicle->average_rating = $reviews->avg('rating') ?? 0;

        return $vehicle;
    });

    // Return Inertia response
        // Generate ItemList schema for the vehicles
        $vehicleListSchema = SchemaBuilder::vehicleList($vehicles->getCollection(), 'Vehicle Search Results', $validated);

        // Fetch SEO meta for the search results page (assuming its url_slug is '/s')
        $seoMeta = \App\Models\SeoMeta::with('translations')->where('url_slug', '/s')->first();

        return Inertia::render('SearchResults', [
            'vehicles' => $vehicles,
            'filters' => $validated,
            'pagination_links' => $vehicles->links()->toHtml(),
            'brands' => $brands, // Use options derived from $potentialVehiclesForOptions
            'colors' => $colors, // Use options derived from $potentialVehiclesForOptions
            'seatingCapacities' => $seatingCapacities, // Use options derived from $potentialVehiclesForOptions
            'transmissions' => $transmissions, // Use options derived from $potentialVehiclesForOptions
            'fuels' => $fuels, // Use options derived from $potentialVehiclesForOptions
            'mileages' => $mileages, // Use options derived from $potentialVehiclesForOptions
            'categories' => $categoriesFromOptions, // Use options derived from $potentialVehiclesForOptions
            'schema' => $vehicleListSchema, // Pass schema to the Vue component
            'seoMeta' => $seoMeta, // Pass SEO meta to the component
            'locale' => \Illuminate\Support\Facades\App::getLocale(), // Pass current locale
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
        'where' => 'nullable|string',
        'city' => 'nullable|string',
        'state' => 'nullable|string',
        'country' => 'nullable|string',
        'latitude' => 'nullable|numeric',
        'longitude' => 'nullable|numeric',
        'radius' => 'nullable|numeric',
        'package_type' => 'nullable|string|in:day,week,month',
    ]);

    // The slug from the URL is the source of truth for the main category filter.
    // It's added to the validated data to be available in the view as a filter.
    $validated['category_slug'] = $slug;

    // Base query
    $query = Vehicle::query();

    if ($slug) {
        $category = VehicleCategory::where('slug', $slug)->first();
        if ($category) {
            $query->where('category_id', $category->id);
        } else {
            // If slug is invalid, return no results.
            $query->whereRaw('1 = 0');
        }
    }
    
    $query->whereIn('status', ['available', 'rented'])
        ->with('images', 'bookings', 'vendorProfile', 'benefits');

    // Apply date filters first
    if (!empty($validated['date_from']) && !empty($validated['date_to'])) {
        $query->whereDoesntHave('bookings', function ($q) use ($validated) {
            $q->where(function ($query) use ($validated) {
                $query->whereBetween('bookings.pickup_date', [$validated['date_from'], $validated['date_to']])
                    ->orWhereBetween('bookings.return_date', [$validated['date_from'], $validated['date_to']])
                    ->orWhere(function ($q) use ($validated) {
                        $q->where('bookings.pickup_date', '<=', $validated['date_from'])
                            ->where('bookings.return_date', '>=', $validated['date_to']);
                    });
            });
        });

        $query->whereDoesntHave('blockings', function ($q) use ($validated) {
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

    // Apply primary location filters
    if (!empty($validated['city'])) {
        $query->where('city', 'LIKE', "%{$validated['city']}%");
    } elseif (!empty($validated['state'])) {
        $query->where('state', 'LIKE', "%{$validated['state']}%");
    } elseif (!empty($validated['country'])) {
        $query->where('country', 'LIKE', "%{$validated['country']}%");
    } elseif (!empty($validated['where'])) { // Fallback to 'where' if specific fields are not provided
        // Attempt geocoding only if specific city/state/country are not given
        $cityFromGeocode = null;
        // $stateFromGeocode = null; // Not strictly needed if we prioritize direct inputs
        // $countryFromGeocode = null; // Not strictly needed

        $geocodeResponse = Http::get('https://api.stadiamaps.com/geocoding/v1/search', [
            'api_key' => env('STADIA_MAPS_API_KEY'),
            'text' => $validated['where'],
            'limit' => 1,
        ]);

        if ($geocodeResponse->successful()) {
            $geocodeData = $geocodeResponse->json()['features'][0]['properties'] ?? [];
            $cityFromGeocode = $geocodeData['locality'] ?? null;
            // $stateFromGeocode = $geocodeData['region'] ?? null;
            // $countryFromGeocode = $geocodeData['country'] ?? null;

            // Use geocoded city if found and no direct city/state/country was input
            if ($cityFromGeocode) {
                $query->where('city', $cityFromGeocode);
            } else { // If geocoding doesn't give a city, search broadly in 'where'
                 $query->where(function ($q) use ($validated) {
                    $q->where('location', 'LIKE', "%{$validated['where']}%")
                      ->orWhere('city', 'LIKE', "%{$validated['where']}%")
                      ->orWhere('state', 'LIKE', "%{$validated['where']}%")
                      ->orWhere('country', 'LIKE', "%{$validated['where']}%");
                });
            }
        } elseif (!empty($validated['latitude']) && !empty($validated['longitude']) && !empty($validated['radius'])) {
            // Fallback to radius search if 'where' fails or not provided, and lat/lon/radius are available
            $radiusInKm = $validated['radius'] / 1000;
            $query->selectRaw('*, ( 6371 * acos( 
                cos(radians(?)) * cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + 
                sin(radians(?)) * sin(radians(latitude)) 
            ) ) AS distance_in_km', [
                $validated['latitude'],
                $validated['longitude'],
                $validated['latitude']
            ])
                ->havingRaw('distance_in_km <= ?', [$radiusInKm])
                ->orderBy('distance_in_km');
        } else {
            // If 'where' is provided but geocoding fails and no lat/lon, search broadly
            $query->where(function ($q) use ($validated) {
                $q->where('location', 'LIKE', "%{$validated['where']}%")
                  ->orWhere('city', 'LIKE', "%{$validated['where']}%")
                  ->orWhere('state', 'LIKE', "%{$validated['where']}%")
                  ->orWhere('country', 'LIKE', "%{$validated['where']}%");
            });
        }
    } elseif (!empty($validated['latitude']) && !empty($validated['longitude']) && !empty($validated['radius'])) {
        // Radius search if no city/state/country/where are provided but lat/lon/radius are
        $radiusInKm = $validated['radius'] / 1000;
        $query->selectRaw('*, ( 6371 * acos( 
            cos(radians(?)) * cos(radians(latitude)) * 
            cos(radians(longitude) - radians(?)) + 
            sin(radians(?)) * sin(radians(latitude)) 
        ) ) AS distance_in_km', [
            $validated['latitude'],
            $validated['longitude'],
            $validated['latitude']
        ])
            ->havingRaw('distance_in_km <= ?', [$radiusInKm])
            ->orderBy('distance_in_km');
    }

    // --- At this point, primary date and location filters are applied to $query ---
    // --- Clone for fetching broad filter options BEFORE specific attribute filters ---
    $queryForOptionsCategory = clone $query; // Use a different name to avoid conflict if in same scope
    $potentialVehiclesForOptionsCategory = $queryForOptionsCategory->get();

    $brands = $potentialVehiclesForOptionsCategory->pluck('brand')->unique()->filter()->values()->all();
    $colors = $potentialVehiclesForOptionsCategory->pluck('color')->unique()->filter()->values()->all();
    $seatingCapacities = $potentialVehiclesForOptionsCategory->pluck('seating_capacity')->unique()->filter()->values()->all();
    $transmissions = $potentialVehiclesForOptionsCategory->pluck('transmission')->unique()->filter()->values()->all();
    $fuels = $potentialVehiclesForOptionsCategory->pluck('fuel')->unique()->filter()->values()->all();
    $mileages = $potentialVehiclesForOptionsCategory->pluck('mileage')->unique()->filter()->map(function ($mileage) {
        if ($mileage >= 0 && $mileage <= 10) return '0-10';
        if ($mileage > 10 && $mileage <= 20) return '10-20';
        if ($mileage > 20 && $mileage <= 30) return '20-30';
        if ($mileage > 30 && $mileage <= 40) return '30-40';
        return null;
    })->filter()->unique()->values()->all();
    // For CategorySearchResults, all categories are usually passed, or just the current one.
    // If we want to show *other* categories available at this location/date, we'd use:
    // $categoriesFromOptions = VehicleCategory::whereIn('id', $potentialVehiclesForOptionsCategory->pluck('category_id')->unique()->filter())->select('id', 'name')->get()->toArray();
    // However, typically for a category search page, you might just pass all categories or the specific one.
    // For consistency with the general search, let's fetch all categories available for the primary filters.
    $allCategoriesForPage = VehicleCategory::select('id', 'name', 'slug')->get()->toArray();


    // --- Now apply secondary attribute filters to the main $query ---
    if (!empty($validated['seating_capacity'])) {
        $query->where('seating_capacity', $validated['seating_capacity']);
    }
    if (!empty($validated['brand'])) {
        $query->where('brand', $validated['brand']);
    }
    if (!empty($validated['transmission'])) {
        $query->where('transmission', $validated['transmission']);
    }
    if (!empty($validated['fuel'])) {
        $query->where('fuel', $validated['fuel']);
    }
    if (!empty($validated['price_range'])) {
        $range = explode('-', $validated['price_range']);
        $query->whereBetween('price_per_day', [(int) $range[0], (int) $range[1]]);
    }
    if (!empty($validated['color'])) {
        $query->where('color', $validated['color']);
    }
    if (!empty($validated['mileage'])) {
        $range = explode('-', $validated['mileage']);
        $query->whereBetween('mileage', [(int) $range[0], (int) $range[1]]);
    }
    // category_id is already applied at the top for this method.

    // Package type filter (applied to the main query for results)
    if (!empty($validated['package_type'])) {
        switch ($validated['package_type']) {
            case 'week':
                $query->whereNotNull('price_per_week')->orderBy('price_per_week');
                break;
            case 'month':
                $query->whereNotNull('price_per_month')->orderBy('price_per_month');
                break;
            default:
                $query->whereNotNull('price_per_day')->orderBy('price_per_day');
                break;
        }
    }

    // Paginate results
    $vehicles = $query->paginate(20)->withQueryString();

    // Transform the collection to include review data and distance
    $vehicles->getCollection()->transform(function ($vehicle) {
        $reviews = Review::where('vehicle_id', $vehicle->id)
            ->where('status', 'approved')
            ->get();
        $vehicle->review_count = $reviews->count();
        $vehicle->average_rating = $reviews->avg('rating') ?? 0;

        if (isset($vehicle->distance_in_km)) {
            $vehicle->distance_in_km = intval($vehicle->distance_in_km);
        }

        return $vehicle;
    });
    
    // Generate ItemList schema for the vehicles
        $vehicleListSchema = SchemaBuilder::vehicleList($vehicles->getCollection(), 'Vehicle Search Results', $validated);
    // Return Inertia response
        // Fetch SEO meta for the category search results page
        // The url_slug for category search pages will be dynamic, e.g., 'search/category/suv'
        $seoUrlSlug = 'search/category/' . ($validated['category_slug'] ?? '');
        $seoMeta = \App\Models\SeoMeta::with('translations')->where('url_slug', $seoUrlSlug)->first();

    return Inertia::render('CategorySearchResults', [
        'vehicles' => $vehicles,
        'filters' => $validated,
        'pagination_links' => $vehicles->links()->toHtml(),
        'brands' => $brands, // Use options derived from $potentialVehiclesForOptionsCategory
        'colors' => $colors, // Use options derived from $potentialVehiclesForOptionsCategory
        'seatingCapacities' => $seatingCapacities, // Use options derived from $potentialVehiclesForOptionsCategory
        'transmissions' => $transmissions, // Use options derived from $potentialVehiclesForOptionsCategory
        'fuels' => $fuels, // Use options derived from $potentialVehiclesForOptionsCategory
        'mileages' => $mileages, // Use options derived from $potentialVehiclesForOptionsCategory
        'categories' => $allCategoriesForPage, // Pass all categories for selection
        'schema' => $vehicleListSchema, 
        'seoMeta' => $seoMeta, // Pass SEO meta to the component
        'locale' => \Illuminate\Support\Facades\App::getLocale(), // Pass current locale
    ]);
}
}
