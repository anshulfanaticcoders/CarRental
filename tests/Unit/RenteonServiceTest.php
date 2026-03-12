<?php

namespace Tests\Unit;

use App\Services\RenteonService;
use PHPUnit\Framework\TestCase;

class RenteonServiceTest extends TestCase
{
    public function test_it_collapses_equivalent_renteon_offers_that_only_differ_by_pricelist(): void
    {
        $service = new class([
            $this->makeVehicle(['PricelistId' => 729, 'PricelistCode' => 'BRKPOSM']),
            $this->makeVehicle(['PricelistId' => 740, 'PricelistCode' => 'BRKPOSH']),
            $this->makeVehicle(['PricelistId' => 797, 'PricelistCode' => 'BRKPOSL']),
        ]) extends RenteonService {
            public function __construct(private readonly array $fakeVehicles)
            {
            }

            public function getVehiclesFromAllProviders($pickupCode, $dropoffCode, $startDate, $startTime, $endDate, $endTime, $options = [], $providerCodes = [])
            {
                return $this->fakeVehicles;
            }

            public function getProviderDetails($providerCode)
            {
                return null;
            }
        };

        $vehicles = $service->getTransformedVehiclesFromAllProviders(
            'ES-AVI-DT',
            'ES-AVI-DT',
            '2026-05-12',
            '09:00',
            '2026-05-16',
            '09:00',
            ['driver_age' => 35, 'currency' => 'EUR'],
            [],
            40.656685,
            -4.681208,
            'Avila Downtown',
            4
        );

        $this->assertCount(1, $vehicles);
        $this->assertSame('BRKPOSM', $vehicles[0]['pricelist_code']);
        $this->assertSame(337.01, $vehicles[0]['total_price']);
    }

    public function test_it_keeps_distinct_renteon_offers_when_the_price_differs(): void
    {
        $service = new class([
            $this->makeVehicle(['Amount' => 337.01, 'PricelistId' => 729, 'PricelistCode' => 'BRKPOSM']),
            $this->makeVehicle(['Amount' => 347.01, 'PricelistId' => 740, 'PricelistCode' => 'BRKPOSH']),
        ]) extends RenteonService {
            public function __construct(private readonly array $fakeVehicles)
            {
            }

            public function getVehiclesFromAllProviders($pickupCode, $dropoffCode, $startDate, $startTime, $endDate, $endTime, $options = [], $providerCodes = [])
            {
                return $this->fakeVehicles;
            }

            public function getProviderDetails($providerCode)
            {
                return null;
            }
        };

        $vehicles = $service->getTransformedVehiclesFromAllProviders(
            'ES-AVI-DT',
            'ES-AVI-DT',
            '2026-05-12',
            '09:00',
            '2026-05-16',
            '09:00',
            ['driver_age' => 35, 'currency' => 'EUR'],
            [],
            40.656685,
            -4.681208,
            'Avila Downtown',
            4
        );

        $this->assertCount(2, $vehicles);
        $this->assertSame(['BRKPOSM', 'BRKPOSH'], array_column($vehicles, 'pricelist_code'));
    }

    private function makeVehicle(array $overrides = []): array
    {
        return array_replace([
            'provider_code' => 'Alquicoche',
            'ModelName' => 'Ford Focus',
            'CarCategory' => 'CDMV',
            'Amount' => 337.01,
            'Currency' => 'EUR',
            'PickupOfficeId' => 25,
            'DropOffOfficeId' => 25,
            'ConnectorId' => 9,
            'Prepaid' => false,
        ], $overrides);
    }
}
