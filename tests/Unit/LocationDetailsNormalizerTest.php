<?php

namespace Tests\Unit;

use App\Services\Bookings\LocationDetailsNormalizer;
use Tests\TestCase;

class LocationDetailsNormalizerTest extends TestCase
{
    public function test_it_returns_null_for_empty_input(): void
    {
        $this->assertNull(LocationDetailsNormalizer::normalize(null));
        $this->assertNull(LocationDetailsNormalizer::normalize([]));
        $this->assertNull(LocationDetailsNormalizer::normalize(['name' => '', 'latitude' => null]));
    }

    public function test_it_produces_the_canonical_shape_with_known_keys(): void
    {
        $normalized = LocationDetailsNormalizer::normalize([
            'name' => 'Dubai Airport',
            'address' => 'Airport Rd',
            'city' => 'Dubai',
            'postal_code' => '12345',
            'country' => 'UAE',
            'phone' => '+971-xx',
            'latitude' => 25.25,
            'longitude' => 55.36,
            'collection_details' => 'Enter via Gate 3',
        ]);

        $this->assertNotNull($normalized);
        $this->assertSame('Dubai Airport', $normalized['name']);
        $this->assertSame('Airport Rd', $normalized['address_1']);
        $this->assertSame('Dubai', $normalized['address_city']);
        $this->assertSame('12345', $normalized['address_postcode']);
        $this->assertSame('UAE', $normalized['address_country']);
        $this->assertSame('+971-xx', $normalized['telephone']);
        $this->assertEqualsWithDelta(25.25, $normalized['latitude'], 0.0001);
        $this->assertEqualsWithDelta(55.36, $normalized['longitude'], 0.0001);
        $this->assertSame('Enter via Gate 3', $normalized['pickup_instructions']);
        // Full key set present (even if null) so downstream readers don't need fallbacks
        foreach (['address_2', 'address_3', 'dropoff_instructions', 'opening_hours'] as $key) {
            $this->assertArrayHasKey($key, $normalized);
        }
    }

    public function test_is_distinct_location_by_coordinates(): void
    {
        $dubai = LocationDetailsNormalizer::normalize(['name' => 'DXB', 'latitude' => 25.25, 'longitude' => 55.36]);
        $abuDhabi = LocationDetailsNormalizer::normalize(['name' => 'AUH', 'latitude' => 24.46, 'longitude' => 54.51]);

        $this->assertTrue(LocationDetailsNormalizer::isDistinctLocation($dubai, $abuDhabi));
        $this->assertFalse(LocationDetailsNormalizer::isDistinctLocation($dubai, $dubai));
    }

    public function test_is_distinct_location_falls_back_to_name_when_coords_missing(): void
    {
        $a = LocationDetailsNormalizer::normalize(['name' => 'DXB']);
        $b = LocationDetailsNormalizer::normalize(['name' => 'AUH']);

        $this->assertTrue(LocationDetailsNormalizer::isDistinctLocation($a, $b));
        $this->assertFalse(LocationDetailsNormalizer::isDistinctLocation($a, $a));
    }

    public function test_coordinate_normalisation_rejects_zero_as_missing(): void
    {
        // Zero coords often mean "not set" in legacy payloads. Treat as null so
        // the BookingDetails map can correctly detect one-way vs round-trip.
        $normalized = LocationDetailsNormalizer::normalize([
            'name' => 'X',
            'latitude' => 0,
            'longitude' => 0,
        ]);

        $this->assertNull($normalized['latitude']);
        $this->assertNull($normalized['longitude']);
    }
}
