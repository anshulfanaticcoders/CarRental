<?php

namespace Tests\Unit\Skyscanner;

use App\Services\Skyscanner\CarHireSecurityService;
use Tests\TestCase;

class CarHireSecurityServiceTest extends TestCase
{
    public function test_it_accepts_a_matching_partner_api_key(): void
    {
        config([
            'skyscanner.api_key' => 'secret-key',
        ]);

        $service = app(CarHireSecurityService::class);

        $this->assertTrue($service->hasValidApiKey('secret-key'));
    }

    public function test_it_rejects_a_missing_or_invalid_partner_api_key(): void
    {
        config([
            'skyscanner.api_key' => 'secret-key',
        ]);

        $service = app(CarHireSecurityService::class);

        $this->assertFalse($service->hasValidApiKey(null));
        $this->assertFalse($service->hasValidApiKey('wrong-key'));
    }

    public function test_it_builds_signed_redirect_params_and_verifies_them(): void
    {
        config([
            'skyscanner.signing_secret' => 'top-secret',
        ]);

        $service = app(CarHireSecurityService::class);

        $params = $service->buildSignedRedirectParams('quote-123', 'rid-123');

        $this->assertSame('quote-123', $params['quote_id']);
        $this->assertSame('rid-123', $params['skyscanner_redirectid']);
        $this->assertNotSame('', $params['signature']);
        $this->assertTrue($service->hasValidSignature($params));
    }

    public function test_it_rejects_tampered_signed_redirect_params(): void
    {
        config([
            'skyscanner.signing_secret' => 'top-secret',
        ]);

        $service = app(CarHireSecurityService::class);

        $params = $service->buildSignedRedirectParams('quote-123', 'rid-123');
        $params['quote_id'] = 'quote-999';

        $this->assertFalse($service->hasValidSignature($params));
    }

    public function test_it_reports_allowlisted_ips_and_access_rules_for_survey_readiness(): void
    {
        config([
            'skyscanner.api_key' => 'secret-key',
            'skyscanner.allowlisted_ips' => ['1.1.1.1', '2.2.2.2'],
            'skyscanner.testing_access' => [
                'ip_allowlist_required' => true,
                'auth_header' => 'x-api-key',
                'auth_scheme' => 'header',
            ],
        ]);

        $service = app(CarHireSecurityService::class);

        $summary = $service->surveyReadinessSummary();

        $this->assertSame([
            'api_auth_configured' => true,
            'ip_allowlist_required' => true,
            'allowlisted_ip_count' => 2,
            'auth_header' => 'x-api-key',
            'auth_scheme' => 'header',
        ], $summary);
    }

    public function test_it_reports_missing_api_auth_when_not_configured(): void
    {
        config([
            'skyscanner.api_key' => '',
            'skyscanner.allowlisted_ips' => [],
            'skyscanner.testing_access' => [
                'ip_allowlist_required' => true,
                'auth_header' => 'x-api-key',
                'auth_scheme' => 'header',
            ],
        ]);

        $service = app(CarHireSecurityService::class);

        $summary = $service->surveyReadinessSummary();

        $this->assertFalse($summary['api_auth_configured']);
        $this->assertSame(0, $summary['allowlisted_ip_count']);
    }
}
