<?php

namespace App\Http\Controllers\Skyscanner;

use App\Http\Controllers\Controller;
use App\Services\Skyscanner\CarHireLocationsService;
use App\Services\Skyscanner\CarHireSecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarHireLocationsController extends Controller
{
    public function __construct(
        private readonly CarHireLocationsService $locationsService,
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

        return response()->json([
            'data' => $this->locationsService->export(),
        ]);
    }
}
