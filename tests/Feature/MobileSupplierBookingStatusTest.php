<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\BookingController;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\StripeCheckoutPayload;
use App\Models\User;
use App\Services\StripeBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MobileSupplierBookingStatusTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_released_authorization_without_a_booking_is_terminal_for_mobile_polling(): void
    {
        StripeCheckoutPayload::create([
            'stripe_session_id' => 'cs_mobile_released',
            'payment_status' => 'authorization_released',
            'fulfilment_status' => 'manual_review',
        ]);

        $response = (new BookingController)->bySession(
            Request::create('/api/mobile/bookings/by-session', 'GET', [
                'session_id' => 'cs_mobile_released',
            ]),
            $this->mock(StripeBookingService::class)
        );

        $this->assertSame('authorization_released', $response->getData(true)['status']);
    }

    #[Test]
    public function an_authorized_supplier_booking_has_an_explicit_mobile_state(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Mobile',
            'last_name' => 'Customer',
            'email' => $user->email,
            'phone' => $user->phone,
            'driver_age' => 35,
        ]);
        Booking::create([
            'booking_number' => 'BK-MOBILE-AUTH',
            'customer_id' => $customer->id,
            'provider_source' => 'emr',
            'vehicle_name' => 'Renault Clio',
            'pickup_date' => now()->addDays(2),
            'return_date' => now()->addDays(4),
            'pickup_time' => '10:00',
            'return_time' => '10:00',
            'pickup_location' => 'Airport',
            'return_location' => 'Airport',
            'plan' => 'BAS',
            'total_days' => 2,
            'base_price' => 100,
            'tax_amount' => 0,
            'total_amount' => 100,
            'amount_paid' => 0,
            'pending_amount' => 100,
            'booking_currency' => 'EUR',
            'payment_status' => 'authorized',
            'booking_status' => 'supplier_pending',
            'stripe_session_id' => 'cs_mobile_authorized',
            'provider_metadata' => [],
        ]);
        $request = Request::create('/api/mobile/bookings/by-session', 'GET', [
            'session_id' => 'cs_mobile_authorized',
        ]);
        $request->setUserResolver(fn () => $user);

        $response = (new BookingController)->bySession(
            $request,
            $this->mock(StripeBookingService::class)
        );
        $data = $response->getData(true);

        $this->assertSame('authorized', $data['status']);
        $this->assertSame('BK-MOBILE-AUTH', $data['booking']['booking_number']);

        $showResponse = (new BookingController)->show($request, $data['booking']['id']);
        $this->assertSame('authorized', $showResponse->getData(true)['status']);
    }

    #[Test]
    public function a_failed_supplier_booking_with_a_released_authorization_is_terminal(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Released',
            'last_name' => 'Customer',
            'email' => $user->email,
            'phone' => $user->phone,
            'driver_age' => 35,
        ]);
        $booking = Booking::create([
            'booking_number' => 'BK-MOBILE-RELEASED',
            'customer_id' => $customer->id,
            'provider_source' => 'emr',
            'vehicle_name' => 'Renault Clio',
            'pickup_date' => now()->addDays(2),
            'return_date' => now()->addDays(4),
            'pickup_time' => '10:00',
            'return_time' => '10:00',
            'pickup_location' => 'Airport',
            'return_location' => 'Airport',
            'plan' => 'BAS',
            'total_days' => 2,
            'base_price' => 100,
            'tax_amount' => 0,
            'total_amount' => 100,
            'amount_paid' => 0,
            'pending_amount' => 100,
            'booking_currency' => 'EUR',
            'payment_status' => 'payment_cancelled',
            'booking_status' => 'reservation_failed',
            'stripe_session_id' => 'cs_mobile_failed_released',
            'provider_metadata' => [],
        ]);
        $request = Request::create('/api/mobile/bookings/'.$booking->id, 'GET');
        $request->setUserResolver(fn () => $user);

        $response = (new BookingController)->show($request, $booking->id);

        $this->assertSame('authorization_released', $response->getData(true)['status']);
    }
}
