<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Radius;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RadiusApiController extends Controller
{
    /**
     * Get radius for a location
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRadius(Request $request)
    {
        $city = $request->input('city');
        $state = $request->input('state');
        $country = $request->input('country');
        
        // Normalize empty strings to NULL for consistent matching
        $city = !empty($city) ? $city : null;
        $state = !empty($state) ? $state : null;
        $country = !empty($country) ? $country : null;
        
        // Log the search parameters
        Log::info('Searching for radius', [
            'city' => $city,
            'state' => $state,
            'country' => $country
        ]);
        
        // Try to find an exact match first
        $radius = Radius::where(function($query) use ($city, $state, $country) {
            $query->where('city', $city)
                  ->where('state', $state)
                  ->where('country', $country);
        })->first();
        
        // If no exact match found, try with city+state, city+country, or state+country
        if (!$radius && $city && ($state || $country)) {
            $radius = Radius::where(function($query) use ($city, $state, $country) {
                if ($state && $country) {
                    $query->whereNull('city')
                          ->where('state', $state)
                          ->where('country', $country);
                } elseif ($state) {
                    $query->whereNull('city')
                          ->where('state', $state)
                          ->whereNull('country');
                } elseif ($country) {
                    $query->whereNull('city')
                          ->whereNull('state')
                          ->where('country', $country);
                }
            })->first();
        }
        
        // If still no match, try just state or country
        if (!$radius) {
            $radius = Radius::where(function($query) use ($state, $country) {
                if ($state) {
                    $query->whereNull('city')
                          ->where('state', $state)
                          ->whereNull('country');
                } elseif ($country) {
                    $query->whereNull('city')
                          ->whereNull('state')
                          ->where('country', $country);
                }
            })->first();
        }
        
        // If no matches found, return a default radius
        if (!$radius) {
            return response()->json([
                'radius_km' => null,
                'found' => false
            ]);
        }
        
        return response()->json([
            'radius_km' => (float) $radius->radius_km,
            'found' => true
        ]);
    }
}