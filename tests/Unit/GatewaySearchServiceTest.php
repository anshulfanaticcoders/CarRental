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
}
