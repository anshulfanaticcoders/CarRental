<?php

namespace Tests\Feature;

use App\Services\LocationSearchService;
use Mockery;
use Tests\TestCase;

class UnifiedLocationApiTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_unified_location_search_returns_locations(): void
    {
        $this->mock(LocationSearchService::class, function ($mock): void {
            $mock->shouldReceive('searchLocations')
                ->once()
                ->with('dubai', 20)
                ->andReturn([
                    [
                        'unified_location_id' => 3385755165,
                        'name' => 'Dubai Airport (DXB)',
                        'country_code' => 'AE',
                        'provider_count' => 2,
                        'providers' => [
                            [
                                'provider' => 'surprice',
                                'pickup_id' => 'DXB01',
                                'dropoffs' => ['AUH01'],
                                'extended_location_code' => 'SECRET-LOCATION',
                                'supports_one_way' => true,
                            ],
                        ],
                    ],
                ]);
        });

        $response = $this->getJson('/api/unified-locations?search_term=dubai&limit=20');

        $response->assertOk()
            ->assertExactJson([
                [
                    'address' => null,
                    'city' => null,
                    'country' => null,
                    'country_code' => 'AE',
                    'iata' => null,
                    'latitude' => null,
                    'location_type' => 'unknown',
                    'longitude' => null,
                    'unified_location_id' => 3385755165,
                    'name' => 'Dubai Airport (DXB)',
                    'provider_count' => 2,
                    'state' => null,
                    'supports_one_way' => true,
                ],
            ]);
    }

    public function test_unified_location_lookup_by_id_returns_single_location_array(): void
    {
        $this->mock(LocationSearchService::class, function ($mock): void {
            $mock->shouldReceive('getLocationByUnifiedId')
                ->once()
                ->with(3385755165)
                ->andReturn([
                    'unified_location_id' => 3385755165,
                    'name' => 'Dubai Airport (DXB)',
                    'provider_count' => 1,
                    'providers' => [
                        [
                            'provider' => 'greenmotion',
                            'pickup_id' => '59610',
                            'dropoffs' => ['59611'],
                            'extended_dropoff_code' => 'SECRET-DROPOFF',
                            'supports_one_way' => false,
                        ],
                    ],
                ]);
        });

        $response = $this->getJson('/api/unified-locations?unified_location_id=3385755165');

        $response->assertOk()
            ->assertExactJson([
                [
                    'address' => null,
                    'city' => null,
                    'country' => null,
                    'country_code' => null,
                    'iata' => null,
                    'latitude' => null,
                    'location_type' => 'unknown',
                    'longitude' => null,
                    'unified_location_id' => 3385755165,
                    'name' => 'Dubai Airport (DXB)',
                    'provider_count' => 1,
                    'state' => null,
                    'supports_one_way' => false,
                ],
            ]);
    }

    public function test_unified_location_endpoint_hides_internal_provider_mappings(): void
    {
        $this->mock(LocationSearchService::class, function ($mock): void {
            $mock->shouldReceive('searchLocations')
                ->once()
                ->with('dubai', 20)
                ->andReturn([
                    [
                        'unified_location_id' => 3385755165,
                        'name' => 'Dubai Airport (DXB)',
                        'providers' => [
                            [
                                'provider' => 'surprice',
                                'pickup_id' => 'DXB01',
                                'dropoffs' => ['AUH01'],
                                'extended_location_code' => 'SECRET-LOCATION',
                                'extended_dropoff_code' => 'SECRET-DROPOFF',
                                'provider_code' => 'SECRET-PROVIDER',
                                'supports_one_way' => true,
                            ],
                        ],
                    ],
                ]);
        });

        $response = $this->getJson('/api/unified-locations?search_term=dubai&limit=20');

        $response->assertOk()
            ->assertJsonMissingPath('0.providers')
            ->assertJsonMissingPath('0.pickup_id')
            ->assertJsonMissingPath('0.dropoffs')
            ->assertJsonPath('0.provider_count', 1)
            ->assertJsonPath('0.supports_one_way', true);
    }

    public function test_unified_location_endpoint_returns_empty_array_when_lookup_fails(): void
    {
        $this->mock(LocationSearchService::class, function ($mock): void {
            $mock->shouldReceive('searchLocations')
                ->once()
                ->with('dubai', 20)
                ->andThrow(new \RuntimeException('Gateway unavailable'));
        });

        $response = $this->getJson('/api/unified-locations?search_term=dubai&limit=20');

        $response->assertOk()
            ->assertExactJson([]);
    }

    public function test_unified_location_endpoint_returns_empty_array_for_invalid_short_search(): void
    {
        $response = $this->getJson('/api/unified-locations?search_term=x&limit=20');

        $response->assertOk()
            ->assertExactJson([]);
    }
}
