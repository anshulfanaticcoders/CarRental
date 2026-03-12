<?php

namespace Tests\Unit;

use App\Services\LocationSearchService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocationSearchServiceTest extends TestCase
{
    public function test_it_uses_gateway_search_results_when_location_search_is_enabled(): void
    {
        config([
            'vrooem.location_search_enabled' => true,
            'vrooem.url' => 'http://gateway.test',
            'vrooem.api_key' => 'test-key',
        ]);

        Http::fake([
            'http://gateway.test/api/v1/locations/search*' => Http::response([
                'query' => 'marrakesh',
                'results' => [
                    [
                        'location' => [
                            'unified_location_id' => 3656758232,
                            'name' => 'Marrakech Airport',
                            'city' => 'Marrakech',
                            'country' => 'Morocco',
                            'location_type' => 'airport',
                            'iata' => 'RAK',
                            'providers' => [
                                ['provider' => 'greenmotion', 'pickup_id' => '359'],
                            ],
                            'our_location_id' => 'internal_1',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = app(LocationSearchService::class);

        $results = $service->searchLocations('marrakesh');

        $this->assertCount(1, $results);
        $this->assertSame('Marrakech Airport', $results[0]['name']);
        $this->assertSame('RAK', $results[0]['iata']);
        $this->assertSame('internal_1', $results[0]['our_location_id']);
    }

    public function test_it_fetches_gateway_location_by_unified_id_when_enabled(): void
    {
        config([
            'vrooem.location_search_enabled' => true,
            'vrooem.url' => 'http://gateway.test',
            'vrooem.api_key' => 'test-key',
        ]);

        Http::fake([
            'http://gateway.test/api/v1/locations/3656758232' => Http::response([
                'unified_location_id' => 3656758232,
                'name' => 'Marrakech Airport',
                'city' => 'Marrakech',
                'country' => 'Morocco',
                'location_type' => 'airport',
                'providers' => [
                    ['provider' => 'greenmotion', 'pickup_id' => '359'],
                ],
                'our_location_id' => 'internal_1',
            ], 200),
        ]);

        $service = app(LocationSearchService::class);

        $location = $service->getLocationByUnifiedId(3656758232);

        $this->assertNotNull($location);
        $this->assertSame('Marrakech Airport', $location['name']);
        $this->assertSame('internal_1', $location['our_location_id']);
    }

    public function test_it_fetches_gateway_location_by_provider_id_when_enabled(): void
    {
        config([
            'vrooem.location_search_enabled' => true,
            'vrooem.url' => 'http://gateway.test',
            'vrooem.api_key' => 'test-key',
        ]);

        Http::fake([
            'http://gateway.test/api/v1/locations/by-provider*' => Http::response([
                'unified_location_id' => 3656758232,
                'name' => 'Marrakech Airport',
                'city' => 'Marrakech',
                'country' => 'Morocco',
                'location_type' => 'airport',
                'providers' => [
                    ['provider' => 'greenmotion', 'pickup_id' => '359'],
                ],
                'our_location_id' => 'internal_1',
            ], 200),
        ]);

        $service = app(LocationSearchService::class);

        $location = $service->getLocationByProviderId('359', 'greenmotion');

        $this->assertNotNull($location);
        $this->assertSame('Marrakech Airport', $location['name']);
        $this->assertSame('internal_1', $location['our_location_id']);
    }

    public function test_it_does_not_fallback_to_json_when_gateway_search_is_enabled(): void
    {
        config([
            'vrooem.location_search_enabled' => true,
            'vrooem.url' => 'http://gateway.test',
            'vrooem.api_key' => 'test-key',
        ]);

        File::shouldReceive('exists')->never();
        File::shouldReceive('get')->never();

        Http::fake([
            'http://gateway.test/api/v1/locations/search*' => Http::response(['detail' => 'Gateway unavailable'], 503),
        ]);

        $service = app(LocationSearchService::class);

        $results = $service->searchLocations('marrakesh');

        $this->assertSame([], $results);
    }

    public function test_it_resolves_a_stale_unified_id_from_json_using_the_provider_pickup_id(): void
    {
        config([
            'vrooem.location_search_enabled' => false,
            'vrooem.url' => 'http://gateway.test',
            'vrooem.api_key' => 'test-key',
        ]);

        File::shouldReceive('exists')
            ->twice()
            ->andReturn(true);

        File::shouldReceive('get')
            ->twice()
            ->andReturn(json_encode([
                [
                    'unified_location_id' => 3385755165,
                    'name' => 'Dubai Airport (DXB)',
                    'city' => 'Dubai',
                    'country' => 'United Arab Emirates',
                    'country_code' => 'AE',
                    'providers' => [
                        [
                            'provider' => 'greenmotion',
                            'pickup_id' => '59610',
                            'original_name' => 'Dubai Airport Terminal 1',
                        ],
                        [
                            'provider' => 'surprice',
                            'pickup_id' => 'DXB',
                            'original_name' => 'Dubai Airport',
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR));

        $service = app(LocationSearchService::class);

        $location = $service->resolveSearchLocation([
            'unified_location_id' => 2701212940,
            'provider' => 'mixed',
            'provider_pickup_id' => '59610',
        ]);

        $this->assertNotNull($location);
        $this->assertSame(3385755165, $location['unified_location_id']);
        $this->assertSame('Dubai Airport (DXB)', $location['name']);
    }

    public function test_it_consolidates_duplicate_json_search_results_for_the_dropdown(): void
    {
        config([
            'vrooem.location_search_enabled' => false,
            'vrooem.url' => 'http://gateway.test',
            'vrooem.api_key' => 'test-key',
        ]);

        File::shouldReceive('exists')
            ->once()
            ->andReturn(true);

        File::shouldReceive('get')
            ->once()
            ->andReturn(json_encode([
                [
                    'unified_location_id' => 1,
                    'name' => 'Dubai',
                    'city' => 'Dubai',
                    'country' => 'United Arab Emirates',
                    'country_code' => 'AE',
                    'location_type' => 'unknown',
                    'providers' => [],
                ],
                [
                    'unified_location_id' => 2,
                    'name' => 'Dubai',
                    'city' => 'Dubai',
                    'country' => 'Verenigde Arabische Emiraten',
                    'country_code' => 'AE',
                    'location_type' => 'unknown',
                    'providers' => [],
                ],
                [
                    'unified_location_id' => 3,
                    'name' => 'Dubai Airport',
                    'city' => 'Dubai',
                    'country' => 'United Arab Emirates',
                    'country_code' => 'AE',
                    'location_type' => 'airport',
                    'providers' => [],
                ],
                [
                    'unified_location_id' => 4,
                    'name' => 'Dubai Airport',
                    'city' => 'Dubai',
                    'country' => 'United Arab Emirates',
                    'country_code' => 'AE',
                    'location_type' => 'airport',
                    'iata' => 'DXB',
                    'providers' => [
                        ['provider' => 'surprice', 'pickup_id' => 'DXB'],
                    ],
                ],
                [
                    'unified_location_id' => 5,
                    'name' => 'Dubai Airport',
                    'city' => 'Dubai',
                    'country' => 'United Arab Emirates',
                    'country_code' => 'AE',
                    'location_type' => 'airport',
                    'iata' => 'DWC',
                    'providers' => [
                        ['provider' => 'surprice', 'pickup_id' => 'DWC'],
                    ],
                ],
                [
                    'unified_location_id' => 6,
                    'name' => 'Dubai Port',
                    'city' => 'Dubai',
                    'country' => 'United Arab Emirates',
                    'country_code' => 'AE',
                    'location_type' => 'port',
                    'providers' => [],
                ],
                [
                    'unified_location_id' => 7,
                    'name' => 'Dubai Train',
                    'city' => 'Dubai',
                    'country' => 'United Arab Emirates',
                    'country_code' => 'AE',
                    'location_type' => 'train',
                    'providers' => [],
                ],
                [
                    'unified_location_id' => 8,
                    'name' => 'Dubai Downtown',
                    'city' => 'Dubai',
                    'country' => 'United Arab Emirates',
                    'country_code' => 'AE',
                    'location_type' => 'downtown',
                    'providers' => [],
                ],
            ], JSON_THROW_ON_ERROR));

        $service = app(LocationSearchService::class);

        $results = $service->searchLocations('dubai', 10);

        $this->assertCount(6, $results);
        $this->assertSame('United Arab Emirates', $results[0]['country']);
        $this->assertCount(1, array_values(array_filter($results, fn ($location) => $location['name'] === 'Dubai' && empty($location['iata']))));
        $airportResults = array_values(array_filter($results, fn ($location) => $location['name'] === 'Dubai Airport'));
        $this->assertCount(2, $airportResults);
        $this->assertSame(['DWC', 'DXB'], collect($airportResults)->pluck('iata')->sort()->values()->all());
    }

    public function test_it_normalizes_location_records_to_the_canonical_contract_shape(): void
    {
        $service = app(LocationSearchService::class);
        $method = new \ReflectionMethod(LocationSearchService::class, 'normalizeLocationRecord');
        $method->setAccessible(true);

        $normalized = $method->invoke($service, [
            'unified_location_id' => 3656758232,
            'name' => 'Marrakesh Airport',
            'city' => 'Marrakesh',
            'country_code' => 'ma',
            'iata' => 'rak',
            'providers' => [
                ['provider' => 'greenmotion', 'pickup_id' => 359],
            ],
        ]);

        $this->assertSame(3656758232, $normalized['unified_location_id']);
        $this->assertSame('Marrakesh Airport', $normalized['name']);
        $this->assertSame('Marrakesh', $normalized['city']);
        $this->assertSame('Morocco', $normalized['country']);
        $this->assertSame('MA', $normalized['country_code']);
        $this->assertSame('RAK', $normalized['iata']);
        $this->assertSame('unknown', $normalized['location_type']);
        $this->assertSame([], $normalized['aliases']);
        $this->assertNull($normalized['our_location_id']);
        $this->assertSame(0.0, $normalized['latitude']);
        $this->assertSame(0.0, $normalized['longitude']);
        $this->assertSame([
            [
                'provider' => 'greenmotion',
                'pickup_id' => '359',
                'original_name' => null,
                'latitude' => null,
                'longitude' => null,
            ],
        ], $normalized['providers']);
    }
}
