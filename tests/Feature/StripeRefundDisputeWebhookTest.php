<?php

namespace Tests\Feature;

use App\Http\Controllers\StripeWebhookController;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Customer;
use App\Models\User;
use App\Notifications\Payment\AdminChargeDisputeNotification;
use App\Notifications\Payment\AdminChargeRefundedNotification;
use App\Services\StripeBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Refunds are performed manually in the Stripe dashboard by design — these
 * events are the only way the app learns about them. Before this handler a
 * refunded booking stayed confirmed/partial forever, the supplier reservation
 * stayed live, and a chargeback was invisible until the money was gone.
 */
class StripeRefundDisputeWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::create([
            'first_name' => 'Site', 'last_name' => 'Admin',
            'email' => config('admin.email'), 'phone' => '+27821110000',
            'password' => bcrypt('password'), 'role' => 'admin', 'status' => 'active',
        ]);
    }

    private function paidBooking(array $overrides = []): Booking
    {
        $user = User::create([
            'first_name' => 'Refund', 'last_name' => 'Customer',
            'email' => uniqid('refund').'@example.com', 'phone' => '+2782'.mt_rand(1000000, 9999999),
            'password' => bcrypt('password'), 'role' => 'customer', 'status' => 'active',
        ]);
        $customer = Customer::create([
            'user_id' => $user->id, 'first_name' => 'Refund', 'last_name' => 'Customer',
            'email' => $user->email, 'phone' => $user->phone,
        ]);

        $booking = Booking::create(array_merge([
            'booking_number' => 'BK-REFUND-'.uniqid(),
            'customer_id' => $customer->id,
            'provider_source' => 'greenmotion',
            'pickup_date' => now()->addDays(10),
            'return_date' => now()->addDays(13),
            'pickup_time' => '10:00', 'return_time' => '10:00',
            'pickup_location' => 'Faro Airport', 'return_location' => 'Faro Airport',
            'plan' => 'BAS', 'total_days' => 3,
            'base_price' => 100, 'tax_amount' => 0, 'total_amount' => 100,
            'amount_paid' => 15, 'pending_amount' => 85,
            'payment_status' => 'partial', 'booking_status' => 'confirmed',
            'stripe_payment_intent_id' => 'pi_refund_'.uniqid(),
        ], $overrides));

        BookingPayment::create([
            'booking_id' => $booking->id,
            'payment_method' => 'stripe',
            'transaction_id' => $booking->stripe_payment_intent_id,
            'amount' => 15, 'currency' => 'EUR',
            'payment_status' => 'succeeded', 'payment_date' => now(),
        ]);

        return $booking;
    }

    private function invoke(string $method, object $payload): void
    {
        $controller = new StripeWebhookController(Mockery::mock(StripeBookingService::class));
        $ref = new ReflectionMethod($controller, $method);
        $ref->invoke($controller, $payload);
    }

    #[Test]
    public function a_full_refund_updates_the_booking_and_alerts_admin(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $booking = $this->paidBooking(['provider_booking_ref' => 'VEM-555']);

        $this->invoke('handleChargeRefunded', (object) [
            'payment_intent' => $booking->stripe_payment_intent_id,
            'refunded' => true,
            'amount_refunded' => 1500,
            'currency' => 'eur',
        ]);

        $booking->refresh();
        $this->assertSame('refunded', $booking->payment_status);
        $this->assertTrue((bool) $booking->provider_metadata['fully_refunded']);
        $this->assertSame(1500, $booking->provider_metadata['refund_amount_minor']);
        $this->assertDatabaseHas('booking_payments', [
            'transaction_id' => $booking->stripe_payment_intent_id,
            'payment_status' => 'refunded',
        ]);
        Notification::assertSentTo($admin, AdminChargeRefundedNotification::class);
    }

    #[Test]
    public function a_partial_refund_is_recorded_without_regressing_payment_status(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $booking = $this->paidBooking();

        $this->invoke('handleChargeRefunded', (object) [
            'payment_intent' => $booking->stripe_payment_intent_id,
            'refunded' => false,
            'amount_refunded' => 500,
            'currency' => 'eur',
        ]);

        $booking->refresh();
        $this->assertSame('partial', $booking->payment_status);
        $this->assertSame(500, $booking->provider_metadata['refund_amount_minor']);
        $this->assertDatabaseHas('booking_payments', [
            'transaction_id' => $booking->stripe_payment_intent_id,
            'payment_status' => 'succeeded',
        ]);
        Notification::assertSentTo($admin, AdminChargeRefundedNotification::class);
    }

    #[Test]
    public function a_refund_with_no_matching_booking_is_ignored_quietly(): void
    {
        Notification::fake();
        $this->admin();

        $this->invoke('handleChargeRefunded', (object) [
            'payment_intent' => 'pi_unknown',
            'refunded' => true,
            'amount_refunded' => 100,
            'currency' => 'eur',
        ]);

        Notification::assertNothingSent();
    }

    #[Test]
    public function a_dispute_is_recorded_and_alerts_admin(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $booking = $this->paidBooking();

        $this->invoke('handleChargeDispute', (object) [
            'payment_intent' => $booking->stripe_payment_intent_id,
            'reason' => 'fraudulent',
            'amount' => 1500,
            'currency' => 'eur',
        ]);

        $booking->refresh();
        $this->assertSame('fraudulent', $booking->provider_metadata['dispute_reason']);
        $this->assertNotNull($booking->provider_metadata['dispute_opened_at']);
        $this->assertSame('confirmed', $booking->booking_status, 'A dispute must not auto-cancel the booking.');
        Notification::assertSentTo($admin, AdminChargeDisputeNotification::class);
    }
}
