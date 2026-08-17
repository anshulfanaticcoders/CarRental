<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use App\Services\StripeBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * booking_number is UNIQUE and used to be 'BK'.Ym.4 random digits with no
 * uniqueness check — 9,999 values per month. Under real volume collisions
 * 500'd the payment webhook and raised false "manual refund required" alerts
 * for payments that were fine.
 */
class BookingNumberCollisionTest extends TestCase
{
    use RefreshDatabase;

    private function bookingWithNumber(string $number): Booking
    {
        $user = User::create([
            'first_name' => 'Collide', 'last_name' => 'Customer',
            'email' => uniqid('collide').'@example.com', 'phone' => '+2782'.mt_rand(1000000, 9999999),
            'password' => bcrypt('password'), 'role' => 'customer', 'status' => 'active',
        ]);
        $customer = Customer::create([
            'user_id' => $user->id, 'first_name' => 'Collide', 'last_name' => 'Customer',
            'email' => $user->email, 'phone' => $user->phone,
        ]);

        return Booking::create([
            'booking_number' => $number,
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
        ]);
    }

    #[Test]
    public function generated_numbers_use_the_widened_format(): void
    {
        $this->assertMatchesRegularExpression(
            '/^BK'.date('Ym').'\d{6}$/',
            Booking::generateBookingNumber()
        );
    }

    #[Test]
    public function the_generator_never_returns_a_number_already_in_the_table(): void
    {
        // Same seed → same random sequence. Occupying the first draw forces the
        // generator's exists() loop to move on to the next one.
        mt_srand(42);
        $firstDraw = 'BK'.date('Ym').str_pad((string) mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $this->bookingWithNumber($firstDraw);

        mt_srand(42);
        $generated = Booking::generateBookingNumber();

        $this->assertNotSame($firstDraw, $generated);
        $this->assertDatabaseMissing('bookings', ['booking_number' => $generated]);
    }

    #[Test]
    public function a_concurrent_insert_collision_is_retried_with_a_fresh_number(): void
    {
        // Simulates the check-then-insert race: the number passed the exists()
        // check but another request inserted it first. The insert must retry
        // with a new number instead of bubbling a webhook 500.
        $taken = 'BK'.date('Ym').'000042';
        $existing = $this->bookingWithNumber($taken);

        $service = app(StripeBookingService::class);
        $method = new ReflectionMethod($service, 'createBookingRow');

        $booking = $method->invoke($service, [
            'booking_number' => $taken,
            'customer_id' => $existing->customer_id,
            'provider_source' => 'greenmotion',
            'pickup_date' => now()->addDays(20),
            'return_date' => now()->addDays(23),
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
        ]);

        $this->assertNotSame($taken, $booking->booking_number);
        $this->assertSame(2, Booking::count());
    }
}
