<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VendorProfile;
use App\Notifications\Booking\BookingCreatedAdminNotification;
use App\Notifications\Booking\BookingCreatedCompanyNotification;
use App\Notifications\Booking\BookingCreatedCustomerNotification;
use App\Notifications\Booking\BookingCreatedVendorNotification;
use App\Notifications\Booking\GuestBookingCreatedNotification;
use App\Services\CurrencyConversionService;
use App\Services\StripeBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StripeBookingServiceAccountingTest extends TestCase
{
    use RefreshDatabase;

    private function makeServiceWithIdentityConversions(): StripeBookingService
    {
        $conversionService = Mockery::mock(CurrencyConversionService::class);
        $conversionService->shouldReceive('convert')
            ->andReturnUsing(static function (float $amount, string $from, string $to): array {
                return [
                    'success' => true,
                    'original_amount' => $amount,
                    'converted_amount' => round($amount, 2),
                    'from_currency' => $from,
                    'to_currency' => $to,
                    'rate' => $from === $to ? 1.0 : 1.0,
                ];
            });

        app()->instance(CurrencyConversionService::class, $conversionService);

        return new StripeBookingService();
    }

    private function createAdminUser(): User
    {
        return User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => env('VITE_ADMIN_EMAIL', 'default@admin.com'),
            'phone' => '+10000000000',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function createInternalVehicleContext(): array
    {
        $vendor = User::create([
            'first_name' => 'Victor',
            'last_name' => 'Vendor',
            'email' => 'vendor@example.com',
            'phone' => '+10000000001',
            'password' => bcrypt('password'),
            'role' => 'vendor',
            'status' => 'active',
        ]);

        $vendorProfile = VendorProfile::create([
            'user_id' => $vendor->id,
            'company_name' => 'Vendor Fleet',
            'company_phone_number' => '+10000000002',
            'company_email' => 'fleet@example.com',
            'company_address' => 'Airport Road',
            'company_gst_number' => 'GST-12345',
            'status' => 'approved',
        ]);

        $category = VehicleCategory::create([
            'name' => 'SUV',
            'slug' => 'suv',
            'description' => 'SUV',
            'status' => true,
        ]);

        $vehicle = Vehicle::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'brand' => 'Example',
            'model' => 'SUV',
            'color' => 'Black',
            'mileage' => 10000,
            'transmission' => 'Automatic',
            'fuel' => 'Petrol',
            'seating_capacity' => 5,
            'number_of_doors' => 4,
            'luggage_capacity' => 3,
            'horsepower' => 140,
            'co2' => '100',
            'location' => 'Dubai Airport',
            'city' => 'Dubai',
            'state' => 'Dubai',
            'country' => 'UAE',
            'latitude' => 25.251369,
            'longitude' => 55.347204,
            'status' => 'available',
            'security_deposit' => 0,
            'payment_method' => 'card',
            'price_per_day' => 100,
        ]);

        return [$vendor, $vendorProfile, $vehicle];
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    #[Test]
    public function it_stores_commission_only_admin_amounts_and_full_provider_settlement_for_stripe_bookings(): void
    {
        Notification::fake();

        config([
            'currency.base_currency' => 'EUR',
            'currency.default' => 'EUR',
            'vrooem.enabled' => false,
        ]);

        $rate = 0.009380335065568542;
        $conversionService = Mockery::mock(CurrencyConversionService::class);
        $conversionService->shouldReceive('convert')
            ->andReturnUsing(static function (float $amount, string $from, string $to) use ($rate): array {
                if ($from === $to) {
                    return [
                        'success' => true,
                        'original_amount' => $amount,
                        'converted_amount' => round($amount, 2),
                        'from_currency' => $from,
                        'to_currency' => $to,
                        'rate' => 1.0,
                    ];
                }

                if ($from === 'INR' && $to === 'EUR') {
                    return [
                        'success' => true,
                        'original_amount' => $amount,
                        'converted_amount' => round($amount * $rate, 2),
                        'from_currency' => $from,
                        'to_currency' => $to,
                        'rate' => $rate,
                    ];
                }

                return [
                    'success' => false,
                    'original_amount' => $amount,
                    'converted_amount' => $amount,
                    'from_currency' => $from,
                    'to_currency' => $to,
                    'error' => "Unexpected conversion {$from}->{$to}",
                ];
            });

        app()->instance(CurrencyConversionService::class, $conversionService);

        $service = new StripeBookingService();

        $session = (object) [
            'id' => 'cs_test_accounting',
            'payment_intent' => 'pi_test_accounting',
            'metadata' => (object) [
                'vehicle_source' => 'internal',
                'vehicle_id' => null,
                'vehicle_brand' => 'Example',
                'vehicle_model' => 'Sedan',
                'pickup_date' => '2026-04-15',
                'pickup_time' => '09:00',
                'dropoff_date' => '2026-04-18',
                'dropoff_time' => '09:00',
                'pickup_location' => 'Sample Airport',
                'dropoff_location' => 'Sample Airport',
                'package' => 'BAS',
                'number_of_days' => 3,
                'currency' => 'INR',
                'provider_currency' => 'USD',
                'total_amount' => 15831.21,
                'total_amount_net' => 13766.27,
                'payable_amount' => 2064.94,
                'pending_amount' => 13766.27,
                'vehicle_total' => 10554.14,
                'extras_total' => 5277.07,
                'provider_vehicle_total' => 100.00,
                'provider_extras_total' => 50.00,
                'provider_grand_total' => 150.00,
                'deposit_percentage' => 15.00,
                'customer_name' => 'Test Customer',
                'customer_email' => 'accounting@example.com',
                'customer_phone' => '+919999999999',
                'customer_driver_age' => 35,
                'payment_method' => 'card',
            ],
        ];

        $booking = $service->createBookingFromSession($session);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertDatabaseHas('customers', [
            'email' => 'accounting@example.com',
        ]);

        $booking->refresh()->load('amounts');

        $this->assertNotNull($booking->amounts);
        $this->assertSame('INR', $booking->booking_currency);
        $this->assertSame('EUR', $booking->amounts->admin_currency);
        $this->assertSame('USD', $booking->amounts->vendor_currency);
        $this->assertEqualsWithDelta(10554.14, (float) $booking->base_price, 0.01);
        $this->assertEqualsWithDelta(5277.07, (float) $booking->extra_charges, 0.01);

        $this->assertEqualsWithDelta(19.37, (float) $booking->amounts->admin_total_amount, 0.01);
        $this->assertEqualsWithDelta(19.37, (float) $booking->amounts->admin_paid_amount, 0.01);
        $this->assertEqualsWithDelta(0.00, (float) $booking->amounts->admin_pending_amount, 0.01);

        $this->assertEqualsWithDelta(150.00, (float) $booking->amounts->vendor_total_amount, 0.01);
        $this->assertEqualsWithDelta(0.00, (float) $booking->amounts->vendor_paid_amount, 0.01);
        $this->assertEqualsWithDelta(150.00, (float) $booking->amounts->vendor_pending_amount, 0.01);
        $this->assertEqualsWithDelta(50.00, (float) $booking->amounts->vendor_extra_amount, 0.01);

        $providerPricing = $booking->provider_metadata['provider_pricing'] ?? [];
        $this->assertEqualsWithDelta(0.00, (float) ($providerPricing['deposit_total'] ?? -1), 0.01);
        $this->assertEqualsWithDelta(150.00, (float) ($providerPricing['due_at_pickup_total'] ?? 0), 0.01);
    }

    #[Test]
    public function it_sends_internal_booking_notifications_to_admin_customer_vendor_and_company(): void
    {
        Notification::fake();

        config([
            'currency.base_currency' => 'EUR',
            'currency.default' => 'EUR',
            'vrooem.enabled' => false,
        ]);

        $service = $this->makeServiceWithIdentityConversions();
        $admin = $this->createAdminUser();
        [$vendor, $vendorProfile, $vehicle] = $this->createInternalVehicleContext();

        $customerUser = User::create([
            'first_name' => 'Cora',
            'last_name' => 'Customer',
            'email' => 'internal-customer@example.com',
            'phone' => '+10000000003',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'status' => 'active',
        ]);

        Customer::create([
            'user_id' => $customerUser->id,
            'first_name' => 'Cora',
            'last_name' => 'Customer',
            'email' => 'internal-customer@example.com',
            'phone' => '+10000000003',
            'driver_age' => 35,
        ]);

        $session = (object) [
            'id' => 'cs_test_internal_notifications',
            'payment_intent' => 'pi_test_internal_notifications',
            'metadata' => (object) [
                'vehicle_source' => 'internal',
                'vehicle_id' => $vehicle->id,
                'vehicle_brand' => 'Example',
                'vehicle_model' => 'SUV',
                'pickup_date' => '2026-04-15',
                'pickup_time' => '09:00',
                'dropoff_date' => '2026-04-18',
                'dropoff_time' => '09:00',
                'pickup_location' => 'Dubai Airport',
                'dropoff_location' => 'Dubai Airport',
                'package' => 'BAS',
                'number_of_days' => 3,
                'currency' => 'EUR',
                'provider_currency' => 'EUR',
                'total_amount' => 172.50,
                'total_amount_net' => 150.00,
                'payable_amount' => 22.50,
                'pending_amount' => 150.00,
                'vehicle_total' => 115.00,
                'extras_total' => 57.50,
                'provider_vehicle_total' => 100.00,
                'provider_extras_total' => 50.00,
                'provider_grand_total' => 150.00,
                'provider_booking_ref' => 'internal-existing-ref',
                'deposit_percentage' => 15.00,
                'customer_name' => 'Cora Customer',
                'customer_email' => 'internal-customer@example.com',
                'customer_phone' => '+10000000003',
                'customer_driver_age' => 35,
                'payment_method' => 'card',
            ],
        ];

        $service->createBookingFromSession($session);

        Notification::assertSentTo($admin, BookingCreatedAdminNotification::class);
        Notification::assertSentTo($vendor, BookingCreatedVendorNotification::class);
        Notification::assertSentTo($customerUser, BookingCreatedCustomerNotification::class);
        Notification::assertSentOnDemandTimes(BookingCreatedCompanyNotification::class, 1);
        Notification::assertSentTimes(GuestBookingCreatedNotification::class, 0);
        Notification::assertCount(4);
    }

    #[Test]
    public function it_only_notifies_admin_for_external_provider_bookings_in_the_unified_flow(): void
    {
        Notification::fake();

        config([
            'currency.base_currency' => 'EUR',
            'currency.default' => 'EUR',
            'vrooem.enabled' => false,
        ]);

        $service = $this->makeServiceWithIdentityConversions();
        $admin = $this->createAdminUser();

        $session = (object) [
            'id' => 'cs_test_external_notifications',
            'payment_intent' => 'pi_test_external_notifications',
            'metadata' => (object) [
                'vehicle_source' => 'greenmotion',
                'vehicle_id' => 'greenmotion_123',
                'vehicle_brand' => 'Example',
                'vehicle_model' => 'Sedan',
                'pickup_date' => '2026-04-15',
                'pickup_time' => '09:00',
                'dropoff_date' => '2026-04-18',
                'dropoff_time' => '09:00',
                'pickup_location' => 'Dubai Airport',
                'dropoff_location' => 'Dubai Airport',
                'package' => 'BAS',
                'number_of_days' => 3,
                'currency' => 'EUR',
                'provider_currency' => 'EUR',
                'total_amount' => 172.50,
                'total_amount_net' => 150.00,
                'payable_amount' => 22.50,
                'pending_amount' => 150.00,
                'vehicle_total' => 115.00,
                'extras_total' => 57.50,
                'provider_vehicle_total' => 100.00,
                'provider_extras_total' => 50.00,
                'provider_grand_total' => 150.00,
                'provider_booking_ref' => 'provider-existing-ref',
                'deposit_percentage' => 15.00,
                'customer_name' => 'Provider Customer',
                'customer_email' => 'provider-customer@example.com',
                'customer_phone' => '+10000000004',
                'customer_driver_age' => 35,
                'payment_method' => 'card',
            ],
        ];

        $booking = $service->createBookingFromSession($session);
        $customer = $booking->customer()->firstOrFail();
        $customerUser = $customer->user()->firstOrFail();

        Notification::assertSentTo($admin, BookingCreatedAdminNotification::class);
        Notification::assertNothingSentTo($customerUser);
        Notification::assertSentTimes(BookingCreatedCustomerNotification::class, 0);
        Notification::assertSentTimes(GuestBookingCreatedNotification::class, 0);
        Notification::assertSentTimes(BookingCreatedVendorNotification::class, 0);
        Notification::assertSentOnDemandTimes(BookingCreatedCompanyNotification::class, 0);
        Notification::assertCount(1);
    }

    #[Test]
    public function it_routes_new_external_provider_reservations_through_the_gateway_even_when_the_legacy_flag_is_disabled(): void
    {
        config([
            'currency.base_currency' => 'EUR',
            'currency.default' => 'EUR',
            'vrooem.enabled' => false,
        ]);

        $service = new class () extends StripeBookingService {
            public array $reservationTriggers = [];

            protected function notifyBookingCreated($booking, $customer, ?string $tempPassword = null): void
            {
            }

            protected function triggerGatewayReservation($booking, $metadata)
            {
                $this->reservationTriggers[] = [
                    'type' => 'gateway',
                    'provider_source' => $booking->provider_source,
                ];
            }

            protected function triggerRecordGoReservation($booking, $metadata)
            {
                $this->reservationTriggers[] = [
                    'type' => 'legacy_recordgo',
                    'provider_source' => $booking->provider_source,
                ];
            }
        };

        $session = (object) [
            'id' => 'cs_test_external_gateway_only',
            'payment_intent' => 'pi_test_external_gateway_only',
            'metadata' => (object) [
                'vehicle_source' => 'recordgo',
                'vehicle_id' => 'recordgo_123',
                'vehicle_brand' => 'Example',
                'vehicle_model' => 'Sedan',
                'pickup_date' => '2026-04-15',
                'pickup_time' => '09:00',
                'dropoff_date' => '2026-04-18',
                'dropoff_time' => '09:00',
                'pickup_location' => 'Dubai Airport',
                'dropoff_location' => 'Dubai Airport',
                'package' => 'BAS',
                'number_of_days' => 3,
                'currency' => 'EUR',
                'provider_currency' => 'EUR',
                'total_amount' => 172.50,
                'total_amount_net' => 150.00,
                'payable_amount' => 22.50,
                'pending_amount' => 150.00,
                'vehicle_total' => 115.00,
                'extras_total' => 57.50,
                'provider_vehicle_total' => 100.00,
                'provider_extras_total' => 50.00,
                'provider_grand_total' => 150.00,
                'provider_booking_ref' => null,
                'gateway_vehicle_id' => 'gateway_recordgo_vehicle_1',
                'gateway_search_id' => 'gw_search_1',
                'deposit_percentage' => 15.00,
                'customer_name' => 'Provider Customer',
                'customer_email' => 'gateway-only-provider@example.com',
                'customer_phone' => '+10000000005',
                'customer_driver_age' => 35,
                'payment_method' => 'card',
            ],
        ];

        $service->createBookingFromSession($session);

        $this->assertSame([
            [
                'type' => 'gateway',
                'provider_source' => 'recordgo',
            ],
        ], $service->reservationTriggers);
    }
}
