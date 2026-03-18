<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FooterSetting;
use App\Models\PopularPlace;
use App\Services\LocationSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Helpers\ImageCompressionHelper;

class PopularPlacesController extends Controller
{
    protected LocationSearchService $locationSearchService;

    public function __construct(LocationSearchService $locationSearchService)
    {
        $this->locationSearchService = $locationSearchService;
    }

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
            'unified_location_id' => 'required|integer|min:1',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $location = $this->locationSearchService->getLocationByUnifiedId((int) $request->unified_location_id);
        if (!$location) {
            return back()->withErrors([
                'unified_location_id' => 'Selected location was not found in system locations.',
            ])->withInput();
        }

        $place = new PopularPlace();
        $place->place_name = $location['name'] ?? $request->place_name ?? '';
        $place->city = $location['city'] ?? $request->city ?? '';
        $place->state = $location['state'] ?? $request->state ?? '';
        $place->country = $location['country'] ?? $request->country ?? '';
        $place->latitude = $location['latitude'] ?? $request->latitude ?? null;
        $place->longitude = $location['longitude'] ?? $request->longitude ?? null;
        $place->unified_location_id = $request->unified_location_id;

        if ($request->hasFile('image')) {
            $folderName = 'popularPlaces';
            $compressedImageUrl = ImageCompressionHelper::compressImage(
                $request->file('image'),
                $folderName,
                80, // Adjust quality as needed (0-100)
                800, // Optional: Set max width
                600 // Optional: Set max height
            );

            if ($compressedImageUrl) {
                $place->image = Storage::disk('upcloud')->url($compressedImageUrl);
            } else {
                // Handle compression failure, e.g., log error or return an error response
                return back()->withErrors(['image' => 'Failed to compress image.']);
            }
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
            'unified_location_id' => 'required|integer|min:1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $location = $this->locationSearchService->getLocationByUnifiedId((int) $request->unified_location_id);
        if (!$location) {
            return back()->withErrors([
                'unified_location_id' => 'Selected location was not found in system locations.',
            ])->withInput();
        }

        $popularPlace->place_name = $location['name'] ?? $request->place_name ?? $popularPlace->place_name;
        $popularPlace->city = $location['city'] ?? $request->city ?? $popularPlace->city;
        $popularPlace->state = $location['state'] ?? $request->state ?? $popularPlace->state;
        $popularPlace->country = $location['country'] ?? $request->country ?? $popularPlace->country;
        $popularPlace->latitude = $location['latitude'] ?? $request->latitude ?? $popularPlace->latitude;
        $popularPlace->longitude = $location['longitude'] ?? $request->longitude ?? $popularPlace->longitude;
        $popularPlace->unified_location_id = $request->unified_location_id;

        if ($request->hasFile('image')) {
            // Delete old image from UpCloud
            if ($popularPlace->image) {
                $oldImagePath = str_replace(Storage::disk('upcloud')->url(''), '', $popularPlace->image);
                if ($oldImagePath) {
                    Storage::disk('upcloud')->delete($oldImagePath);
                }
            }
    
            $folderName = 'popularPlaces';
            $compressedImageUrl = ImageCompressionHelper::compressImage(
                $request->file('image'),
                $folderName,
                80, // Adjust quality as needed (0-100)
                800, // Optional: Set max width
                600 // Optional: Set max height
            );

            if ($compressedImageUrl) {
                $popularPlace->image = Storage::disk('upcloud')->url($compressedImageUrl);
            } else {
                // Handle compression failure, e.g., log error or return an error response
                return back()->withErrors(['image' => 'Failed to compress image.']);
            }
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
