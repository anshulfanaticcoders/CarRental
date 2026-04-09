<?php

namespace App\Services\Skyscanner;

use Illuminate\Support\Facades\Cache;

class CarHireTrackingService
{
    public function buildRedirectTrackingContext(array $payload = []): array
    {
        return [
            'case_id' => config('skyscanner.case_id'),
            'dv_required_before_go_live' => (bool) config('skyscanner.dv_required_before_go_live'),
            'keyword_tracking_enabled' => (bool) config('skyscanner.keyword_tracking_enabled'),
            'payload' => $payload,
        ];
    }

    public function rememberRedirectCorrelation(string $redirectId, string $quoteId, array $payload = []): void
    {
        $correlation = array_merge([
            'redirect_id' => $redirectId,
            'quote_id' => $quoteId,
        ], $payload);

        Cache::put($this->key($redirectId), $correlation, now()->addHours(24));
    }

    public function getRedirectCorrelation(string $redirectId): ?array
    {
        $correlation = Cache::get($this->key($redirectId));

        return is_array($correlation) ? $correlation : null;
    }

    private function key(string $redirectId): string
    {
        return 'skyscanner.redirect.' . $redirectId;
    }
}
