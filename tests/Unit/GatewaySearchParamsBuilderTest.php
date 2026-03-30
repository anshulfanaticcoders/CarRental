<?php

namespace Tests\Unit;

use App\Services\GatewaySearchParamsBuilder;
use App\Services\LocationSearchService;
use PHPUnit\Framework\TestCase;

class GatewaySearchParamsBuilderTest extends TestCase
{
    public function test_it_builds_gateway_search_params_without_forcing_currency(): void
    {
        $locationSearchService = $this->createMock(LocationSearchService::class);
        $locationSearchService->expects($this->once())
            ->method('resolveSearchLocation')
            ->with($this->arrayHasKey('unified_location_id'))
            ->willReturn(null);

        $builder = new GatewaySearchParamsBuilder($locationSearchService);

        $params = $builder->build([
            'unified_location_id' => 4135592672,
            'date_from' => '2026-05-12',
            'date_to' => '2026-05-16',
            'start_time' => '09:00',
            'end_time' => '09:00',
            'age' => 35,
            'currency' => 'USD',
        ]);

        $this->assertSame([
            'unified_location_id' => 4135592672,
            'pickup_date' => '2026-05-12',
            'dropoff_date' => '2026-05-16',
            'pickup_time' => '09:00',
            'dropoff_time' => '09:00',
            'driver_age' => 35,
        ], $params);
        $this->assertArrayNotHasKey('currency', $params);
    }

    public function test_it_adds_dropoff_unified_location_when_present(): void
    {
        $locationSearchService = $this->createMock(LocationSearchService::class);
        $locationSearchService->expects($this->once())
            ->method('resolveSearchLocation')
            ->with($this->arrayHasKey('unified_location_id'))
            ->willReturn(null);

        $builder = new GatewaySearchParamsBuilder($locationSearchService);

        $params = $builder->build([
            'unified_location_id' => 4135592672,
            'dropoff_unified_location_id' => 4135592672,
        ]);

        $this->assertSame(4135592672, $params['dropoff_unified_location_id']);
    }

    public function test_it_includes_provider_locations_when_resolved_from_fallback_search_context(): void
    {
        $locationSearchService = $this->createMock(LocationSearchService::class);
        $locationSearchService->expects($this->once())
            ->method('resolveSearchLocation')
            ->with([
                'unified_location_id' => 2701212940,
                'provider' => 'mixed',
                'provider_pickup_id' => '59610',
            ])
            ->willReturn([
                'unified_location_id' => 3385755165,
                'country_code' => 'AE',
                'providers' => [
                    [
                        'provider' => 'greenmotion',
                        'pickup_id' => '59610',
                        'original_name' => 'Dubai Airport Terminal 1',
                        'dropoffs' => ['59610', '59611'],
                        'supports_one_way' => true,
                        'extended_location_code' => 'DXB-T1',
                        'extended_dropoff_code' => 'SHJ-T1',
                    ],
                    [
                        'provider' => 'surprice',
                        'pickup_id' => 'DXB',
                        'original_name' => 'Dubai Airport',
                    ],
                ],
            ]);

        $builder = new GatewaySearchParamsBuilder($locationSearchService);

        $params = $builder->build([
            'unified_location_id' => 2701212940,
            'provider' => 'mixed',
            'provider_pickup_id' => '59610',
        ]);

        $this->assertSame('AE', $params['country_code']);
        $this->assertCount(2, $params['provider_locations']);
        $this->assertSame('59610', $params['provider_locations'][0]['pickup_id']);
        $this->assertSame(['59610', '59611'], $params['provider_locations'][0]['dropoffs']);
        $this->assertTrue($params['provider_locations'][0]['supports_one_way']);
        $this->assertSame('DXB-T1', $params['provider_locations'][0]['extended_location_code']);
        $this->assertSame('SHJ-T1', $params['provider_locations'][0]['extended_dropoff_code']);
    }

    public function test_it_filters_provider_locations_for_provider_specific_gateway_searches(): void
    {
        $locationSearchService = $this->createMock(LocationSearchService::class);
        $locationSearchService->expects($this->once())
            ->method('resolveSearchLocation')
            ->with([
                'unified_location_id' => 4135592672,
                'provider' => 'greenmotion',
                'provider_pickup_id' => '359',
            ])
            ->willReturn([
                'unified_location_id' => 4135592672,
                'country_code' => 'MA',
                'providers' => [
                    [
                        'provider' => 'greenmotion',
                        'pickup_id' => '359',
                        'original_name' => 'Marrakech Airport',
                    ],
                    [
                        'provider' => 'usave',
                        'pickup_id' => '359',
                        'original_name' => 'Marrakech Airport',
                    ],
                ],
            ]);

        $builder = new GatewaySearchParamsBuilder($locationSearchService);

        $params = $builder->build([
            'unified_location_id' => 4135592672,
            'provider' => 'greenmotion',
            'provider_pickup_id' => '359',
        ]);

        $this->assertSame('MA', $params['country_code']);
        $this->assertCount(1, $params['provider_locations']);
        $this->assertSame('greenmotion', $params['provider_locations'][0]['provider']);
    }

    public function test_it_filters_provider_locations_to_the_exact_selected_pickup_id_when_present(): void
    {
        $locationSearchService = $this->createMock(LocationSearchService::class);
        $locationSearchService->expects($this->once())
            ->method('resolveSearchLocation')
            ->with([
                'unified_location_id' => 3038777513,
                'provider' => 'internal',
                'provider_pickup_id' => '2',
            ])
            ->willReturn([
                'unified_location_id' => 3038777513,
                'country_code' => 'BE',
                'providers' => [
                    [
                        'provider' => 'internal',
                        'pickup_id' => '2',
                        'original_name' => 'Antwerp Downtown A',
                    ],
                    [
                        'provider' => 'internal',
                        'pickup_id' => '4',
                        'original_name' => 'Antwerp Downtown B',
                    ],
                ],
            ]);

        $builder = new GatewaySearchParamsBuilder($locationSearchService);

        $params = $builder->build([
            'unified_location_id' => 3038777513,
            'provider' => 'internal',
            'provider_pickup_id' => '2',
        ]);

        $this->assertSame('BE', $params['country_code']);
        $this->assertCount(1, $params['provider_locations']);
        $this->assertSame('internal', $params['provider_locations'][0]['provider']);
        $this->assertSame('2', $params['provider_locations'][0]['pickup_id']);
    }
}
