<?php

namespace Tests\Unit\Skyscanner;

use App\Services\Skyscanner\CarHireInventoryAuditService;
use Tests\TestCase;

class CarHireInventoryAuditServiceTest extends TestCase
{
    public function test_it_marks_a_vehicle_ready_when_all_required_fields_are_present(): void
    {
        $service = app(CarHireInventoryAuditService::class);

        $report = $service->auditVehicle([
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'image' => 'https://example.com/yaris.jpg',
            'pricing' => [
                'currency' => 'EUR',
                'total_price' => 90.0,
            ],
            'specs' => [
                'transmission' => 'automatic',
                'fuel' => 'petrol',
                'seating_capacity' => 5,
                'doors' => 4,
            ],
            'location' => [
                'pickup' => [
                    'provider_location_id' => '3272373056',
                    'name' => 'Dubai Airport (DXB)',
                ],
            ],
            'policies' => [
                'fuel_policy' => 'full_to_full',
                'mileage_policy' => 'limited',
                'cancellation' => [
                    'available' => true,
                    'days_before_pickup' => 2,
                ],
            ],
        ]);

        $this->assertSame([
            'ready' => true,
            'missing_fields' => [],
        ], $report);
    }

    public function test_it_reports_missing_skyscanner_readiness_fields_for_a_vehicle(): void
    {
        $service = app(CarHireInventoryAuditService::class);

        $report = $service->auditVehicle([
            'brand' => 'Toyota',
            'pricing' => [
                'currency' => 'EUR',
            ],
            'specs' => [
                'fuel' => 'petrol',
            ],
            'location' => [
                'pickup' => [
                    'name' => 'Dubai Airport (DXB)',
                ],
            ],
            'policies' => [],
        ]);

        $this->assertSame([
            'ready' => false,
            'missing_fields' => [
                'model',
                'image',
                'pricing.total_price',
                'specs.transmission',
                'specs.seating_capacity',
                'specs.doors',
                'location.pickup.provider_location_id',
                'policies.fuel_policy',
                'policies.mileage_policy',
                'policies.cancellation',
            ],
        ], $report);
    }

    public function test_it_summarizes_readiness_for_multiple_vehicles(): void
    {
        $service = app(CarHireInventoryAuditService::class);

        $summary = $service->auditVehicles([
            [
                'brand' => 'Toyota',
                'model' => 'Yaris',
                'image' => 'https://example.com/yaris.jpg',
                'pricing' => [
                    'currency' => 'EUR',
                    'total_price' => 90.0,
                ],
                'specs' => [
                    'transmission' => 'automatic',
                    'fuel' => 'petrol',
                    'seating_capacity' => 5,
                    'doors' => 4,
                ],
                'location' => [
                    'pickup' => [
                        'provider_location_id' => '3272373056',
                        'name' => 'Dubai Airport (DXB)',
                    ],
                ],
                'policies' => [
                    'fuel_policy' => 'full_to_full',
                    'mileage_policy' => 'limited',
                    'cancellation' => ['available' => true],
                ],
            ],
            [
                'brand' => 'Nissan',
                'pricing' => [
                    'currency' => 'EUR',
                ],
                'specs' => [],
                'location' => [
                    'pickup' => [],
                ],
                'policies' => [],
            ],
        ]);

        $this->assertSame(2, $summary['total']);
        $this->assertSame(1, $summary['ready']);
        $this->assertSame(1, $summary['not_ready']);
        $this->assertSame(1, $summary['vehicles'][0]['index']);
        $this->assertTrue($summary['vehicles'][0]['report']['ready']);
        $this->assertSame(2, $summary['vehicles'][1]['index']);
        $this->assertFalse($summary['vehicles'][1]['report']['ready']);
        $this->assertContains('model', $summary['vehicles'][1]['report']['missing_fields']);
    }
}
