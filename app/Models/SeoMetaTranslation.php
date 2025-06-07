<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoMetaTranslation extends Model
{
    use HasFactory;

    protected $table = 'seo_meta_translations';

    protected $fillable = [
        'seo_meta_id',
        'locale',
        'seo_title',
        'meta_description',
        'keywords',
    ];

    /**
     * Get the parent SEO meta record.
     */
    public function seoMeta()
    {
        return $this->belongsTo(SeoMeta::class);
    }
}
