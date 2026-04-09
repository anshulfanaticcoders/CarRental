<?php

namespace Tests\Unit\Skyscanner;

use App\Services\Skyscanner\CarHireFieldStrategyService;
use Tests\TestCase;

class CarHireFieldStrategyServiceTest extends TestCase
{
    public function test_it_preserves_an_explicit_sipp_code(): void
    {
        $service = app(CarHireFieldStrategyService::class);

        $result = $service->resolveSipp([
            'specs' => [
                'sipp_code' => 'ecmr',
            ],
        ]);

        $this->assertSame([
            'code' => 'ECMR',
            'source' => 'explicit',
        ], $result);
    }

    public function test_it_derives_a_coarse_sipp_code_when_missing(): void
    {
        $service = app(CarHireFieldStrategyService::class);

        $result = $service->resolveSipp([
            'category' => 'economy',
            'specs' => [
                'transmission' => 'automatic',
                'fuel' => 'petrol',
                'air_conditioning' => true,
            ],
        ]);

        $this->assertSame([
            'code' => 'ECAR',
            'source' => 'derived',
        ], $result);
    }

    public function test_it_builds_a_stable_internal_location_identifier_when_missing(): void
    {
        $service = app(CarHireFieldStrategyService::class);

        $location = $service->resolveLocationIdentifier([
            'name' => 'Marrakech Airport',
            'latitude' => 31.6069,
            'longitude' => -8.0363,
        ]);

        $this->assertStringStartsWith('internal-', $location['id']);
        $this->assertSame('derived', $location['source']);
    }

    public function test_it_keeps_an_explicit_location_identifier_when_present(): void
    {
        $service = app(CarHireFieldStrategyService::class);

        $location = $service->resolveLocationIdentifier([
            'provider_location_id' => 'PMI-01',
            'name' => 'Palma Airport',
        ]);

        $this->assertSame([
            'id' => 'PMI-01',
            'source' => 'explicit',
        ], $location);
    }

    public function test_it_builds_a_default_internal_supplier_identity(): void
    {
        $service = app(CarHireFieldStrategyService::class);

        $supplier = $service->resolveSupplier([
            'source' => 'internal',
            'provider_code' => 'internal',
        ]);

        $this->assertSame([
            'code' => 'internal',
            'name' => 'Vrooem Internal Fleet',
            'source' => 'derived',
        ], $supplier);
    }
}
