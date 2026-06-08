<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LocationSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnifiedLocationController extends Controller
{
    public function __invoke(Request $request, LocationSearchService $locations): JsonResponse
    {
        try {
            $validated = $request->validate([
                'search_term' => 'sometimes|string|min:2',
                'limit' => 'sometimes|integer|min:1|max:50',
                'unified_location_id' => 'sometimes|integer',
            ]);

            if (! empty($validated['unified_location_id'])) {
                $location = $locations->getLocationByUnifiedId((int) $validated['unified_location_id']);

                return response()->json($location ? [$location] : []);
            }

            $searchTerm = $validated['search_term'] ?? null;
            $limit = (int) ($validated['limit'] ?? 20);

            if ($searchTerm) {
                return response()->json($locations->searchLocations($searchTerm, $limit));
            }

            return response()->json($locations->getAllLocations($limit));
        } catch (\Throwable $exception) {
            try {
                report($exception);
            } catch (\Throwable) {
                // Keep public autocomplete resilient even if production logging is misconfigured.
            }

            return response()->json([]);
        }
    }
}
