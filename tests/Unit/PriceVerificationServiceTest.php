<?php

namespace Tests\Unit;

use App\Services\OfferService;
use App\Services\PriceVerificationService;
use Carbon\Carbon;
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
                    'max_quantity' => 2,
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

    public function test_it_rejects_extra_quantity_above_supplier_limit(): void
    {
        $service = app(PriceVerificationService::class);

        $result = $service->verifyAndResolveExtras([
            [
                'id' => 'child-seat',
                'qty' => 3,
                'total_for_booking' => 10.00,
            ],
        ], [
            'extras' => [
                [
                    'id' => 'child-seat',
                    'total_for_booking' => 10.00,
                    'max_quantity' => 2,
                ],
            ],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertSame('Price verification failed: Extra quantity exceeds supplier limit.', $result['error']);
    }

    public function test_it_resolves_recordgo_complements_from_selected_product_context(): void
    {
        $service = app(PriceVerificationService::class);

        $result = $service->verifyAndResolveExtras([
            [
                'id' => 'ext_recordgo_44',
                'qty' => 1,
                'total_for_booking' => 18.50,
            ],
        ], [
            'extras' => [
                ['id' => 'ext_recordgo_11', 'total_for_booking' => 7.00],
            ],
            'vehicle_context' => [
                'recordgo_products' => [
                    [
                        'type' => 'RG_BASE',
                        'complements_associated' => [
                            ['complementId' => 24, 'complementName' => 'Base Cover', 'priceTaxIncComplement' => 9.00],
                        ],
                    ],
                    [
                        'type' => 'RG_PRE',
                        'complements_associated' => [
                            [
                                'complementId' => 44,
                                'complementName' => 'Full Cover',
                                'complementCategory' => 'COVERAGE',
                                'priceTaxIncComplement' => 18.50,
                                'priceTaxIncDay' => 9.25,
                                'maxUnits' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ], 'RG_PRE');

        $this->assertTrue($result['valid']);
        $this->assertSame('ext_recordgo_44', $result['extras'][0]['id']);
        $this->assertSame(18.50, $result['extras'][0]['total_for_booking']);
        $this->assertSame(1, $result['extras'][0]['qty']);
        $this->assertArrayNotHasKey('purpose', $result['extras'][0]);
    }

    public function test_it_rejects_recordgo_complements_outside_selected_product_context(): void
    {
        $service = app(PriceVerificationService::class);

        $result = $service->verifyAndResolveExtras([
            [
                'id' => 'ext_recordgo_44',
                'qty' => 1,
                'total_for_booking' => 18.50,
            ],
        ], [
            'vehicle_context' => [
                'recordgo_products' => [
                    [
                        'type' => 'RG_BASE',
                        'complements_associated' => [
                            ['complementId' => 24, 'complementName' => 'Base Cover', 'priceTaxIncComplement' => 9.00],
                        ],
                    ],
                    [
                        'type' => 'RG_PRE',
                        'complements_associated' => [
                            ['complementId' => 44, 'complementName' => 'Full Cover', 'priceTaxIncComplement' => 18.50],
                        ],
                    ],
                ],
            ],
        ], 'RG_BASE');

        $this->assertFalse($result['valid']);
        $this->assertSame('Price verification failed: Selected extra is no longer available.', $result['error']);
    }

    public function test_it_stores_canonical_nested_pricing_for_internal_search_vehicles(): void
    {
        Cache::flush();
        $offerService = $this->createMock(OfferService::class);
        $offerService->method('getOfferFingerprint')->willReturn('no-active-offers');
        $this->app->instance(OfferService::class, $offerService);

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

    public function test_it_stores_gateway_vehicle_context_for_external_search_vehicles(): void
    {
        Cache::flush();
        $offerService = $this->createMock(OfferService::class);
        $offerService->method('getOfferFingerprint')->willReturn('no-active-offers');
        $this->app->instance(OfferService::class, $offerService);

        $service = app(PriceVerificationService::class);
        $priceMap = $service->storeOriginalPrices('search_gateway_context', [[
            'id' => 'gw_recordgo_vehicle_1',
            'gateway_vehicle_id' => 'gw_recordgo_vehicle_1',
            'gateway_search_id' => 'search_recordgo_1',
            'source' => 'recordgo',
            'provider_vehicle_id' => 'EDMR',
            'brand' => 'Fiat',
            'model' => '500',
            'category' => 'mini',
            'sipp_code' => 'EDMR',
            'transmission' => 'Manual',
            'fuel_type' => 'Petrol',
            'pricing' => [
                'currency' => 'EUR',
                'daily_rate' => 50.0,
                'total_price' => 100.0,
            ],
            'supplier_data' => [
                'product_id' => 123,
                'rate_id' => 'RATE1',
            ],
        ]]);

        $verified = $service->verifyPrices('search_gateway_context', [
            'id' => 'gw_recordgo_vehicle_1',
            'price_hash' => $priceMap['gw_recordgo_vehicle_1']['price_hash'],
            'pricing' => [
                'total_price' => 100.0,
            ],
        ]);

        $context = $verified['original_prices']['gateway_vehicle_context'];

        $this->assertTrue($verified['valid']);
        $this->assertSame('gw_recordgo_vehicle_1', $context['id']);
        $this->assertSame('search_recordgo_1', $context['search_id']);
        $this->assertSame('recordgo', $context['supplier_id']);
        $this->assertSame('EDMR', $context['supplier_vehicle_id']);
        $this->assertSame('Fiat 500', $context['name']);
        $this->assertSame(123, data_get($context, 'supplier_data.product_id'));
        $this->assertSame('RATE1', data_get($context, 'supplier_data.rate_id'));
        $this->assertSame('EUR', data_get($context, 'pricing.currency'));
        $this->assertSame(100.0, data_get($context, 'pricing.total_price'));
        $this->assertNotEmpty($context['context_valid_until'] ?? null);
        $this->assertTrue(Carbon::parse($context['context_valid_until'])->lessThanOrEqualTo(now()->addMinutes(16)));
    }

    public function test_price_verification_rejects_expired_quote_context(): void
    {
        Cache::flush();
        $now = Carbon::parse('2026-06-25 10:00:00');
        Carbon::setTestNow($now);

        try {
            $offerService = $this->createMock(OfferService::class);
            $offerService->method('getOfferFingerprint')->willReturn('no-active-offers');
            $this->app->instance(OfferService::class, $offerService);

            $service = app(PriceVerificationService::class);
            $priceMap = $service->storeOriginalPrices('search_expiring_context', [[
                'id' => 'gw_expiring_vehicle_1',
                'gateway_vehicle_id' => 'gw_expiring_vehicle_1',
                'source' => 'surprice',
                'provider_vehicle_id' => 'ECMR',
                'pricing' => [
                    'currency' => 'EUR',
                    'price_per_day' => 20.0,
                    'total_price' => 60.0,
                ],
            ]]);
            $cacheKey = 'price_verify_'.sha1('search_expiring_context_gw_expiring_vehicle_1');
            $cachedPriceContext = Cache::get($cacheKey);

            Carbon::setTestNow($now->copy()->addMinutes(16));
            Cache::put($cacheKey, $cachedPriceContext, now()->addHour());

            $verified = $service->verifyPrices('search_expiring_context', [
                'id' => 'gw_expiring_vehicle_1',
                'price_hash' => $priceMap['gw_expiring_vehicle_1']['price_hash'],
                'pricing' => [
                    'total_price' => 60.0,
                ],
            ]);

            $this->assertFalse($verified['valid']);
            $this->assertSame('Price verification failed: Original pricing data expired. Please refresh search results.', $verified['error']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_price_hash_changes_when_offer_fingerprint_changes(): void
    {
        Cache::flush();

        $vehicle = [[
            'id' => 'offer-sensitive-1',
            'source' => 'internal',
            'pricing' => [
                'currency' => 'EUR',
                'price_per_day' => 45.0,
                'total_price' => 135.0,
            ],
            'products' => [
                ['type' => 'BAS', 'total' => 135.0],
            ],
        ]];

        $offerServiceA = $this->createMock(OfferService::class);
        $offerServiceA->method('getOfferFingerprint')->willReturn('discount-10');
        $this->app->instance(OfferService::class, $offerServiceA);

        $service = app(PriceVerificationService::class);
        $first = $service->storeOriginalPrices('search_offer_a', $vehicle);

        $offerServiceB = $this->createMock(OfferService::class);
        $offerServiceB->method('getOfferFingerprint')->willReturn('discount-15');
        $this->app->instance(OfferService::class, $offerServiceB);

        $second = app(PriceVerificationService::class)->storeOriginalPrices('search_offer_b', $vehicle);

        $this->assertNotSame(
            $first['offer-sensitive-1']['price_hash'],
            $second['offer-sensitive-1']['price_hash']
        );
    }
}
