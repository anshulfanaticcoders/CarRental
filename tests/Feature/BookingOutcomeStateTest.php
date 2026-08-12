<?php

namespace Tests\Feature;

use App\Http\Controllers\StripeCheckoutController;
use App\Models\Booking;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The status page must never lie to a charged customer: "payment cancelled"
 * is only allowed when nothing was captured.
 */
class BookingOutcomeStateTest extends TestCase
{
    private function resolveState(Booking $booking): string
    {
        $method = new ReflectionMethod(StripeCheckoutController::class, 'resolveBookingOutcomeState');
        $method->setAccessible(true);

        return $method->invoke(app(StripeCheckoutController::class), $booking, 'support_review');
    }

    private function makeBooking(array $attributes): Booking
    {
        return (new Booking)->forceFill($attributes);
    }

    #[Test]
    public function charged_cancelled_booking_shows_refund_review_not_payment_cancelled(): void
    {
        $state = $this->resolveState($this->makeBooking([
            'booking_status' => 'cancelled',
            'payment_status' => 'payment_cancelled',
            'amount_paid' => 100,
        ]));

        $this->assertSame('refund_pending', $state);
    }

    #[Test]
    public function uncharged_cancelled_booking_shows_payment_cancelled(): void
    {
        $state = $this->resolveState($this->makeBooking([
            'booking_status' => 'cancelled',
            'payment_status' => 'payment_cancelled',
            'amount_paid' => 0,
        ]));

        $this->assertSame('payment_cancelled', $state);
    }

    #[Test]
    public function reservation_failed_booking_shows_truthful_review_state(): void
    {
        $state = $this->resolveState($this->makeBooking([
            'booking_status' => 'reservation_failed',
            'payment_status' => 'partial',
            'amount_paid' => 100,
        ]));

        $this->assertSame('reservation_failed', $state);
    }
}
