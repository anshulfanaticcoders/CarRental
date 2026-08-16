<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Notifications\Booking\AdminBookingNeedsCorrectionNotification;
use App\Services\StripeBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * A Stripe session reaching booking creation is already PAID. If its metadata
 * arrives degraded, the booking must still be created — defaulted and flagged —
 * never rolled back by a NOT NULL column, which would orphan the charge.
 */
class PaidSessionDegradedMetadataTest extends TestCase
{
    use RefreshDatabase;

    /** Build a session whose metadata is the given array, mimicking Stripe's object. */
    private function sessionWithMetadata(array $metadata): object
    {
        return (object) [
            'id' => 'cs_test_degraded_metadata',
            'payment_intent' => 'pi_test_degraded_metadata',
            'metadata' => new class($metadata)
            {
                public $extras_payload_id = null;

                public function __construct(private array $data) {}

                public function toArray(): array
                {
                    return $this->data;
                }

                public function __get($name)
                {
                    return $this->data[$name] ?? null;
                }

                public function __isset($name)
                {
                    return array_key_exists($name, $this->data);
                }
            },
        ];
    }

    /** The alert recipient the service looks up by config('admin.email'). */
    private function makeAdmin(): \App\Models\User
    {
        return \App\Models\User::create([
            'first_name' => 'Site',
            'last_name' => 'Admin',
            'email' => config('admin.email'),
            'phone' => '+27821110000',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function guard(array $bookingData): array
    {
        $method = new ReflectionMethod(StripeBookingService::class, 'guardRequiredBookingFields');
        $method->setAccessible(true);

        return $method->invoke(app(StripeBookingService::class), $bookingData);
    }

    #[Test]
    public function every_required_column_is_covered_by_a_default(): void
    {
        // The guard is only as good as its column list. If a NOT NULL column with
        // no DB default is added and not registered, this fails instead of a
        // customer's paid booking failing.
        $required = collect(DB::select(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
               AND IS_NULLABLE = \'NO\' AND COLUMN_DEFAULT IS NULL',
            ['bookings']
        ))->pluck('COLUMN_NAME')
            // Not metadata-fed: generated, resolved, or auto-managed.
            ->reject(fn ($c) => in_array($c, ['id', 'booking_number', 'customer_id'], true))
            ->sort()->values()->all();

        $method = new ReflectionMethod(StripeBookingService::class, 'requiredBookingDefaults');
        $method->setAccessible(true);
        $covered = array_keys($method->invoke(app(StripeBookingService::class)));

        // Subset, not equality: the guard deliberately covers columns that are
        // nullable in this schema but NOT NULL in others.
        $this->assertEmpty(array_diff($required, $covered),
            'Unguarded NOT NULL bookings columns — a paid session can still be '
            .'rejected by the database: '.implode(', ', array_diff($required, $covered)));
    }

    #[Test]
    public function present_values_pass_through_untouched(): void
    {
        [$data, $defaulted] = $this->guard([
            'pickup_time' => '14:30',
            'return_time' => '11:00',
            'pickup_location' => 'Faro Airport',
            'return_location' => 'Faro Airport',
        ]);

        $this->assertSame('14:30', $data['pickup_time']);
        $this->assertSame('Faro Airport', $data['pickup_location']);
        $this->assertSame([], $defaulted);
    }

    #[Test]
    public function null_and_blank_values_are_filled_and_reported(): void
    {
        [$data, $defaulted] = $this->guard([
            'pickup_time' => null,
            'return_time' => '  ',
            'pickup_location' => null,
            'return_location' => 'Faro Airport',
        ]);

        $this->assertSame('09:00', $data['pickup_time']);
        $this->assertSame('09:00', $data['return_time']);
        $this->assertSame('Unknown — needs correction', $data['pickup_location']);
        $this->assertSame('Faro Airport', $data['return_location']);
        $this->assertSame(['pickup_time', 'return_time', 'pickup_location'], $defaulted);
    }

    #[Test]
    public function paid_session_with_stripped_metadata_still_creates_a_flagged_booking(): void
    {
        Queue::fake();
        Notification::fake();
        $admin = $this->makeAdmin();

        // The exact shape of the Aug 16 live failure, widened: the date, time AND
        // location keys never arrived. Previously this threw a 1048 and the
        // transaction rolled back, leaving a captured charge with no booking.
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->sessionWithMetadata([
                'vehicle_source' => 'greenmotion',
                'vehicle_id' => 'gw_9',
                'customer_name' => 'Degraded Metadata',
                'customer_email' => 'degraded@example.com',
                'customer_phone' => '+27829999123',
                'customer_driver_age' => 35,
                'number_of_days' => 3,
                'total_amount' => 100,
                'payable_amount' => 15,
                'pending_amount' => 85,
                'currency' => 'EUR',
            ])
        );

        $this->assertNotNull($booking, 'A paid session must never fail to produce a booking.');
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'stripe_session_id' => 'cs_test_degraded_metadata',
            'pickup_time' => '09:00',
            'return_time' => '09:00',
            'pickup_location' => 'Unknown — needs correction',
        ]);

        $this->assertNotNull($booking->pickup_date, 'A NOT NULL date column must be filled, not left null.');
        $this->assertStringContainsString('NEEDS CORRECTION', (string) $booking->fresh()->notes);
        Notification::assertSentTo($admin, AdminBookingNeedsCorrectionNotification::class);
    }

    #[Test]
    public function complete_metadata_creates_a_booking_with_no_correction_flag(): void
    {
        Queue::fake();
        Notification::fake();
        $admin = $this->makeAdmin();

        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->sessionWithMetadata([
                'vehicle_source' => 'greenmotion',
                'vehicle_id' => 'gw_10',
                'customer_name' => 'Complete Metadata',
                'customer_email' => 'complete@example.com',
                'customer_phone' => '+27829999124',
                'customer_driver_age' => 35,
                'pickup_date' => now()->addDays(30)->toDateString(),
                'pickup_time' => '10:30',
                'dropoff_date' => now()->addDays(33)->toDateString(),
                'dropoff_time' => '16:45',
                'pickup_location' => 'Faro Airport',
                'dropoff_location' => 'Faro Airport',
                'number_of_days' => 3,
                'total_amount' => 100,
                'payable_amount' => 15,
                'pending_amount' => 85,
                'currency' => 'EUR',
                'package' => 'BAS',
            ])
        );

        $this->assertNotNull($booking);
        $this->assertSame('10:30', $booking->pickup_time);
        $this->assertSame('16:45', $booking->return_time);
        $this->assertStringNotContainsString('NEEDS CORRECTION', (string) $booking->fresh()->notes);
        Notification::assertNotSentTo($admin, AdminBookingNeedsCorrectionNotification::class);
    }

    #[Test]
    public function bookings_table_rejects_the_pre_fix_insert(): void
    {
        // Guards the guard: proves pickup_time is genuinely NOT NULL, so the
        // tests above are exercising a real constraint and not a no-op.
        $this->expectException(\Illuminate\Database\QueryException::class);

        Booking::create([
            'booking_number' => 'BK-NULL-TIME-CHECK',
            'customer_id' => 1,
            'pickup_date' => now()->addDays(5),
            'return_date' => now()->addDays(7),
            'pickup_time' => null,
            'return_time' => '09:00',
            'pickup_location' => 'Faro Airport',
            'return_location' => 'Faro Airport',
            'plan' => 'BAS',
            'total_days' => 2,
            'base_price' => 10,
            'tax_amount' => 0,
            'total_amount' => 10,
        ]);
    }
}
