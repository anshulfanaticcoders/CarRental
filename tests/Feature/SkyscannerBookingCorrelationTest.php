<?php

namespace Tests\Feature;

use App\Services\Skyscanner\CarHireBookingCorrelationService;
use App\Services\Skyscanner\CarHireTrackingService;
use App\Services\StripeBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The correlation service existed with zero callers — Skyscanner saw traffic
 * and zero conversions. A booking whose checkout metadata carries the
 * redirect id must now land in the partner-facing correlation store.
 */
class SkyscannerBookingCorrelationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_booking_with_a_skyscanner_redirect_id_is_correlated(): void
    {
        Queue::fake();
        Notification::fake();

        app(CarHireTrackingService::class)->rememberRedirectCorrelation('rid-123', 'quote-123', [
            'case_id' => 'PSM-46100',
            'provider_vehicle_id' => '327',
        ]);

        $booking = app(StripeBookingService::class)->createBookingFromSession((object) [
            'id' => 'cs_sky_corr_1',
            'payment_intent' => 'pi_sky_corr_1',
            'metadata' => \Stripe\StripeObject::constructFrom([
                'vehicle_source' => 'greenmotion',
                'vehicle_id' => 'gw_9',
                'gateway_vehicle_id' => 'gw_9',
                'customer_name' => 'Sky Customer',
                'customer_email' => 'sky@example.com',
                'customer_phone' => '+27829990002',
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
                'skyscanner_redirectid' => 'rid-123',
                'skyscanner_quote_id' => 'quote-123',
            ]),
        ]);

        $correlation = app(CarHireBookingCorrelationService::class)->getBookingCorrelation('rid-123');

        $this->assertNotNull($correlation, 'The booking must be visible to Skyscanner reporting.');
        $this->assertSame($booking->booking_number, $correlation['booking_reference']);
        $this->assertSame('quote-123', $correlation['quote_id']);
        $this->assertSame('confirmed', $correlation['booking_status']);
    }

    #[Test]
    public function a_booking_without_skyscanner_attribution_correlates_nothing(): void
    {
        Queue::fake();
        Notification::fake();

        app(StripeBookingService::class)->createBookingFromSession((object) [
            'id' => 'cs_sky_corr_2',
            'payment_intent' => 'pi_sky_corr_2',
            'metadata' => \Stripe\StripeObject::constructFrom([
                'vehicle_source' => 'greenmotion',
                'vehicle_id' => 'gw_9',
                'customer_name' => 'Plain Customer',
                'customer_email' => 'plain@example.com',
                'customer_phone' => '+27829990003',
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
        ]);

        $this->assertSame([], app(CarHireBookingCorrelationService::class)->allBookingCorrelations());
    }
}
