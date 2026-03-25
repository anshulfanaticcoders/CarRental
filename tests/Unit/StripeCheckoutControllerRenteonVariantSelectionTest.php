<?php

namespace Tests\Unit;

use App\Http\Controllers\StripeCheckoutController;
use App\Services\StripeBookingService;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class StripeCheckoutControllerRenteonVariantSelectionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_resolves_the_selected_renteon_rate_variant_from_grouped_products(): void
    {
        $controller = new StripeCheckoutController(Mockery::mock(StripeBookingService::class));

        $vehicle = [
            'source' => 'renteon',
            'gateway_vehicle_id' => 'gw_root_lowest',
            'connector_id' => 51,
            'provider_pickup_office_id' => 490,
            'provider_dropoff_office_id' => 490,
            'pricelist_id' => 797,
            'price_date' => '2026-03-23T00:00:00',
            'prepaid' => false,
            'benefits' => [
                'deposit_amount' => 1100.0,
                'deposit_currency' => 'EUR',
                'excess_amount' => 1100.0,
                'excess_theft_amount' => 1100.0,
            ],
            'products' => [
                [
                    'type' => 'REN_797',
                    'gateway_vehicle_id' => 'gw_root_lowest',
                    'connector_id' => 51,
                    'provider_pickup_office_id' => 490,
                    'provider_dropoff_office_id' => 490,
                    'pricelist_id' => 797,
                    'price_date' => '2026-03-23T00:00:00',
                    'prepaid' => false,
                    'deposit' => 1100.0,
                    'deposit_currency' => 'EUR',
                    'excess' => 1100.0,
                    'excess_theft_amount' => 1100.0,
                ],
                [
                    'type' => 'REN_729',
                    'gateway_vehicle_id' => 'gw_mid_variant',
                    'connector_id' => 51,
                    'provider_pickup_office_id' => 490,
                    'provider_dropoff_office_id' => 490,
                    'pricelist_id' => 729,
                    'price_date' => '2026-03-23T00:00:00',
                    'prepaid' => false,
                    'deposit' => 500.0,
                    'deposit_currency' => 'EUR',
                    'excess' => 500.0,
                    'excess_theft_amount' => 500.0,
                ],
            ],
        ];

        $resolved = $this->resolveSelectedProductContext($controller, $vehicle, 'REN_729');

        $this->assertSame('gw_mid_variant', $resolved['gateway_vehicle_id']);
        $this->assertSame(51, $resolved['connector_id']);
        $this->assertSame(490, $resolved['provider_pickup_office_id']);
        $this->assertSame(490, $resolved['provider_dropoff_office_id']);
        $this->assertSame(729, $resolved['pricelist_id']);
        $this->assertSame('2026-03-23T00:00:00', $resolved['price_date']);
        $this->assertFalse($resolved['prepaid']);
        $this->assertSame(500.0, $resolved['deposit_amount']);
        $this->assertSame('EUR', $resolved['deposit_currency']);
        $this->assertSame(500.0, $resolved['excess_amount']);
        $this->assertSame(500.0, $resolved['excess_theft_amount']);
    }

    private function resolveSelectedProductContext(StripeCheckoutController $controller, array $vehicle, ?string $package): array
    {
        $method = new ReflectionMethod($controller, 'resolveSelectedVehicleContext');
        $method->setAccessible(true);

        /** @var array<string, mixed> $result */
        $result = $method->invoke($controller, $vehicle, $package);

        return $result;
    }
}
