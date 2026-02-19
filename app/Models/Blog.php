<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'image',
        'countries',
        'canonical_country',
        'is_published'
    ];

    protected $appends = ['title', 'content', 'translated_slug']; // Force append accessors

    // Removed automatic slug generation from here, will be handled in controller
    // protected static function boot()
    // {
    //     parent::boot();
    // }

    public function translations()
    {
        return $this->hasMany(BlogTranslation::class);
    }

    public function tags()
    {
        return $this->belongsToMany(BlogTag::class, 'blog_tag_blog', 'blog_id', 'blog_tag_id');
    }

    public function getTranslation(string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $this->translations()->where('locale', $locale)->first();
    }

    public function getTitleAttribute()
    {
        return $this->getTranslation(app()->getLocale())?->title;
    }

    public function getContentAttribute()
    {
        return $this->getTranslation(app()->getLocale())?->content;
    }

    public function getTranslatedSlugAttribute()
    {
        return $this->getTranslation(app()->getLocale())?->slug;
    }


    // Helper to get title for a specific locale, useful for admin forms
    public function getTitleForLocale(string $locale)
    {
        return $this->translations()->where('locale', $locale)->value('title');
    }

    // Helper to get content for a specific locale, useful for admin forms
    public function getContentForLocale(string $locale)
    {
        return $this->translations()->where('locale', $locale)->value('content');
    }

    protected $casts = [
        'countries' => 'array',
    ];
}
