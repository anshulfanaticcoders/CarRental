<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleSeoRedirects;
use App\Services\Skyscanner\CarHirePublicResponseSerializer;
use App\Services\Skyscanner\CarHireQuoteStoreService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
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

        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 7, 11, 10, 0, 0, 'UTC'));

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
                'name' => 'Vrooem',
            ],
            'pricing' => [
                'currency' => 'EUR',
                'total_price' => 138,
                'price_per_day' => 46,
                'deposit_amount' => 500,
                'deposit_currency' => 'EUR',
                'excess_amount' => 1000,
            ],
            'insurance_options' => [
                [
                    'id' => 'cdw-basic',
                    'name' => 'Collision Damage Waiver',
                    'coverage_type' => 'CDW',
                    'included' => true,
                    'total_price' => 0,
                    'currency' => 'EUR',
                    'excess_amount' => 1000,
                    'description' => 'Included supplier collision cover.',
                ],
            ],
            'coverages' => [
                'cdw' => [
                    'included' => true,
                    'excess_amount' => 1000,
                    'currency' => 'EUR',
                    'description' => 'Included supplier collision cover.',
                ],
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
            'extras_preview' => [
                [
                    'id' => 'gps',
                    'code' => 'GPS',
                    'name' => 'GPS',
                    'daily_rate' => 5,
                    'total_for_booking' => 15,
                    'currency' => 'EUR',
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
                'name' => 'Vrooem',
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
        $response->assertSee('Vrooem');
        $response->assertDontSee('Vrooem Internal Fleet');
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
        $response->assertInertia(fn (Assert $page) => $page
            ->component('OfferResults')
            ->where('quote.insurance_options.0.name', 'Collision Damage Waiver')
            ->where('bookingContexts.quote-123.vehicle.insurance_options.0.name', 'Collision Damage Waiver')
            ->where('bookingContexts.quote-123.vehicle.coverages.cdw.excess_amount', 1000)
            ->where('bookingContexts.quote-123.vehicle.extras_preview.0.name', 'GPS')
        );
    }

    public function test_skyscanner_public_payload_matches_offer_page_and_booking_extras_context(): void
    {
        $store = app(CarHireQuoteStoreService::class);
        $serializer = app(CarHirePublicResponseSerializer::class);

        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 7, 11, 10, 0, 0, 'UTC'));

        $quote = [
            'quote_id' => 'quote-parity',
            'case_id' => 'PSM-PARITY',
            'created_at' => CarbonImmutable::now('UTC')->toIso8601String(),
            'expires_at' => CarbonImmutable::now('UTC')->addMinutes(30)->toIso8601String(),
            'vehicle' => [
                'provider_vehicle_id' => 'gm-vehicle-42',
                'source' => 'greenmotion',
                'provider_code' => 'greenmotion',
                'display_name' => 'Toyota Corolla or similar',
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'category' => 'compact',
                'image_url' => 'https://example.com/toyota-corolla.jpg',
                'sipp_code' => 'CDAR',
                'transmission' => 'automatic',
                'fuel_type' => 'petrol',
                'air_conditioning' => true,
                'seats' => 5,
                'doors' => 4,
                'luggage' => [
                    'small' => 1,
                    'medium' => 1,
                    'large' => 2,
                ],
                'booking_context' => [
                    'provider_payload' => [
                        'source' => 'greenmotion',
                        'products' => [
                            [
                                'type' => 'BAS',
                                'name' => 'Provider stale basic package',
                                'total' => 99.99,
                                'price_per_day' => 33.33,
                                'currency' => 'EUR',
                                'is_basic' => true,
                            ],
                        ],
                        'extras' => [
                            [
                                'id' => 'provider-stale-gps',
                                'code' => 'GPS',
                                'name' => 'Provider stale GPS',
                                'price' => 9.99,
                                'currency' => 'EUR',
                            ],
                        ],
                        'insurance_options' => [
                            [
                                'id' => 'provider-stale-cdw',
                                'name' => 'Provider stale CDW',
                                'included' => true,
                                'total_price' => 0,
                                'currency' => 'EUR',
                            ],
                        ],
                        'coverages' => [
                            'cdw' => [
                                'included' => true,
                                'excess_amount' => 333.0,
                                'currency' => 'EUR',
                            ],
                        ],
                        'policies' => [
                            'fuel_policy' => 'provider_stale_policy',
                        ],
                    ],
                ],
            ],
            'supplier' => [
                'code' => 'greenmotion',
                'name' => 'Vrooem',
            ],
            'pricing' => [
                'currency' => 'EUR',
                'total_price' => 312.45,
                'price_per_day' => 104.15,
                'deposit_amount' => 750.0,
                'deposit_currency' => 'EUR',
                'excess_amount' => 1200.0,
                'excess_theft_amount' => 1300.0,
            ],
            'policies' => [
                'mileage_policy' => 'limited',
                'mileage_limit_km' => 250,
                'fuel_policy' => 'full_to_full',
                'cancellation' => [
                    'available' => true,
                    'days_before_pickup' => 2,
                ],
            ],
            'insurance_options' => [
                [
                    'id' => 'quote-cdw',
                    'name' => 'Collision Damage Waiver',
                    'coverage_type' => 'CDW',
                    'included' => true,
                    'total_price' => 0,
                    'currency' => 'EUR',
                    'excess_amount' => 1200.0,
                    'description' => 'Included collision cover from the stored Skyscanner quote.',
                ],
                [
                    'id' => 'quote-premium',
                    'name' => 'Premium Cover',
                    'coverage_type' => 'SCDW',
                    'included' => false,
                    'daily_rate' => 11.0,
                    'total_price' => 33.0,
                    'currency' => 'EUR',
                    'excess_amount' => 150.0,
                    'description' => 'Optional reduced-excess protection from the stored quote.',
                ],
            ],
            'coverages' => [
                'cdw' => [
                    'included' => true,
                    'excess_amount' => 1200.0,
                    'currency' => 'EUR',
                    'description' => 'Collision cover included with this quote.',
                ],
                'theft' => [
                    'included' => true,
                    'excess_amount' => 1300.0,
                    'currency' => 'EUR',
                    'description' => 'Theft protection included with this quote.',
                ],
            ],
            'pickup_location_details' => [
                'provider_location_id' => 'gm-rak-airport',
                'name' => 'Menara Airport',
                'address' => 'Menara Airport, Marrakech, Morocco',
                'city' => 'Marrakech',
                'country' => 'Morocco',
                'country_code' => 'MA',
                'location_type' => 'airport',
                'iata' => 'RAK',
                'phone' => '+2120493000000',
                'pickup_instructions' => 'Collect at the Vrooem partner desk.',
                'dropoff_instructions' => 'Return to the same airport desk.',
                'latitude' => 31.6069,
                'longitude' => -8.0363,
            ],
            'dropoff_location_details' => [
                'provider_location_id' => 'gm-rak-airport',
                'name' => 'Menara Airport',
                'address' => 'Menara Airport, Marrakech, Morocco',
                'city' => 'Marrakech',
                'country' => 'Morocco',
                'country_code' => 'MA',
                'location_type' => 'airport',
                'iata' => 'RAK',
                'phone' => '+2120493000000',
                'pickup_instructions' => 'Collect at the Vrooem partner desk.',
                'dropoff_instructions' => 'Return to the same airport desk.',
                'latitude' => 31.6069,
                'longitude' => -8.0363,
            ],
            'search' => [
                'pickup_date' => '2026-08-05',
                'pickup_time' => '10:00',
                'dropoff_date' => '2026-08-08',
                'dropoff_time' => '10:00',
                'driver_age' => '35',
                'currency' => 'EUR',
            ],
            'products' => [
                [
                    'type' => 'BAS',
                    'name' => 'Basic Rental',
                    'subtitle' => 'Standard quote package',
                    'total' => 312.45,
                    'price_per_day' => 104.15,
                    'deposit' => 750.0,
                    'deposit_currency' => 'EUR',
                    'excess' => 1200.0,
                    'excess_theft_amount' => 1300.0,
                    'benefits' => ['Collision Damage Waiver included'],
                    'currency' => 'EUR',
                    'is_basic' => true,
                ],
                [
                    'type' => 'PRE',
                    'name' => 'Premium Cover',
                    'subtitle' => 'Reduced excess package',
                    'total' => 345.45,
                    'price_per_day' => 115.15,
                    'deposit' => 750.0,
                    'deposit_currency' => 'EUR',
                    'excess' => 150.0,
                    'benefits' => ['Reduced excess'],
                    'currency' => 'EUR',
                ],
            ],
            'extras_preview' => [
                [
                    'id' => 'quote-gps',
                    'code' => 'GPS',
                    'name' => 'GPS Navigation',
                    'description' => 'Portable GPS from the stored quote.',
                    'daily_rate' => 8.0,
                    'total_for_booking' => 24.0,
                    'currency' => 'EUR',
                    'max_quantity' => 1,
                ],
                [
                    'id' => 'quote-child-seat',
                    'code' => 'CHILD_SEAT',
                    'name' => 'Child Seat',
                    'description' => 'Child seat from the stored quote.',
                    'daily_rate' => 7.0,
                    'total_for_booking' => 21.0,
                    'currency' => 'EUR',
                    'max_quantity' => 2,
                ],
            ],
        ];

        $apiQuote = json_decode(json_encode($serializer->quote($quote), JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);

        $this->assertArrayNotHasKey('products', $apiQuote);
        $this->assertArrayNotHasKey('extras_preview', $apiQuote);

        $store->put($quote, [
            'search' => $quote['search'],
            'quotes' => [$quote],
        ]);

        $response = $this
            ->withoutMiddleware(HandleSeoRedirects::class)
            ->get('/en/offers/quote-parity');

        $response->assertOk();
        $response->assertSee('Toyota Corolla or similar');
        $response->assertSee('Basic Rental');
        $response->assertSee('Premium Cover');
        $response->assertSee('Collision Damage Waiver');
        $response->assertSee('GPS Navigation');
        $response->assertSee('Menara Airport');
        $response->assertDontSee('Provider stale basic package');
        $response->assertDontSee('Provider stale GPS');
        $response->assertDontSee('Provider stale CDW');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('OfferResults')
            ->where('quote.quote_id', $apiQuote['quote_id'])
            ->where('quote.case_id', $apiQuote['case_id'])
            ->where('quote.vehicle.display_name', $apiQuote['vehicle']['display_name'])
            ->where('quote.vehicle.sipp_code', $apiQuote['vehicle']['sipp_code'])
            ->where('quote.vehicle.transmission', $apiQuote['vehicle']['transmission'])
            ->where('quote.supplier.name', $apiQuote['supplier']['name'])
            ->where('quote.pricing', $apiQuote['pricing'])
            ->where('quote.policies', $apiQuote['policies'])
            ->where('quote.insurance_options', $apiQuote['insurance_options'])
            ->where('quote.coverages', $apiQuote['coverages'])
            ->where('quote.pickup_location_details.name', $apiQuote['pickup_location_details']['name'])
            ->where('quote.pickup_location_details.address', $apiQuote['pickup_location_details']['address'])
            ->where('quote.pickup_location_details.city', $apiQuote['pickup_location_details']['city'])
            ->where('quote.dropoff_location_details.name', $apiQuote['dropoff_location_details']['name'])
            ->where('bookingContext.vehicle.quoteid', $apiQuote['quote_id'])
            ->where('bookingContext.vehicle.display_name', $apiQuote['vehicle']['display_name'])
            ->where('bookingContext.vehicle.sipp_code', $apiQuote['vehicle']['sipp_code'])
            ->where('bookingContext.vehicle.total_price', $apiQuote['pricing']['total_price'])
            ->where('bookingContext.vehicle.price_per_day', $apiQuote['pricing']['price_per_day'])
            ->where('bookingContext.vehicle.pricing', $apiQuote['pricing'])
            ->where('bookingContext.vehicle.policies', $apiQuote['policies'])
            ->where('bookingContext.vehicle.insurance_options', $apiQuote['insurance_options'])
            ->where('bookingContext.vehicle.coverages', $apiQuote['coverages'])
            ->where('bookingContext.vehicle.products.0.name', 'Basic Rental')
            ->where('bookingContext.vehicle.products.0.total', 312.45)
            ->where('bookingContext.vehicle.products.1.name', 'Premium Cover')
            ->where('bookingContext.vehicle.products.1.total', 345.45)
            ->where('bookingContext.vehicle.extras_preview.0.name', 'GPS Navigation')
            ->where('bookingContext.vehicle.extras_preview.0.total_for_booking', 24)
            ->where('bookingContext.optional_extras.0.name', 'GPS Navigation')
            ->where('bookingContext.optional_extras.1.name', 'Child Seat')
            ->where('bookingContext.pickup_location', $apiQuote['pickup_location_details']['name'])
            ->where('bookingContext.dropoff_location', $apiQuote['dropoff_location_details']['name'])
            ->where('bookingContext.location_details.address_1', $apiQuote['pickup_location_details']['address'])
            ->where('bookingContext.vehicle.booking_context.provider_payload.insurance_options', $apiQuote['insurance_options'])
            ->where('bookingContext.vehicle.booking_context.provider_payload.coverages', $apiQuote['coverages'])
            ->where('bookingContext.vehicle.booking_context.provider_payload.extras_preview.0.name', 'GPS Navigation')
        );
    }

    public function test_it_renders_a_user_friendly_state_for_an_expired_offer_quote(): void
    {
        $store = app(CarHireQuoteStoreService::class);

        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 7, 11, 10, 0, 0, 'UTC'));

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
