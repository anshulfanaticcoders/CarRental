<?php

namespace Tests\Feature;

use App\Http\Controllers\StripeCheckoutController;
use App\Jobs\ProcessPaidCheckoutSessionJob;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Customer;
use App\Models\StripeCheckoutPayload;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Services\CurrencyConversionService;
use App\Services\OfferService;
use App\Services\PriceVerificationService;
use App\Services\ProviderBookingCancellationService;
use App\Services\StripeBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Local, Stripe-free audit of the internal-fleet payment fulfilment path.
 * Stripe sessions are constructed in memory; no test calls api.stripe.com.
 */
class InternalFleetBookingPipelineTest extends TestCase
{
    use RefreshDatabase;

    private int $vehicleSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Notification::fake();
        config([
            'awin.enabled' => false,
            'currency.base_currency' => 'EUR',
            'currency.default' => 'EUR',
            'services.stripe.secret' => 'sk_test_local_never_used',
            'services.pricing.provider_markup_percent' => 15,
            'vrooem.enabled' => false,
        ]);

        $conversion = Mockery::mock(CurrencyConversionService::class);
        $conversion->shouldReceive('convert')->andReturnUsing(
            static fn (float $amount, string $from, string $to): array => [
                'success' => true,
                'converted_amount' => round($amount, 2),
                'rate' => $from === $to ? 1.0 : 1.0,
            ]
        );
        $this->app->instance(CurrencyConversionService::class, $conversion);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function a_paid_guest_internal_checkout_writes_the_booking_payment_amounts_extras_and_fulfilment_payload(): void
    {
        $vehicle = $this->createInternalVehicle();
        $sessionId = 'cs_internal_guest_full';
        $extras = [
            ['id' => 'child-seat', 'name' => 'Child seat', 'qty' => 2, 'total' => 10.00],
            ['id' => 'gps', 'name' => 'GPS', 'qty' => 1, 'total' => 30.00],
        ];
        $payload = $this->createPayload($sessionId, $extras);
        $metadata = $this->metadata($vehicle, [
            'extras_payload_id' => (string) $payload->id,
            'extras_total' => 50.00,
            'total_amount' => 150.00,
            'payable_amount' => 150.00,
            'pending_amount' => 0.00,
            'vehicle_total' => 100.00,
            'provider_vehicle_total' => 100.00,
            'provider_extras_total' => 50.00,
            'provider_grand_total' => 150.00,
        ]);
        $session = $this->fakeSession($sessionId, $metadata, ['amount_total' => 15000, 'currency' => 'eur']);

        // Alias only Stripe's static retrieve API. The result is the local fake
        // session above, so the queued fulfillment path cannot contact Stripe.
        $stripeSession = Mockery::mock('alias:Stripe\\Checkout\\Session');
        $stripeSession->shouldReceive('retrieve')->once()->with($sessionId)->andReturn($session);

        (new ProcessPaidCheckoutSessionJob($sessionId))->handle(app(StripeBookingService::class));

        $booking = Booking::where('stripe_session_id', $sessionId)->firstOrFail();
        $booking->load(['amounts', 'extras', 'payments', 'customer.user']);
        $payload->refresh();

        $this->assertSame('confirmed', $booking->booking_status);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame($vehicle->id, $booking->vehicle_id);
        $this->assertSame('internal', $booking->provider_source);
        $this->assertSame('BAS', $booking->plan);
        $this->assertSame('2026-09-10', $booking->pickup_date->toDateString());
        $this->assertSame('2026-09-13', $booking->return_date->toDateString());
        $this->assertSame('Faro Airport', $booking->pickup_location);
        $this->assertSame('Faro Airport', $booking->return_location);
        $this->assertSame('fulfilled', $payload->fulfilment_status);
        $this->assertSame($booking->id, $payload->booking_id);

        $this->assertNotNull($booking->customer);
        $this->assertNotNull($booking->customer->user);
        $this->assertSame('guest.internal@example.test', $booking->customer->email);
        $this->assertSame('customer', $booking->customer->user->role);
        $this->assertSame(1, Customer::count());
        $this->assertSame(2, User::count(), 'One vendor and one guest user must exist.');

        $this->assertCount(1, $booking->payments);
        $this->assertSame('pi_'.$sessionId, $booking->payments->first()->transaction_id);
        $this->assertSame('succeeded', $booking->payments->first()->payment_status);
        $this->assertEqualsWithDelta(150.00, (float) $booking->payments->first()->amount, 0.01);

        $amounts = $booking->amounts;
        $this->assertNotNull($amounts);
        $this->assertSame('EUR', $amounts->booking_currency);
        $this->assertSame('EUR', $amounts->admin_currency);
        $this->assertSame('EUR', $amounts->vendor_currency);
        $this->assertAmountTier($amounts->booking_total_amount, $amounts->booking_paid_amount, $amounts->booking_pending_amount, 150.00, 150.00, 0.00);
        $this->assertAmountTier($amounts->admin_total_amount, $amounts->admin_paid_amount, $amounts->admin_pending_amount, 150.00, 150.00, 0.00);
        $this->assertAmountTier($amounts->vendor_total_amount, $amounts->vendor_paid_amount, $amounts->vendor_pending_amount, 150.00, 0.00, 150.00);
        $this->assertEqualsWithDelta(50.00, (float) $amounts->booking_extra_amount, 0.01);

        $this->assertCount(2, $booking->extras);
        $this->assertSame(2, $booking->extras->firstWhere('provider_extra_id', 'child-seat')->quantity);
        $this->assertEqualsWithDelta(10.00, (float) $booking->extras->firstWhere('provider_extra_id', 'child-seat')->price, 0.01);
        $this->assertEqualsWithDelta(50.00, (float) $booking->extras->sum(
            fn ($extra): float => (float) $extra->price * $extra->quantity
        ), 0.01);

        $expiredSessionId = 'cs_internal_expired';
        $expiredPayload = $this->createPayload($expiredSessionId);
        $stripeSession->shouldReceive('retrieve')->once()->with($expiredSessionId)->andReturn((object) [
            'id' => $expiredSessionId,
            'payment_status' => 'unpaid',
            'status' => 'expired',
        ]);
        $unpaidService = Mockery::mock(StripeBookingService::class);
        $unpaidService->shouldNotReceive('createBookingFromSession');

        (new ProcessPaidCheckoutSessionJob($expiredSessionId))->handle($unpaidService);

        $expiredPayload->refresh();
        $this->assertSame('expired', $expiredPayload->fulfilment_status);
        $this->assertSame('unpaid', $expiredPayload->payment_status);

        $failingVehicle = $this->createInternalVehicle();
        $failingSessionId = 'cs_internal_forced_failure';
        $failingPayload = $this->createPayload($failingSessionId);
        $stripeSession->shouldReceive('retrieve')->once()->with($failingSessionId)->andReturn(
            $this->fakeSession($failingSessionId, $this->metadata($failingVehicle))
        );
        $failingService = new class extends StripeBookingService
        {
            protected function findOrCreateCustomer($metadata): array
            {
                throw new \RuntimeException('forced customer persistence failure');
            }
        };
        $failingJob = new ProcessPaidCheckoutSessionJob($failingSessionId);

        try {
            $failingJob->handle($failingService);
            $this->fail('The forced booking creation failure must be rethrown for queue retry.');
        } catch (\RuntimeException $exception) {
            $failingPayload->refresh();
            $this->assertSame('pending', $failingPayload->fulfilment_status);
            $this->assertStringContainsString('forced customer persistence failure', $failingPayload->last_error);
            $this->assertSame(1, Booking::count());

            $failingJob->failed($exception);
        }

        $failingPayload->refresh();
        $this->assertSame('manual_review', $failingPayload->fulfilment_status);
        $this->assertSame(1, Booking::count());
    }

    #[Test]
    public function a_registered_customer_is_reused_without_creating_a_guest_user(): void
    {
        $vehicle = $this->createInternalVehicle();
        $user = User::create([
            'first_name' => 'Registered', 'last_name' => 'Customer',
            'email' => 'registered.internal@example.test', 'phone' => '+12025550122',
            'password' => bcrypt('password'), 'role' => 'customer', 'status' => 'active',
        ]);
        $customer = Customer::create([
            'user_id' => $user->id, 'first_name' => 'Registered', 'last_name' => 'Customer',
            'email' => $user->email, 'phone' => $user->phone, 'driver_age' => 33,
        ]);

        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->fakeSession('cs_internal_registered', $this->metadata($vehicle, [
                'user_id' => $user->id,
                'customer_name' => 'Registered Customer',
                'customer_email' => $user->email,
                'customer_phone' => $user->phone,
            ]))
        );

        $this->assertSame($customer->id, $booking->customer_id);
        $this->assertSame(2, User::count(), 'Only vendor plus the registered customer may exist.');
        $this->assertSame(1, Customer::count());
    }

    #[Test]
    public function a_deposit_internal_booking_records_customer_admin_and_vendor_payment_splits(): void
    {
        $vehicle = $this->createInternalVehicle();

        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->fakeSession('cs_internal_deposit', $this->metadata($vehicle, [
                'total_amount' => 150.00,
                'payable_amount' => 22.50,
                'pending_amount' => 127.50,
                'provider_grand_total' => 150.00,
            ]))
        );
        $booking->load('amounts');

        $this->assertSame('partial', $booking->payment_status);
        $this->assertAmountTier($booking->amounts->booking_total_amount, $booking->amounts->booking_paid_amount, $booking->amounts->booking_pending_amount, 150.00, 22.50, 127.50);
        $this->assertAmountTier($booking->amounts->admin_total_amount, $booking->amounts->admin_paid_amount, $booking->amounts->admin_pending_amount, 22.50, 22.50, 0.00);
        $this->assertAmountTier($booking->amounts->vendor_total_amount, $booking->amounts->vendor_paid_amount, $booking->amounts->vendor_pending_amount, 150.00, 0.00, 150.00);
    }

    #[Test]
    public function duplicate_webhook_and_success_page_paths_collapse_to_one_booking_and_one_payment(): void
    {
        $vehicle = $this->createInternalVehicle();
        $session = $this->fakeSession('cs_internal_replay', $this->metadata($vehicle));
        $service = app(StripeBookingService::class);

        $first = $service->createBookingFromSession($session);
        $second = $service->createBookingFromSession($session);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Booking::where('stripe_session_id', $session->id)->count());
        $this->assertSame(1, BookingPayment::where('booking_id', $first->id)->count());
    }

    #[Test]
    public function a_success_page_revisit_after_fulfilment_does_not_create_a_second_internal_booking(): void
    {
        $vehicle = $this->createInternalVehicle();
        $session = $this->fakeSession('cs_internal_success_revisit', $this->metadata($vehicle));
        $booking = app(StripeBookingService::class)->createBookingFromSession($session);
        $service = Mockery::mock(StripeBookingService::class);
        $service->shouldNotReceive('createBookingFromSession');

        (new StripeCheckoutController($service))->success(
            Request::create('/stripe/success?session_id='.$session->id, 'GET'),
            $service
        );

        $this->assertSame(1, Booking::where('stripe_session_id', $session->id)->count());
        $this->assertSame(1, BookingPayment::where('booking_id', $booking->id)->count());
    }

    #[Test]
    public function an_overlapping_paid_session_for_the_same_internal_vehicle_is_rejected_without_double_booking(): void
    {
        $vehicle = $this->createInternalVehicle();
        $first = app(StripeBookingService::class)->createBookingFromSession(
            $this->fakeSession('cs_internal_overlap_a', $this->metadata($vehicle))
        );
        $second = app(StripeBookingService::class)->createBookingFromSession(
            $this->fakeSession('cs_internal_overlap_b', $this->metadata($vehicle, [
                'customer_email' => 'second.internal@example.test',
                'customer_phone' => '+12025550123',
            ]))
        );

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertSame(1, Booking::where('vehicle_id', $vehicle->id)->count());
        $this->assertSame(0, Booking::where('stripe_session_id', 'cs_internal_overlap_b')->count());
    }

    #[Test]
    public function checkout_rejects_a_tampered_internal_price_before_a_stripe_session_is_created(): void
    {
        $vehicle = $this->createInternalVehicle();
        $offerService = Mockery::mock(OfferService::class);
        $offerService->shouldReceive('getOfferFingerprint')->andReturn('internal-pipeline');
        $this->app->instance(OfferService::class, $offerService);
        $searchSession = 'internal-price-tamper';
        app(PriceVerificationService::class)->storeOriginalPrices($searchSession, [[
            'id' => (string) $vehicle->id,
            'source' => 'internal',
            'pricing' => ['currency' => 'EUR', 'price_per_day' => 50.00, 'total_price' => 150.00],
            'products' => [['type' => 'BAS', 'total' => 150.00]],
        ]]);

        $response = (new StripeCheckoutController(app(StripeBookingService::class)))->createSession(
            Request::create('/stripe/create-session', 'POST', [
                'search_session_id' => $searchSession,
                'vehicle' => [
                    'id' => $vehicle->id,
                    'source' => 'internal',
                    'price_hash' => str_repeat('0', 64),
                    'pricing' => ['currency' => 'EUR', 'total_price' => 1.00],
                ],
                'package' => 'BAS',
                'customer' => [
                    'name' => 'Tampered Price', 'email' => 'tampered.internal@example.test',
                    'phone' => '+12025550124', 'driver_age' => 35,
                ],
                'pickup_date' => '2026-09-10', 'pickup_time' => '10:00',
                'dropoff_date' => '2026-09-13', 'dropoff_time' => '10:00',
                'pickup_location' => 'Faro Airport', 'dropoff_location' => 'Faro Airport',
                'total_amount' => 1.00, 'currency' => 'EUR', 'number_of_days' => 3,
            ])
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('Price verification failed', $response->getContent());
        $this->assertSame(0, StripeCheckoutPayload::count());
    }

    #[Test]
    public function cancellation_of_a_paid_internal_booking_flags_the_manual_refund(): void
    {
        $vehicle = $this->createInternalVehicle();
        $booking = app(StripeBookingService::class)->createBookingFromSession(
            $this->fakeSession('cs_internal_cancel', $this->metadata($vehicle, [
                'payable_amount' => 150.00,
                'pending_amount' => 0.00,
            ]))
        );

        $result = app(ProviderBookingCancellationService::class)->cancel(
            $booking->id,
            'Customer plans changed.',
            'Test'
        );
        $booking->refresh();

        $this->assertTrue($result['success']);
        $this->assertSame('cancelled', $booking->booking_status);
        $this->assertSame('Customer plans changed.', $booking->cancellation_reason);
        // Captured money on a cancelled booking must enter the manual-refund flow.
        $this->assertSame('refund_pending', $booking->payment_status);
        $this->assertTrue((bool) ($booking->provider_metadata['manual_refund_required'] ?? false));
    }

    private function createInternalVehicle(): Vehicle
    {
        $sequence = ++$this->vehicleSequence;
        $vendor = User::create([
            'first_name' => 'Fleet', 'last_name' => 'Vendor',
            'email' => 'vendor.'.uniqid().'@example.test', 'phone' => sprintf('+1202555%04d', $sequence),
            'password' => bcrypt('password'), 'role' => 'vendor', 'status' => 'active',
        ]);
        $category = VehicleCategory::firstOrCreate(
            ['name' => 'Economy'],
            ['slug' => 'economy', 'description' => 'Economy', 'status' => true]
        );

        return Vehicle::create([
            'vendor_id' => $vendor->id, 'category_id' => $category->id,
            'brand' => 'Toyota', 'model' => 'Yaris', 'color' => 'White', 'mileage' => 12000,
            'transmission' => 'Automatic', 'fuel' => 'Petrol', 'seating_capacity' => 5,
            'number_of_doors' => 4, 'luggage_capacity' => 2, 'horsepower' => 100, 'co2' => '110',
            'location' => 'Faro Airport', 'city' => 'Faro', 'state' => 'Faro', 'country' => 'Portugal',
            'latitude' => 37.0194, 'longitude' => -7.9304, 'status' => 'available',
            'security_deposit' => 0, 'payment_method' => 'card', 'price_per_day' => 50.00,
        ]);
    }

    private function createPayload(string $sessionId, array $extras = []): StripeCheckoutPayload
    {
        return StripeCheckoutPayload::create([
            'stripe_session_id' => $sessionId,
            'payload' => ['detailed_extras' => $extras],
            'payment_status' => 'unpaid',
            'fulfilment_status' => 'pending',
        ]);
    }

    private function metadata(Vehicle $vehicle, array $overrides = []): array
    {
        return array_merge([
            'vehicle_source' => 'internal', 'vehicle_id' => $vehicle->id,
            'vehicle_brand' => $vehicle->brand, 'vehicle_model' => $vehicle->model,
            'customer_name' => 'Guest Internal', 'customer_email' => 'guest.internal@example.test',
            'customer_phone' => '+12025550121', 'customer_driver_age' => 35,
            'pickup_date' => '2026-09-10', 'pickup_time' => '10:00',
            'dropoff_date' => '2026-09-13', 'dropoff_time' => '10:00',
            'pickup_location' => 'Faro Airport', 'dropoff_location' => 'Faro Airport',
            'number_of_days' => 3, 'package' => 'BAS', 'currency' => 'EUR', 'provider_currency' => 'EUR',
            'total_amount' => 150.00, 'payable_amount' => 22.50, 'pending_amount' => 127.50,
            'vehicle_total' => 150.00, 'extras_total' => 0.00,
            'provider_vehicle_total' => 150.00, 'provider_extras_total' => 0.00, 'provider_grand_total' => 150.00,
            'payment_method' => 'stripe',
        ], $overrides);
    }

    private function fakeSession(string $id, array $metadata, array $overrides = []): object
    {
        return (object) array_merge([
            'id' => $id,
            'payment_intent' => 'pi_'.$id,
            'payment_status' => 'paid',
            'metadata' => (object) $metadata,
        ], $overrides);
    }

    private function assertAmountTier($total, $paid, $pending, float $expectedTotal, float $expectedPaid, float $expectedPending): void
    {
        $this->assertEqualsWithDelta($expectedTotal, (float) $total, 0.01);
        $this->assertEqualsWithDelta($expectedPaid, (float) $paid, 0.01);
        $this->assertEqualsWithDelta($expectedPending, (float) $pending, 0.01);
        $this->assertEqualsWithDelta((float) $total, (float) $paid + (float) $pending, 0.01);
    }
}
