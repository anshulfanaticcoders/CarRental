<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StripeCheckoutStatusReturnSearchTest extends TestCase
{
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
}
