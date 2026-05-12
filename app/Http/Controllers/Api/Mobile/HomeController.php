<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\PopularPlace;
use App\Services\OfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(private OfferService $offerService) {}

    public function popularPlaces(Request $request): JsonResponse
    {
        $host = rtrim($request->getSchemeAndHttpHost(), '/');
        $absolutize = function (?string $path) use ($host): ?string {
            if (! $path) return null;
            if (preg_match('#^https?://#i', $path)) return $path;
            return $host.'/'.ltrim($path, '/');
        };

        $places = PopularPlace::query()
            ->orderBy('place_name')
            ->get()
            ->map(function (PopularPlace $p) use ($absolutize) {
                return [
                    'id' => $p->id,
                    'place_name' => $p->place_name,
                    'city' => $p->city,
                    'state' => $p->state,
                    'country' => $p->country,
                    'unified_location_id' => $p->unified_location_id !== null
                        ? (int) $p->unified_location_id
                        : null,
                    'latitude' => $p->latitude !== null ? (float) $p->latitude : null,
                    'longitude' => $p->longitude !== null ? (float) $p->longitude : null,
                    'image' => $absolutize($p->image),
                ];
            });

        return response()->json(['places' => $places->values()]);
    }

    public function offers(Request $request): JsonResponse
    {
        $host = rtrim($request->getSchemeAndHttpHost(), '/');
        $absolutize = function (?string $path) use ($host): ?string {
            if (! $path) return null;
            if (preg_match('#^https?://#i', $path)) return $path;
            return $host.'/'.ltrim($path, '/');
        };

        $offers = $this->offerService
            ->getDisplayOffers('homepage')
            ->map(function ($offer) use ($absolutize) {
                $summary = $this->offerService->summarizeHomepageOffer($offer);
                $summary['image_path'] = $absolutize($summary['image_path'] ?? null);
                return $summary;
            })
            ->values()
            ->all();

        return response()->json(['offers' => $offers]);
    }
}
