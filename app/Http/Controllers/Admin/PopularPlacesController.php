<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PopularPlace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class PopularPlacesController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $places = PopularPlace::when($search, function ($query, $search) {
            return $query->where(function ($query) use ($search) {
                $query->where('place_name', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");
            });
        })
            ->paginate(10); // Add pagination

        return Inertia::render('AdminDashboardPages/PopularPlaces/Index', [
            'places' => $places,
            'filters' => $request->only(['search']),
            'status' => session('status') ?? null
        ]);
    }
    

    public function create()
    {
        return Inertia::render('AdminDashboardPages/PopularPlaces/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'place_name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $place = new PopularPlace();
        $place->place_name = $request->place_name;
        $place->city = $request->city;
        $place->state = $request->state;
        $place->country = $request->country;
        $place->latitude = $request->latitude;
        $place->longitude = $request->longitude;

        if ($request->hasFile('image')) {
            $place->image = $request->file('image')->store('popularPlaces', 'public');
        }

        $place->save();

        return redirect()->route('popular-places.index')
            ->with('status', 'Place created successfully');
    }

    public function edit(PopularPlace $popularPlace)
    {
        return Inertia::render('AdminDashboardPages/PopularPlaces/Edit', [
            'place' => $popularPlace
        ]);
    }

    public function update(Request $request, PopularPlace $popularPlace)
    {
        $request->validate([
            'place_name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $popularPlace->place_name = $request->place_name;
        $popularPlace->city = $request->city;
        $popularPlace->state = $request->state;
        $popularPlace->country = $request->country;
        $popularPlace->latitude = $request->latitude;
        $popularPlace->longitude = $request->longitude;

        if ($request->hasFile('image')) {
            // Delete old image
            if ($popularPlace->image) {
                Storage::disk('public')->delete($popularPlace->image);
            }
            $popularPlace->image = $request->file('image')->store('popularPlaces', 'public');
        }

        $popularPlace->save();

        return redirect()->route('popular-places.index')
            ->with('status', 'Place updated successfully');
    }

    public function destroy(PopularPlace $popularPlace)
    {
        if ($popularPlace->image) {
            Storage::disk('public')->delete($popularPlace->image);
        }

        $popularPlace->delete();

        return redirect()->route('popular-places.index')
            ->with('status', 'Place deleted successfully');
    }
}
