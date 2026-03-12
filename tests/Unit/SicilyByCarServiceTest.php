<?php

namespace Tests\Unit;

use App\Services\SicilyByCarService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use ReflectionClass;
use Tests\TestCase;

class SicilyByCarServiceTest extends TestCase
{
    public function test_it_builds_pending_request_with_configured_ssl_verification_flag(): void
    {
        config([
            'services.sicily_by_car.base_url' => 'https://booking.sbc.it/dev',
            'services.sicily_by_car.account_code' => 'demo',
            'services.sicily_by_car.api_key' => '24e07.Demo',
            'services.sicily_by_car.timeout' => 20,
            'services.sicily_by_car.verify_ssl' => false,
        ]);

        $service = new class () extends SicilyByCarService {
            public function exposePendingRequest(): PendingRequest
            {
                return $this->makePendingRequest();
            }
        };

        $request = $service->exposePendingRequest();
        $reflection = new ReflectionClass($request);
        $options = $reflection->getProperty('options');
        $options->setAccessible(true);

        $this->assertFalse($options->getValue($request)['verify']);
    }

    public function test_list_locations_returns_locations_from_api_response(): void
    {
        config([
            'services.sicily_by_car.base_url' => 'https://booking.sbc.it/dev',
            'services.sicily_by_car.account_code' => 'demo',
            'services.sicily_by_car.api_key' => '24e07.Demo',
            'services.sicily_by_car.timeout' => 20,
            'services.sicily_by_car.verify_ssl' => false,
        ]);

        Http::fake([
            'https://booking.sbc.it/dev/v2/demo/locations/list' => Http::response([
                'requestId' => 'abc',
                'locations' => [
                    [
                        'id' => 'IT020',
                        'name' => 'PISA',
                        'type' => 'Airport',
                        'airportCode' => null,
                    ],
                ],
                'errors' => null,
            ], 200),
        ]);

        $service = new SicilyByCarService();

        $locations = $service->listLocations();

        $this->assertCount(1, $locations);
        $this->assertSame('IT020', $locations[0]['id']);
        $this->assertSame('PISA', $locations[0]['name']);
    }
}
