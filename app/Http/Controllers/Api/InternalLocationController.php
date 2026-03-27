<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\CountryCodeResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Returns unique vehicle locations for the Vrooem Gateway location sync.
 * The gateway's internal adapter calls GET /api/internal/locations to pull
 * all active vehicle parking locations into the unified location database.
 */
class InternalLocationController extends Controller
{
    public function index(): JsonResponse
    {
        $locations = Vehicle::whereIn('status', ['active', 'available'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereNotNull('full_vehicle_address')
            ->where('full_vehicle_address', '!=', '')
            ->select([
                DB::raw('MIN(id) as id'),
                'full_vehicle_address as name',
                'location as location',
                'location_type as type',
                'city',
                'state',
                'country',
                DB::raw('ROUND(latitude, 6) as latitude'),
                DB::raw('ROUND(longitude, 6) as longitude'),
            ])
            ->groupBy('full_vehicle_address', 'location', 'location_type', 'city', 'state', 'country',
                DB::raw('ROUND(latitude, 6)'), DB::raw('ROUND(longitude, 6)'))
            ->get()
            ->map(function ($loc) {
                return [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'location' => $loc->location,
                    'type' => $loc->type ?: 'other',
                    'city' => $loc->city,
                    'state' => $loc->state,
                    'country' => $loc->country,
                    'country_code' => CountryCodeResolver::resolve($loc->country),
                    'latitude' => (float) $loc->latitude,
                    'longitude' => (float) $loc->longitude,
                ];
            });

        return response()->json(['data' => $locations]);
    }
}
