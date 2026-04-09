<?php

namespace Tests\Unit\Skyscanner;

use App\Services\Skyscanner\CarHireTrackingService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CarHireTrackingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_it_builds_redirect_tracking_context(): void
    {
        config([
            'skyscanner.case_id' => 'PSM-46100',
            'skyscanner.dv_required_before_go_live' => true,
            'skyscanner.keyword_tracking_enabled' => false,
        ]);

        $service = app(CarHireTrackingService::class);

        $context = $service->buildRedirectTrackingContext([
            'redirect_id' => 'rid-123',
            'quote_id' => 'quote-123',
        ]);

        $this->assertSame('PSM-46100', $context['case_id']);
        $this->assertTrue($context['dv_required_before_go_live']);
        $this->assertFalse($context['keyword_tracking_enabled']);
        $this->assertSame('rid-123', $context['payload']['redirect_id']);
        $this->assertSame('quote-123', $context['payload']['quote_id']);
    }

    public function test_it_stores_and_retrieves_redirect_correlation(): void
    {
        $service = app(CarHireTrackingService::class);

        $service->rememberRedirectCorrelation('rid-123', 'quote-123', [
            'case_id' => 'PSM-46100',
            'provider_vehicle_id' => '327',
        ]);

        $correlation = $service->getRedirectCorrelation('rid-123');

        $this->assertSame([
            'redirect_id' => 'rid-123',
            'quote_id' => 'quote-123',
            'case_id' => 'PSM-46100',
            'provider_vehicle_id' => '327',
        ], $correlation);
    }

    public function test_it_returns_null_for_unknown_redirect_correlation(): void
    {
        $service = app(CarHireTrackingService::class);

        $this->assertNull($service->getRedirectCorrelation('missing-rid'));
    }
}
