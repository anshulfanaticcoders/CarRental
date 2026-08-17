<?php

namespace Tests\Feature;

use App\Jobs\TriggerProviderReservationJob;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\StripeCheckoutPayload;
use App\Models\User;
use App\Notifications\Payment\AdminReservationManualCheckNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A paid booking whose reservation job was lost (worker down, restart mid-job,
 * swallowed dispatch) used to sit "Provider pending" forever — the admin rescue
 * queue is a read-only filter. The sweep is the retry. It must rescue exactly
 * the stuck ones, never race an in-flight job, and hand permanently broken
 * bookings to a human instead of looping.
 */
class RetryPendingProviderReservationsTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    private function booking(array $overrides = []): Booking
    {
        $this->seq++;
        $user = User::create([
            'first_name' => 'Stuck', 'last_name' => 'Customer',
            'email' => "stuck{$this->seq}@example.com", 'phone' => '+2782999'.str_pad((string) $this->seq, 4, '0'),
            'password' => bcrypt('password'), 'role' => 'customer', 'status' => 'active',
        ]);
        $customer = Customer::create([
            'user_id' => $user->id, 'first_name' => 'Stuck', 'last_name' => 'Customer',
            'email' => "stuck{$this->seq}@example.com", 'phone' => '+2782999'.str_pad((string) $this->seq, 4, '0'),
        ]);

        $booking = Booking::create(array_merge([
            'booking_number' => 'BK-SWEEP-'.$this->seq,
            'customer_id' => $customer->id,
            'provider_source' => 'greenmotion',
            'pickup_date' => now()->addDays(10),
            'return_date' => now()->addDays(13),
            'pickup_time' => '10:00',
            'return_time' => '10:00',
            'pickup_location' => 'Faro Airport',
            'return_location' => 'Faro Airport',
            'plan' => 'BAS',
            'total_days' => 3,
            'base_price' => 100,
            'tax_amount' => 0,
            'total_amount' => 100,
            'amount_paid' => 15,
            'pending_amount' => 85,
            'payment_status' => 'partial',
            'booking_status' => 'confirmed',
            'stripe_session_id' => 'cs_sweep_'.$this->seq,
        ], $overrides));

        // The sweep only touches bookings quiet for 3+ hours.
        Booking::whereKey($booking->id)->update([
            'created_at' => now()->subHours(5),
            'updated_at' => now()->subHours(5),
        ]);

        return $booking->refresh();
    }

    private function payloadFor(Booking $booking): void
    {
        StripeCheckoutPayload::create([
            'stripe_session_id' => $booking->stripe_session_id,
            'payload' => ['full_metadata' => [
                'gateway_vehicle_id' => 'gw_1', 'gateway_search_id' => 'gws-1',
                'customer_name' => 'Stuck Customer', 'customer_email' => 'stuck@example.com',
            ]],
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'first_name' => 'Site', 'last_name' => 'Admin',
            'email' => config('admin.email'), 'phone' => '+27821119999',
            'password' => bcrypt('password'), 'role' => 'admin', 'status' => 'active',
        ]);
    }

    #[Test]
    public function a_stuck_paid_booking_is_redispatched(): void
    {
        Queue::fake();
        $booking = $this->booking();
        $this->payloadFor($booking);

        $this->artisan('bookings:retry-provider-reservations')->assertSuccessful();

        Queue::assertPushed(TriggerProviderReservationJob::class,
            fn ($job) => $job->bookingId === $booking->id && $job->metadata['gateway_vehicle_id'] === 'gw_1');
        $this->assertSame(1, $booking->refresh()->provider_metadata['rescue_attempts']);
    }

    #[Test]
    public function a_pending_paid_booking_is_swept_too(): void
    {
        // The admin provider-pending queue includes booking_status 'pending';
        // a sweep that only matched 'confirmed' left those rows permanently
        // stuck with no retry path and (until the close-out fix) no cancel path.
        Queue::fake();
        $booking = $this->booking(['booking_status' => 'pending']);
        $this->payloadFor($booking);

        $this->artisan('bookings:retry-provider-reservations')->assertSuccessful();

        Queue::assertPushed(TriggerProviderReservationJob::class,
            fn ($job) => $job->bookingId === $booking->id);
    }

    #[Test]
    public function a_recently_active_booking_is_left_alone(): void
    {
        // Its job chain may still be retrying with backoff — racing it could
        // reserve two cars for one customer.
        Queue::fake();
        $booking = $this->booking();
        $this->payloadFor($booking);
        Booking::whereKey($booking->id)->update(['updated_at' => now()->subMinutes(30)]);

        $this->artisan('bookings:retry-provider-reservations')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    #[Test]
    public function reserved_internal_unpaid_and_manual_review_bookings_are_ignored(): void
    {
        Queue::fake();
        $this->payloadFor($this->booking(['provider_booking_ref' => 'VEM-111']));
        $this->payloadFor($this->booking(['provider_source' => 'internal']));
        $this->payloadFor($this->booking(['payment_status' => 'pending']));
        $this->payloadFor($this->booking(['provider_metadata' => ['reservation_manual_check' => true]]));
        $this->payloadFor($this->booking(['provider_metadata' => ['manual_refund_required' => true]]));

        $this->artisan('bookings:retry-provider-reservations')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    #[Test]
    public function after_max_attempts_it_goes_to_a_human_and_stops(): void
    {
        Queue::fake();
        Notification::fake();
        $admin = $this->admin();
        $booking = $this->booking(['provider_metadata' => ['rescue_attempts' => 2]]);
        $this->payloadFor($booking);

        $this->artisan('bookings:retry-provider-reservations')->assertSuccessful();

        Queue::assertNothingPushed();
        $this->assertTrue((bool) $booking->refresh()->provider_metadata['reservation_manual_check']);
        Notification::assertSentTo($admin, AdminReservationManualCheckNotification::class);

        // The flag removes it from every future sweep — this must fire once.
        Notification::fake();
        $this->artisan('bookings:retry-provider-reservations')->assertSuccessful();
        Notification::assertNothingSent();
    }

    #[Test]
    public function metadata_is_recovered_via_the_payload_id_when_the_session_backpatch_failed(): void
    {
        // The payload row's stripe_session_id is written best-effort AFTER the
        // Stripe session is created; when that write failed, the sweep used to
        // give up on a booking whose metadata was sitting right there.
        Queue::fake();
        $booking = $this->booking();
        $payload = StripeCheckoutPayload::create([
            'stripe_session_id' => null, // the failed back-patch
            'payload' => ['full_metadata' => [
                'gateway_vehicle_id' => 'gw_backpatch', 'gateway_search_id' => 'gws-bp',
            ]],
        ]);
        Booking::whereKey($booking->id)->update([
            'provider_metadata' => json_encode(['checkout_payload_id' => $payload->id]),
            'updated_at' => now()->subHours(5), // stay in the sweep's quiet window
        ]);

        $this->artisan('bookings:retry-provider-reservations')->assertSuccessful();

        Queue::assertPushed(TriggerProviderReservationJob::class,
            fn ($job) => $job->bookingId === $booking->id && $job->metadata['gateway_vehicle_id'] === 'gw_backpatch');
    }

    #[Test]
    public function unrecoverable_metadata_goes_to_a_human_not_a_loop(): void
    {
        Queue::fake();
        Notification::fake();
        $admin = $this->admin();
        $booking = $this->booking(); // no payload row

        $this->artisan('bookings:retry-provider-reservations')->assertSuccessful();

        Queue::assertNothingPushed();
        $this->assertTrue((bool) $booking->refresh()->provider_metadata['reservation_manual_check']);
        Notification::assertSentTo($admin, AdminReservationManualCheckNotification::class);
    }

    #[Test]
    public function dry_run_changes_nothing(): void
    {
        Queue::fake();
        Notification::fake();
        $booking = $this->booking();
        $this->payloadFor($booking);

        $this->artisan('bookings:retry-provider-reservations', ['--dry-run' => true])->assertSuccessful();

        Queue::assertNothingPushed();
        Notification::assertNothingSent();
        $this->assertArrayNotHasKey('rescue_attempts', $booking->refresh()->provider_metadata ?? []);
    }
}
