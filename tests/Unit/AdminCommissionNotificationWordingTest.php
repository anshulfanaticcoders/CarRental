<?php

namespace Tests\Unit;

use App\Notifications\Booking\BookingCancelledNotification;
use App\Notifications\Booking\BookingCreatedAdminNotification;
use App\Notifications\Payment\AdminPaymentFailedNotification;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminCommissionNotificationWordingTest extends TestCase
{
    private function makeBooking(): object
    {
        return (object) [
            'id' => 42,
            'booking_number' => 'BK-4242',
            'pickup_date' => Carbon::parse('2026-04-15'),
            'pickup_time' => '09:00',
            'return_date' => Carbon::parse('2026-04-18'),
            'return_time' => '09:00',
            'cancellation_reason' => 'Customer changed plans',
            'pickup_location' => 'Dubai Airport',
            'return_location' => 'Dubai Airport',
            'amounts' => (object) [
                'admin_currency' => 'EUR',
                'admin_total_amount' => 22.50,
                'admin_paid_amount' => 22.50,
                'admin_pending_amount' => 0.00,
            ],
        ];
    }

    private function makeCustomer(): object
    {
        return (object) [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'phone' => '+49123456789',
        ];
    }

    private function makeVehicle(): object
    {
        return (object) [
            'brand' => 'Example',
            'model' => 'Sedan',
            'location' => 'Dubai Airport',
            'city' => 'Dubai',
            'state' => 'Dubai',
            'country' => 'UAE',
        ];
    }

    #[Test]
    public function admin_booking_notifications_use_commission_labels_in_mail_content(): void
    {
        $booking = $this->makeBooking();
        $customer = $this->makeCustomer();
        $vehicle = $this->makeVehicle();

        $createdMail = (new BookingCreatedAdminNotification($booking, $customer, $vehicle))->toMail((object) []);
        $createdLines = implode("\n", array_merge($createdMail->introLines, $createdMail->outroLines));

        $paymentFailedMail = (new AdminPaymentFailedNotification($booking, $customer, $vehicle))->toMail((object) []);
        $paymentFailedLines = implode("\n", array_merge($paymentFailedMail->introLines, $paymentFailedMail->outroLines));

        $cancelledMail = (new BookingCancelledNotification($booking, $customer, $vehicle, 'admin'))->toMail((object) []);
        $cancelledLines = implode("\n", array_merge($cancelledMail->introLines, $cancelledMail->outroLines));

        $this->assertStringContainsString('**Commission Total:** €22.50', $createdLines);
        $this->assertStringContainsString('**Commission Collected:** €22.50', $createdLines);
        $this->assertStringContainsString('**Pending Commission:** €0.00', $createdLines);
        $this->assertStringNotContainsString('**Total Amount:**', $createdLines);
        $this->assertStringNotContainsString('**Amount Paid:**', $createdLines);
        $this->assertStringNotContainsString('**Pending Amount:**', $createdLines);

        $this->assertStringContainsString('**Commission Total:** €22.50', $paymentFailedLines);
        $this->assertStringNotContainsString('**Total Amount:**', $paymentFailedLines);

        $this->assertStringContainsString('**Commission Total:** €22.50', $cancelledLines);
        $this->assertStringNotContainsString('**Total Amount:**', $cancelledLines);
    }
}
