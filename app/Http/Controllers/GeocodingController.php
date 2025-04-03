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
}