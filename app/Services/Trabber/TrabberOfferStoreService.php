<?php

namespace App\Services\Trabber;

use Illuminate\Support\Facades\Cache;

class TrabberOfferStoreService
{
    public function put(string $offerId, array $payload): void
    {
        Cache::put($this->key($offerId), $payload, $this->expiresAt());
    }

    public function putOfferResults(string $offerId, array $offerResults): void
    {
        $payload = $this->get($offerId);

        if (! is_array($payload)) {
            return;
        }

        $payload['offer_results'] = $offerResults;

        $this->put($offerId, $payload);
    }

    public function get(string $offerId): ?array
    {
        $payload = Cache::get($this->key($offerId));

        return is_array($payload) ? $payload : null;
    }

    private function key(string $offerId): string
    {
        return 'trabber:offer:'.$offerId;
    }

    private function expiresAt(): \Illuminate\Support\Carbon
    {
        return now()->addMinutes((int) config('trabber.offer_ttl_minutes', 60));
    }
}
