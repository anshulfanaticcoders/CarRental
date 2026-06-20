<?php

namespace Tests\Feature\Auth;

use App\Models\Affiliate\AffiliateBusiness;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.request', ['locale' => 'en']));

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email', ['locale' => 'en']), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_link_can_be_requested_for_legacy_affiliate_business_email(): void
    {
        Notification::fake();

        $business = AffiliateBusiness::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Legacy Partner',
            'business_type' => 'hotel',
            'contact_email' => 'legacy.partner@example.test',
            'contact_phone' => '+971501777777',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'currency' => 'EUR',
            'verification_status' => 'verified',
            'status' => 'active',
        ]);

        $response = $this->from(route('password.request', ['locale' => 'en']))
            ->post(route('password.email', ['locale' => 'en']), [
                'email' => $business->contact_email,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status', trans(Password::RESET_LINK_SENT));

        $user = User::where('email', $business->contact_email)->firstOrFail();

        $this->assertSame('affiliate', $user->role);
        $this->assertSame($user->id, $business->fresh()->user_id);
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email', ['locale' => 'en']), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get(route('password.reset', [
                'locale' => 'en',
                'token' => $notification->token,
            ]));

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email', ['locale' => 'en']), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post(route('password.store', ['locale' => 'en']), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login', ['locale' => 'en']));

            return true;
        });
    }
}
