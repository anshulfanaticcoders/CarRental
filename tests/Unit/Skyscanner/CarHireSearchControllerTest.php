<?php

namespace Tests\Unit\Skyscanner;

use App\Http\Controllers\Skyscanner\CarHireSearchController;
use App\Services\Skyscanner\CarHireSearchService;
use Illuminate\Http\Request;
use Tests\TestCase;

class CarHireSearchControllerTest extends TestCase
{
    public function test_it_rejects_requests_with_an_invalid_api_key(): void
    {
        config([
            'skyscanner.api_key' => 'secret-key',
            'skyscanner.testing_access.auth_header' => 'x-api-key',
        ]);

        $controller = app(CarHireSearchController::class);

        $response = $controller(Request::create('/api/skyscanner/car-hire/search', 'POST', [], [], [], [
            'HTTP_X_API_KEY' => 'wrong-key',
        ]));

        $payload = $response->getData(true);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('invalid_api_key', $payload['error']);
    }

    public function test_it_returns_search_results_for_a_valid_authenticated_request(): void
    {
        config([
            'skyscanner.api_key' => 'secret-key',
            'skyscanner.testing_access.auth_header' => 'x-api-key',
        ]);

        $this->mock(CarHireSearchService::class, function ($mock): void {
            $mock->shouldReceive('search')
                ->once()
                ->with([
                    'pickup_location_id' => 101,
                    'dropoff_location_id' => 101,
                    'pickup_date' => '2026-06-15',
                    'pickup_time' => '09:00',
                    'dropoff_date' => '2026-06-18',
                    'dropoff_time' => '09:00',
                    'driver_age' => 35,
                    'currency' => 'EUR',
                ])
                ->andReturn([
                    'search' => [
                        'pickup_location_id' => 101,
                    ],
                    'quotes' => [
                        [
                            'quote_id' => 'quote-123',
                        ],
                    ],
                    'excluded_vehicle_ids' => [],
                ]);
        });

        $controller = app(CarHireSearchController::class);

        $response = $controller(Request::create('/api/skyscanner/car-hire/search', 'POST', [
            'pickup_location_id' => 101,
            'dropoff_location_id' => 101,
            'pickup_date' => '2026-06-15',
            'pickup_time' => '09:00',
            'dropoff_date' => '2026-06-18',
            'dropoff_time' => '09:00',
            'driver_age' => 35,
            'currency' => 'EUR',
        ], [], [], [
            'HTTP_X_API_KEY' => 'secret-key',
        ]));

        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, count($payload['quotes']));
        $this->assertSame('quote-123', $payload['quotes'][0]['quote_id']);
    }
}
