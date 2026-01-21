<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\ProviderFavorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia; // Added Inertia

class FavoriteController extends Controller
{
    // Add a vehicle to favorites
    public function favourite(Request $request, $locale, Vehicle $vehicle)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $user = Auth::user();
        $user->favorites()->syncWithoutDetaching([$vehicle->id]);

        return response()->json(['message' => 'Vehicle added to favorites']);
    }

    // Remove a vehicle from favorites
    public function unfavourite(Request $request, $locale, Vehicle $vehicle)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $user = Auth::user();
        $user->favorites()->detach($vehicle->id);

        return response()->json(['message' => 'Vehicle removed from favorites']);
    }
    public function getFavorites(Request $request) // Added Request $request
    {
        if (!Auth::check()) {
            // If this route is for an Inertia page, redirect to login.
            // If it's also an API, you might need separate endpoints or content negotiation.
            return redirect()->route('login')->with('error', 'Please log in to view your favorites.');
        }
        $user = Auth::user();

        $paginatedFavorites = $user->favorites()
            ->with(['images', 'vendorProfile'])
            ->paginate(3) // Adjust items per page as needed
            ->withQueryString();


        $paginatedFavorites->getCollection()->transform(function ($vehicle) {
            $vehicle->is_favourite = true;
            return $vehicle;
        });

        $providerFavorites = $user->providerFavorites()
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Profile/Favourites', [
            'favoriteVehicles' => $paginatedFavorites,
            'providerFavorites' => $providerFavorites,
        ]);
    }

    // Get favorite status for the current user
    public function getFavoriteStatus(Request $request, $locale)
    {
        if (!Auth::check()) {
            return response()->json([]); // Return empty array if not logged in
        }
        $user = Auth::user();
        $favoriteIds = $user->favorites()
            ->pluck('vehicles.id')
            ->map(fn ($id) => (string) $id);
        $providerFavoriteKeys = $user->providerFavorites()
            ->pluck('vehicle_key');

        return response()->json($favoriteIds->merge($providerFavoriteKeys)->values());
    }

    public function toggleProviderFavourite(Request $request, $locale)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'vehicle_key' => ['required', 'string'],
            'source' => ['required', 'string'],
            'payload' => ['nullable', 'array'],
        ]);

        $user = Auth::user();
        $favorite = $user->providerFavorites()
            ->where('vehicle_key', $validated['vehicle_key'])
            ->first();

        if ($favorite) {
            $favorite->delete();

            return response()->json([
                'success' => true,
                'action' => 'removed',
            ]);
        }

        $user->providerFavorites()->create([
            'vehicle_key' => $validated['vehicle_key'],
            'source' => $validated['source'],
            'payload' => $validated['payload'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'action' => 'added',
        ]);
    }
}
