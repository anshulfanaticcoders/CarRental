<?php

namespace Tests\Feature;

use App\Exceptions\ReservationOutcomeUnknownException;
use App\Jobs\TriggerProviderReservationJob;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use App\Notifications\Payment\AdminReservationManualCheckNotification;
use App\Services\StripeBookingService;
use App\Services\VrooemGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProviderReservationOutcomeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function timeout_unknown_outcome_routes_to_manual_review_without_cancelling(): void
    {
        Notification::fake();
        $admin = $this->createAdminUser();
        $booking = $this->createExternalBooking();

        $this->mockGateway([
            'status' => 'failed',
            'provider_status' => 'timeout_unknown',
            'supplier_booking_id' => '',
            'supplier_data' => ['outcome_unknown' => true],
        ]);

        $threw = false;
        try {
            (new StripeBookingService)->triggerGatewayReservation($booking, $this->metadata());
        } catch (ReservationOutcomeUnknownException) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected ReservationOutcomeUnknownException for a supplier timeout');

        $booking->refresh();
        $this->assertTrue((bool) ($booking->provider_metadata['reservation_manual_check'] ?? false));
        // Must NOT be cancelled — supplier may already hold a reservation.
        $this->assertNotSame('cancelled', $booking->booking_status);
        $this->assertNull($booking->provider_booking_ref);

        Notification::assertSentTo($admin, AdminReservationManualCheckNotification::class);
    }

    #[Test]
    public function laravel_to_gateway_timeout_is_treated_as_unknown(): void
    {
        Notification::fake();
        $this->createAdminUser();
        $booking = $this->createExternalBooking();

        $this->mockGateway(null, [
            'type' => 'connection',
            'message' => 'cURL error 28: Operation timed out after 60000 milliseconds',
        ]);

        $this->expectException(ReservationOutcomeUnknownException::class);
        (new StripeBookingService)->triggerGatewayReservation($booking, $this->metadata());
    }

    #[Test]
    public function definite_failure_stays_retryable_and_is_not_manual_check(): void
    {
        Notification::fake();
        $this->createAdminUser();
        $booking = $this->createExternalBooking();

        // Gateway reachable, supplier rejected — safe to retry, not a duplicate risk.
        $this->mockGateway([
            'status' => 'failed',
            'provider_status' => 'failed',
            'supplier_booking_id' => '',
        ], null);

        $caughtUnknown = false;
        $caughtOther = false;
        try {
            (new StripeBookingService)->triggerGatewayReservation($booking, $this->metadata());
        } catch (ReservationOutcomeUnknownException) {
            $caughtUnknown = true;
        } catch (\RuntimeException) {
            $caughtOther = true;
        }

        $this->assertFalse($caughtUnknown, 'A plain supplier rejection must not be treated as unknown');
        $this->assertTrue($caughtOther, 'A plain failure must still throw (so the job retries)');

        $booking->refresh();
        $this->assertArrayNotHasKey('reservation_manual_check', $booking->provider_metadata ?? []);
        Notification::assertNothingSent();
    }

    #[Test]
    public function job_failed_leaves_unknown_outcome_booking_uncancelled(): void
    {
        $booking = $this->createExternalBooking();

        (new TriggerProviderReservationJob($booking->id, []))
            ->failed(new ReservationOutcomeUnknownException('unknown'));

        $booking->refresh();
        $this->assertNotSame('cancelled', $booking->booking_status);
        $this->assertNotSame('payment_cancelled', $booking->payment_status);
    }

    #[Test]
    public function job_failed_cancels_a_genuinely_failed_booking(): void
    {
        $booking = $this->createExternalBooking();

        (new TriggerProviderReservationJob($booking->id, []))
            ->failed(new \RuntimeException('gateway unreachable after retries'));

        $booking->refresh();
        $this->assertSame('cancelled', $booking->booking_status);
        $this->assertSame('payment_cancelled', $booking->payment_status);
        $this->assertTrue((bool) ($booking->provider_metadata['manual_refund_required'] ?? false));
    }

    private function mockGateway(?array $createResult, ?array $lastError = null): void
    {
        $this->mock(VrooemGatewayService::class, function ($mock) use ($createResult, $lastError): void {
            $mock->shouldReceive('createBooking')->andReturn($createResult);
            $mock->shouldReceive('getLastError')->andReturn($lastError);
        });
    }

    private function metadata(): object
    {
        return (object) [
            'gateway_vehicle_id' => 'gwv-1',
            'gateway_search_id' => 'gws-1',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'pickup_date' => now()->addDay()->toDateString(),
            'dropoff_date' => now()->addDays(2)->toDateString(),
        ];
    }

    private function createAdminUser(): User
    {
        return User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => config('admin.email', 'default@admin.com'),
            'phone' => '+10000000000',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function createExternalBooking(array $overrides = []): Booking
    {
        $customerUser = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => $customerUser->email,
            'phone' => $customerUser->phone,
            'driver_age' => 30,
        ]);

        return Booking::create(array_merge([
            'booking_number' => 'BKRES-'.uniqid(),
            'customer_id' => $customer->id,
            'vehicle_id' => null,
            'provider_source' => 'greenmotion',
            'provider_vehicle_id' => 'gwv-1',
            'provider_booking_ref' => null,
            'provider_metadata' => [],
            'vehicle_name' => 'Provider Vehicle',
            'pickup_date' => now()->addDay(),
            'return_date' => now()->addDays(2),
            'pickup_time' => '09:00',
            'return_time' => '09:00',
            'pickup_location' => 'Airport',
            'return_location' => 'Airport',
            'plan' => 'BAS',
            'total_days' => 1,
            'base_price' => 100,
            'extra_charges' => 0,
            'tax_amount' => 0,
            'total_amount' => 100,
            'pending_amount' => 100,
            'amount_paid' => 0,
            'booking_currency' => 'EUR',
            'payment_status' => 'partial',
            'booking_status' => 'confirmed',
        ], $overrides));
    }
}
