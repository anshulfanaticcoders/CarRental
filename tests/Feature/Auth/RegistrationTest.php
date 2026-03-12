<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register', ['locale' => 'en']));

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post(route('register', ['locale' => 'en']), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'phone_code' => '+1',
            'password' => 'password',
            'password_confirmation' => 'password',
            'date_of_birth' => '1990-01-01',
            'address' => '123 Test Street',
            'postcode' => '10001',
            'city' => 'New York',
            'country' => 'US',
            'currency' => 'USD',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('profile.edit', ['locale' => 'en']));
    }
}
