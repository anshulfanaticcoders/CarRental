<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoMeta extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'seo_metas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'url_slug',
        'seoable_type',
        'seoable_id',
        'route_name',
        'route_params',
        'route_params_hash',
        'seo_title',
        'meta_description',
        'keywords',
        'canonical_url',
        'seo_image_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // Add casts if needed, e.g., for keywords if you store them as JSON
        // 'keywords' => 'array',
        'route_params' => 'array',
    ];

    public function seoable()
    {
        return $this->morphTo();
    }

    /**
     * Get all translations for the SEO meta.
     */
    public function translations()
    {
        return $this->hasMany(SeoMetaTranslation::class);
    }

    /**
     * Get a specific translation.
     *
     * @param string $locale
     * @return SeoMetaTranslation|null
     */
    public function getTranslation(string $locale)
    {
        return $this->translations()->where('locale', $locale)->first();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($seoMeta) {
            // Delete all related translations when a SeoMeta record is deleted
            $seoMeta->translations()->delete();
        });
    }
}
