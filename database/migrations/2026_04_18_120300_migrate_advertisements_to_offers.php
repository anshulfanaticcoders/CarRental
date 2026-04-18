<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('advertisements') || !Schema::hasTable('offers')) {
            return;
        }

        $ads = DB::table('advertisements')->get();

        foreach ($ads as $ad) {
            $slugBase = Str::slug($ad->title ?: $ad->offer_type ?: 'offer');
            $slug = $slugBase !== '' ? $slugBase : 'offer-'.$ad->id;

            while (DB::table('offers')->where('slug', $slug)->exists()) {
                $slug = $slugBase.'-'.$ad->id;
            }

            $offerId = DB::table('offers')->insertGetId([
                'name' => $ad->title ?: $ad->offer_type ?: 'Offer '.$ad->id,
                'slug' => $slug,
                'title' => $ad->title,
                'description' => $ad->description,
                'image_path' => $ad->image_path,
                'button_text' => $ad->button_text,
                'button_link' => $ad->button_link,
                'start_date' => $ad->start_date,
                'end_date' => $ad->end_date,
                'is_active' => (bool) $ad->is_active,
                'is_external' => (bool) $ad->is_external,
                'priority' => (bool) $ad->is_promo ? 100 : 10,
                'placements' => json_encode((bool) $ad->is_promo
                    ? ['homepage', 'search', 'checkout', 'success']
                    : ['homepage']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ((bool) $ad->is_promo && (float) $ad->discount_percentage > 0) {
                DB::table('offer_effects')->insert([
                    'offer_id' => $offerId,
                    'type' => 'price_discount_percentage',
                    'config' => json_encode(['percentage' => (float) $ad->discount_percentage]),
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('advertisements') || !Schema::hasTable('offers')) {
            return;
        }

        $slugs = DB::table('advertisements')
            ->get()
            ->map(function ($ad) {
                $slug = Str::slug($ad->title ?: $ad->offer_type ?: 'offer');
                return $slug !== '' ? $slug : 'offer-'.$ad->id;
            })
            ->all();

        $offerIds = DB::table('offers')->whereIn('slug', $slugs)->pluck('id');
        DB::table('offer_effects')->whereIn('offer_id', $offerIds)->delete();
        DB::table('offers')->whereIn('id', $offerIds)->delete();
    }
};
