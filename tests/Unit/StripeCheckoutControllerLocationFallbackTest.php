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
        $this->assertSame($pickupOffice['address'], $pickup['address']);
        $this->assertSame(40.656685, $pickup['latitude']);
        $this->assertSame(-4.681208, $pickup['longitude']);
        $this->assertSame($pickup['latitude'], $dropoff['latitude']);
        $this->assertSame($pickup['longitude'], $dropoff['longitude']);
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
}
