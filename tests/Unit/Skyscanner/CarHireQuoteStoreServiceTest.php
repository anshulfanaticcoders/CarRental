<?php

namespace Tests\Unit\Skyscanner;

use App\Services\Skyscanner\CarHireQuoteStoreService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CarHireQuoteStoreServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 8, 10, 0, 0, 'UTC'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow(null);

        parent::tearDown();
    }

    public function test_it_stores_and_retrieves_a_quote_snapshot_by_quote_id(): void
    {
        $service = app(CarHireQuoteStoreService::class);

        $quote = [
            'quote_id' => 'quote-123',
            'expires_at' => CarbonImmutable::now('UTC')->addHour()->toIso8601String(),
            'vehicle' => [
                'display_name' => 'Toyota Yaris',
            ],
        ];

        $service->put($quote);

        $this->assertSame($quote, $service->get('quote-123'));
    }

    public function test_it_returns_null_when_quote_snapshot_does_not_exist(): void
    {
        $service = app(CarHireQuoteStoreService::class);

        $this->assertNull($service->get('missing-quote'));
    }

    public function test_it_keeps_an_expired_quote_snapshot_available_for_revalidation(): void
    {
        $service = app(CarHireQuoteStoreService::class);

        $quote = [
            'quote_id' => 'quote-expired',
            'expires_at' => CarbonImmutable::create(2026, 4, 8, 9, 0, 0, 'UTC')->toIso8601String(),
            'vehicle' => [
                'display_name' => 'Toyota Yaris',
            ],
        ];

        $service->put($quote);

        $this->assertSame($quote, $service->get('quote-expired'));
    }
}
