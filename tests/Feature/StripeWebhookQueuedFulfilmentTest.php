<?php

namespace Tests\Feature;

use App\Http\Controllers\StripeWebhookController;
use App\Jobs\ProcessPaidCheckoutSessionJob;
use App\Models\User;
use App\Notifications\Payment\AdminManualRefundRequiredNotification;
use App\Services\StripeBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The webhook must ack Stripe fast and do the work on the queue. Doing the
 * Stripe retrieve + FX calls inline pushed responses past Stripe's ~20s
 * timeout; the concurrent redeliveries then deadlocked on the bookings
 * unique index and raised false orphaned-payment alerts.
 */
class StripeWebhookQueuedFulfilmentTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function invokeComplete(object $session): void
    {
        $controller = new StripeWebhookController(Mockery::mock(StripeBookingService::class));
        $method = new ReflectionMethod($controller, 'handleCheckoutComplete');
        $method->invoke($controller, $session);
    }

    #[Test]
    public function a_paid_completed_session_queues_the_fulfilment_job(): void
    {
        Queue::fake();

        $this->invokeComplete((object) [
            'id' => 'cs_queued_1',
            'payment_status' => 'paid',
        ]);

        Queue::assertPushed(ProcessPaidCheckoutSessionJob::class,
            fn ($job) => $job->sessionId === 'cs_queued_1');
    }

    #[Test]
    public function an_unpaid_completed_session_queues_nothing(): void
    {
        Queue::fake();

        $this->invokeComplete((object) [
            'id' => 'cs_queued_2',
            'payment_status' => 'unpaid',
        ]);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function a_webhook_replay_never_downgrades_a_terminal_payload(): void
    {
        Queue::fake();
        \App\Models\StripeCheckoutPayload::create([
            'stripe_session_id' => 'cs_queued_5',
            'payload' => null,
            'fulfilment_status' => 'fulfilled',
            'booking_id' => 42,
        ]);

        $this->invokeComplete((object) [
            'id' => 'cs_queued_5',
            'payment_status' => 'paid',
        ]);

        $this->assertSame('fulfilled', \App\Models\StripeCheckoutPayload::where('stripe_session_id', 'cs_queued_5')
            ->value('fulfilment_status'));
    }

    #[Test]
    public function a_fulfilled_payload_whose_booking_was_deleted_is_not_resurrected(): void
    {
        $payload = \App\Models\StripeCheckoutPayload::create([
            'stripe_session_id' => 'cs_queued_4',
            'payload' => null,
            'fulfilment_status' => 'fulfilled',
            'booking_id' => 999999, // deleted booking
        ]);

        // Service must never be asked to create a booking.
        $service = Mockery::mock(StripeBookingService::class);
        $service->shouldNotReceive('createBookingFromSession');

        (new ProcessPaidCheckoutSessionJob('cs_queued_4'))->handle($service);

        $payload->refresh();
        $this->assertSame('manual_review', $payload->fulfilment_status);
        $this->assertStringContainsString('deleted after fulfilment', $payload->last_error);
    }

    #[Test]
    public function a_direct_service_call_refuses_to_recreate_a_deleted_booking(): void
    {
        // The success page and mobile bySession call the service directly,
        // bypassing the job — the guard must live in the service itself.
        \App\Models\StripeCheckoutPayload::create([
            'stripe_session_id' => 'cs_queued_6',
            'payload' => null,
            'fulfilment_status' => 'manual_review',
            'booking_id' => 999999,
        ]);

        $booking = app(StripeBookingService::class)
            ->createBookingFromSession((object) ['id' => 'cs_queued_6']);

        $this->assertNull($booking);
        $this->assertSame(0, \App\Models\Booking::count());
    }

    #[Test]
    public function a_paid_session_without_booking_metadata_never_becomes_a_booking(): void
    {
        // Other products (eSIM shop) share the Stripe account; their paid
        // sessions carry none of our booking metadata keys.
        $booking = app(StripeBookingService::class)->createBookingFromSession((object) [
            'id' => 'cs_foreign_1',
            'metadata' => (object) [],
        ]);

        $this->assertNull($booking);
        $this->assertSame(0, \App\Models\Booking::count());
        $this->assertSame('ignored', \App\Models\StripeCheckoutPayload::where('stripe_session_id', 'cs_foreign_1')
            ->value('fulfilment_status'));
    }

    #[Test]
    public function exhausted_fulfilment_retries_raise_the_orphaned_payment_alert(): void
    {
        Notification::fake();
        $admin = User::create([
            'first_name' => 'Site', 'last_name' => 'Admin',
            'email' => config('admin.email'), 'phone' => '+27821110000',
            'password' => bcrypt('password'), 'role' => 'admin', 'status' => 'active',
        ]);

        (new ProcessPaidCheckoutSessionJob('cs_queued_3'))
            ->failed(new \RuntimeException('everything is on fire'));

        Notification::assertSentTo($admin, AdminManualRefundRequiredNotification::class);
    }
}
