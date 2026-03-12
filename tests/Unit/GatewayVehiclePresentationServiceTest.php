<?php

namespace Tests\Unit;

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class GatewayVehiclePresentationServiceTest extends TestCase
{
    public function test_it_collapses_equivalent_sicily_by_car_pay_on_arrival_and_prepaid_cards(): void
    {
        $service = new \App\Services\GatewayVehiclePresentationService();

        $vehicles = new Collection([
            [
                'id' => 'sicily-poa',
                'source' => 'sicily_by_car',
                'provider_vehicle_id' => 'B',
                'provider_pickup_id' => 'IT020',
                'provider_return_id' => 'IT020',
                'model' => 'Fiat Panda 1.2 or similar',
                'sipp_code' => 'MDMRS',
                'currency' => 'EUR',
                'total_price' => 319.20,
                'payment_type' => 'PayOnArrival',
                'rate_id' => 'BASIC-POA',
            ],
            [
                'id' => 'sicily-prepaid',
                'source' => 'sicily_by_car',
                'provider_vehicle_id' => 'B',
                'provider_pickup_id' => 'IT020',
                'provider_return_id' => 'IT020',
                'model' => 'Fiat Panda 1.2 or similar',
                'sipp_code' => 'MDMRS',
                'currency' => 'EUR',
                'total_price' => 319.20,
                'payment_type' => 'Prepaid',
                'rate_id' => 'BASIC-PP',
            ],
            [
                'id' => 'greenmotion-1',
                'source' => 'greenmotion',
                'provider_vehicle_id' => 'GM-1',
                'provider_pickup_id' => '114',
                'provider_return_id' => '114',
                'model' => 'Hyundai I10',
                'currency' => 'EUR',
                'total_price' => 75.29,
            ],
        ]);

        $collapsed = $service->collapseEquivalentSicilyByCarVehicles($vehicles);

        $this->assertCount(2, $collapsed);
        $sicilyVehicle = $collapsed->firstWhere('source', 'sicily_by_car');

        $this->assertNotNull($sicilyVehicle);
        $this->assertSame('sicily-poa', $sicilyVehicle['id']);
        $this->assertSame('PayOnArrival', $sicilyVehicle['payment_type']);
        $this->assertSame('BASIC-POA', $sicilyVehicle['rate_id']);
    }

    public function test_it_keeps_distinct_sicily_by_car_offers_when_price_differs(): void
    {
        $service = new \App\Services\GatewayVehiclePresentationService();

        $vehicles = new Collection([
            [
                'id' => 'sicily-basic',
                'source' => 'sicily_by_car',
                'provider_vehicle_id' => 'B',
                'provider_pickup_id' => 'IT020',
                'provider_return_id' => 'IT020',
                'model' => 'Fiat Panda 1.2 or similar',
                'sipp_code' => 'MDMRS',
                'currency' => 'EUR',
                'total_price' => 319.20,
                'payment_type' => 'PayOnArrival',
                'rate_id' => 'BASIC-POA',
            ],
            [
                'id' => 'sicily-plus',
                'source' => 'sicily_by_car',
                'provider_vehicle_id' => 'B',
                'provider_pickup_id' => 'IT020',
                'provider_return_id' => 'IT020',
                'model' => 'Fiat Panda 1.2 or similar',
                'sipp_code' => 'MDMRS',
                'currency' => 'EUR',
                'total_price' => 389.20,
                'payment_type' => 'PayOnArrival',
                'rate_id' => 'PLUS-POA',
            ],
        ]);

        $collapsed = $service->collapseEquivalentSicilyByCarVehicles($vehicles);

        $this->assertCount(2, $collapsed);
        $this->assertSame(['sicily-basic', 'sicily-plus'], $collapsed->pluck('id')->all());
    }

    public function test_it_collapses_equivalent_renteon_cards_that_only_differ_by_gateway_id_and_pricelist(): void
    {
        $service = new \App\Services\GatewayVehiclePresentationService();

        $vehicles = new Collection([
            [
                'id' => 'gw_df8681dfb808457f',
                'source' => 'renteon',
                'provider_code' => 'Alquicoche',
                'provider_pickup_id' => 'ES-AVI-DT',
                'provider_return_id' => 'ES-AVI-DT',
                'provider_pickup_office_id' => 1094,
                'provider_dropoff_office_id' => 1094,
                'connector_id' => 51,
                'model' => 'Focus',
                'brand' => 'Ford',
                'sipp_code' => 'CDMV',
                'currency' => 'EUR',
                'total_price' => 337.01,
                'pricelist_id' => 729,
                'pricelist_code' => 'BRKPOSM',
                'prepaid' => false,
                'is_on_request' => false,
            ],
            [
                'id' => 'gw_39c70af227734b12',
                'source' => 'renteon',
                'provider_code' => 'Alquicoche',
                'provider_pickup_id' => 'ES-AVI-DT',
                'provider_return_id' => 'ES-AVI-DT',
                'provider_pickup_office_id' => 1094,
                'provider_dropoff_office_id' => 1094,
                'connector_id' => 51,
                'model' => 'Focus',
                'brand' => 'Ford',
                'sipp_code' => 'CDMV',
                'currency' => 'EUR',
                'total_price' => 337.01,
                'pricelist_id' => 740,
                'pricelist_code' => 'BRKPOSH',
                'prepaid' => false,
                'is_on_request' => false,
            ],
            [
                'id' => 'gw_f9ee3d1ab1bd43bb',
                'source' => 'renteon',
                'provider_code' => 'Alquicoche',
                'provider_pickup_id' => 'ES-AVI-DT',
                'provider_return_id' => 'ES-AVI-DT',
                'provider_pickup_office_id' => 1094,
                'provider_dropoff_office_id' => 1094,
                'connector_id' => 51,
                'model' => 'Focus',
                'brand' => 'Ford',
                'sipp_code' => 'CDMV',
                'currency' => 'EUR',
                'total_price' => 337.01,
                'pricelist_id' => 797,
                'pricelist_code' => 'BRKPOSL',
                'prepaid' => false,
                'is_on_request' => false,
            ],
            [
                'id' => 'gw_distinct_price',
                'source' => 'renteon',
                'provider_code' => 'Alquicoche',
                'provider_pickup_id' => 'ES-AVI-DT',
                'provider_return_id' => 'ES-AVI-DT',
                'provider_pickup_office_id' => 1094,
                'provider_dropoff_office_id' => 1094,
                'connector_id' => 51,
                'model' => 'Focus',
                'brand' => 'Ford',
                'sipp_code' => 'CDMV',
                'currency' => 'EUR',
                'total_price' => 347.01,
                'pricelist_id' => 901,
                'pricelist_code' => 'DIFF',
                'prepaid' => false,
                'is_on_request' => false,
            ],
        ]);

        $collapsed = $service->collapseEquivalentRenteonVehicles($vehicles);

        $this->assertCount(2, $collapsed);
        $this->assertSame(['gw_df8681dfb808457f', 'gw_distinct_price'], $collapsed->pluck('id')->all());
    }
}
