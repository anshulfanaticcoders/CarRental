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
                    ],
                ]);
        });

        $response = $this->getJson('/api/unified-locations?search_term=dubai&limit=20');

        $response->assertOk()
            ->assertExactJson([
                [
                    'unified_location_id' => 3385755165,
                    'name' => 'Dubai Airport (DXB)',
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
                ]);
        });

        $response = $this->getJson('/api/unified-locations?unified_location_id=3385755165');

        $response->assertOk()
            ->assertExactJson([
                [
                    'unified_location_id' => 3385755165,
                    'name' => 'Dubai Airport (DXB)',
                ],
            ]);
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
