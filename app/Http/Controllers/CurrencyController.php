<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CurrencyController extends Controller
{
    public function index()
    {
        // Load the currencies.json file from storage
        $currenciesJson = Storage::disk('public')->get('currencies.json');

        // Return the JSON response
        return response()->json(json_decode($currenciesJson));
    }
}
