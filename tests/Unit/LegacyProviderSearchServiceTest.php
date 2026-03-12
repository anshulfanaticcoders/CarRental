<?php

namespace Tests\Unit;

use App\Services\AdobeCarService;
use App\Services\FavricaService;
use App\Services\GreenMotionService;
use App\Services\LocautoRentService;
use App\Services\LocationSearchService;
use App\Services\OkMobilityService;
use App\Services\PriceVerificationService;
use App\Services\RecordGoService;
use App\Services\RenteonService;
use App\Services\Search\InternalVehicleMergeService;
use App\Services\Search\LegacyProviderSearchService;
use App\Services\Search\SearchOrchestratorService;
use App\Services\SicilyByCarService;
use App\Services\SurpriceService;
use App\Services\WheelsysService;
use App\Services\XDriveService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LegacyProviderSearchServiceTest extends TestCase
{
    #[Test]
    public function it_returns_empty_legacy_props_when_the_search_orchestrator_reports_errors(): void
    {
        $orchestratorService = $this->createMock(SearchOrchestratorService::class);
        $orchestratorService->expects($this->once())
            ->method('resolveProviderEntries')
            ->willReturn([
                'providerName' => 'mixed',
                'matchedLocation' => null,
                'providerEntries' => [],
                'errors' => ['location source unavailable'],
                'isOneWay' => false,
            ]);

        $service = new LegacyProviderSearchService(
            $this->createMock(GreenMotionService::class),
            $this->createMock(OkMobilityService::class),
            $this->createMock(LocationSearchService::class),
            $this->createMock(AdobeCarService::class),
            $this->createMock(WheelsysService::class),
            $this->createMock(LocautoRentService::class),
            $this->createMock(RenteonService::class),
            $this->createMock(FavricaService::class),
            $this->createMock(XDriveService::class),
            $this->createMock(SicilyByCarService::class),
            $this->createMock(RecordGoService::class),
            $this->createMock(SurpriceService::class),
            $orchestratorService,
            $this->createMock(InternalVehicleMergeService::class),
            $this->createMock(PriceVerificationService::class),
        );

        $props = $service->buildPageProps(
            Request::create('/en/s', 'GET'),
            [
                'unified_location_id' => 4135592672,
                'where' => 'Marrakech Airport',
                'provider' => 'mixed',
                'date_from' => '2026-05-12',
                'date_to' => '2026-05-16',
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
            ]
        );

        $this->assertSame(false, $props['via_gateway']);
        $this->assertSame('Search locations are temporarily unavailable.', $props['searchError']);
        $this->assertSame([], $props['providerStatus']);
        $this->assertSame([], $props['optionalExtras']);
        $this->assertNull($props['locationName']);
        $this->assertInstanceOf(LengthAwarePaginator::class, $props['vehicles']);
        $this->assertSame(0, $props['vehicles']->total());
    }
}
