<?php

namespace Tests\Unit;

use App\Http\Controllers\StripeCheckoutController;
use App\Services\StripeBookingService;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class StripeCheckoutControllerLocationFallbackTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_builds_generic_location_details_from_vehicle_address_and_coordinates(): void
    {
        $controller = $this->makeController();

        $validated = [
            'vehicle' => [
                'source' => 'recordgo',
                'full_vehicle_address' => 'Terminal 1, Lisbon Airport',
                'latitude' => 38.7742,
                'longitude' => -9.1342,
            ],
        ];

        [$pickup, $dropoff] = $this->resolveCheckoutLocationDetails($controller, $validated);

        $this->assertSame('Terminal 1, Lisbon Airport', $pickup['name']);
        $this->assertSame('Terminal 1, Lisbon Airport', $pickup['address_1']);
        $this->assertSame(38.7742, $pickup['latitude']);
        $this->assertSame(-9.1342, $pickup['longitude']);
        $this->assertSame($pickup, $dropoff);
    }

    public function test_it_prefers_provider_specific_station_and_address_fields_when_available(): void
    {
        $controller = $this->makeController();

        $validated = [
            'vehicle' => [
                'source' => 'okmobility',
                'pickup_station_name' => 'Palma Shuttle Point',
                'pickup_address' => 'Camí de Can Pastilla 41',
                'dropoff_station_name' => 'Palma Return Point',
                'dropoff_address' => 'Camí de Can Pastilla 99',
                'latitude' => 39.5517,
                'longitude' => 2.7388,
            ],
        ];

        [$pickup, $dropoff] = $this->resolveCheckoutLocationDetails($controller, $validated);

        $this->assertSame('Palma Shuttle Point', $pickup['name']);
        $this->assertSame('Camí de Can Pastilla 41', $pickup['address_1']);
        $this->assertSame('Palma Return Point', $dropoff['name']);
        $this->assertSame('Camí de Can Pastilla 99', $dropoff['address_1']);
    }

    public function test_it_keeps_explicit_location_details_from_request(): void
    {
        $controller = $this->makeController();

        $validated = [
            'location_details' => [
                'name' => 'Explicit Pickup',
                'address_1' => 'Custom Street 1',
            ],
            'vehicle' => [
                'source' => 'xdrive',
                'full_vehicle_address' => 'Should Not Override',
                'latitude' => 10,
                'longitude' => 20,
            ],
        ];

        [$pickup, $dropoff] = $this->resolveCheckoutLocationDetails($controller, $validated);

        $this->assertSame('Explicit Pickup', $pickup['name']);
        $this->assertSame('Custom Street 1', $pickup['address_1']);
        $this->assertSame('Should Not Override', $dropoff['name']);
        $this->assertSame('Should Not Override', $dropoff['address_1']);
    }

    public function test_it_uses_renteon_office_payload_when_location_details_are_missing(): void
    {
        $controller = $this->makeController();

        $pickupOffice = [
            'office_id' => 1094,
            'name' => 'Alquicoche Avila',
            'address' => 'Avenida de Madrid 23',
            'town' => 'Avila',
            'postal_code' => '05001',
            'phone' => '+34 123 456',
            'email' => 'office@example.com',
            'pickup_instructions' => 'Desk near parking gate',
        ];

        $validated = [
            'vehicle' => [
                'source' => 'renteon',
                'pickup_office' => $pickupOffice,
                'dropoff_office' => null,
                'latitude' => 40.656685,
                'longitude' => -4.681208,
            ],
        ];

        [$pickup, $dropoff] = $this->resolveCheckoutLocationDetails($controller, $validated);

        $this->assertSame($pickupOffice['name'], $pickup['name']);
        $this->assertSame($pickupOffice['address'], $pickup['address_1']);
        $this->assertSame(40.656685, $pickup['latitude']);
        $this->assertSame(-4.681208, $pickup['longitude']);
        $this->assertSame($pickup['latitude'], $dropoff['latitude']);
        $this->assertSame($pickup['longitude'], $dropoff['longitude']);
    }

    public function test_it_does_not_copy_pickup_details_to_one_way_dropoff(): void
    {
        $controller = $this->makeController();

        $validated = [
            'pickup_location' => 'Dubai Airport (DXB)',
            'dropoff_location' => 'Dubai Downtown',
            'vehicle' => [
                'source' => 'surprice',
                'pickup_station_name' => 'Dubai Airport',
                'pickup_address' => '34 24 St - Hor Al Anz East - Dubai - United Arab Emirates',
                'dropoff_station_name' => 'Dubai Airport',
                'dropoff_address' => '34 24 St - Hor Al Anz East - Dubai - United Arab Emirates',
                'latitude' => 25.2815459,
                'longitude' => 55.3519485,
            ],
        ];

        [$pickup, $dropoff] = $this->resolveCheckoutLocationDetails($controller, $validated);

        $this->assertSame('Dubai Airport', $pickup['name']);
        $this->assertNull($dropoff);
    }

    public function test_it_counts_recordgo_resolved_complements_as_provider_options_total(): void
    {
        $controller = $this->makeController();

        $result = $this->computeServerTotals($controller, [
            'vehicle' => [
                'source' => 'recordgo',
                'products' => [
                    ['type' => 'RG_PRE', 'total' => 100.00, 'currency' => 'EUR'],
                ],
            ],
            'package' => 'RG_PRE',
            'number_of_days' => 2,
            'detailed_extras' => [
                [
                    'id' => 'ext_recordgo_44',
                    'qty' => 1,
                    'total_for_booking' => 18.50,
                ],
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(18.50, $result['provider_options_total']);
        $this->assertSame(118.50, $result['provider_grand_total']);
        $this->assertSame(118.50, $result['booking_total_net']);
    }

    public function test_it_does_not_apply_external_markup_to_internal_partner_offer_totals(): void
    {
        $controller = $this->makeController();

        $result = $this->computeServerTotals($controller, [
            'vehicle' => [
                'source' => 'internal',
                'products' => [
                    ['type' => 'BAS', 'total' => 1560.00, 'currency' => 'AED'],
                ],
            ],
            'package' => 'BAS',
            'number_of_days' => 3,
            'total_amount' => 1665.00,
            'detailed_extras' => [
                [
                    'id' => 'internal_addon_223',
                    'qty' => 1,
                    'total_for_booking' => 105.00,
                ],
            ],
        ], 'AED', 'AED');

        $this->assertTrue($result['success']);
        $this->assertSame(105.00, $result['provider_options_total']);
        $this->assertSame(1665.00, $result['booking_total']);
        $this->assertSame(1665.00, $result['booking_total_net']);
        $this->assertSame(249.75, $result['booking_deposit']);
        $this->assertSame(0.0, $result['provider_commission_rate']);
    }

    public function test_it_only_applies_trabber_attribution_to_trabber_offer_sessions(): void
    {
        $controller = $this->makeController();

        $this->assertFalse($this->shouldApplyTrabberAttribution(
            $controller,
            'skyscanner_offer_59ac3f15-e82c-4d67-8113-77c8a27cbeea',
            ['quoteid' => '59ac3f15-e82c-4d67-8113-77c8a27cbeea']
        ));

        $this->assertTrue($this->shouldApplyTrabberAttribution(
            $controller,
            'trabber_offer_3c11aec8-adca-470b-bde4-a0f2cb4c4933',
            ['quoteid' => '3c11aec8-adca-470b-bde4-a0f2cb4c4933']
        ));
    }

    public function test_it_uses_display_name_for_checkout_when_brand_and_model_are_empty(): void
    {
        $controller = $this->makeController();

        $result = $this->resolveVehicleCheckoutName($controller, [
            'brand' => '',
            'model' => '',
            'display_name' => 'CGMR',
            'provider_vehicle_id' => 'CGMR',
        ]);

        $this->assertSame('CGMR', $result);
    }

    private function makeController(): StripeCheckoutController
    {
        return new StripeCheckoutController(Mockery::mock(StripeBookingService::class));
    }

    private function resolveCheckoutLocationDetails(StripeCheckoutController $controller, array $validated): array
    {
        $method = new ReflectionMethod($controller, 'resolveCheckoutLocationDetails');
        $method->setAccessible(true);

        /** @var array{0: mixed, 1: mixed} $result */
        $result = $method->invoke($controller, $validated);

        return $result;
    }

    private function computeServerTotals(
        StripeCheckoutController $controller,
        array $validated,
        string $bookingCurrency = 'EUR',
        string $providerCurrency = 'EUR'
    ): array
    {
        $method = new ReflectionMethod($controller, 'computeServerTotals');
        $method->setAccessible(true);

        /** @var array<string, mixed> $result */
        $result = $method->invoke($controller, $validated, $bookingCurrency, $providerCurrency, 15.0);

        return $result;
    }

    private function shouldApplyTrabberAttribution(
        StripeCheckoutController $controller,
        ?string $searchSessionId,
        array $validated
    ): bool {
        $method = new ReflectionMethod($controller, 'shouldApplyTrabberAttribution');
        $method->setAccessible(true);

        return $method->invoke($controller, $searchSessionId, $validated);
    }

    private function resolveVehicleCheckoutName(StripeCheckoutController $controller, array $vehicle): string
    {
        $method = new ReflectionMethod($controller, 'resolveVehicleCheckoutName');
        $method->setAccessible(true);

        return $method->invoke($controller, $vehicle);
    }
}
