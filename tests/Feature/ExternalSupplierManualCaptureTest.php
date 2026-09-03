<?php

namespace Tests\Feature;

use App\Exceptions\ReservationOutcomeUnknownException;
use App\Exceptions\StripePaymentAlreadyCapturedException;
use App\Exceptions\SupplierReservationRejectedException;
use App\Http\Controllers\StripeCheckoutController;
use App\Http\Controllers\StripeWebhookController;
use App\Jobs\NotifyDelayedSupplierConfirmationJob;
use App\Jobs\ProcessPaidCheckoutSessionJob;
use App\Jobs\SendAwinConversion;
use App\Jobs\TriggerProviderReservationJob;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\StripeCheckoutPayload;
use App\Models\User;
use App\Notifications\Booking\BookingCreatedAdminNotification;
use App\Notifications\Booking\BookingPaymentReceivedCustomerNotification;
use App\Notifications\Booking\BookingSupplierConfirmedCustomerNotification;
use App\Notifications\Booking\GuestSupplierBookingAccessNotification;
use App\Notifications\Booking\PaymentCaptureReviewCustomerNotification;
use App\Notifications\Booking\ReservationFailedCustomerNotification;
use App\Notifications\Payment\AdminAuthorizationReviewNotification;
use App\Notifications\Payment\AdminReservationFailedNotification;
use App\Notifications\Payment\AdminReservationManualCheckNotification;
use App\Services\ProviderBookingCancellationService;
use App\Services\ProviderQuoteRevalidationService;
use App\Services\StripeBookingService;
use App\Services\StripePaymentLifecycleService;
use App\Services\VrooemGatewayService;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class ExternalSupplierManualCaptureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Notification::fake();
        config([
            'awin.enabled' => false,
            'currency.base_currency' => 'EUR',
            'currency.default' => 'EUR',
        ]);
    }

    protected function tearDown(): void
    {
        try {
            Mockery::close();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function an_authorized_supplier_checkout_is_pending_and_records_no_captured_money(): void
    {
        $booking = app(StripeBookingService::class)->createBookingFromSession($this->authorizedSession());

        $this->assertSame('supplier_pending', $booking->booking_status);
        $this->assertSame('authorized', $booking->payment_status);
        $this->assertEqualsWithDelta(0, (float) $booking->amount_paid, 0.01);
        $this->assertEqualsWithDelta(100, (float) $booking->pending_amount, 0.01);
        $this->assertNull($booking->provider_booking_ref);

        $payment = BookingPayment::where('booking_id', $booking->id)->sole();
        $this->assertSame('authorized', $payment->payment_status);
        $this->assertEqualsWithDelta(15, (float) $payment->amount, 0.01);

        Queue::assertPushed(TriggerProviderReservationJob::class, fn ($job) => $job->bookingId === $booking->id);
        Queue::assertPushed(NotifyDelayedSupplierConfirmationJob::class, fn ($job) => $job->bookingId === $booking->id);
    }

    #[Test]
    public function a_pending_supplier_checkout_defers_routine_booking_notifications_for_five_minutes(): void
    {
        $admin = User::factory()->create([
            'email' => config('admin.email'),
            'role' => 'admin',
        ]);

        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_notification_grace')
        );
        $booking->load('customer.user');

        $this->assertNotNull($booking->supplier_confirmation_deadline_at);
        $this->assertSame(
            $booking->created_at->copy()->addMinutes(5)->format('Y-m-d H:i'),
            $booking->supplier_confirmation_deadline_at->format('Y-m-d H:i')
        );
        Notification::assertNotSentTo($admin, BookingCreatedAdminNotification::class);
        Notification::assertNotSentTo(
            $booking->customer->user,
            BookingPaymentReceivedCustomerNotification::class
        );
        Notification::assertSentTo(
            $booking->customer->user,
            GuestSupplierBookingAccessNotification::class
        );
    }

    #[Test]
    public function delayed_pending_notification_is_sent_once_only_after_the_deadline(): void
    {
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_delayed_notification')
        );
        $booking->update(['supplier_confirmation_deadline_at' => now()->subSecond()]);
        $booking->load('customer.user');
        Notification::fake();

        $job = new NotifyDelayedSupplierConfirmationJob($booking->id);
        $job->handle();
        $job->handle();

        Notification::assertSentToTimes(
            $booking->customer->user,
            BookingPaymentReceivedCustomerNotification::class,
            1
        );
        $this->assertNotNull($booking->fresh()->supplier_pending_notified_at);
    }

    #[Test]
    public function delayed_pending_notification_does_nothing_after_confirmation(): void
    {
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_delayed_notification_skip')
        );
        $booking->update([
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
            'provider_booking_ref' => 'EMR-ALREADY-CONFIRMED',
            'supplier_confirmation_deadline_at' => now()->subSecond(),
        ]);
        $booking->load('customer.user');
        Notification::fake();

        (new NotifyDelayedSupplierConfirmationJob($booking->id))->handle();

        Notification::assertNotSentTo(
            $booking->customer->user,
            BookingPaymentReceivedCustomerNotification::class
        );
        $this->assertNull($booking->fresh()->supplier_pending_notified_at);
    }

    #[Test]
    public function an_authorized_checkout_webhook_queues_fulfilment_even_though_checkout_is_unpaid(): void
    {
        $bookingService = Mockery::mock(StripeBookingService::class);
        $bookingService->shouldReceive('isManualSupplierCaptureMetadata')->once()->andReturnTrue();
        $controller = new class($bookingService) extends StripeWebhookController
        {
            public ?int $waitBudget = null;

            protected function waitForSupplierResolution(string $sessionId, ?int $maxWaitMilliseconds = null): bool
            {
                $this->waitBudget = $maxWaitMilliseconds;

                return false;
            }
        };
        $method = new ReflectionMethod($controller, 'handleCheckoutComplete');
        $method->invoke($controller, (object) [
            'id' => 'cs_supplier_authorized_webhook',
            'payment_status' => 'unpaid',
            'payment_intent' => 'pi_supplier_authorized_webhook',
            'metadata' => (object) [
                'capture_policy' => 'manual_supplier',
                'vehicle_source' => 'emr',
            ],
        ]);

        Queue::assertPushed(ProcessPaidCheckoutSessionJob::class,
            fn ($job) => $job->sessionId === 'cs_supplier_authorized_webhook');
        $this->assertIsInt($controller->waitBudget);
        $this->assertGreaterThanOrEqual(0, $controller->waitBudget);
        $this->assertLessThanOrEqual(8000, $controller->waitBudget);
    }

    #[Test]
    public function supplier_checkout_configuration_is_card_only_and_manual_capture(): void
    {
        $controller = new StripeCheckoutController(app(StripeBookingService::class));
        $method = new ReflectionMethod($controller, 'stripePaymentConfiguration');
        $method->setAccessible(true);

        $external = $method->invoke($controller, 'emr', 'klarna', 'EUR');
        $internal = $method->invoke($controller, 'internal', 'klarna', 'EUR');

        $this->assertSame(['card'], $external['payment_method_types']);
        $this->assertSame('manual', $external['payment_intent_data']['capture_method']);
        $this->assertSame('card', $external['recorded_payment_method']);
        $this->assertSame(['klarna'], $internal['payment_method_types']);
        $this->assertArrayNotHasKey('capture_method', $internal['payment_intent_data']);
        $this->assertSame('klarna', $internal['recorded_payment_method']);
    }

    #[Test]
    public function a_manual_supplier_success_redirect_race_shows_authorized_pending_and_queues_fulfilment(): void
    {
        $session = $this->authorizedSession('cs_supplier_redirect_race');
        $stripeSession = Mockery::mock('alias:Stripe\\Checkout\\Session');
        $stripeSession->shouldReceive('retrieve')->once()->with($session->id)->andReturn($session);
        $payments = Mockery::mock(StripePaymentLifecycleService::class);
        $payments->shouldReceive('isAuthorized')->once()->with('pi_cs_supplier_redirect_race')->andReturnTrue();
        $this->app->instance(StripePaymentLifecycleService::class, $payments);

        $response = (new StripeCheckoutController(app(StripeBookingService::class)))->success(
            Request::create('/booking/success', 'GET', ['session_id' => $session->id]),
            app(StripeBookingService::class)
        );

        parse_str((string) parse_url($response->getTargetUrl(), PHP_URL_QUERY), $query);
        $this->assertSame('card_authorized_supplier_confirmation', $query['state'] ?? null);
        Queue::assertPushed(ProcessPaidCheckoutSessionJob::class,
            fn ($job) => $job->sessionId === $session->id);
        $this->assertDatabaseMissing('bookings', ['stripe_session_id' => $session->id]);
    }

    #[Test]
    public function an_authorized_amount_mismatch_stops_before_supplier_dispatch(): void
    {
        $session = $this->authorizedSession('cs_supplier_amount_mismatch');
        $session->amount_total = 1600;

        $threw = false;
        try {
            app(StripeBookingService::class)->createBookingFromSession($session);
        } catch (\RuntimeException) {
            $threw = true;
        }

        $this->assertTrue($threw);
        $this->assertDatabaseMissing('bookings', ['stripe_session_id' => $session->id]);
        Queue::assertNotPushed(TriggerProviderReservationJob::class);
    }

    #[Test]
    public function an_authorized_currency_mismatch_stops_before_supplier_dispatch(): void
    {
        $session = $this->authorizedSession('cs_supplier_currency_mismatch');
        $session->currency = 'usd';

        $this->expectException(\RuntimeException::class);

        try {
            app(StripeBookingService::class)->createBookingFromSession($session);
        } finally {
            $this->assertDatabaseMissing('bookings', ['stripe_session_id' => $session->id]);
            Queue::assertNotPushed(TriggerProviderReservationJob::class);
        }
    }

    #[Test]
    public function a_supplier_reference_is_persisted_before_capture_and_only_then_confirms_payment(): void
    {
        $booking = app(StripeBookingService::class)->createBookingFromSession($this->authorizedSession('cs_supplier_finalize'));
        $booking->update(['provider_booking_ref' => 'EMR-778899']);

        $capture = new class
        {
            public array $calls = [];

            public function captureAuthorized(string $paymentIntentId, string $idempotencyKey): void
            {
                $this->calls[] = [$paymentIntentId, $idempotencyKey];
            }
        };

        app(StripeBookingService::class)->finalizeAuthorizedSupplierBooking(
            $booking,
            (object) $this->metadata(),
            $capture
        );

        $booking->refresh()->load('payments');
        $this->assertSame([['pi_cs_supplier_finalize', 'capture_booking_'.$booking->id]], $capture->calls);
        $this->assertSame('confirmed', $booking->booking_status);
        $this->assertSame('partial', $booking->payment_status);
        $this->assertEqualsWithDelta(15, (float) $booking->amount_paid, 0.01);
        $this->assertSame('succeeded', $booking->payments->sole()->payment_status);
    }

    #[Test]
    public function supplier_confirmation_is_sent_once_and_only_after_capture(): void
    {
        $admin = User::factory()->create([
            'email' => config('admin.email'),
            'role' => 'admin',
        ]);
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_notification_order')
        );
        $gateway = Mockery::mock(VrooemGatewayService::class);
        $gateway->shouldReceive('createBooking')->once()->andReturn([
            'status' => 'confirmed',
            'supplier_booking_id' => 'EMR-NOTIFY-AFTER-CAPTURE',
            'gateway_booking_id' => 'gateway-booking-notify',
            'supplier_id' => 'emr',
        ]);
        $this->app->instance(VrooemGatewayService::class, $gateway);

        app(StripeBookingService::class)->triggerGatewayReservation(
            $booking,
            (object) $this->metadata()
        );

        Notification::assertNotSentTo(
            $booking->customer->user,
            BookingSupplierConfirmedCustomerNotification::class
        );

        $capture = new class
        {
            public function captureAuthorized(string $paymentIntentId, string $idempotencyKey): void {}
        };
        app(StripeBookingService::class)->finalizeAuthorizedSupplierBooking(
            $booking->fresh(),
            (object) $this->metadata(),
            $capture
        );

        Notification::assertSentToTimes(
            $booking->customer->user,
            BookingSupplierConfirmedCustomerNotification::class,
            1
        );
        Notification::assertSentToTimes($admin, BookingCreatedAdminNotification::class, 1);
        $this->assertNotNull($booking->fresh()->supplier_confirmed_notified_at);
    }

    #[Test]
    public function the_fulfilment_job_accepts_a_real_card_authorization_and_leaves_supplier_work_pending(): void
    {
        $session = $this->authorizedSession('cs_supplier_job');
        $stripeSession = Mockery::mock('alias:Stripe\\Checkout\\Session');
        $stripeSession->shouldReceive('retrieve')->once()->with($session->id)->andReturn($session);
        $payments = new class
        {
            public function isAuthorized(string $paymentIntentId): bool
            {
                return $paymentIntentId === 'pi_cs_supplier_job';
            }
        };

        (new ProcessPaidCheckoutSessionJob($session->id))->handle(
            app(StripeBookingService::class),
            $payments
        );

        $booking = Booking::where('stripe_session_id', $session->id)->sole();
        $payload = StripeCheckoutPayload::where('stripe_session_id', $session->id)->sole();
        $this->assertSame('authorized', $booking->payment_status);
        $this->assertSame('supplier_pending', $payload->fulfilment_status);
        $this->assertSame($booking->id, $payload->booking_id);
    }

    #[Test]
    public function a_job_retry_with_an_existing_supplier_reference_resumes_capture_without_reserving_again(): void
    {
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_capture_resume')
        );
        $booking->update(['provider_booking_ref' => 'EMR-ALREADY-RESERVED']);
        $payments = new class {};

        $service = Mockery::mock(StripeBookingService::class);
        $service->shouldNotReceive('triggerGatewayReservation');
        $service->shouldReceive('finalizeAuthorizedSupplierBooking')
            ->once()
            ->withArgs(fn (Booking $actual, object $metadata, $actualPayments) => $actual->id === $booking->id
                && $metadata->gateway_vehicle_id === 'gateway-emr-vehicle-1'
                && $actualPayments === $payments);

        (new TriggerProviderReservationJob($booking->id, $this->metadata()))->handle($service, $payments);

        $this->assertSame('EMR-ALREADY-RESERVED', $booking->fresh()->provider_booking_ref);
    }

    #[Test]
    public function a_definite_supplier_failure_releases_authorization_without_creating_a_refund(): void
    {
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_release')
        );
        $payments = Mockery::mock(StripePaymentLifecycleService::class);
        $payments->shouldReceive('releaseAuthorization')
            ->once()
            ->with('pi_cs_supplier_release', 'release_booking_'.$booking->id);
        $this->app->instance(StripePaymentLifecycleService::class, $payments);

        (new TriggerProviderReservationJob($booking->id, $this->metadata()))
            ->failed(new \RuntimeException('supplier rejected after retries'));

        $booking->refresh()->load('payments');
        $this->assertSame('reservation_failed', $booking->booking_status);
        $this->assertSame('payment_cancelled', $booking->payment_status);
        $this->assertEqualsWithDelta(0, (float) $booking->amount_paid, 0.01);
        $this->assertArrayNotHasKey('manual_refund_required', $booking->provider_metadata ?? []);
        $this->assertSame('authorization_released', $booking->payments->sole()->payment_status);
        $this->assertEqualsWithDelta(0, (float) $booking->pending_amount, 0.01);
        $this->assertEqualsWithDelta(0, (float) $booking->amounts->admin_pending_amount, 0.01);
    }

    #[Test]
    public function an_expired_confirmation_deadline_stops_before_another_supplier_attempt(): void
    {
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_deadline_expired')
        );
        $booking->update(['supplier_confirmation_deadline_at' => now()->subSecond()]);

        $service = Mockery::mock(StripeBookingService::class);
        $service->shouldNotReceive('refreshAuthorizedSupplierMetadata');
        $service->shouldNotReceive('triggerGatewayReservation');
        $payments = Mockery::mock(StripePaymentLifecycleService::class);
        $payments->shouldReceive('releaseAuthorization')
            ->once()
            ->with('pi_cs_supplier_deadline_expired', 'release_booking_'.$booking->id);
        $this->app->instance(StripePaymentLifecycleService::class, $payments);

        (new TriggerProviderReservationJob($booking->id, $this->metadata()))->handle($service, $payments);

        $booking->refresh();
        $this->assertSame('reservation_failed', $booking->booking_status);
        $this->assertSame('payment_cancelled', $booking->payment_status);
        $this->assertNull($booking->provider_booking_ref);
    }

    #[Test]
    public function an_explicit_supplier_rejection_is_classified_as_non_retryable(): void
    {
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_explicit_rejection')
        );
        $gateway = Mockery::mock(VrooemGatewayService::class);
        $gateway->shouldReceive('createBooking')->once()->andReturn([
            'status' => 'rejected',
            'provider_status' => 'rejected',
            'failure_reason' => 'Vehicle sold out',
        ]);
        $gateway->shouldReceive('getLastError')->once()->andReturn(null);
        $this->app->instance(VrooemGatewayService::class, $gateway);

        $this->expectException(SupplierReservationRejectedException::class);

        app(StripeBookingService::class)->triggerGatewayReservation(
            $booking,
            (object) $this->metadata()
        );
    }

    #[Test]
    public function a_generic_gateway_failure_is_treated_as_unknown_instead_of_releasing_the_card(): void
    {
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_generic_gateway_failure')
        );
        $gateway = Mockery::mock(VrooemGatewayService::class);
        $gateway->shouldReceive('createBooking')->once()->andReturn([
            'status' => 'failed',
            'provider_status' => 'failed',
            'failure_reason' => 'Unexpected adapter exception',
            'supplier_data' => [
                'exception_type' => 'ValueError',
                'outcome_unknown' => false,
            ],
        ]);
        $gateway->shouldReceive('getLastError')->once()->andReturn(null);
        $this->app->instance(VrooemGatewayService::class, $gateway);

        $this->expectException(ReservationOutcomeUnknownException::class);

        try {
            app(StripeBookingService::class)->triggerGatewayReservation(
                $booking,
                (object) $this->metadata()
            );
        } finally {
            $booking->refresh();
            $this->assertSame('authorized', $booking->payment_status);
            $this->assertTrue((bool) ($booking->provider_metadata['reservation_manual_check'] ?? false));
        }
    }

    #[Test]
    public function legacy_paid_supplier_success_notifies_admin_after_the_reference_is_saved(): void
    {
        $admin = User::factory()->create([
            'email' => config('admin.email'),
            'role' => 'admin',
        ]);
        $session = $this->authorizedSession('cs_legacy_paid_supplier_success');
        unset($session->metadata->capture_policy);
        $booking = app(StripeBookingService::class)->createBookingFromSession($session);
        Notification::fake();

        $gateway = Mockery::mock(VrooemGatewayService::class);
        $gateway->shouldReceive('createBooking')->once()->andReturn([
            'status' => 'confirmed',
            'supplier_booking_id' => 'LEGACY-SUPPLIER-REF',
            'gateway_booking_id' => 'legacy-gateway-id',
            'supplier_id' => 'emr',
        ]);
        $this->app->instance(VrooemGatewayService::class, $gateway);

        app(StripeBookingService::class)->triggerGatewayReservation($booking, (object) $this->metadata());

        Notification::assertSentTo($admin, BookingCreatedAdminNotification::class);
    }

    #[Test]
    public function guest_supplier_access_notification_encrypts_the_queued_password(): void
    {
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_guest_access_encryption')
        );

        $notification = new GuestSupplierBookingAccessNotification(
            $booking,
            $booking->customer,
            'temporary-secret'
        );

        $this->assertInstanceOf(ShouldBeEncrypted::class, $notification);
    }

    #[Test]
    public function timeout_and_conflict_responses_are_unknown_but_rate_limits_remain_retryable(): void
    {
        $service = app(StripeBookingService::class);
        $unknown = new ReflectionMethod($service, 'isUnknownReservationOutcome');
        $unknown->setAccessible(true);
        $rejected = new ReflectionMethod($service, 'isDefiniteReservationRejection');
        $rejected->setAccessible(true);

        foreach ([408, 409] as $status) {
            $error = ['type' => 'http', 'method' => 'POST', 'status' => $status];
            $this->assertTrue($unknown->invoke($service, null, $error));
            $this->assertFalse($rejected->invoke($service, null, $error));
        }

        $rateLimit = ['type' => 'http', 'method' => 'POST', 'status' => 429];
        $this->assertFalse($unknown->invoke($service, null, $rateLimit));
        $this->assertFalse($rejected->invoke($service, null, $rateLimit));
    }

    #[Test]
    public function authorization_time_quote_refresh_replaces_stale_gateway_ids_and_context(): void
    {
        StripeCheckoutPayload::create([
            'stripe_session_id' => 'cs_supplier_quote_refresh',
            'fulfilment_status' => 'pending',
            'payload' => [],
        ]);
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_quote_refresh')
        );
        $payload = StripeCheckoutPayload::where('stripe_session_id', $booking->stripe_session_id)->firstOrFail();
        $payload->update(['payload' => [
            'supplier_revalidation' => [
                'validated' => ['vehicle' => ['source' => 'emr', 'id' => 'old-vehicle']],
                'verified_prices' => ['provider' => 'emr', 'original_total' => 100, 'currency' => 'EUR'],
                'search_session_id' => 'original-search-session',
            ],
        ]]);

        $revalidator = Mockery::mock(ProviderQuoteRevalidationService::class);
        $revalidator->shouldReceive('revalidate')->once()->andReturn([
            'valid' => true,
            'vehicle' => ['id' => 'fresh-gateway-vehicle', 'gateway_vehicle_id' => 'fresh-gateway-vehicle'],
            'verified_prices' => ['provider' => 'emr', 'original_total' => 100, 'currency' => 'EUR'],
            'gateway_search_id' => 'fresh-gateway-search',
            'extras' => [],
            'gateway_vehicle_context' => [
                'id' => 'fresh-gateway-vehicle',
                'search_id' => 'fresh-gateway-search',
                'context_valid_until' => now()->addMinutes(15)->toIso8601String(),
            ],
        ]);
        $this->app->instance(ProviderQuoteRevalidationService::class, $revalidator);

        $fresh = app(StripeBookingService::class)->refreshAuthorizedSupplierMetadata(
            $booking,
            $this->metadata()
        );

        $this->assertSame('fresh-gateway-vehicle', $fresh['gateway_vehicle_id']);
        $this->assertSame('fresh-gateway-search', $fresh['gateway_search_id']);
        $this->assertSame('fresh-gateway-search', $fresh['gateway_vehicle_context']['search_id']);
    }

    #[Test]
    public function conversion_tracking_waits_until_supplier_reference_and_capture_are_both_complete(): void
    {
        config(['awin.enabled' => true]);
        $metadata = array_merge($this->metadata(), ['awc' => 'trusted-awin-click']);
        $session = $this->authorizedSession('cs_supplier_conversion');
        $session->metadata = (object) $metadata;
        $booking = app(StripeBookingService::class)->createBookingFromSession($session);

        Queue::assertNotPushed(SendAwinConversion::class);
        $booking->update(['provider_booking_ref' => 'EMR-CONVERSION-1']);
        $capture = new class
        {
            public function captureAuthorized(string $paymentIntentId, string $idempotencyKey): void {}
        };

        app(StripeBookingService::class)->finalizeAuthorizedSupplierBooking(
            $booking,
            (object) $metadata,
            $capture
        );

        Queue::assertPushed(SendAwinConversion::class, fn ($job) => $job->bookingId === $booking->id);
    }

    #[Test]
    public function the_pending_customer_message_says_card_authorized_not_payment_received(): void
    {
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_authorization_message')
        );
        $booking->load('customer.user');
        $notification = new BookingPaymentReceivedCustomerNotification(
            $booking,
            $booking->customer,
            null
        );

        $mail = $notification->toMail($booking->customer->user);

        $this->assertStringContainsString('Card authorized', (string) $mail->subject);
        $this->assertStringNotContainsString(
            'we have received your payment',
            implode(' ', $mail->introLines)
        );
    }

    #[Test]
    public function an_authorized_supplier_booking_uses_the_card_authorized_status_page(): void
    {
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_status_page')
        );
        $controller = new StripeCheckoutController(app(StripeBookingService::class));
        $method = new ReflectionMethod($controller, 'resolveBookingOutcomeState');
        $method->setAccessible(true);

        $state = $method->invoke($controller, $booking, 'support_review');

        $this->assertSame('card_authorized_supplier_confirmation', $state);
    }

    #[Test]
    public function an_unknown_supplier_outcome_redirects_to_review_instead_of_continuing_the_spinner(): void
    {
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_unknown_redirect')
        );
        $booking->update([
            'provider_metadata' => array_merge($booking->provider_metadata ?? [], [
                'reservation_manual_check' => true,
            ]),
        ]);

        $response = (new StripeCheckoutController(app(StripeBookingService::class)))->success(
            Request::create('/booking/success', 'GET', ['session_id' => $booking->stripe_session_id]),
            app(StripeBookingService::class)
        );

        parse_str((string) parse_url($response->getTargetUrl(), PHP_URL_QUERY), $query);
        $this->assertSame('authorization_review', $query['state'] ?? null);
    }

    #[Test]
    public function a_saved_supplier_reference_with_uncertain_capture_redirects_to_payment_review(): void
    {
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_capture_review_redirect')
        );
        $booking->update([
            'provider_booking_ref' => 'EMR-REVIEW-REDIRECT',
            'provider_metadata' => array_merge($booking->provider_metadata ?? [], [
                'payment_capture_manual_check' => true,
            ]),
        ]);

        $response = (new StripeCheckoutController(app(StripeBookingService::class)))->success(
            Request::create('/booking/success', 'GET', ['session_id' => $booking->stripe_session_id]),
            app(StripeBookingService::class)
        );

        parse_str((string) parse_url($response->getTargetUrl(), PHP_URL_QUERY), $query);
        $this->assertSame('supplier_confirmed_payment_review', $query['state'] ?? null);
    }

    #[Test]
    public function supplier_status_page_exposes_the_five_minute_confirmation_deadline(): void
    {
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_status_deadline')
        );

        $this->get('/en/booking/status?state=card_authorized_supplier_confirmation&session_id='.$booking->stripe_session_id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Booking/Status')
                ->where('state', 'card_authorized_supplier_confirmation')
                ->where('confirmation_deadline_at', $booking->supplier_confirmation_deadline_at->toIso8601String())
            );
    }

    #[Test]
    public function supplier_status_page_has_a_deadline_even_before_the_booking_row_exists(): void
    {
        $payload = StripeCheckoutPayload::create([
            'stripe_session_id' => 'cs_supplier_payload_only_deadline',
            'payment_status' => 'authorized',
            'fulfilment_status' => 'pending',
        ]);

        $this->get('/en/booking/status?state=card_authorized_supplier_confirmation&session_id='.$payload->stripe_session_id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Booking/Status')
                ->where('confirmation_deadline_at', $payload->created_at->copy()->addMinutes(5)->toIso8601String())
            );
    }

    #[Test]
    public function cancelling_a_pending_supplier_booking_releases_the_card_authorization(): void
    {
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_customer_cancel')
        );
        $payments = Mockery::mock(StripePaymentLifecycleService::class);
        $payments->shouldReceive('releaseAuthorization')
            ->once()
            ->with('pi_cs_supplier_customer_cancel', 'release_booking_'.$booking->id);
        $this->app->instance(StripePaymentLifecycleService::class, $payments);

        $result = app(ProviderBookingCancellationService::class)->cancel(
            $booking->id,
            'Customer changed plans.',
            'Customer'
        );

        $this->assertTrue($result['success']);
        $booking->refresh()->load('payments');
        $this->assertSame('cancelled', $booking->booking_status);
        $this->assertSame('payment_cancelled', $booking->payment_status);
        $this->assertSame('authorization_released', $booking->payments->sole()->payment_status);
        $this->assertEqualsWithDelta(0, (float) $booking->pending_amount, 0.01);
        $this->assertEqualsWithDelta(0, (float) $booking->amounts->booking_pending_amount, 0.01);
    }

    #[Test]
    public function cancelling_an_authorized_reserved_booking_cancels_the_supplier_before_releasing_the_card(): void
    {
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_reserved_cancel')
        );
        $booking->update([
            'provider_booking_ref' => 'EMR-CANCEL-FIRST',
            'provider_metadata' => [
                'gateway_booking_id' => 'gateway-booking-1',
                'gateway_supplier_id' => 'emr',
            ],
        ]);

        $operations = [];
        $gateway = Mockery::mock(VrooemGatewayService::class);
        $gateway->shouldReceive('cancelBooking')
            ->once()
            ->andReturnUsing(function () use (&$operations): array {
                $operations[] = 'supplier_cancelled';

                return ['status' => 'cancelled'];
            });
        $this->app->instance(VrooemGatewayService::class, $gateway);

        $payments = Mockery::mock(StripePaymentLifecycleService::class);
        $payments->shouldReceive('releaseAuthorization')
            ->once()
            ->andReturnUsing(function () use (&$operations): void {
                $operations[] = 'authorization_released';
            });
        $this->app->instance(StripePaymentLifecycleService::class, $payments);

        $result = app(ProviderBookingCancellationService::class)->cancel(
            $booking->id,
            'Customer changed plans.',
            'Customer'
        );

        $this->assertTrue($result['success']);
        $this->assertSame(['supplier_cancelled', 'authorization_released'], $operations);
        $this->assertSame('cancelled', $booking->fresh()->booking_status);
    }

    #[Test]
    public function a_cancelled_supplier_booking_can_retry_a_transient_authorization_release_failure(): void
    {
        $admin = User::factory()->create([
            'email' => config('admin.email'),
            'role' => 'admin',
        ]);
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_cancel_release_retry')
        );
        $booking->update([
            'provider_booking_ref' => 'EMR-RELEASE-RETRY',
            'provider_metadata' => [
                'gateway_booking_id' => 'gateway-release-retry',
                'gateway_supplier_id' => 'emr',
            ],
        ]);

        $gateway = Mockery::mock(VrooemGatewayService::class);
        $gateway->shouldReceive('cancelBooking')->once()->andReturn(['status' => 'cancelled']);
        $this->app->instance(VrooemGatewayService::class, $gateway);

        $releaseAttempts = 0;
        $payments = Mockery::mock(StripePaymentLifecycleService::class);
        $payments->shouldReceive('releaseAuthorization')
            ->twice()
            ->andReturnUsing(function () use (&$releaseAttempts): void {
                $releaseAttempts++;
                if ($releaseAttempts === 1) {
                    throw new \RuntimeException('Stripe temporarily unavailable');
                }
            });
        $this->app->instance(StripePaymentLifecycleService::class, $payments);

        $first = app(ProviderBookingCancellationService::class)->cancel(
            $booking->id,
            'Customer changed plans.',
            'Customer'
        );
        $this->assertFalse($first['success']);
        $this->assertSame('authorization_release_failed', $first['code']);
        $this->assertSame('cancelled', $booking->fresh()->booking_status);
        $this->assertSame('authorized', $booking->fresh()->payment_status);
        Notification::assertSentTo($admin, AdminAuthorizationReviewNotification::class);

        $controller = new StripeCheckoutController(app(StripeBookingService::class));
        $method = new ReflectionMethod($controller, 'resolveBookingOutcomeState');
        $method->setAccessible(true);
        $this->assertSame('authorization_review', $method->invoke(
            $controller,
            $booking->fresh(),
            'payment_cancelled'
        ));

        $second = app(ProviderBookingCancellationService::class)->cancel(
            $booking->id,
            'Customer changed plans.',
            'Automated recovery'
        );

        $this->assertTrue($second['success']);
        $booking->refresh();
        $this->assertSame('cancelled', $booking->booking_status);
        $this->assertSame('payment_cancelled', $booking->payment_status);
        $this->assertArrayNotHasKey('authorization_release_error', $booking->provider_metadata ?? []);
    }

    #[Test]
    public function an_unknown_supplier_outcome_keeps_the_card_authorization_for_manual_reconciliation(): void
    {
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_unknown_cancel')
        );
        $booking->update([
            'provider_metadata' => array_merge($booking->provider_metadata ?? [], [
                'reservation_manual_check' => true,
                'reservation_unknown_at' => now()->toIso8601String(),
            ]),
        ]);

        $payments = Mockery::mock(StripePaymentLifecycleService::class);
        $payments->shouldNotReceive('releaseAuthorization');
        $this->app->instance(StripePaymentLifecycleService::class, $payments);

        $result = app(ProviderBookingCancellationService::class)->cancel(
            $booking->id,
            'Customer changed plans.',
            'Customer'
        );

        $this->assertFalse($result['success']);
        $this->assertSame('supplier_outcome_unknown', $result['code']);
        $this->assertSame('authorized', $booking->fresh()->payment_status);
    }

    #[Test]
    public function an_admin_can_close_an_unknown_outcome_after_auditing_the_supplier_portal(): void
    {
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_unknown_checked')
        );
        $booking->update([
            'provider_metadata' => array_merge($booking->provider_metadata ?? [], [
                'reservation_manual_check' => true,
                'reservation_unknown_at' => now()->toIso8601String(),
            ]),
        ]);
        $payments = Mockery::mock(StripePaymentLifecycleService::class);
        $payments->shouldReceive('releaseAuthorization')->once();
        $this->app->instance(StripePaymentLifecycleService::class, $payments);

        $result = app(ProviderBookingCancellationService::class)->cancel(
            $booking->id,
            'Supplier portal verified empty.',
            'Admin #42',
            true
        );

        $this->assertTrue($result['success']);
        $booking->refresh();
        $this->assertSame('cancelled', $booking->booking_status);
        $this->assertSame('payment_cancelled', $booking->payment_status);
        $this->assertSame('no_reservation_found', $booking->provider_metadata['supplier_portal_check_result']);
        $this->assertSame('Admin #42', $booking->provider_metadata['supplier_portal_checked_by']);
    }

    #[Test]
    public function a_capture_discovered_during_cancellation_is_recorded_for_manual_refund(): void
    {
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_cancel_discovered_capture')
        );
        $booking->update([
            'provider_booking_ref' => 'EMR-CAPTURED-CANCEL',
            'provider_metadata' => [
                'gateway_booking_id' => 'gateway-booking-captured',
                'gateway_supplier_id' => 'emr',
            ],
        ]);
        $gateway = Mockery::mock(VrooemGatewayService::class);
        $gateway->shouldReceive('cancelBooking')->once()->andReturn(['status' => 'cancelled']);
        $this->app->instance(VrooemGatewayService::class, $gateway);
        $payments = Mockery::mock(StripePaymentLifecycleService::class);
        $payments->shouldReceive('releaseAuthorization')
            ->once()
            ->andThrow(new StripePaymentAlreadyCapturedException('Stripe payment was already captured.'));
        $this->app->instance(StripePaymentLifecycleService::class, $payments);

        $result = app(ProviderBookingCancellationService::class)->cancel(
            $booking->id,
            'Customer changed plans.',
            'Customer'
        );

        $this->assertTrue($result['success']);
        $booking->refresh()->load(['payments', 'amounts']);
        $this->assertSame('cancelled', $booking->booking_status);
        $this->assertSame('refund_pending', $booking->payment_status);
        $this->assertEqualsWithDelta(15, (float) $booking->amount_paid, 0.01);
        $this->assertEqualsWithDelta(0, (float) $booking->pending_amount, 0.01);
        $this->assertTrue((bool) ($booking->provider_metadata['manual_refund_required'] ?? false));
        $this->assertSame('succeeded', $booking->payments->sole()->payment_status);
        $this->assertEqualsWithDelta(0, (float) $booking->amounts->admin_pending_amount, 0.01);
    }

    #[Test]
    public function a_failed_authorization_release_uses_manual_check_messaging(): void
    {
        $admin = User::factory()->create([
            'email' => config('admin.email'),
            'role' => 'admin',
        ]);
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_release_review')
        );
        $payments = Mockery::mock(StripePaymentLifecycleService::class);
        $payments->shouldReceive('releaseAuthorization')->once()->andThrow(new \RuntimeException('Stripe unavailable'));
        $this->app->instance(StripePaymentLifecycleService::class, $payments);

        (new TriggerProviderReservationJob($booking->id, $this->metadata()))
            ->failed(new \RuntimeException('supplier rejected after retries'));

        $booking->refresh();
        $this->assertSame('authorized', $booking->payment_status);
        $this->assertTrue((bool) ($booking->provider_metadata['reservation_manual_check'] ?? false));
        Notification::assertSentTo($admin, AdminReservationManualCheckNotification::class);
        Notification::assertNotSentTo($admin, AdminReservationFailedNotification::class);
        Notification::assertSentTo(
            $booking->customer->user,
            ReservationFailedCustomerNotification::class,
            function (ReservationFailedCustomerNotification $notification, array $channels) use ($booking): bool {
                $mail = $notification->toMail($booking->customer->user);
                $copy = implode(' ', $mail->introLines);

                return str_contains($copy, 'not been charged')
                    && ! str_contains($copy, 'Amount Paid');
            }
        );
    }

    #[Test]
    public function an_orphaned_authorization_is_released_when_fulfilment_exhausts_retries(): void
    {
        $admin = User::factory()->create([
            'email' => config('admin.email'),
            'role' => 'admin',
        ]);
        StripeCheckoutPayload::create([
            'stripe_session_id' => 'cs_supplier_orphaned_authorization',
            'payment_status' => 'authorized',
            'fulfilment_status' => 'pending',
            'stripe_payment_intent_id' => 'pi_supplier_orphaned_authorization',
            'payload' => [],
        ]);
        $payments = Mockery::mock(StripePaymentLifecycleService::class);
        $payments->shouldReceive('releaseAuthorization')
            ->once()
            ->with('pi_supplier_orphaned_authorization', 'release_checkout_cs_supplier_orphaned_authorization');
        $this->app->instance(StripePaymentLifecycleService::class, $payments);

        (new ProcessPaidCheckoutSessionJob('cs_supplier_orphaned_authorization'))
            ->failed(new \RuntimeException('booking row could not be created'));

        $payload = StripeCheckoutPayload::where('stripe_session_id', 'cs_supplier_orphaned_authorization')->sole();
        $this->assertSame('authorization_released', $payload->payment_status);
        $this->assertSame('manual_review', $payload->fulfilment_status);
        Notification::assertSentTo($admin, AdminAuthorizationReviewNotification::class);
    }

    #[Test]
    public function exhausted_capture_retries_after_supplier_confirmation_require_manual_review_without_rebooking(): void
    {
        $admin = User::factory()->create([
            'email' => config('admin.email'),
            'role' => 'admin',
        ]);
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->authorizedSession('cs_supplier_capture_review')
        );
        $booking->update([
            'provider_booking_ref' => 'EMR-CAPTURE-REVIEW',
            'supplier_resolution_notified_at' => now(),
        ]);

        (new TriggerProviderReservationJob($booking->id, $this->metadata()))
            ->failed(new \RuntimeException('Stripe capture outcome unknown'));

        $booking->refresh();
        $this->assertSame('authorized', $booking->payment_status);
        $this->assertSame('EMR-CAPTURE-REVIEW', $booking->provider_booking_ref);
        $this->assertTrue((bool) ($booking->provider_metadata['reservation_manual_check'] ?? false));
        $controller = new StripeCheckoutController(app(StripeBookingService::class));
        $method = new ReflectionMethod($controller, 'resolveBookingOutcomeState');
        $method->setAccessible(true);
        $this->assertSame('supplier_confirmed_payment_review', $method->invoke(
            $controller,
            $booking,
            'support_review'
        ));
        Notification::assertSentTo($admin, AdminReservationManualCheckNotification::class);
        Notification::assertSentTo(
            $booking->customer->user,
            PaymentCaptureReviewCustomerNotification::class,
            function (PaymentCaptureReviewCustomerNotification $notification, array $channels) use ($booking): bool {
                $mail = $notification->toMail($booking->customer->user);
                $copy = implode(' ', $mail->introLines);

                return str_contains($copy, 'EMR-CAPTURE-REVIEW')
                    && str_contains($copy, 'payment status is under review')
                    && ! str_contains($copy, 'not been charged')
                    && ! str_contains($copy, 'unable to confirm your reservation');
            }
        );
        $this->assertNotNull($booking->fresh()->supplier_capture_review_notified_at);
    }

    private function authorizedSession(string $id = 'cs_supplier_authorized'): object
    {
        return (object) [
            'id' => $id,
            'payment_intent' => 'pi_'.$id,
            'payment_status' => 'unpaid',
            'amount_total' => 1500,
            'currency' => 'eur',
            'metadata' => (object) $this->metadata(),
        ];
    }

    private function metadata(): array
    {
        return [
            'capture_policy' => 'manual_supplier',
            'vehicle_source' => 'emr',
            'vehicle_id' => 'gateway-emr-vehicle-1',
            'gateway_vehicle_id' => 'gateway-emr-vehicle-1',
            'gateway_search_id' => 'gateway-search-1',
            'vehicle_brand' => 'Renault',
            'vehicle_model' => 'Clio',
            'customer_name' => 'Manual Capture Customer',
            'customer_email' => 'manual.capture@example.test',
            'customer_phone' => '+905551112233',
            'customer_driver_age' => 35,
            'pickup_date' => now()->addDays(10)->toDateString(),
            'pickup_time' => '10:00',
            'dropoff_date' => now()->addDays(13)->toDateString(),
            'dropoff_time' => '10:00',
            'pickup_location' => 'Antalya Airport',
            'dropoff_location' => 'Antalya Airport',
            'number_of_days' => 3,
            'package' => 'BAS',
            'currency' => 'EUR',
            'provider_currency' => 'EUR',
            'total_amount' => 100,
            'payable_amount' => 15,
            'pending_amount' => 85,
            'vehicle_total' => 100,
            'provider_grand_total' => 100,
            'payment_method' => 'card',
        ];
    }
}
