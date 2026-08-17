<?php

namespace Tests\Feature;

use App\Notifications\Booking\AdminBookingNeedsCorrectionNotification;
use App\Services\StripeBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The booking records metadata's payable_amount; Stripe's session records what
 * was actually captured. When they disagree (failed conversion charged the raw
 * number in the wrong currency, replayed session, Stripe-side change), the
 * booking must be created but flagged — never silently recorded as correct.
 */
class StripeAmountReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): \App\Models\User
    {
        return \App\Models\User::create([
            'first_name' => 'Site', 'last_name' => 'Admin',
            'email' => config('admin.email'), 'phone' => '+27821110000',
            'password' => bcrypt('password'), 'role' => 'admin', 'status' => 'active',
        ]);
    }

    private function paidSession(array $sessionOverrides = []): object
    {
        return (object) array_merge([
            'id' => 'cs_test_amount_reconciliation',
            'payment_intent' => 'pi_test_amount_reconciliation',
            'amount_total' => 1500, // 15.00 EUR — matches payable_amount below
            'currency' => 'eur',
            'metadata' => \Stripe\StripeObject::constructFrom([
                'vehicle_source' => 'greenmotion',
                'vehicle_id' => 'gw_5',
                'gateway_vehicle_id' => 'gw_5',
                'customer_name' => 'Amount Check',
                'customer_email' => 'amount@example.com',
                'customer_phone' => '+27829999456',
                'customer_driver_age' => 35,
                'number_of_days' => 3,
                'total_amount' => 100,
                'payable_amount' => 15,
                'pending_amount' => 85,
                'currency' => 'EUR',
                'pickup_date' => now()->addDays(5)->format('Y-m-d'),
                'dropoff_date' => now()->addDays(8)->format('Y-m-d'),
                'pickup_time' => '10:00',
                'dropoff_time' => '10:00',
                'pickup_location' => 'Faro Airport',
                'dropoff_location' => 'Faro Airport',
            ]),
        ], $sessionOverrides);
    }

    #[Test]
    public function a_matching_charge_is_not_flagged(): void
    {
        Queue::fake();
        Notification::fake();
        $this->makeAdmin();

        $booking = app(StripeBookingService::class)->createBookingFromSession($this->paidSession());

        $this->assertNotNull($booking);
        $this->assertStringNotContainsString('NEEDS CORRECTION', (string) $booking->fresh()->notes);
        Notification::assertNotSentTo(
            \App\Models\User::where('email', config('admin.email'))->first(),
            AdminBookingNeedsCorrectionNotification::class
        );
    }

    #[Test]
    public function a_wrong_currency_capture_is_flagged_but_the_booking_survives(): void
    {
        Queue::fake();
        Notification::fake();
        $admin = $this->makeAdmin();

        // The B2 disaster shape: metadata says 15 EUR, Stripe captured 1500 HUF.
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->paidSession(['currency' => 'huf'])
        );

        $this->assertNotNull($booking, 'A mismatched charge must flag, never destroy, the booking.');
        $booking->refresh();
        $this->assertStringContainsString('NEEDS CORRECTION', (string) $booking->notes);
        $this->assertSame('HUF', $booking->provider_metadata['amount_mismatch']['charged_currency']);
        Notification::assertSentTo($admin, AdminBookingNeedsCorrectionNotification::class);
    }

    #[Test]
    public function a_wrong_amount_capture_is_flagged(): void
    {
        Queue::fake();
        Notification::fake();
        $admin = $this->makeAdmin();

        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->paidSession(['amount_total' => 300]) // 3.00 EUR captured, 15.00 expected
        );

        $booking->refresh();
        $this->assertStringContainsString('NEEDS CORRECTION', (string) $booking->notes);
        $this->assertSame(300, $booking->provider_metadata['amount_mismatch']['charged_minor']);
        Notification::assertSentTo($admin, AdminBookingNeedsCorrectionNotification::class);
    }
}
