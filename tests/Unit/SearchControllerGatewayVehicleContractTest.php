<?php

namespace Tests\Unit;

use App\Services\Vehicles\GatewayVehicleTransformer;
use PHPUnit\Framework\TestCase;

class SearchControllerGatewayVehicleContractTest extends TestCase
{
    public function test_it_transforms_gateway_vehicles_to_the_canonical_contract_shape(): void
    {
        $transformer = new GatewayVehicleTransformer();

        $vehicle = $transformer->transform([
            'id' => 'gw_1',
            'supplier_id' => 'renteon',
            'supplier_vehicle_id' => 'veh-1',
            'make' => 'Ford',
            'model' => 'Focus',
            'category' => 'compact',
            'image_url' => 'https://example.com/focus.png',
            'pricing' => [
                'total_price' => 337.01,
                'daily_rate' => 84.2525,
                'currency' => 'EUR',
            ],
            'pickup_location' => [
                'supplier_location_id' => 'ES-AVI-DT',
                'name' => 'Avila Downtown',
                'latitude' => 40.656685,
                'longitude' => -4.681208,
            ],
            'supplier_data' => [
                'provider_code' => 'Alquicoche',
                'connector_id' => 51,
                'pickup_office_id' => 1094,
                'dropoff_office_id' => 1094,
                'pricelist_id' => 729,
                'pricelist_code' => 'BRKPOSM',
                'price_date' => '2026-03-12T08:36:16.9534909Z',
                'prepaid' => false,
                'is_on_request' => false,
                'pickup_office' => ['office_id' => 1094],
                'dropoff_office' => ['office_id' => 1094],
                'products' => [
                    ['type' => 'BAS', 'total' => 337.01, 'currency' => 'EUR'],
                ],
            ],
            'transmission' => 'manual',
            'fuel_type' => 'petrol',
            'seats' => 5,
            'doors' => 5,
            'bags_small' => 1,
            'bags_large' => 2,
            'extras' => [],
            'insurance_options' => [],
        ], 4);

        foreach ([
            'id',
            'gateway_vehicle_id',
            'provider_vehicle_id',
            'source',
            'brand',
            'model',
            'category',
            'image',
            'total_price',
            'price_per_day',
            'currency',
            'provider_pickup_id',
            'provider_return_id',
            'features',
            'benefits',
            'products',
            'options',
            'extras',
            'insurance_options',
            'pickup_office',
            'dropoff_office',
            'connector_id',
            'provider_pickup_office_id',
            'provider_dropoff_office_id',
            'pricelist_id',
            'pricelist_code',
            'price_date',
            'prepaid',
            'provider_code',
            'is_on_request',
        ] as $key) {
            $this->assertArrayHasKey($key, $vehicle);
        }

        $this->assertSame('renteon', $vehicle['source']);
        $this->assertSame('Alquicoche', $vehicle['provider_code']);
        $this->assertSame('ES-AVI-DT', $vehicle['provider_pickup_id']);
        $this->assertSame('EUR', $vehicle['currency']);
        $this->assertSame('BAS', $vehicle['products'][0]['type']);
        $this->assertSame('Basic', $vehicle['products'][0]['name']);
        $this->assertSame(337.01, $vehicle['products'][0]['total']);
        $this->assertSame(84.25, $vehicle['products'][0]['price_per_day']);
        $this->assertSame('EUR', $vehicle['products'][0]['currency']);
        $this->assertSame([
            'minimum_driver_age',
            'maximum_driver_age',
            'fuel_policy',
            'deposit_amount',
            'deposit_currency',
            'excess_amount',
            'excess_theft_amount',
        ], array_keys($vehicle['benefits']));
    }

    public function test_it_preserves_recordgo_products_needed_for_booking(): void
    {
        $transformer = new GatewayVehicleTransformer();

        $vehicle = $transformer->transform([
            'id' => 'gw_rg_1',
            'supplier_id' => 'record_go',
            'supplier_vehicle_id' => 'MDMR',
            'make' => 'Fiat',
            'model' => '500',
            'category' => 'mini',
            'image_url' => 'https://example.com/fiat.png',
            'pricing' => [
                'total_price' => 180.00,
                'daily_rate' => 36.00,
                'currency' => 'EUR',
            ],
            'pickup_location' => [
                'supplier_location_id' => '35001',
                'name' => 'Loures Airport',
                'latitude' => 38.7742,
                'longitude' => -9.1342,
            ],
            'supplier_data' => [
                'sell_code_ver' => 'sell-v1',
                'products' => [
                    [
                        'type' => 'BAS',
                        'name' => 'Basic',
                        'total' => 180.00,
                        'price_per_day' => 36.00,
                        'product_id' => 11,
                        'product_ver' => 1,
                        'rate_prod_ver' => 'A',
                    ],
                    [
                        'type' => 'PRE',
                        'name' => 'Premium',
                        'total' => 220.00,
                        'price_per_day' => 44.00,
                        'product_id' => 12,
                        'product_ver' => 1,
                        'rate_prod_ver' => 'B',
                    ],
                ],
            ],
            'transmission' => 'manual',
            'fuel_type' => 'petrol',
            'seats' => 4,
            'doors' => 3,
            'bags_small' => 1,
            'bags_large' => 1,
            'extras' => [],
            'insurance_options' => [],
            'sipp_code' => 'MDMR',
        ], 5);

        $this->assertArrayHasKey('recordgo_products', $vehicle);
        $this->assertCount(2, $vehicle['recordgo_products']);
        $this->assertSame(11, $vehicle['recordgo_products'][0]['product_id']);
        $this->assertSame('B', $vehicle['recordgo_products'][1]['rate_prod_ver']);
    }
}
