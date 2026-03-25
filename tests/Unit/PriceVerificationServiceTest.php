<?php

namespace Tests\Unit;

use App\Services\PromoService;
use App\Services\PriceVerificationService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PriceVerificationServiceTest extends TestCase
{
    public function test_it_rejects_unknown_selected_extras(): void
    {
        $service = app(PriceVerificationService::class);

        $result = $service->verifyAndResolveExtras([
            [
                'id' => 'missing-extra',
                'qty' => 1,
                'total_for_booking' => 5.00,
            ],
        ], [
            'extras' => [
                [
                    'id' => 'child-seat',
                    'total_for_booking' => 10.00,
                ],
            ],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertSame('Price verification failed: Selected extra is no longer available.', $result['error']);
    }

    public function test_it_rejects_tampered_extra_prices(): void
    {
        $service = app(PriceVerificationService::class);

        $result = $service->verifyAndResolveExtras([
            [
                'id' => 'child-seat',
                'qty' => 1,
                'total_for_booking' => 1.00,
            ],
        ], [
            'extras' => [
                [
                    'id' => 'child-seat',
                    'total_for_booking' => 10.00,
                ],
            ],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertSame('Price verification failed: Extra price mismatch detected.', $result['error']);
    }

    public function test_it_returns_server_trusted_extra_payload_for_valid_selected_extras(): void
    {
        $service = app(PriceVerificationService::class);

        $result = $service->verifyAndResolveExtras([
            [
                'id' => 'child-seat',
                'qty' => 2,
                'total_for_booking' => 10.00,
            ],
        ], [
            'extras' => [
                [
                    'id' => 'child-seat',
                    'total_for_booking' => 10.00,
                    'daily_rate' => 2.50,
                    'name' => 'Child seat',
                ],
            ],
        ]);

        $this->assertTrue($result['valid']);
        $this->assertCount(1, $result['extras']);
        $this->assertSame('child-seat', $result['extras'][0]['id']);
        $this->assertSame(2, $result['extras'][0]['qty']);
        $this->assertSame(10.00, $result['extras'][0]['total_for_booking']);
        $this->assertSame('Child seat', $result['extras'][0]['name']);
    }

    public function test_it_stores_canonical_nested_pricing_for_internal_search_vehicles(): void
    {
        Cache::flush();
        $promoService = $this->createMock(PromoService::class);
        $promoService->method('getActivePromo')->willReturn(null);
        $this->app->instance(PromoService::class, $promoService);

        $service = app(PriceVerificationService::class);
        $priceMap = $service->storeOriginalPrices('search_internal_contract', [[
            'id' => '327',
            'source' => 'internal',
            'pricing' => [
                'currency' => 'EUR',
                'price_per_day' => 30.0,
                'total_price' => 90.0,
            ],
            'products' => [
                ['type' => 'BAS', 'total' => 90.0],
            ],
            'extras_preview' => [
                ['id' => 'internal_addon_11', 'total_for_booking' => 12.0],
            ],
        ]]);

        $this->assertArrayHasKey('327', $priceMap);

        $verified = $service->verifyPrices('search_internal_contract', [
            'id' => '327',
            'price_hash' => $priceMap['327']['price_hash'],
            'pricing' => [
                'total_price' => 90.0,
            ],
            'products' => [
                ['type' => 'BAS', 'total' => 90.0],
            ],
        ]);

        $this->assertTrue($verified['valid']);
        $this->assertSame(90.0, $verified['original_prices']['original_total']);
        $this->assertSame(30.0, $verified['original_prices']['original_daily_rate']);
        $this->assertSame('EUR', $verified['original_prices']['currency']);
        $this->assertSame('internal_addon_11', $verified['original_prices']['extras'][0]['id']);
    }
}
