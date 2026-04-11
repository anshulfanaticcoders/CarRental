<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VendorLocation;
use App\Services\CountryCodeResolver;
use Illuminate\Http\JsonResponse;

/**
 * Returns canonical vendor locations for the Vrooem Gateway location sync.
 * The gateway's internal adapter calls GET /api/internal/locations to pull
 * all active internal pickup locations into the unified location database.
 */
class InternalLocationController extends Controller
{
    public function index(): JsonResponse
    {
        $locations = VendorLocation::query()
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->whereHas('vehicles', function ($query) {
                $query->whereIn('status', ['active', 'available']);
            })
            ->get()
            ->map(function ($loc) {
                return [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'location' => $loc->name,
                    'type' => $loc->location_type ?: 'other',
                    'city' => $loc->city,
                    'state' => $loc->state,
                    'country' => $loc->country,
                    'country_code' => $loc->country_code ?: CountryCodeResolver::resolve($loc->country),
                    'latitude' => (float) $loc->latitude,
                    'longitude' => (float) $loc->longitude,
                    'iata' => $loc->iata_code,
                ];
            });

        return response()->json(['data' => $locations]);
    }
}
