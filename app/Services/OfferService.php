<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingOffer;
use App\Models\Offer;
use App\Models\OfferEffect;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class OfferService
{
    private const CACHE_KEY = 'active_offers_v1';
    private const CACHE_TTL_SECONDS = 60;

    public function getActiveOffers(?string $placement = null): Collection
    {
        /** @var Collection<int, Offer> $offers */
        $offers = Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            return Offer::query()
                ->active()
                ->with('effects')
                ->orderByDesc('priority')
                ->orderBy('id')
                ->get();
        });

        if (!$placement) {
            return $offers;
        }

        return $offers
            ->filter(function (Offer $offer) use ($placement): bool {
                $placements = $offer->placements ?? [];

                return in_array($placement, $placements, true);
            })
            ->values();
    }

    public function getDisplayOffers(string $placement): Collection
    {
        return $this->getActiveOffers($placement);
    }

    public function resolveAppliedOffers(array $context = []): array
    {
        $placement = $context['placement'] ?? null;
        $offers = $this->getActiveOffers($placement);

        $monetaryOffer = null;
        $perkOffers = [];
        $appliedOffers = [];
        $perkTypes = [];

        foreach ($offers as $offer) {
            foreach ($offer->effects as $effect) {
                if ($effect->type === 'price_discount_percentage') {
                    if ($monetaryOffer === null) {
                        $monetaryOffer = $this->serializeAppliedOffer($offer, $effect);
                    }

                    continue;
                }

                if (isset($perkTypes[$effect->type])) {
                    continue;
                }

                $serialized = $this->serializeAppliedOffer($offer, $effect);
                $perkOffers[] = $serialized;
                $perkTypes[$effect->type] = true;
            }
        }

        if ($monetaryOffer !== null) {
            $appliedOffers[] = $monetaryOffer;
        }

        foreach ($perkOffers as $perkOffer) {
            $appliedOffers[] = $perkOffer;
        }

        return [
            'monetary_offer' => $monetaryOffer,
            'perk_offers' => $perkOffers,
            'applied_offers' => $appliedOffers,
            'price_discount_rate' => $monetaryOffer
                ? round(((float) ($monetaryOffer['effect_payload']['percentage'] ?? 0)) / 100, 4)
                : 0.0,
            'free_esim_included' => collect($perkOffers)->contains(
                fn (array $offer) => $offer['effect_type'] === 'free_esim'
            ),
        ];
    }

    public function computePricing(float $finalAmount, array $resolvedOffers): array
    {
        $discountRate = (float) ($resolvedOffers['price_discount_rate'] ?? 0);

        if ($discountRate <= 0) {
            return [
                'display_total' => round($finalAmount, 2),
                'final_total' => round($finalAmount, 2),
                'discount_amount' => 0.0,
            ];
        }

        $displayTotal = round($finalAmount * (1 + $discountRate), 2);

        return [
            'display_total' => $displayTotal,
            'final_total' => round($finalAmount, 2),
            'discount_amount' => round($displayTotal - $finalAmount, 2),
        ];
    }

    public function getOfferFingerprint(?string $placement = null): string
    {
        $resolved = $this->resolveAppliedOffers(['placement' => $placement]);

        return sha1(json_encode([
            'placement' => $placement,
            'applied_offers' => collect($resolved['applied_offers'] ?? [])
                ->map(fn (array $offer) => [
                    'id' => $offer['id'] ?? null,
                    'effect_type' => $offer['effect_type'] ?? null,
                    'effect_payload' => $offer['effect_payload'] ?? [],
                ])
                ->values()
                ->all(),
        ]));
    }

    public function syncBookingOffers(Booking $booking, array $appliedOffers): void
    {
        if (empty($appliedOffers) || $booking->offers()->exists()) {
            return;
        }

        foreach ($appliedOffers as $offer) {
            if (!is_array($offer) || empty($offer['effect_type'])) {
                continue;
            }

            BookingOffer::create([
                'booking_id' => $booking->id,
                'offer_id' => $offer['id'] ?? null,
                'name' => $offer['name'] ?? $offer['title'] ?? 'Offer',
                'slug' => $offer['slug'] ?? null,
                'title' => $offer['title'] ?? $offer['name'] ?? 'Offer',
                'effect_type' => $offer['effect_type'],
                'effect_payload' => $offer['effect_payload'] ?? [],
                'discount_amount' => round((float) ($offer['discount_amount'] ?? 0), 2),
                'metadata' => [
                    'description' => $offer['description'] ?? null,
                ],
            ]);
        }
    }

    public function invalidateCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function summarizeHomepageOffer(Offer $offer): array
    {
        $primaryEffect = $offer->effects->first();
        $discountPercentage = null;
        $isPromo = false;

        if ($primaryEffect && $primaryEffect->type === 'price_discount_percentage') {
            $discountPercentage = (float) ($primaryEffect->config['percentage'] ?? 0);
            $isPromo = $discountPercentage > 0;
        }

        return [
            'id' => $offer->id,
            'offer_type' => $primaryEffect?->type === 'free_esim' ? 'Perk' : 'Offer',
            'title' => $offer->title ?: $offer->name,
            'description' => $offer->description,
            'button_text' => $offer->button_text,
            'button_link' => $offer->button_link,
            'image_path' => $offer->image_path,
            'is_external' => (bool) $offer->is_external,
            'is_promo' => $isPromo,
            'discount_percentage' => $discountPercentage,
        ];
    }

    private function serializeAppliedOffer(Offer $offer, OfferEffect $effect): array
    {
        $config = $effect->config ?? [];
        $discountAmount = 0.0;

        if ($effect->type === 'price_discount_percentage') {
            $discountAmount = round((float) ($config['discount_amount'] ?? 0), 2);
        }

        return [
            'id' => $offer->id,
            'name' => $offer->name,
            'slug' => $offer->slug,
            'title' => $offer->title ?: $offer->name,
            'description' => $offer->description,
            'effect_type' => $effect->type,
            'effect_payload' => $config,
            'discount_amount' => $discountAmount,
        ];
    }
}
