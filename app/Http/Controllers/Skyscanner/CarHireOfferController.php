<?php

namespace App\Http\Controllers\Skyscanner;

use App\Http\Controllers\Controller;
use App\Services\Skyscanner\CarHireOfferBookingAdapter;
use App\Services\Skyscanner\CarHireQuoteLifecycleService;
use App\Services\Skyscanner\CarHireQuoteStoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class CarHireOfferController extends Controller
{
    public function __construct(
        private readonly CarHireQuoteStoreService $quoteStoreService,
        private readonly CarHireQuoteLifecycleService $quoteLifecycleService,
        private readonly CarHireOfferBookingAdapter $offerBookingAdapter,
    ) {
    }

    public function show(Request $request, string $locale, string $quoteId): Response
    {
        $quote = $this->quoteStoreService->get($quoteId);

        abort_if($quote === null, 404, 'Quote not found.');

        $validation = $this->quoteLifecycleService->revalidate($quote);
        $isExpired = ($validation['valid'] ?? false) !== true;

        $offerResults = is_array($quote['offer_results'] ?? null) ? $quote['offer_results'] : ['quotes' => [$quote]];
        $quotes = is_array($offerResults['quotes'] ?? null) ? $offerResults['quotes'] : [$quote];

        usort($quotes, static function (array $left, array $right) use ($quoteId): int {
            return ($left['quote_id'] ?? null) === $quoteId ? -1 : (($right['quote_id'] ?? null) === $quoteId ? 1 : 0);
        });

        $bookingContexts = [];

        if (!$isExpired) {
            foreach ($quotes as $offerQuote) {
                $offerQuoteId = (string) ($offerQuote['quote_id'] ?? '');

                if ($offerQuoteId === '') {
                    continue;
                }

                $bookingContexts[$offerQuoteId] = $this->offerBookingAdapter->build($offerQuote);
            }
        }

        return Inertia::render('OfferResults', [
            'quote' => $quote,
            'offerResults' => [
                'selected_quote_id' => $quoteId,
                'search' => $offerResults['search'] ?? ($quote['search'] ?? []),
                'quotes' => $quotes,
            ],
            'bookingContext' => !$isExpired ? $this->offerBookingAdapter->build($quote) : null,
            'bookingContexts' => $bookingContexts,
            'quoteStatus' => [
                'valid' => !$isExpired,
                'expired' => $isExpired,
                'reason' => $validation['reason'] ?? null,
                'message' => $isExpired
                    ? 'This offer has expired. Run the search again to see live prices and current availability.'
                    : null,
                'search_again_url' => $this->buildSearchAgainUrl($locale, $quote),
            ],
        ]);
    }

    private function buildSearchAgainUrl(string $locale, array $quote): string
    {
        if (!Route::has('search')) {
            return '/';
        }

        $search = is_array($quote['search'] ?? null) ? $quote['search'] : [];
        $pickup = is_array($quote['pickup_location_details'] ?? null) ? $quote['pickup_location_details'] : [];
        $dropoff = is_array($quote['dropoff_location_details'] ?? null) ? $quote['dropoff_location_details'] : [];
        $source = strtolower(trim((string) data_get($quote, 'vehicle.source', data_get($quote, 'supplier.code', 'internal'))));
        $provider = $source === '' ? 'mixed' : $source;

        return route('search', array_filter([
            'locale' => $locale,
            'where' => $pickup['name'] ?? null,
            'city' => $pickup['city'] ?? null,
            'country' => $pickup['country'] ?? null,
            'latitude' => $pickup['latitude'] ?? null,
            'longitude' => $pickup['longitude'] ?? null,
            'unified_location_id' => $search['pickup_location_id'] ?? null,
            'provider' => $provider,
            'provider_pickup_id' => $provider === 'internal' ? null : ($pickup['provider_location_id'] ?? null),
            'date_from' => $search['pickup_date'] ?? null,
            'date_to' => $search['dropoff_date'] ?? null,
            'start_time' => $search['pickup_time'] ?? null,
            'end_time' => $search['dropoff_time'] ?? null,
            'age' => $search['driver_age'] ?? null,
            'currency' => $search['currency'] ?? null,
            'dropoff_where' => $dropoff['name'] ?? null,
            'dropoff_unified_location_id' => $search['dropoff_location_id'] ?? null,
            'dropoff_location_id' => $provider === 'internal' ? null : ($dropoff['provider_location_id'] ?? null),
            'dropoff_latitude' => $dropoff['latitude'] ?? null,
            'dropoff_longitude' => $dropoff['longitude'] ?? null,
        ], static fn ($value) => $value !== null && $value !== ''));
    }
}
