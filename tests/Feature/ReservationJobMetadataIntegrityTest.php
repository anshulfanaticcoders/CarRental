<?php

namespace Tests\Feature;

use App\Jobs\TriggerProviderReservationJob;
use App\Services\StripeBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * When the checkout payload row is gone (pruned after 7 days, or the payload
 * merge was skipped), $metadata used to still be a Stripe\StripeObject at
 * dispatch time — and (array) on a StripeObject yields internal
 * "\0*\0_values" garbage instead of keys. The reservation job then saw no
 * gateway_vehicle_id and burned all 5 retries into reservation_failed on a
 * perfectly recoverable booking.
 */
class ReservationJobMetadataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_stripe_object_metadata_session_dispatches_a_job_with_clean_keys(): void
    {
        Queue::fake();
        Notification::fake();

        // Real Stripe SDK object, no extras_payload row — the exact shape the
        // webhook sees when the payload row no longer exists.
        $session = (object) [
            'id' => 'cs_test_stripeobject_metadata',
            'payment_intent' => 'pi_test_stripeobject_metadata',
            'metadata' => \Stripe\StripeObject::constructFrom([
                'vehicle_source' => 'greenmotion',
                'vehicle_id' => 'gw_77',
                'gateway_vehicle_id' => 'gw_77',
                'customer_name' => 'Stripe Object',
                'customer_email' => 'stripeobject@example.com',
                'customer_phone' => '+27829999321',
                'customer_driver_age' => 35,
                'number_of_days' => 3,
                'total_amount' => 100,
                'payable_amount' => 15,
                'pending_amount' => 85,
                'currency' => 'EUR',
                'pickup_date' => now()->addDays(5)->format('Y-m-d'),
                'dropoff_date' => now()->addDays(8)->format('Y-m-d'),
                'pickup_time' => '10:00',
                'dropoff_time' => '10:00',
                'pickup_location' => 'Faro Airport',
                'dropoff_location' => 'Faro Airport',
            ]),
        ];

        $booking = app(StripeBookingService::class)->createBookingFromSession($session);

        $this->assertNotNull($booking);

        Queue::assertPushed(TriggerProviderReservationJob::class, function ($job) {
            foreach (array_keys($job->metadata) as $key) {
                $this->assertStringNotContainsString("\0", $key,
                    'Job metadata contains a mangled cast key: '.addslashes($key));
            }

            return $job->metadata['gateway_vehicle_id'] === 'gw_77'
                && $job->metadata['vehicle_source'] === 'greenmotion';
        });
    }
}
