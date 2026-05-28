<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\AuthReturnUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login', ['locale' => 'en']));

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login', ['locale' => 'en']), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('profile.edit', ['locale' => 'en']));
    }

    public function test_login_screen_captures_search_return_url(): void
    {
        $returnTo = '/en/s?where=Dubai%20Airport&date_from=2026-06-24';

        $response = $this
            ->from($returnTo)
            ->get(route('login', ['locale' => 'en']));

        $response->assertStatus(200);
        $this->assertSame($returnTo, session(AuthReturnUrl::SESSION_KEY));
    }

    public function test_customer_returns_to_search_page_after_login_when_allowed(): void
    {
        $user = User::factory()->create();
        $returnTo = '/en/s?where=Dubai%20Airport&date_from=2026-06-24';

        $response = $this
            ->withSession([AuthReturnUrl::SESSION_KEY => $returnTo])
            ->post(route('login', ['locale' => 'en']), [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticated();
        $response->assertRedirect($returnTo);
    }

    public function test_customer_returns_to_booking_page_after_login_when_allowed(): void
    {
        $user = User::factory()->create();
        $returnTo = '/en/booking/status?state=quote_expired';

        $response = $this
            ->withSession([AuthReturnUrl::SESSION_KEY => $returnTo])
            ->post(route('login', ['locale' => 'en']), [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticated();
        $response->assertRedirect($returnTo);
    }

    public function test_login_ignores_non_search_or_booking_return_url(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->withSession([AuthReturnUrl::SESSION_KEY => '/en/contact'])
            ->post(route('login', ['locale' => 'en']), [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('profile.edit', ['locale' => 'en']));
    }

    public function test_login_ignores_external_return_url(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login', ['locale' => 'en']), [
            'email' => $user->email,
            'password' => 'password',
            'return_to' => 'https://example.com/en/s?where=Dubai',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('profile.edit', ['locale' => 'en']));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post(route('login', ['locale' => 'en']), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout', ['locale' => 'en']));

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
