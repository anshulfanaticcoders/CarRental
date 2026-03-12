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
            $providerStatus = [];
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
            Log::info('VrooemGateway: After transform', ['count' => $transformedVehicles->count(), 'sources' => $transformedVehicles->pluck('source')->countBy()->all()]);

            $transformedVehicles = $this->presentationService
                ->collapseEquivalentSicilyByCarVehicles($transformedVehicles);
            Log::info('VrooemGateway: After SBC collapse', ['count' => $transformedVehicles->count()]);

            $transformedVehicles = $this->presentationService
                ->collapseEquivalentRenteonVehicles($transformedVehicles);
            Log::info('VrooemGateway: After Renteon collapse', ['count' => $transformedVehicles->count()]);

            $providerVehicles = $this->groupRecordGoVehicles($transformedVehicles);
            Log::info('VrooemGateway: After RecordGo grouping', ['count' => $providerVehicles->count()]);

            $providerVehicles = $this->searchOrchestratorService
                ->filterGatewayVehiclesForRequestedProvider($providerVehicles, $validated);
            Log::info('VrooemGateway: After requested-provider filter', [
                'count' => $providerVehicles->count(),
                'provider' => $validated['provider'] ?? 'mixed',
            ]);

            $providerStatus = collect($gatewayResult['supplier_results'] ?? [])->map(function ($supplierResult) use ($normalizeSupplierId) {
                $providerId = $normalizeSupplierId((string) ($supplierResult['supplier_id'] ?? 'unknown'));
                return [
                    'provider' => $providerId,
                    'status' => empty($supplierResult['error']) ? 'ok' : 'error',
                    'vehicles' => $supplierResult['vehicle_count'] ?? 0,
                    'ms' => $supplierResult['response_time_ms'] ?? null,
                    'errors' => !empty($supplierResult['error']) ? [$supplierResult['error']] : [],
                ];
            })->values()->all();
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

        $combinedVehicles = $internalForMerge->merge($filteredProviderVehicles);
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

        $okMobilityVehicles = $providerVehicles->filter(fn ($vehicle) => ($vehicle['source'] ?? '') === 'okmobility');
        $renteonVehicles = $providerVehicles->filter(fn ($vehicle) => ($vehicle['source'] ?? '') === 'renteon');

        $okMobilityVehiclesPaginated = new LengthAwarePaginator(
            $okMobilityVehicles->values(),
            $okMobilityVehicles->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        $renteonVehiclesPaginated = new LengthAwarePaginator(
            $renteonVehicles->values(),
            $renteonVehicles->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $vehicleSources = $vehicles->getCollection()->map(fn ($vehicle) => is_array($vehicle) ? ($vehicle['source'] ?? 'unknown') : ($vehicle->source ?? 'unknown'));
        Log::info('VrooemGateway: FINAL Inertia render', [
            'total_vehicles_in_paginator' => $vehicles->total(),
            'paginator_page_items' => $vehicles->getCollection()->count(),
            'sources_breakdown' => $vehicleSources->countBy()->all(),
            'okMobility_count' => $okMobilityVehicles->count(),
            'renteon_count' => $renteonVehicles->count(),
        ]);

        return [
            'vehicles' => $vehicles,
            'okMobilityVehicles' => $okMobilityVehiclesPaginated,
            'renteonVehicles' => $renteonVehiclesPaginated,
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

    private function groupRecordGoVehicles(Collection $vehicles): Collection
    {
        $recordGoGroups = [];
        $otherVehicles = [];

        foreach ($vehicles as $vehicle) {
            $v = is_array($vehicle) ? $vehicle : (array) $vehicle;
            if (($v['source'] ?? '') !== 'recordgo') {
                $otherVehicles[] = $vehicle;
                continue;
            }

            $acrissCode = $v['sipp_code'] ?? ($v['supplier_data']['acriss_code'] ?? 'unknown');
            $pickupId = $v['provider_pickup_id'] ?? '';
            $groupKey = $pickupId . '_' . $acrissCode;

            if (!isset($recordGoGroups[$groupKey])) {
                $recordGoGroups[$groupKey] = [
                    'base' => $v,
                    'products' => [],
                ];
            }

            $supplierData = $v['supplier_data'] ?? [];
            $productData = $supplierData['product_data'] ?? null;

            if ($productData) {
                $recordGoGroups[$groupKey]['products'][] = $productData;
            }

            $currentBase = $recordGoGroups[$groupKey]['base'];
            if (($v['total_price'] ?? PHP_FLOAT_MAX) < ($currentBase['total_price'] ?? PHP_FLOAT_MAX)) {
                $recordGoGroups[$groupKey]['base'] = $v;
            }
        }

        foreach ($recordGoGroups as $group) {
            $base = $group['base'];
            $base['recordgo_products'] = $group['products'];
            if (!empty($group['products'])) {
                $minTotal = null;
                $minDaily = null;
                foreach ($group['products'] as $product) {
                    $productTotal = (float) ($product['total'] ?? PHP_FLOAT_MAX);
                    if ($minTotal === null || $productTotal < $minTotal) {
                        $minTotal = $productTotal;
                        $minDaily = (float) ($product['price_per_day'] ?? 0);
                    }
                }
                if ($minTotal !== null) {
                    $base['total_price'] = $minTotal;
                    $base['total'] = $minTotal;
                    $base['price_per_day'] = $minDaily;
                    $base['daily_rate'] = $minDaily;
                }
            }
            $otherVehicles[] = $base;
        }

        return collect($otherVehicles);
    }
}
