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
        $offerPayload = $offerId !== '' ? $this->offers->get($offerId) : null;

        if (! $offerPayload) {
            return redirect('/');
        }

        $landingUrl = $this->attribution->landingUrl($offerPayload);
        if ($clickid === '') {
            return redirect()->away($landingUrl);
        }

        return redirect()
            ->away($landingUrl)
            ->withCookie($this->attribution->store($request, $offerPayload, $clickid));
    }
}
