<?php

namespace Tests\Unit;

use App\Notifications\Booking\BookingCancelledCustomerNotification;
use App\Notifications\Booking\BookingCancelledNotification;
use App\Notifications\Booking\BookingCreatedCompanyNotification;
use App\Notifications\Booking\BookingCreatedCustomerNotification;
use App\Notifications\Booking\BookingCreatedVendorNotification;
use App\Notifications\Booking\GuestBookingCreatedNotification;
use App\Notifications\Booking\PendingBookingReminderNotification;
use App\Notifications\Payment\CustomerPaymentFailedNotification;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExternalFacingNotificationWordingTest extends TestCase
{
    private function makeBooking(): object
    {
        return (object) [
            'id' => 77,
            'booking_number' => 'BK-7700',
            'pickup_date' => Carbon::parse('2026-04-15'),
            'pickup_time' => '09:00',
            'return_date' => Carbon::parse('2026-04-18'),
            'return_time' => '09:00',
            'pickup_location' => 'Dubai Airport',
            'return_location' => 'Dubai Airport',
            'cancellation_reason' => 'Customer changed plans',
            'vehicle_name' => 'Example Sedan',
            'booking_currency' => 'EUR',
            'total_amount' => 172.50,
            'amount_paid' => 22.50,
            'pending_amount' => 150.00,
            'amounts' => (object) [
                'admin_currency' => 'EUR',
                'admin_total_amount' => 22.50,
                'admin_paid_amount' => 22.50,
                'admin_pending_amount' => 0.00,
                'vendor_currency' => 'EUR',
                'vendor_total_amount' => 150.00,
                'vendor_paid_amount' => 0.00,
                'vendor_pending_amount' => 150.00,
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
            'vendor' => (object) [
                'first_name' => 'Victor',
            ],
        ];
    }

    private function makeVendor(): object
    {
        return (object) [
            'first_name' => 'Victor',
        ];
    }

    private function makeCompany(): object
    {
        return (object) [
            'company_name' => 'Vendor Fleet',
        ];
    }

    private function renderLines($mailMessage): string
    {
        return implode("\n", array_merge($mailMessage->introLines, $mailMessage->outroLines));
    }

    #[Test]
    public function customer_notifications_keep_neutral_amount_labels_and_never_mention_commission(): void
    {
        $booking = $this->makeBooking();
        $customer = $this->makeCustomer();
        $vehicle = $this->makeVehicle();

        $createdLines = $this->renderLines((new BookingCreatedCustomerNotification($booking, $customer, $vehicle))->toMail((object) []));
        $guestLines = $this->renderLines((new GuestBookingCreatedNotification($booking, $customer, $vehicle, 'secret-pass'))->toMail((object) []));
        $cancelledLines = $this->renderLines((new BookingCancelledCustomerNotification($booking, $customer, $vehicle, 'Support cancellation'))->toMail((object) []));
        $paymentFailedLines = $this->renderLines((new CustomerPaymentFailedNotification($booking, $customer, $vehicle))->toMail((object) []));

        $this->assertStringContainsString('**Total Amount:** €172.50', $createdLines);
        $this->assertStringContainsString('**Amount Paid:** €22.50', $createdLines);
        $this->assertStringContainsString('**Pending Amount:** €150.00', $createdLines);
        $this->assertStringNotContainsString('Commission', $createdLines);

        $this->assertStringContainsString('**Total Amount:** €172.50', $guestLines);
        $this->assertStringContainsString('**Amount Paid:** €22.50', $guestLines);
        $this->assertStringContainsString('**Pending Amount:** €150.00', $guestLines);
        $this->assertStringNotContainsString('Commission', $guestLines);

        $this->assertStringContainsString('**Total Amount:** €172.50', $cancelledLines);
        $this->assertStringNotContainsString('Commission', $cancelledLines);

        $this->assertStringContainsString('**Total Amount:** €172.50', $paymentFailedLines);
        $this->assertStringNotContainsString('Commission', $paymentFailedLines);
    }

    #[Test]
    public function vendor_and_company_notifications_keep_neutral_amount_labels_and_never_mention_commission(): void
    {
        $booking = $this->makeBooking();
        $customer = $this->makeCustomer();
        $vehicle = $this->makeVehicle();
        $vendor = $this->makeVendor();
        $company = $this->makeCompany();

        $vendorCreatedLines = $this->renderLines((new BookingCreatedVendorNotification($booking, $customer, $vehicle, $vendor))->toMail((object) []));
        $companyCreatedLines = $this->renderLines((new BookingCreatedCompanyNotification($booking, $customer, $vehicle, $company))->toMail((object) []));
        $pendingLines = $this->renderLines((new PendingBookingReminderNotification($booking, $customer, $vehicle))->toMail((object) []));
        $vendorCancelledLines = $this->renderLines((new BookingCancelledNotification($booking, $customer, $vehicle, 'vendor'))->toMail((object) ['first_name' => 'Victor']));

        foreach ([$vendorCreatedLines, $companyCreatedLines, $pendingLines] as $lines) {
            $this->assertStringContainsString('**Total Amount:** €150.00', $lines);
            $this->assertStringContainsString('**Amount Paid:** €0.00', $lines);
            $this->assertStringContainsString('**Pending Amount:** €150.00', $lines);
            $this->assertStringNotContainsString('Commission', $lines);
        }

        $this->assertStringContainsString('**Total Amount:** €150.00', $vendorCancelledLines);
        $this->assertStringNotContainsString('Commission', $vendorCancelledLines);
    }
}
