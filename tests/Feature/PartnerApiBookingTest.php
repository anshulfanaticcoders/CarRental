<?php

namespace Tests\Feature;

use App\Models\ApiBooking;
use App\Models\ApiConsumer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VendorLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * First test coverage for the inbound partner API — the booking creator with
 * no Stripe safety net in front of it. Pins the money rules: full-datetime
 * day counting, loud rejection of unknown extras/insurance, honest EUR-only
 * quoting, idempotent cancels, and the status state machine.
 */
class PartnerApiBookingTest extends TestCase
{
    use RefreshDatabase;

    private function gatewayHeaders(): array
    {
        config([
            'vrooem.internal_api_token' => 'test-gateway-token',
            'vrooem.allow_legacy_partner_identity' => true,
        ]);

        return ['Authorization' => 'Bearer test-gateway-token'];
    }

    #[Test]
    public function partner_routes_reject_headerless_consumer_identity_by_default(): void
    {
        config([
            'vrooem.internal_api_token' => 'test-gateway-token',
            'vrooem.allow_legacy_partner_identity' => false,
        ]);

        $this->postJson('/api/internal/provider/vehicles/search', [], [
            'Authorization' => 'Bearer test-gateway-token',
        ])->assertStatus(401)->assertJsonPath('error.code', 'MISSING_API_KEY');
    }

    private function consumer(): ApiConsumer
    {
        return ApiConsumer::create([
            'name' => 'Partner Co', 'contact_name' => 'Partner Person',
            'contact_email' => uniqid('p').'@example.com', 'contact_phone' => '+123456789',
            'status' => 'active', 'mode' => 'live',
        ]);
    }

    private function vehicle(): Vehicle
    {
        $category = VehicleCategory::firstOrCreate(
            ['slug' => 'economy'],
            ['name' => 'Economy', 'description' => 'Economy vehicles', 'status' => true]
        );
        $vendor = User::factory()->create(['role' => 'vendor', 'status' => 'active']);
        $location = VendorLocation::create([
            'vendor_id' => $vendor->id, 'name' => 'Faro Airport', 'code' => 'vl-'.$vendor->id.'-fao',
            'address_line_1' => 'Faro Airport', 'city' => 'Faro', 'country' => 'Portugal',
            'country_code' => 'PT', 'latitude' => 37.0146, 'longitude' => -7.9659,
            'location_type' => 'airport', 'iata_code' => 'FAO', 'is_active' => true,
        ]);

        return Vehicle::create([
            'vendor_id' => $vendor->id, 'vendor_location_id' => $location->id,
            'category_id' => $category->id, 'brand' => 'Toyota', 'model' => 'Yaris',
            'transmission' => 'automatic', 'fuel' => 'petrol', 'body_style' => 'hatchback',
            'air_conditioning' => true, 'sipp_code' => 'ECAR', 'seating_capacity' => 5,
            'number_of_doors' => 4, 'luggage_capacity' => 2, 'horsepower' => 110,
            'co2' => '120', 'color' => 'white', 'mileage' => 20,
            'location' => 'Faro Airport', 'location_type' => 'airport',
            'latitude' => 37.0146, 'longitude' => -7.9659, 'city' => 'Faro',
            'country' => 'Portugal', 'full_vehicle_address' => 'Faro Airport, Portugal',
            'status' => 'available', 'features' => json_encode([]), 'featured' => false,
            'security_deposit' => 200, 'payment_method' => json_encode(['credit_card']),
            'guidelines' => 'g', 'terms_policy' => 't', 'price_per_day' => 50,
            'price_per_week' => 300, 'price_per_month' => 1000,
            'preferred_price_type' => 'day', 'pickup_times' => ['09:00'], 'return_times' => ['09:00'],
        ]);
    }

    private function bookingPayload(Vehicle $vehicle, ApiConsumer $consumer, array $overrides = []): array
    {
        return array_merge([
            'api_consumer_id' => $consumer->id,
            'vehicle_id' => $vehicle->id,
            'driver' => [
                'first_name' => 'Test', 'last_name' => 'Driver',
                'email' => 'driver@example.com', 'phone' => '+123456789',
                'age' => 35, 'driving_license_number' => 'DL123456',
                'driving_license_country' => 'PT',
            ],
            'pickup_date' => now()->addDays(10)->toDateString(),
            'pickup_time' => '09:00',
            'dropoff_date' => now()->addDays(12)->toDateString(),
            'dropoff_time' => '18:00',
        ], $overrides);
    }

    #[Test]
    public function rental_days_are_counted_from_the_full_datetimes(): void
    {
        // Mon 09:00 → Wed 18:00 is 3 rental days by every rental convention;
        // date-only diffing billed 2 and the vendor supplied the third free.
        $response = $this->postJson('/api/internal/provider/bookings',
            $this->bookingPayload($this->vehicle(), $this->consumer()),
            $this->gatewayHeaders());

        $response->assertCreated();
        $this->assertSame(3, ApiBooking::first()->total_days);
        $this->assertSame('150.00', (string) ApiBooking::first()->total_amount); // 3 × 50
    }

    #[Test]
    public function an_inverted_same_day_window_is_rejected(): void
    {
        $vehicle = $this->vehicle();
        $day = now()->addDays(10)->toDateString();

        $this->postJson('/api/internal/provider/bookings',
            $this->bookingPayload($vehicle, $this->consumer(), [
                'pickup_date' => $day, 'pickup_time' => '17:00',
                'dropoff_date' => $day, 'dropoff_time' => '09:00',
            ]),
            $this->gatewayHeaders())
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_RENTAL_WINDOW');
    }

    #[Test]
    public function an_unknown_extra_is_rejected_loudly_not_silently_dropped(): void
    {
        $this->postJson('/api/internal/provider/bookings',
            $this->bookingPayload($this->vehicle(), $this->consumer(), [
                'extras' => [['extra_id' => 999999, 'quantity' => 1]],
            ]),
            $this->gatewayHeaders())
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'UNKNOWN_EXTRA');

        $this->assertSame(0, ApiBooking::count());
    }

    #[Test]
    public function unpriceable_insurance_is_refused_instead_of_stored_for_free(): void
    {
        $this->postJson('/api/internal/provider/bookings',
            $this->bookingPayload($this->vehicle(), $this->consumer(), ['insurance_id' => 5]),
            $this->gatewayHeaders())
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INSURANCE_NOT_SUPPORTED');
    }

    #[Test]
    public function cancelling_twice_is_idempotent(): void
    {
        $consumer = $this->consumer();
        $this->postJson('/api/internal/provider/bookings',
            $this->bookingPayload($this->vehicle(), $consumer),
            $this->gatewayHeaders())->assertCreated();
        $bookingNumber = ApiBooking::first()->booking_number;

        $this->postJson("/api/internal/provider/bookings/{$bookingNumber}/cancel?api_consumer_id={$consumer->id}",
            ['reason' => 'test'], $this->gatewayHeaders())->assertOk();

        // The retry after a network timeout must read as success, not failure.
        $this->postJson("/api/internal/provider/bookings/{$bookingNumber}/cancel?api_consumer_id={$consumer->id}",
            ['reason' => 'test'], $this->gatewayHeaders())
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    #[Test]
    public function search_refuses_non_eur_instead_of_relabeling_amounts(): void
    {
        $vehicle = $this->vehicle();

        $this->postJson('/api/internal/provider/vehicles/search', [
            'pickup_location_id' => $vehicle->vendor_location_id,
            'pickup_date' => now()->addDays(10)->toDateString(),
            'pickup_time' => '09:00',
            'dropoff_date' => now()->addDays(12)->toDateString(),
            'dropoff_time' => '09:00',
            'currency' => 'USD',
        ], $this->gatewayHeaders())
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'UNSUPPORTED_CURRENCY');
    }

    #[Test]
    public function stale_pending_bookings_are_expired_after_their_pickup_passes(): void
    {
        $consumer = $this->consumer();
        $vehicle = $this->vehicle();

        $stale = ApiBooking::create([
            'booking_number' => ApiBooking::generateBookingNumber(),
            'api_consumer_id' => $consumer->id, 'vehicle_id' => $vehicle->id,
            'vehicle_name' => 'Toyota Yaris',
            'driver_first_name' => 'T', 'driver_last_name' => 'D',
            'driver_email' => 'd@example.com', 'driver_phone' => '+1', 'driver_age' => 30,
            'driver_license_number' => 'DL1', 'driver_license_country' => 'PT',
            'pickup_date' => now()->subDay(), 'pickup_time' => '09:00',
            'return_date' => now()->addDay(), 'return_time' => '09:00',
            'pickup_location' => 'Faro', 'return_location' => 'Faro',
            'total_days' => 2, 'daily_rate' => 50, 'base_price' => 100,
            'extras_total' => 0, 'total_amount' => 100, 'currency' => 'EUR',
            'status' => 'pending',
        ]);
        $future = ApiBooking::create(array_merge($stale->only([
            'api_consumer_id', 'vehicle_id', 'vehicle_name', 'driver_first_name', 'driver_last_name',
            'driver_email', 'driver_phone', 'driver_age', 'driver_license_number', 'driver_license_country',
            'pickup_time', 'return_time', 'pickup_location', 'return_location',
            'total_days', 'daily_rate', 'base_price', 'extras_total', 'total_amount', 'currency',
        ]), [
            'booking_number' => ApiBooking::generateBookingNumber(),
            'pickup_date' => now()->addDays(5), 'return_date' => now()->addDays(7),
            'status' => 'pending',
        ]));
        $laterToday = ApiBooking::create(array_merge($stale->only([
            'api_consumer_id', 'vehicle_id', 'vehicle_name', 'driver_first_name', 'driver_last_name',
            'driver_email', 'driver_phone', 'driver_age', 'driver_license_number', 'driver_license_country',
            'return_time', 'pickup_location', 'return_location', 'total_days', 'daily_rate', 'base_price',
            'extras_total', 'total_amount', 'currency',
        ]), [
            'booking_number' => ApiBooking::generateBookingNumber(),
            'pickup_date' => now()->toDateString(),
            'pickup_time' => now()->addHours(2)->format('H:i'),
            'return_date' => now()->addDay(),
            'status' => 'pending',
        ]));

        $this->artisan('api-bookings:expire-stale-pending')->assertSuccessful();

        $this->assertSame('cancelled', $stale->fresh()->status);
        $this->assertSame('pending', $future->fresh()->status);
        $this->assertSame('pending', $laterToday->fresh()->status);
    }

    #[Test]
    public function search_rejects_an_inverted_same_day_window(): void
    {
        $vehicle = $this->vehicle();
        $day = now()->addDays(5)->toDateString();

        $this->postJson('/api/internal/provider/vehicles/search', [
            'pickup_location_id' => $vehicle->vendor_location_id,
            'pickup_date' => $day,
            'pickup_time' => '17:00',
            'dropoff_date' => $day,
            'dropoff_time' => '09:00',
        ], $this->gatewayHeaders())
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_RENTAL_WINDOW');
    }

    #[Test]
    public function a_valid_api_key_overrides_the_client_asserted_consumer_id(): void
    {
        // Identity used to be whatever api_consumer_id the caller typed —
        // anyone with the shared gateway token could book as any partner.
        $realConsumer = $this->consumer();
        $victimConsumer = $this->consumer();
        $plaintext = 'vrm_live_'.bin2hex(random_bytes(20));
        \App\Models\ApiKey::create([
            'api_consumer_id' => $realConsumer->id,
            'key_hash' => hash('sha256', $plaintext),
            'key_prefix' => substr($plaintext, 0, 12),
            'name' => 'Test', 'status' => 'active',
            'scopes' => ['vehicles:search', 'bookings:create', 'bookings:read', 'bookings:cancel'],
        ]);

        $this->postJson('/api/internal/provider/bookings',
            $this->bookingPayload($this->vehicle(), $victimConsumer), // lies about identity
            $this->gatewayHeaders() + ['X-Api-Key' => $plaintext])
            ->assertCreated();

        $this->assertSame($realConsumer->id, ApiBooking::first()->api_consumer_id,
            'The booking must belong to the KEY owner, not the claimed id.');
    }

    #[Test]
    public function a_revoked_key_is_rejected_and_a_suspended_consumer_cannot_book(): void
    {
        $consumer = $this->consumer();
        $plaintext = 'vrm_live_'.bin2hex(random_bytes(20));
        $key = \App\Models\ApiKey::create([
            'api_consumer_id' => $consumer->id,
            'key_hash' => hash('sha256', $plaintext),
            'key_prefix' => substr($plaintext, 0, 12),
            'name' => 'Test', 'status' => 'revoked',
            'scopes' => ['bookings:create'],
        ]);

        $this->postJson('/api/internal/provider/bookings',
            $this->bookingPayload($this->vehicle(), $consumer),
            $this->gatewayHeaders() + ['X-Api-Key' => $plaintext])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'INVALID_API_KEY');

        // Suspension must block even the legacy keyless path.
        $consumer->update(['status' => 'suspended']);
        $this->postJson('/api/internal/provider/bookings',
            $this->bookingPayload($this->vehicle(), $consumer),
            $this->gatewayHeaders())
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'CONSUMER_SUSPENDED');
    }

    #[Test]
    public function a_configured_partner_markup_is_recorded_as_settlement(): void
    {
        config(['vrooem.partner_markup_percent' => 10]);

        $this->postJson('/api/internal/provider/bookings',
            $this->bookingPayload($this->vehicle(), $this->consumer()),
            $this->gatewayHeaders())
            ->assertCreated();

        $booking = ApiBooking::first();
        $this->assertSame('150.00', (string) $booking->vendor_net);       // 3 days × 50
        $this->assertSame('15.00', (string) $booking->platform_commission);
        $this->assertSame('165.00', (string) $booking->total_amount);
    }

    #[Test]
    public function a_late_cancellation_records_the_advertised_fee(): void
    {
        $consumer = $this->consumer();
        $vehicle = $this->vehicle();
        \App\Models\VehicleBenefit::create([
            'vehicle_id' => $vehicle->id,
            'cancellation_available_per_day' => true,
            'cancellation_available_per_day_date' => 3, // free until 3 days before pickup
            'cancellation_fee_per_day' => 40,
        ]);

        $this->postJson('/api/internal/provider/bookings',
            $this->bookingPayload($vehicle, $consumer, [
                'pickup_date' => now()->addDay()->toDateString(), // inside the fee window
                'dropoff_date' => now()->addDays(3)->toDateString(),
            ]),
            $this->gatewayHeaders())->assertCreated();
        $bookingNumber = ApiBooking::first()->booking_number;

        $this->postJson("/api/internal/provider/bookings/{$bookingNumber}/cancel?api_consumer_id={$consumer->id}",
            ['reason' => 'late cancel'], $this->gatewayHeaders())
            ->assertOk()
            ->assertJsonPath('data.cancellation_fee', 40);

        $this->assertSame('40.00', (string) ApiBooking::first()->cancellation_fee);
    }

    #[Test]
    public function a_status_change_notifies_the_partner_webhook(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $consumer = $this->consumer();
        $consumer->update(['webhook_url' => 'https://partner.example.com/webhooks/vrooem', 'webhook_secret' => 's3cret']);

        $booking = ApiBooking::create([
            'booking_number' => ApiBooking::generateBookingNumber(),
            'api_consumer_id' => $consumer->id, 'vehicle_id' => $this->vehicle()->id,
            'vehicle_name' => 'Toyota Yaris',
            'driver_first_name' => 'T', 'driver_last_name' => 'D',
            'driver_email' => 'd@example.com', 'driver_phone' => '+1', 'driver_age' => 30,
            'driver_license_number' => 'DL1', 'driver_license_country' => 'PT',
            'pickup_date' => now()->addDays(3), 'pickup_time' => '09:00',
            'return_date' => now()->addDays(5), 'return_time' => '09:00',
            'pickup_location' => 'Faro', 'return_location' => 'Faro',
            'total_days' => 2, 'daily_rate' => 50, 'base_price' => 100,
            'extras_total' => 0, 'total_amount' => 100, 'currency' => 'EUR',
            'status' => 'pending',
        ]);

        $booking->update(['status' => 'cancelled', 'cancellation_reason' => 'car broke down']);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendPartnerBookingWebhook::class,
            fn ($job) => $job->apiBookingId === $booking->id && $job->event === 'booking.cancelled');
    }

    #[Test]
    public function a_cancelled_booking_cannot_be_resurrected(): void
    {
        $booking = new ApiBooking(['status' => 'cancelled']);

        $this->assertFalse($booking->canTransitionTo('confirmed'));
        $this->assertFalse($booking->canTransitionTo('completed'));
        $this->assertTrue((new ApiBooking(['status' => 'pending']))->canTransitionTo('confirmed'));
        $this->assertTrue((new ApiBooking(['status' => 'confirmed']))->canTransitionTo('completed'));
        $this->assertFalse((new ApiBooking(['status' => 'completed']))->canTransitionTo('confirmed'));
    }
}
