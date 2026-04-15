<?php

namespace Tests\Unit\Skyscanner;

use App\Services\Skyscanner\CarHireOfferBookingAdapter;
use Tests\TestCase;

class CarHireOfferBookingAdapterTest extends TestCase
{
    public function test_it_builds_internal_booking_context_from_a_quote_snapshot(): void
    {
        $service = app(CarHireOfferBookingAdapter::class);

        $context = $service->build([
            'quote_id' => 'quote-123',
            'vehicle' => [
                'provider_vehicle_id' => '326',
                'display_name' => 'Opel Corsa',
                'brand' => 'Opel',
                'model' => 'Corsa',
                'category' => 'city cars',
                'image_url' => 'https://example.com/opel-corsa.jpg',
                'supplier_name' => 'Vrooem Internal Fleet',
                'supplier_code' => 'internal',
                'sipp_code' => 'ECAR',
                'transmission' => 'automatic',
                'fuel_type' => 'petrol',
                'air_conditioning' => true,
                'seats' => 5,
                'doors' => 5,
                'luggage' => [
                    'small' => 1,
                    'medium' => 1,
                    'large' => 2,
                ],
            ],
            'supplier' => [
                'code' => 'internal',
                'name' => 'Vrooem Internal Fleet',
            ],
            'pricing' => [
                'currency' => 'EUR',
                'total_price' => 138.0,
                'price_per_day' => 46.0,
                'deposit_amount' => 500.0,
                'deposit_currency' => 'EUR',
                'excess_amount' => 750.0,
                'excess_theft_amount' => 900.0,
            ],
            'policies' => [
                'mileage_policy' => 'unlimited',
                'mileage_limit_km' => null,
                'fuel_policy' => 'full_to_full',
                'cancellation' => [
                    'available' => true,
                    'days_before_pickup' => 2,
                ],
            ],
            'pickup_location_details' => [
                'provider_location_id' => '62',
                'name' => 'Menara Airport',
                'address' => 'Menara Airport, Marrakech, Morocco',
                'city' => 'Marrakech',
                'state' => null,
                'country' => 'Morocco',
                'country_code' => 'MA',
                'location_type' => 'airport',
                'iata' => 'RAK',
                'phone' => '+2120493000000',
                'pickup_instructions' => 'Collect at the rental desk.',
                'dropoff_instructions' => 'Return to the rental lane.',
                'latitude' => 31.6069,
                'longitude' => -8.0363,
            ],
            'dropoff_location_details' => [
                'provider_location_id' => '62',
                'name' => 'Menara Airport',
                'address' => 'Menara Airport, Marrakech, Morocco',
                'city' => 'Marrakech',
                'state' => null,
                'country' => 'Morocco',
                'country_code' => 'MA',
                'location_type' => 'airport',
                'iata' => 'RAK',
                'phone' => '+2120493000000',
                'pickup_instructions' => 'Collect at the rental desk.',
                'dropoff_instructions' => 'Return to the rental lane.',
                'latitude' => 31.6069,
                'longitude' => -8.0363,
            ],
            'products' => [
                [
                    'type' => 'BAS',
                    'name' => 'Basic Rental',
                    'subtitle' => 'Standard Package',
                    'total' => 138.0,
                    'price_per_day' => 46.0,
                    'deposit' => 500.0,
                    'deposit_currency' => 'EUR',
                    'excess' => 750.0,
                    'excess_theft_amount' => 900.0,
                    'benefits' => [],
                    'currency' => 'EUR',
                    'is_basic' => true,
                ],
                [
                    'type' => 'PRE',
                    'name' => 'Premium Cover',
                    'subtitle' => 'Reduced excess',
                    'total' => 186.0,
                    'price_per_day' => 62.0,
                    'deposit' => 500.0,
                    'deposit_currency' => 'EUR',
                    'excess' => 250.0,
                    'excess_theft_amount' => 300.0,
                    'benefits' => ['Reduced excess'],
                    'currency' => 'EUR',
                ],
            ],
            'extras_preview' => [
                [
                    'id' => 'internal_addon_9',
                    'code' => '9',
                    'name' => 'Child Seat',
                    'description' => 'Seat for toddlers',
                    'daily_rate' => 8.0,
                    'total_for_booking' => 24.0,
                    'currency' => 'EUR',
                    'max_quantity' => 2,
                ],
            ],
            'search' => [
                'pickup_date' => '2026-05-10',
                'pickup_time' => '09:00',
                'dropoff_date' => '2026-05-13',
                'dropoff_time' => '09:00',
                'driver_age' => '35',
                'currency' => 'EUR',
            ],
        ]);

        $this->assertSame('BAS', $context['initial_package']);
        $this->assertSame('Menara Airport', $context['pickup_location']);
        $this->assertSame(3, $context['number_of_days']);
        $this->assertSame('internal', $context['vehicle']['source']);
        $this->assertSame('326', $context['vehicle']['id']);
        $this->assertSame('326', $context['vehicle']['provider_vehicle_id']);
        $this->assertSame('62', $context['vehicle']['provider_pickup_id']);
        $this->assertSame('ECAR', $context['vehicle']['sipp_code']);
        $this->assertCount(2, $context['vehicle']['products']);
        $this->assertCount(1, $context['optional_extras']);
        $this->assertSame('Child Seat', $context['optional_extras'][0]['name']);
        $this->assertSame('Collect at the rental desk.', $context['location_instructions']);
        $this->assertSame('Menara Airport', $context['location_details']['name']);
        $this->assertSame('Vrooem Internal Fleet', $context['vehicle']['booking_context']['provider_payload']['vendorProfileData']['company_name']);
        $this->assertSame('PRE', $context['vehicle']['booking_context']['provider_payload']['vendorPlans'][0]['plan_type']);
    }

    public function test_it_preserves_provider_source_and_payload_for_non_internal_quotes(): void
    {
        $service = app(CarHireOfferBookingAdapter::class);

        $context = $service->build([
            'quote_id' => 'quote-provider-123',
            'vehicle' => [
                'provider_vehicle_id' => 'gm-vehicle-1',
                'source' => 'greenmotion',
                'provider_code' => 'greenmotion',
                'provider_product_id' => 'product-77',
                'provider_rate_id' => 'rate-88',
                'display_name' => 'Hyundai i10 or similar',
                'brand' => 'Hyundai',
                'model' => 'i10',
                'category' => 'economy',
                'image_url' => 'https://example.com/hyundai-i10.jpg',
                'supplier_name' => 'Green Motion',
                'supplier_code' => 'greenmotion',
                'sipp_code' => 'ECMR',
                'transmission' => 'manual',
                'fuel_type' => 'petrol',
                'air_conditioning' => true,
                'seats' => 5,
                'doors' => 5,
                'booking_context' => [
                    'provider_payload' => [
                        'source' => 'greenmotion',
                        'provider_code' => 'greenmotion',
                        'product_id' => 'product-77',
                        'rate_id' => 'rate-88',
                        'vendorProfileData' => [
                            'company_name' => 'Green Motion',
                        ],
                    ],
                ],
            ],
            'supplier' => [
                'code' => 'greenmotion',
                'name' => 'Green Motion',
            ],
            'pricing' => [
                'currency' => 'EUR',
                'total_price' => 120.0,
                'price_per_day' => 40.0,
            ],
            'pickup_location_details' => [
                'provider_location_id' => 'gm-dxb-1',
                'name' => 'Dubai Airport',
                'address' => 'Airport Road, Dubai, United Arab Emirates',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'country_code' => 'AE',
                'location_type' => 'airport',
                'iata' => 'DXB',
                'latitude' => 25.251369,
                'longitude' => 55.347204,
            ],
            'dropoff_location_details' => [
                'provider_location_id' => 'gm-dxb-1',
                'name' => 'Dubai Airport',
                'address' => 'Airport Road, Dubai, United Arab Emirates',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'country_code' => 'AE',
                'location_type' => 'airport',
                'iata' => 'DXB',
                'latitude' => 25.251369,
                'longitude' => 55.347204,
            ],
            'products' => [],
            'extras_preview' => [],
            'search' => [
                'pickup_date' => '2026-05-10',
                'pickup_time' => '09:00',
                'dropoff_date' => '2026-05-13',
                'dropoff_time' => '09:00',
                'driver_age' => '35',
                'currency' => 'EUR',
            ],
        ]);

        $this->assertSame('greenmotion', $context['vehicle']['source']);
        $this->assertSame('greenmotion', $context['vehicle']['provider_code']);
        $this->assertSame('product-77', $context['vehicle']['provider_product_id']);
        $this->assertSame('rate-88', $context['vehicle']['provider_rate_id']);
        $this->assertSame('greenmotion', $context['vehicle']['booking_context']['provider_payload']['source']);
        $this->assertSame('Green Motion', $context['vehicle']['booking_context']['provider_payload']['vendorProfileData']['company_name']);
    }
}
