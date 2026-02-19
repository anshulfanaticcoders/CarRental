<?php

namespace App\Helpers;

use App\Models\BlogTranslation;
use App\Models\PageTranslation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class HreflangHelper
{
    /**
     * @param array $routeParameters Route parameters excluding 'locale'
     * @return array<string, string> locale => url
     */
    public static function getAlternateUrls(string $routeName, array $routeParameters, string $currentLocale): array
    {
        $locales = config('app.available_locales', ['en']);
        $urls = [];

        foreach ($locales as $locale) {
            try {
                $url = self::buildAlternateUrl($routeName, $routeParameters, $currentLocale, (string) $locale);
                if ($url) {
                    $urls[$locale] = $url;
                }
            } catch (\Throwable $e) {
                // Omit locale on any generation failure.
            }
        }

        return $urls;
    }

    private static function buildAlternateUrl(string $routeName, array $params, string $currentLocale, string $targetLocale): ?string
    {
        if ($routeName === 'welcome') {
            return route('welcome', ['locale' => $targetLocale]);
        }

        if ($routeName === 'faq.show') {
            return route('faq.show', ['locale' => $targetLocale]);
        }

        if ($routeName === 'contact-us') {
            return route('contact-us', ['locale' => $targetLocale]);
        }

        if ($routeName === 'affiliate.business.register') {
            return route('affiliate.business.register', ['locale' => $targetLocale]);
        }

        if ($routeName === 'blog') {
            $country = strtolower((string) ($params['country'] ?? 'us'));
            return route('blog', ['locale' => $targetLocale, 'country' => $country]);
        }

        if ($routeName === 'pages.show') {
            $slug = (string) ($params['slug'] ?? '');
            if ($slug === '') {
                return null;
            }

            $cacheKey = 'hreflang:page:' . $currentLocale . ':' . $slug;
            $data = Cache::remember($cacheKey, now()->addHours(6), function () use ($currentLocale, $slug) {
                $translation = PageTranslation::query()
                    ->where('locale', $currentLocale)
                    ->where('slug', $slug)
                    ->first();

                if (! $translation) {
                    return null;
                }

                return [
                    'page_id' => $translation->page_id,
                ];
            });

            if (! $data) {
                return null;
            }

            $alt = PageTranslation::query()
                ->where('page_id', $data['page_id'])
                ->where('locale', $targetLocale)
                ->first();

            if (! $alt || ! $alt->slug) {
                return null;
            }

            return route('pages.show', ['locale' => $targetLocale, 'slug' => $alt->slug]);
        }

        if ($routeName === 'blog.show') {
            $country = strtolower((string) ($params['country'] ?? 'us'));
            $slug = (string) ($params['slug'] ?? ($params['blog'] ?? ''));
            if ($slug === '') {
                return null;
            }

            $cacheKey = 'hreflang:blog:' . $currentLocale . ':' . $slug;
            $data = Cache::remember($cacheKey, now()->addHours(6), function () use ($currentLocale, $slug) {
                $translation = BlogTranslation::query()
                    ->where('locale', $currentLocale)
                    ->where('slug', $slug)
                    ->with('blog:id,canonical_country,countries')
                    ->first();

                if (! $translation || ! $translation->blog) {
                    return null;
                }

                $blog = $translation->blog;
                $canonicalCountry = $blog->canonical_country;
                if (!is_string($canonicalCountry) || trim($canonicalCountry) === '') {
                    $canonicalCountry = is_array($blog->countries) && !empty($blog->countries) ? (string) $blog->countries[0] : 'us';
                }
                $canonicalCountry = strtolower((string) $canonicalCountry);

                return [
                    'blog_id' => $translation->blog_id,
                    'canonical_country' => $canonicalCountry,
                ];
            });

            if (! $data) {
                return null;
            }

            $alt = BlogTranslation::query()
                ->where('blog_id', $data['blog_id'])
                ->where('locale', $targetLocale)
                ->first();

            if (! $alt || ! $alt->slug) {
                return null;
            }

            $canonicalCountry = $data['canonical_country'] ?? $country;

            return url("/{$targetLocale}/{$canonicalCountry}/blog/{$alt->slug}");
        }

        return null;
    }
}
