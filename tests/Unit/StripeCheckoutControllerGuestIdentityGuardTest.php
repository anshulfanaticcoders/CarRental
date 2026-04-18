<?php

namespace Tests\Unit;

use App\Http\Controllers\StripeCheckoutController;
use App\Services\CheckoutIdentityGuardService;
use App\Services\StripeBookingService;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class StripeCheckoutControllerGuestIdentityGuardTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_guest_checkout_returns_a_login_required_response_when_email_belongs_to_existing_customer(): void
    {
        $guard = Mockery::mock(CheckoutIdentityGuardService::class);
        $guard->shouldReceive('findExistingUsers')
            ->once()
            ->andReturn([
                'email_user' => new \App\Models\User(['role' => 'customer']),
                'phone_user' => null,
            ]);
        $guard->shouldReceive('resolveCheckoutConflict')
            ->once()
            ->andReturn([
                'code' => 'checkout_login_required',
                'message' => 'This email is already linked to an account. Please log in to continue with your booking.',
                'show_login' => true,
            ]);

        app()->instance(CheckoutIdentityGuardService::class, $guard);

        $controller = new StripeCheckoutController(Mockery::mock(StripeBookingService::class));
        $request = Request::create('/api/stripe/checkout', 'POST', [
            'vehicle' => ['source' => 'internal', 'id' => 1],
            'package' => 'BAS',
            'customer' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+10000000000',
                'driver_age' => 30,
            ],
            'pickup_date' => '2026-05-01',
            'pickup_time' => '09:00',
            'dropoff_date' => '2026-05-05',
            'dropoff_time' => '09:00',
            'pickup_location' => 'Dubai Airport',
            'dropoff_location' => 'Dubai Airport',
            'total_amount' => 100,
            'currency' => 'EUR',
            'number_of_days' => 4,
        ]);
        $request->setUserResolver(static fn () => null);

        $response = $controller->createSession($request);

        $this->assertSame(409, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);

        $this->assertSame('checkout_login_required', $payload['error_code']);
        $this->assertSame(route('login', ['locale' => 'en']), $payload['login_url']);
    }
}
