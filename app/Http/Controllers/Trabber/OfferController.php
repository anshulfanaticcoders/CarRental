<?php

namespace App\Http\Controllers\Trabber;

use App\Http\Controllers\Controller;
use App\Services\PriceVerificationService;
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
        private readonly PriceVerificationService $priceVerificationService,
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

            if (! isset($bookingContexts[$offerId])) {
                $bookingContexts[$offerId] = $this->bookingAdapter->build($quote);
            }

            $bookingContexts = $this->prepareBookingContextsForCheckout($offerId, $bookingContexts);
        }

        $selectedBookingContext = $bookingContexts[$offerId] ?? null;

        return Inertia::render('OfferResults', [
            'quote' => $quote,
            'offerResults' => [
                'selected_quote_id' => $offerId,
                'search' => $quote['search'] ?? [],
                'quotes' => $quotes,
            ],
            'bookingContext' => ! $isExpired ? $selectedBookingContext : null,
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

    private function prepareBookingContextsForCheckout(string $offerId, array $bookingContexts): array
    {
        if ($bookingContexts === []) {
            return [];
        }

        $searchSessionId = $this->buildSearchSessionId($offerId);
        $vehicles = [];

        foreach ($bookingContexts as $contextOfferId => $context) {
            if (! is_array($context)) {
                continue;
            }

            $context['search_session_id'] = $searchSessionId;
            $vehicle = is_array($context['vehicle'] ?? null) ? $context['vehicle'] : [];

            if ($vehicle !== []) {
                $vehicle['search_session_id'] = $searchSessionId;
                $gatewaySearchId = $this->resolveGatewaySearchId($context, $vehicle);

                if ($gatewaySearchId !== null) {
                    $context['gateway_search_id'] = $gatewaySearchId;
                    $vehicle['gateway_search_id'] = $gatewaySearchId;

                    if (is_array($vehicle['booking_context']['provider_payload'] ?? null)) {
                        $vehicle['booking_context']['provider_payload']['gateway_search_id'] = $gatewaySearchId;
                        $vehicle['booking_context']['provider_payload']['search_id'] = $gatewaySearchId;
                    }
                }

                $context['vehicle'] = $vehicle;
                $vehicles[] = $vehicle;
            }

            $bookingContexts[$contextOfferId] = $context;
        }

        if ($vehicles === []) {
            return $bookingContexts;
        }

        $priceMap = $this->priceVerificationService->storeOriginalPrices($searchSessionId, $vehicles);

        foreach ($bookingContexts as $contextOfferId => $context) {
            $vehicle = is_array($context['vehicle'] ?? null) ? $context['vehicle'] : [];
            $vehicleId = $this->resolveVehicleId($vehicle);

            if ($vehicleId !== null && isset($priceMap[$vehicleId]['price_hash'])) {
                $vehicle['price_hash'] = $priceMap[$vehicleId]['price_hash'];
                $context['vehicle'] = $vehicle;
            }

            $bookingContexts[$contextOfferId] = $context;
        }

        return $bookingContexts;
    }

    private function buildSearchSessionId(string $offerId): string
    {
        $normalizedOfferId = preg_replace('/[^A-Za-z0-9_-]+/', '-', $offerId) ?: sha1($offerId);

        return 'trabber_offer_'.$normalizedOfferId;
    }

    private function resolveGatewaySearchId(array $context, array $vehicle): ?string
    {
        foreach ([
            $context['gateway_search_id'] ?? null,
            $vehicle['gateway_search_id'] ?? null,
            data_get($vehicle, 'booking_context.provider_payload.gateway_search_id'),
            data_get($vehicle, 'booking_context.provider_payload.search_id'),
        ] as $candidate) {
            $normalized = trim((string) $candidate);

            if ($normalized !== '') {
                return $normalized;
            }
        }

        return null;
    }

    private function resolveVehicleId(array $vehicle): ?string
    {
        foreach ([
            $vehicle['id'] ?? null,
            $vehicle['gateway_vehicle_id'] ?? null,
            $vehicle['provider_vehicle_id'] ?? null,
            $vehicle['unified_location_id'] ?? null,
        ] as $candidate) {
            $normalized = trim((string) $candidate);

            if ($normalized !== '') {
                return $normalized;
            }
        }

        return null;
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
