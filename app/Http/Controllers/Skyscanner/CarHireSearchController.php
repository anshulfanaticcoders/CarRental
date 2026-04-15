<?php

namespace App\Http\Controllers\Skyscanner;

use App\Http\Controllers\Controller;
use App\Services\Skyscanner\CarHirePublicResponseSerializer;
use App\Services\Skyscanner\CarHireSecurityService;
use App\Services\Skyscanner\CarHireSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarHireSearchController extends Controller
{
    public function __construct(
        private readonly CarHireSearchService $searchService,
        private readonly CarHireSecurityService $securityService,
        private readonly CarHirePublicResponseSerializer $publicResponseSerializer,
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

        $payload = $this->normalizePayload($request);

        $validated = validator($payload, [
            'pickup_location_id' => ['required', 'integer'],
            'dropoff_location_id' => ['nullable', 'integer'],
            'pickup_date' => ['required', 'date'],
            'pickup_time' => ['required', 'date_format:H:i'],
            'dropoff_date' => ['required', 'date', 'after_or_equal:pickup_date'],
            'dropoff_time' => ['required', 'date_format:H:i'],
            'driver_age' => ['nullable', 'integer', 'min:18'],
            'currency' => ['nullable', 'string', 'size:3'],
        ])->validate();

        return response()->json(
            $this->publicResponseSerializer->searchPayload(
                $this->searchService->search($validated),
            ),
        );
    }

    private function normalizePayload(Request $request): array
    {
        if (strtoupper($request->method()) !== 'GET') {
            return $request->all();
        }

        $pickupDateTime = $this->splitDateTime((string) $request->route('pickup_datetime', ''));
        $dropoffDateTime = $this->splitDateTime((string) $request->route('dropoff_datetime', ''));

        return array_filter([
            'pickup_location_id' => $request->route('pickup_point'),
            'dropoff_location_id' => $request->route('dropoff_point'),
            'pickup_date' => $pickupDateTime['date'] ?? null,
            'pickup_time' => $pickupDateTime['time'] ?? null,
            'dropoff_date' => $dropoffDateTime['date'] ?? null,
            'dropoff_time' => $dropoffDateTime['time'] ?? null,
            'driver_age' => $request->route('driver_age'),
            'currency' => strtoupper((string) $request->route('currency', '')),
        ], static fn ($value) => $value !== null && $value !== '');
    }

    private function splitDateTime(string $value): array
    {
        $normalized = trim($value);
        if ($normalized === '' || !str_contains($normalized, 'T')) {
            return [];
        }

        [$date, $time] = array_pad(explode('T', $normalized, 2), 2, null);

        return [
            'date' => $date,
            'time' => $time,
        ];
    }
}
