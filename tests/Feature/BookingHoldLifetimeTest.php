<?php

namespace Tests\Feature;

use App\Http\Controllers\StripeCheckoutController;
use App\Models\BookingHold;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VendorLocation;
use App\Services\StripeBookingService;
use App\Services\Vehicles\InternalVehicleAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The internal-vehicle hold must outlive the Stripe session (30 min). The old
 * 15-min hold left minutes 15-30 as a double-payment window: customer B books
 * while customer A's session is still payable, then A's webhook rolls back to
 * a manual refund. Reused holds must also re-arm, because a retried checkout
 * mints a fresh 30-min session.
 */
class BookingHoldLifetimeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createVehicle(): Vehicle
    {
        $category = VehicleCategory::firstOrCreate(
            ['slug' => 'economy'],
            ['name' => 'Economy', 'description' => 'Economy vehicles', 'status' => true]
        );
        $vendor = User::factory()->create(['role' => 'vendor', 'status' => 'active']);
        $location = VendorLocation::create([
            'vendor_id' => $vendor->id,
            'name' => 'Faro Airport', 'code' => 'vl-'.$vendor->id.'-fao',
            'address_line_1' => 'Faro Airport', 'city' => 'Faro',
            'country' => 'Portugal', 'country_code' => 'PT',
            'latitude' => 37.0146, 'longitude' => -7.9659,
            'location_type' => 'airport', 'iata_code' => 'FAO', 'is_active' => true,
        ]);

        return Vehicle::create([
            'vendor_id' => $vendor->id, 'vendor_location_id' => $location->id,
            'category_id' => $category->id, 'brand' => 'Toyota', 'model' => 'Yaris',
            'transmission' => 'automatic', 'fuel' => 'petrol', 'body_style' => 'hatchback',
            'air_conditioning' => true, 'sipp_code' => 'ECAR', 'seating_capacity' => 5,
            'number_of_doors' => 4, 'luggage_capacity' => 2, 'horsepower' => 110,
            'co2' => '120', 'color' => 'white', 'mileage' => 20,
            'location' => 'Faro Airport', 'location_type' => 'airport',
            'latitude' => 37.0146, 'longitude' => -7.9659, 'city' => 'Faro',
            'country' => 'Portugal', 'full_vehicle_address' => 'Faro Airport, Portugal',
            'status' => 'available', 'features' => json_encode([]), 'featured' => false,
            'security_deposit' => 200, 'payment_method' => json_encode(['credit_card']),
            'guidelines' => 'g', 'terms_policy' => 't', 'price_per_day' => 55,
            'price_per_week' => 300, 'price_per_month' => 1000,
            'preferred_price_type' => 'day', 'pickup_times' => ['09:00'], 'return_times' => ['09:00'],
        ]);
    }

    private function reserve(array $validated, ?string $searchSessionId): array
    {
        $controller = new StripeCheckoutController(Mockery::mock(StripeBookingService::class));
        $method = new ReflectionMethod($controller, 'reserveInternalVehicleForCheckout');

        return $method->invoke($controller, Request::create('/api/stripe/checkout', 'POST'), $validated, $searchSessionId);
    }

    private function validatedFor(Vehicle $vehicle): array
    {
        return [
            'vehicle' => ['source' => 'internal', 'id' => $vehicle->id],
            'customer' => ['email' => 'hold@example.com'],
            'pickup_date' => now()->addDays(10)->format('Y-m-d'),
            'pickup_time' => '10:00',
            'dropoff_date' => now()->addDays(13)->format('Y-m-d'),
            'dropoff_time' => '10:00',
        ];
    }

    #[Test]
    public function a_new_hold_covers_the_full_stripe_session_lifetime(): void
    {
        $vehicle = $this->createVehicle();
        $availability = Mockery::mock(InternalVehicleAvailabilityService::class);
        $availability->shouldReceive('isVehicleAvailable')->andReturn(true);
        $this->app->instance(InternalVehicleAvailabilityService::class, $availability);

        $result = $this->reserve($this->validatedFor($vehicle), 'sess-hold-1');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['hold']);
        $this->assertTrue(
            $result['hold']->expires_at->greaterThanOrEqualTo(now()->addMinutes(29)),
            'Hold expires at '.$result['hold']->expires_at.' — inside the Stripe session lifetime.'
        );
    }

    #[Test]
    public function a_reused_hold_is_rearmed_for_the_new_session(): void
    {
        $vehicle = $this->createVehicle();
        $validated = $this->validatedFor($vehicle);

        $stale = BookingHold::create([
            'vehicle_id' => $vehicle->id,
            'search_session_id' => 'sess-hold-2',
            'customer_email' => 'hold@example.com',
            'pickup_date' => $validated['pickup_date'],
            'pickup_time' => $validated['pickup_time'],
            'dropoff_date' => $validated['dropoff_date'],
            'dropoff_time' => $validated['dropoff_time'],
            'expires_at' => now()->addMinutes(3), // nearly expired from the first attempt
            'status' => 'active',
        ]);

        $result = $this->reserve($validated, 'sess-hold-2');

        $this->assertTrue($result['success']);
        $this->assertSame($stale->id, $result['hold']->id);
        $this->assertTrue(
            $stale->fresh()->expires_at->greaterThanOrEqualTo(now()->addMinutes(29)),
            'A reused hold must be re-armed to cover the retried checkout\'s fresh session.'
        );
    }
}
