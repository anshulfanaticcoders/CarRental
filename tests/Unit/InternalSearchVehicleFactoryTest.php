<?php

namespace Tests\Unit;

use App\Services\Search\InternalSearchVehicleFactory;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class InternalSearchVehicleFactoryTest extends TestCase
{
    public function test_it_builds_the_canonical_internal_search_vehicle_payload(): void
    {
        $factory = app(InternalSearchVehicleFactory::class);

        $vehicle = [
            'id' => 327,
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'category' => ['name' => 'Economy'],
            'transmission' => 'automatic',
            'fuel' => 'petrol',
            'seating_capacity' => 5,
            'doors' => 4,
            'small_bags' => 1,
            'medium_bags' => 1,
            'large_bags' => 0,
            'air_conditioning' => true,
            'price_per_day' => 30.0,
            'security_deposit' => 150.0,
            'payment_method' => ['cash', 'card'],
            'location' => 'Dubai Airport (DXB)',
            'city' => 'Dubai',
            'state' => null,
            'country' => 'United Arab Emirates',
            'latitude' => 25.251369,
            'longitude' => 55.347204,
            'images' => [
                ['image_type' => 'primary', 'image_url' => 'https://example.com/internal/yaris-primary.jpg'],
                ['image_type' => 'gallery', 'image_url' => 'https://example.com/internal/yaris-gallery.jpg'],
            ],
            'vendorPlans' => [
                [
                    'id' => 91,
                    'plan_type' => 'PRE',
                    'plan_description' => 'Premium internal package',
                    'price' => 40.0,
                    'features' => ['Priority support', 'Lower excess'],
                ],
            ],
            'addons' => [
                [
                    'id' => 11,
                    'addon_id' => 101,
                    'extra_name' => 'Baby Seat',
                    'description' => 'Rear-facing seat',
                    'price' => 4.0,
                    'quantity' => 2,
                ],
            ],
            'benefits' => [
                'limited_km_per_day' => 1,
                'limited_km_per_day_range' => 300,
                'price_per_km_per_day' => 1.5,
                'deposit_amount' => 150.0,
                'deposit_currency' => 'EUR',
                'excess_amount' => 650.0,
                'excess_theft_amount' => 700.0,
                'cancellation_available_per_day' => 1,
                'cancellation_available_per_day_date' => 2,
            ],
            'vendorProfileData' => [
                'company_name' => 'Desert Drive LLC',
                'currency' => 'EUR',
            ],
            'operating_hours' => [
                ['day' => 0, 'day_name' => 'Monday', 'is_open' => true, 'open_time' => '09:00', 'close_time' => '18:00'],
            ],
        ];

        $payload = $factory->make($vehicle, 3, [
            'pickup_location_id' => '3272373056',
            'dropoff_location_id' => '3272373056',
        ]);

        $this->assertSame('327', $payload['id']);
        $this->assertNull($payload['gateway_vehicle_id']);
        $this->assertSame('327', $payload['provider_vehicle_id']);
        $this->assertSame('internal', $payload['source']);
        $this->assertSame('internal', $payload['provider_code']);
        $this->assertSame('Toyota Yaris', $payload['display_name']);
        $this->assertSame('Toyota', $payload['brand']);
        $this->assertSame('Yaris', $payload['model']);
        $this->assertSame('economy', $payload['category']);
        $this->assertSame('https://example.com/internal/yaris-primary.jpg', $payload['image']);

        $this->assertSame([
            'transmission' => 'automatic',
            'fuel' => 'petrol',
            'seating_capacity' => 5.0,
            'doors' => 4.0,
            'luggage_small' => 1.0,
            'luggage_medium' => 1.0,
            'luggage_large' => 0.0,
            'air_conditioning' => true,
            'sipp_code' => null,
        ], $payload['specs']);

        $this->assertSame([
            'currency' => 'EUR',
            'price_per_day' => 30.0,
            'total_price' => 90.0,
            'deposit_amount' => 150.0,
            'deposit_currency' => 'EUR',
            'excess_amount' => 650.0,
            'excess_theft_amount' => 700.0,
        ], $payload['pricing']);

        $this->assertSame([
            'mileage_policy' => 'limited',
            'mileage_limit_km' => 300.0,
            'fuel_policy' => null,
            'cancellation' => [
                'available' => true,
                'days_before_pickup' => 2,
            ],
        ], $payload['policies']);

        $this->assertCount(2, $payload['products']);
        $this->assertSame('BAS', $payload['products'][0]['type']);
        $this->assertSame(90.0, $payload['products'][0]['total']);
        $this->assertSame('PRE', $payload['products'][1]['type']);
        $this->assertSame(120.0, $payload['products'][1]['total']);

        $this->assertCount(1, $payload['extras_preview']);
        $this->assertSame('101', $payload['extras_preview'][0]['code']);
        $this->assertSame(12.0, $payload['extras_preview'][0]['total_for_booking']);

        $this->assertSame('3272373056', $payload['location']['pickup']['provider_location_id']);
        $this->assertSame('Dubai Airport (DXB)', $payload['location']['pickup']['name']);
        $this->assertSame(25.251369, $payload['location']['pickup']['latitude']);
        $this->assertSame(55.347204, $payload['location']['pickup']['longitude']);

        $this->assertSame([], $payload['data_quality_flags']);
        $this->assertSame([], $payload['pricing_transparency_flags']);
        $this->assertSame(['image' => false], $payload['ui_placeholders']);

        $legacyPayload = $payload['booking_context']['provider_payload'];
        $this->assertSame('Desert Drive LLC', $legacyPayload['vendorProfileData']['company_name']);
        $this->assertSame(150.0, $legacyPayload['security_deposit']);
        $this->assertSame('Baby Seat', $legacyPayload['addons'][0]['extra_name']);
        $this->assertSame('PRE', $legacyPayload['vendorPlans'][0]['plan_type']);
    }

    public function test_it_prefers_a_fresh_upcloud_url_from_image_path_over_a_stale_image_url(): void
    {
        Config::set('filesystems.disks.upcloud.url', 'https://my-public-bucket.4tcl8.upcloudobjects.com');

        $factory = app(InternalSearchVehicleFactory::class);

        $vehicle = [
            'id' => 328,
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'category' => ['name' => 'Economy'],
            'transmission' => 'automatic',
            'fuel' => 'petrol',
            'seating_capacity' => 5,
            'doors' => 4,
            'air_conditioning' => true,
            'price_per_day' => 30.0,
            'security_deposit' => 150.0,
            'location' => 'Dubai Airport (DXB)',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'latitude' => 25.251369,
            'longitude' => 55.347204,
            'images' => [
                [
                    'image_type' => 'primary',
                    'image_path' => 'vehicle_images/WhatsApp Image 2026-04-15 328.jpg',
                    'image_url' => 'https://example.com/stale-internal-image.jpg',
                ],
            ],
            'vendorProfileData' => [
                'company_name' => 'Desert Drive LLC',
                'currency' => 'EUR',
            ],
        ];

        $payload = $factory->make($vehicle, 3, [
            'pickup_location_id' => '3272373056',
            'dropoff_location_id' => '3272373056',
        ]);

        $this->assertSame(
            'https://my-public-bucket.4tcl8.upcloudobjects.com/vehicle_images/WhatsApp%20Image%202026-04-15%20328.jpg',
            $payload['image']
        );
        $this->assertSame(
            'https://my-public-bucket.4tcl8.upcloudobjects.com/vehicle_images/WhatsApp%20Image%202026-04-15%20328.jpg',
            $payload['booking_context']['provider_payload']['images'][0]['image_url']
        );
    }
}
