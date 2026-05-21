<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VendorLocation;
use App\Models\VendorProfile;
use App\Services\LocationSearchService;
use App\Services\Search\GatewaySearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

class SearchControllerInternalLocationFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_internal_search_returns_no_local_cars_when_selected_unified_location_has_no_internal_mapping(): void
    {
        $this->createInternalVehicleAtDubaiAirport();

        $capturedInternalVehicles = null;
        $this->mockSearchDependencies(
            [
                'unified_location_id' => 961653316,
                'name' => 'Dubai Airport (DWC)',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'country_code' => 'AE',
                'latitude' => 25.116073,
                'longitude' => 55.204087,
                'location_type' => 'airport',
                'iata' => 'DWC',
                'providers' => [
                    ['provider' => 'surprice', 'pickup_id' => 'DWC:DWCA01'],
                ],
            ],
            $capturedInternalVehicles
        );

        $response = $this->get('/en/s?'.http_build_query([
            'where' => 'Dubai Airport (DWC)',
            'location' => 'Dubai Airport (DWC)',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'latitude' => 25.116073,
            'longitude' => 55.204087,
            'radius' => 50000,
            'provider' => 'internal',
            'matched_field' => 'city',
            'unified_location_id' => 961653316,
            'date_from' => '2026-06-15',
            'date_to' => '2026-06-16',
            'start_time' => '09:00',
            'end_time' => '09:00',
            'age' => 35,
            'currency' => 'EUR',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(Collection::class, $capturedInternalVehicles);
        $this->assertCount(0, $capturedInternalVehicles);
    }

    public function test_internal_search_uses_the_internal_provider_mapping_from_the_selected_unified_location(): void
    {
        [, $dxbLocation] = $this->createInternalVehicleAtDubaiAirport();
        $this->createInternalVehicleAtDubaiAirport([
            'name' => 'Dubai Downtown',
            'location_type' => 'downtown',
            'latitude' => 25.2048,
            'longitude' => 55.2708,
            'iata_code' => null,
        ], [
            'brand' => 'Nissan',
            'model' => 'Sunny',
            'location' => 'Dubai Downtown',
            'location_type' => 'downtown',
            'latitude' => 25.2048,
            'longitude' => 55.2708,
            'full_vehicle_address' => 'Dubai Downtown, Dubai, United Arab Emirates',
        ]);

        $capturedInternalVehicles = null;
        $this->mockSearchDependencies(
            [
                'unified_location_id' => 3385755165,
                'name' => 'Dubai Airport (DXB)',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'country_code' => 'AE',
                'latitude' => 25.248081,
                'longitude' => 55.345093,
                'location_type' => 'airport',
                'iata' => 'DXB',
                'providers' => [
                    ['provider' => 'internal', 'pickup_id' => (string) $dxbLocation->id],
                    ['provider' => 'surprice', 'pickup_id' => 'DXB:DXBA01'],
                ],
            ],
            $capturedInternalVehicles
        );

        $response = $this->get('/en/s?'.http_build_query([
            'where' => 'Dubai Airport (DXB)',
            'location' => 'Dubai Airport (DXB)',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'latitude' => 25.248081,
            'longitude' => 55.345093,
            'radius' => 50000,
            'provider' => 'internal',
            'matched_field' => 'city',
            'unified_location_id' => 3385755165,
            'date_from' => '2026-06-15',
            'date_to' => '2026-06-16',
            'start_time' => '09:00',
            'end_time' => '09:00',
            'age' => 35,
            'currency' => 'EUR',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(Collection::class, $capturedInternalVehicles);
        $this->assertCount(1, $capturedInternalVehicles);
        $this->assertSame('Toyota', $capturedInternalVehicles->first()['brand']);
    }

    public function test_mixed_search_returns_no_local_cars_when_selected_unified_location_has_no_internal_mapping(): void
    {
        $this->createInternalVehicleAtDubaiAirport();

        $capturedInternalVehicles = null;
        $this->mockSearchDependencies(
            [
                'unified_location_id' => 961653316,
                'name' => 'Dubai Airport (DWC)',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'country_code' => 'AE',
                'latitude' => 25.116073,
                'longitude' => 55.204087,
                'location_type' => 'airport',
                'iata' => 'DWC',
                'providers' => [
                    ['provider' => 'surprice', 'pickup_id' => 'DWC:DWCA01'],
                ],
            ],
            $capturedInternalVehicles
        );

        $response = $this->get('/en/s?'.http_build_query([
            'where' => 'Dubai Airport (DWC)',
            'location' => 'Dubai Airport (DWC)',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'latitude' => 25.116073,
            'longitude' => 55.204087,
            'radius' => 50000,
            'provider' => 'mixed',
            'provider_pickup_id' => 'DWC:DWCA01',
            'matched_field' => 'city',
            'unified_location_id' => 961653316,
            'date_from' => '2026-06-15',
            'date_to' => '2026-06-16',
            'start_time' => '09:00',
            'end_time' => '09:00',
            'age' => 35,
            'currency' => 'EUR',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(Collection::class, $capturedInternalVehicles);
        $this->assertCount(0, $capturedInternalVehicles);
    }

    public function test_mixed_search_uses_internal_provider_mapping_from_the_selected_unified_location(): void
    {
        [, $dxbLocation] = $this->createInternalVehicleAtDubaiAirport();
        $this->createInternalVehicleAtDubaiAirport([
            'name' => 'Dubai Downtown',
            'location_type' => 'downtown',
            'latitude' => 25.2048,
            'longitude' => 55.2708,
            'iata_code' => null,
        ], [
            'brand' => 'Nissan',
            'model' => 'Sunny',
            'location' => 'Dubai Downtown',
            'location_type' => 'downtown',
            'latitude' => 25.2048,
            'longitude' => 55.2708,
            'full_vehicle_address' => 'Dubai Downtown, Dubai, United Arab Emirates',
        ]);

        $capturedInternalVehicles = null;
        $this->mockSearchDependencies(
            [
                'unified_location_id' => 3385755165,
                'name' => 'Dubai Airport (DXB)',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'country_code' => 'AE',
                'latitude' => 25.248081,
                'longitude' => 55.345093,
                'location_type' => 'airport',
                'iata' => 'DXB',
                'providers' => [
                    ['provider' => 'internal', 'pickup_id' => (string) $dxbLocation->id],
                    ['provider' => 'surprice', 'pickup_id' => 'DXB:DXBA01'],
                ],
            ],
            $capturedInternalVehicles
        );

        $response = $this->get('/en/s?'.http_build_query([
            'where' => 'Dubai Airport (DXB)',
            'location' => 'Dubai Airport (DXB)',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'latitude' => 25.248081,
            'longitude' => 55.345093,
            'radius' => 50000,
            'provider' => 'mixed',
            'provider_pickup_id' => 'DXB:DXBA01',
            'matched_field' => 'city',
            'unified_location_id' => 3385755165,
            'date_from' => '2026-06-15',
            'date_to' => '2026-06-16',
            'start_time' => '09:00',
            'end_time' => '09:00',
            'age' => 35,
            'currency' => 'EUR',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(Collection::class, $capturedInternalVehicles);
        $this->assertCount(1, $capturedInternalVehicles);
        $this->assertSame('Toyota', $capturedInternalVehicles->first()['brand']);
    }

    public function test_search_returns_exact_empty_state_when_selected_location_cannot_be_verified(): void
    {
        $this->createInternalVehicleAtDubaiAirport();

        $locationSearchService = Mockery::mock(LocationSearchService::class);
        $locationSearchService->shouldReceive('resolveSearchLocation')
            ->once()
            ->andReturn(null);
        $locationSearchService->shouldReceive('nearbyLocations')
            ->once()
            ->andReturn([]);
        $this->app->instance(LocationSearchService::class, $locationSearchService);

        $gatewaySearchService = Mockery::mock(GatewaySearchService::class);
        $gatewaySearchService->shouldReceive('buildPageProps')->never();
        $this->app->instance(GatewaySearchService::class, $gatewaySearchService);

        $response = $this->get('/en/s?'.http_build_query([
            'where' => 'Dubai Airport (DWC)',
            'location' => 'Dubai Airport (DWC)',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'latitude' => 25.116073,
            'longitude' => 55.204087,
            'radius' => 50000,
            'provider' => 'internal',
            'matched_field' => 'city',
            'unified_location_id' => 999999,
            'date_from' => '2026-06-15',
            'date_to' => '2026-06-16',
            'start_time' => '09:00',
            'end_time' => '09:00',
            'age' => 35,
            'currency' => 'EUR',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('SearchResults')
            ->where('vehicles.total', 0)
            ->where('recommendedLocations', [])
            ->where('searchError', 'We could not verify this pickup location. Please choose a location from the search suggestions.')
        );
    }

    private function mockSearchDependencies(array $resolvedLocation, ?Collection &$capturedInternalVehicles): void
    {
        $locationSearchService = Mockery::mock(LocationSearchService::class);
        $locationSearchService->shouldReceive('resolveSearchLocation')
            ->once()
            ->andReturn($resolvedLocation);
        $this->app->instance(LocationSearchService::class, $locationSearchService);

        $gatewaySearchService = Mockery::mock(GatewaySearchService::class);
        $gatewaySearchService->shouldReceive('buildPageProps')
            ->once()
            ->andReturnUsing(function (
                $request,
                array $validated,
                int $rentalDays,
                Collection $internalVehiclesCollection
            ) use (&$capturedInternalVehicles) {
                $capturedInternalVehicles = $internalVehiclesCollection;

                return [
                    'vehicles' => new LengthAwarePaginator(collect(), 0, 500),
                    'providerStatus' => [],
                    'searchError' => null,
                    'filters' => $validated,
                    'pagination_links' => '',
                    'brands' => [],
                    'colors' => [],
                    'seatingCapacities' => [],
                    'transmissions' => [],
                    'fuels' => [],
                    'mileages' => [],
                    'categories' => [],
                    'schema' => null,
                    'seo' => [],
                    'locale' => 'en',
                    'optionalExtras' => [],
                    'locationName' => $validated['where'] ?? 'Selected Location',
                ];
            });
        $this->app->instance(GatewaySearchService::class, $gatewaySearchService);
    }

    private function createInternalVehicleAtDubaiAirport(array $locationOverrides = [], array $vehicleOverrides = []): array
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
        ]);

        VendorProfile::create([
            'user_id' => $vendor->id,
            'company_name' => 'Airport Fleet Co',
            'company_email' => 'fleet-'.$vendor->id.'@example.com',
            'company_phone_number' => '+97150000'.str_pad((string) $vendor->id, 4, '0', STR_PAD_LEFT),
            'company_address' => 'Terminal 1',
            'company_gst_number' => 'GST-DXB-'.$vendor->id,
            'status' => 'approved',
        ]);

        $location = VendorLocation::create(array_merge([
            'vendor_id' => $vendor->id,
            'name' => 'Dubai Airport (DXB)',
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
        ], $locationOverrides));

        $vehicle = Vehicle::create(array_merge([
            'vendor_id' => $vendor->id,
            'vendor_location_id' => $location->id,
            'category_id' => $category->id,
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'transmission' => 'automatic',
            'fuel' => 'petrol',
            'seating_capacity' => 5,
            'number_of_doors' => 4,
            'luggage_capacity' => 2,
            'horsepower' => 110,
            'co2' => '120',
            'color' => 'white',
            'mileage' => 20,
            'location' => 'Dubai Airport (DXB)',
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
            'preferred_price_type' => 'day',
        ], $vehicleOverrides));

        return [$vehicle, $location];
    }
}
