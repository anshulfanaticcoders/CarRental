<?php

namespace App\Helpers;

use App\Models\BlogTranslation;
use App\Models\Page;
use App\Models\PageTranslation;
use Illuminate\Support\Facades\Cache;

class HreflangHelper
{
    /**
     * @param  array  $routeParameters  Route parameters excluding 'locale'
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
            return self::buildCustomPageAlternate('contact-us', $targetLocale);
        }

        if ($routeName === 'affiliate.register' || $routeName === 'affiliate.business.register') {
            return route('affiliate.register', ['locale' => $targetLocale]);
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

            $cacheKey = 'hreflang:page:'.$currentLocale.':'.$slug;
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

        if ($routeName === 'pages.custom') {
            $customSlug = (string) ($params['customSlug'] ?? '');

            return self::buildCustomPageAlternate($customSlug, $targetLocale);
        }

        if ($routeName === 'blog.show') {
            $country = strtolower((string) ($params['country'] ?? 'us'));
            $blogParam = $params['slug'] ?? ($params['blog'] ?? '');
            $slug = is_object($blogParam) && isset($blogParam->slug)
                ? (string) $blogParam->slug
                : (string) $blogParam;
            if ($slug === '') {
                return null;
            }

            $cacheKey = 'hreflang:blog:'.$currentLocale.':'.$slug;
            $data = Cache::remember($cacheKey, now()->addHours(6), function () use ($currentLocale, $slug) {
                $translation = BlogTranslation::query()
                    ->where('locale', $currentLocale)
                    ->where('slug', $slug)
                    ->with('blog:id,canonical_country,countries')
                    ->first();

                if (! $translation || ! $translation->blog) {
                    return null;
                }

                return [
                    'blog_id' => $translation->blog_id,
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

            // Use the CURRENT URL's country for alternates — not the blog's
            // stored canonical_country. Country represents a target market
            // cluster; each cluster has its own hreflang group. A visitor on
            // /nl/be/ must see Belgian siblings (/fr/be/, /en/be/, ...), not
            // US siblings that contradict the self-canonical on the page.
            return url("/{$targetLocale}/{$country}/blog/{$alt->slug}");
        }

        return null;
    }

    private static function buildCustomPageAlternate(string $customSlug, string $targetLocale): ?string
    {
        if ($customSlug === '') {
            return null;
        }

        $page = Page::query()
            ->where('custom_slug', $customSlug)
            ->where('status', 'published')
            ->first();

        if (! $page) {
            return null;
        }

        $translation = PageTranslation::query()
            ->where('page_id', $page->id)
            ->where('locale', $targetLocale)
            ->first();

        if (! $translation || ! $translation->slug) {
            return null;
        }

        return route('pages.show', ['locale' => $targetLocale, 'slug' => $translation->slug]);
    }
}
