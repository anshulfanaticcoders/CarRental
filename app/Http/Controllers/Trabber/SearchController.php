<?php

namespace App\Http\Controllers\Trabber;

use App\Http\Controllers\Controller;
use App\Services\Trabber\TrabberSearchService;
use App\Services\Trabber\TrabberSecurityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SearchController extends Controller
{
    public function __construct(
        private readonly TrabberSecurityService $security,
        private readonly TrabberSearchService $search
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->security->isAuthorized($request)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'pickup' => ['required', 'array'],
            'pickup.iata' => ['nullable', 'string', 'size:3'],
            'pickup.latitude' => ['nullable', 'numeric'],
            'pickup.longitude' => ['nullable', 'numeric'],
            'pickup.geoname_id' => ['nullable', 'string', 'max:32'],
            'pickup.vendor_location_id' => ['nullable', 'integer'],
            'dropoff' => ['nullable', 'array'],
            'dropoff.iata' => ['nullable', 'string', 'size:3'],
            'dropoff.latitude' => ['nullable', 'numeric'],
            'dropoff.longitude' => ['nullable', 'numeric'],
            'dropoff.geoname_id' => ['nullable', 'string', 'max:32'],
            'dropoff.vendor_location_id' => ['nullable', 'integer'],
            'pickup_date_time' => ['required', 'date'],
            'dropoff_date_time' => ['required', 'date'],
            'currency' => ['nullable', 'string', 'size:3'],
            'language' => ['nullable', 'string', 'max:8'],
            'user_country' => ['nullable', 'string', 'max:2'],
            'driver_age' => ['nullable', 'integer', 'min:18', 'max:99'],
        ]);

        if (Carbon::parse($validated['dropoff_date_time'])->lessThanOrEqualTo(Carbon::parse($validated['pickup_date_time']))) {
            throw ValidationException::withMessages([
                'dropoff_date_time' => 'The dropoff date time must be after the pickup date time.',
            ]);
        }

        return response()->json($this->search->search($validated));
    }
}
