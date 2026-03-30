<?php

namespace Tests\Unit;

use App\Services\GatewaySearchParamsBuilder;
use App\Services\GatewayVehiclePresentationService;
use App\Services\LocationSearchService;
use App\Services\PriceVerificationService;
use App\Services\Search\GatewaySearchService;
use App\Services\Search\InternalVehicleMergeService;
use App\Services\Search\SearchOrchestratorService;
use App\Services\VrooemGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GatewaySearchServiceTest extends TestCase
{
    #[Test]
    public function it_builds_gateway_page_props_when_gateway_returns_no_provider_vehicles(): void
    {
        $locationSearchService = $this->createMock(LocationSearchService::class);
        $locationSearchService->method('resolveSearchLocation')->willReturn([
            'unified_location_id' => 123,
            'name' => 'Marrakech Airport',
            'our_location_id' => null,
        ]);
        $locationSearchService->method('getLocationByUnifiedId')->willReturn([
            'unified_location_id' => 123,
            'name' => 'Marrakech Airport',
        ]);

        $paramsBuilder = $this->createMock(GatewaySearchParamsBuilder::class);
        $paramsBuilder->expects($this->once())
            ->method('build')
            ->willReturn(['unified_location_id' => 123]);

        $gatewayService = $this->createMock(VrooemGatewayService::class);
        $gatewayService->expects($this->once())
            ->method('searchVehicles')
            ->with(['unified_location_id' => 123])
            ->willReturn([
                'vehicles' => [],
                'supplier_results' => [],
                'search_id' => 'search_123',
                'response_time_ms' => 321,
            ]);

        $presentationService = $this->createMock(GatewayVehiclePresentationService::class);
        $searchOrchestratorService = $this->createMock(SearchOrchestratorService::class);

        $internalMergeService = $this->createMock(InternalVehicleMergeService::class);
        $internalMergeService->expects($this->once())
            ->method('forGatewayMerge')
            ->willReturn(collect());

        $priceVerificationService = $this->createMock(PriceVerificationService::class);
        $priceVerificationService->expects($this->once())
            ->method('storeOriginalPrices')
            ->willReturn([]);

        $service = new GatewaySearchService(
            $locationSearchService,
            $paramsBuilder,
            $gatewayService,
            $presentationService,
            $searchOrchestratorService,
            $internalMergeService,
            $priceVerificationService
        );

        $props = $service->buildPageProps(
            Request::create('/en/s', 'GET'),
            [
                'unified_location_id' => 123,
                'where' => 'Marrakech Airport',
                'provider' => 'mixed',
            ],
            4,
            collect(),
            [
                'brands' => [],
                'colors' => [],
                'seatingCapacities' => [],
                'transmissions' => [],
                'fuels' => [],
                'mileages' => [],
                'categories' => [],
                'schema' => null,
                'seo' => ['title' => 'Search'],
                'locale' => 'en',
            ],
            fn (): array => [],
            fn (string $supplierId): string => $supplierId
        );

        $this->assertSame(true, $props['via_gateway']);
        $this->assertSame('search_123', $props['gateway_search_id']);
        $this->assertSame(321, $props['gateway_response_time_ms']);
        $this->assertSame('Marrakech Airport', $props['locationName']);
        $this->assertCount(0, $props['providerStatus']);
        $this->assertInstanceOf(Collection::class, collect($props['vehicles']->items()));
    }

    #[Test]
    public function it_prefers_structured_gateway_provider_status_when_present(): void
    {
        $locationSearchService = $this->createMock(LocationSearchService::class);
        $locationSearchService->method('resolveSearchLocation')->willReturn([
            'unified_location_id' => 123,
            'name' => 'Casablanca Airport',
            'our_location_id' => null,
        ]);
        $locationSearchService->method('getLocationByUnifiedId')->willReturn([
            'unified_location_id' => 123,
            'name' => 'Casablanca Airport',
        ]);

        $paramsBuilder = $this->createMock(GatewaySearchParamsBuilder::class);
        $paramsBuilder->method('build')->willReturn(['unified_location_id' => 123]);

        $gatewayService = $this->createMock(VrooemGatewayService::class);
        $gatewayService->method('searchVehicles')->willReturn([
            'vehicles' => [],
            'provider_status' => [
                [
                    'provider' => 'surprice',
                    'stage' => 'vehicle_search',
                    'failure_type' => 'provider_error',
                    'http_status' => 422,
                    'provider_code' => '225',
                    'message' => 'One way rentals not allowed to this location',
                    'retryable' => false,
                ],
            ],
            'supplier_results' => [
                [
                    'supplier_id' => 'surprice',
                    'vehicle_count' => 0,
                    'response_time_ms' => 120,
                    'error' => 'legacy error that should not be preferred',
                ],
            ],
            'search_id' => 'search_123',
            'response_time_ms' => 321,
        ]);

        $presentationService = $this->createMock(GatewayVehiclePresentationService::class);
        $searchOrchestratorService = $this->createMock(SearchOrchestratorService::class);

        $internalMergeService = $this->createMock(InternalVehicleMergeService::class);
        $internalMergeService->expects($this->once())
            ->method('forGatewayMerge')
            ->willReturn(collect());

        $priceVerificationService = $this->createMock(PriceVerificationService::class);
        $priceVerificationService->expects($this->once())
            ->method('storeOriginalPrices')
            ->willReturn([]);

        $service = new GatewaySearchService(
            $locationSearchService,
            $paramsBuilder,
            $gatewayService,
            $presentationService,
            $searchOrchestratorService,
            $internalMergeService,
            $priceVerificationService
        );

        $props = $service->buildPageProps(
            Request::create('/en/s', 'GET'),
            [
                'unified_location_id' => 123,
                'where' => 'Casablanca Airport',
                'provider' => 'mixed',
            ],
            4,
            collect(),
            [
                'brands' => [],
                'colors' => [],
                'seatingCapacities' => [],
                'transmissions' => [],
                'fuels' => [],
                'mileages' => [],
                'categories' => [],
                'schema' => null,
                'seo' => ['title' => 'Search'],
                'locale' => 'en',
            ],
            fn (): array => [],
            fn (string $supplierId): string => $supplierId
        );

        $this->assertCount(1, $props['providerStatus']);
        $this->assertSame('surprice', $props['providerStatus'][0]['provider']);
        $this->assertSame('error', $props['providerStatus'][0]['status']);
        $this->assertSame(['One way rentals not allowed to this location'], $props['providerStatus'][0]['errors']);
    }

    #[Test]
    public function it_aggregates_duplicate_provider_status_rows_by_provider(): void
    {
        $locationSearchService = $this->createMock(LocationSearchService::class);
        $locationSearchService->method('resolveSearchLocation')->willReturn([
            'unified_location_id' => 123,
            'name' => 'Dubai Downtown',
            'our_location_id' => null,
        ]);
        $locationSearchService->method('getLocationByUnifiedId')->willReturn([
            'unified_location_id' => 123,
            'name' => 'Dubai Downtown',
        ]);

        $paramsBuilder = $this->createMock(GatewaySearchParamsBuilder::class);
        $paramsBuilder->method('build')->willReturn(['unified_location_id' => 123]);

        $gatewayService = $this->createMock(VrooemGatewayService::class);
        $gatewayService->method('searchVehicles')->willReturn([
            'vehicles' => [],
            'supplier_results' => [
                [
                    'supplier_id' => 'okmobility',
                    'vehicle_count' => 0,
                    'response_time_ms' => 1181,
                    'error' => null,
                ],
                [
                    'supplier_id' => 'okmobility',
                    'vehicle_count' => 7,
                    'response_time_ms' => 1209,
                    'error' => null,
                ],
                [
                    'supplier_id' => 'greenmotion',
                    'vehicle_count' => 9,
                    'response_time_ms' => 2044,
                    'error' => null,
                ],
            ],
            'search_id' => 'search_dup',
            'response_time_ms' => 321,
        ]);

        $presentationService = $this->createMock(GatewayVehiclePresentationService::class);
        $searchOrchestratorService = $this->createMock(SearchOrchestratorService::class);

        $internalMergeService = $this->createMock(InternalVehicleMergeService::class);
        $internalMergeService->expects($this->once())
            ->method('forGatewayMerge')
            ->willReturn(collect());

        $priceVerificationService = $this->createMock(PriceVerificationService::class);
        $priceVerificationService->expects($this->once())
            ->method('storeOriginalPrices')
            ->willReturn([]);

        $service = new GatewaySearchService(
            $locationSearchService,
            $paramsBuilder,
            $gatewayService,
            $presentationService,
            $searchOrchestratorService,
            $internalMergeService,
            $priceVerificationService
        );

        $props = $service->buildPageProps(
            Request::create('/en/s', 'GET'),
            [
                'unified_location_id' => 123,
                'where' => 'Dubai Downtown',
                'provider' => 'mixed',
            ],
            4,
            collect(),
            [
                'brands' => [],
                'colors' => [],
                'seatingCapacities' => [],
                'transmissions' => [],
                'fuels' => [],
                'mileages' => [],
                'categories' => [],
                'schema' => null,
                'seo' => ['title' => 'Search'],
                'locale' => 'en',
            ],
            fn (): array => [],
            fn (string $supplierId): string => $supplierId
        );

        $this->assertCount(2, $props['providerStatus']);

        $okmobility = collect($props['providerStatus'])->firstWhere('provider', 'okmobility');
        $this->assertNotNull($okmobility);
        $this->assertSame('ok', $okmobility['status']);
        $this->assertSame(7, $okmobility['vehicles']);
        $this->assertSame(1209, $okmobility['ms']);
        $this->assertSame([], $okmobility['errors']);
    }

    #[Test]
    public function it_keeps_all_sicily_by_car_gateway_rows_in_the_frontend_payload(): void
    {
        $locationSearchService = $this->createMock(LocationSearchService::class);
        $locationSearchService->method('resolveSearchLocation')->willReturn([
            'unified_location_id' => 433440995,
            'name' => 'Fiumicino Airport',
            'our_location_id' => null,
        ]);
        $locationSearchService->method('getLocationByUnifiedId')->willReturn([
            'unified_location_id' => 433440995,
            'name' => 'Fiumicino Airport',
        ]);

        $paramsBuilder = $this->createMock(GatewaySearchParamsBuilder::class);
        $paramsBuilder->method('build')->willReturn(['unified_location_id' => 433440995]);

        $gatewayService = $this->createMock(VrooemGatewayService::class);
        $gatewayService->method('searchVehicles')->willReturn([
            'vehicles' => [
                [
                    'id' => 'sbc-poa',
                    'source' => 'sicily_by_car',
                    'brand' => 'Fiat',
                    'model' => 'Panda',
                    'category' => 'mini',
                    'currency' => 'EUR',
                    'total_price' => 120.00,
                    'price_per_day' => 24.00,
                    'provider_pickup_id' => 'IT011',
                    'provider_return_id' => 'IT011',
                    'payment_type' => 'PayOnArrival',
                ],
                [
                    'id' => 'sbc-pre',
                    'source' => 'sicily_by_car',
                    'brand' => 'Fiat',
                    'model' => 'Panda',
                    'category' => 'mini',
                    'currency' => 'EUR',
                    'total_price' => 120.00,
                    'price_per_day' => 24.00,
                    'provider_pickup_id' => 'IT011',
                    'provider_return_id' => 'IT011',
                    'payment_type' => 'Prepaid',
                ],
            ],
            'supplier_results' => [
                [
                    'supplier_id' => 'sicily_by_car',
                    'vehicle_count' => 2,
                    'response_time_ms' => 120,
                    'error' => null,
                ],
            ],
            'search_id' => 'search_sbc',
            'response_time_ms' => 120,
        ]);

        $presentationService = $this->createMock(GatewayVehiclePresentationService::class);
        $presentationService->expects($this->never())
            ->method('collapseEquivalentSicilyByCarVehicles');
        $presentationService->expects($this->once())
            ->method('collapseEquivalentRenteonVehicles')
            ->willReturnCallback(fn (Collection $vehicles) => $vehicles);

        $searchOrchestratorService = $this->createMock(SearchOrchestratorService::class);
        $searchOrchestratorService->expects($this->once())
            ->method('filterGatewayVehiclesForRequestedProvider')
            ->willReturnCallback(fn (Collection $vehicles) => $vehicles);

        $internalMergeService = $this->createMock(InternalVehicleMergeService::class);
        $internalMergeService->expects($this->once())
            ->method('forGatewayMerge')
            ->willReturn(collect());

        $priceVerificationService = $this->createMock(PriceVerificationService::class);
        $priceVerificationService->expects($this->once())
            ->method('storeOriginalPrices')
            ->willReturn([]);

        $service = new GatewaySearchService(
            $locationSearchService,
            $paramsBuilder,
            $gatewayService,
            $presentationService,
            $searchOrchestratorService,
            $internalMergeService,
            $priceVerificationService
        );

        $props = $service->buildPageProps(
            Request::create('/en/s', 'GET'),
            [
                'unified_location_id' => 433440995,
                'where' => 'Fiumicino Airport',
                'provider' => 'sicily_by_car',
            ],
            5,
            collect(),
            [
                'brands' => [],
                'colors' => [],
                'seatingCapacities' => [],
                'transmissions' => [],
                'fuels' => [],
                'mileages' => [],
                'categories' => [],
                'schema' => null,
                'seo' => ['title' => 'Search'],
                'locale' => 'en',
            ],
            fn (array $vehicle): array => $vehicle,
            fn (string $supplierId): string => $supplierId
        );

        $items = collect($props['vehicles']->items());

        $this->assertCount(2, $items);
        $this->assertSame(['sbc-poa', 'sbc-pre'], $items->pluck('id')->all());
    }

    #[Test]
    public function it_preserves_gateway_provided_recordgo_product_groups(): void
    {
        $locationSearchService = $this->createMock(LocationSearchService::class);
        $locationSearchService->method('resolveSearchLocation')->willReturn([
            'unified_location_id' => 4080882780,
            'name' => 'Loures Airport',
            'our_location_id' => null,
        ]);
        $locationSearchService->method('getLocationByUnifiedId')->willReturn([
            'unified_location_id' => 4080882780,
            'name' => 'Loures Airport',
        ]);

        $paramsBuilder = $this->createMock(GatewaySearchParamsBuilder::class);
        $paramsBuilder->method('build')->willReturn(['unified_location_id' => 4080882780]);

        $gatewayService = $this->createMock(VrooemGatewayService::class);
        $gatewayService->method('searchVehicles')->willReturn([
            'vehicles' => [
                [
                    'id' => 'rg-base',
                    'source' => 'recordgo',
                    'brand' => 'Fiat',
                    'model' => '500',
                    'category' => 'mini',
                    'currency' => 'EUR',
                    'total_price' => 180.00,
                    'price_per_day' => 22.50,
                    'provider_pickup_id' => '35001',
                    'provider_return_id' => '35001',
                    'recordgo_products' => [
                        [
                            'type' => 'BAS',
                            'product_id' => 11,
                            'product_ver' => 1,
                            'rate_prod_ver' => 'A',
                            'total' => 180.00,
                            'price_per_day' => 22.50,
                        ],
                        [
                            'type' => 'PRE',
                            'product_id' => 12,
                            'product_ver' => 1,
                            'rate_prod_ver' => 'B',
                            'total' => 220.00,
                            'price_per_day' => 27.50,
                        ],
                    ],
                    'supplier_data' => [],
                ],
            ],
            'supplier_results' => [
                [
                    'supplier_id' => 'record_go',
                    'vehicle_count' => 1,
                    'response_time_ms' => 140,
                    'error' => null,
                ],
            ],
            'search_id' => 'search_rg',
            'response_time_ms' => 140,
        ]);

        $presentationService = $this->createMock(GatewayVehiclePresentationService::class);
        $presentationService->expects($this->once())
            ->method('collapseEquivalentRenteonVehicles')
            ->willReturnCallback(fn (Collection $vehicles) => $vehicles);

        $searchOrchestratorService = $this->createMock(SearchOrchestratorService::class);
        $searchOrchestratorService->expects($this->once())
            ->method('filterGatewayVehiclesForRequestedProvider')
            ->willReturnCallback(fn (Collection $vehicles) => $vehicles);

        $internalMergeService = $this->createMock(InternalVehicleMergeService::class);
        $internalMergeService->expects($this->once())
            ->method('forGatewayMerge')
            ->willReturn(collect());

        $priceVerificationService = $this->createMock(PriceVerificationService::class);
        $priceVerificationService->expects($this->once())
            ->method('storeOriginalPrices')
            ->willReturn([]);

        $service = new GatewaySearchService(
            $locationSearchService,
            $paramsBuilder,
            $gatewayService,
            $presentationService,
            $searchOrchestratorService,
            $internalMergeService,
            $priceVerificationService
        );

        $props = $service->buildPageProps(
            Request::create('/en/s', 'GET'),
            [
                'unified_location_id' => 4080882780,
                'where' => 'Loures Airport',
                'provider' => 'recordgo',
            ],
            8,
            collect(),
            [
                'brands' => [],
                'colors' => [],
                'seatingCapacities' => [],
                'transmissions' => [],
                'fuels' => [],
                'mileages' => [],
                'categories' => [],
                'schema' => null,
                'seo' => ['title' => 'Search'],
                'locale' => 'en',
            ],
            fn (array $vehicle): array => $vehicle,
            fn (string $supplierId): string => $supplierId
        );

        $item = collect($props['vehicles']->items())->first();

        $this->assertNotNull($item);
        $this->assertArrayHasKey('recordgo_products', $item);
        $this->assertCount(2, $item['recordgo_products']);
        $this->assertSame(['BAS', 'PRE'], array_column($item['recordgo_products'], 'type'));
    }

    #[Test]
    public function it_replaces_zero_zero_coordinates_with_resolved_search_location_coordinates(): void
    {
        $locationSearchService = $this->createMock(LocationSearchService::class);
        $locationSearchService->method('resolveSearchLocation')->willReturn([
            'unified_location_id' => 2023813061,
            'name' => 'Rabat Airport',
            'latitude' => 34.03638573251851,
            'longitude' => -6.748127835819658,
            'our_location_id' => null,
        ]);
        $locationSearchService->method('getLocationByUnifiedId')->willReturn([
            'unified_location_id' => 2023813061,
            'name' => 'Rabat Airport',
        ]);

        $paramsBuilder = $this->createMock(GatewaySearchParamsBuilder::class);
        $paramsBuilder->method('build')->willReturn(['unified_location_id' => 2023813061]);

        $gatewayService = $this->createMock(VrooemGatewayService::class);
        $gatewayService->method('searchVehicles')->willReturn([
            'vehicles' => [
                [
                    'id' => 'renteon-luxgoo-1',
                    'source' => 'renteon',
                    'provider_code' => 'LuxGoo',
                    'latitude' => 0.0,
                    'longitude' => 0.0,
                    'total_price' => 120.0,
                    'price_per_day' => 30.0,
                ],
            ],
            'supplier_results' => [
                [
                    'supplier_id' => 'renteon',
                    'vehicle_count' => 1,
                    'response_time_ms' => 120,
                    'error' => null,
                ],
            ],
            'search_id' => 'search_rabat',
            'response_time_ms' => 120,
        ]);

        $presentationService = $this->createMock(GatewayVehiclePresentationService::class);
        $presentationService->expects($this->once())
            ->method('collapseEquivalentRenteonVehicles')
            ->willReturnCallback(fn (Collection $vehicles) => $vehicles);

        $searchOrchestratorService = $this->createMock(SearchOrchestratorService::class);
        $searchOrchestratorService->expects($this->once())
            ->method('filterGatewayVehiclesForRequestedProvider')
            ->willReturnCallback(fn (Collection $vehicles) => $vehicles);

        $internalMergeService = $this->createMock(InternalVehicleMergeService::class);
        $internalMergeService->expects($this->once())
            ->method('forGatewayMerge')
            ->willReturn(collect());

        $priceVerificationService = $this->createMock(PriceVerificationService::class);
        $priceVerificationService->expects($this->once())
            ->method('storeOriginalPrices')
            ->willReturn([]);

        $service = new GatewaySearchService(
            $locationSearchService,
            $paramsBuilder,
            $gatewayService,
            $presentationService,
            $searchOrchestratorService,
            $internalMergeService,
            $priceVerificationService
        );

        $props = $service->buildPageProps(
            Request::create('/en/s', 'GET'),
            [
                'unified_location_id' => 2023813061,
                'where' => 'Rabat Airport',
                'provider' => 'renteon',
                'latitude' => 34.03638573251851,
                'longitude' => -6.748127835819658,
            ],
            4,
            collect(),
            [
                'brands' => [],
                'colors' => [],
                'seatingCapacities' => [],
                'transmissions' => [],
                'fuels' => [],
                'mileages' => [],
                'categories' => [],
                'schema' => null,
                'seo' => ['title' => 'Search'],
                'locale' => 'en',
            ],
            fn (array $vehicle): array => $vehicle,
            fn (string $supplierId): string => $supplierId
        );

        $item = collect($props['vehicles']->items())->first();

        $this->assertNotNull($item);
        $this->assertSame(34.03638573251851, $item['latitude']);
        $this->assertSame(-6.748127835819658, $item['longitude']);
    }

    #[Test]
    public function it_ignores_zero_zero_resolved_location_coordinates_and_uses_request_coordinates(): void
    {
        $locationSearchService = $this->createMock(LocationSearchService::class);
        $locationSearchService->method('resolveSearchLocation')->willReturn([
            'unified_location_id' => 34903,
            'name' => 'Lanzarote Airport',
            'latitude' => 0.0,
            'longitude' => 0.0,
            'our_location_id' => null,
        ]);
        $locationSearchService->method('getLocationByUnifiedId')->willReturn([
            'unified_location_id' => 34903,
            'name' => 'Lanzarote Airport',
        ]);

        $paramsBuilder = $this->createMock(GatewaySearchParamsBuilder::class);
        $paramsBuilder->method('build')->willReturn(['unified_location_id' => 34903]);

        $gatewayService = $this->createMock(VrooemGatewayService::class);
        $gatewayService->method('searchVehicles')->willReturn([
            'vehicles' => [
                [
                    'id' => 'recordgo-ace-1',
                    'source' => 'recordgo',
                    'latitude' => 0.0,
                    'longitude' => 0.0,
                    'total_price' => 100.0,
                    'price_per_day' => 25.0,
                ],
            ],
            'supplier_results' => [
                [
                    'supplier_id' => 'recordgo',
                    'vehicle_count' => 1,
                    'response_time_ms' => 120,
                    'error' => null,
                ],
            ],
            'search_id' => 'search_lanzarote',
            'response_time_ms' => 120,
        ]);

        $presentationService = $this->createMock(GatewayVehiclePresentationService::class);
        $presentationService->expects($this->once())
            ->method('collapseEquivalentRenteonVehicles')
            ->willReturnCallback(fn (Collection $vehicles) => $vehicles);

        $searchOrchestratorService = $this->createMock(SearchOrchestratorService::class);
        $searchOrchestratorService->expects($this->once())
            ->method('filterGatewayVehiclesForRequestedProvider')
            ->willReturnCallback(fn (Collection $vehicles) => $vehicles);

        $internalMergeService = $this->createMock(InternalVehicleMergeService::class);
        $internalMergeService->expects($this->once())
            ->method('forGatewayMerge')
            ->willReturn(collect());

        $priceVerificationService = $this->createMock(PriceVerificationService::class);
        $priceVerificationService->expects($this->once())
            ->method('storeOriginalPrices')
            ->willReturn([]);

        $service = new GatewaySearchService(
            $locationSearchService,
            $paramsBuilder,
            $gatewayService,
            $presentationService,
            $searchOrchestratorService,
            $internalMergeService,
            $priceVerificationService
        );

        $props = $service->buildPageProps(
            Request::create('/en/s', 'GET'),
            [
                'unified_location_id' => 34903,
                'where' => 'Lanzarote Airport',
                'provider' => 'recordgo',
                'latitude' => 28.9462,
                'longitude' => -13.6052,
            ],
            4,
            collect(),
            [
                'brands' => [],
                'colors' => [],
                'seatingCapacities' => [],
                'transmissions' => [],
                'fuels' => [],
                'mileages' => [],
                'categories' => [],
                'schema' => null,
                'seo' => ['title' => 'Search'],
                'locale' => 'en',
            ],
            fn (array $vehicle): array => $vehicle,
            fn (string $supplierId): string => $supplierId
        );

        $item = collect($props['vehicles']->items())->first();

        $this->assertNotNull($item);
        $this->assertSame(28.9462, $item['latitude']);
        $this->assertSame(-13.6052, $item['longitude']);
    }

    #[Test]
    public function it_deduplicates_internal_vehicles_when_gateway_and_local_merge_return_the_same_vehicle(): void
    {
        $locationSearchService = $this->createMock(LocationSearchService::class);
        $locationSearchService->method('resolveSearchLocation')->willReturn([
            'unified_location_id' => 3038777513,
            'name' => 'Antwerp Downtown',
            'our_location_id' => 'internal_317e002afc63269b03028d6b10b46378',
        ]);
        $locationSearchService->method('getLocationByUnifiedId')->willReturn([
            'unified_location_id' => 3038777513,
            'name' => 'Antwerp Downtown',
        ]);

        $paramsBuilder = $this->createMock(GatewaySearchParamsBuilder::class);
        $paramsBuilder->method('build')->willReturn(['unified_location_id' => 3038777513]);

        $gatewayService = $this->createMock(VrooemGatewayService::class);
        $gatewayService->method('searchVehicles')->willReturn([
            'vehicles' => [
                [
                    'id' => 2,
                    'source' => 'internal',
                    'brand' => 'Toyota',
                    'model' => 'Yaris',
                    'total_price' => 210.00,
                    'price_per_day' => 30.00,
                ],
            ],
            'supplier_results' => [
                [
                    'supplier_id' => 'internal',
                    'vehicle_count' => 1,
                    'response_time_ms' => 50,
                    'error' => null,
                ],
            ],
            'search_id' => 'search_antwerp',
            'response_time_ms' => 50,
        ]);

        $presentationService = $this->createMock(GatewayVehiclePresentationService::class);
        $presentationService->expects($this->once())
            ->method('collapseEquivalentRenteonVehicles')
            ->willReturnCallback(fn (Collection $vehicles) => $vehicles);

        $searchOrchestratorService = $this->createMock(SearchOrchestratorService::class);
        $searchOrchestratorService->expects($this->once())
            ->method('filterGatewayVehiclesForRequestedProvider')
            ->willReturnCallback(fn (Collection $vehicles) => $vehicles);

        $internalMergeService = $this->createMock(InternalVehicleMergeService::class);
        $internalMergeService->expects($this->once())
            ->method('forGatewayMerge')
            ->willReturn(collect([
                [
                    'id' => 2,
                    'source' => 'internal',
                    'brand' => 'Toyota',
                    'model' => 'Yaris',
                    'total_price' => 210.00,
                    'price_per_day' => 30.00,
                ],
            ]));

        $priceVerificationService = $this->createMock(PriceVerificationService::class);
        $priceVerificationService->expects($this->once())
            ->method('storeOriginalPrices')
            ->willReturn([
                2 => ['price_hash' => 'hash_2'],
            ]);

        $service = new GatewaySearchService(
            $locationSearchService,
            $paramsBuilder,
            $gatewayService,
            $presentationService,
            $searchOrchestratorService,
            $internalMergeService,
            $priceVerificationService
        );

        $props = $service->buildPageProps(
            Request::create('/en/s', 'GET'),
            [
                'unified_location_id' => 3038777513,
                'where' => 'Antwerp Downtown',
                'provider' => 'mixed',
            ],
            7,
            collect(),
            [
                'brands' => [],
                'colors' => [],
                'seatingCapacities' => [],
                'transmissions' => [],
                'fuels' => [],
                'mileages' => [],
                'categories' => [],
                'schema' => null,
                'seo' => ['title' => 'Search'],
                'locale' => 'en',
            ],
            fn (array $vehicle): array => $vehicle,
            fn (string $supplierId): string => $supplierId
        );

        $items = collect($props['vehicles']->items());

        $this->assertCount(1, $items);
        $this->assertSame([2], $items->pluck('id')->all());
    }
}
