<?php

namespace Tests\Unit\Skyscanner;

use App\Services\Skyscanner\CarHireAuditLogService;
use App\Services\Skyscanner\CarHireBookingCorrelationService;
use App\Services\Skyscanner\CarHireTrackingService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CarHireBookingCorrelationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_it_builds_and_stores_booking_correlation_from_redirect_context(): void
    {
        $auditLog = app(CarHireAuditLogService::class);
        $tracking = app(CarHireTrackingService::class);
        $service = app(CarHireBookingCorrelationService::class);

        $tracking->rememberRedirectCorrelation('rid-123', 'quote-123', [
            'case_id' => 'PSM-46100',
            'provider_vehicle_id' => '327',
        ]);

        $correlation = $service->correlateBooking('rid-123', [
            'booking_reference' => 'BK123',
            'provider_booking_ref' => 'SUP123',
            'booking_currency' => 'EUR',
            'total_amount' => 120.50,
            'booking_status' => 'confirmed',
            'pickup_date' => '2026-06-15',
            'return_date' => '2026-06-18',
        ]);

        $stored = $service->getBookingCorrelation('rid-123');
        $logs = $auditLog->get('redirect', 'rid-123');

        $this->assertSame([
            'redirect_id' => 'rid-123',
            'quote_id' => 'quote-123',
            'case_id' => 'PSM-46100',
            'provider_vehicle_id' => '327',
            'booking_reference' => 'BK123',
            'provider_booking_ref' => 'SUP123',
            'booking_currency' => 'EUR',
            'total_amount' => 120.50,
            'booking_status' => 'confirmed',
            'pickup_date' => '2026-06-15',
            'return_date' => '2026-06-18',
        ], $correlation);

        $this->assertSame($correlation, $stored);
        $this->assertCount(1, $logs);
        $this->assertSame('booking_correlated', $logs[0]['event']);
        $this->assertSame('BK123', $logs[0]['payload']['booking_reference']);
    }

    public function test_it_returns_null_when_redirect_context_does_not_exist(): void
    {
        $service = app(CarHireBookingCorrelationService::class);

        $this->assertNull($service->correlateBooking('missing-rid', [
            'booking_reference' => 'BK123',
        ]));
    }

    public function test_it_builds_a_report_ready_row_from_booking_correlation(): void
    {
        $service = app(CarHireBookingCorrelationService::class);

        $row = $service->buildReportRow([
            'redirect_id' => 'rid-123',
            'quote_id' => 'quote-123',
            'case_id' => 'PSM-46100',
            'provider_vehicle_id' => '327',
            'booking_reference' => 'BK123',
            'provider_booking_ref' => 'SUP123',
            'booking_currency' => 'EUR',
            'total_amount' => 120.50,
            'booking_status' => 'confirmed',
            'pickup_date' => '2026-06-15',
            'return_date' => '2026-06-18',
        ]);

        $this->assertSame([
            'case_id' => 'PSM-46100',
            'redirect_id' => 'rid-123',
            'quote_id' => 'quote-123',
            'provider_vehicle_id' => '327',
            'booking_reference' => 'BK123',
            'provider_booking_ref' => 'SUP123',
            'status' => 'confirmed',
            'currency' => 'EUR',
            'amount' => 120.50,
            'pickup_date' => '2026-06-15',
            'return_date' => '2026-06-18',
        ], $row);
    }
}
