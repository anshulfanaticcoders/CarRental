<?php

namespace Tests\Unit\Skyscanner;

use App\Services\Skyscanner\CarHireQuoteValidationService;
use Tests\TestCase;

class CarHireQuoteValidationServiceTest extends TestCase
{
    public function test_it_marks_a_complete_quote_as_ready(): void
    {
        $service = app(CarHireQuoteValidationService::class);

        $result = $service->validate([
            'provider_vehicle_id' => '327',
            'display_name' => 'Toyota Yaris',
            'supplier' => [
                'code' => 'internal',
                'name' => 'Vrooem Internal Fleet',
            ],
            'specs' => [
                'sipp_code' => 'ECAR',
            ],
            'pricing' => [
                'currency' => 'EUR',
                'total_price' => 90.0,
            ],
            'location' => [
                'pickup' => [
                    'provider_location_id' => 'internal-abc',
                ],
                'dropoff' => [
                    'provider_location_id' => 'internal-abc',
                ],
            ],
        ]);

        $this->assertSame([
            'ready' => true,
            'errors' => [],
        ], $result);
    }

    public function test_it_reports_missing_required_quote_fields(): void
    {
        $service = app(CarHireQuoteValidationService::class);

        $result = $service->validate([
            'display_name' => '',
            'supplier' => [
                'code' => 'internal',
                'name' => '',
            ],
            'specs' => [
                'sipp_code' => '',
            ],
            'pricing' => [
                'currency' => '',
                'total_price' => null,
            ],
            'location' => [
                'pickup' => [
                    'provider_location_id' => '',
                ],
                'dropoff' => [
                    'provider_location_id' => '',
                ],
            ],
        ]);

        $this->assertFalse($result['ready']);
        $this->assertSame([
            'missing_provider_vehicle_id',
            'missing_display_name',
            'missing_supplier_name',
            'missing_sipp_code',
            'missing_currency',
            'missing_total_price',
            'missing_pickup_location_id',
            'missing_dropoff_location_id',
        ], $result['errors']);
    }
}
