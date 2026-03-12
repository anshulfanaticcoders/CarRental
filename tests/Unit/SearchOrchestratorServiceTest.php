<?php

namespace Tests\Unit;

use App\Services\LocationSearchService;
use App\Services\Search\SearchOrchestratorService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SearchOrchestratorServiceTest extends TestCase
{
    public function test_it_resolves_provider_entries_from_location_search_service(): void
    {
        $locationSearchService = $this->createMock(LocationSearchService::class);
        $locationSearchService->expects($this->once())
            ->method('resolveSearchLocation')
            ->with([
                'provider' => 'mixed',
                'where' => 'Marrakech Airport',
                'unified_location_id' => 3656758232,
                'dropoff_unified_location_id' => 999999999,
            ])
            ->willReturn([
                'unified_location_id' => 3656758232,
                'name' => 'Marrakech Airport',
                'latitude' => 31.600026,
                'longitude' => -8.024344,
                'providers' => [
                    [
                        'provider' => 'greenmotion',
                        'pickup_id' => '359',
                        'original_name' => 'Marrakech Airport',
                        'latitude' => 31.600026,
                        'longitude' => -8.024344,
                    ],
                    [
                        'provider' => 'okmobility',
                        'pickup_id' => 'OK-1',
                        'original_name' => 'Marrakech Airport',
                        'latitude' => 31.600026,
                        'longitude' => -8.024344,
                    ],
                    [
                        'provider' => 'usave',
                        'pickup_id' => 'US-1',
                        'original_name' => 'Marrakech Airport',
                        'latitude' => 31.600026,
                        'longitude' => -8.024344,
                    ],
                ],
            ]);

        $service = new SearchOrchestratorService($locationSearchService);

        $result = $service->resolveProviderEntries([
            'provider' => 'mixed',
            'where' => 'Marrakech Airport',
            'unified_location_id' => 3656758232,
            'dropoff_unified_location_id' => 999999999,
        ]);

        $this->assertSame('Marrakech Airport', $result['locationAddress']);
        $this->assertTrue($result['isOneWay']);
        $this->assertSame(['greenmotion', 'usave'], array_column($result['providerEntries'], 'provider'));
        $this->assertSame('359', $result['providerEntries'][0]['pickup_id']);
    }

    public function test_it_falls_back_to_provider_pickup_id_when_the_unified_id_is_stale(): void
    {
        $locationSearchService = $this->createMock(LocationSearchService::class);
        $locationSearchService->expects($this->once())
            ->method('resolveSearchLocation')
            ->with([
                'provider' => 'mixed',
                'where' => 'Dubai Airport',
                'unified_location_id' => 2701212940,
                'provider_pickup_id' => '59610',
            ])
            ->willReturn([
                'unified_location_id' => 3385755165,
                'name' => 'Dubai Airport (DXB)',
                'latitude' => 25.24808104894642,
                'longitude' => 55.34509318818677,
                'providers' => [
                    [
                        'provider' => 'greenmotion',
                        'pickup_id' => '59610',
                        'original_name' => 'Dubai Airport Terminal 1',
                        'latitude' => 25.24808104894642,
                        'longitude' => 55.34509318818677,
                    ],
                    [
                        'provider' => 'surprice',
                        'pickup_id' => 'DXB',
                        'original_name' => 'Dubai Airport',
                        'latitude' => 25.2815459,
                        'longitude' => 55.3519485,
                    ],
                ],
            ]);

        $service = new SearchOrchestratorService($locationSearchService);

        $result = $service->resolveProviderEntries([
            'provider' => 'mixed',
            'where' => 'Dubai Airport',
            'unified_location_id' => 2701212940,
            'provider_pickup_id' => '59610',
        ]);

        $this->assertSame('Dubai Airport (DXB)', $result['locationAddress']);
        $this->assertCount(2, $result['providerEntries']);
        $this->assertSame('greenmotion', $result['providerEntries'][0]['provider']);
        $this->assertSame('59610', $result['providerEntries'][0]['pickup_id']);
    }

    public function test_it_filters_gateway_vehicles_to_the_requested_provider(): void
    {
        $service = new SearchOrchestratorService($this->createMock(LocationSearchService::class));

        $vehicles = collect([
            ['source' => 'greenmotion', 'id' => 'gm-1'],
            ['source' => 'usave', 'id' => 'us-1'],
            ['source' => 'greenmotion', 'id' => 'gm-2'],
        ]);

        $filtered = $service->filterGatewayVehiclesForRequestedProvider($vehicles, [
            'provider' => 'greenmotion',
        ]);

        $this->assertInstanceOf(Collection::class, $filtered);
        $this->assertCount(2, $filtered);
        $this->assertSame(['gm-1', 'gm-2'], $filtered->pluck('id')->all());
    }

    public function test_it_excludes_gateway_vehicles_for_internal_only_searches(): void
    {
        $service = new SearchOrchestratorService($this->createMock(LocationSearchService::class));

        $vehicles = collect([
            ['source' => 'greenmotion', 'id' => 'gm-1'],
        ]);

        $filtered = $service->filterGatewayVehiclesForRequestedProvider($vehicles, [
            'provider' => 'internal',
        ]);

        $this->assertCount(0, $filtered);
    }
}
