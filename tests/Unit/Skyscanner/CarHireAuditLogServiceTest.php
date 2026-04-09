<?php

namespace Tests\Unit\Skyscanner;

use App\Services\Skyscanner\CarHireAuditLogService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CarHireAuditLogServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_it_appends_and_returns_logs_for_a_specific_entity(): void
    {
        config([
            'skyscanner.case_id' => 'PSM-46100',
        ]);

        $service = app(CarHireAuditLogService::class);

        $service->append('quote', 'quote-123', 'quote_created', [
            'provider_vehicle_id' => '327',
        ]);

        $service->append('quote', 'quote-123', 'quote_redirected', [
            'redirect_id' => 'rid-123',
        ]);

        $logs = $service->get('quote', 'quote-123');

        $this->assertCount(2, $logs);
        $this->assertSame('PSM-46100', $logs[0]['case_id']);
        $this->assertSame('quote_created', $logs[0]['event']);
        $this->assertSame('quote_redirected', $logs[1]['event']);
        $this->assertSame('rid-123', $logs[1]['payload']['redirect_id']);
        $this->assertArrayHasKey('logged_at', $logs[0]);
    }

    public function test_it_returns_empty_array_for_unknown_entity_logs(): void
    {
        $service = app(CarHireAuditLogService::class);

        $this->assertSame([], $service->get('quote', 'missing-quote'));
    }
}
