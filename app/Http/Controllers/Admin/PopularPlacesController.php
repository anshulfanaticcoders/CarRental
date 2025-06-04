<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FooterSetting;
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
            ->paginate(6); // Add pagination

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
            $imagePath = $request->file('image')->store('popularPlaces', 'upcloud'); // Store in UpCloud
            $place->image = Storage::disk('upcloud')->url($imagePath); // Get full image URL
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
            // Delete old image from UpCloud
            if ($popularPlace->image) {
                $oldImagePath = str_replace(Storage::disk('upcloud')->url(''), '', $popularPlace->image);
                Storage::disk('upcloud')->delete($oldImagePath);
            }
    
            $imagePath = $request->file('image')->store('popularPlaces', 'upcloud');
            $popularPlace->image = Storage::disk('upcloud')->url($imagePath);
        }

        $popularPlace->save();

        return redirect()->route('popular-places.index')
            ->with('status', 'Place updated successfully');
    }

    public function destroy(PopularPlace $popularPlace)
    {
        if ($popularPlace->image) {
            $oldImagePath = str_replace(Storage::disk('upcloud')->url(''), '', $popularPlace->image);
            Storage::disk('upcloud')->delete($oldImagePath);
        }

        $popularPlace->delete();

        return redirect()->route('popular-places.index')
            ->with('status', 'Place deleted successfully');
    }


    public function footerSettings()
    {
        $places = PopularPlace::all();
        
        // Get selected places from the footer_settings table
        $footerSettings = FooterSetting::where('type', 'popular_places')->first();
        $selectedPlaces = [];
        
        if ($footerSettings) {
            // Decode the JSON stored in the settings value
            $selectedPlaces = json_decode($footerSettings->value, true) ?? [];
        }

        return Inertia::render('AdminDashboardPages/Settings/Footer/Index', [
            'places' => $places,
            'selectedPlaces' => $selectedPlaces,
        ]);
    }

    public function updateFooterSettings(Request $request)
    {
        $selectedPlaces = $request->input('selected_places', []);
        
        // Save to footer_settings table - using upsert to create or update
        FooterSetting::updateOrCreate(
            ['type' => 'popular_places'],
            ['value' => json_encode($selectedPlaces)]
        );

        return redirect()->route('admin.settings.footer')->with('status', 'Footer settings updated successfully');
    }

     // method to get footer places for the front-end
     public function getFooterPlaces()
     {
         // Get selected place IDs from footer settings
         $footerSettings = FooterSetting::where('type', 'popular_places')->first();
         $selectedPlaceIds = [];
         
         if ($footerSettings) {
             $selectedPlaceIds = json_decode($footerSettings->value, true) ?? [];
         }
         
         // Get the actual place data for the selected IDs
         $places = PopularPlace::whereIn('id', $selectedPlaceIds)->get();
         
         return response()->json($places);
     }
}
