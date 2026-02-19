<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'template',
        'custom_slug',
        'status',
        'sort_order',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(PageTranslation::class);
    }

    public function meta(): HasMany
    {
        return $this->hasMany(PageMeta::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order');
    }

    /**
     * Get all meta as key=>value for a locale, falling back to 'en' for missing keys.
     */
    public function getMetaForLocale(string $locale): array
    {
        $metaItems = $this->meta()->get();

        // Group by meta_key
        $grouped = $metaItems->groupBy('meta_key');

        $result = [];
        foreach ($grouped as $key => $items) {
            $localeItem = $items->firstWhere('locale', $locale);
            if ($localeItem) {
                $result[$key] = $localeItem->meta_value;
            } else {
                $fallback = $items->firstWhere('locale', 'en');
                if ($fallback) {
                    $result[$key] = $fallback->meta_value;
                }
            }
        }

        return $result;
    }

    public function getTitleAttribute()
    {
        $locale = app()->getLocale();
        $translation = $this->translations()->where('locale', $locale)->first();
        return $translation ? $translation->title : $this->translations()->first()->title;
    }

    public function getContentAttribute()
    {
        $locale = app()->getLocale();
        $translation = $this->translations()->where('locale', $locale)->first();
        return $translation ? $translation->content : $this->translations()->first()->content;
    }

    public function getSlugAttribute()
    {
        $locale = app()->getLocale();
        $translation = $this->translations()->where('locale', $locale)->first();
        return $translation ? $translation->slug : $this->translations()->first()->slug;
    }
}
