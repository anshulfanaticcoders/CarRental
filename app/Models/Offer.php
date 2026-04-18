<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'title',
        'description',
        'image_path',
        'button_text',
        'button_link',
        'start_date',
        'end_date',
        'is_active',
        'is_external',
        'priority',
        'placements',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'is_external' => 'boolean',
        'priority' => 'integer',
        'placements' => 'array',
    ];

    public function effects(): HasMany
    {
        return $this->hasMany(OfferEffect::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeForPlacement($query, ?string $placement)
    {
        if (!$placement) {
            return $query;
        }

        return $query->whereJsonContains('placements', $placement);
    }
}
