<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{

    // public function search(Request $request)
    // {
    //     $validated = $request->validate([
    //         'date_from' => 'required|date',
    //         'date_to' => 'required|date|after:date_from',
    //         'where' => 'nullable|string',
    //         'latitude' => 'required|numeric',
    //         'longitude' => 'required|numeric',
    //         'radius' => 'required|numeric',
    //     ]);
    
    //     $query = Vehicle::query()
    //     ->where('status', 'available')
    //         // Calculate distance using Haversine formula
    //         ->selectRaw('*, ( 6371 * acos( 
    //             cos(radians(?)) * cos(radians(latitude)) * 
    //             cos(radians(longitude) - radians(?)) + 
    //             sin(radians(?)) * sin(radians(latitude)) 
    //         ) * 1000 ) AS distance', [
    //             $validated['latitude'],
    //             $validated['longitude'],
    //             $validated['latitude']
    //         ])
    //         // Filter by radius (in meters)
    //         ->havingRaw('distance <= ?', [$validated['radius']]);
    
    //     // Apply location search if 'where' parameter is provided
    //     if (!empty($validated['where'])) {
    //         $locationParts = array_map('trim', explode(',', $validated['where']));
    //         $query->where(function ($q) use ($locationParts) {
    //             foreach ($locationParts as $part) {
    //                 $searchTerm = '%' . trim($part) . '%';
    //                 $q->orWhere('location', 'like', $searchTerm);
    //             }
    //         });
    //     }
    //     $query->with('images');
    //     // Order by distance
    //     $query->orderBy('distance');
    
    //     // Get paginated results
    //     $vehicles = $query->paginate(3)->withQueryString();
        
    //     return Inertia::render('SearchResults', [
    //         'vehicles' => $vehicles,
    //         'filters' => $validated,
    //         'pagination_links' => $vehicles->links()->toHtml(),
    //     ]);
    // }
    public function search(Request $request)
{
    $validated = $request->validate([
        'date_from' => 'nullable|date', // Make date_from optional
        'date_to' => 'nullable|date|after:date_from', // Make date_to optional
        'where' => 'nullable|string',
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'radius' => 'required|numeric',
    ]);

    $query = Vehicle::query()
        ->where('status', 'available');

    // Conditionally filter by created_at date range if date_from and date_to are provided
    // if (!empty($validated['date_from']) && !empty($validated['date_to'])) {
    //     $query->whereBetween('created_at', [
    //         $validated['date_from'] . ' 00:00:00',
    //         $validated['date_to'] . ' 23:59:59'
    //     ]);
    // }

    // Calculate distance using Haversine formula
    $query->selectRaw('*, ( 6371 * acos( 
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
    $vehicles = $query->paginate(15)->withQueryString();
    
    return Inertia::render('SearchResults', [
        'vehicles' => $vehicles,
        'filters' => $validated,
        'pagination_links' => $vehicles->links()->toHtml(),
    ]);
}
}

