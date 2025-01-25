<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PopularPlace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class PopularPlacesController extends Controller
{
    public function index()
    {
        $places = PopularPlace::paginate(10);
        
        return Inertia::render('AdminDashboardPages/PopularPlaces/Index', [
            'places' => $places
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'place_name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'image' => 'nullable|image|max:2048'
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('popularPlaces', 'public');
            $validated['image'] = $imagePath;
        }

        PopularPlace::create($validated);

        return redirect()->route('popular-places.index')->with('success', 'Place created successfully.');
    }

    public function update(Request $request, PopularPlace $popularPlace)
    {
        $validated = $request->validate([
            'place_name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'image' => 'nullable|image|max:2048'
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($popularPlace->image) {
                Storage::disk('public')->delete($popularPlace->image);
            }

            $imagePath = $request->file('image')->store('popularPlaces', 'public');
            $validated['image'] = $imagePath;
        }

        $popularPlace->update($validated);

        return redirect()->route('popular-places.index')->with('success', 'Place updated successfully.');
    }

    public function destroy(PopularPlace $popularPlace)
    {
        // Delete image if exists
        if ($popularPlace->image) {
            Storage::disk('public')->delete($popularPlace->image);
        }

        $popularPlace->delete();
        return redirect()->route('popular-places.index')->with('success', 'Place deleted successfully.');
    }
}