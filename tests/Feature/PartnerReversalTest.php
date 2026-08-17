<?php

namespace Tests\Feature;

use App\Models\Affiliate\AffiliateBusiness;
use App\Models\Affiliate\AffiliateCommission;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use App\Notifications\Payment\AdminPartnerReversalNeededNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A booking that dies after partner conversions fired must un-pay them:
 * refunded bookings used to keep a validated Awin transaction and a pending
 * affiliate commission that the monthly sweep then approved and paid out.
 */
class PartnerReversalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'first_name' => 'Site', 'last_name' => 'Admin',
            'email' => config('admin.email'), 'phone' => '+27821110000',
            'password' => bcrypt('password'), 'role' => 'admin', 'status' => 'active',
        ]);
    }

    private function booking(array $overrides = []): Booking
    {
        $user = User::factory()->create(['role' => 'customer']);
        $customer = Customer::create([
            'user_id' => $user->id, 'first_name' => 'Rev', 'last_name' => 'Customer',
            'email' => $user->email, 'phone' => $user->phone, 'driver_age' => 30,
        ]);

        return Booking::create(array_merge([
            'booking_number' => 'BKREV-'.uniqid(),
            'customer_id' => $customer->id,
            'provider_source' => 'greenmotion',
            'pickup_date' => now()->addDays(5), 'return_date' => now()->addDays(8),
            'pickup_time' => '10:00', 'return_time' => '10:00',
            'pickup_location' => 'Airport', 'return_location' => 'Airport',
            'plan' => 'BAS', 'total_days' => 3,
            'base_price' => 100, 'tax_amount' => 0, 'total_amount' => 100,
            'amount_paid' => 15, 'pending_amount' => 85,
            'payment_status' => 'partial', 'booking_status' => 'confirmed',
        ], $overrides));
    }

    private function business(): AffiliateBusiness
    {
        $affiliateUser = User::factory()->create(['role' => 'affiliate']);

        return AffiliateBusiness::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $affiliateUser->id,
            'name' => 'Hotel Partner',
            'contact_email' => uniqid('partner').'@example.com',
            'contact_phone' => '+1234567890',
            'verification_status' => 'verified',
            'status' => 'active',
            'business_type' => 'hotel',
            'legal_address' => '123 Main Street',
            'city' => 'Antwerp', 'country' => 'Belgium', 'postal_code' => '2000',
            'currency' => 'EUR',
            'dashboard_access_token' => 'AFF-REV-'.uniqid(),
        ]);
    }

    private function commission(Booking $booking, array $overrides = []): AffiliateCommission
    {
        return AffiliateCommission::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'business_id' => $this->business()->id,
            'booking_id' => $booking->id,
            'customer_id' => null,
            'booking_amount' => 100, 'commissionable_amount' => 100,
            'commission_amount' => 3.0, 'currency' => 'USD',
            'discount_amount' => 0, 'commission_rate' => 3,
            'commission_type' => 'percentage', 'status' => 'pending',
            'booking_type' => 'platform', 'net_commission' => 3.0, 'tax_amount' => 0,
        ], $overrides));
    }

    #[Test]
    public function cancelling_a_booking_cancels_its_unpaid_commission(): void
    {
        Notification::fake();
        $this->admin();
        $booking = $this->booking();
        $commission = $this->commission($booking);

        $booking->update(['booking_status' => 'cancelled']);

        $this->assertSame('cancelled', $commission->fresh()->status);
        $this->assertNotNull($booking->fresh()->provider_metadata['partner_reversal_done']);
    }

    #[Test]
    public function a_refund_cancels_an_approved_commission_too(): void
    {
        Notification::fake();
        $this->admin();
        $booking = $this->booking();
        $commission = $this->commission($booking, ['status' => 'approved']);

        $booking->update(['payment_status' => 'refunded']);

        $this->assertSame('cancelled', $commission->fresh()->status);
    }

    #[Test]
    public function a_paid_commission_is_flagged_for_clawback_and_admin_is_told(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $booking = $this->booking();
        $commission = $this->commission($booking, ['status' => 'paid']);

        $booking->update(['booking_status' => 'cancelled']);

        $fresh = $commission->fresh();
        $this->assertSame('paid', $fresh->status, 'Paid money does not vanish — it gets flagged.');
        $this->assertStringContainsString('clawback', $fresh->dispute_reason);
        Notification::assertSentTo($admin, AdminPartnerReversalNeededNotification::class);
    }

    #[Test]
    public function a_sent_awin_conversion_triggers_a_manual_void_alert(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $booking = $this->booking([
            'provider_metadata' => ['awin_conversion_sent_at' => now()->toIso8601String()],
        ]);

        $booking->update(['payment_status' => 'refunded']);

        Notification::assertSentTo($admin, AdminPartnerReversalNeededNotification::class,
            fn ($n) => str_contains(implode(' ', $n->toArray($admin)['actions']), $booking->booking_number));
    }

    #[Test]
    public function the_reversal_runs_once_even_across_multiple_dead_transitions(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $booking = $this->booking([
            'provider_metadata' => ['awin_conversion_sent_at' => now()->toIso8601String()],
        ]);

        $booking->update(['booking_status' => 'cancelled']);
        $booking->refresh()->update(['payment_status' => 'refunded']);

        Notification::assertSentToTimes($admin, AdminPartnerReversalNeededNotification::class, 1);
    }

    #[Test]
    public function a_healthy_booking_update_does_nothing(): void
    {
        Notification::fake();
        $this->admin();
        $booking = $this->booking();
        $commission = $this->commission($booking);

        $booking->update(['notes' => 'admin note']);

        $this->assertSame('pending', $commission->fresh()->status);
        $this->assertArrayNotHasKey('partner_reversal_done', $booking->fresh()->provider_metadata ?? []);
    }
}
