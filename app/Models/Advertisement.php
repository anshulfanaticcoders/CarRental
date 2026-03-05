<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    protected $fillable = [
        'offer_type',
        'title',
        'description',
        'button_text',
        'button_link',
        'image_path',
        'start_date',
        'end_date',
        'is_active',
        'is_external',
        'is_promo',
        'discount_percentage',
        'promo_markup_rate',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'is_external' => 'boolean',
        'is_promo' => 'boolean',
        'discount_percentage' => 'decimal:2',
        'promo_markup_rate' => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::saving(function (Advertisement $ad) {
            if ($ad->is_promo && $ad->discount_percentage > 0) {
                $ad->promo_markup_rate = $ad->discount_percentage / 100;
            } else {
                $ad->promo_markup_rate = 0;
            }
        });
    }

    /**
     * Scope a query to only include active advertisements.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Scope for the highest-discount active promo ad.
     */
    public function scopeActivePromo($query)
    {
        return $query->active()
            ->where('is_promo', true)
            ->where('discount_percentage', '>', 0)
            ->orderByDesc('discount_percentage');
    }
}
