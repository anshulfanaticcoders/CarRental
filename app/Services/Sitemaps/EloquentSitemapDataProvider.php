<?php

namespace App\Services\Sitemaps;

use App\Models\Blog;
use App\Models\PageTranslation;

class EloquentSitemapDataProvider implements SitemapDataProvider
{
    public function getLocales(): array
    {
        return config('app.available_locales', ['en']);
    }

    public function getPageTranslations(): array
    {
        return PageTranslation::query()
            ->select([
                'page_translations.page_id',
                'page_translations.locale',
                'page_translations.slug',
                'page_translations.updated_at',
                'pages.updated_at as page_updated_at',
            ])
            ->join('pages', 'pages.id', '=', 'page_translations.page_id')
            ->whereNotNull('page_translations.slug')
            ->get()
            ->map(static function ($row) {
                return [
                    'page_id' => (int) $row->page_id,
                    'locale' => (string) $row->locale,
                    'slug' => (string) $row->slug,
                    'updated_at' => $row->updated_at,
                    'page_updated_at' => $row->page_updated_at,
                ];
            })
            ->all();
    }

    public function getPublishedBlogs(): array
    {
        return Blog::query()
            ->select(['id', 'image', 'countries', 'updated_at'])
            ->with(['translations:id,blog_id,locale,slug,title,updated_at'])
            ->where('is_published', true)
            ->get()
            ->map(static function (Blog $blog) {
                return [
                    'id' => (int) $blog->id,
                    'image' => $blog->image,
                    'countries' => $blog->countries,
                    'updated_at' => $blog->updated_at,
                    'translations' => $blog->translations->map(static function ($translation) {
                        return [
                            'locale' => (string) $translation->locale,
                            'slug' => (string) $translation->slug,
                            'title' => $translation->title,
                            'updated_at' => $translation->updated_at,
                        ];
                    })->all(),
                ];
            })
            ->all();
    }
}
