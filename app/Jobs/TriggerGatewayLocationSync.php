<?php

namespace App\Jobs;

use App\Services\VrooemGatewayService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class TriggerGatewayLocationSync
{
    use Dispatchable;

    public function __construct(private readonly string $source = 'unknown') {}

    public function handle(VrooemGatewayService $gateway): void
    {
        if (app()->runningUnitTests() && ! app()->bound(VrooemGatewayService::class)) {
            return;
        }

        try {
            $gateway->triggerLocationSync();
        } catch (\Throwable $exception) {
            Log::warning('Gateway location sync trigger failed', [
                'source' => $this->source,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
