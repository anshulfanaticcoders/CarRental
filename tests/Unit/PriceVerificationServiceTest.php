<?php

namespace Tests\Unit;

use App\Services\PriceVerificationService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PriceVerificationServiceTest extends TestCase
{
    public function test_it_rejects_unknown_selected_extras(): void
    {
        $service = app(PriceVerificationService::class);

        $result = $service->verifyAndResolveExtras([
            [
                'id' => 'missing-extra',
                'qty' => 1,
                'total_for_booking' => 5.00,
            ],
        ], [
            'extras' => [
                [
                    'id' => 'child-seat',
                    'total_for_booking' => 10.00,
                ],
            ],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertSame('Price verification failed: Selected extra is no longer available.', $result['error']);
    }

    public function test_it_rejects_tampered_extra_prices(): void
    {
        $service = app(PriceVerificationService::class);

        $result = $service->verifyAndResolveExtras([
            [
                'id' => 'child-seat',
                'qty' => 1,
                'total_for_booking' => 1.00,
            ],
        ], [
            'extras' => [
                [
                    'id' => 'child-seat',
                    'total_for_booking' => 10.00,
                ],
            ],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertSame('Price verification failed: Extra price mismatch detected.', $result['error']);
    }

    public function test_it_returns_server_trusted_extra_payload_for_valid_selected_extras(): void
    {
        $service = app(PriceVerificationService::class);

        $result = $service->verifyAndResolveExtras([
            [
                'id' => 'child-seat',
                'qty' => 2,
                'total_for_booking' => 10.00,
            ],
        ], [
            'extras' => [
                [
                    'id' => 'child-seat',
                    'total_for_booking' => 10.00,
                    'daily_rate' => 2.50,
                    'name' => 'Child seat',
                ],
            ],
        ]);

        $this->assertTrue($result['valid']);
        $this->assertCount(1, $result['extras']);
        $this->assertSame('child-seat', $result['extras'][0]['id']);
        $this->assertSame(2, $result['extras'][0]['qty']);
        $this->assertSame(10.00, $result['extras'][0]['total_for_booking']);
        $this->assertSame('Child seat', $result['extras'][0]['name']);
    }
}
