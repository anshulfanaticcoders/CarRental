<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleSeoRedirects;
use App\Services\Skyscanner\CarHireQuoteStoreService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SkyscannerOfferPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        CarbonImmutable::setTestNow(null);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow(null);

        parent::tearDown();
    }

    public function test_it_renders_a_dedicated_offer_results_page_for_a_valid_quote(): void
    {
        $store = app(CarHireQuoteStoreService::class);

        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 11, 10, 0, 0, 'UTC'));

        $selectedQuote = [
            'quote_id' => 'quote-123',
            'case_id' => 'PSM-46100',
            'created_at' => CarbonImmutable::now('UTC')->toIso8601String(),
            'expires_at' => CarbonImmutable::now('UTC')->addMinutes(30)->toIso8601String(),
            'vehicle' => [
                'provider_vehicle_id' => '326',
                'display_name' => 'Opel Corsa',
                'image_url' => 'https://example.com/opel-corsa.jpg',
                'sipp_code' => 'ECAR',
                'transmission' => 'automatic',
                'fuel_type' => 'petrol',
                'air_conditioning' => true,
                'seats' => 5,
                'doors' => 5,
                'luggage' => [
                    'large' => 2,
                ],
            ],
            'supplier' => [
                'code' => 'internal',
                'name' => 'Vrooem Internal Fleet',
            ],
            'pricing' => [
                'currency' => 'EUR',
                'total_price' => 138,
                'price_per_day' => 46,
            ],
            'pickup_location_details' => [
                'name' => 'Menara Airport',
                'address' => 'Menara Airport, Marrakech, Morocco',
            ],
            'dropoff_location_details' => [
                'name' => 'Menara Airport',
                'address' => 'Menara Airport, Marrakech, Morocco',
            ],
            'search' => [
                'pickup_date' => '2026-05-10',
                'pickup_time' => '09:00',
                'dropoff_date' => '2026-05-13',
                'dropoff_time' => '09:00',
                'driver_age' => '35',
                'currency' => 'EUR',
            ],
            'products' => [
                [
                    'type' => 'BAS',
                    'name' => 'Basic Rental',
                    'subtitle' => 'Standard Package',
                    'total' => 138,
                    'price_per_day' => 46,
                    'deposit' => 500,
                    'deposit_currency' => 'EUR',
                    'benefits' => [],
                    'currency' => 'EUR',
                    'is_basic' => true,
                ],
            ],
        ];

        $secondaryQuote = [
            'quote_id' => 'quote-456',
            'case_id' => 'PSM-46100',
            'created_at' => CarbonImmutable::now('UTC')->toIso8601String(),
            'expires_at' => CarbonImmutable::now('UTC')->addMinutes(30)->toIso8601String(),
            'vehicle' => [
                'provider_vehicle_id' => '327',
                'display_name' => 'Peugeot 208',
                'image_url' => 'https://example.com/peugeot-208.jpg',
                'sipp_code' => 'ECMN',
                'transmission' => 'manual',
                'fuel_type' => 'petrol',
                'air_conditioning' => true,
                'seats' => 5,
                'doors' => 5,
                'luggage' => [
                    'large' => 2,
                ],
            ],
            'supplier' => [
                'code' => 'internal',
                'name' => 'Vrooem Internal Fleet',
            ],
            'pricing' => [
                'currency' => 'EUR',
                'total_price' => 129,
                'price_per_day' => 43,
            ],
            'pickup_location_details' => [
                'name' => 'Menara Airport',
                'address' => 'Menara Airport, Marrakech, Morocco',
            ],
            'dropoff_location_details' => [
                'name' => 'Menara Airport',
                'address' => 'Menara Airport, Marrakech, Morocco',
            ],
            'search' => $selectedQuote['search'],
        ];

        $store->put($selectedQuote, [
            'search' => $selectedQuote['search'],
            'quotes' => [$selectedQuote, $secondaryQuote],
        ]);

        $response = $this
            ->withoutMiddleware(HandleSeoRedirects::class)
            ->get('/en/offers/quote-123');

        $response->assertOk();
        $response->assertSee('Opel Corsa');
        $response->assertSee('Peugeot 208');
        $response->assertSee('Vrooem Internal Fleet');
        $response->assertSee('Menara Airport');
        $response->assertSee('ECAR');
        $response->assertSee('selected_quote_id');
        $response->assertSee('bookingContext');
        $response->assertSee('bookingContexts');
        $response->assertSee('initial_package');
        $response->assertSee('pickup_location');
        $response->assertSee('source');
        $response->assertSee('OfferResults');
        $response->assertDontSee('/en/skyscanner/offer/');
    }

    public function test_it_renders_a_user_friendly_state_for_an_expired_offer_quote(): void
    {
        $store = app(CarHireQuoteStoreService::class);

        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 11, 10, 0, 0, 'UTC'));

        $store->put([
            'quote_id' => 'quote-expired',
            'case_id' => 'PSM-46100',
            'created_at' => CarbonImmutable::now('UTC')->subMinutes(40)->toIso8601String(),
            'expires_at' => CarbonImmutable::now('UTC')->subMinutes(10)->toIso8601String(),
            'vehicle' => [
                'provider_vehicle_id' => '326',
                'display_name' => 'Opel Corsa',
            ],
        ]);

        $response = $this
            ->withoutMiddleware(HandleSeoRedirects::class)
            ->get('/en/offers/quote-expired');

        $response->assertOk();
        $response->assertSee('Offer expired');
        $response->assertSee('Search again');
        $response->assertSee('quoteStatus');
    }
}
