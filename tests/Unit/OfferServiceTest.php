<?php

namespace Tests\Unit;

use App\Models\Offer;
use App\Models\OfferEffect;
use App\Services\OfferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfferServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_applies_one_monetary_offer_and_stacks_perk_offers(): void
    {
        $discountLow = Offer::create([
            'name' => 'Spring Sale',
            'slug' => 'spring-sale',
            'title' => 'Spring Sale',
            'description' => 'Save on every booking',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
            'priority' => 10,
            'placements' => ['homepage', 'search'],
        ]);

        OfferEffect::create([
            'offer_id' => $discountLow->id,
            'type' => 'price_discount_percentage',
            'config' => ['percentage' => 5],
            'sort_order' => 1,
        ]);

        $discountHigh = Offer::create([
            'name' => 'Summer Discount',
            'slug' => 'summer-discount',
            'title' => 'Summer Discount',
            'description' => 'Save 10%',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
            'priority' => 100,
            'placements' => ['homepage', 'search', 'checkout'],
        ]);

        OfferEffect::create([
            'offer_id' => $discountHigh->id,
            'type' => 'price_discount_percentage',
            'config' => ['percentage' => 10],
            'sort_order' => 1,
        ]);

        $freeEsim = Offer::create([
            'name' => 'Free E-Sim',
            'slug' => 'free-e-sim',
            'title' => 'Free E-Sim',
            'description' => 'Free eSIM with every booking',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
            'priority' => 50,
            'placements' => ['homepage', 'search', 'checkout', 'success'],
        ]);

        OfferEffect::create([
            'offer_id' => $freeEsim->id,
            'type' => 'free_esim',
            'config' => ['included' => true],
            'sort_order' => 1,
        ]);

        $resolved = app(OfferService::class)->resolveAppliedOffers([
            'placement' => 'search',
        ]);

        $this->assertSame($discountHigh->id, $resolved['monetary_offer']['id']);
        $this->assertSame(0.10, $resolved['price_discount_rate']);
        $this->assertTrue($resolved['free_esim_included']);
        $this->assertCount(2, $resolved['applied_offers']);
        $this->assertSame(
            ['free_esim', 'price_discount_percentage'],
            collect($resolved['applied_offers'])->pluck('effect_type')->sort()->values()->all()
        );

        $pricing = app(OfferService::class)->computePricing(172.50, $resolved);

        $this->assertSame(189.75, $pricing['display_total']);
        $this->assertSame(172.50, $pricing['final_total']);
        $this->assertSame(17.25, $pricing['discount_amount']);
    }

    #[Test]
    public function it_filters_display_offers_by_placement(): void
    {
        $homepageOffer = Offer::create([
            'name' => 'Homepage E-Sim',
            'slug' => 'homepage-e-sim',
            'title' => 'Homepage E-Sim',
            'description' => 'Homepage only',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
            'priority' => 1,
            'placements' => ['homepage'],
        ]);

        OfferEffect::create([
            'offer_id' => $homepageOffer->id,
            'type' => 'free_esim',
            'config' => ['included' => true],
            'sort_order' => 1,
        ]);

        $checkoutOffer = Offer::create([
            'name' => 'Checkout Discount',
            'slug' => 'checkout-discount',
            'title' => 'Checkout Discount',
            'description' => 'Checkout only',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
            'priority' => 1,
            'placements' => ['checkout'],
        ]);

        OfferEffect::create([
            'offer_id' => $checkoutOffer->id,
            'type' => 'price_discount_percentage',
            'config' => ['percentage' => 10],
            'sort_order' => 1,
        ]);

        $offers = app(OfferService::class)->getDisplayOffers('homepage');

        $this->assertCount(1, $offers);
        $this->assertSame($homepageOffer->id, $offers->first()->id);
    }
}
