<?php

namespace Tests\Unit\Skyscanner;

use App\Services\Skyscanner\CarHireBookingCorrelationService;
use App\Services\Skyscanner\CarHireReportingExportService;
use App\Services\Skyscanner\CarHireTrackingService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CarHireReportingExportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_it_exports_report_rows_for_stored_booking_correlations(): void
    {
        $tracking = app(CarHireTrackingService::class);
        $bookingCorrelation = app(CarHireBookingCorrelationService::class);
        $exportService = app(CarHireReportingExportService::class);

        $tracking->rememberRedirectCorrelation('rid-123', 'quote-123', [
            'case_id' => 'PSM-46100',
            'provider_vehicle_id' => '327',
        ]);

        $bookingCorrelation->correlateBooking('rid-123', [
            'booking_reference' => 'BK123',
            'provider_booking_ref' => 'SUP123',
            'booking_currency' => 'EUR',
            'total_amount' => 120.50,
            'booking_status' => 'confirmed',
            'pickup_date' => '2026-06-15',
            'return_date' => '2026-06-18',
        ]);

        $rows = $exportService->exportRows();

        $this->assertSame([
            [
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
            ],
        ], $rows);
    }
}
