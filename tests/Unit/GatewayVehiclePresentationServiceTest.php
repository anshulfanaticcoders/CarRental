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

    public function test_it_collapses_sicily_by_car_variants_when_provider_vehicle_id_only_differs_by_payment_suffix(): void
    {
        $service = new \App\Services\GatewayVehiclePresentationService();

        $vehicles = new Collection([
            [
                'id' => 'sicily-poa-suffixed',
                'source' => 'sicily_by_car',
                'provider_vehicle_id' => 'B_BASIC-POA',
                'provider_pickup_id' => 'IT014',
                'provider_return_id' => 'IT014',
                'model' => 'Panda 1.2',
                'sipp_code' => 'MDMRS',
                'currency' => 'EUR',
                'total_price' => 112.70,
                'payment_type' => 'PayOnArrival',
                'rate_id' => 'BASIC-POA',
            ],
            [
                'id' => 'sicily-prepaid-suffixed',
                'source' => 'sicily_by_car',
                'provider_vehicle_id' => 'B_BASIC-PP',
                'provider_pickup_id' => 'IT014',
                'provider_return_id' => 'IT014',
                'model' => 'Panda 1.2',
                'sipp_code' => 'MDMRS',
                'currency' => 'EUR',
                'total_price' => 112.70,
                'payment_type' => 'Prepaid',
                'rate_id' => 'BASIC-PP',
            ],
        ]);

        $collapsed = $service->collapseEquivalentSicilyByCarVehicles($vehicles);

        $this->assertCount(1, $collapsed);
        $this->assertSame(['sicily-poa-suffixed'], $collapsed->pluck('id')->all());
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

        $this->assertCount(1, $collapsed);
        $vehicle = $collapsed->first();

        $this->assertSame('gw_df8681dfb808457f', $vehicle['id']);
        $this->assertCount(2, $vehicle['products']);
        $this->assertSame(['gw_df8681dfb808457f', 'gw_distinct_price'], array_column($vehicle['products'], 'gateway_vehicle_id'));
        $this->assertSame([337.01, 347.01], array_column($vehicle['products'], 'total'));
    }

    public function test_it_groups_renteon_rate_variants_into_a_single_semantic_vehicle_with_products(): void
    {
        $service = new \App\Services\GatewayVehiclePresentationService();

        $vehicles = new Collection([
            [
                'id' => 'gw_mid',
                'gateway_vehicle_id' => 'gw_mid',
                'source' => 'renteon',
                'provider_code' => 'Alquicoche',
                'provider_pickup_id' => 'ES-MAL-AGP',
                'provider_return_id' => 'ES-MAL-AGP',
                'provider_pickup_office_id' => 490,
                'provider_dropoff_office_id' => 490,
                'connector_id' => 51,
                'provider_vehicle_id' => '51',
                'brand' => 'Fiat',
                'model' => '500',
                'sipp_code' => 'MBMV',
                'currency' => 'EUR',
                'total_price' => 188.03,
                'price_per_day' => 37.61,
                'benefits' => [
                    'deposit_amount' => 500.0,
                    'deposit_currency' => 'EUR',
                    'excess_amount' => 500.0,
                    'excess_theft_amount' => 500.0,
                ],
                'products' => [[
                    'type' => 'BAS',
                    'name' => 'Basic Package',
                    'total' => 188.03,
                    'currency' => 'EUR',
                    'deposit' => 500.0,
                    'excess' => 500.0,
                ]],
                'extras' => [
                    ['id' => 'ext_driver', 'code' => 'ADR', 'daily_rate' => 9.0, 'total_price' => 45.0],
                    ['id' => 'ext_wifi', 'code' => 'WIFI', 'daily_rate' => 5.0, 'total_price' => 25.0],
                ],
                'pricelist_id' => 729,
                'pricelist_code' => 'BRKPOSM',
                'price_date' => '2026-03-23T00:00:00',
                'prepaid' => false,
                'is_on_request' => false,
            ],
            [
                'id' => 'gw_high',
                'gateway_vehicle_id' => 'gw_high',
                'source' => 'renteon',
                'provider_code' => 'Alquicoche',
                'provider_pickup_id' => 'ES-MAL-AGP',
                'provider_return_id' => 'ES-MAL-AGP',
                'provider_pickup_office_id' => 490,
                'provider_dropoff_office_id' => 490,
                'connector_id' => 51,
                'provider_vehicle_id' => '51',
                'brand' => 'Fiat',
                'model' => '500',
                'sipp_code' => 'MBMV',
                'currency' => 'EUR',
                'total_price' => 235.53,
                'price_per_day' => 47.11,
                'benefits' => [
                    'deposit_amount' => 242.0,
                    'deposit_currency' => 'EUR',
                    'excess_amount' => null,
                    'excess_theft_amount' => 0.0,
                ],
                'products' => [[
                    'type' => 'BAS',
                    'name' => 'Basic Package',
                    'total' => 235.53,
                    'currency' => 'EUR',
                    'deposit' => 242.0,
                    'excess' => null,
                ]],
                'extras' => [
                    ['id' => 'ext_driver', 'code' => 'ADR', 'daily_rate' => 9.0, 'total_price' => 45.0],
                    ['id' => 'ext_wifi', 'code' => 'WIFI', 'daily_rate' => 5.0, 'total_price' => 25.0],
                ],
                'pricelist_id' => 740,
                'pricelist_code' => 'BRKPOSH',
                'price_date' => '2026-03-23T00:00:00',
                'prepaid' => false,
                'is_on_request' => false,
            ],
            [
                'id' => 'gw_low',
                'gateway_vehicle_id' => 'gw_low',
                'source' => 'renteon',
                'provider_code' => 'Alquicoche',
                'provider_pickup_id' => 'ES-MAL-AGP',
                'provider_return_id' => 'ES-MAL-AGP',
                'provider_pickup_office_id' => 490,
                'provider_dropoff_office_id' => 490,
                'connector_id' => 51,
                'provider_vehicle_id' => '51',
                'brand' => 'Fiat',
                'model' => '500',
                'sipp_code' => 'MBMV',
                'currency' => 'EUR',
                'total_price' => 151.98,
                'price_per_day' => 30.40,
                'benefits' => [
                    'deposit_amount' => 1100.0,
                    'deposit_currency' => 'EUR',
                    'excess_amount' => 1100.0,
                    'excess_theft_amount' => 1100.0,
                ],
                'products' => [[
                    'type' => 'BAS',
                    'name' => 'Basic Package',
                    'total' => 151.98,
                    'currency' => 'EUR',
                    'deposit' => 1100.0,
                    'excess' => 1100.0,
                ]],
                'extras' => [
                    ['id' => 'ext_driver', 'code' => 'ADR', 'daily_rate' => 9.0, 'total_price' => 45.0],
                    ['id' => 'ext_wifi', 'code' => 'WIFI', 'daily_rate' => 5.0, 'total_price' => 25.0],
                ],
                'pricelist_id' => 797,
                'pricelist_code' => 'BRKPOSL',
                'price_date' => '2026-03-23T00:00:00',
                'prepaid' => false,
                'is_on_request' => false,
            ],
        ]);

        $collapsed = $service->collapseEquivalentRenteonVehicles($vehicles);

        $this->assertCount(1, $collapsed);

        $vehicle = $collapsed->first();
        $this->assertSame('gw_low', $vehicle['id']);
        $this->assertSame(151.98, $vehicle['total_price']);
        $this->assertSame(30.40, $vehicle['price_per_day']);
        $this->assertCount(3, $vehicle['products']);
        $this->assertSame(['REN_797', 'REN_729', 'REN_740'], array_column($vehicle['products'], 'type'));
        $this->assertSame(['gw_low', 'gw_mid', 'gw_high'], array_column($vehicle['products'], 'gateway_vehicle_id'));
        $this->assertSame([151.98, 188.03, 235.53], array_column($vehicle['products'], 'total'));
        $this->assertSame([1100.0, 500.0, 242.0], array_column($vehicle['products'], 'deposit'));
        $this->assertSame([1100.0, 500.0, null], array_column($vehicle['products'], 'excess'));
    }

    public function test_it_groups_renteon_cards_when_only_the_raw_sipp_code_differs_but_the_display_signature_matches(): void
    {
        $service = new \App\Services\GatewayVehiclePresentationService();

        $vehicles = new Collection([
            [
                'id' => 'gw_mbv',
                'gateway_vehicle_id' => 'gw_mbv',
                'source' => 'renteon',
                'provider_code' => 'Alquicoche',
                'provider_pickup_id' => 'ES-MAL-AGP',
                'provider_return_id' => 'ES-MAL-AGP',
                'provider_pickup_office_id' => 490,
                'provider_dropoff_office_id' => 490,
                'connector_id' => 51,
                'provider_vehicle_id' => '51',
                'brand' => 'Fiat',
                'model' => '500',
                'category' => 'mini',
                'sipp_code' => 'MBMV',
                'currency' => 'EUR',
                'transmission' => 'manual',
                'fuel' => 'Petrol',
                'seating_capacity' => 4,
                'doors' => 3,
                'luggageSmall' => '2',
                'luggageLarge' => '0',
                'airConditioning' => true,
                'total_price' => 151.98,
                'price_per_day' => 30.40,
                'benefits' => [
                    'deposit_amount' => 1100.0,
                    'deposit_currency' => 'EUR',
                    'excess_amount' => 1100.0,
                    'excess_theft_amount' => 1100.0,
                ],
                'products' => [[
                    'type' => 'REN_797',
                    'name' => 'Rate Option 1',
                    'total' => 151.98,
                    'currency' => 'EUR',
                    'deposit' => 1100.0,
                    'excess' => 1100.0,
                ]],
                'extras' => [
                    ['id' => 'ext_driver', 'code' => 'ADR', 'daily_rate' => 9.0, 'total_price' => 45.0],
                ],
                'pricelist_id' => 797,
                'pricelist_code' => 'BRKPOSL',
                'price_date' => '2026-03-23T00:00:00',
                'prepaid' => false,
                'is_on_request' => false,
            ],
            [
                'id' => 'gw_mbr',
                'gateway_vehicle_id' => 'gw_mbr',
                'source' => 'renteon',
                'provider_code' => 'Alquicoche',
                'provider_pickup_id' => 'ES-MAL-AGP',
                'provider_return_id' => 'ES-MAL-AGP',
                'provider_pickup_office_id' => 490,
                'provider_dropoff_office_id' => 490,
                'connector_id' => 51,
                'provider_vehicle_id' => '51',
                'brand' => 'Fiat',
                'model' => '500',
                'category' => 'mini',
                'sipp_code' => 'MBMR',
                'currency' => 'EUR',
                'transmission' => 'manual',
                'fuel' => 'Petrol',
                'seating_capacity' => 4,
                'doors' => 3,
                'luggageSmall' => '2',
                'luggageLarge' => '0',
                'airConditioning' => true,
                'total_price' => 151.98,
                'price_per_day' => 30.40,
                'benefits' => [
                    'deposit_amount' => 1100.0,
                    'deposit_currency' => 'EUR',
                    'excess_amount' => 1100.0,
                    'excess_theft_amount' => 1100.0,
                ],
                'products' => [[
                    'type' => 'REN_797',
                    'name' => 'Rate Option 1',
                    'total' => 151.98,
                    'currency' => 'EUR',
                    'deposit' => 1100.0,
                    'excess' => 1100.0,
                ]],
                'extras' => [
                    ['id' => 'ext_driver', 'code' => 'ADR', 'daily_rate' => 9.0, 'total_price' => 45.0],
                ],
                'pricelist_id' => 797,
                'pricelist_code' => 'BRKPOSL',
                'price_date' => '2026-03-23T00:00:00',
                'prepaid' => false,
                'is_on_request' => false,
            ],
        ]);

        $collapsed = $service->collapseEquivalentRenteonVehicles($vehicles);

        $this->assertCount(1, $collapsed);
        $this->assertSame('gw_mbv', $collapsed->first()['id']);
    }
}
