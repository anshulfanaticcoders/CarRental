<?php

namespace Tests\Unit\Skyscanner;

use App\Services\Skyscanner\CarHireAuditLogService;
use App\Services\Skyscanner\CarHireInternalInventoryService;
use App\Services\Skyscanner\CarHireQuoteStoreService;
use App\Services\Skyscanner\CarHireSearchService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CarHireSearchServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_it_builds_quotes_from_internal_inventory_search_results(): void
    {
        config([
            'skyscanner.case_id' => 'PSM-46100',
            'skyscanner.quote_ttl_minutes' => 30,
        ]);

        $criteria = [
            'pickup_location_id' => 101,
            'dropoff_location_id' => 101,
            'pickup_date' => '2026-06-15',
            'pickup_time' => '09:00',
            'dropoff_date' => '2026-06-18',
            'dropoff_time' => '09:00',
            'driver_age' => 35,
            'currency' => 'EUR',
        ];

        $this->mock(CarHireInternalInventoryService::class, function ($mock) use ($criteria): void {
            $mock->shouldReceive('search')
                ->once()
                ->with($criteria)
                ->andReturn([
                    [
                        'provider_vehicle_id' => '327',
                        'source' => 'internal',
                        'provider_code' => 'internal',
                        'display_name' => 'Toyota Yaris',
                        'supplier' => [
                            'code' => 'internal',
                            'name' => 'Vrooem Internal Fleet',
                        ],
                        'image' => 'https://example.com/yaris.jpg',
                        'specs' => [
                            'sipp_code' => 'ECMR',
                            'transmission' => 'manual',
                            'fuel' => 'petrol',
                            'air_conditioning' => true,
                            'seating_capacity' => 5,
                            'doors' => 4,
                        ],
                        'pricing' => [
                            'currency' => 'EUR',
                            'total_price' => 90.0,
                        ],
                        'location' => [
                            'pickup' => [
                                'provider_location_id' => '101',
                                'name' => 'Marrakech Airport',
                            ],
                            'dropoff' => [
                                'provider_location_id' => '101',
                                'name' => 'Marrakech Airport',
                            ],
                        ],
                    ],
                ]);
        });

        $service = app(CarHireSearchService::class);

        $results = $service->search($criteria);

        $this->assertCount(1, $results['quotes']);
        $this->assertSame('327', $results['quotes'][0]['vehicle']['provider_vehicle_id']);
        $this->assertSame('ECMR', $results['quotes'][0]['vehicle']['sipp_code']);
        $this->assertSame('Vrooem Internal Fleet', $results['quotes'][0]['supplier']['name']);
        $this->assertSame([], $results['excluded_vehicle_ids']);
    }

    public function test_it_builds_and_stores_only_ready_quotes_for_candidate_vehicles(): void
    {
        config([
            'skyscanner.case_id' => 'PSM-46100',
            'skyscanner.quote_ttl_minutes' => 30,
        ]);

        $service = app(CarHireSearchService::class);
        $store = app(CarHireQuoteStoreService::class);
        $auditLog = app(CarHireAuditLogService::class);
        $now = CarbonImmutable::create(2026, 4, 11, 10, 0, 0, 'UTC');

        $results = $service->buildQuotes(
            [
                [
                    'provider_vehicle_id' => '327',
                    'display_name' => 'Toyota Yaris',
                    'supplier' => [
                        'code' => 'internal',
                        'name' => 'Vrooem Internal Fleet',
                    ],
                    'image' => 'https://example.com/yaris.jpg',
                    'specs' => [
                        'sipp_code' => 'ECMR',
                        'transmission' => 'manual',
                        'fuel' => 'petrol',
                        'air_conditioning' => true,
                        'seating_capacity' => 5,
                        'doors' => 4,
                    ],
                    'location' => [
                        'pickup' => [
                            'provider_location_id' => '3272373056',
                            'name' => 'Marrakech Airport',
                        ],
                        'dropoff' => [
                            'provider_location_id' => '3272373056',
                            'name' => 'Marrakech Airport',
                        ],
                    ],
                    'pricing' => [
                        'currency' => 'EUR',
                        'total_price' => 90.0,
                    ],
                    'policies' => [
                        'mileage_policy' => 'limited',
                    ],
                ],
                [
                    'provider_vehicle_id' => '328',
                    'display_name' => '',
                    'pricing' => [
                        'currency' => '',
                        'total_price' => null,
                    ],
                    'policies' => [
                        'mileage_policy' => 'unlimited',
                    ],
                ],
            ],
            [
                'pickup_location_id' => '3272373056',
                'dropoff_location_id' => '3272373056',
            ],
            $now,
        );

        $this->assertCount(1, $results['quotes']);
        $this->assertSame('327', $results['quotes'][0]['vehicle']['provider_vehicle_id']);
        $this->assertSame('Toyota Yaris', $results['quotes'][0]['vehicle']['display_name']);
        $this->assertSame('ECMR', $results['quotes'][0]['vehicle']['sipp_code']);
        $this->assertArrayHasKey('deeplink', $results['quotes'][0]);
        $this->assertSame('2026-04-11T10:30:00+00:00', $results['quotes'][0]['expires_at']);
        $this->assertSame([
            '328',
        ], $results['excluded_vehicle_ids']);

        $stored = $store->get($results['quotes'][0]['quote_id']);
        $logs = $auditLog->get('quote', $results['quotes'][0]['quote_id']);
        $rejectedLogs = $auditLog->get('candidate', '328');
        $this->assertSame($results['quotes'][0]['quote_id'], $stored['quote_id'] ?? null);
        $this->assertCount(1, $stored['offer_results']['quotes'] ?? []);
        $this->assertCount(1, $logs);
        $this->assertSame('quote_created', $logs[0]['event']);
        $this->assertSame('327', $logs[0]['payload']['provider_vehicle_id']);
        $this->assertCount(1, $rejectedLogs);
        $this->assertSame('quote_rejected', $rejectedLogs[0]['event']);
        $this->assertSame([
            'missing_display_name',
            'missing_currency',
            'missing_total_price',
        ], $rejectedLogs[0]['payload']['validation_errors']);
    }

    public function test_it_stores_related_offer_results_with_each_ready_quote(): void
    {
        config([
            'skyscanner.case_id' => 'PSM-46100',
            'skyscanner.quote_ttl_minutes' => 30,
        ]);

        $service = app(CarHireSearchService::class);
        $store = app(CarHireQuoteStoreService::class);
        $now = CarbonImmutable::create(2026, 4, 11, 10, 0, 0, 'UTC');

        $results = $service->buildQuotes(
            [
                [
                    'provider_vehicle_id' => '327',
                    'display_name' => 'Toyota Yaris',
                    'supplier' => [
                        'code' => 'internal',
                        'name' => 'Vrooem Internal Fleet',
                    ],
                    'pricing' => [
                        'currency' => 'EUR',
                        'total_price' => 90.0,
                    ],
                    'location' => [
                        'pickup' => [
                            'provider_location_id' => '3272373056',
                            'name' => 'Marrakech Airport',
                        ],
                        'dropoff' => [
                            'provider_location_id' => '3272373056',
                            'name' => 'Marrakech Airport',
                        ],
                    ],
                ],
                [
                    'provider_vehicle_id' => '328',
                    'display_name' => 'Peugeot 208',
                    'supplier' => [
                        'code' => 'internal',
                        'name' => 'Vrooem Internal Fleet',
                    ],
                    'pricing' => [
                        'currency' => 'EUR',
                        'total_price' => 84.0,
                    ],
                    'location' => [
                        'pickup' => [
                            'provider_location_id' => '3272373056',
                            'name' => 'Marrakech Airport',
                        ],
                        'dropoff' => [
                            'provider_location_id' => '3272373056',
                            'name' => 'Marrakech Airport',
                        ],
                    ],
                ],
            ],
            [
                'pickup_location_id' => '3272373056',
                'dropoff_location_id' => '3272373056',
                'currency' => 'EUR',
            ],
            $now,
        );

        $stored = $store->get($results['quotes'][0]['quote_id']);

        $this->assertCount(2, $results['quotes']);
        $this->assertCount(2, $stored['offer_results']['quotes'] ?? []);
        $this->assertSame($results['quotes'][0]['quote_id'], $stored['offer_results']['quotes'][0]['quote_id'] ?? null);
    }
}
