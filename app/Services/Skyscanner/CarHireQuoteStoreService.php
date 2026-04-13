<?php

namespace App\Services\Skyscanner;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class CarHireQuoteStoreService
{
    public function put(array $quote, ?array $offerResults = null): void
    {
        $quoteId = (string) ($quote['quote_id'] ?? '');
        $expiresAt = CarbonImmutable::parse((string) ($quote['expires_at'] ?? now('UTC')->toIso8601String()));
        $archiveExpiresAt = $expiresAt->addMinutes((int) config('skyscanner.expired_quote_retention_minutes', 1440));

        Cache::put($this->activeKey($quoteId), $quote, $expiresAt);
        Cache::put($this->archiveKey($quoteId), $quote, $archiveExpiresAt);

        if (is_array($offerResults)) {
            Cache::put($this->activeOfferResultsKey($quoteId), $offerResults, $expiresAt);
            Cache::put($this->archiveOfferResultsKey($quoteId), $offerResults, $archiveExpiresAt);
        }
    }

    public function get(string $quoteId): ?array
    {
        $quote = Cache::get($this->activeKey($quoteId));

        if (!is_array($quote)) {
            $quote = Cache::get($this->archiveKey($quoteId));
        }

        if (!is_array($quote)) {
            return null;
        }

        $offerResults = Cache::get($this->activeOfferResultsKey($quoteId));

        if (!is_array($offerResults)) {
            $offerResults = Cache::get($this->archiveOfferResultsKey($quoteId));
        }

        if (is_array($offerResults)) {
            $quote['offer_results'] = $offerResults;
        }

        return $quote;
    }

    private function activeKey(string $quoteId): string
    {
        return 'skyscanner.quote.' . $quoteId;
    }

    private function archiveKey(string $quoteId): string
    {
        return 'skyscanner.quote.archive.' . $quoteId;
    }

    private function activeOfferResultsKey(string $quoteId): string
    {
        return 'skyscanner.quote.offers.' . $quoteId;
    }

    private function archiveOfferResultsKey(string $quoteId): string
    {
        return 'skyscanner.quote.offers.archive.' . $quoteId;
    }
}
