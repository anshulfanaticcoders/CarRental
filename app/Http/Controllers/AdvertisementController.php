<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Services\PromoService;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    /**
     * Fetch the active advertisement for the homepage.
     */
    public function index()
    {
        $advertisements = Advertisement::active()->latest()->get();

        return response()->json($advertisements);
    }

    /**
     * Get the currently active promo (if any).
     */
    public function activePromo()
    {
        $promo = app(PromoService::class)->getActivePromo();

        if (!$promo) {
            return response()->json(null);
        }

        return response()->json([
            'id' => $promo->id,
            'title' => $promo->title,
            'description' => $promo->description,
            'discount_percentage' => (float) $promo->discount_percentage,
            'promo_markup_rate' => (float) $promo->promo_markup_rate,
        ]);
    }
}
