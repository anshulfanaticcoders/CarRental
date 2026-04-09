<?php

namespace App\Http\Controllers\Skyscanner;

use App\Http\Controllers\Controller;
use App\Services\Skyscanner\CarHireSecurityService;
use App\Services\Skyscanner\CarHireSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarHireSearchController extends Controller
{
    public function __construct(
        private readonly CarHireSearchService $searchService,
        private readonly CarHireSecurityService $securityService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $authHeader = (string) config('skyscanner.testing_access.auth_header', 'x-api-key');
        $apiKey = $request->header($authHeader);

        if (!$this->securityService->hasValidApiKey($apiKey)) {
            return response()->json([
                'error' => 'invalid_api_key',
            ], 401);
        }

        $validated = $request->validate([
            'pickup_location_id' => ['required', 'integer'],
            'dropoff_location_id' => ['nullable', 'integer'],
            'pickup_date' => ['required', 'date'],
            'pickup_time' => ['required', 'date_format:H:i'],
            'dropoff_date' => ['required', 'date', 'after_or_equal:pickup_date'],
            'dropoff_time' => ['required', 'date_format:H:i'],
            'driver_age' => ['nullable', 'integer', 'min:18'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        return response()->json($this->searchService->search($validated));
    }
}
