<?php

namespace Tests\Unit;

use App\Services\Vehicles\GatewayVehicleTransformer;
use PHPUnit\Framework\TestCase;

class GatewayVehicleTransformerTest extends TestCase
{
    private GatewayVehicleTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new GatewayVehicleTransformer();
    }

    /**
     * Canonical fields that EVERY extra must have, regardless of provider.
     */
    private const CANONICAL_EXTRA_FIELDS = [
        'id', 'name', 'description', 'code', 'price', 'daily_rate',
        'total_price', 'price_per_day', 'currency', 'mandatory', 'type',
        'max_quantity', 'per_day',
    ];

    /**
     * Legacy alias fields preserved for backward compatibility.
     */
    private const LEGACY_ALIAS_FIELDS = [
        'Daily_rate', 'total_for_booking', 'Total_for_this_booking',
        'total_for_booking_currency', 'Total_for_this_booking_currency',
        'option_id', 'optionID', 'amount', 'service_id',
        'numberAllowed', 'required',
    ];

    public function test_all_providers_return_extras_in_canonical_format(): void
    {
        foreach (['surprice', 'renteon', 'ok_mobility', 'sicily_by_car', 'locauto_rent'] as $provider) {
            $gv = $this->makeGatewayVehicle($provider);
            $result = $this->transformer->transform($gv, 5);

            $this->assertArrayHasKey('extras', $result, "Provider {$provider} missing extras key");
            $this->assertNotEmpty($result['extras'], "Provider {$provider} has empty extras");

            foreach ($result['extras'] as $i => $extra) {
                foreach (self::CANONICAL_EXTRA_FIELDS as $field) {
                    $this->assertArrayHasKey(
                        $field,
                        $extra,
                        "Provider {$provider} extra[{$i}] missing canonical field: {$field}"
                    );
                }
                foreach (self::LEGACY_ALIAS_FIELDS as $field) {
                    $this->assertArrayHasKey(
                        $field,
                        $extra,
                        "Provider {$provider} extra[{$i}] missing legacy alias field: {$field}"
                    );
                }
            }
        }
    }

    public function test_default_provider_extras_have_canonical_format(): void
    {
        $gv = $this->makeGatewayVehicle('record_go');
        $result = $this->transformer->transform($gv, 5);

        $this->assertNotEmpty($result['extras']);
        foreach ($result['extras'] as $extra) {
            foreach (self::CANONICAL_EXTRA_FIELDS as $field) {
                $this->assertArrayHasKey($field, $extra, "Default provider extra missing field: {$field}");
            }
        }
    }

    public function test_options_mirror_extras(): void
    {
        $gv = $this->makeGatewayVehicle('surprice');
        $result = $this->transformer->transform($gv, 5);

        $this->assertSame($result['extras'], $result['options']);
    }

    public function test_sicily_by_car_extras_preserve_supplier_data_fields(): void
    {
        $gv = $this->makeGatewayVehicle('sicily_by_car');
        $result = $this->transformer->transform($gv, 5);

        $extra = $result['extras'][0];
        // WHY 'id' is overridden here: Sicily by Car's supplier_data.id carries the protection-plan
        // code (e.g. 'CDW') that the frontend SBC adapter reads to match protection plans. The
        // canonical extra id ('sbc_ext_1') is the gateway's internal ID; the supplier_data id is the
        // supplier's own code. mapExtras() intentionally allows supplier_data.id to override the
        // canonical id for this reason — all other canonical keys are protected from $sd collisions.
        $this->assertSame('CDW', $extra['id']);
        $this->assertSame('CDW Insurance', $extra['name']);
        $this->assertSame(8.0, $extra['daily_rate']);
        // supplier_data fields merged in for backward compat
        $this->assertArrayHasKey('isMandatory', $extra);
        $this->assertArrayHasKey('excess', $extra);
        $this->assertArrayHasKey('excessAmount', $extra);
        $this->assertArrayHasKey('payment', $extra);
    }

    public function test_ok_mobility_extras_preserve_supplier_data_fields(): void
    {
        $gv = $this->makeGatewayVehicle('ok_mobility');
        $result = $this->transformer->transform($gv, 5);

        $extra = $result['extras'][0];
        $this->assertSame('ok_ext_1', $extra['id']);
        // supplier_data fields merged in
        $this->assertArrayHasKey('extraID', $extra);
        $this->assertArrayHasKey('extra', $extra);
        $this->assertArrayHasKey('value', $extra);
        $this->assertArrayHasKey('valueWithTax', $extra);
        $this->assertArrayHasKey('pricePerContract', $extra);
        $this->assertArrayHasKey('extra_Included', $extra);
        $this->assertArrayHasKey('extra_Required', $extra);
    }

    public function test_renteon_extras_preserve_supplier_data_fields(): void
    {
        $gv = $this->makeGatewayVehicle('renteon');
        $result = $this->transformer->transform($gv, 5);

        $extra = $result['extras'][0];
        $this->assertSame('ren_ext_1', $extra['id']);
        // supplier_data fields merged in
        $this->assertArrayHasKey('service_group', $extra);
        $this->assertArrayHasKey('service_type', $extra);
        $this->assertArrayHasKey('free_of_charge', $extra);
        $this->assertArrayHasKey('included_in_price', $extra);
        $this->assertArrayHasKey('is_one_time', $extra);
        $this->assertArrayHasKey('quantity_included', $extra);
        // computed enrichment
        $this->assertArrayHasKey('included', $extra);
    }

    public function test_locauto_extras_preserve_supplier_data_fields(): void
    {
        $gv = $this->makeGatewayVehicle('locauto_rent');
        $result = $this->transformer->transform($gv, 5);

        $extra = $result['extras'][0];
        $this->assertSame('loc_ext_1', $extra['id']);
        $this->assertArrayHasKey('included_in_rate', $extra);
    }

    public function test_surprice_extras_preserve_supplier_data_fields(): void
    {
        $gv = $this->makeGatewayVehicle('surprice');
        $result = $this->transformer->transform($gv, 5);

        $extra = $result['extras'][0];
        $this->assertSame('sur_ext_1', $extra['id']);
        $this->assertArrayHasKey('allow_quantity', $extra);
        $this->assertArrayHasKey('purpose', $extra);
        $this->assertTrue($extra['per_day']);
    }

    public function test_canonical_price_fields_are_numeric(): void
    {
        foreach (['surprice', 'renteon', 'ok_mobility', 'sicily_by_car', 'locauto_rent', 'record_go'] as $provider) {
            $gv = $this->makeGatewayVehicle($provider);
            $result = $this->transformer->transform($gv, 5);

            foreach ($result['extras'] as $i => $extra) {
                $this->assertIsFloat($extra['price'], "Provider {$provider} extra[{$i}] price not float");
                $this->assertIsFloat($extra['daily_rate'], "Provider {$provider} extra[{$i}] daily_rate not float");
                $this->assertIsFloat($extra['total_price'], "Provider {$provider} extra[{$i}] total_price not float");
                $this->assertIsFloat($extra['price_per_day'], "Provider {$provider} extra[{$i}] price_per_day not float");
            }
        }
    }

    public function test_empty_extras_returns_empty_array(): void
    {
        $gv = $this->makeGatewayVehicle('surprice', []);
        $result = $this->transformer->transform($gv, 5);

        $this->assertSame([], $result['extras']);
        $this->assertSame([], $result['options']);
    }

    public function test_it_preserves_enriched_pickup_location_fields_from_gateway_payload(): void
    {
        $gv = $this->makeGatewayVehicle('locauto_rent');
        $gv['pickup_location'] = [
            'supplier_location_id' => 'FCO',
            'name' => 'Roma Fiumicino Airport',
            'latitude' => 41.79479,
            'longitude' => 12.25221,
            'address' => "AEROPORTO L.DA VINCI - Via dell'aeroporto di Fiumicino 320, 00054 Fiumicino (RM)",
            'phone' => '+39 06 65953615',
            'operating_hours' => [
                'weekday' => '07:00 - 24:00',
                'saturday' => '07:00 - 24:00',
                'sunday' => '07:00 - 24:00',
            ],
            'pickup_instructions' => 'Desk inside the Epua 2 Tower.',
            'dropoff_instructions' => 'Follow the Car Rental signs to Multilevel Parking C.',
            'is_airport' => true,
        ];
        $gv['dropoff_location'] = $gv['pickup_location'];

        $result = $this->transformer->transform($gv, 5);

        $this->assertSame($gv['pickup_location']['address'], $result['pickup_address']);
        $this->assertSame($gv['pickup_location']['address'], $result['dropoff_address']);
        $this->assertSame($gv['pickup_location']['phone'], $result['office_phone']);
        $this->assertSame($gv['pickup_location']['operating_hours'], $result['office_schedule']);
        $this->assertSame($gv['pickup_location']['pickup_instructions'], $result['pickup_instructions']);
        $this->assertSame($gv['pickup_location']['dropoff_instructions'], $result['dropoff_instructions']);
        $this->assertTrue($result['at_airport']);
    }

    public function test_renteon_included_computed_field(): void
    {
        $gv = $this->makeGatewayVehicle('renteon');
        // Add an extra with free_of_charge = true
        $gv['extras'][] = [
            'id' => 'ren_ext_free',
            'name' => 'Free GPS',
            'description' => 'Free GPS',
            'daily_rate' => 0,
            'total_price' => 0,
            'currency' => 'EUR',
            'max_quantity' => 1,
            'mandatory' => false,
            'type' => 'equipment',
            'supplier_data' => [
                'code' => 'GPS',
                'service_group' => 'extras',
                'service_type' => 'GPS',
                'free_of_charge' => true,
                'included_in_price' => false,
                'included_in_price_limited' => false,
                'is_one_time' => false,
                'quantity_included' => 1,
            ],
        ];

        $result = $this->transformer->transform($gv, 5);

        $freeExtra = collect($result['extras'])->firstWhere('id', 'ren_ext_free');
        $this->assertNotNull($freeExtra);
        $this->assertTrue($freeExtra['included']);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Fixture builder
    // ──────────────────────────────────────────────────────────────────────

    private function makeGatewayVehicle(string $supplierId, ?array $extras = null): array
    {
        $defaultExtras = $this->makeProviderExtras($supplierId);

        return [
            'id' => 'gv_' . $supplierId . '_1',
            'supplier_id' => $supplierId,
            'supplier_vehicle_id' => 'veh_1',
            'make' => 'Fiat',
            'model' => 'Panda',
            'category' => 'economy',
            'image_url' => 'https://example.com/car.jpg',
            'seats' => 5,
            'doors' => 4,
            'transmission' => 'manual',
            'fuel_type' => 'petrol',
            'air_conditioning' => true,
            'bags_large' => 1,
            'bags_small' => 1,
            'sipp_code' => 'EDMR',
            'mileage_policy' => 'unlimited',
            'pricing' => [
                'total_price' => 200.0,
                'daily_rate' => 40.0,
                'currency' => 'EUR',
            ],
            'pickup_location' => [
                'supplier_location_id' => 'loc_1',
                'name' => 'Test Airport',
                'latitude' => 40.0,
                'longitude' => 2.0,
            ],
            'dropoff_location' => [
                'supplier_location_id' => 'loc_1',
            ],
            'extras' => $extras ?? $defaultExtras,
            'insurance_options' => [],
            'supplier_data' => [],
            'cancellation_policy' => [],
        ];
    }

    private function makeProviderExtras(string $supplierId): array
    {
        return match ($supplierId) {
            'sicily_by_car' => [
                [
                    'id' => 'sbc_ext_1',
                    'name' => 'CDW Insurance',
                    'description' => 'CDW Insurance',
                    'daily_rate' => 8.0,
                    'total_price' => 40.0,
                    'currency' => 'EUR',
                    'max_quantity' => 1,
                    'mandatory' => true,
                    'type' => 'insurance',
                    'supplier_data' => [
                        'id' => 'CDW',
                        'description' => 'CDW Insurance',
                        'isMandatory' => true,
                        'total' => 40.0,
                        'excess' => 800,
                        'excessAmount' => 800,
                        'payment' => 'prepaid',
                    ],
                ],
            ],
            'ok_mobility' => [
                [
                    'id' => 'ok_ext_1',
                    'name' => 'Baby Seat',
                    'description' => 'Baby Seat for infants',
                    'daily_rate' => 5.0,
                    'total_price' => 25.0,
                    'currency' => 'EUR',
                    'max_quantity' => 2,
                    'mandatory' => false,
                    'type' => 'equipment',
                    'supplier_data' => [
                        'extraID' => 'BSEAT',
                        'code' => 'BSEAT',
                        'extra' => 'Baby Seat',
                        'value' => '5.00',
                        'valueWithTax' => '6.05',
                        'pricePerContract' => 'false',
                        'extra_Included' => 'false',
                        'extra_Required' => 'false',
                    ],
                ],
            ],
            'renteon' => [
                [
                    'id' => 'ren_ext_1',
                    'name' => 'Child Seat',
                    'description' => 'Child Seat 0-13kg',
                    'daily_rate' => 4.0,
                    'total_price' => 20.0,
                    'currency' => 'EUR',
                    'max_quantity' => 3,
                    'mandatory' => false,
                    'type' => 'equipment',
                    'supplier_data' => [
                        'code' => 'CS01',
                        'service_group' => 'extras',
                        'service_type' => 'child_seat',
                        'free_of_charge' => false,
                        'included_in_price' => false,
                        'included_in_price_limited' => false,
                        'is_one_time' => false,
                        'quantity_included' => 0,
                    ],
                ],
            ],
            'locauto_rent' => [
                [
                    'id' => 'loc_ext_1',
                    'name' => 'GPS Navigator',
                    'description' => 'GPS Navigator',
                    'daily_rate' => 6.0,
                    'total_price' => 30.0,
                    'currency' => 'EUR',
                    'max_quantity' => 1,
                    'mandatory' => false,
                    'type' => 'equipment',
                    'supplier_data' => [
                        'code' => '43',
                        'amount' => 30.0,
                        'included_in_rate' => false,
                    ],
                ],
            ],
            'surprice' => [
                [
                    'id' => 'sur_ext_1',
                    'name' => 'Additional Driver',
                    'description' => 'Additional Driver',
                    'daily_rate' => 7.0,
                    'total_price' => 35.0,
                    'currency' => 'EUR',
                    'max_quantity' => 1,
                    'mandatory' => false,
                    'type' => 'equipment',
                    'supplier_data' => [
                        'code' => 'ADR',
                        'per_day' => true,
                        'allow_quantity' => 3,
                        'purpose' => 'driver',
                        'unit_charge' => 7.0,
                    ],
                ],
            ],
            default => [
                [
                    'id' => 'def_ext_1',
                    'name' => 'Snow Chains',
                    'description' => 'Snow Chains',
                    'daily_rate' => 3.0,
                    'total_price' => 15.0,
                    'currency' => 'EUR',
                    'max_quantity' => 1,
                    'mandatory' => false,
                    'type' => 'equipment',
                ],
            ],
        };
    }
}
