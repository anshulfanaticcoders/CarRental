<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Radius;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RadiusController extends Controller
{
     public function index(Request $request)
    {
        // Get search parameters
        $search = $request->input('search', '');
        
        // Fetch unique city, state, country combinations with pagination
        $query = Vehicle::select('city', 'state', 'country')
        ->where(function($q) {
            $q->whereNotNull('city')
              ->orWhereNotNull('state')
              ->orWhereNotNull('country');
        })
        ->distinct();
            
        // Apply search filter if provided
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('city', 'LIKE', "%{$search}%")
                  ->orWhere('state', 'LIKE', "%{$search}%")
                  ->orWhere('country', 'LIKE', "%{$search}%");
            });
        }
        
        $combinations = $query->paginate(10)
                              ->withQueryString(); // Important to preserve search params in pagination links

        // Get all radii
        $radiuses = Radius::all();

        return inertia('AdminDashboardPages/Radius/Index', [
            'combinations' => $combinations,
            'radiuses' => $radiuses,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'radius_km' => 'required|numeric|min:0',
        ]);
    
        // Normalize empty strings to NULL for consistent comparison
        $city = !empty($validated['city']) ? $validated['city'] : null;
        $state = !empty($validated['state']) ? $validated['state'] : null;
        $country = !empty($validated['country']) ? $validated['country'] : null;
        
        // Log the values we're checking
        Log::info('Checking existence for new record', [
            'city' => $city,
            'state' => $state,
            'country' => $country
        ]);
    
        // Check if the combination already exists using our custom method
        if (Radius::locationExists($city, $state, $country)) {
            return redirect()->back()->with('error', 'A radius with this location combination already exists.');
        }
    
        // Create with normalized values
        Radius::create([
            'city' => $city,
            'state' => $state,
            'country' => $country,
            'radius_km' => $validated['radius_km']
        ]);
    
        return redirect()->back()->with('success', 'Radius created successfully!');
    }
    
    public function update(Request $request, Radius $radius)
    {
        $validated = $request->validate([
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'radius_km' => 'required|numeric|min:0',
        ]);
        
        // Normalize empty strings to NULL for consistent comparison
        $city = !empty($validated['city']) ? $validated['city'] : null;
        $state = !empty($validated['state']) ? $validated['state'] : null;
        $country = !empty($validated['country']) ? $validated['country'] : null;
        
        // Check if anything has actually changed
        if (($radius->city === $city || ($radius->city === null && $city === null)) && 
            ($radius->state === $state || ($radius->state === null && $state === null)) && 
            ($radius->country === $country || ($radius->country === null && $country === null)) &&
            (float)$radius->radius_km === (float)$validated['radius_km']) {
            return redirect()->back()->with('success', 'No changes were made.');
        }
        
        // Log the values we're checking
        Log::info('Checking existence for update', [
            'id' => $radius->id,
            'city' => $city,
            'state' => $state,
            'country' => $country
        ]);
        
        // Check if the new combination already exists (excluding the current radius)
        if (Radius::locationExists($city, $state, $country, $radius->id)) {
            return redirect()->back()->with('error', 'A radius with this location combination already exists.');
        }
        
        // Update with normalized values
        $radius->update([
            'city' => $city,
            'state' => $state,
            'country' => $country,
            'radius_km' => $validated['radius_km']
        ]);
    
        return redirect()->back()->with('success', 'Radius updated successfully!');
    }

    public function destroy(Radius $radius)
    {
        $radius->delete();

        return redirect()->back()->with('success', 'Radius deleted successfully!');
    }
}