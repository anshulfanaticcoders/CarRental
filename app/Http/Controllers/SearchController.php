<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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
            'package_type' => 'nullable|string|in:day,week,month',
            'category_id' => 'nullable|exists:vehicle_categories,id',
        ]);

        // Fetch filter options
        $brands = Vehicle::distinct('brand')->pluck('brand');
        $colors = Vehicle::distinct('color')->pluck('color');
        $seatingCapacities = Vehicle::distinct('seating_capacity')->pluck('seating_capacity');

        // Base query
        $query = Vehicle::query()->whereIn('status', ['available', 'rented']);

    //     $latitude = $validated['latitude'] ?? null;
    // $longitude = $validated['longitude'] ?? null;
    // $radius = $validated['radius'] ?? null;

    // if (!empty($validated['where']) && (!$latitude || !$longitude)) {
    //     // Assume 'where' contains a location that can be geocoded (e.g., using a service like Google Maps API)
    //     // For simplicity, this example uses a placeholder. Replace with actual geocoding logic.
    //     [$latitude, $longitude] = $this->geocodeLocation($validated['where']);
    //     $radius = $radius ?? 5000; // Default radius of 5km if not provided
    // }

        // Exclude vehicles that are booked in the selected date range
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

            // Add blocking dates exclusion
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


        // Apply filters
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
        // Then in your query building section, add:
        if (!empty($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        $categories = DB::table('vehicle_categories')->select('id', 'name')->get();

        // Location search
        if (!empty($validated['where'])) {
            $trimmedWhere = trim($validated['where']);
            $locationParts = array_map('trim', explode(',', $trimmedWhere));
            $normalizedWhere = strtolower($trimmedWhere);
            \Illuminate\Support\Facades\Log::info('Processed where value:', ['trimmedWhere' => $trimmedWhere, 'parts' => $locationParts]);
        
            $query->where(function ($q) use ($locationParts, $normalizedWhere, $query) {
                $primaryTerm = strtolower(trim($locationParts[0]));
        
                // Check city first
                \Illuminate\Support\Facades\Log::info('Checking city:', ['term' => $primaryTerm]);
                $q->orWhereRaw('LOWER(city) = ?', [$primaryTerm]);
        
                // Then state
                \Illuminate\Support\Facades\Log::info('Checking state:', ['term' => $primaryTerm]);
                $q->orWhereRaw('LOWER(state) = ?', [$primaryTerm]);
        
                // Then country
                \Illuminate\Support\Facades\Log::info('Checking country:', ['term' => $primaryTerm]);
                $q->orWhereRaw('LOWER(country) = ?', [$primaryTerm]);
        
                // Then location (partial match)
                \Illuminate\Support\Facades\Log::info('Checking location:', ['term' => $normalizedWhere]);
                $q->orWhereRaw('LOWER(location) = ?', [$normalizedWhere]);
        
                // If multiple parts, check full location as a fallback
                if (count($locationParts) > 1) {
                    $fullLocation = strtolower(implode(', ', $locationParts));
                    \Illuminate\Support\Facades\Log::info('Checking full location:', ['location' => $fullLocation]);
                    $q->orWhereRaw('LOWER(location) = ?', [$fullLocation]);
                }
            });
        }
        $query->with('images', 'bookings', 'vendorProfile', 'benefits');
        // Distance filter (Haversine formula)
        if (!empty($validated['latitude']) && !empty($validated['longitude']) && !empty($validated['radius'])) {
            $radiusInKm = $validated['radius'] / 1000; // Convert meters to kilometers
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
        $vehicles = $query->paginate(4)->withQueryString();

        // Transform the collection to include review data and distance
        $vehicles->getCollection()->transform(function ($vehicle) {
            // Add review statistics
            $reviews = Review::where('vehicle_id', $vehicle->id)
                ->where('status', 'approved')
                ->get();
            $vehicle->review_count = $reviews->count();
            $vehicle->average_rating = $reviews->avg('rating') ?? 0;

            // Transform distance_in_km to integer if it exists
            if (isset($vehicle->distance_in_km) && $vehicle->distance_in_km === 0) {
                // Log or debug to check why distance is 0
                \Log::debug('Distance is 0 for vehicle ID: ' . $vehicle->id . ', Latitude: ' . $vehicle->latitude . ', Longitude: ' . $vehicle->longitude);
            }

            return $vehicle;
        });

        // Return Inertia response
        return Inertia::render('SearchResults', [
            'vehicles' => $vehicles,
            'filters' => $validated,
            'pagination_links' => $vehicles->links()->toHtml(),
            'brands' => $brands,
            'colors' => $colors,
            'seatingCapacities' => $seatingCapacities,
            'categories' => $categories,
        ]);
    }

    
    // New function to handle category-based search
    public function searchByCategory(Request $request, $category_id)
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
            'package_type' => 'nullable|string|in:day,week,month',
        ]);

        // Fetch filter options
        $brands = Vehicle::distinct('brand')->pluck('brand');
        $colors = Vehicle::distinct('color')->pluck('color');
        $seatingCapacities = Vehicle::distinct('seating_capacity')->pluck('seating_capacity');

        // Base query
        $query = Vehicle::query()->where('category_id', $category_id)->whereIn('status', ['available', 'rented']);

        // Exclude vehicles that are booked in the selected date range
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

            // Add blocking dates exclusion
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

        // Apply filters
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
        if (!empty($validated['where'])) {
            $locationParts = array_map('trim', explode(',', $validated['where']));
            $query->where(function ($q) use ($locationParts) {
                foreach ($locationParts as $part) {
                    $searchTerm = '%' . trim($part) . '%';
                    $q->orWhere('location', 'like', $searchTerm);
                }
            });
        }
        $query->with('images', 'bookings', 'vendorProfile', 'benefits');

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
        $vehicles = $query->paginate(4)->withQueryString();

        $vehicles->getCollection()->transform(function ($vehicle) {
            // Add review statistics
            $reviews = Review::where('vehicle_id', $vehicle->id)
                ->where('status', 'approved')
                ->get();
            $vehicle->review_count = $reviews->count();
            $vehicle->average_rating = $reviews->avg('rating') ?? 0;

            // Transform distance_in_km to integer if it exists
            if (isset($vehicle->distance_in_km)) {
                $vehicle->distance_in_km = intval($vehicle->distance_in_km);
            }

            return $vehicle;
        });

        // Fetch categories
        $categories = VehicleCategory::all();

        // Return Inertia response
        return Inertia::render('SearchResults', [
            'vehicles' => $vehicles,
            'filters' => $validated,
            'pagination_links' => $vehicles->links()->toHtml(),
            'brands' => $brands,
            'colors' => $colors,
            'seatingCapacities' => $seatingCapacities,
            'categories' => $categories,
        ]);
    }
}

