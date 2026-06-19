<?php

namespace App\Http\Controllers\Skyscanner;

use App\Http\Controllers\Controller;
use App\Services\LocationSearchService;
use App\Services\PriceVerificationService;
use App\Services\Skyscanner\CarHireOfferBookingAdapter;
use App\Services\Skyscanner\CarHirePublicResponseSerializer;
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
        private readonly CarHirePublicResponseSerializer $publicResponseSerializer,
        private readonly PriceVerificationService $priceVerificationService,
        private readonly LocationSearchService $locationSearchService,
    ) {}

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

        if (! $isExpired) {
            foreach ($quotes as $offerQuote) {
                $offerQuoteId = (string) ($offerQuote['quote_id'] ?? '');

                if ($offerQuoteId === '') {
                    continue;
                }

                $bookingContexts[$offerQuoteId] = $this->offerBookingAdapter->build($offerQuote);
            }

            if (! isset($bookingContexts[$quoteId])) {
                $bookingContexts[$quoteId] = $this->offerBookingAdapter->build($quote);
            }

            $bookingContexts = $this->prepareBookingContextsForCheckout($quoteId, $bookingContexts);
        }

        $selectedBookingContext = $bookingContexts[$quoteId] ?? null;

        return Inertia::render('OfferResults', [
            'quote' => $this->buildDisplayQuote($quote),
            'offerResults' => [
                'selected_quote_id' => $quoteId,
                'search' => $offerResults['search'] ?? ($quote['search'] ?? []),
                'quotes' => array_map(fn (array $offerQuote): array => $this->buildDisplayQuote($offerQuote), $quotes),
            ],
            'bookingContext' => ! $isExpired ? $selectedBookingContext : null,
            'bookingContexts' => $bookingContexts,
            'quoteStatus' => [
                'valid' => ! $isExpired,
                'expired' => $isExpired,
                'reason' => $validation['reason'] ?? null,
                'message' => $isExpired
                    ? 'This offer has expired. Run the search again to see live prices and current availability.'
                    : null,
                'search_again_url' => $isExpired ? $this->buildSearchAgainUrl($locale, $quote) : null,
            ],
        ]);
    }

    private function prepareBookingContextsForCheckout(string $quoteId, array $bookingContexts): array
    {
        if ($bookingContexts === []) {
            return [];
        }

        $searchSessionId = $this->buildSearchSessionId($quoteId);
        $vehicles = [];

        foreach ($bookingContexts as $contextQuoteId => $context) {
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

            $bookingContexts[$contextQuoteId] = $context;
        }

        if ($vehicles === []) {
            return $bookingContexts;
        }

        $priceMap = $this->priceVerificationService->storeOriginalPrices($searchSessionId, $vehicles);

        foreach ($bookingContexts as $contextQuoteId => $context) {
            $vehicle = is_array($context['vehicle'] ?? null) ? $context['vehicle'] : [];
            $vehicleId = $this->resolveVehicleId($vehicle);

            if ($vehicleId !== null && isset($priceMap[$vehicleId]['price_hash'])) {
                $vehicle['price_hash'] = $priceMap[$vehicleId]['price_hash'];
                $context['vehicle'] = $vehicle;
            }

            $bookingContexts[$contextQuoteId] = $context;
        }

        return $bookingContexts;
    }

    private function buildSearchSessionId(string $quoteId): string
    {
        $normalizedQuoteId = preg_replace('/[^A-Za-z0-9_-]+/', '-', $quoteId) ?: sha1($quoteId);

        return 'skyscanner_offer_'.$normalizedQuoteId;
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

    private function buildDisplayQuote(array $quote): array
    {
        $displayQuote = $this->publicResponseSerializer->quote($quote);

        foreach (['search', 'products', 'extras_preview', 'inclusions'] as $key) {
            if (is_array($quote[$key] ?? null)) {
                $displayQuote[$key] = $quote[$key];
            }
        }

        return $displayQuote;
    }

    private function buildSearchAgainUrl(string $locale, array $quote): string
    {
        if (! Route::has('search')) {
            return '/';
        }

        $search = is_array($quote['search'] ?? null) ? $quote['search'] : [];
        $pickup = is_array($quote['pickup_location_details'] ?? null) ? $quote['pickup_location_details'] : [];
        $dropoff = is_array($quote['dropoff_location_details'] ?? null) ? $quote['dropoff_location_details'] : [];
        $source = strtolower(trim((string) data_get($quote, 'vehicle.source', data_get($quote, 'supplier.code', 'internal'))));
        $provider = $source === '' ? 'mixed' : $source;
        $pickupUnifiedLocationId = $this->resolveSearchAgainUnifiedLocationId($search, $pickup, 'pickup_location_id', $provider);
        $dropoffUnifiedLocationId = $this->resolveSearchAgainUnifiedLocationId($search, $dropoff, 'dropoff_location_id', $provider)
            ?? $pickupUnifiedLocationId;

        return route('search', array_filter([
            'locale' => $locale,
            'where' => $pickup['name'] ?? null,
            'city' => $pickup['city'] ?? null,
            'country' => $pickup['country'] ?? null,
            'latitude' => $pickup['latitude'] ?? null,
            'longitude' => $pickup['longitude'] ?? null,
            'unified_location_id' => $pickupUnifiedLocationId,
            'provider' => $provider,
            'provider_pickup_id' => $provider === 'internal' ? null : ($pickup['provider_location_id'] ?? null),
            'date_from' => $search['pickup_date'] ?? null,
            'date_to' => $search['dropoff_date'] ?? null,
            'start_time' => $search['pickup_time'] ?? null,
            'end_time' => $search['dropoff_time'] ?? null,
            'age' => $search['driver_age'] ?? null,
            'currency' => $search['currency'] ?? null,
            'dropoff_where' => $dropoff['name'] ?? null,
            'dropoff_unified_location_id' => $dropoffUnifiedLocationId,
            'dropoff_location_id' => $provider === 'internal' ? null : ($dropoff['provider_location_id'] ?? null),
            'dropoff_latitude' => $dropoff['latitude'] ?? null,
            'dropoff_longitude' => $dropoff['longitude'] ?? null,
        ], static fn ($value) => $value !== null && $value !== ''));
    }

    private function resolveSearchAgainUnifiedLocationId(array $search, array $location, string $searchKey, string $provider): ?int
    {
        foreach ([
            $search[$searchKey] ?? null,
            $location['unified_location_id'] ?? null,
            $location['id'] ?? null,
        ] as $candidate) {
            $normalized = $this->positiveIntegerOrNull($candidate);

            if ($normalized !== null) {
                return $normalized;
            }
        }

        $providerPickupId = trim((string) ($location['provider_location_id'] ?? ''));

        if ($providerPickupId === '' || $provider === 'internal' || $provider === 'mixed') {
            return null;
        }

        $searchPayload = [
            'provider' => $provider,
            'provider_pickup_id' => $providerPickupId,
            'where' => $location['name'] ?? null,
            'city' => $location['city'] ?? null,
            'country' => $location['country'] ?? null,
            'latitude' => $location['latitude'] ?? null,
            'longitude' => $location['longitude'] ?? null,
        ];

        $resolvedLocation = $this->locationSearchService->resolveSearchLocation($searchPayload);
        $resolvedUnifiedLocationId = $this->positiveIntegerOrNull($resolvedLocation['unified_location_id'] ?? null);

        if ($resolvedUnifiedLocationId !== null) {
            return $resolvedUnifiedLocationId;
        }

        return $this->resolveFallbackSearchAgainUnifiedLocationId($searchPayload, $location);
    }

    private function resolveFallbackSearchAgainUnifiedLocationId(array $searchPayload, array $location): ?int
    {
        foreach ($this->searchAgainLocationTerms($location) as $term) {
            foreach ($this->locationSearchService->searchLocations($term, 10) as $candidate) {
                if (! is_array($candidate) || ! $this->searchAgainLocationMatches($candidate, $location)) {
                    continue;
                }

                return $this->positiveIntegerOrNull($candidate['unified_location_id'] ?? null);
            }
        }

        foreach ($this->locationSearchService->nearbyLocations($searchPayload, null, 1) as $candidate) {
            if (! is_array($candidate) || ! $this->searchAgainLocationMatches($candidate, $location)) {
                continue;
            }

            return $this->positiveIntegerOrNull($candidate['unified_location_id'] ?? null);
        }

        return null;
    }

    private function searchAgainLocationTerms(array $location): array
    {
        return collect([
            $location['name'] ?? null,
            trim((string) ($location['city'] ?? '').' '.(string) ($location['country'] ?? '')),
            $location['city'] ?? null,
        ])
            ->filter(fn ($term): bool => is_string($term) && strlen(trim($term)) >= 2)
            ->map(fn ($term): string => trim((string) $term))
            ->unique()
            ->values()
            ->all();
    }

    private function searchAgainLocationMatches(array $candidate, array $location): bool
    {
        if ($this->positiveIntegerOrNull($candidate['unified_location_id'] ?? null) === null) {
            return false;
        }

        $requestedCountryCode = strtoupper(trim((string) ($location['country_code'] ?? '')));
        $candidateCountryCode = strtoupper(trim((string) ($candidate['country_code'] ?? '')));

        if ($requestedCountryCode !== '' && $candidateCountryCode !== '' && $requestedCountryCode !== $candidateCountryCode) {
            return false;
        }

        $requestedCountry = strtolower(trim((string) ($location['country'] ?? '')));
        $candidateCountry = strtolower(trim((string) ($candidate['country'] ?? '')));

        if ($requestedCountryCode === '' && $candidateCountryCode === '' && $requestedCountry !== '' && $candidateCountry !== '' && $requestedCountry !== $candidateCountry) {
            return false;
        }

        if ($this->hasUsableCoordinatePair($location['latitude'] ?? null, $location['longitude'] ?? null)
            && $this->hasUsableCoordinatePair($candidate['latitude'] ?? null, $candidate['longitude'] ?? null)
        ) {
            $distanceKm = $this->distanceKm(
                (float) $location['latitude'],
                (float) $location['longitude'],
                (float) $candidate['latitude'],
                (float) $candidate['longitude'],
            );

            if ($distanceKm > 75.0) {
                return false;
            }
        }

        return true;
    }

    private function positiveIntegerOrNull(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value) && preg_match('/^[1-9]\d*$/', trim($value)) === 1) {
            return (int) $value;
        }

        return null;
    }

    private function hasUsableCoordinatePair(mixed $latitude, mixed $longitude): bool
    {
        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return false;
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;

        if ($latitude < -90.0 || $latitude > 90.0 || $longitude < -180.0 || $longitude > 180.0) {
            return false;
        }

        return ! (abs($latitude) < 0.000001 && abs($longitude) < 0.000001);
    }

    private function distanceKm(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $earthRadiusKm = 6371;
        $latDelta = deg2rad($toLatitude - $fromLatitude);
        $lonDelta = deg2rad($toLongitude - $fromLongitude);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLatitude)) * cos(deg2rad($toLatitude)) * sin($lonDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
