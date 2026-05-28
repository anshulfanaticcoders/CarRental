<?php

namespace Tests\Unit;

use App\Support\AuthReturnUrl;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class AuthReturnUrlTest extends TestCase
{
    public function test_it_allows_search_and_booking_paths(): void
    {
        $request = Request::create('https://vrooem.test/en/login');

        $this->assertSame(
            '/en/s?where=Dubai%20Airport',
            AuthReturnUrl::normalize($request, '/en/s?where=Dubai%20Airport')
        );
        $this->assertSame(
            '/en/booking/status?state=quote_expired',
            AuthReturnUrl::normalize($request, '/en/booking/status?state=quote_expired')
        );
    }

    public function test_it_rejects_other_paths_and_external_hosts(): void
    {
        $request = Request::create('https://vrooem.test/en/login');

        $this->assertNull(AuthReturnUrl::normalize($request, '/en/profile'));
        $this->assertNull(AuthReturnUrl::normalize($request, 'https://example.com/en/s?where=Dubai'));
        $this->assertNull(AuthReturnUrl::normalize($request, '//example.com/en/s?where=Dubai'));
    }
}
