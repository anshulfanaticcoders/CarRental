<?php

namespace Tests\Unit\Skyscanner;

use App\Services\Skyscanner\CarHireSurveyReadinessService;
use Tests\TestCase;

class CarHireSurveyReadinessServiceTest extends TestCase
{
    public function test_it_builds_a_technical_survey_ready_summary(): void
    {
        config([
            'skyscanner.case_id' => 'PSM-46100',
            'skyscanner.inventory_scope' => 'internal',
            'skyscanner.dv_required_before_go_live' => true,
            'skyscanner.keyword_tracking_enabled' => false,
            'skyscanner.testing_access' => [
                'base_path' => '/api/skyscanner',
                'redirect_path' => '/skyscanner/redirect',
                'ip_allowlist_required' => true,
                'auth_header' => 'x-api-key',
                'auth_scheme' => 'header',
            ],
            'skyscanner.allowlisted_ips' => ['1.1.1.1'],
            'skyscanner.api_key' => 'secret-key',
        ]);

        $service = app(CarHireSurveyReadinessService::class);

        $summary = $service->buildSummary();

        $this->assertSame('PSM-46100', $summary['case_id']);
        $this->assertSame('internal', $summary['inventory_scope']);
        $this->assertTrue($summary['tracking']['dv_required_before_go_live']);
        $this->assertFalse($summary['tracking']['keyword_tracking_enabled']);
        $this->assertSame('/api/skyscanner', $summary['testing']['base_path']);
        $this->assertSame('/skyscanner/redirect', $summary['testing']['redirect_path']);
        $this->assertTrue($summary['security']['api_auth_configured']);
        $this->assertSame(1, $summary['security']['allowlisted_ip_count']);
    }
}
