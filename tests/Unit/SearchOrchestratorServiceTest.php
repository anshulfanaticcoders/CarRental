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

    public function test_one_way_filter_keeps_all_gateway_supported_providers(): void
    {
        // Regression guard: providers whose gateway adapter has supports_one_way = True
        // must survive the Laravel-side one-way filter. Ensures the hardcoded list in
        // SearchOrchestratorService stays in sync with vrooem-gateway adapters.
        $locationSearchService = $this->createMock(LocationSearchService::class);
        $locationSearchService->method('resolveSearchLocation')->willReturn([
            'unified_location_id' => 100,
            'name' => 'Test Airport',
            'latitude' => 25.0,
            'longitude' => 55.0,
            'providers' => [
                // One-way supported (10 from gateway adapters)
                ['provider' => 'greenmotion',   'pickup_id' => 'GM-1'],
                ['provider' => 'usave',         'pickup_id' => 'US-1'],
                ['provider' => 'adobe',         'pickup_id' => 'AD-1'],
                ['provider' => 'click2rent',    'pickup_id' => 'C2R-1'],
                ['provider' => 'easirent',      'pickup_id' => 'ES-1'],
                ['provider' => 'locauto_rent',  'pickup_id' => 'LR-1'],
                ['provider' => 'recordgo',      'pickup_id' => 'RG-1'],
                ['provider' => 'renteon',       'pickup_id' => 'RT-1'],
                ['provider' => 'surprice',      'pickup_id' => 'SP-1'],
                ['provider' => 'sicily_by_car', 'pickup_id' => 'SBC-1'],
                // Round-trip only (must be removed)
                ['provider' => 'okmobility',    'pickup_id' => 'OK-1'],
                ['provider' => 'xdrive',        'pickup_id' => 'XD-1'],
                ['provider' => 'favrica',       'pickup_id' => 'FV-1'],
                ['provider' => 'emr',           'pickup_id' => 'EMR-1'],
                ['provider' => 'wheelsys',      'pickup_id' => 'WS-1'],
            ],
        ]);

        $service = new SearchOrchestratorService($locationSearchService);

        $result = $service->resolveProviderEntries([
            'provider' => 'mixed',
            'unified_location_id' => 100,
            'dropoff_unified_location_id' => 200,
        ]);

        $providerIds = array_column($result['providerEntries'], 'provider');

        $this->assertTrue($result['isOneWay']);
        $this->assertEqualsCanonicalizing(
            ['greenmotion', 'usave', 'adobe', 'click2rent', 'easirent', 'locauto_rent', 'recordgo', 'renteon', 'surprice', 'sicily_by_car'],
            $providerIds,
        );
    }

    public function test_one_way_filter_skipped_when_explicit_provider_selected(): void
    {
        // When user explicitly picks a provider, the one-way filter does not run — the
        // gateway will reject the request at the adapter level if the provider cannot
        // fulfill one-way. This test locks in that Laravel passes the entry through.
        $locationSearchService = $this->createMock(LocationSearchService::class);
        $locationSearchService->method('resolveSearchLocation')->willReturn([
            'unified_location_id' => 100,
            'name' => 'Test Airport',
            'providers' => [
                ['provider' => 'okmobility', 'pickup_id' => 'OK-1'],
                ['provider' => 'greenmotion', 'pickup_id' => 'GM-1'],
            ],
        ]);

        $service = new SearchOrchestratorService($locationSearchService);

        $result = $service->resolveProviderEntries([
            'provider' => 'okmobility',
            'unified_location_id' => 100,
            'dropoff_unified_location_id' => 200,
        ]);

        $this->assertTrue($result['isOneWay']);
        $this->assertSame(['okmobility'], array_column($result['providerEntries'], 'provider'));
    }

    public function test_one_way_detection_ignores_identical_pickup_and_dropoff(): void
    {
        $locationSearchService = $this->createMock(LocationSearchService::class);
        $locationSearchService->method('resolveSearchLocation')->willReturn([
            'unified_location_id' => 100,
            'name' => 'Test Airport',
            'providers' => [
                ['provider' => 'greenmotion', 'pickup_id' => 'GM-1'],
                ['provider' => 'okmobility', 'pickup_id' => 'OK-1'],
            ],
        ]);

        $service = new SearchOrchestratorService($locationSearchService);

        $result = $service->resolveProviderEntries([
            'provider' => 'mixed',
            'unified_location_id' => 100,
            'dropoff_unified_location_id' => 100,
        ]);

        $this->assertFalse($result['isOneWay']);
        $this->assertCount(2, $result['providerEntries']);
    }
}
