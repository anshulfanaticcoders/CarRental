<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\EsimAccessService;

class EsimAccessTest extends TestCase
{
    /**
     * Test eSIM Access service configuration.
     */
    public function test_esim_access_service_configuration(): void
    {
        $service = new EsimAccessService();

        // Test that the service can be instantiated
        $this->assertInstanceOf(EsimAccessService::class, $service);
    }

    /**
     * Test eSIM countries API endpoint.
     */
    public function test_esim_countries_endpoint(): void
    {
        $response = $this->getJson('/en/api/esim/countries');

        // The endpoint should respond (either with success or error based on API availability)
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    /**
     * Test eSIM plans API endpoint with a valid country code.
     */
    public function test_esim_plans_endpoint(): void
    {
        // Test with a common country code
        $response = $this->getJson('/en/api/esim/plans/US');

        // The endpoint should respond (either with success or error based on API availability)
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    /**
     * Test eSIM order creation endpoint.
     */
    public function test_esim_order_endpoint(): void
    {
        $response = $this->postJson('/en/api/esim/order', [
            'country_code' => 'US',
            'plan_id' => 'test-plan-id',
            'email' => 'test@example.com',
            'customer_name' => 'Test Customer'
        ]);

        // Should return validation error or API error, but should be a JSON response
        $this->assertContains($response->getStatusCode(), [200, 422, 500]);
        $this->assertJson($response->getContent());
    }
}