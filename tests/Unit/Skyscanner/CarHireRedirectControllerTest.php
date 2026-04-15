<?php

namespace Tests\Unit\Skyscanner;

use App\Http\Controllers\Skyscanner\CarHireRedirectController;
use App\Services\Skyscanner\CarHireAuditLogService;
use App\Services\Skyscanner\CarHireQuoteStoreService;
use App\Services\Skyscanner\CarHireSecurityService;
use App\Services\Skyscanner\CarHireTrackingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class CarHireRedirectControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        CarbonImmutable::setTestNow(null);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow(null);

        parent::tearDown();
    }

    public function test_it_returns_the_quote_when_it_exists_and_is_still_valid(): void
    {
        $store = app(CarHireQuoteStoreService::class);

        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 11, 10, 0, 0, 'UTC'));

        $quote = [
            'quote_id' => 'quote-123',
            'expires_at' => CarbonImmutable::now('UTC')->addMinutes(30)->toIso8601String(),
            'vehicle' => [
                'display_name' => 'Toyota Yaris',
            ],
        ];

        $store->put($quote);

        $response = $this->getJson('/api/skyscanner/redirect?quote_id=quote-123');

        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('quote-123', $payload['quote']['quote_id']);
        $this->assertSame('Toyota Yaris', $payload['quote']['vehicle']['display_name']);
    }

    public function test_it_hides_internal_quote_fields_from_redirect_json_response(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 11, 10, 0, 0, 'UTC'));

        $quote = [
            'quote_id' => 'quote-123',
            'case_id' => 'PSM-46100',
            'created_at' => CarbonImmutable::now('UTC')->toIso8601String(),
            'expires_at' => CarbonImmutable::now('UTC')->addMinutes(30)->toIso8601String(),
            'vehicle' => [
                'provider_vehicle_id' => 'gm-vehicle-1',
                'source' => 'greenmotion',
                'provider_code' => 'greenmotion',
                'provider_product_id' => 'gm-product-1',
                'provider_rate_id' => 'gm-rate-1',
                'display_name' => 'Toyota Yaris',
                'booking_context' => [
                    'provider_payload' => [
                        'source' => 'greenmotion',
                    ],
                ],
            ],
            'supplier' => [
                'code' => 'greenmotion',
                'name' => 'Green Motion',
            ],
            'specs' => [
                'sipp_code' => 'ECMR',
                'sipp_source' => 'explicit',
            ],
            'pickup_location_details' => [
                'provider_location_id' => 'gm-dxb-1',
                'provider_location_source' => 'explicit',
                'name' => 'Dubai Airport',
            ],
            'dropoff_location_details' => [
                'provider_location_id' => 'gm-dxb-1',
                'provider_location_source' => 'explicit',
                'name' => 'Dubai Airport',
            ],
            'products' => [
                ['id' => 'product-1'],
            ],
            'extras_preview' => [
                ['code' => 'gps'],
            ],
            'data_quality_flags' => ['missing_postal_code'],
            'search' => [
                'pickup_location_id' => '3272373056',
            ],
        ];

        $store = Mockery::mock(CarHireQuoteStoreService::class);
        $store->shouldReceive('get')
            ->once()
            ->with('quote-123')
            ->andReturn($quote);
        $this->app->instance(CarHireQuoteStoreService::class, $store);

        $controller = app(CarHireRedirectController::class);

        $response = $controller(Request::create('/api/skyscanner/redirect', 'GET', [
            'quote_id' => 'quote-123',
            'format' => 'json',
        ]));

        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('quote-123', $payload['quote']['quote_id']);
        $this->assertSame('Toyota Yaris', $payload['quote']['vehicle']['display_name']);
        $this->assertArrayNotHasKey('source', $payload['quote']['vehicle']);
        $this->assertArrayNotHasKey('provider_code', $payload['quote']['vehicle']);
        $this->assertArrayNotHasKey('provider_product_id', $payload['quote']['vehicle']);
        $this->assertArrayNotHasKey('provider_rate_id', $payload['quote']['vehicle']);
        $this->assertArrayNotHasKey('booking_context', $payload['quote']['vehicle']);
        $this->assertArrayNotHasKey('supplier', $payload['quote']);
        $this->assertArrayNotHasKey('sipp_source', $payload['quote']['specs']);
        $this->assertArrayNotHasKey('provider_location_id', $payload['quote']['pickup_location_details']);
        $this->assertArrayNotHasKey('provider_location_source', $payload['quote']['pickup_location_details']);
        $this->assertArrayNotHasKey('provider_location_id', $payload['quote']['dropoff_location_details']);
        $this->assertArrayNotHasKey('provider_location_source', $payload['quote']['dropoff_location_details']);
        $this->assertArrayNotHasKey('products', $payload['quote']);
        $this->assertArrayNotHasKey('extras_preview', $payload['quote']);
        $this->assertArrayNotHasKey('data_quality_flags', $payload['quote']);
        $this->assertArrayNotHasKey('search', $payload['quote']);
    }

    public function test_it_returns_gone_when_the_quote_is_expired(): void
    {
        $store = app(CarHireQuoteStoreService::class);

        $quote = [
            'quote_id' => 'quote-123',
            'expires_at' => CarbonImmutable::create(2026, 4, 11, 10, 30, 0, 'UTC')->toIso8601String(),
            'vehicle' => [
                'display_name' => 'Toyota Yaris',
            ],
        ];

        $store->put($quote);
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 11, 10, 31, 0, 'UTC'));

        $response = $this->getJson('/api/skyscanner/redirect?quote_id=quote-123');

        $payload = $response->getData(true);

        $this->assertSame(410, $response->getStatusCode());
        $this->assertSame('quote_expired', $payload['error']);
    }

    public function test_it_redirects_browser_requests_to_the_offer_page_when_the_quote_is_expired(): void
    {
        $store = app(CarHireQuoteStoreService::class);
        $controller = app(CarHireRedirectController::class);

        $quote = [
            'quote_id' => 'quote-expired',
            'case_id' => 'PSM-46100',
            'expires_at' => CarbonImmutable::create(2026, 4, 11, 10, 30, 0, 'UTC')->toIso8601String(),
            'vehicle' => [
                'display_name' => 'Toyota Yaris',
            ],
            'deeplink' => [
                'landing_page_url' => 'https://vrooem.test/en/offers/quote-expired',
            ],
        ];

        $store->put($quote);
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 11, 10, 31, 0, 'UTC'));

        $response = $controller(Request::create('/api/skyscanner/redirect', 'GET', [
            'quote_id' => 'quote-expired',
            'skyscanner_redirectid' => 'rid-expired',
        ]));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://vrooem.test/en/offers/quote-expired', $response->headers->get('Location'));
    }

    public function test_it_returns_not_found_when_the_quote_does_not_exist(): void
    {
        $response = $this->getJson('/api/skyscanner/redirect?quote_id=missing-quote');

        $payload = $response->getData(true);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('quote_not_found', $payload['error']);
    }

    public function test_it_stores_redirect_correlation_when_skyscanner_redirect_id_is_present(): void
    {
        $store = app(CarHireQuoteStoreService::class);
        $tracking = app(CarHireTrackingService::class);
        $auditLog = app(CarHireAuditLogService::class);
        $security = app(CarHireSecurityService::class);

        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 11, 10, 0, 0, 'UTC'));
        config([
            'skyscanner.signing_secret' => 'top-secret',
        ]);

        $quote = [
            'quote_id' => 'quote-123',
            'case_id' => 'PSM-46100',
            'expires_at' => CarbonImmutable::now('UTC')->addMinutes(30)->toIso8601String(),
            'vehicle' => [
                'provider_vehicle_id' => '327',
                'display_name' => 'Toyota Yaris',
            ],
        ];

        $store->put($quote);
        $params = $security->buildSignedRedirectParams('quote-123', 'rid-123');

        $response = $this->getJson('/api/skyscanner/redirect?' . http_build_query($params));

        $payload = $response->getData(true);
        $correlation = $tracking->getRedirectCorrelation('rid-123');
        $logs = $auditLog->get('quote', 'quote-123');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('rid-123', $payload['tracking']['redirect_id']);
        $this->assertSame([
            'redirect_id' => 'rid-123',
            'quote_id' => 'quote-123',
            'case_id' => 'PSM-46100',
            'provider_vehicle_id' => '327',
        ], $correlation);
        $this->assertCount(1, $logs);
        $this->assertSame('quote_redirected', $logs[0]['event']);
        $this->assertSame('rid-123', $logs[0]['payload']['redirect_id']);
    }

    public function test_it_rejects_tampered_signed_redirect_params(): void
    {
        $store = app(CarHireQuoteStoreService::class);
        $tracking = app(CarHireTrackingService::class);
        $security = app(CarHireSecurityService::class);

        config([
            'skyscanner.signing_secret' => 'top-secret',
        ]);

        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 11, 10, 0, 0, 'UTC'));

        $quote = [
            'quote_id' => 'quote-123',
            'case_id' => 'PSM-46100',
            'expires_at' => CarbonImmutable::now('UTC')->addMinutes(30)->toIso8601String(),
            'vehicle' => [
                'provider_vehicle_id' => '327',
                'display_name' => 'Toyota Yaris',
            ],
        ];

        $store->put($quote);
        $params = $security->buildSignedRedirectParams('quote-123', 'rid-123');
        $params['quote_id'] = 'quote-999';

        $response = $this->getJson('/api/skyscanner/redirect?' . http_build_query($params));
        $payload = $response->getData(true);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('invalid_signature', $payload['error']);
        $this->assertNull($tracking->getRedirectCorrelation('rid-123'));
    }

    public function test_it_redirects_browser_requests_to_the_landing_page_with_tracking_context(): void
    {
        $store = app(CarHireQuoteStoreService::class);
        $tracking = app(CarHireTrackingService::class);
        $controller = app(CarHireRedirectController::class);

        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 11, 10, 0, 0, 'UTC'));

        $quote = [
            'quote_id' => 'quote-123',
            'case_id' => 'PSM-46100',
            'expires_at' => CarbonImmutable::now('UTC')->addMinutes(30)->toIso8601String(),
            'vehicle' => [
                'provider_vehicle_id' => '327',
                'display_name' => 'Toyota Yaris',
            ],
            'deeplink' => [
                'landing_page_url' => 'https://vrooem.test/en/offers/quote-123',
            ],
        ];

        $store->put($quote);

        $response = $controller(Request::create('/api/skyscanner/redirect', 'GET', [
            'quote_id' => 'quote-123',
            'skyscanner_redirectid' => 'rid-123',
        ]));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://vrooem.test/en/offers/quote-123', $response->headers->get('Location'));
        $this->assertSame([
            'redirect_id' => 'rid-123',
            'quote_id' => 'quote-123',
            'case_id' => 'PSM-46100',
            'provider_vehicle_id' => '327',
        ], $tracking->getRedirectCorrelation('rid-123'));
    }
}
