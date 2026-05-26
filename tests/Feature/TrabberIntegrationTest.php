<?php

namespace Tests\Feature;

use App\Mail\TrabberReportMail;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\PayableSetting;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VehicleImage;
use App\Models\VendorLocation;
use App\Models\VendorProfile;
use App\Services\LocationSearchService;
use App\Services\Trabber\TrabberAttributionService;
use App\Services\Trabber\TrabberReportService;
use App\Services\VrooemGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TrabberIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'trabber.api_key' => 'trabber-secret',
            'trabber.inventory_scope' => 'internal',
        ]);

        PayableSetting::create(['payment_percentage' => 15]);
    }

    public function test_trabber_api_rejects_missing_or_invalid_api_key(): void
    {
        $this->getJson('/api/trabber/locations')->assertUnauthorized();

        $this->withHeader('x-api-key', 'wrong-key')
            ->getJson('/api/trabber/locations')
            ->assertUnauthorized();
    }

    public function test_trabber_search_accepts_iata_and_returns_offers_with_deeplinks(): void
    {
        $this->createInternalVehicleAtDubaiAirport();

        $response = $this
            ->withHeader('x-api-key', 'trabber-secret')
            ->postJson('/api/trabber/car-hire/search', [
                'pickup' => ['iata' => 'DXB'],
                'dropoff' => ['iata' => 'DXB'],
                'pickup_date_time' => '2026-06-15 09:00:00',
                'dropoff_date_time' => '2026-06-18 09:00:00',
                'currency' => 'EUR',
                'language' => 'en',
                'user_country' => 'AE',
                'driver_age' => 35,
            ]);

        $response->assertOk();
        $response->assertJsonCount(1, 'offers');
        $response->assertJsonPath('offers.0.vehicle_name', 'Toyota Yaris');
        $response->assertJsonPath('offers.0.sipp', 'ECAR');
        $response->assertJsonPath('offers.0.currency', 'EUR');
        $response->assertJsonPath('offers.0.price', 149.49);
        $this->assertStringContainsString('/api/trabber/redirect?offer_id=', $response->json('offers.0.deeplink_url'));
    }

    public function test_trabber_search_accepts_latitude_and_longitude(): void
    {
        $this->createInternalVehicleAtDubaiAirport();

        $response = $this
            ->withHeader('x-api-key', 'trabber-secret')
            ->postJson('/api/trabber/car-hire/search', [
                'pickup' => ['latitude' => 25.251369, 'longitude' => 55.347204],
                'dropoff' => ['latitude' => 25.251369, 'longitude' => 55.347204],
                'pickup_date_time' => '2026-06-15 09:00:00',
                'dropoff_date_time' => '2026-06-18 09:00:00',
                'currency' => 'EUR',
            ]);

        $response->assertOk();
        $response->assertJsonCount(1, 'offers');
    }

    public function test_trabber_redirect_stores_last_click_attribution(): void
    {
        $this->createInternalVehicleAtDubaiAirport();

        $search = $this
            ->withHeader('x-api-key', 'trabber-secret')
            ->postJson('/api/trabber/car-hire/search', [
                'pickup' => ['iata' => 'DXB'],
                'dropoff' => ['iata' => 'DXB'],
                'pickup_date_time' => '2026-06-15 09:00:00',
                'dropoff_date_time' => '2026-06-18 09:00:00',
                'currency' => 'EUR',
            ]);

        $deeplink = $search->json('offers.0.deeplink_url').'&clickid=CLICK-ONE';
        $offerId = (string) $search->json('offers.0.offer_id');
        $response = $this->get($deeplink);

        $response->assertRedirect(route('trabber.offer', [
            'locale' => 'en',
            'offerId' => $offerId,
        ]));
        $response->assertCookie(TrabberAttributionService::COOKIE_NAME);
        $this->assertDatabaseHas('trabber_clicks', [
            'clickid' => 'CLICK-ONE',
            'source' => 'trabber',
            'offer_id' => $offerId,
        ]);
        $this->assertSame('CLICK-ONE', session('trabber.attribution.trabber_clickid'));

        $this->get($search->json('offers.0.deeplink_url').'&clickid=CLICK-TWO')->assertRedirect();

        $this->assertDatabaseHas('trabber_clicks', [
            'clickid' => 'CLICK-TWO',
            'source' => 'trabber',
        ]);
        $this->assertSame('CLICK-TWO', session('trabber.attribution.trabber_clickid'));
    }

    public function test_trabber_redirect_without_clickid_opens_offer_page_without_click_row(): void
    {
        $this->createInternalVehicleAtDubaiAirport();

        $search = $this
            ->withHeader('x-api-key', 'trabber-secret')
            ->postJson('/api/trabber/car-hire/search', [
                'pickup' => ['iata' => 'DXB'],
                'dropoff' => ['iata' => 'DXB'],
                'pickup_date_time' => '2026-06-15 09:00:00',
                'dropoff_date_time' => '2026-06-18 09:00:00',
                'currency' => 'EUR',
                'language' => 'en',
            ]);

        $offerId = (string) $search->json('offers.0.offer_id');

        $this->get($search->json('offers.0.deeplink_url'))
            ->assertRedirect(route('trabber.offer', [
                'locale' => 'en',
                'offerId' => $offerId,
            ]));

        $this->assertDatabaseMissing('trabber_clicks', [
            'offer_id' => $offerId,
        ]);
    }

    public function test_trabber_offer_page_shows_selected_vehicle_and_other_available_offers(): void
    {
        $vehicle = $this->createInternalVehicleAtDubaiAirport();
        $this->createSecondVehicleAtSameLocation($vehicle);

        $search = $this
            ->withHeader('x-api-key', 'trabber-secret')
            ->postJson('/api/trabber/car-hire/search', [
                'pickup' => ['iata' => 'DXB'],
                'dropoff' => ['iata' => 'DXB'],
                'pickup_date_time' => '2026-06-15 09:00:00',
                'dropoff_date_time' => '2026-06-18 09:00:00',
                'currency' => 'EUR',
                'language' => 'en',
            ]);

        $offerId = (string) $search->json('offers.0.offer_id');

        $response = $this->get(route('trabber.offer', [
            'locale' => 'en',
            'offerId' => $offerId,
        ]));

        $response->assertOk();
        $response->assertSee('OfferResults');
        $response->assertSee('Toyota Yaris');
        $response->assertSee('Toyota Corolla');
        $response->assertSee('selected_quote_id');
        $response->assertSee('bookingContext');
        $response->assertSee('bookingContexts');
        $response->assertInertia(fn (Assert $page) => $page
            ->component('OfferResults')
            ->where('quote.pricing.total_price', 149.49)
            ->where("bookingContexts.{$offerId}.vehicle.total_price", 129.99)
        );
    }

    public function test_trabber_search_includes_external_provider_offers_from_gateway(): void
    {
        config(['trabber.inventory_scope' => 'mixed']);

        $unifiedLocation = [
            'unified_location_id' => 3385755165,
            'name' => 'Dubai Airport (DXB)',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'country_code' => 'AE',
            'latitude' => 25.251369,
            'longitude' => 55.347204,
            'iata' => 'DXB',
            'providers' => [
                ['provider' => 'surprice', 'pickup_id' => 'DXB:DXBA01'],
            ],
        ];

        $this->mock(LocationSearchService::class, function ($mock) use ($unifiedLocation): void {
            $mock->shouldReceive('getAllLocations')->andReturn([$unifiedLocation]);
            $mock->shouldReceive('resolveSearchLocation')->andReturn($unifiedLocation);
            $mock->shouldReceive('getLocationByUnifiedId')->andReturn($unifiedLocation);
        });

        $this->mock(VrooemGatewayService::class, function ($mock): void {
            $mock->shouldReceive('searchVehicles')
                ->once()
                ->andReturn([
                    'vehicles' => [[
                        'id' => 'surprice_001',
                        'supplier_id' => 'surprice',
                        'supplier_vehicle_id' => 'SP-ECAR-001',
                        'make' => 'Nissan',
                        'model' => 'Sunny',
                        'category' => 'economy',
                        'image_url' => 'https://example.com/surprice-sunny.jpg',
                        'pricing' => [
                            'total_price' => 180.0,
                            'daily_rate' => 60.0,
                            'currency' => 'EUR',
                        ],
                        'pickup_location' => [
                            'supplier_location_id' => 'DXB:DXBA01',
                            'name' => 'Dubai Airport (DXB)',
                            'latitude' => 25.251369,
                            'longitude' => 55.347204,
                        ],
                        'dropoff_location' => [
                            'supplier_location_id' => 'DXB:DXBA01',
                            'name' => 'Dubai Airport (DXB)',
                            'latitude' => 25.251369,
                            'longitude' => 55.347204,
                        ],
                        'transmission' => 'automatic',
                        'fuel_type' => 'petrol',
                        'seats' => 5,
                        'mileage_policy' => 'unlimited',
                        'sipp_code' => 'ECAR',
                        'supplier_data' => [
                            'provider_code' => 'surprice',
                            'fuel_policy' => 'full_to_full',
                        ],
                    ]],
                    'provider_status' => [],
                    'search_id' => 'gateway-search-001',
                ]);
        });

        $response = $this
            ->withHeader('x-api-key', 'trabber-secret')
            ->postJson('/api/trabber/car-hire/search', [
                'pickup' => ['iata' => 'DXB'],
                'dropoff' => ['iata' => 'DXB'],
                'pickup_date_time' => '2026-06-15 09:00:00',
                'dropoff_date_time' => '2026-06-18 09:00:00',
                'currency' => 'EUR',
            ]);

        $response->assertOk();
        $response->assertJsonCount(1, 'offers');
        $response->assertJsonPath('offers.0.vehicle_name', 'Nissan Sunny');
        $response->assertJsonPath('offers.0.supplier_name', 'surprice');
        $response->assertJsonPath('offers.0.price', 207);
        $response->assertJsonPath('offers.0.image_url', 'https://example.com/surprice-sunny.jpg');
        $response->assertJsonPath('offers.0.mileage_policy', 'unlimited');
        $response->assertJsonPath('meta.inventory_scope', 'mixed');
    }

    public function test_trabber_locations_returns_unified_locations_in_mixed_inventory_mode(): void
    {
        config(['trabber.inventory_scope' => 'mixed']);

        $this->mock(LocationSearchService::class, function ($mock): void {
            $mock->shouldReceive('getAllLocations')->once()->andReturn([
                [
                    'unified_location_id' => 3385755165,
                    'name' => 'Dubai Airport (DXB)',
                    'city' => 'Dubai',
                    'country' => 'United Arab Emirates',
                    'country_code' => 'AE',
                    'latitude' => 25.251369,
                    'longitude' => 55.347204,
                    'iata' => 'DXB',
                    'location_type' => 'airport',
                    'providers' => [
                        ['provider' => 'internal', 'pickup_id' => '1'],
                        ['provider' => 'surprice', 'pickup_id' => 'DXB:DXBA01'],
                    ],
                ],
            ]);
        });

        $response = $this
            ->withHeader('x-api-key', 'trabber-secret')
            ->getJson('/api/trabber/locations');

        $response->assertOk();
        $response->assertJsonPath('locations.0.id', '3385755165');
        $response->assertJsonPath('locations.0.iata', 'DXB');
        $response->assertJsonPath('locations.0.providers', ['internal', 'surprice']);
    }

    public function test_trabber_report_exports_commission_and_status_rows(): void
    {
        $vehicle = $this->createInternalVehicleAtDubaiAirport();
        $customer = $this->createCustomer();

        Booking::create([
            'booking_number' => 'BK202605220001',
            'booking_reference' => 'VRM-TRABBER-001',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'pickup_date' => '2026-06-15 09:00:00',
            'return_date' => '2026-06-18 09:00:00',
            'pickup_location' => 'Dubai Airport (DXB)',
            'return_location' => 'Dubai Airport (DXB)',
            'pickup_time' => '09:00',
            'return_time' => '09:00',
            'total_days' => 3,
            'base_price' => 120,
            'tax_amount' => 10,
            'total_amount' => 130,
            'amount_paid' => 130,
            'pending_amount' => 0,
            'booking_currency' => 'EUR',
            'payment_status' => 'paid',
            'booking_status' => 'confirmed',
            'provider_metadata' => [
                'partner_source' => 'trabber',
                'trabber_clickid' => 'CLICK-REPORT',
                'trabber_offer_id' => 'OFFER-REPORT',
                'trabber_commission_rate' => '0.05',
            ],
        ]);

        $csv = app(TrabberReportService::class)->csvSince(now()->subYear());

        $this->assertStringContainsString('booking_reference,clickid,commission,status,total_amount,currency,booking_date,pickup_date,dropoff_date', $csv);
        $this->assertStringContainsString('VRM-TRABBER-001,CLICK-REPORT,6.50,confirmed,130.00,EUR', $csv);
    }

    public function test_trabber_daily_report_command_emails_configured_recipient(): void
    {
        config(['trabber.report_recipient' => 'reports@trabber.com']);
        Mail::fake();

        $path = storage_path('framework/testing/trabber-daily-report.csv');

        $this->artisan('trabber:export-daily-report', [
            '--path' => $path,
            '--send' => true,
        ])->assertSuccessful();

        $this->assertFileExists($path);
        Mail::assertSent(TrabberReportMail::class, function (TrabberReportMail $mail) use ($path): bool {
            return $mail->hasTo('reports@trabber.com')
                && $mail->reportType === 'daily'
                && $mail->filename === basename($path)
                && str_contains($mail->csv, 'booking_reference,clickid,commission,status,total_amount,currency,booking_date,pickup_date,dropoff_date');
        });
    }

    public function test_trabber_report_command_fails_send_without_recipient(): void
    {
        config(['trabber.report_recipient' => null]);
        Mail::fake();

        $this->artisan('trabber:export-monthly-report', [
            '--path' => storage_path('framework/testing/trabber-monthly-report.csv'),
            '--send' => true,
        ])->assertExitCode(1);

        Mail::assertNothingSent();
    }

    private function createInternalVehicleAtDubaiAirport(): Vehicle
    {
        $category = VehicleCategory::create([
            'name' => 'Economy',
            'slug' => 'economy',
            'description' => 'Economy vehicles',
            'status' => true,
        ]);

        $vendor = User::factory()->create([
            'role' => 'vendor',
            'status' => 'active',
        ]);

        UserProfile::create([
            'user_id' => $vendor->id,
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'currency' => 'EUR',
        ]);

        VendorProfile::create([
            'user_id' => $vendor->id,
            'company_name' => 'Airport Fleet Co',
            'company_email' => 'fleet@example.com',
            'company_phone_number' => '+971500000000',
            'company_address' => 'Terminal 1',
            'company_gst_number' => 'GST-DXB-'.$vendor->id,
            'status' => 'approved',
        ]);

        $location = VendorLocation::create([
            'vendor_id' => $vendor->id,
            'name' => 'Dubai Airport (DXB)',
            'code' => 'dxb-'.$vendor->id,
            'address_line_1' => 'Dubai Airport Terminal 1',
            'city' => 'Dubai',
            'state' => null,
            'country' => 'United Arab Emirates',
            'country_code' => 'AE',
            'latitude' => 25.251369,
            'longitude' => 55.347204,
            'location_type' => 'airport',
            'iata_code' => 'DXB',
            'is_active' => true,
        ]);

        $vehicle = Vehicle::create([
            'vendor_id' => $vendor->id,
            'vendor_location_id' => $location->id,
            'category_id' => $category->id,
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'sipp_code' => 'ECAR',
            'seating_capacity' => 5,
            'number_of_doors' => 4,
            'transmission' => 'automatic',
            'fuel' => 'petrol',
            'air_conditioning' => true,
            'luggage_capacity' => 2,
            'horsepower' => 100,
            'co2' => '110',
            'color' => 'White',
            'mileage' => 10000,
            'location' => 'Dubai Airport (DXB)',
            'location_type' => 'airport',
            'city' => 'Dubai',
            'state' => null,
            'country' => 'United Arab Emirates',
            'full_vehicle_address' => 'Dubai Airport Terminal 1',
            'latitude' => 25.251369,
            'longitude' => 55.347204,
            'status' => 'available',
            'features' => json_encode([]),
            'featured' => false,
            'security_deposit' => 150,
            'payment_method' => 'card',
            'price_per_day' => 43.3333,
            'pickup_times' => ['09:00'],
            'return_times' => ['09:00'],
        ]);

        VehicleImage::create([
            'vehicle_id' => $vehicle->id,
            'image_path' => '',
            'image_url' => 'https://example.com/yaris.jpg',
            'image_type' => 'primary',
        ]);

        return $vehicle;
    }

    private function createSecondVehicleAtSameLocation(Vehicle $baseVehicle): Vehicle
    {
        $vehicle = Vehicle::create([
            'vendor_id' => $baseVehicle->vendor_id,
            'vendor_location_id' => $baseVehicle->vendor_location_id,
            'category_id' => $baseVehicle->category_id,
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'sipp_code' => 'CDAR',
            'seating_capacity' => 5,
            'number_of_doors' => 4,
            'transmission' => 'automatic',
            'fuel' => 'petrol',
            'air_conditioning' => true,
            'luggage_capacity' => 3,
            'horsepower' => 120,
            'co2' => '120',
            'color' => 'Black',
            'mileage' => 10000,
            'location' => 'Dubai Airport (DXB)',
            'location_type' => 'airport',
            'city' => 'Dubai',
            'state' => null,
            'country' => 'United Arab Emirates',
            'full_vehicle_address' => 'Dubai Airport Terminal 1',
            'latitude' => 25.251369,
            'longitude' => 55.347204,
            'status' => 'available',
            'features' => json_encode([]),
            'featured' => false,
            'security_deposit' => 150,
            'payment_method' => 'card',
            'price_per_day' => 55,
            'pickup_times' => ['09:00'],
            'return_times' => ['09:00'],
        ]);

        VehicleImage::create([
            'vehicle_id' => $vehicle->id,
            'image_path' => '',
            'image_url' => 'https://example.com/corolla.jpg',
            'image_type' => 'primary',
        ]);

        return $vehicle;
    }

    private function createCustomer(): Customer
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        return Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'trabber-customer@example.com',
            'phone' => '+971500000001',
            'driver_age' => 35,
        ]);
    }
}
