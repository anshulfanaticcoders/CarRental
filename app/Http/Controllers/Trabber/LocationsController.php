<?php

namespace App\Http\Controllers\Trabber;

use App\Http\Controllers\Controller;
use App\Services\Trabber\TrabberLocationResolver;
use App\Services\Trabber\TrabberSecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationsController extends Controller
{
    public function __construct(
        private readonly TrabberSecurityService $security,
        private readonly TrabberLocationResolver $locations
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->security->isAuthorized($request)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json([
            'locations' => $this->locations->listLocations()->values(),
        ]);
    }
}
