<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Services\BookingAmountService;
use App\Services\CurrencyConversionService;
use App\Services\OfferService;
use App\Services\StripeBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Once the customer has paid and the booking row exists, NOTHING may destroy it.
 *
 * Booking creation, offers, amounts, the payment record and extras all ran in one
 * transaction, so a throw in any of them rolled the booking back and left a
 * captured charge with nothing attached. Each is now guarded: the failure is
 * recorded and flagged for admin, and the booking survives.
 */
class PaidBookingSurvivesStepFailureTest extends TestCase
{
    use RefreshDatabase;

    private function paidSession(string $id, array $overrides = []): object
    {
        $metadata = array_merge([
            'vehicle_source' => 'greenmotion',
            'vehicle_id' => 'gw_survive',
            'customer_name' => 'Survives Failure',
            'customer_email' => 'survives@example.com',
            'customer_phone' => '+27829990001',
            'customer_driver_age' => 35,
            'pickup_date' => now()->addDays(20)->toDateString(),
            'pickup_time' => '10:00',
            'dropoff_date' => now()->addDays(23)->toDateString(),
            'dropoff_time' => '10:00',
            'pickup_location' => 'Faro Airport',
            'dropoff_location' => 'Faro Airport',
            'number_of_days' => 3,
            'total_amount' => 100,
            'payable_amount' => 15,
            'pending_amount' => 85,
            'currency' => 'EUR',
            'package' => 'BAS',
        ], $overrides);

        return (object) [
            'id' => $id,
            'payment_intent' => 'pi_'.$id,
            'metadata' => new class($metadata)
            {
                public $extras_payload_id = null;

                public function __construct(private array $data) {}

                public function toArray(): array
                {
                    return $this->data;
                }

                public function __get($name)
                {
                    return $this->data[$name] ?? null;
                }

                public function __isset($name)
                {
                    return array_key_exists($name, $this->data);
                }
            },
        ];
    }

    private function book(string $id, array $overrides = []): ?Booking
    {
        Queue::fake();
        Notification::fake();

        return app(StripeBookingService::class)->createBookingFromSession($this->paidSession($id, $overrides));
    }

    #[Test]
    public function booking_survives_a_failing_offer_sync(): void
    {
        $this->mock(OfferService::class, fn ($m) => $m->shouldReceive('syncBookingOffers')
            ->andThrow(new \RuntimeException('offer service exploded')));

        $booking = $this->book('cs_test_offers_blow_up');

        $this->assertNotNull($booking, 'A paid booking must survive a failing offer sync.');
        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
        $this->assertStringContainsString('offers', (string) $booking->fresh()->notes);
    }

    #[Test]
    public function booking_survives_a_failing_amount_service(): void
    {
        $this->mock(BookingAmountService::class, fn ($m) => $m->shouldReceive('createForBooking')
            ->andThrow(new \RuntimeException('amount service exploded')));

        $booking = $this->book('cs_test_amounts_blow_up');

        $this->assertNotNull($booking, 'A paid booking must survive a failing amount service.');
        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
        $this->assertStringContainsString('booking_amounts', (string) $booking->fresh()->notes);
    }

    /** Extras priced in a different currency to the booking, which forces a conversion call. */
    private function extrasNeedingConversion(): array
    {
        return [
            'provider_currency' => 'GBP',
            'currency' => 'EUR',
            'extras_data' => json_encode([
                ['id' => 'gps', 'name' => 'GPS', 'qty' => 1, 'total' => 20],
            ]),
        ];
    }

    #[Test]
    public function booking_survives_a_failing_currency_api_during_extras(): void
    {
        // The exchange-rate call is an outbound HTTP request made while the
        // transaction is open — a provider outage must not cost a paid booking.
        $this->mock(CurrencyConversionService::class, fn ($m) => $m->shouldReceive('convert')
            ->andThrow(new \RuntimeException('exchange rate api down')));

        $booking = $this->book('cs_test_currency_blow_up', $this->extrasNeedingConversion());

        $this->assertNotNull($booking, 'A paid booking must survive an exchange-rate API outage.');
        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
        $this->assertStringContainsString('extras', (string) $booking->fresh()->notes);
    }

    #[Test]
    public function the_conversion_path_is_actually_exercised(): void
    {
        // Guards the test above: without this, mocking a service that never gets
        // called would make that test pass for the wrong reason.
        // atLeast once, not exactly once: the amount tiers convert as well.
        $this->mock(CurrencyConversionService::class, fn ($m) => $m->shouldReceive('convert')
            ->atLeast()->once()
            ->andReturn(['success' => true, 'converted_amount' => 23.5]));

        $booking = $this->book('cs_test_conversion_runs', $this->extrasNeedingConversion());

        $this->assertDatabaseHas('booking_extras', [
            'booking_id' => $booking->id,
            'extra_name' => 'GPS',
            'price' => 23.5,
        ]);
    }

    #[Test]
    public function extras_survive_an_fx_outage_when_the_checkout_rate_is_stored(): void
    {
        // Checkout already computed the provider→booking rate (and now fails
        // closed if it can't). With the rate in metadata the extras step needs
        // no FX HTTP call at all — a rates outage during booking creation must
        // not cost the customer their paid child seat on the supplier reservation.
        $this->mock(CurrencyConversionService::class, fn ($m) => $m->shouldReceive('convert')
            ->andThrow(new \RuntimeException('exchange rate api down')));

        $booking = $this->book('cs_test_rate_stored', array_merge(
            $this->extrasNeedingConversion(),
            ['exchange_rate_provider_to_booking' => 1.175]
        ));

        $this->assertDatabaseHas('booking_extras', [
            'booking_id' => $booking->id,
            'extra_name' => 'GPS',
            'price' => 23.5, // 20 GBP × stored rate 1.175
        ]);
        $this->assertStringNotContainsString('extras', (string) $booking->fresh()->notes);
    }

    #[Test]
    public function a_healthy_session_is_not_flagged(): void
    {
        // The guards must stay silent when everything works, or the flag is noise.
        $booking = $this->book('cs_test_all_healthy');

        $this->assertNotNull($booking);
        $this->assertStringNotContainsString('NEEDS CORRECTION', (string) $booking->fresh()->notes);
    }

    #[Test]
    public function the_payment_record_is_still_written_on_a_healthy_session(): void
    {
        // Guarding a step must not mean skipping it.
        $booking = $this->book('cs_test_payment_written');

        $this->assertDatabaseHas('booking_payments', [
            'booking_id' => $booking->id,
            'transaction_id' => 'pi_cs_test_payment_written',
            'payment_status' => 'succeeded',
        ]);
    }
}
