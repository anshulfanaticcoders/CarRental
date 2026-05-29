<?php

namespace App\Http\Controllers\Trabber;

use App\Http\Controllers\Controller;
use App\Services\Trabber\TrabberOfferBookingAdapter;
use App\Services\Trabber\TrabberOfferPageService;
use App\Services\Trabber\TrabberOfferStoreService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OfferController extends Controller
{
    public function __construct(
        private readonly TrabberOfferStoreService $offers,
        private readonly TrabberOfferPageService $offerPage,
        private readonly TrabberOfferBookingAdapter $bookingAdapter,
    ) {}

    public function __invoke(Request $request, string $locale, string $offerId): Response
    {
        $payload = $this->offers->get($offerId);

        abort_if($payload === null, 404, 'Offer not found.');

        $quote = $this->offerPage->quoteFromPayload($payload);
        $validation = $this->offerPage->revalidate($quote);
        $isExpired = ($validation['valid'] ?? false) !== true;
        $quotes = $this->quotesForPayload($payload, $quote, $offerId);
        $bookingContexts = [];

        if (! $isExpired) {
            foreach ($quotes as $offerQuote) {
                $offerQuoteId = (string) ($offerQuote['quote_id'] ?? '');

                if ($offerQuoteId === '') {
                    continue;
                }

                $bookingContexts[$offerQuoteId] = $this->bookingAdapter->build($offerQuote);
            }
        }

        return Inertia::render('OfferResults', [
            'quote' => $quote,
            'offerResults' => [
                'selected_quote_id' => $offerId,
                'search' => $quote['search'] ?? [],
                'quotes' => $quotes,
            ],
            'bookingContext' => ! $isExpired ? $this->bookingAdapter->build($quote) : null,
            'bookingContexts' => $bookingContexts,
            'quoteStatus' => [
                'valid' => ! $isExpired,
                'expired' => $isExpired,
                'reason' => $validation['reason'] ?? null,
                'message' => $isExpired
                    ? __('offerresults.offer_expired_message')
                    : null,
                'search_again_url' => $this->offerPage->searchAgainUrl($locale, $quote),
            ],
        ]);
    }

    private function quotesForPayload(array $payload, array $selectedQuote, string $selectedOfferId): array
    {
        $offerResults = is_array($payload['offer_results'] ?? null) ? $payload['offer_results'] : [];
        $offers = is_array($offerResults['offers'] ?? null) ? $offerResults['offers'] : [];
        $quotes = [];

        foreach ($offers as $offer) {
            if (! is_array($offer)) {
                continue;
            }

            $offerId = (string) ($offer['offer_id'] ?? '');
            $offerPayload = $offerId !== '' ? $this->offers->get($offerId) : null;

            if (is_array($offerPayload)) {
                $quotes[] = $this->offerPage->quoteFromPayload($offerPayload);
            }
        }

        if ($quotes === []) {
            $quotes[] = $selectedQuote;
        }

        usort($quotes, static function (array $left, array $right) use ($selectedOfferId): int {
            return ($left['quote_id'] ?? null) === $selectedOfferId ? -1 : (($right['quote_id'] ?? null) === $selectedOfferId ? 1 : 0);
        });

        return $quotes;
    }
}
