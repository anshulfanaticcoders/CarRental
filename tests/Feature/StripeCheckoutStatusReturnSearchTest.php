<?php

namespace Tests\Feature;

use App\Models\StripeCheckoutPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StripeCheckoutStatusReturnSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_page_uses_return_search_url_query_when_booking_is_missing(): void
    {
        $returnSearchUrl = urlencode('/s?where=Athens+Airport&date_from=2026-08-20&date_to=2026-08-23');

        $response = $this->get("/en/booking/status?state=quote_expired&return_search_url={$returnSearchUrl}");

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Booking/Status')
                ->where('state', 'quote_expired')
                ->where('search_url', '/en/s?where=Athens+Airport&date_from=2026-08-20&date_to=2026-08-23')
            );
    }

    public function test_status_page_reports_a_released_authorization_without_a_booking(): void
    {
        StripeCheckoutPayload::create([
            'stripe_session_id' => 'cs_released_without_booking',
            'payment_status' => 'authorization_released',
            'fulfilment_status' => 'manual_review',
        ]);

        $this->get('/en/booking/status?state=card_authorized_supplier_confirmation&session_id=cs_released_without_booking')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Booking/Status')
                ->where('state', 'authorization_released')
                ->where('booking', null)
            );
    }

    public function test_status_page_reports_an_authorization_that_needs_manual_review(): void
    {
        StripeCheckoutPayload::create([
            'stripe_session_id' => 'cs_authorized_review',
            'payment_status' => 'authorized',
            'fulfilment_status' => 'manual_review',
        ]);

        $this->get('/en/booking/status?state=card_authorized_supplier_confirmation&session_id=cs_authorized_review')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Booking/Status')
                ->where('state', 'authorization_review')
                ->where('booking', null)
            );
    }
}
