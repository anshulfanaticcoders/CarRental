<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;

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

    // Exclude vehicles booked in the selected date range
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

    // Apply non-location filters
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

    // Location-based filtering based on matched_field
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

    // Package type filter
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

    // Transform the collection to include review data
    $vehicles->getCollection()->transform(function ($vehicle) {
        $reviews = Review::where('vehicle_id', $vehicle->id)
            ->where('status', 'approved')
            ->get();
        $vehicle->review_count = $reviews->count();
        $vehicle->average_rating = $reviews->avg('rating') ?? 0;

        return $vehicle;
    });

    // Fetch filter options only from the current paginated results and convert to arrays
    $brands = $vehicles->pluck('brand')->unique()->values()->all();
    $colors = $vehicles->pluck('color')->unique()->filter()->values()->all();
    $seatingCapacities = $vehicles->pluck('seating_capacity')->unique()->filter()->values()->all();
    $transmissions = $vehicles->pluck('transmission')->unique()->filter()->values()->all();
    $fuels = $vehicles->pluck('fuel')->unique()->filter()->values()->all();

    // For mileage, map to the range format used in the frontend
    $mileages = $vehicles->pluck('mileage')->unique()->filter()->map(function ($mileage) {
        if ($mileage >= 0 && $mileage <= 10) return '0-10';
        if ($mileage > 10 && $mileage <= 20) return '10-20';
        if ($mileage > 20 && $mileage <= 30) return '20-30';
        if ($mileage > 30 && $mileage <= 40) return '30-40';
        return null;
    })->filter()->unique()->values()->all();

    $categories = VehicleCategory::whereIn('id', $vehicles->pluck('category_id')->unique())->select('id', 'name')->get()->toArray();

    // Return Inertia response
    return Inertia::render('SearchResults', [
        'vehicles' => $vehicles,
        'filters' => $validated,
        'pagination_links' => $vehicles->links()->toHtml(),
        'brands' => $brands,
        'colors' => $colors,
        'seatingCapacities' => $seatingCapacities,
        'transmissions' => $transmissions,
        'fuels' => $fuels,
        'mileages' => $mileages,
        'categories' => $categories,
    ]);
}

public function searchByCategory(Request $request, $category_id = null)
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

    // Base query
    $query = Vehicle::query();

    if ($category_id) {
        $query->where('category_id', $category_id);
    }
    
    $query->whereIn('status', ['available', 'rented'])
        ->with('images', 'bookings', 'vendorProfile', 'benefits');

    // Exclude vehicles booked in the selected date range
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

    // Apply non-location filters
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

    // Location-based filtering
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


    // Package type filter
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

    // Fetch filter options only from the current paginated results and convert to arrays
    $brands = $vehicles->pluck('brand')->unique()->values()->all();
    $colors = $vehicles->pluck('color')->unique()->filter()->values()->all();
    $seatingCapacities = $vehicles->pluck('seating_capacity')->unique()->filter()->values()->all();
    $transmissions = $vehicles->pluck('transmission')->unique()->filter()->values()->all();
    $fuels = $vehicles->pluck('fuel')->unique()->filter()->values()->all();

    // For mileage, map to the range format used in the frontend
    $mileages = $vehicles->pluck('mileage')->unique()->filter()->map(function ($mileage) {
        if ($mileage >= 0 && $mileage <= 10) return '0-10';
        if ($mileage > 10 && $mileage <= 20) return '10-20';
        if ($mileage > 20 && $mileage <= 30) return '20-30';
        if ($mileage > 30 && $mileage <= 40) return '30-40';
        return null;
    })->filter()->unique()->values()->all();

    $categories = VehicleCategory::whereIn('id', $vehicles->pluck('category_id')->unique())->select('id', 'name')->get()->toArray();

    // Return Inertia response
    return Inertia::render('CategorySearchResults', [
        'vehicles' => $vehicles,
        'filters' => $validated,
        'pagination_links' => $vehicles->links()->toHtml(),
        'brands' => $brands,
        'colors' => $colors,
        'seatingCapacities' => $seatingCapacities,
        'transmissions' => $transmissions,
        'fuels' => $fuels,
        'mileages' => $mileages,
        'categories' => $categories,
    ]);
}
}
