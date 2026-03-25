<?php

namespace App\Observers;

use App\Models\Blog;
use App\Models\SeoRedirect;
use Illuminate\Support\Facades\Artisan;

class BlogObserver
{
    /**
     * When a blog is deleted, create 410 entries for all its URLs
     * and regenerate the sitemap.
     */
    public function deleted(Blog $blog): void
    {
        $countries = $this->getBlogCountries($blog);
        $translations = $blog->translations;

        foreach ($translations as $translation) {
            if (empty($translation->slug)) {
                continue;
            }
            foreach ($countries as $country) {
                $path = "/{$translation->locale}/{$country}/blog/{$translation->slug}";
                SeoRedirect::addGone($path, "Blog #{$blog->id} deleted");
            }
        }

        $this->regenerateSitemap();
    }

    /**
     * When a blog is created or updated, regenerate the sitemap.
     */
    public function saved(Blog $blog): void
    {
        $this->regenerateSitemap();
    }

    private function getBlogCountries(Blog $blog): array
    {
        $countries = $blog->countries;
        if (is_array($countries) && ! empty($countries)) {
            return array_map('strtolower', $countries);
        }

        $canonical = $blog->canonical_country;
        if ($canonical) {
            return [strtolower($canonical)];
        }

        return ['us'];
    }

    private function regenerateSitemap(): void
    {
        try {
            Artisan::call('sitemap:generate');
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
