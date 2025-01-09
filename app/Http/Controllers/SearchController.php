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


    public function show(Vehicle $vehicle)
    {
        return Inertia::render('VehicleDetail', [
            'vehicle' => $vehicle
        ]);
    }

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
            ->whereRaw(
                '(
                    6371 * acos(
                        cos(radians(?)) * cos(radians(latitude)) *
                        cos(radians(longitude) - radians(?)) +
                        sin(radians(?)) * sin(radians(latitude))
                    )
                ) <= ?',
                [
                    $validated['latitude'],
                    $validated['longitude'],
                    $validated['latitude'],
                    $validated['radius'] / 1000, // Convert to kilometers
                ]
            );
        $query->with('images');
        $vehicles = $query->paginate(12);
        return Inertia::render('SearchResults', [
            'vehicles' => $vehicles,
            'filters' => $validated,
        ]);
    }
}

