<?php

namespace App\Http\Controllers;

use App\Services\AdobeCarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdobeController extends Controller
{
    public function __construct(private AdobeCarService $adobeCarService)
    {
    }

    /**
     * Get dropoff locations for Adobe provider
     * Adobe typically allows same location returns, so return empty array
     */
    public function getDropoffLocationsForProvider(Request $request, $location_id)
    {
        // Adobe dropoff is same as pickup
        return response()->json([
            'locations' => [],
            'message' => 'Adobe dropoff location will be the same as pickup location.'
        ]);
    }

    /**
     * Get Adobe vehicle availability
     */
    public function getVehicles(Request $request)
    {
        try {
            $validated = $request->validate([
                'pickupoffice' => 'required|string',
                'returnoffice' => 'required|string',
                'startdate' => 'required|string',
                'enddate' => 'required|string',
                'promotionCode' => 'nullable|string'
            ]);

            $vehicles = $this->adobeCarService->getAvailableVehicles($validated);

            return response()->json([
                'success' => true,
                'data' => $vehicles
            ]);

        } catch (\Exception $e) {
            Log::error('Adobe getVehicles error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch Adobe vehicles'
            ], 500);
        }
    }

    /**
     * Get Adobe vehicle details including protections and extras
     */
    public function getVehicleDetails(Request $request)
    {
        try {
            $validated = $request->validate([
                'location_code' => 'required|string',
                'category' => 'required|string',
                'startdate' => 'required|string',
                'enddate' => 'required|string'
            ]);

            $details = $this->adobeCarService->getProtectionsAndExtras(
                $validated['location_code'],
                $validated['category'],
                [
                    'startdate' => $validated['startdate'],
                    'enddate' => $validated['enddate']
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $details
            ]);

        } catch (\Exception $e) {
            Log::error('Adobe getVehicleDetails error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch Adobe vehicle details'
            ], 500);
        }
    }
}