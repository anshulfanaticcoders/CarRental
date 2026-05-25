<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingHold;
use App\Models\Customer;
use App\Models\MerchantFeedItem;
use App\Models\PopularPlace;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VehicleImage;
use App\Models\VendorLocation;
use App\Models\VendorProfile;
use App\Services\MerchantFeeds\GoogleMerchantXmlWriter;
use App\Services\VrooemGatewayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://www.vrooem.test',
            'filesystems.disks.upcloud.url' => 'https://cdn.vrooem.test',
            'merchant_feeds.awin.output_path' => $this->feedPath(),
            'merchant_feeds.awin.include_internal' => true,
            'merchant_feeds.awin.include_external' => false,
            'merchant_feeds.awin.pickup_offset_days' => 1,
            'merchant_feeds.awin.rental_days' => 1,
            'merchant_feeds.awin.pickup_time' => '09:00',
            'merchant_feeds.awin.dropoff_time' => '09:00',
            'merchant_feeds.awin.currency' => 'EUR',
            'merchant_feeds.awin.driver_age' => 35,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-05-25 10:00:00'));
    }

    protected function tearDown(): void
    {
        @unlink($this->feedPath());
        @unlink($this->feedPath().'.tmp');
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_xml_writer_outputs_utf8_google_namespaced_fields(): void
    {
        $item = new MerchantFeedItem([
            'feed_key' => 'internal-1',
            'title' => 'Toyota & BMW rental in Dubai',
            'description' => 'Clean UTF-8 feed item for cafe pickup.',
            'link' => 'https://www.vrooem.test/en/vehicle/1',
            'image_link' => 'https://cdn.vrooem.test/vehicle.jpg',
            'price' => 55,
            'currency' => 'EUR',
            'availability' => 'in_stock',
            'brand' => 'Toyota',
            'condition' => 'used',
            'product_type' => 'Car Rental > Economy',
        ]);

        $xml = app(GoogleMerchantXmlWriter::class)->render([$item]);

        $this->assertStringContainsString('<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">', $xml);
        $this->assertStringContainsString('<g:id>internal-1</g:id>', $xml);
        $this->assertStringContainsString('<g:title>Toyota &amp; BMW rental in Dubai</g:title>', $xml);
        $this->assertStringContainsString('<g:price>55.00 EUR</g:price>', $xml);
        $this->assertNotFalse(simplexml_load_string($xml));
    }

    public function test_feed_route_serves_latest_generated_xml_file(): void
    {
        $path = $this->feedPath();
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        file_put_contents($path, '<?xml version="1.0" encoding="UTF-8"?><rss></rss>');

        $response = $this->get('/feeds/awin/google-merchant.xml');

        $response->assertOk();
        $this->assertStringContainsString('application/xml', $response->headers->get('content-type'));
    }

    public function test_refresh_command_builds_internal_items_and_marks_unavailable_windows(): void
    {
        [$availableVehicle] = $this->createVehicleContext(['brand' => 'Toyota']);
        [$bookedVehicle, , , $customer] = $this->createVehicleContext(['brand' => 'Nissan']);
        [$heldVehicle] = $this->createVehicleContext(['brand' => 'Mazda']);

        Booking::create([
            'booking_number' => 'BK-FEED-1',
            'customer_id' => $customer->id,
            'vehicle_id' => $bookedVehicle->id,
            'pickup_date' => '2026-05-26 00:00:00',
            'return_date' => '2026-05-27 00:00:00',
            'pickup_time' => '09:00',
            'return_time' => '09:00',
            'pickup_location' => 'Dubai Airport',
            'return_location' => 'Dubai Airport',
            'total_days' => 1,
            'base_price' => 60,
            'tax_amount' => 0,
            'total_amount' => 60,
            'booking_status' => 'confirmed',
        ]);

        BookingHold::create([
            'vehicle_id' => $heldVehicle->id,
            'pickup_date' => '2026-05-26',
            'pickup_time' => '09:00',
            'dropoff_date' => '2026-05-27',
            'dropoff_time' => '09:00',
            'expires_at' => now()->addMinutes(30),
            'status' => 'active',
        ]);

        $this->artisan('merchant-feed:refresh', ['feed' => 'awin'])->assertExitCode(0);

        $this->assertSame('in_stock', MerchantFeedItem::where('feed_key', 'internal-'.$availableVehicle->id)->value('availability'));
        $this->assertSame('out_of_stock', MerchantFeedItem::where('feed_key', 'internal-'.$bookedVehicle->id)->value('availability'));
        $this->assertSame('out_of_stock', MerchantFeedItem::where('feed_key', 'internal-'.$heldVehicle->id)->value('availability'));
        $this->assertFileExists($this->feedPath());
        $this->assertStringContainsString('<g:id>internal-'.$availableVehicle->id.'</g:id>', file_get_contents($this->feedPath()));
    }

    public function test_refresh_command_imports_external_gateway_items(): void
    {
        config([
            'merchant_feeds.awin.include_internal' => false,
            'merchant_feeds.awin.include_external' => true,
            'vrooem.enabled' => true,
        ]);

        PopularPlace::create([
            'place_name' => 'Dubai Airport',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'latitude' => 25.251369,
            'longitude' => 55.347204,
            'unified_location_id' => 12345,
            'image' => 'https://cdn.vrooem.test/dxb.jpg',
        ]);

        $this->app->instance(VrooemGatewayService::class, new FakeMerchantFeedGatewayService([
            'search_id' => 'search_feed_1',
            'vehicles' => [$this->gatewayVehicle()],
            'provider_status' => [],
        ]));

        $this->artisan('merchant-feed:refresh', ['feed' => 'awin'])->assertExitCode(0);

        $item = MerchantFeedItem::where('source', 'external')->firstOrFail();
        $this->assertSame('greenmotion', $item->provider);
        $this->assertSame('in_stock', $item->availability);
        $this->assertStringContainsString('/en/s?', $item->link);
        $this->assertStringContainsString('provider=greenmotion', $item->link);
        $this->assertStringContainsString('<g:id>'.$item->feed_key.'</g:id>', file_get_contents($this->feedPath()));
    }

    public function test_external_gateway_failure_keeps_existing_external_items(): void
    {
        config([
            'merchant_feeds.awin.include_internal' => false,
            'merchant_feeds.awin.include_external' => true,
            'vrooem.enabled' => true,
        ]);

        PopularPlace::create([
            'place_name' => 'Dubai Airport',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'latitude' => 25.251369,
            'longitude' => 55.347204,
            'unified_location_id' => 12345,
            'image' => 'https://cdn.vrooem.test/dxb.jpg',
        ]);

        MerchantFeedItem::create([
            'feed_name' => 'awin',
            'feed_key' => 'external-existing',
            'source' => 'external',
            'provider' => 'greenmotion',
            'provider_vehicle_id' => 'old-1',
            'title' => 'Existing car rental',
            'description' => 'Existing external item',
            'link' => 'https://www.vrooem.test/en/s',
            'image_link' => 'https://cdn.vrooem.test/old.jpg',
            'price' => 40,
            'currency' => 'EUR',
            'availability' => 'in_stock',
            'brand' => 'Toyota',
            'product_type' => 'Car Rental',
            'condition' => 'used',
            'last_seen_at' => now()->subHour(),
            'expires_at' => now()->addHours(3),
        ]);

        $this->app->instance(VrooemGatewayService::class, new FakeMerchantFeedGatewayService([
            'search_id' => null,
            'vehicles' => [],
            'provider_status' => [],
        ]));

        $this->artisan('merchant-feed:refresh', ['feed' => 'awin'])->assertExitCode(0);

        $this->assertSame('in_stock', MerchantFeedItem::where('feed_key', 'external-existing')->value('availability'));
        $this->assertStringContainsString('<g:id>external-existing</g:id>', file_get_contents($this->feedPath()));
    }

    private function feedPath(): string
    {
        return storage_path('framework/testing/awin-google-merchant.xml');
    }

    private function gatewayVehicle(): array
    {
        return [
            'id' => 'gw_1',
            'source' => 'green_motion',
            'provider_vehicle_id' => 'GM-ECAR',
            'display_name' => 'Toyota Yaris or similar',
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'category' => 'economy',
            'image' => 'https://cdn.vrooem.test/gateway/yaris.jpg',
            'pricing' => [
                'currency' => 'EUR',
                'price_per_day' => 44.5,
                'total_price' => 44.5,
            ],
            'specs' => [
                'transmission' => 'automatic',
                'fuel' => 'petrol',
                'seating_capacity' => 5,
            ],
            'location' => [
                'pickup' => [
                    'provider_location_id' => 'DXB',
                    'name' => 'Dubai Airport',
                    'latitude' => 25.251369,
                    'longitude' => 55.347204,
                ],
            ],
            'booking_context' => [
                'provider_payload' => [
                    'rate_id' => 'RATE-1',
                    'product_id' => 'BAS',
                ],
            ],
        ];
    }

    private function createVehicleContext(array $vehicleOverrides = []): array
    {
        $category = VehicleCategory::firstOrCreate([
            'slug' => 'economy',
        ], [
            'name' => 'Economy',
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
            'company_email' => 'fleet-'.$vendor->id.'@example.com',
            'company_phone_number' => '+971500000000',
            'company_address' => 'Terminal 1',
            'company_gst_number' => 'GST-DXB-'.$vendor->id,
            'status' => 'approved',
        ]);

        $location = VendorLocation::create([
            'vendor_id' => $vendor->id,
            'name' => 'Dubai Airport',
            'code' => 'vl-'.$vendor->id.'-dxb',
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

        $vehicle = Vehicle::create(array_merge([
            'vendor_id' => $vendor->id,
            'vendor_location_id' => $location->id,
            'category_id' => $category->id,
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'transmission' => 'automatic',
            'fuel' => 'petrol',
            'body_style' => 'hatchback',
            'air_conditioning' => true,
            'sipp_code' => 'ECAR',
            'seating_capacity' => 5,
            'number_of_doors' => 4,
            'luggage_capacity' => 2,
            'horsepower' => 110,
            'co2' => '120',
            'color' => 'white',
            'mileage' => 20,
            'location' => 'Dubai Airport',
            'location_type' => 'airport',
            'latitude' => 25.251369,
            'longitude' => 55.347204,
            'city' => 'Dubai',
            'state' => null,
            'country' => 'United Arab Emirates',
            'full_vehicle_address' => 'Dubai Airport Terminal 1, Dubai, United Arab Emirates',
            'status' => 'available',
            'features' => json_encode(['Air Conditioning']),
            'featured' => false,
            'security_deposit' => 200,
            'payment_method' => json_encode(['credit_card']),
            'guidelines' => 'Bring passport and license.',
            'terms_policy' => 'Return with same fuel level.',
            'price_per_day' => 55,
            'price_per_week' => 300,
            'price_per_month' => 1000,
            'preferred_price_type' => 'day',
            'pickup_times' => ['09:00'],
            'return_times' => ['09:00'],
        ], $vehicleOverrides));

        VehicleImage::create([
            'vehicle_id' => $vehicle->id,
            'image_path' => 'vehicle_images/'.$vehicle->id.'.jpg',
            'image_type' => 'primary',
        ]);

        $customerUser = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'customer-'.$customerUser->id.'@example.com',
            'phone' => '+971500000099',
            'driver_age' => 30,
        ]);

        return [$vehicle, $location, $vendor, $customer];
    }
}

class FakeMerchantFeedGatewayService extends VrooemGatewayService
{
    public function __construct(private readonly array $response) {}

    public function searchVehicles(array $params): array
    {
        return $this->response;
    }
}
