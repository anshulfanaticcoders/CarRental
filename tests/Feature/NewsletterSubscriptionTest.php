<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscription;
use App\Notifications\NewsletterSubscriptionConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.turnstile.secret_key' => 'test-turnstile-secret']);
    }

    public function test_missing_turnstile_token_returns_validation_error_and_sends_no_email(): void
    {
        Notification::fake();
        Http::fake();

        $response = $this->postJson('/api/newsletter/subscriptions', [
            'email' => 'missing-token@example.com',
            'source' => 'footer',
            'locale' => 'en',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['cf_turnstile_response']);

        $this->assertDatabaseMissing('newsletter_subscriptions', [
            'email' => 'missing-token@example.com',
        ]);
        Notification::assertNothingSent();
        Http::assertNothingSent();
    }

    public function test_invalid_turnstile_token_returns_validation_error_and_sends_no_email(): void
    {
        Notification::fake();
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);

        $response = $this->postJson('/api/newsletter/subscriptions', [
            'email' => 'invalid-token@example.com',
            'source' => 'footer',
            'locale' => 'en',
            'cf_turnstile_response' => 'bad-token',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['cf_turnstile_response']);

        $this->assertDatabaseMissing('newsletter_subscriptions', [
            'email' => 'invalid-token@example.com',
        ]);
        Notification::assertNothingSent();
    }

    public function test_valid_turnstile_token_creates_pending_subscription_and_sends_confirmation(): void
    {
        Notification::fake();
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $response = $this->postJson('/api/newsletter/subscriptions', [
            'email' => 'VALID-NEWSLETTER@example.com',
            'source' => 'homepage_cta',
            'locale' => 'es',
            'cf_turnstile_response' => 'valid-token',
        ]);

        $response->assertAccepted();

        $this->assertDatabaseHas('newsletter_subscriptions', [
            'email' => 'valid-newsletter@example.com',
            'status' => 'pending',
            'source' => 'homepage_cta',
            'locale' => 'es',
        ]);
        Notification::assertSentOnDemandTimes(NewsletterSubscriptionConfirmation::class, 1);
    }

    public function test_already_subscribed_email_returns_conflict_and_sends_no_email(): void
    {
        Notification::fake();
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => true,
            ], 200),
        ]);

        NewsletterSubscription::create([
            'email' => 'already-subscribed@example.com',
            'status' => 'subscribed',
            'source' => 'footer',
            'locale' => 'en',
            'confirmed_at' => now(),
        ]);

        $response = $this->postJson('/api/newsletter/subscriptions', [
            'email' => 'already-subscribed@example.com',
            'source' => 'footer',
            'locale' => 'en',
            'cf_turnstile_response' => 'valid-token',
        ]);

        $response->assertConflict()
            ->assertJsonPath('message', 'This email is already subscribed.');

        Notification::assertNothingSent();
    }
}
