<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use App\Services\FavricaService;
use App\Services\GreenMotionService;
use App\Services\RenteonService;
use App\Services\SicilyByCarService;
use App\Services\SurpriceService;
use App\Services\VrooemGatewayService;
use App\Services\XDriveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCancellationGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_gateway_cancellation_is_used_when_gateway_booking_metadata_exists(): void
    {
        config(['vrooem.enabled' => true]);

        $user = User::factory()->create();
        $booking = $this->createExternalBooking($user, [
            'provider_source' => 'recordgo',
            'provider_booking_ref' => 'SUP-BOOK-1',
            'provider_metadata' => [
                'gateway_booking_id' => 'gw_1',
                'gateway_supplier_id' => 'record_go',
            ],
        ]);

        $this->mock(VrooemGatewayService::class, function ($mock): void {
            $mock->shouldReceive('cancelBooking')
                ->once()
                ->with('gw_1', 'record_go', 'SUP-BOOK-1', 'Need to cancel')
                ->andReturn(['status' => 'cancelled']);
        });

        $response = $this
            ->actingAs($user)
            ->from(route('profile.bookings.all', ['locale' => 'en']))
            ->post(route('booking.cancel', ['locale' => 'en']), [
                'booking_id' => $booking->id,
                'cancellation_reason' => 'Need to cancel',
            ]);

        $response->assertRedirect();

        $booking->refresh();
        $this->assertSame('cancelled', $booking->booking_status);
        $this->assertSame('Need to cancel', $booking->cancellation_reason);
        $this->assertStringContainsString('Gateway Cancel', (string) $booking->notes);
    }

    public function test_sicily_by_car_cancellation_uses_provider_service_for_underscored_source(): void
    {
        config(['vrooem.enabled' => false]);

        $user = User::factory()->create();
        $booking = $this->createExternalBooking($user, [
            'provider_source' => 'sicily_by_car',
            'provider_booking_ref' => 'SBC-123',
            'provider_metadata' => [],
        ]);

        $this->mock(SicilyByCarService::class, function ($mock): void {
            $mock->shouldReceive('cancelReservation')
                ->once()
                ->with('SBC-123')
                ->andReturn(['ok' => true]);
        });

        $response = $this
            ->actingAs($user)
            ->from(route('profile.bookings.all', ['locale' => 'en']))
            ->post(route('booking.cancel', ['locale' => 'en']), [
                'booking_id' => $booking->id,
                'cancellation_reason' => 'Need to cancel',
            ]);

        $response->assertRedirect();

        $booking->refresh();
        $this->assertSame('cancelled', $booking->booking_status);
        $this->assertSame('Need to cancel', $booking->cancellation_reason);
    }

    public function test_greenmotion_cancellation_uses_provider_service_and_marks_booking_cancelled(): void
    {
        config(['vrooem.enabled' => false]);

        $user = User::factory()->create();
        $booking = $this->createExternalBooking($user, [
            'provider_source' => 'greenmotion',
            'provider_booking_ref' => 'GM-123',
            'provider_metadata' => [
                'pickup_location_id' => 'LOC-10',
            ],
        ]);

        $this->mock(GreenMotionService::class, function ($mock): void {
            $mock->shouldReceive('setProvider')
                ->once()
                ->with('greenmotion')
                ->andReturnSelf();
            $mock->shouldReceive('cancelReservation')
                ->once()
                ->with('LOC-10', 'GM-123', 'Need to cancel')
                ->andReturn('<response><status>success</status><booking_ref>GM-123</booking_ref><booking_notes>Cancelled by provider</booking_notes></response>');
        });

        $response = $this
            ->actingAs($user)
            ->from(route('profile.bookings.all', ['locale' => 'en']))
            ->post(route('booking.cancel', ['locale' => 'en']), [
                'booking_id' => $booking->id,
                'cancellation_reason' => 'Need to cancel',
            ]);

        $response->assertRedirect();

        $booking->refresh();
        $this->assertSame('cancelled', $booking->booking_status);
        $this->assertSame('Need to cancel', $booking->cancellation_reason);
        $this->assertStringContainsString('GreenMotion Cancel: Cancelled by provider', (string) $booking->notes);
    }

    public function test_usave_cancellation_routes_through_greenmotion_service(): void
    {
        config(['vrooem.enabled' => false]);

        $user = User::factory()->create();
        $booking = $this->createExternalBooking($user, [
            'provider_source' => 'usave',
            'provider_booking_ref' => 'US-123',
            'provider_metadata' => [
                'dropoff_location_id' => 'LOC-20',
            ],
        ]);

        $this->mock(GreenMotionService::class, function ($mock): void {
            $mock->shouldReceive('setProvider')
                ->once()
                ->with('usave')
                ->andReturnSelf();
            $mock->shouldReceive('cancelReservation')
                ->once()
                ->with('LOC-20', 'US-123', 'Need to cancel')
                ->andReturn('<response><status>ok</status><booking_ref>US-123</booking_ref></response>');
        });

        $response = $this
            ->actingAs($user)
            ->from(route('profile.bookings.all', ['locale' => 'en']))
            ->post(route('booking.cancel', ['locale' => 'en']), [
                'booking_id' => $booking->id,
                'cancellation_reason' => 'Need to cancel',
            ]);

        $response->assertRedirect();

        $booking->refresh();
        $this->assertSame('cancelled', $booking->booking_status);
        $this->assertStringContainsString('GreenMotion Cancel: Cancellation requested.', (string) $booking->notes);
    }

    public function test_favrica_cancellation_uses_provider_service(): void
    {
        config(['vrooem.enabled' => false]);

        $user = User::factory()->create();
        $booking = $this->createExternalBooking($user, [
            'provider_source' => 'favrica',
            'provider_booking_ref' => 'FV-123',
        ]);

        $this->mock(FavricaService::class, function ($mock): void {
            $mock->shouldReceive('cancelReservation')
                ->once()
                ->with('FV-123')
                ->andReturn([['success' => 'true']]);
        });

        $response = $this
            ->actingAs($user)
            ->from(route('profile.bookings.all', ['locale' => 'en']))
            ->post(route('booking.cancel', ['locale' => 'en']), [
                'booking_id' => $booking->id,
                'cancellation_reason' => 'Need to cancel',
            ]);

        $response->assertRedirect();

        $booking->refresh();
        $this->assertSame('cancelled', $booking->booking_status);
        $this->assertStringContainsString('Favrica Cancel: cancellation requested.', (string) $booking->notes);
    }

    public function test_xdrive_cancellation_uses_provider_service(): void
    {
        config(['vrooem.enabled' => false]);

        $user = User::factory()->create();
        $booking = $this->createExternalBooking($user, [
            'provider_source' => 'xdrive',
            'provider_booking_ref' => 'XD-123',
        ]);

        $this->mock(XDriveService::class, function ($mock): void {
            $mock->shouldReceive('cancelReservation')
                ->once()
                ->with('XD-123')
                ->andReturn([['success' => 'true']]);
        });

        $response = $this
            ->actingAs($user)
            ->from(route('profile.bookings.all', ['locale' => 'en']))
            ->post(route('booking.cancel', ['locale' => 'en']), [
                'booking_id' => $booking->id,
                'cancellation_reason' => 'Need to cancel',
            ]);

        $response->assertRedirect();

        $booking->refresh();
        $this->assertSame('cancelled', $booking->booking_status);
        $this->assertStringContainsString('XDrive Cancel: cancellation requested.', (string) $booking->notes);
    }

    public function test_surprice_cancellation_uses_provider_service(): void
    {
        config(['vrooem.enabled' => false]);

        $user = User::factory()->create();
        $booking = $this->createExternalBooking($user, [
            'provider_source' => 'surprice',
            'provider_booking_ref' => 'SP-123',
        ]);

        $this->mock(SurpriceService::class, function ($mock): void {
            $mock->shouldReceive('cancelReservation')
                ->once()
                ->with('SP-123', 'Need to cancel')
                ->andReturn(['ok' => true]);
        });

        $response = $this
            ->actingAs($user)
            ->from(route('profile.bookings.all', ['locale' => 'en']))
            ->post(route('booking.cancel', ['locale' => 'en']), [
                'booking_id' => $booking->id,
                'cancellation_reason' => 'Need to cancel',
            ]);

        $response->assertRedirect();

        $booking->refresh();
        $this->assertSame('cancelled', $booking->booking_status);
        $this->assertStringContainsString('Surprice Cancel: cancellation requested.', (string) $booking->notes);
    }

    public function test_renteon_cancellation_uses_provider_service(): void
    {
        config(['vrooem.enabled' => false]);

        $user = User::factory()->create();
        $booking = $this->createExternalBooking($user, [
            'provider_source' => 'renteon',
            'provider_booking_ref' => 'RE-123',
        ]);

        $this->mock(RenteonService::class, function ($mock): void {
            $mock->shouldReceive('cancelBooking')
                ->once()
                ->with('RE-123')
                ->andReturn(['ok' => true]);
        });

        $response = $this
            ->actingAs($user)
            ->from(route('profile.bookings.all', ['locale' => 'en']))
            ->post(route('booking.cancel', ['locale' => 'en']), [
                'booking_id' => $booking->id,
                'cancellation_reason' => 'Need to cancel',
            ]);

        $response->assertRedirect();

        $booking->refresh();
        $this->assertSame('cancelled', $booking->booking_status);
        $this->assertStringContainsString('Renteon Cancel: cancellation requested.', (string) $booking->notes);
    }

    public function test_cancellation_is_blocked_when_free_cancellation_deadline_has_passed(): void
    {
        config(['vrooem.enabled' => false]);

        $user = User::factory()->create();
        $booking = $this->createExternalBooking($user, [
            'provider_source' => 'recordgo',
            'provider_metadata' => [
                'cancellation_deadline' => now()->subHour()->toIso8601String(),
            ],
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(route('booking.cancel', ['locale' => 'en']), [
                'booking_id' => $booking->id,
                'cancellation_reason' => 'Need to cancel',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'Free cancellation period has expired.'));

        $booking->refresh();
        $this->assertSame('confirmed', $booking->booking_status);
    }

    private function createExternalBooking(User $user, array $overrides = []): Booking
    {
        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => $user->email,
            'phone' => $user->phone,
            'driver_age' => 30,
        ]);

        return Booking::create(array_merge([
            'booking_number' => 'BKTEST-' . uniqid(),
            'customer_id' => $customer->id,
            'vehicle_id' => null,
            'provider_source' => 'recordgo',
            'provider_vehicle_id' => 'vehicle-1',
            'provider_booking_ref' => 'SUP-REF-1',
            'provider_metadata' => [],
            'vehicle_name' => 'Provider Vehicle',
            'pickup_date' => now()->addDay(),
            'return_date' => now()->addDays(2),
            'pickup_time' => '09:00',
            'return_time' => '09:00',
            'pickup_location' => 'Airport',
            'return_location' => 'Airport',
            'plan' => 'BAS',
            'total_days' => 1,
            'base_price' => 100,
            'extra_charges' => 0,
            'tax_amount' => 0,
            'total_amount' => 100,
            'pending_amount' => 100,
            'amount_paid' => 0,
            'booking_currency' => 'EUR',
            'payment_status' => 'partial',
            'booking_status' => 'confirmed',
        ], $overrides));
    }
}
