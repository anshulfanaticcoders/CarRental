<?php

namespace Tests\Unit;

use App\Models\Booking;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingReceiptPricingBreakdownTest extends TestCase
{
    #[Test]
    public function it_renders_vehicle_and_extras_using_customer_pricing_metadata_when_booking_columns_are_legacy(): void
    {
        $booking = Booking::make([
            'booking_number' => 'BK2026030001',
            'booking_status' => 'confirmed',
            'pickup_date' => '2026-04-15',
            'pickup_time' => '09:00',
            'return_date' => '2026-04-18',
            'return_time' => '09:00',
            'pickup_location' => 'Sample Airport',
            'return_location' => 'Sample Airport',
            'total_days' => 3,
            'booking_currency' => 'EUR',
            'base_price' => 150.00,
            'extra_charges' => 0.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => 172.50,
            'amount_paid' => 22.50,
            'pending_amount' => 150.00,
            'provider_metadata' => [
                'customer_pricing' => [
                    'vehicle_total' => 115.00,
                    'extras_total' => 57.50,
                    'grand_total' => 172.50,
                ],
            ],
            'created_at' => now(),
        ]);

        $booking->setRelation('extras', new Collection());

        $html = view('pdfs.booking-receipt', [
            'booking' => $booking,
            'vehicle' => (object) [
                'brand' => 'Example',
                'model' => 'Sedan',
            ],
            'payment' => (object) [
                'payment_method' => 'card',
                'transaction_id' => 'pi_test_receipt',
                'payment_date' => now(),
            ],
            'vendorProfile' => null,
            'vendorUser' => null,
            'vendorCompany' => null,
        ])->render();

        $this->assertStringContainsString('Vehicle Rental', $html);
        $this->assertStringContainsString('EUR 115.00', $html);
        $this->assertStringContainsString('Extras & Add-ons', $html);
        $this->assertStringContainsString('EUR 57.50', $html);
        $this->assertStringContainsString('EUR 172.50', $html);
    }
}
