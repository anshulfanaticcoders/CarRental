<?php

namespace Tests\Unit;

use App\Services\VrooemGatewayService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VrooemGatewayReadinessTest extends TestCase
{
    public function test_booking_readiness_requires_both_redis_and_mysql(): void
    {
        config(['vrooem.url' => 'http://gateway.test']);
        Http::fakeSequence()
            ->push([
                'status' => 'degraded',
                'redis' => 'disconnected',
                'mysql' => 'connected',
            ])
            ->push([
                'status' => 'ready',
                'redis' => 'connected',
                'mysql' => 'connected',
            ]);

        $service = new VrooemGatewayService;
        $this->assertFalse($service->isReadyForBooking());

        $this->assertTrue($service->isReadyForBooking());
    }
}
