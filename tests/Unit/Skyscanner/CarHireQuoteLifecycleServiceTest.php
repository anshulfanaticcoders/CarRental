<?php

namespace Tests\Unit\Skyscanner;

use App\Services\Skyscanner\CarHireQuoteLifecycleService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Tests\TestCase;

class CarHireQuoteLifecycleServiceTest extends TestCase
{
    public function test_it_creates_an_isolated_quote_snapshot_with_expiry(): void
    {
        config([
            'skyscanner.case_id' => 'PSM-46100',
            'skyscanner.quote_ttl_minutes' => 30,
        ]);

        $service = app(CarHireQuoteLifecycleService::class);
        $now = CarbonImmutable::create(2026, 4, 8, 10, 0, 0, 'UTC');

        $quote = $service->createQuote(
            [
                'provider_vehicle_id' => '327',
                'display_name' => 'Toyota Yaris',
                'pricing' => [
                    'currency' => 'EUR',
                    'total_price' => 90.0,
                ],
                'policies' => [
                    'mileage_policy' => 'limited',
                    'fuel_policy' => 'full_to_full',
                ],
            ],
            [
                'pickup_location_id' => '3272373056',
                'dropoff_location_id' => '3272373056',
                'pickup_at' => '2026-06-15T09:00:00Z',
                'dropoff_at' => '2026-06-18T09:00:00Z',
            ],
            $now,
        );

        $this->assertTrue(Str::isUuid($quote['quote_id']));
        $this->assertSame('PSM-46100', $quote['case_id']);
        $this->assertSame('2026-04-08T10:00:00+00:00', $quote['created_at']);
        $this->assertSame('2026-04-08T10:30:00+00:00', $quote['expires_at']);
        $this->assertSame('327', $quote['vehicle']['provider_vehicle_id']);
        $this->assertSame('Toyota Yaris', $quote['vehicle']['display_name']);
        $this->assertSame(90.0, $quote['pricing']['total_price']);
        $this->assertSame('EUR', $quote['pricing']['currency']);
        $this->assertSame('3272373056', $quote['search']['pickup_location_id']);
        $this->assertSame('limited', $quote['policies']['mileage_policy']);
    }

    public function test_it_revalidates_quotes_against_expiry(): void
    {
        $service = app(CarHireQuoteLifecycleService::class);

        $quote = [
            'quote_id' => 'quote-123',
            'expires_at' => '2026-04-08T10:30:00+00:00',
        ];

        $valid = $service->revalidate($quote, CarbonImmutable::create(2026, 4, 8, 10, 15, 0, 'UTC'));
        $expired = $service->revalidate($quote, CarbonImmutable::create(2026, 4, 8, 10, 31, 0, 'UTC'));

        $this->assertSame([
            'valid' => true,
            'reason' => null,
        ], $valid);

        $this->assertSame([
            'valid' => false,
            'reason' => 'expired',
        ], $expired);
    }

    public function test_it_detects_price_mismatch_between_stored_and_current_quotes(): void
    {
        $service = app(CarHireQuoteLifecycleService::class);

        $storedQuote = [
            'vehicle' => [
                'provider_vehicle_id' => '327',
            ],
            'pricing' => [
                'currency' => 'EUR',
                'total_price' => 90.0,
            ],
        ];

        $currentQuote = [
            'vehicle' => [
                'provider_vehicle_id' => '327',
            ],
            'pricing' => [
                'currency' => 'EUR',
                'total_price' => 95.0,
            ],
        ];

        $result = $service->detectMismatch($storedQuote, $currentQuote);

        $this->assertSame([
            'mismatched' => true,
            'reason' => 'price_changed',
        ], $result);
    }

    public function test_it_detects_currency_mismatch_between_stored_and_current_quotes(): void
    {
        $service = app(CarHireQuoteLifecycleService::class);

        $storedQuote = [
            'vehicle' => [
                'provider_vehicle_id' => '327',
            ],
            'pricing' => [
                'currency' => 'EUR',
                'total_price' => 90.0,
            ],
        ];

        $currentQuote = [
            'vehicle' => [
                'provider_vehicle_id' => '327',
            ],
            'pricing' => [
                'currency' => 'USD',
                'total_price' => 90.0,
            ],
        ];

        $result = $service->detectMismatch($storedQuote, $currentQuote);

        $this->assertSame([
            'mismatched' => true,
            'reason' => 'currency_changed',
        ], $result);
    }

    public function test_it_returns_no_mismatch_when_quotes_match(): void
    {
        $service = app(CarHireQuoteLifecycleService::class);

        $storedQuote = [
            'vehicle' => [
                'provider_vehicle_id' => '327',
            ],
            'pricing' => [
                'currency' => 'EUR',
                'total_price' => 90.0,
            ],
        ];

        $currentQuote = [
            'vehicle' => [
                'provider_vehicle_id' => '327',
            ],
            'pricing' => [
                'currency' => 'EUR',
                'total_price' => 90.0,
            ],
        ];

        $result = $service->detectMismatch($storedQuote, $currentQuote);

        $this->assertSame([
            'mismatched' => false,
            'reason' => null,
        ], $result);
    }
}
