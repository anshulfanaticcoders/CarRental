<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeocodingController extends Controller
{
    public function autocomplete(Request $request)
    {
        $response = Http::get('https://api.stadiamaps.com/geocoding/v1/autocomplete', [
            'api_key' => '44ebd3d8-2401-4dab-825b-ea0c5e59294b',
            'text' => $request->query('text')
        ]);
        
        return $response->json();
    }
}