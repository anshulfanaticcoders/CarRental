<?php

namespace Tests\Unit;

use App\Services\ProviderQuoteRevalidationService;
use ReflectionMethod;
use Tests\TestCase;

class ProviderQuoteRevalidationServiceTest extends TestCase
{
    public function test_surprice_fdw_package_validates_from_supplier_context(): void
    {
        $result = $this->validateSelectedPackagePrice('FDW', $this->surpricePrices(), $this->surpricePrices());

        $this->assertTrue($result['valid']);
    }

    public function test_surprice_fdw_package_rejects_missing_fresh_rate_context(): void
    {
        $freshPrices = $this->surpricePrices();
        unset($freshPrices['vehicle_context']['supplier_data']['fdw_vendor_rate_id']);

        $result = $this->validateSelectedPackagePrice('FDW', $this->surpricePrices(), $freshPrices);

        $this->assertFalse($result['valid']);
        $this->assertSame('surprice_fdw_context_missing', $result['reason']);
    }

    public function test_surprice_fdw_package_rejects_changed_fresh_total(): void
    {
        $freshPrices = $this->surpricePrices();
        $freshPrices['vehicle_context']['supplier_data']['fdw_total_amount'] = 220.00;

        $result = $this->validateSelectedPackagePrice('FDW', $this->surpricePrices(), $freshPrices);

        $this->assertFalse($result['valid']);
        $this->assertSame('package_price_changed', $result['reason']);
    }

    private function validateSelectedPackagePrice(?string $package, array $oldPrices, array $freshPrices): array
    {
        $service = app(ProviderQuoteRevalidationService::class);
        $method = new ReflectionMethod($service, 'validateSelectedPackagePrice');
        $method->setAccessible(true);

        return $method->invoke($service, $package, $oldPrices, $freshPrices);
    }

    private function surpricePrices(): array
    {
        return [
            'provider' => 'surprice',
            'currency' => 'EUR',
            'original_total' => 171.42,
            'products' => [
                ['type' => 'BAS', 'total' => 171.42],
            ],
            'vehicle_context' => [
                'supplier_data' => [
                    'fdw_total_amount' => 213.93,
                    'fdw_vendor_rate_id' => 'surprice-fdw-rate-1',
                    'fdw_rate_code' => 'Vrooem FDW',
                ],
            ],
        ];
    }
}
