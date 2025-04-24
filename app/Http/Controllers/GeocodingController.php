<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeocodingController extends Controller
{
    public function autocomplete(Request $request)
    {
        $apiKey = env('VITE_STADIA_MAPS_API_KEY');
        $response = Http::get('https://api.stadiamaps.com/geocoding/v1/autocomplete', [
            'api_key' => $apiKey,
            'text' => $request->query('text')
        ]);
        
        return $response->json();
    }


    public function reverse(Request $request)
    {
        $apiKey = env('VITE_STADIA_MAPS_API_KEY');
        
        // Validate input parameters
        $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric'
        ]);
        
        // Call Stadia Maps reverse geocoding API
        $response = Http::get('https://api.stadiamaps.com/geocoding/v1/reverse', [
            'api_key' => $apiKey,
            'point.lat' => $request->query('lat'),
            'point.lon' => $request->query('lon'),
            'size' => 1 // Return only one result
        ]);
        
        return $response->json();
    }
}