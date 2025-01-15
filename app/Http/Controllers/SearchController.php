<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    // public function index()
    // {

    //     return Inertia::render('Welcome');
    // }


    // public function show(Vehicle $vehicle)
    // {
    //     return Inertia::render('VehicleDetail', [
    //         'vehicle' => $vehicle
    //     ]);
    // }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after:date_from',
            'where' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|numeric',
        ]);
    
        $query = Vehicle::query()
        ->where('status', 'available')
            // Calculate distance using Haversine formula
            ->selectRaw('*, ( 6371 * acos( 
                cos(radians(?)) * cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + 
                sin(radians(?)) * sin(radians(latitude)) 
            ) * 1000 ) AS distance', [
                $validated['latitude'],
                $validated['longitude'],
                $validated['latitude']
            ])
            // Filter by radius (in meters)
            ->havingRaw('distance <= ?', [$validated['radius']]);
    
        // Apply location search if 'where' parameter is provided
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
        // Order by distance
        $query->orderBy('distance');
    
        // Get paginated results
        $vehicles = $query->paginate(3)->withQueryString();
        
        return Inertia::render('SearchResults', [
            'vehicles' => $vehicles,
            'filters' => $validated,
        ]);
    }
}

