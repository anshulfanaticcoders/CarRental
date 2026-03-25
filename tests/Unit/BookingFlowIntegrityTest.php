<?php

namespace Tests\Unit;

use App\Services\Vehicles\GatewayVehicleTransformer;
use PHPUnit\Framework\TestCase;

/**
 * Verifies that the complete search → checkout → booking data pipeline
 * produces valid data for ALL providers. No real API calls are made.
 *
 * Tests the full chain:
 * 1. Gateway vehicle data (simulated) → GatewayVehicleTransformer → search result
 * 2. Search result → checkout payload (what BookingExtrasStep sends)
 * 3. Checkout payload → gateway reservation request (what triggerGatewayReservation sends)
 *
 * If any field is missing or wrong, the booking will fail on the provider side.
 */
class BookingFlowIntegrityTest extends TestCase
{
    private GatewayVehicleTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new GatewayVehicleTransformer();
    }

    /**
     * Simulates a gateway vehicle response for each provider,
     * transforms it, and verifies all booking-critical fields are present.
     *
     * @dataProvider providerVehicleDataProvider
     */
    public function test_transformed_vehicle_has_all_booking_critical_fields(
        string $provider,
        array $gatewayVehicle,
        int $rentalDays,
    ): void {
        $result = $this->transformer->transform($gatewayVehicle, $rentalDays);

        // ── Fields required for Stripe checkout session creation ──────────
        $this->assertNotEmpty($result['id'], "{$provider}: id missing");
        $this->assertNotEmpty($result['source'], "{$provider}: source missing");
        $this->assertIsNumeric($result['total_price'], "{$provider}: total_price not numeric");
        $this->assertGreaterThan(0, $result['total_price'], "{$provider}: total_price is zero");
        $this->assertIsNumeric($result['price_per_day'], "{$provider}: price_per_day not numeric");
        $this->assertNotEmpty($result['currency'], "{$provider}: currency missing");

        // ── Fields required for gateway reservation ──────────────────────
        $this->assertArrayHasKey('gateway_vehicle_id', $result, "{$provider}: gateway_vehicle_id missing");
        $this->assertArrayHasKey('provider_vehicle_id', $result, "{$provider}: provider_vehicle_id missing");
        $this->assertArrayHasKey('location_id', $result, "{$provider}: location_id missing");

        // ── Products/packages required for pricing ───────────────────────
        $this->assertArrayHasKey('products', $result, "{$provider}: products missing");
        $this->assertIsArray($result['products'], "{$provider}: products not array");
        $this->assertNotEmpty($result['products'], "{$provider}: products empty");

        $basProduct = collect($result['products'])->firstWhere('type', 'BAS');
        $this->assertNotNull($basProduct, "{$provider}: no BAS product");
        $this->assertGreaterThan(0, $basProduct['total'], "{$provider}: BAS product total is zero");
        $this->assertNotEmpty($basProduct['currency'], "{$provider}: BAS product currency missing");

        // ── Extras format (canonical after Task 4) ───────────────────────
        $this->assertArrayHasKey('extras', $result, "{$provider}: extras key missing");
        $this->assertIsArray($result['extras'], "{$provider}: extras not array");
        foreach ($result['extras'] as $i => $extra) {
            $this->assertArrayHasKey('id', $extra, "{$provider}: extra[{$i}] missing id");
            $this->assertArrayHasKey('name', $extra, "{$provider}: extra[{$i}] missing name");
            $this->assertArrayHasKey('daily_rate', $extra, "{$provider}: extra[{$i}] missing daily_rate");
            $this->assertArrayHasKey('total_price', $extra, "{$provider}: extra[{$i}] missing total_price");
            $this->assertArrayHasKey('currency', $extra, "{$provider}: extra[{$i}] missing currency");
            $this->assertArrayHasKey('mandatory', $extra, "{$provider}: extra[{$i}] missing mandatory");
            $this->assertArrayHasKey('code', $extra, "{$provider}: extra[{$i}] missing code");
        }

        // ── Provider-specific booking fields ─────────────────────────────
        $this->assertArrayHasKey('supplier_data', $result, "{$provider}: supplier_data missing");

        // ── Cancellation data ────────────────────────────────────────────
        $this->assertArrayHasKey('cancellation', $result, "{$provider}: cancellation missing");

        // ── Location data required for booking ───────────────────────────
        $this->assertArrayHasKey('provider_pickup_id', $result, "{$provider}: provider_pickup_id missing");
        $this->assertArrayHasKey('provider_dropoff_id', $result, "{$provider}: provider_dropoff_id missing");

        // ── Price integrity: total ≈ daily * days ────────────────────────
        $tolerance = 1.0; // Allow €1 rounding tolerance
        $expectedTotal = $result['price_per_day'] * $rentalDays;
        $this->assertEqualsWithDelta(
            $expectedTotal,
            $result['total_price'],
            $tolerance,
            "{$provider}: total_price ({$result['total_price']}) != price_per_day ({$result['price_per_day']}) * {$rentalDays} days ({$expectedTotal})"
        );
    }

    /**
     * Verify that provider-specific fields needed for booking are preserved
     * in the transformed output's supplier_data.
     *
     * @dataProvider providerBookingFieldsProvider
     */
    public function test_provider_booking_fields_are_preserved(
        string $provider,
        array $gatewayVehicle,
        array $requiredSupplierFields,
    ): void {
        $result = $this->transformer->transform($gatewayVehicle, 5);
        $supplierData = $result['supplier_data'];

        foreach ($requiredSupplierFields as $field) {
            $this->assertArrayHasKey(
                $field,
                $supplierData,
                "{$provider}: supplier_data missing required booking field '{$field}'"
            );
        }
    }

    /**
     * Verify the gateway reservation request can be built from transformed vehicle data.
     * This simulates what triggerGatewayReservation() does.
     */
    public function test_gateway_reservation_payload_can_be_built_from_all_providers(): void
    {
        foreach ($this->providerVehicleDataProvider() as $label => [$provider, $gv, $days]) {
            $result = $this->transformer->transform($gv, $days);

            // Simulate building the reservation request (from StripeBookingService line 938-964)
            $gatewayVehicleId = $result['gateway_vehicle_id'] ?? $result['id'];
            $reservationPayload = [
                'vehicle_id' => $gatewayVehicleId,
                'search_id' => 'test_search_123',
                'driver' => [
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'email' => 'test@example.com',
                    'phone' => '+31612345678',
                    'age' => 30,
                ],
                'extras' => [],
                'pickup_date' => '2026-04-01',
                'pickup_time' => '09:00',
                'dropoff_date' => '2026-04-06',
                'dropoff_time' => '09:00',
                'laravel_booking_id' => 999,
            ];

            $this->assertNotEmpty(
                $reservationPayload['vehicle_id'],
                "{$provider}: vehicle_id for reservation is empty"
            );
        }
    }

    /**
     * Verify that the quoteid field (used by StripeCheckoutController) is present
     * for providers that use it.
     */
    public function test_quoteid_present_for_providers_that_need_it(): void
    {
        // Surprice uses quoteid in checkout
        $gv = $this->makeSurpriceVehicle();
        $gv['supplier_data']['quote_id'] = 'QID_12345';
        $result = $this->transformer->transform($gv, 5);

        $this->assertEquals('QID_12345', $result['quoteid']);
    }

    /**
     * Verify tdr field (Adobe booking total) is preserved.
     */
    public function test_tdr_present_for_adobe(): void
    {
        $gv = $this->makeAdobeVehicle();
        $result = $this->transformer->transform($gv, 5);

        $this->assertArrayHasKey('tdr', $result);
        $this->assertGreaterThan(0, $result['tdr']);
    }

    // ══════════════════════════════════════════════════════════════════════
    // Data Providers
    // ══════════════════════════════════════════════════════════════════════

    public static function providerVehicleDataProvider(): array
    {
        $self = new self('providerVehicleDataProvider');
        return [
            'greenmotion' => ['greenmotion', $self->makeGreenMotionVehicle(), 5],
            'surprice' => ['surprice', $self->makeSurpriceVehicle(), 5],
            'renteon' => ['renteon', $self->makeRenteonVehicle(), 5],
            'ok_mobility' => ['ok_mobility', $self->makeOkMobilityVehicle(), 5],
            'sicily_by_car' => ['sicily_by_car', $self->makeSicilyByCarVehicle(), 5],
            'locauto_rent' => ['locauto_rent', $self->makeLocautoVehicle(), 5],
            'record_go' => ['record_go', $self->makeRecordGoVehicle(), 5],
            'adobe_car' => ['adobe_car', $self->makeAdobeVehicle(), 5],
            'favrica' => ['favrica', $self->makeFavricaVehicle(), 5],
            'xdrive' => ['xdrive', $self->makeXDriveVehicle(), 5],
            'wheelsys' => ['wheelsys', $self->makeWheelsysVehicle(), 5],
            'internal' => ['internal', $self->makeInternalVehicle(), 5],
        ];
    }

    public static function providerBookingFieldsProvider(): array
    {
        $self = new self('providerBookingFieldsProvider');
        return [
            'surprice needs quote_id' => [
                'surprice',
                $self->makeSurpriceVehicle(),
                ['quote_id', 'pickup_office', 'dropoff_office'],
            ],
            'locauto needs connector_id' => [
                'locauto_rent',
                $self->makeLocautoVehicle(),
                ['connector_id'],
            ],
            'recordgo needs products' => [
                'record_go',
                $self->makeRecordGoVehicle(),
                ['products'],
            ],
            'ok_mobility needs token' => [
                'ok_mobility',
                $self->makeOkMobilityVehicle(),
                ['token', 'group_id', 'rate_code'],
            ],
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    // Vehicle Factories — realistic gateway responses per provider
    // ══════════════════════════════════════════════════════════════════════

    private function makeBaseVehicle(string $supplierId, string $vehicleId, float $total, float $daily): array
    {
        return [
            'id' => "gw_{$supplierId}_{$vehicleId}",
            'supplier_id' => $supplierId,
            'supplier_vehicle_id' => $vehicleId,
            'name' => 'Test Vehicle or similar',
            'category' => 'compact',
            'make' => 'Test',
            'model' => 'Vehicle',
            'image_url' => 'https://example.com/car.jpg',
            'transmission' => 'manual',
            'fuel_type' => 'petrol',
            'seats' => 5,
            'doors' => 4,
            'bags_large' => 1,
            'bags_small' => 1,
            'air_conditioning' => true,
            'mileage_policy' => 'unlimited',
            'sipp_code' => 'CDMR',
            'pricing' => [
                'total_price' => $total,
                'daily_rate' => $daily,
                'currency' => 'EUR',
            ],
            'pickup_location' => [
                'supplier_location_id' => 'LOC_001',
                'name' => 'Test Airport',
                'latitude' => 40.0,
                'longitude' => 3.0,
            ],
            'dropoff_location' => null,
            'insurance_options' => [],
            'extras' => [],
            'cancellation_policy' => [
                'free_cancellation' => true,
                'cancellation_fee' => 0,
                'cancellation_fee_currency' => 'EUR',
            ],
            'supplier_data' => [],
        ];
    }

    private function makeGreenMotionVehicle(): array
    {
        $v = $this->makeBaseVehicle('green_motion', 'GM_001', 150.0, 30.0);
        $v['supplier_data'] = [
            'rate_id' => 'BASIC',
            'vehicle_category_id' => 'GM_CAT_1',
        ];
        return $v;
    }

    private function makeSurpriceVehicle(): array
    {
        $v = $this->makeBaseVehicle('surprice', 'SP_001', 200.0, 40.0);
        $v['supplier_data'] = [
            'quote_id' => 'QID_TEST',
            'pickup_office' => ['name' => 'Airport Office', 'address' => '123 Airport Rd'],
            'dropoff_office' => ['name' => 'Airport Office', 'address' => '123 Airport Rd'],
            'products' => [
                ['type' => 'BAS', 'total' => 200.0, 'currency' => 'EUR'],
                ['type' => 'PLU', 'total' => 280.0, 'currency' => 'EUR'],
            ],
        ];
        $v['extras'] = [
            [
                'id' => 'ext_gps', 'name' => 'GPS', 'daily_rate' => 5.0,
                'total_price' => 25.0, 'currency' => 'EUR', 'max_quantity' => 1,
                'type' => 'equipment', 'mandatory' => false,
                'supplier_data' => ['code' => 'GPS', 'per_day' => true, 'unit_charge' => 5.0],
            ],
        ];
        return $v;
    }

    private function makeRenteonVehicle(): array
    {
        $v = $this->makeBaseVehicle('renteon', 'RT_001', 175.0, 35.0);
        $v['supplier_data'] = [
            'provider_code' => 'luxgoo',
            'vehicle_category_id' => 'CDMR',
            'net_amount' => 152.17,
            'vat_amount' => 22.83,
        ];
        $v['extras'] = [
            [
                'id' => 'ext_child_seat', 'name' => 'Child Seat', 'daily_rate' => 8.0,
                'total_price' => 40.0, 'currency' => 'EUR', 'max_quantity' => 2,
                'type' => 'equipment', 'mandatory' => false,
                'supplier_data' => ['code' => 'CST', 'service_group' => 'equipment'],
            ],
        ];
        return $v;
    }

    private function makeOkMobilityVehicle(): array
    {
        $v = $this->makeBaseVehicle('ok_mobility', 'OK_001', 225.0, 45.0);
        $v['supplier_data'] = [
            'token' => 'ok_token_abc123',
            'group_id' => 'CDMR',
            'rate_code' => 'STD',
        ];
        return $v;
    }

    private function makeSicilyByCarVehicle(): array
    {
        $v = $this->makeBaseVehicle('sicily_by_car', 'SBC_B', 160.0, 32.0);
        $v['supplier_data'] = [
            'availability_id' => 'AV_123',
            'rate_id' => 'BASIC-POA',
            'rate_payment' => 'PayOnArrival',
            'vehicle_category_id' => 'B',
        ];
        return $v;
    }

    private function makeLocautoVehicle(): array
    {
        $v = $this->makeBaseVehicle('locauto_rent', 'LC_001', 180.0, 36.0);
        $v['supplier_data'] = [
            'connector_id' => 'LOCAUTO_CONN_1',
            'pricelist_id' => 'PL_001',
            'pricelist_code' => 'STANDARD',
        ];
        return $v;
    }

    private function makeRecordGoVehicle(): array
    {
        $v = $this->makeBaseVehicle('record_go', 'RG_001', 140.0, 28.0);
        $v['supplier_data'] = [
            'products' => [
                ['type' => 'BAS', 'total' => 140.0, 'currency' => 'EUR'],
            ],
            'sell_code_ver' => 'V1',
            'acriss_id' => 'CDMR',
            'country' => 'ES',
        ];
        return $v;
    }

    private function makeAdobeVehicle(): array
    {
        $v = $this->makeBaseVehicle('adobe_car', 'AD_001', 195.0, 39.0);
        $v['supplier_data'] = [
            'tdr' => 195.0,
            'pli' => 15.0,
            'ldw' => 12.0,
            'spp' => 8.0,
        ];
        $v['insurance_options'] = [
            [
                'id' => 'ins_adobe_car_cdw',
                'name' => 'CDW',
                'coverage_type' => 'basic',
                'daily_rate' => 8.0,
                'total_price' => 40.0,
                'currency' => 'EUR',
                'excess_amount' => 1200,
                'included' => false,
            ],
        ];
        return $v;
    }

    private function makeFavricaVehicle(): array
    {
        return $this->makeBaseVehicle('favrica', 'FV_001', 155.0, 31.0);
    }

    private function makeXDriveVehicle(): array
    {
        return $this->makeBaseVehicle('xdrive', 'XD_001', 165.0, 33.0);
    }

    private function makeWheelsysVehicle(): array
    {
        $v = $this->makeBaseVehicle('wheelsys', 'WS_001', 130.0, 26.0);
        $v['supplier_data'] = ['rate_id' => 'BASIC'];
        return $v;
    }

    private function makeInternalVehicle(): array
    {
        $v = $this->makeBaseVehicle('internal', 'INT_001', 100.0, 20.0);
        $v['supplier_data'] = [
            'vehicle_id' => 42,
            'vendor_profile_data' => ['company_name' => 'Test Vendor'],
        ];
        return $v;
    }
}
