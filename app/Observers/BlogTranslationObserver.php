<?php

namespace App\Observers;

use App\Models\BlogTranslation;
use App\Models\SeoRedirect;
use Illuminate\Support\Facades\Artisan;

class BlogTranslationObserver
{
    /**
     * When a blog translation slug changes, create 301 redirects
     * from old slug to new slug for all countries.
     */
    public function updating(BlogTranslation $translation): void
    {
        $originalSlug = $translation->getOriginal('slug');
        $newSlug = $translation->slug;

        if (! $originalSlug || $originalSlug === $newSlug) {
            return;
        }

        $blog = $translation->blog;
        if (! $blog) {
            return;
        }

        $countries = $this->getBlogCountries($blog);
        $locale = $translation->locale;

        foreach ($countries as $country) {
            $oldPath = "/{$locale}/{$country}/blog/{$originalSlug}";
            $newPath = "/{$locale}/{$country}/blog/{$newSlug}";
            SeoRedirect::addRedirect($oldPath, $newPath, "Blog #{$blog->id} slug changed");
        }
    }

    public function saved(BlogTranslation $translation): void
    {
        $this->regenerateSitemap();
    }

    public function deleted(BlogTranslation $translation): void
    {
        $blog = $translation->blog;
        if (! $blog || empty($translation->slug)) {
            $this->regenerateSitemap();

            return;
        }

        foreach ($this->getBlogCountries($blog) as $country) {
            SeoRedirect::addGone(
                "/{$translation->locale}/{$country}/blog/{$translation->slug}",
                "Blog #{$blog->id} translation deleted"
            );
        }

        $this->regenerateSitemap();
    }

    private function getBlogCountries($blog): array
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
