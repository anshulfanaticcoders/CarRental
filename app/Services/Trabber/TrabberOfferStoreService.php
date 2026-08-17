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

    /**
     * One shared write per SEARCH; offers keep a pointer. Rewriting every
     * offer with the complete offer list was O(n²) — up to 50 offers each
     * carrying 50 offers' payload.
     */
    public function putSharedOfferResults(string $searchKey, array $offerResults): void
    {
        Cache::put('trabber:offer_results:'.$searchKey, $offerResults, $this->expiresAt());
    }

    public function attachSharedOfferResults(string $offerId, string $searchKey): void
    {
        $payload = Cache::get($this->key($offerId));

        if (! is_array($payload)) {
            return;
        }

        $payload['offer_results_ref'] = $searchKey;

        $this->put($offerId, $payload);
    }

    public function get(string $offerId): ?array
    {
        $payload = Cache::get($this->key($offerId));

        if (! is_array($payload)) {
            return null;
        }

        // Resolve the shared-results pointer so consumers keep reading
        // $payload['offer_results'] exactly as before.
        if (! isset($payload['offer_results']) && ! empty($payload['offer_results_ref'])) {
            $shared = Cache::get('trabber:offer_results:'.$payload['offer_results_ref']);
            if (is_array($shared)) {
                $payload['offer_results'] = $shared;
            }
        }

        return $payload;
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
