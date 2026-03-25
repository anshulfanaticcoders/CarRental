<?php

namespace Tests\Unit;

use App\Services\LocationSearchService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocationSearchServiceTest extends TestCase
{
    public function test_it_prefers_gateway_location_search_when_gateway_is_enabled(): void
    {
        config([
            'vrooem.enabled' => true,
            'vrooem.url' => 'http://gateway.test',
            'vrooem.api_key' => 'test-key',
        ]);

        Http::fake([
            'http://gateway.test/api/v1/locations/search*' => Http::response([
                'query' => 'dubai',
                'results' => [
                    [
                        'location' => [
                            'unified_location_id' => 2701212940,
                            'name' => 'Dubai Airport',
                            'city' => 'Dubai',
                            'country' => 'United Arab Emirates',
                            'location_type' => 'airport',
                            'providers' => [
                                ['provider' => 'greenmotion', 'pickup_id' => '59610'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = app(LocationSearchService::class);

        $results = $service->searchLocations('dubai');

        $this->assertCount(1, $results);
        $this->assertSame('Dubai Airport', $results[0]['name']);
        $this->assertSame(2701212940, $results[0]['unified_location_id']);
    }

    public function test_it_uses_gateway_search_results_when_gateway_is_enabled(): void
    {
        config([
            'vrooem.enabled' => true,
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

    public function test_it_collapses_same_iata_gateway_search_results_and_merges_providers(): void
    {
        config([
            'vrooem.enabled' => true,
            'vrooem.url' => 'http://gateway.test',
            'vrooem.api_key' => 'test-key',
        ]);

        Http::fake([
            'http://gateway.test/api/v1/locations/search*' => Http::response([
                'query' => 'casablanca airport',
                'results' => [
                    [
                        'location' => [
                            'unified_location_id' => 2962361044,
                            'name' => 'Casablanca Airport',
                            'city' => 'Casablanca',
                            'country' => 'Morocco',
                            'location_type' => 'airport',
                            'iata' => 'CMN',
                            'providers' => [
                                ['provider' => 'greenmotion', 'pickup_id' => '354'],
                                ['provider' => 'usave', 'pickup_id' => '354'],
                            ],
                        ],
                    ],
                    [
                        'location' => [
                            'unified_location_id' => 4103471891,
                            'name' => 'Nouaceur Airport',
                            'city' => 'Nouaceur',
                            'country' => 'Morocco',
                            'location_type' => 'airport',
                            'iata' => 'CMN',
                            'providers' => [
                                ['provider' => 'surprice', 'pickup_id' => 'CMN'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = app(LocationSearchService::class);

        $results = $service->searchLocations('casablanca airport');

        $this->assertCount(1, $results);
        $this->assertSame('Casablanca Airport', $results[0]['name']);
        $this->assertSame('CMN', $results[0]['iata']);
        $this->assertSame([
            'greenmotion',
            'usave',
            'surprice',
        ], collect($results[0]['providers'])->pluck('provider')->all());
        $this->assertSame(3, $results[0]['provider_count']);
    }


    public function test_it_collapses_equivalent_airport_results_when_aliases_overlap_without_iata(): void
    {
        config([
            'vrooem.enabled' => true,
            'vrooem.url' => 'http://gateway.test',
            'vrooem.api_key' => 'test-key',
        ]);

        Http::fake([
            'http://gateway.test/api/v1/locations/search*' => Http::response([
                'query' => 'fiumicino',
                'results' => [
                    [
                        'location' => [
                            'unified_location_id' => 1191543869,
                            'name' => 'Fiumicino Airport (FCO)',
                            'city' => 'Fiumicino',
                            'country' => 'Italy',
                            'location_type' => 'airport',
                            'iata' => 'FCO',
                            'aliases' => ['Rome Fiumicino Airport'],
                            'providers' => [
                                ['provider' => 'greenmotion', 'pickup_id' => '157'],
                                ['provider' => 'locauto_rent', 'pickup_id' => 'FCO'],
                            ],
                        ],
                    ],
                    [
                        'location' => [
                            'unified_location_id' => 957477491,
                            'name' => 'Rome Fiumicino Airport',
                            'city' => 'Rome Fiumicino',
                            'country' => 'Italy',
                            'location_type' => 'airport',
                            'aliases' => ['Rome Fiumicino'],
                            'providers' => [
                                ['provider' => 'recordgo', 'pickup_id' => '39005'],
                            ],
                        ],
                    ],
                    [
                        'location' => [
                            'unified_location_id' => 1496605060,
                            'name' => 'Roma Airport',
                            'city' => 'Roma',
                            'country' => 'Italy',
                            'location_type' => 'airport',
                            'aliases' => ['Rome Fiumicino Airport'],
                            'providers' => [
                                ['provider' => 'sicily_by_car', 'pickup_id' => 'IT011'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = app(LocationSearchService::class);

        $results = $service->searchLocations('fiumicino');

        $this->assertCount(1, $results);
        $this->assertSame('Fiumicino Airport (FCO)', $results[0]['name']);
        $this->assertSame([
            'greenmotion',
            'locauto_rent',
            'recordgo',
            'sicily_by_car',
        ], collect($results[0]['providers'])->pluck('provider')->all());
        $this->assertSame(4, $results[0]['provider_count']);
    }

    public function test_it_augments_gateway_location_by_unified_id_with_alias_matched_airport_provider_rows(): void
    {
        config([
            'vrooem.enabled' => true,
            'vrooem.url' => 'http://gateway.test',
            'vrooem.api_key' => 'test-key',
        ]);

        Http::fake([
            'http://gateway.test/api/v1/locations/1191543869' => Http::response([
                'unified_location_id' => 1191543869,
                'name' => 'Fiumicino Airport (FCO)',
                'city' => 'Fiumicino',
                'country' => 'Italy',
                'location_type' => 'airport',
                'iata' => 'FCO',
                'aliases' => ['Rome Fiumicino Airport'],
                'providers' => [
                    ['provider' => 'greenmotion', 'pickup_id' => '157'],
                    ['provider' => 'locauto_rent', 'pickup_id' => 'FCO'],
                ],
            ], 200),
            'http://gateway.test/api/v1/locations/search*' => Http::response([
                'query' => 'FCO',
                'results' => [
                    [
                        'location' => [
                            'unified_location_id' => 1191543869,
                            'name' => 'Fiumicino Airport (FCO)',
                            'city' => 'Fiumicino',
                            'country' => 'Italy',
                            'location_type' => 'airport',
                            'iata' => 'FCO',
                            'aliases' => ['Rome Fiumicino Airport'],
                            'providers' => [
                                ['provider' => 'greenmotion', 'pickup_id' => '157'],
                                ['provider' => 'locauto_rent', 'pickup_id' => 'FCO'],
                            ],
                        ],
                    ],
                    [
                        'location' => [
                            'unified_location_id' => 957477491,
                            'name' => 'Rome Fiumicino Airport',
                            'city' => 'Rome Fiumicino',
                            'country' => 'Italy',
                            'location_type' => 'airport',
                            'aliases' => ['Rome Fiumicino'],
                            'providers' => [
                                ['provider' => 'recordgo', 'pickup_id' => '39005'],
                            ],
                        ],
                    ],
                    [
                        'location' => [
                            'unified_location_id' => 1496605060,
                            'name' => 'Roma Airport',
                            'city' => 'Roma',
                            'country' => 'Italy',
                            'location_type' => 'airport',
                            'aliases' => ['Rome Fiumicino Airport'],
                            'providers' => [
                                ['provider' => 'sicily_by_car', 'pickup_id' => 'IT011'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = app(LocationSearchService::class);

        $location = $service->getLocationByUnifiedId(1191543869);

        $this->assertNotNull($location);
        $this->assertSame('Fiumicino Airport (FCO)', $location['name']);
        $this->assertSame([
            'greenmotion',
            'locauto_rent',
            'recordgo',
            'sicily_by_car',
        ], collect($location['providers'])->pluck('provider')->all());
        $this->assertSame(4, $location['provider_count']);
    }

    public function test_it_fetches_gateway_location_by_unified_id(): void
    {
        config([
            'vrooem.enabled' => true,
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

    public function test_it_augments_gateway_location_by_unified_id_with_same_iata_provider_aliases(): void
    {
        config([
            'vrooem.enabled' => true,
            'vrooem.url' => 'http://gateway.test',
            'vrooem.api_key' => 'test-key',
        ]);

        Http::fake([
            'http://gateway.test/api/v1/locations/2962361044' => Http::response([
                'unified_location_id' => 2962361044,
                'name' => 'Casablanca Airport',
                'city' => 'Casablanca',
                'country' => 'Morocco',
                'location_type' => 'airport',
                'iata' => 'CMN',
                'providers' => [
                    ['provider' => 'greenmotion', 'pickup_id' => '354'],
                    ['provider' => 'usave', 'pickup_id' => '354'],
                ],
            ], 200),
            'http://gateway.test/api/v1/locations/search*' => Http::response([
                'query' => 'CMN',
                'results' => [
                    [
                        'location' => [
                            'unified_location_id' => 2962361044,
                            'name' => 'Casablanca Airport',
                            'city' => 'Casablanca',
                            'country' => 'Morocco',
                            'location_type' => 'airport',
                            'iata' => 'CMN',
                            'providers' => [
                                ['provider' => 'greenmotion', 'pickup_id' => '354'],
                                ['provider' => 'usave', 'pickup_id' => '354'],
                            ],
                        ],
                    ],
                    [
                        'location' => [
                            'unified_location_id' => 4103471891,
                            'name' => 'Nouaceur Airport',
                            'city' => 'Nouaceur',
                            'country' => 'Morocco',
                            'location_type' => 'airport',
                            'iata' => 'CMN',
                            'providers' => [
                                ['provider' => 'surprice', 'pickup_id' => 'CMN'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = app(LocationSearchService::class);

        $location = $service->getLocationByUnifiedId(2962361044);

        $this->assertNotNull($location);
        $this->assertSame('Casablanca Airport', $location['name']);
        $this->assertSame([
            'greenmotion',
            'usave',
            'surprice',
        ], collect($location['providers'])->pluck('provider')->all());
        $this->assertSame(3, $location['provider_count']);
    }

    public function test_it_fetches_gateway_location_by_provider_id(): void
    {
        config([
            'vrooem.enabled' => true,
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

    public function test_it_returns_empty_results_when_gateway_search_fails(): void
    {
        config([
            'vrooem.enabled' => true,
            'vrooem.url' => 'http://gateway.test',
            'vrooem.api_key' => 'test-key',
        ]);

        Http::fake([
            'http://gateway.test/api/v1/locations/search*' => Http::response(['detail' => 'Gateway unavailable'], 503),
        ]);

        $service = app(LocationSearchService::class);

        $results = $service->searchLocations('marrakesh');

        $this->assertSame([], $results);
    }

    public function test_it_uses_gateway_list_locations_for_full_location_listing(): void
    {
        config([
            'vrooem.enabled' => true,
            'vrooem.url' => 'http://gateway.test',
            'vrooem.api_key' => 'test-key',
        ]);

        Http::fake([
            'http://gateway.test/api/v1/locations*' => Http::response([
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
                    ],
                ],
            ], 200),
        ]);

        $service = app(LocationSearchService::class);

        $locations = $service->getAllLocations();

        $this->assertCount(1, $locations);
        $this->assertSame(3385755165, $locations[0]['unified_location_id']);
        $this->assertSame('Dubai Airport (DXB)', $locations[0]['name']);
    }

    public function test_location_search_service_source_is_gateway_only(): void
    {
        $source = file_get_contents(app_path('Services/LocationSearchService.php'));

        $this->assertStringNotContainsString('unified_locations.json', $source);
        $this->assertStringNotContainsString('location_search_enabled', $source);
        $this->assertStringNotContainsString('shouldUseGateway', $source);
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
