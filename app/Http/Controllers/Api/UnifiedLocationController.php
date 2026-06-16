<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LocationSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
                if (! $location) {
                    Log::warning('UnifiedLocations: lookup returned empty', [
                        'unified_location_id' => (int) $validated['unified_location_id'],
                        'gateway_error' => $this->gatewayError($locations),
                    ]);
                }

                return response()->json($location ? [$this->publicLocation($location)] : []);
            }

            $searchTerm = $validated['search_term'] ?? null;
            $limit = (int) ($validated['limit'] ?? 20);

            if ($searchTerm) {
                $results = $locations->searchLocations($searchTerm, $limit);
                if ($results === []) {
                    Log::warning('UnifiedLocations: search returned empty', [
                        'search_term' => $searchTerm,
                        'limit' => $limit,
                        'gateway_error' => $this->gatewayError($locations),
                    ]);
                }

                return response()->json($this->publicLocations($results));
            }

            $results = $locations->getAllLocations($limit);
            if ($results === []) {
                Log::warning('UnifiedLocations: list returned empty', [
                    'limit' => $limit,
                    'gateway_error' => $this->gatewayError($locations),
                ]);
            }

            return response()->json($this->publicLocations($results));
        } catch (ValidationException) {
            return response()->json([]);
        } catch (\Throwable $exception) {
            try {
                Log::error('UnifiedLocations: request failed', [
                    'search_term' => $request->query('search_term'),
                    'limit' => $request->query('limit'),
                    'unified_location_id' => $request->query('unified_location_id'),
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
                report($exception);
            } catch (\Throwable) {
                // Keep public autocomplete resilient even if production logging is misconfigured.
            }

            return response()->json([]);
        }
    }

    private function gatewayError(LocationSearchService $locations): ?array
    {
        try {
            return $locations->lastGatewayError();
        } catch (\Throwable) {
            return null;
        }
    }

    private function publicLocations(array $locations): array
    {
        return collect($locations)
            ->filter(fn ($location) => is_array($location))
            ->map(fn (array $location): array => $this->publicLocation($location))
            ->values()
            ->all();
    }

    private function publicLocation(array $location): array
    {
        $providers = collect($location['providers'] ?? [])->filter(fn ($provider) => is_array($provider));

        return [
            'unified_location_id' => $location['unified_location_id'] ?? null,
            'name' => $location['name'] ?? null,
            'address' => $location['address'] ?? null,
            'city' => $location['city'] ?? null,
            'state' => $location['state'] ?? null,
            'country' => $location['country'] ?? null,
            'country_code' => $location['country_code'] ?? null,
            'iata' => $location['iata'] ?? null,
            'latitude' => $location['latitude'] ?? null,
            'longitude' => $location['longitude'] ?? null,
            'location_type' => $location['location_type'] ?? 'unknown',
            'provider_count' => (int) ($location['provider_count'] ?? $providers->count()),
            'supports_one_way' => $providers->contains(fn (array $provider): bool => (bool) ($provider['supports_one_way'] ?? false)),
        ];
    }
}
