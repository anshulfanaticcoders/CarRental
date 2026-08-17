<?php

namespace Tests\Unit;

use App\Http\Controllers\StripeCheckoutController;
use App\Services\CurrencyConversionService;
use App\Services\StripeBookingService;
use App\Support\CurrencyRegistry;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

/**
 * A failed currency conversion must BLOCK checkout, never fall through 1:1.
 * Falling through charges the raw provider number in the customer's currency
 * (EUR 300 quote shown in HUF becomes a 300 HUF ≈ €0.75 charge for a real
 * €300 car). The protection-amount path always failed closed; these tests
 * pin the base-total and extras paths to the same behavior.
 */
class StripeCheckoutControllerCurrencyFailClosedTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_base_total_conversion_failure_blocks_checkout(): void
    {
        $this->mockConversion(fn () => ['success' => false, 'converted_amount' => 100.0, 'error' => 'Missing rate for USD or HUF']);

        $result = $this->computeServerTotals($this->recordgoQuote(), 'HUF', 'USD');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_extras_conversion_failure_blocks_checkout(): void
    {
        // Base converts fine; the extras call fails (e.g. circuit breaker opened
        // between the two calls). The checkout must still be blocked.
        $this->mockConversion(function (float $amount) {
            return $amount === 100.0
                ? ['success' => true, 'converted_amount' => 90.0]
                : ['success' => false, 'converted_amount' => $amount, 'error' => 'circuit open'];
        });

        $result = $this->computeServerTotals($this->recordgoQuote(extras: true), 'EUR', 'USD');

        $this->assertFalse($result['success']);
    }

    public function test_successful_conversion_uses_converted_amounts(): void
    {
        $this->mockConversion(fn (float $amount) => ['success' => true, 'converted_amount' => round($amount * 0.9, 2)]);

        $result = $this->computeServerTotals($this->recordgoQuote(), 'EUR', 'USD');

        $this->assertTrue($result['success']);
        $this->assertSame(90.0, $result['booking_total_net']);
    }

    public function test_display_currency_without_a_live_rate_falls_back_to_source_currency(): void
    {
        // HUF is a real ISO code the registry may know, but no rate exists for
        // it. Charging in it would need the (blocked) conversion, so the charge
        // stays in the quote's own currency instead of failing the checkout.
        $this->mockRegistrySelectable(['EUR', 'USD']);

        $this->assertSame('EUR', $this->resolveBookingCurrency('HUF', 'EUR'));
    }

    public function test_display_currency_with_a_live_rate_is_honoured(): void
    {
        $this->mockRegistrySelectable(['EUR', 'USD']);

        $this->assertSame('USD', $this->resolveBookingCurrency('USD', 'EUR'));
    }

    public function test_missing_or_garbage_display_currency_falls_back_to_source(): void
    {
        $this->mockRegistrySelectable(['EUR', 'USD']);

        $this->assertSame('GBP', $this->resolveBookingCurrency(null, 'GBP'));
        $this->assertSame('GBP', $this->resolveBookingCurrency('', 'GBP'));
        $this->assertSame('GBP', $this->resolveBookingCurrency('NOPE', 'GBP'));
    }

    private function recordgoQuote(bool $extras = false): array
    {
        return [
            'vehicle' => [
                'source' => 'recordgo',
                'products' => [
                    ['type' => 'RG_PRE', 'total' => 100.00, 'currency' => 'USD'],
                ],
            ],
            'package' => 'RG_PRE',
            'number_of_days' => 2,
            'detailed_extras' => $extras
                ? [['id' => 'ext_recordgo_44', 'qty' => 1, 'total_for_booking' => 18.50]]
                : [],
        ];
    }

    private function mockConversion(callable $handler): void
    {
        $mock = Mockery::mock(CurrencyConversionService::class);
        $mock->shouldReceive('convert')
            ->andReturnUsing(fn ($amount) => $handler((float) $amount));
        $this->app->instance(CurrencyConversionService::class, $mock);
    }

    private function mockRegistrySelectable(array $codes): void
    {
        $registry = Mockery::mock(CurrencyRegistry::class)->makePartial();
        $registry->shouldReceive('selectableCodes')->andReturn($codes);
        $this->app->instance(CurrencyRegistry::class, $registry);
    }

    private function computeServerTotals(array $validated, string $bookingCurrency, string $providerCurrency): array
    {
        $controller = new StripeCheckoutController(Mockery::mock(StripeBookingService::class));
        $method = new ReflectionMethod($controller, 'computeServerTotals');
        $method->setAccessible(true);

        return $method->invoke($controller, $validated, $bookingCurrency, $providerCurrency, 15.0);
    }

    private function resolveBookingCurrency($displayCurrency, $sourceCurrency): string
    {
        $controller = new StripeCheckoutController(Mockery::mock(StripeBookingService::class));
        $method = new ReflectionMethod($controller, 'resolveBookingCurrency');
        $method->setAccessible(true);

        return $method->invoke($controller, $displayCurrency, $sourceCurrency);
    }
}
