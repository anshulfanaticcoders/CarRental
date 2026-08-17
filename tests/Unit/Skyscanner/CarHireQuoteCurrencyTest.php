<?php

namespace Tests\Unit\Skyscanner;

use App\Services\CurrencyConversionService;
use App\Services\Skyscanner\CarHireSearchService;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Skyscanner quotes used to STAMP the requested currency onto raw EUR
 * amounts — no conversion. "45 USD" that was really 45 EUR understates the
 * price ~8%, wins the ranking, and the checkout then charges the real
 * amount: a price-accuracy breach. Now: real conversion, and when no rate
 * exists the quote keeps its true source currency instead of lying.
 */
class CarHireQuoteCurrencyTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function applyPricing(array $vehicle, array $search): array
    {
        $service = app(CarHireSearchService::class);
        $method = new ReflectionMethod($service, 'applyCustomerPricing');

        return $method->invoke($service, $vehicle, $search);
    }

    private function vehicle(string $source = 'internal'): array
    {
        return [
            'source' => $source,
            'pricing' => ['total_price' => 100.0, 'currency' => 'EUR'],
        ];
    }

    private function search(string $currency): array
    {
        return [
            'currency' => $currency,
            'pickup_date' => now()->addDays(10)->toDateString(),
            'dropoff_date' => now()->addDays(12)->toDateString(),
        ];
    }

    public function test_requested_currency_is_reached_by_real_conversion(): void
    {
        $mock = Mockery::mock(CurrencyConversionService::class);
        $mock->shouldReceive('convert')->with(1.0, 'EUR', 'USD')
            ->andReturn(['success' => true, 'converted_amount' => 1.1]);
        $this->app->instance(CurrencyConversionService::class, $mock);

        $result = $this->applyPricing($this->vehicle(), $this->search('USD'));

        $this->assertSame('USD', $result['pricing']['currency']);
        $this->assertSame(110.0, $result['pricing']['total_price']);
    }

    public function test_a_missing_rate_keeps_the_true_currency_instead_of_lying(): void
    {
        $mock = Mockery::mock(CurrencyConversionService::class);
        $mock->shouldReceive('convert')->andReturn(['success' => false, 'converted_amount' => 100.0]);
        $this->app->instance(CurrencyConversionService::class, $mock);

        $result = $this->applyPricing($this->vehicle(), $this->search('HUF'));

        $this->assertSame('EUR', $result['pricing']['currency'], 'Label must match the amounts.');
        $this->assertSame(100.0, $result['pricing']['total_price']);
    }

    public function test_internal_fleet_carries_no_markup_and_external_does(): void
    {
        config(['services.pricing.provider_markup_percent' => 15]);

        $internal = $this->applyPricing($this->vehicle('internal'), $this->search('EUR'));
        $external = $this->applyPricing($this->vehicle('recordgo'), $this->search('EUR'));

        $this->assertSame(100.0, $internal['pricing']['total_price'], 'Internal sells at net — same as checkout.');
        $this->assertSame(115.0, $external['pricing']['total_price']);
    }
}
