<?php

namespace App\Services\Search;

use App\Services\GatewaySearchParamsBuilder;
use App\Services\GatewayVehiclePresentationService;
use App\Services\LocationSearchService;
use App\Services\PriceVerificationService;
use App\Services\VrooemGatewayService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GatewaySearchService
{
    public function __construct(
        private readonly LocationSearchService $locationSearchService,
        private readonly GatewaySearchParamsBuilder $gatewaySearchParamsBuilder,
        private readonly VrooemGatewayService $gatewayService,
        private readonly GatewayVehiclePresentationService $presentationService,
        private readonly SearchOrchestratorService $searchOrchestratorService,
        private readonly InternalVehicleMergeService $internalVehicleMergeService,
        private readonly PriceVerificationService $priceVerificationService,
    ) {
    }

    public function buildPageProps(
        Request $request,
        array $validated,
        int $rentalDays,
        Collection $internalVehiclesCollection,
        array $pageMeta,
        callable $transformVehicle,
        callable $normalizeSupplierId
    ): array {
        $locationName = $validated['where'] ?? 'Selected Location';
        $matchedLocation = $this->locationSearchService->resolveSearchLocation($validated);
        if (!empty($validated['unified_location_id'])) {
            $selectedLocation = $this->locationSearchService->getLocationByUnifiedId((int) $validated['unified_location_id']);
            $locationName = $selectedLocation['name'] ?? $locationName;
        }

        $gatewayParams = $this->gatewaySearchParamsBuilder->build($validated);
        $gatewayResult = $this->gatewayService->searchVehicles($gatewayParams);

        if (empty($gatewayResult) || empty($gatewayResult['vehicles'])) {
            Log::info('VrooemGateway: No vehicles returned or gateway error');
            $providerVehicles = collect();
            $providerStatus = $this->mapGatewayProviderStatus($gatewayResult ?? [], $normalizeSupplierId);
        } else {
            Log::info('VrooemGateway: Raw vehicle count from gateway', ['count' => count($gatewayResult['vehicles'])]);

            $transformErrors = [];
            $transformedVehicles = collect($gatewayResult['vehicles'])->map(function ($gatewayVehicle) use ($rentalDays, $transformVehicle, &$transformErrors) {
                try {
                    return $transformVehicle($gatewayVehicle, $rentalDays);
                } catch (\Throwable $e) {
                    $transformErrors[] = ['vehicle_id' => $gatewayVehicle['id'] ?? 'unknown', 'error' => $e->getMessage()];
                    return null;
                }
            })->filter()->values();

            if (!empty($transformErrors)) {
                Log::warning('VrooemGateway: Transform errors', ['errors' => $transformErrors]);
            }

            $matchedLatitude = $matchedLocation['latitude'] ?? null;
            $matchedLongitude = $matchedLocation['longitude'] ?? null;
            $validatedLatitude = $validated['latitude'] ?? null;
            $validatedLongitude = $validated['longitude'] ?? null;

            if ($this->isUsableCoordinatePair($matchedLatitude, $matchedLongitude)) {
                $fallbackLatitude = $matchedLatitude;
                $fallbackLongitude = $matchedLongitude;
            } else {
                $fallbackLatitude = $validatedLatitude;
                $fallbackLongitude = $validatedLongitude;
            }
            $fallbackAppliedCount = 0;

            $transformedVehicles = $transformedVehicles->map(function ($vehicle) use ($fallbackLatitude, $fallbackLongitude, &$fallbackAppliedCount) {
                $v = is_array($vehicle) ? $vehicle : (array) $vehicle;
                if (!$this->requiresCoordinateFallback($v) || !$this->isUsableCoordinatePair($fallbackLatitude, $fallbackLongitude)) {
                    return $v;
                }

                $v['latitude'] = (float) $fallbackLatitude;
                $v['longitude'] = (float) $fallbackLongitude;
                $fallbackAppliedCount++;

                return $v;
            })->values();

            if ($fallbackAppliedCount > 0) {
                Log::info('VrooemGateway: Applied coordinate fallback', [
                    'count' => $fallbackAppliedCount,
                    'fallback_latitude' => (float) $fallbackLatitude,
                    'fallback_longitude' => (float) $fallbackLongitude,
                ]);
            }

            Log::info('VrooemGateway: After transform', ['count' => $transformedVehicles->count(), 'sources' => $transformedVehicles->pluck('source')->countBy()->all()]);

            $transformedVehicles = $this->presentationService
                ->collapseEquivalentRenteonVehicles($transformedVehicles);
            Log::info('VrooemGateway: After Renteon collapse', ['count' => $transformedVehicles->count()]);

            $providerVehicles = $this->searchOrchestratorService
                ->filterGatewayVehiclesForRequestedProvider($transformedVehicles, $validated);
            Log::info('VrooemGateway: After requested-provider filter', [
                'count' => $providerVehicles->count(),
                'provider' => $validated['provider'] ?? 'mixed',
            ]);

            $providerStatus = $this->mapGatewayProviderStatus($gatewayResult, $normalizeSupplierId);
        }

        $filteredProviderVehicles = $providerVehicles->filter(function ($vehicle) use ($validated) {
            $v = is_array($vehicle) ? $vehicle : (array) $vehicle;
            if (!empty($validated['seating_capacity']) && ($v['seating_capacity'] ?? null) != $validated['seating_capacity']) return false;
            if (!empty($validated['brand']) && strcasecmp($v['brand'] ?? '', $validated['brand']) != 0) return false;
            if (!empty($validated['transmission']) && strcasecmp($v['transmission'] ?? '', $validated['transmission']) != 0) return false;
            if (!empty($validated['fuel']) && strcasecmp($v['fuel'] ?? '', $validated['fuel']) != 0) return false;
            if (!empty($validated['category_id']) && !is_numeric($validated['category_id'])) {
                if (strcasecmp($v['category'] ?? '', $validated['category_id']) != 0) return false;
            }
            return true;
        });

        $providerVehicles = $this->filterDisplayableVehicles($providerVehicles);
        $filteredProviderVehicles = $this->filterDisplayableVehicles($filteredProviderVehicles);

        Log::info('VrooemGateway: After filtering', [
            'before' => $providerVehicles->count(),
            'after' => $filteredProviderVehicles->count(),
            'filters_applied' => array_filter([
                'seating_capacity' => $validated['seating_capacity'] ?? null,
                'brand' => $validated['brand'] ?? null,
                'transmission' => $validated['transmission'] ?? null,
                'fuel' => $validated['fuel'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
            ]),
        ]);

        $isOneWay = !empty($validated['dropoff_unified_location_id'])
            && $validated['dropoff_unified_location_id'] != $validated['unified_location_id'];
        $internalForMerge = $this->internalVehicleMergeService->forGatewayMerge(
            $internalVehiclesCollection,
            $validated,
            $matchedLocation,
            $isOneWay
        );
        $internalForMerge = $this->filterDisplayableVehicles($internalForMerge);

        $combinedVehicles = $this->deduplicateVehicles(
            $internalForMerge->merge($filteredProviderVehicles)
        );
        Log::info('VrooemGateway: Combined vehicles', [
            'internal' => $internalForMerge->count(),
            'provider' => $filteredProviderVehicles->count(),
            'combined' => $combinedVehicles->count(),
            'isOneWay' => $isOneWay,
        ]);

        $perPage = 500;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $combinedVehicles->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $vehicles = new LengthAwarePaginator(
            $currentItems,
            $combinedVehicles->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $allProviderBrands = $providerVehicles->pluck('brand')->unique()->filter()->values()->all();
        $allProviderSeatingCapacities = $providerVehicles->pluck('seating_capacity')->unique()->filter()->values()->all();
        $allProviderTransmissions = $providerVehicles->pluck('transmission')->unique()->filter()->values()->all();
        $allProviderFuels = $providerVehicles->pluck('fuel')->unique()->filter()->values()->all();

        $combinedBrands = collect($pageMeta['brands'] ?? [])->merge($allProviderBrands)->map(fn ($val) => trim($val))->unique(fn ($val) => strtolower($val))->sort()->values()->all();
        $combinedSeatingCapacities = collect($pageMeta['seatingCapacities'] ?? [])->merge($allProviderSeatingCapacities)->unique()->sort()->values()->all();
        $combinedTransmissions = collect($pageMeta['transmissions'] ?? [])->merge($allProviderTransmissions)->map(fn ($val) => trim($val))->unique(fn ($val) => strtolower($val))->sort()->values()->all();
        $combinedFuels = collect($pageMeta['fuels'] ?? [])->merge($allProviderFuels)->map(fn ($val) => trim($val))->unique(fn ($val) => strtolower($val))->sort()->values()->all();

        $providerCategories = $providerVehicles->pluck('category')->unique()->filter()
            ->map(fn ($cat) => ['id' => $cat, 'name' => ucfirst(is_string($cat) ? $cat : (string) $cat)])
            ->values()->all();
        $categories = collect(array_merge($pageMeta['categories'] ?? [], $providerCategories))->unique('id')->values()->all();

        $searchSessionId = 'search_' . session()->getId() . '_' . now()->timestamp;
        $allVehicles = [];

        foreach ($vehicles->getCollection() as $vehicle) {
            $vehicleArray = is_array($vehicle) ? $vehicle : (array) $vehicle;
            $vehicleArray['source'] = $vehicleArray['source'] ?? 'internal';
            $allVehicles[] = $vehicleArray;
        }
        foreach ($providerVehicles as $vehicle) {
            $allVehicles[] = is_array($vehicle) ? $vehicle : (array) $vehicle;
        }

        $priceMap = $this->priceVerificationService->storeOriginalPrices($searchSessionId, $allVehicles);

        $vehicles = new LengthAwarePaginator(
            $vehicles->getCollection()->map(function ($vehicle) use ($priceMap) {
                $vehicleId = is_array($vehicle) ? ($vehicle['id'] ?? null) : ($vehicle->id ?? null);
                if ($vehicleId && isset($priceMap[$vehicleId])) {
                    if (is_array($vehicle)) {
                        $vehicle['price_hash'] = $priceMap[$vehicleId]['price_hash'];
                    } else {
                        $vehicle->price_hash = $priceMap[$vehicleId]['price_hash'];
                    }
                }
                return $vehicle;
            }),
            $vehicles->total(),
            $vehicles->perPage(),
            $vehicles->currentPage(),
            ['path' => $vehicles->path()]
        );

        $vehicleSources = $vehicles->getCollection()->map(fn ($vehicle) => is_array($vehicle) ? ($vehicle['source'] ?? 'unknown') : ($vehicle->source ?? 'unknown'));
        Log::info('VrooemGateway: FINAL Inertia render', [
            'total_vehicles_in_paginator' => $vehicles->total(),
            'paginator_page_items' => $vehicles->getCollection()->count(),
            'sources_breakdown' => $vehicleSources->countBy()->all(),
        ]);

        return [
            'vehicles' => $vehicles,
            'providerStatus' => $providerStatus,
            'searchError' => null,
            'filters' => $validated,
            'pagination_links' => $vehicles->links('pagination::tailwind')->toHtml(),
            'brands' => $combinedBrands,
            'colors' => $pageMeta['colors'] ?? [],
            'seatingCapacities' => $combinedSeatingCapacities,
            'transmissions' => $combinedTransmissions,
            'fuels' => $combinedFuels,
            'mileages' => $pageMeta['mileages'] ?? [],
            'categories' => $categories,
            'schema' => $pageMeta['schema'] ?? null,
            'seo' => $pageMeta['seo'] ?? [],
            'locale' => $pageMeta['locale'] ?? app()->getLocale(),
            'optionalExtras' => [],
            'locationName' => $locationName,
            'search_session_id' => $searchSessionId,
            'price_map' => $priceMap,
            'via_gateway' => true,
            'gateway_search_id' => $gatewayResult['search_id'] ?? null,
            'gateway_response_time_ms' => $gatewayResult['response_time_ms'] ?? null,
        ];
    }

    private function requiresCoordinateFallback(array $vehicle): bool
    {
        $lat = $vehicle['latitude'] ?? null;
        $lng = $vehicle['longitude'] ?? null;

        if (!$this->isNumericCoordinate($lat) || !$this->isNumericCoordinate($lng)) {
            return true;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        return abs($lat) < 0.000001 && abs($lng) < 0.000001;
    }

    private function isUsableCoordinatePair(mixed $lat, mixed $lng): bool
    {
        if (!$this->isNumericCoordinate($lat) || !$this->isNumericCoordinate($lng)) {
            return false;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if ($lat < -90.0 || $lat > 90.0 || $lng < -180.0 || $lng > 180.0) {
            return false;
        }

        return !(abs($lat) < 0.000001 && abs($lng) < 0.000001);
    }

    private function isNumericCoordinate(mixed $value): bool
    {
        return is_numeric($value);
    }

    private function deduplicateVehicles(Collection $vehicles): Collection
    {
        return $vehicles
            ->unique(function ($vehicle) {
                $v = is_array($vehicle) ? $vehicle : (array) $vehicle;

                $source = strtolower((string) ($v['source'] ?? 'unknown'));
                $identity = $v['gateway_vehicle_id']
                    ?? $v['id']
                    ?? $v['provider_vehicle_id']
                    ?? md5(json_encode($v));

                return $source . '|' . (string) $identity;
            })
            ->values();
    }

    private function filterDisplayableVehicles(Collection $vehicles): Collection
    {
        return $vehicles
            ->filter(function ($vehicle): bool {
                $total = $this->extractVehicleTotal($vehicle);

                return $total !== null && $total > 0;
            })
            ->values();
    }

    private function extractVehicleTotal(mixed $vehicle): ?float
    {
        $vehicle = is_array($vehicle) ? $vehicle : (array) $vehicle;

        $candidates = [
            $vehicle['pricing']['total_price'] ?? null,
            $vehicle['total_price'] ?? null,
            $vehicle['total'] ?? null,
            $vehicle['products'][0]['total'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (!is_numeric($candidate)) {
                continue;
            }

            return (float) $candidate;
        }

        return null;
    }

    private function mapGatewayProviderStatus(array $gatewayResult, callable $normalizeSupplierId): array
    {
        $aggregate = [];

        $mergeStatus = function (array $status) use (&$aggregate): void {
            $provider = trim((string) ($status['provider'] ?? 'unknown'));
            if ($provider === '') {
                $provider = 'unknown';
            }

            $normalizedErrors = collect($status['errors'] ?? [])
                ->filter(fn ($error) => is_string($error) && trim($error) !== '')
                ->map(fn ($error) => trim($error))
                ->values()
                ->all();

            if (!isset($aggregate[$provider])) {
                $aggregate[$provider] = [
                    'provider' => $provider,
                    'status' => ($status['status'] ?? 'ok') === 'error' ? 'error' : 'ok',
                    'vehicles' => (int) ($status['vehicles'] ?? 0),
                    'ms' => $status['ms'] ?? null,
                    'errors' => $normalizedErrors,
                    'failure_type' => $status['failure_type'] ?? null,
                    'stage' => $status['stage'] ?? null,
                    'http_status' => $status['http_status'] ?? null,
                    'provider_code' => $status['provider_code'] ?? null,
                    'retryable' => (bool) ($status['retryable'] ?? false),
                ];

                return;
            }

            $existing = &$aggregate[$provider];
            $existing['vehicles'] += (int) ($status['vehicles'] ?? 0);

            if (isset($status['ms']) && is_numeric($status['ms'])) {
                $existing['ms'] = max((int) ($existing['ms'] ?? 0), (int) $status['ms']);
            }

            $isStructuredFailure = array_key_exists('failure_type', $status)
                || array_key_exists('stage', $status)
                || array_key_exists('http_status', $status)
                || array_key_exists('provider_code', $status)
                || array_key_exists('retryable', $status);

            if ($isStructuredFailure && !empty($normalizedErrors)) {
                $existing['errors'] = $normalizedErrors;
            } else {
                $existing['errors'] = collect(array_merge($existing['errors'], $normalizedErrors))
                    ->unique()
                    ->values()
                    ->all();
            }

            if ($existing['vehicles'] <= 0 && ($status['status'] ?? 'ok') === 'error') {
                $existing['status'] = 'error';
            } elseif ($existing['vehicles'] > 0) {
                $existing['status'] = 'ok';
            }

            foreach (['failure_type', 'stage', 'http_status', 'provider_code'] as $field) {
                if (!empty($status[$field])) {
                    $existing[$field] = $status[$field];
                }
            }

            $existing['retryable'] = $existing['retryable'] || (bool) ($status['retryable'] ?? false);
        };

        collect($gatewayResult['supplier_results'] ?? [])
            ->filter(fn ($item) => is_array($item))
            ->each(function (array $supplierResult) use ($normalizeSupplierId, $mergeStatus): void {
                $providerId = $normalizeSupplierId((string) ($supplierResult['supplier_id'] ?? 'unknown'));
                $mergeStatus([
                    'provider' => $providerId,
                    'status' => empty($supplierResult['error']) ? 'ok' : 'error',
                    'vehicles' => $supplierResult['vehicle_count'] ?? 0,
                    'ms' => $supplierResult['response_time_ms'] ?? null,
                    'errors' => !empty($supplierResult['error']) ? [$supplierResult['error']] : [],
                ]);
            });

        $structured = collect($gatewayResult['provider_status'] ?? [])->filter(fn ($item) => is_array($item));
        if ($structured->isNotEmpty()) {
            $structured->each(function (array $item) use ($normalizeSupplierId, $mergeStatus): void {
                $providerId = $normalizeSupplierId((string) ($item['provider'] ?? 'unknown'));
                $message = trim((string) ($item['message'] ?? ''));
                $mergeStatus([
                    'provider' => $providerId,
                    'status' => $message === '' ? 'ok' : 'error',
                    'vehicles' => 0,
                    'ms' => null,
                    'errors' => $message !== '' ? [$message] : [],
                    'failure_type' => $item['failure_type'] ?? null,
                    'stage' => $item['stage'] ?? null,
                    'http_status' => $item['http_status'] ?? null,
                    'provider_code' => $item['provider_code'] ?? null,
                    'retryable' => $item['retryable'] ?? false,
                ]);
            });
        }

        return array_values($aggregate);
    }
}
