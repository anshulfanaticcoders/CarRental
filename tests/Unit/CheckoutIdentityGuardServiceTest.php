<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\CheckoutIdentityGuardService;
use Tests\TestCase;

class CheckoutIdentityGuardServiceTest extends TestCase
{
    public function test_guest_checkout_requires_login_for_existing_customer_email(): void
    {
        $service = new CheckoutIdentityGuardService();
        $existingUser = new User([
            'role' => 'customer',
        ]);

        $conflict = $service->resolveCheckoutConflict(null, $existingUser, null);

        $this->assertNotNull($conflict);
        $this->assertSame('checkout_login_required', $conflict['code']);
        $this->assertTrue($conflict['show_login']);
    }

    public function test_guest_checkout_blocks_vendor_or_admin_email_reuse(): void
    {
        $service = new CheckoutIdentityGuardService();
        $existingUser = new User([
            'role' => 'vendor',
        ]);

        $conflict = $service->resolveCheckoutConflict(null, $existingUser, null);

        $this->assertNotNull($conflict);
        $this->assertSame('checkout_identity_reserved', $conflict['code']);
        $this->assertFalse($conflict['show_login']);
    }

    public function test_logged_in_customer_cannot_checkout_with_another_users_email(): void
    {
        $service = new CheckoutIdentityGuardService();
        $authenticatedUser = new User(['role' => 'customer']);
        $authenticatedUser->id = 10;

        $otherUser = new User(['role' => 'customer']);
        $otherUser->id = 25;

        $conflict = $service->resolveCheckoutConflict($authenticatedUser, $otherUser, null);

        $this->assertNotNull($conflict);
        $this->assertSame('checkout_identity_reserved', $conflict['code']);
        $this->assertFalse($conflict['show_login']);
    }
}
