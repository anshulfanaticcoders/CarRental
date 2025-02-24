<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeocodingController extends Controller
{
    public function autocomplete(Request $request)
    {
        $response = Http::get('https://api.stadiamaps.com/geocoding/v1/autocomplete', [
            'api_key' => '9ea15dfe-b025-47ac-9a0e-cc35cc26891f',
            'text' => $request->query('text')
        ]);
        
        return $response->json();
    }
}