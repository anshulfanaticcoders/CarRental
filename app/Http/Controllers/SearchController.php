<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Http\Request;
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
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|numeric',
        ]);

        // Fetch filter options
        $brands = Vehicle::distinct('brand')->pluck('brand');
        $colors = Vehicle::distinct('color')->pluck('color');
        $seatingCapacities = Vehicle::distinct('seating_capacity')->pluck('seating_capacity');

        // Base query
        $query = Vehicle::query()->where('status', 'available');

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
            $query->whereBetween('price_per_day', [(int)$range[0], (int)$range[1]]);
        }
        if (!empty($validated['color'])) {
            $query->where('color', $validated['color']);
        }
        if (!empty($validated['mileage'])) {
            $range = explode('-', $validated['mileage']);
            $query->whereBetween('mileage', [(int)$range[0], (int)$range[1]]);
        }

       

        // Location search
        if (!empty($validated['where'])) {
            $locationParts = array_map('trim', explode(',', $validated['where']));
            $query->where(function ($q) use ($locationParts) {
                foreach ($locationParts as $part) {
                    $searchTerm = '%' . trim($part) . '%';
                    $q->orWhere('location', 'like', $searchTerm);
                }
            });
        }
        $query->with('images');
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

        // Paginate results
        $vehicles = $query->paginate(3)->withQueryString();

        // Transform distance_in_km to integer
        $vehicles->getCollection()->transform(function ($vehicle) {
            if (isset($vehicle->distance_in_km)) {
                $vehicle->distance_in_km = intval($vehicle->distance_in_km);
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
        ]);
    }
}

