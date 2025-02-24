<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    // Add a vehicle to favorites
    public function favourite(Vehicle $vehicle)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $user = Auth::user();
        $user->favorites()->syncWithoutDetaching([$vehicle->id]);

        return response()->json(['message' => 'Vehicle added to favorites']);
    }

    // Remove a vehicle from favorites
    public function unfavourite(Vehicle $vehicle)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $user = Auth::user();
        $user->favorites()->detach($vehicle->id);

        return response()->json(['message' => 'Vehicle removed from favorites']);
    }
    public function getFavorites()
{
    $user = Auth::user();
    $favorites = $user->favorites()->with(['images'])->get();

    return response()->json($favorites);
}

}