<?php

namespace Tests\Feature;

use App\Jobs\TriggerProviderReservationJob;
use App\Models\StripeCheckoutPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards the "invalid customer data reaches a supplier after payment" class of
 * bug (the Marika incident). Every rule here must reject BEFORE any money moves.
 */
class CheckoutCustomerValidationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $customerOverrides = [], array $vehicleOverrides = []): array
    {
        return [
            'vehicle' => array_merge(['source' => 'greenmotion', 'gateway_vehicle_id' => 'gw_1'], $vehicleOverrides),
            'package' => 'BAS',
            'customer' => array_merge([
                'name' => 'Diag Test',
                'email' => 'diag.test@example.com',
                'phone' => '+27821234567',
                'driver_age' => 35,
                'driver_license_number' => '8801015800083',
                'driving_license_country' => 'ZA',
                'address' => '1 Test Road',
                'city' => 'Cape Town',
                'postal_code' => '8001',
                'country' => 'South Africa',
            ], $customerOverrides),
            'pickup_date' => now()->addDays(30)->toDateString(),
            'pickup_time' => '10:00',
            'dropoff_date' => now()->addDays(33)->toDateString(),
            'dropoff_time' => '10:00',
            'pickup_location' => 'Dubai Airport',
            'dropoff_location' => 'Dubai Airport',
            'total_amount' => 100,
            'currency' => 'EUR',
            'number_of_days' => 3,
        ];
    }

    private function checkout(array $customerOverrides = [], array $vehicleOverrides = [])
    {
        return $this->postJson(route('api.stripe.checkout'), $this->payload($customerOverrides, $vehicleOverrides));
    }

    #[Test]
    public function single_word_name_is_rejected(): void
    {
        $this->checkout(['name' => 'Madonna'])
            ->assertStatus(422)
            ->assertJson(['invalid_fields' => ['name']]);
    }

    #[Test]
    public function junk_phone_is_rejected(): void
    {
        foreach (['aaa', '+12', '12345678901234567890'] as $phone) {
            $this->checkout(['phone' => $phone])
                ->assertStatus(422)
                ->assertJson(['invalid_fields' => ['phone']]);
        }
    }

    #[Test]
    public function malformed_email_is_rejected(): void
    {
        $this->checkout(['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer.email']);
    }

    #[Test]
    public function out_of_bounds_driver_age_is_rejected(): void
    {
        foreach ([12, 120] as $age) {
            $this->checkout(['driver_age' => $age])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['customer.driver_age']);
        }
    }

    #[Test]
    public function junk_licence_is_rejected_for_yesaway_and_okmobility(): void
    {
        foreach (['yesaway', 'okmobility'] as $source) {
            $this->checkout(['driver_license_number' => '13;'], ['source' => $source])
                ->assertStatus(422)
                ->assertJson(['invalid_fields' => ['driver_license_number']]);
        }
    }

    #[Test]
    public function missing_licence_country_is_rejected_for_licence_providers(): void
    {
        $this->checkout(['driving_license_country' => ''])
            ->assertStatus(422);
        $this->assertContains('driving_license_country', $this->checkout(['driving_license_country' => ''])->json('missing_fields'));
    }

    #[Test]
    public function whitespace_only_address_is_missing_for_greenmotion(): void
    {
        $response = $this->checkout(['address' => '   ']);

        $response->assertStatus(422);
        $this->assertContains('address', $response->json('missing_fields'));
    }

    #[Test]
    public function oversized_notes_are_rejected(): void
    {
        $this->checkout(['notes' => str_repeat('x', 500)])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer.notes']);
    }

    #[Test]
    public function post_payment_identity_conflict_keeps_booking_as_unlinked_guest_and_never_throws(): void
    {
        // An account with the checkout email appears while the guest is on
        // Stripe. The PAID booking must survive (never throw) but must NOT be
        // auto-attached to that account from unauthenticated input.
        Queue::fake();
        Notification::fake();

        $existingUser = \App\Models\User::create([
            'first_name' => 'Existing',
            'last_name' => 'Account',
            'email' => 'race@example.com',
            'phone' => '+27829999999',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'status' => 'active',
        ]);

        $session = (object) [
            'id' => 'cs_test_identity_race',
            'payment_intent' => 'pi_test_identity_race',
            'metadata' => new class
            {
                public $extras_payload_id = null;

                public function toArray(): array
                {
                    return [
                        'vehicle_source' => 'greenmotion',
                        'vehicle_id' => 'gw_2',
                        'gateway_vehicle_id' => 'gw_2',
                        'gateway_search_id' => 'gws-2',
                        'customer_name' => 'Race Customer',
                        'customer_email' => 'race@example.com',
                        'customer_phone' => '+27829999999',
                        'customer_driver_age' => 35,
                        'pickup_date' => now()->addDays(30)->toDateString(),
                        'pickup_time' => '10:00',
                        'dropoff_date' => now()->addDays(33)->toDateString(),
                        'dropoff_time' => '10:00',
                        'pickup_location' => 'Dubai Airport',
                        'dropoff_location' => 'Dubai Airport',
                        'number_of_days' => 3,
                        'total_amount' => 100,
                        'payable_amount' => 15,
                        'pending_amount' => 85,
                        'currency' => 'EUR',
                        'package' => 'BAS',
                    ];
                }

                public function __get($name)
                {
                    return $this->toArray()[$name] ?? null;
                }

                public function __isset($name)
                {
                    return array_key_exists($name, $this->toArray());
                }
            },
        ];

        $booking = app(\App\Services\StripeBookingService::class)->createBookingFromSession($session);

        // Booking is kept (not lost) but NOT linked to the existing account —
        // it gets its own fresh guest account instead.
        $this->assertNotNull($booking);
        $this->assertNotNull($booking->customer->user_id);
        $this->assertNotSame($existingUser->id, (int) $booking->customer->user_id);
    }

    #[Test]
    public function licence_survives_stripe_metadata_compaction_via_full_metadata(): void
    {
        // If Stripe's 50-key cap drops driver_license_number from session
        // metadata, the payload record's full_metadata must restore it before
        // the supplier reservation is triggered.
        Queue::fake();
        Notification::fake();

        $payloadRecord = StripeCheckoutPayload::create([
            'stripe_session_id' => 'cs_test_compaction',
            'payload' => [
                'full_metadata' => [
                    'vehicle_source' => 'greenmotion',
                    'vehicle_id' => 'gw_1',
                    'gateway_vehicle_id' => 'gw_1',
                    'gateway_search_id' => 'gws-1',
                    'customer_name' => 'Diag Test',
                    'customer_email' => 'diag.test@example.com',
                    'customer_phone' => '+27821234567',
                    'customer_driver_age' => 35,
                    'driver_license_number' => '8801015800083',
                    'driving_license_country' => 'ZA',
                    'customer_address' => '1 Test Road',
                    'customer_city' => 'Cape Town',
                    'customer_postal_code' => '8001',
                    'customer_country' => 'South Africa',
                    'pickup_date' => now()->addDays(30)->toDateString(),
                    'pickup_time' => '10:00',
                    'dropoff_date' => now()->addDays(33)->toDateString(),
                    'dropoff_time' => '10:00',
                    'pickup_location' => 'Dubai Airport',
                    'dropoff_location' => 'Dubai Airport',
                    'number_of_days' => 3,
                    'total_amount' => 100,
                    'payable_amount' => 15,
                    'pending_amount' => 85,
                    'currency' => 'EUR',
                    'package' => 'BAS',
                ],
            ],
        ]);

        // Stripe metadata WITHOUT the licence (simulating key-cap compaction).
        $session = (object) [
            'id' => 'cs_test_compaction',
            'payment_intent' => 'pi_test_compaction',
            'metadata' => new class($payloadRecord->id)
            {
                public function __construct(public $extras_payload_id) {}

                public function toArray(): array
                {
                    return ['extras_payload_id' => (string) $this->extras_payload_id];
                }
            },
        ];

        $booking = app(\App\Services\StripeBookingService::class)->createBookingFromSession($session);

        $this->assertNotNull($booking);
        Queue::assertPushed(TriggerProviderReservationJob::class, function ($job) {
            return ($job->metadata['driver_license_number'] ?? null) === '8801015800083'
                && ($job->metadata['driving_license_country'] ?? null) === 'ZA';
        });
    }
}
