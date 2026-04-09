<?php

namespace App\Services\Skyscanner;

class CarHireSurveyReadinessService
{
    public function __construct(
        private readonly CarHireSecurityService $securityService,
    ) {
    }

    public function buildSummary(): array
    {
        return [
            'case_id' => (string) config('skyscanner.case_id'),
            'inventory_scope' => (string) config('skyscanner.inventory_scope'),
            'security' => $this->securityService->surveyReadinessSummary(),
            'tracking' => [
                'dv_required_before_go_live' => (bool) config('skyscanner.dv_required_before_go_live'),
                'keyword_tracking_enabled' => (bool) config('skyscanner.keyword_tracking_enabled'),
            ],
            'testing' => [
                'base_path' => (string) config('skyscanner.testing_access.base_path', '/api/skyscanner'),
                'redirect_path' => (string) config('skyscanner.testing_access.redirect_path', '/skyscanner/redirect'),
            ],
        ];
    }
}
