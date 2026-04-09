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
        $controller = app(CarHireRedirectController::class);

        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 8, 10, 0, 0, 'UTC'));

        $quote = [
            'quote_id' => 'quote-123',
            'expires_at' => CarbonImmutable::now('UTC')->addMinutes(30)->toIso8601String(),
            'vehicle' => [
                'display_name' => 'Toyota Yaris',
            ],
        ];

        $store->put($quote);

        $response = $controller(Request::create('/skyscanner/redirect', 'GET', [
            'quote_id' => 'quote-123',
        ]));

        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('quote-123', $payload['quote']['quote_id']);
        $this->assertSame('Toyota Yaris', $payload['quote']['vehicle']['display_name']);
    }

    public function test_it_returns_gone_when_the_quote_is_expired(): void
    {
        $store = app(CarHireQuoteStoreService::class);
        $controller = app(CarHireRedirectController::class);

        $quote = [
            'quote_id' => 'quote-123',
            'expires_at' => CarbonImmutable::create(2026, 4, 8, 10, 30, 0, 'UTC')->toIso8601String(),
            'vehicle' => [
                'display_name' => 'Toyota Yaris',
            ],
        ];

        $store->put($quote);
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 8, 10, 31, 0, 'UTC'));

        $response = $controller(Request::create('/skyscanner/redirect', 'GET', [
            'quote_id' => 'quote-123',
        ]));

        $payload = $response->getData(true);

        $this->assertSame(410, $response->getStatusCode());
        $this->assertSame('quote_expired', $payload['error']);
    }

    public function test_it_returns_not_found_when_the_quote_does_not_exist(): void
    {
        $controller = app(CarHireRedirectController::class);

        $response = $controller(Request::create('/skyscanner/redirect', 'GET', [
            'quote_id' => 'missing-quote',
        ]));

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
        $controller = app(CarHireRedirectController::class);

        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 8, 10, 0, 0, 'UTC'));
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

        $response = $controller(Request::create('/skyscanner/redirect', 'GET', $params));

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
        $controller = app(CarHireRedirectController::class);

        config([
            'skyscanner.signing_secret' => 'top-secret',
        ]);

        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 8, 10, 0, 0, 'UTC'));

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

        $response = $controller(Request::create('/skyscanner/redirect', 'GET', $params));
        $payload = $response->getData(true);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('invalid_signature', $payload['error']);
        $this->assertNull($tracking->getRedirectCorrelation('rid-123'));
    }
}
