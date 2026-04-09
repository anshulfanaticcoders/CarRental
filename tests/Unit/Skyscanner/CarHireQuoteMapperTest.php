<?php

namespace Tests\Unit\Skyscanner;

use App\Services\Skyscanner\CarHireQuoteMapper;
use Tests\TestCase;

class CarHireQuoteMapperTest extends TestCase
{
    public function test_it_enriches_internal_inventory_with_supplier_context_and_fallback_flags(): void
    {
        $mapper = app(CarHireQuoteMapper::class);

        $mapped = $mapper->map([
            'provider_vehicle_id' => '327',
            'source' => 'internal',
            'provider_code' => 'internal',
            'display_name' => 'Toyota Yaris',
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'image' => 'https://example.com/yaris.jpg',
            'specs' => [
                'transmission' => 'automatic',
                'fuel' => 'petrol',
                'seating_capacity' => 5,
                'doors' => 4,
                'sipp_code' => null,
            ],
            'pricing' => [
                'currency' => 'EUR',
                'total_price' => 90.0,
            ],
            'location' => [
                'pickup' => [
                    'provider_location_id' => null,
                    'name' => 'Marrakech Airport',
                    'latitude' => 31.6069,
                    'longitude' => -8.0363,
                ],
                'dropoff' => [
                    'provider_location_id' => null,
                    'name' => 'Marrakech Airport',
                    'latitude' => 31.6069,
                    'longitude' => -8.0363,
                ],
            ],
        ]);

        $this->assertSame([
            'code' => 'internal',
            'name' => 'Vrooem Internal Fleet',
        ], $mapped['supplier']);
        $this->assertSame('XCAR', $mapped['specs']['sipp_code']);
        $this->assertSame('derived', $mapped['specs']['sipp_source']);
        $this->assertStringStartsWith('internal-', $mapped['location']['pickup']['provider_location_id']);
        $this->assertStringStartsWith('internal-', $mapped['location']['dropoff']['provider_location_id']);
        $this->assertSame([], $mapped['data_quality_flags']);
        $this->assertTrue($mapped['validation']['ready']);
        $this->assertSame([], $mapped['validation']['errors']);
    }

    public function test_it_preserves_explicit_supplier_and_location_identifiers_when_present(): void
    {
        $mapper = app(CarHireQuoteMapper::class);

        $mapped = $mapper->map([
            'provider_vehicle_id' => '328',
            'source' => 'internal',
            'provider_code' => 'internal-fleet',
            'supplier' => [
                'code' => 'internal-fleet',
                'name' => 'Vrooem Fleet',
            ],
            'display_name' => 'Nissan Sunny',
            'specs' => [
                'sipp_code' => 'ECMR',
            ],
            'pricing' => [
                'currency' => 'EUR',
                'total_price' => 95.0,
            ],
            'location' => [
                'pickup' => [
                    'provider_location_id' => 'PMI-01',
                    'name' => 'Palma Airport',
                ],
                'dropoff' => [
                    'provider_location_id' => 'PMI-01',
                    'name' => 'Palma Airport',
                ],
            ],
        ]);

        $this->assertSame([
            'code' => 'internal-fleet',
            'name' => 'Vrooem Fleet',
        ], $mapped['supplier']);
        $this->assertSame('ECMR', $mapped['specs']['sipp_code']);
        $this->assertSame('explicit', $mapped['specs']['sipp_source']);
        $this->assertSame([], $mapped['data_quality_flags']);
        $this->assertTrue($mapped['validation']['ready']);
    }
}
