<?php

namespace App\Services;

use App\Models\Advertisement;
use Illuminate\Support\Facades\Cache;

class PromoService
{
    private const CACHE_KEY = 'active_promo';
    private const CACHE_TTL = 60; // seconds

    /**
     * Get the highest-discount active promo advertisement (cached 60s).
     */
    public function getActivePromo(): ?Advertisement
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Advertisement::activePromo()->first();
        });
    }

    /**
     * Invalidate the active promo cache (call on ad save/update/delete).
     */
    public function invalidateCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Compute promo pricing using the stacking model.
     *
     * @param float $netAmount     The amount after platform markup (e.g. $115)
     * @param float $promoRate     The promo markup rate (e.g. 0.10 for 10%)
     * @return array{inflated_price: float, final_price: float, discount_amount: float}
     */
    public function computePromoPrice(float $netAmount, float $promoRate): array
    {
        if ($promoRate <= 0) {
            return [
                'inflated_price' => $netAmount,
                'final_price' => $netAmount,
                'discount_amount' => 0,
            ];
        }

        $inflatedPrice = round($netAmount * (1 + $promoRate), 2);
        $discountAmount = round($inflatedPrice - $netAmount, 2);

        return [
            'inflated_price' => $inflatedPrice,
            'final_price' => $netAmount,
            'discount_amount' => $discountAmount,
        ];
    }
}
