<?php

namespace Tests\Unit;

use App\Services\Bookings\ProviderBookingContract;
use PHPUnit\Framework\TestCase;

class ProviderBookingContractTest extends TestCase
{
    public function test_it_blocks_external_checkout_without_gateway_context(): void
    {
        $result = (new ProviderBookingContract)->validateCheckout([
            'vehicle' => [
                'source' => 'recordgo',
                'id' => 'recordgo_vehicle_1',
            ],
            'package' => 'BAS',
            'customer' => [
                'name' => 'Test Customer',
                'email' => 'test@example.com',
                'phone' => '+10000000000',
                'driver_age' => 35,
            ],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertContains('vehicle.gateway_vehicle_id', $result['missing_fields']);
        $this->assertContains('gateway_search_id', $result['missing_fields']);
    }

    public function test_it_accepts_renteon_selected_product_gateway_context(): void
    {
        $contract = new ProviderBookingContract;

        $validated = [
            'gateway_search_id' => 'search_123',
            'package' => 'REN_729',
            'customer' => [
                'name' => 'Test Customer',
                'email' => 'test@example.com',
                'phone' => '+10000000000',
                'driver_age' => 35,
            ],
            'vehicle' => [
                'source' => 'renteon',
                'gateway_vehicle_id' => 'gw_root',
                'products' => [
                    [
                        'type' => 'REN_729',
                        'gateway_vehicle_id' => 'gw_selected',
                        'connector_id' => 51,
                        'provider_pickup_office_id' => 490,
                        'provider_dropoff_office_id' => 491,
                        'pricelist_id' => 729,
                        'price_date' => '2026-03-23T00:00:00',
                    ],
                ],
            ],
        ];

        $this->assertTrue($contract->validateCheckout($validated)['valid']);
        $this->assertSame('gw_selected', $contract->gatewayVehicleId($validated));
    }

    public function test_it_blocks_surprice_without_rate_context(): void
    {
        $result = (new ProviderBookingContract)->validateCheckout([
            'gateway_search_id' => 'search_123',
            'package' => 'BAS',
            'customer' => [
                'name' => 'Test Customer',
                'email' => 'test@example.com',
                'phone' => '+10000000000',
                'driver_age' => 35,
            ],
            'vehicle' => [
                'source' => 'surprice',
                'gateway_vehicle_id' => 'gw_surprice_1',
                'provider_pickup_id' => 'DXB',
                'provider_return_id' => 'DXB',
            ],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertContains('vehicle.surprice_vendor_rate_id', $result['missing_fields']);
        $this->assertContains('vehicle.surprice_rate_code', $result['missing_fields']);
    }

    public function test_it_applies_ok_mobility_contract_for_frontend_source_alias(): void
    {
        $result = (new ProviderBookingContract)->validateCheckout([
            'gateway_search_id' => 'search_123',
            'package' => 'BAS',
            'customer' => [
                'name' => 'Test Customer',
                'email' => 'test@example.com',
                'phone' => '+10000000000',
                'driver_age' => 35,
            ],
            'vehicle' => [
                'source' => 'okmobility',
                'gateway_vehicle_id' => 'gw_ok_1',
            ],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertContains('vehicle.ok_mobility_token', $result['missing_fields']);
        $this->assertContains('vehicle.ok_mobility_group_id', $result['missing_fields']);
        $this->assertContains('vehicle.ok_mobility_rate_code', $result['missing_fields']);
    }
}
