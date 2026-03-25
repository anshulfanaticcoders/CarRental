<?php

namespace Tests\Unit;

use App\Http\Controllers\StripeCheckoutController;
use App\Services\StripeBookingService;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class StripeCheckoutControllerInternalCanonicalContextTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_resolves_deposit_and_excess_from_canonical_internal_vehicle_pricing(): void
    {
        $controller = new StripeCheckoutController(Mockery::mock(StripeBookingService::class));

        $vehicle = [
            'source' => 'internal',
            'pricing' => [
                'currency' => 'EUR',
                'deposit_amount' => 150.0,
                'deposit_currency' => 'EUR',
                'excess_amount' => 650.0,
                'excess_theft_amount' => 700.0,
            ],
            'products' => [
                [
                    'type' => 'BAS',
                    'total' => 90.0,
                    'deposit' => 150.0,
                    'deposit_currency' => 'EUR',
                    'excess' => 650.0,
                    'excess_theft_amount' => 700.0,
                ],
            ],
            'booking_context' => [
                'provider_payload' => [
                    'benefits' => [
                        'deposit_amount' => 150.0,
                        'deposit_currency' => 'EUR',
                        'excess_amount' => 650.0,
                        'excess_theft_amount' => 700.0,
                    ],
                ],
            ],
        ];

        $resolved = $this->resolveSelectedProductContext($controller, $vehicle, 'BAS');

        $this->assertSame(150.0, $resolved['deposit_amount']);
        $this->assertSame('EUR', $resolved['deposit_currency']);
        $this->assertSame(650.0, $resolved['excess_amount']);
        $this->assertSame(700.0, $resolved['excess_theft_amount']);
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
