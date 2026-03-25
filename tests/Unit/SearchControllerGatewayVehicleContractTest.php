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
        $this->assertSame('alquicoche|ford|focus', $vehicle['provider_vehicle_id']);
        $this->assertSame('ES-AVI-DT', $vehicle['provider_pickup_id']);
        $this->assertSame('EUR', $vehicle['currency']);
        $this->assertSame('', $vehicle['image']);
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

    public function test_it_uses_adobe_supplier_booking_total_for_frontend_contract(): void
    {
        $transformer = new GatewayVehicleTransformer();

        $vehicle = $transformer->transform([
            'id' => 'gw_adobe_1',
            'supplier_id' => 'adobe_car',
            'supplier_vehicle_id' => 'adobe-veh-1',
            'make' => 'Kia',
            'model' => 'Picanto AT',
            'category' => 'mini',
            'image_url' => 'https://example.com/kia.png',
            'pricing' => [
                'total_price' => 268.83,
                'daily_rate' => 89.61,
                'currency' => 'USD',
            ],
            'pickup_location' => [
                'supplier_location_id' => 'OCO',
                'name' => 'San Jose Airport',
                'latitude' => 10.00413,
                'longitude' => -84.185504,
            ],
            'supplier_data' => [
                'tdr' => 89.61,
                'pli' => 40.68,
                'ldw' => 15.82,
                'spp' => 20.34,
                'pickup_office' => 'OCO',
                'return_office' => 'OCO',
                'pickup_datetime' => '2026-04-15 09:00',
                'dropoff_datetime' => '2026-04-18 09:00',
            ],
            'transmission' => 'automatic',
            'fuel_type' => 'petrol',
            'seats' => 4,
            'doors' => 4,
            'bags_small' => 1,
            'bags_large' => 1,
            'extras' => [],
            'insurance_options' => [],
        ], 3);

        $this->assertSame(89.61, $vehicle['total_price']);
        $this->assertSame(29.87, $vehicle['price_per_day']);
        $this->assertSame(89.61, $vehicle['products'][0]['total']);
        $this->assertSame(29.87, $vehicle['products'][0]['price_per_day']);
        $this->assertSame(89.61, $vehicle['tdr']);
    }

    public function test_it_accepts_canonical_greenmotion_payload_without_inventing_specs(): void
    {
        $transformer = new GatewayVehicleTransformer();

        $vehicle = $transformer->transform([
            'id' => 'gw_gm_1',
            'gateway_vehicle_id' => 'gw_gm_1',
            'provider_vehicle_id' => 'gm_veh_1',
            'source' => 'greenmotion',
            'provider_code' => 'greenmotion',
            'display_name' => 'Volkswagen Up or similar',
            'brand' => 'Volkswagen',
            'model' => 'Up',
            'category' => 'mini',
            'image' => 'https://example.com/up.png',
            'specs' => [
                'transmission' => null,
                'fuel' => 'petrol',
                'seating_capacity' => null,
                'doors' => null,
                'luggage_small' => 1,
                'luggage_medium' => null,
                'luggage_large' => 1,
                'air_conditioning' => null,
                'sipp_code' => 'MBMR',
            ],
            'pricing' => [
                'total_price' => 120.0,
                'price_per_day' => 40.0,
                'currency' => 'EUR',
                'deposit_amount' => 300.0,
                'deposit_currency' => 'EUR',
                'excess_amount' => 900.0,
                'excess_theft_amount' => 1200.0,
            ],
            'policies' => [
                'mileage_policy' => null,
                'mileage_limit_km' => null,
                'fuel_policy' => 'Same to same',
                'cancellation' => null,
            ],
            'products' => [
                [
                    'type' => 'BAS',
                    'name' => 'Basic',
                    'total' => 120.0,
                    'price_per_day' => 40.0,
                    'currency' => 'EUR',
                    'benefits' => [
                        'fuel_policy' => 'Same to same',
                    ],
                ],
                [
                    'type' => 'PRE',
                    'name' => 'Premium',
                    'total' => 150.0,
                    'price_per_day' => 50.0,
                    'currency' => 'EUR',
                    'benefits' => [
                        'fuel_policy' => 'Full to full',
                    ],
                ],
            ],
            'extras_preview' => [
                [
                    'id' => 'ext_green_motion_1',
                    'name' => 'Baby Seat',
                    'type' => 'equipment',
                    'currency' => 'EUR',
                    'daily_rate' => 5.0,
                    'total_price' => 15.0,
                    'mandatory' => false,
                ],
            ],
            'location' => [
                'pickup' => [
                    'provider_location_id' => '359',
                    'name' => 'Marrakech Airport',
                    'latitude' => 31.6069,
                    'longitude' => -8.0363,
                ],
                'dropoff' => [
                    'provider_location_id' => '359',
                    'name' => 'Marrakech Airport',
                    'latitude' => 31.6069,
                    'longitude' => -8.0363,
                ],
            ],
            'data_quality_flags' => ['missing_transmission', 'missing_seating_capacity', 'missing_doors'],
            'pricing_transparency_flags' => [],
            'ui_placeholders' => ['image' => false],
            'booking_context' => [
                'version' => 1,
                'provider_payload' => [
                    'quote_id' => 'quote-1',
                    'products' => [
                        ['type' => 'BAS', 'total' => 120.0, 'currency' => 'EUR'],
                        ['type' => 'PRE', 'total' => 150.0, 'currency' => 'EUR'],
                    ],
                ],
            ],
        ], 3);

        $this->assertSame('greenmotion', $vehicle['source']);
        $this->assertSame('Volkswagen', $vehicle['brand']);
        $this->assertSame('Up', $vehicle['model']);
        $this->assertSame(120.0, $vehicle['total_price']);
        $this->assertSame(40.0, $vehicle['price_per_day']);
        $this->assertNull($vehicle['transmission']);
        $this->assertNull($vehicle['seating_capacity']);
        $this->assertNull($vehicle['doors']);
        $this->assertNull($vehicle['airConditioning']);
        $this->assertSame('Same to same', $vehicle['benefits']['fuel_policy']);
        $this->assertCount(2, $vehicle['products']);
        $this->assertSame('PRE', $vehicle['products'][1]['type']);
        $this->assertSame(15.0, $vehicle['extras'][0]['total_price']);
        $this->assertSame('359', $vehicle['provider_pickup_id']);
    }

    public function test_it_does_not_invent_missing_specs_for_legacy_gateway_payloads(): void
    {
        $transformer = new GatewayVehicleTransformer();

        $vehicle = $transformer->transform([
            'id' => 'gw_xd_1',
            'supplier_id' => 'xdrive',
            'supplier_vehicle_id' => 'rz-1',
            'make' => 'Hyundai',
            'model' => 'i20',
            'category' => 'economy',
            'image_url' => 'https://example.com/i20.png',
            'pricing' => [
                'total_price' => 180.0,
                'daily_rate' => 60.0,
                'currency' => 'EUR',
            ],
            'pickup_location' => [
                'supplier_location_id' => 'AMM',
                'name' => 'Amman Airport',
                'latitude' => 31.7226,
                'longitude' => 35.9932,
            ],
            'supplier_data' => [],
            'extras' => [],
            'insurance_options' => [],
        ], 3);

        $this->assertNull($vehicle['transmission']);
        $this->assertNull($vehicle['fuel']);
        $this->assertNull($vehicle['seating_capacity']);
        $this->assertNull($vehicle['doors']);
        $this->assertNull($vehicle['airConditioning']);
        $this->assertNull($vehicle['mileage']);
        $this->assertSame([], $vehicle['features']);
        $this->assertNull($vehicle['luggageSmall']);
        $this->assertNull($vehicle['luggageLarge']);
        $this->assertNull($vehicle['bags']);
        $this->assertNull($vehicle['suitcases']);
    }
}
