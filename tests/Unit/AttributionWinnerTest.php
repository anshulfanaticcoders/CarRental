<?php

namespace Tests\Unit;

use App\Http\Controllers\StripeCheckoutController;
use App\Models\Booking;
use App\Services\AwinService;
use App\Services\StripeBookingService;
use Illuminate\Http\Request;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

/**
 * One booking used to pay Awin (gross), Trabber (5% gross) and a QR affiliate
 * (3% net) simultaneously — no arbitration existed. Last click wins now, and
 * the Awin order value is configurable (default: money actually collected).
 */
class AttributionWinnerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function resolve(Request $request, array $trabber = [], array $skyscanner = []): array
    {
        $controller = new StripeCheckoutController(Mockery::mock(StripeBookingService::class));
        $method = new ReflectionMethod($controller, 'resolveAttributionWinner');

        return $method->invoke($controller, $request, $trabber, $skyscanner);
    }

    private function requestWithCookies(array $cookies): Request
    {
        return Request::create('/checkout', 'POST', [], $cookies);
    }

    public function test_the_most_recent_click_wins(): void
    {
        $result = $this->resolve(
            $this->requestWithCookies(['awc' => '1_2_abc', 'awc_at' => now()->subMinutes(5)->toIso8601String()]),
            ['trabber_clickid' => 'tc-1', 'trabber_clicked_at' => now()->subHours(2)->toIso8601String()],
        );

        $this->assertSame('awin', $result['winner']);
        $this->assertArrayHasKey('trabber', $result['candidates']);
    }

    public function test_a_channel_with_a_known_click_time_beats_an_undated_one(): void
    {
        $result = $this->resolve(
            $this->requestWithCookies(['awc' => '1_2_abc']), // legacy cookie, no awc_at
            ['trabber_clickid' => 'tc-1', 'trabber_clicked_at' => now()->subDays(3)->toIso8601String()],
        );

        $this->assertSame('trabber', $result['winner']);
    }

    public function test_no_candidates_means_no_winner(): void
    {
        $result = $this->resolve($this->requestWithCookies([]));

        $this->assertNull($result['winner']);
        $this->assertSame([], $result['candidates']);
    }

    public function test_awin_order_value_defaults_to_the_amount_actually_collected(): void
    {
        config(['awin.commission_base' => 'collected']);
        $booking = new Booking(['total_amount' => 500, 'amount_paid' => 75, 'provider_grand_total' => 420]);
        $this->assertSame(75.0, AwinService::commissionAmountFor($booking));

        config(['awin.commission_base' => 'gross']);
        $this->assertSame(500.0, AwinService::commissionAmountFor($booking));

        config(['awin.commission_base' => 'net']);
        $this->assertSame(420.0, AwinService::commissionAmountFor($booking));
    }
}
