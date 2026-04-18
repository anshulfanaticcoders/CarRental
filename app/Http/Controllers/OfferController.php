<?php

namespace App\Http\Controllers;

use App\Services\OfferService;

class OfferController extends Controller
{
    public function index(OfferService $offerService)
    {
        return response()->json(
            $offerService->getDisplayOffers('homepage')
                ->map(fn ($offer) => $offerService->summarizeHomepageOffer($offer))
                ->values()
        );
    }

    public function activePriceOffer(OfferService $offerService)
    {
        $resolved = $offerService->resolveAppliedOffers(['placement' => 'search']);
        $monetary = $resolved['monetary_offer'];

        if (!$monetary) {
            return response()->json(null);
        }

        return response()->json([
            'id' => $monetary['id'],
            'title' => $monetary['title'],
            'description' => $monetary['description'],
            'discount_percentage' => (float) ($monetary['effect_payload']['percentage'] ?? 0),
            'offer_rate' => (float) $resolved['price_discount_rate'],
        ]);
    }
}
