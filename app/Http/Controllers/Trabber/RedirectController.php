<?php

namespace App\Http\Controllers\Trabber;

use App\Http\Controllers\Controller;
use App\Services\Trabber\TrabberAttributionService;
use App\Services\Trabber\TrabberOfferStoreService;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function __construct(
        private readonly TrabberOfferStoreService $offers,
        private readonly TrabberAttributionService $attribution
    ) {}

    public function __invoke(Request $request)
    {
        $offerId = trim((string) $request->query('offer_id', ''));
        $clickParameter = (string) config('trabber.click_parameter', 'clickid');
        $clickid = trim((string) $request->query($clickParameter, ''));

        // Meta-search partners serve cached SERPs, so clicks on offers older
        // than the 60-min cache are NORMAL traffic. This used to dump them on
        // the bare homepage AND discard the clickid before attribution ran —
        // losing the booking and Trabber's commission credit in one move.
        // Now: attribution is stored regardless, and an expired offer lands
        // on a prefilled search instead of the homepage.
        $offerPayload = $offerId !== '' ? $this->offers->get($offerId) : null;
        $landingUrl = $this->attribution->landingUrl($offerPayload ?? []);

        if ($clickid === '') {
            return redirect()->away($landingUrl);
        }

        try {
            $cookie = $this->attribution->store($request, $offerPayload ?? [], $clickid);
        } catch (\Throwable $e) {
            // The customer must reach the car even when the click insert
            // fails — attribution is worth less than the booking.
            \Illuminate\Support\Facades\Log::warning('Trabber redirect: failed to store attribution', [
                'clickid' => $clickid,
                'error' => $e->getMessage(),
            ]);

            return redirect()->away($landingUrl);
        }

        return redirect()->away($landingUrl)->withCookie($cookie);
    }
}
