<?php

namespace Tests\Unit;

use App\Http\Controllers\StripeCheckoutController;
use App\Models\Booking;
use App\Services\StripeBookingService;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class StripeCheckoutStatusReturnSearchTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_normalizes_stored_search_url_for_status_return_button(): void
    {
        $booking = new Booking([
            'provider_metadata' => [
                'return_search_url' => '/s?where=Athens+Airport&date_from=2026-08-20&date_to=2026-08-23',
            ],
        ]);

        $url = $this->resolveBookingReturnSearchUrl($booking);

        $this->assertSame('/en/s?where=Athens+Airport&date_from=2026-08-20&date_to=2026-08-23', $url);
    }

    public function test_it_rebuilds_search_url_from_booking_when_stored_url_is_missing(): void
    {
        $booking = new Booking([
            'provider_source' => 'surprice',
            'pickup_location' => 'Athens Airport',
            'return_location' => 'Athens Airport',
            'pickup_date' => '2026-08-20',
            'return_date' => '2026-08-23',
            'pickup_time' => '10:00:00',
            'return_time' => '10:00:00',
            'provider_metadata' => [
                'unified_location_id' => 12345,
                'provider_pickup_id' => 'ATH',
                'customer_snapshot' => [
                    'driver_age' => 35,
                ],
            ],
        ]);

        $url = $this->resolveBookingReturnSearchUrl($booking);
        $query = [];
        parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);

        $this->assertStringStartsWith('/en/s?', $url);
        $this->assertSame('Athens Airport', $query['where']);
        $this->assertSame('2026-08-20', $query['date_from']);
        $this->assertSame('2026-08-23', $query['date_to']);
        $this->assertSame('10:00', $query['start_time']);
        $this->assertSame('10:00', $query['end_time']);
        $this->assertSame('35', $query['age']);
        $this->assertSame('surprice', $query['provider']);
        $this->assertSame('ATH', $query['provider_pickup_id']);
        $this->assertSame('12345', $query['unified_location_id']);
    }

    private function resolveBookingReturnSearchUrl(Booking $booking): ?string
    {
        $controller = new StripeCheckoutController(Mockery::mock(StripeBookingService::class));
        $method = new ReflectionMethod($controller, 'resolveBookingReturnSearchUrl');
        $method->setAccessible(true);

        return $method->invoke($controller, $booking);
    }
}
