<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Services\Trabber\TrabberReportService;
use Tests\TestCase;

/**
 * The Trabber report used to emit FULL commission on rows it itself marked
 * 'cancelled' — invoicing the partner for refunded bookings.
 */
class TrabberReportCommissionTest extends TestCase
{
    private function service(): TrabberReportService
    {
        return app(TrabberReportService::class);
    }

    public function test_cancelled_bookings_report_zero_commission(): void
    {
        $booking = new Booking([
            'total_amount' => 500,
            'booking_status' => 'cancelled',
            'payment_status' => 'refund_pending',
        ]);

        $this->assertSame(0.0, $this->service()->commissionForBooking($booking));
    }

    public function test_refunded_bookings_report_zero_commission(): void
    {
        $booking = new Booking([
            'total_amount' => 500,
            'booking_status' => 'confirmed',
            'payment_status' => 'refunded',
        ]);

        $this->assertSame(0.0, $this->service()->commissionForBooking($booking));
    }

    public function test_live_bookings_still_earn_commission(): void
    {
        config(['trabber.commission_rate' => 0.05]);
        $booking = new Booking([
            'total_amount' => 500,
            'booking_status' => 'confirmed',
            'payment_status' => 'partial',
        ]);

        $this->assertSame(25.0, $this->service()->commissionForBooking($booking));
    }
}
