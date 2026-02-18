<?php

namespace App\Services\Sitemaps;

use Carbon\Carbon;
use DateTimeInterface;
use RuntimeException;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Sitemap as SitemapTag;
use Spatie\Sitemap\Tags\Url;

class PublicSitemapBuilder
{
    private string $baseUrl;
    private int $maxUrlsPerSitemap;

    public function __construct(
        private SitemapDataProvider $dataProvider,
        private SitemapUrlPolicy $urlPolicy,
        ?string $baseUrl = null,
        int $maxUrlsPerSitemap = 45000
    ) {
        $resolvedBaseUrl = $baseUrl ?? config('app.url');
        $resolvedBaseUrl = rtrim((string) $resolvedBaseUrl, '/');

        if ($resolvedBaseUrl === '') {
            throw new RuntimeException('APP_URL is required to build sitemap URLs.');
        }

        $this->baseUrl = $resolvedBaseUrl;
        $this->maxUrlsPerSitemap = max(1, $maxUrlsPerSitemap);
    }

    public function build(): SitemapBuildResult
    {
        $locales = $this->dataProvider->getLocales();
        if (empty($locales)) {
            throw new RuntimeException('No locales configured for sitemap generation.');
        }

        $pageTranslations = $this->groupPageTranslations($this->dataProvider->getPageTranslations());
        $blogs = $this->dataProvider->getPublishedBlogs();
        $countries = $this->extractCountries($blogs);

        $result = new SitemapBuildResult();
        $files = [];

        foreach ($locales as $locale) {
            $staticFile = $this->buildStaticSitemap((string) $locale, $locales);
            if ($staticFile) {
                $files[] = $staticFile;
            }
        }

        foreach ($locales as $locale) {
            $pagesFile = $this->buildPagesSitemap((string) $locale, $pageTranslations);
            if ($pagesFile) {
                $files[] = $pagesFile;
            }
        }

        $countryBlogData = [];
        $countryLastMods = [];
        foreach ($countries as $country) {
            $visibleBlogs = $this->filterBlogsByCountry($blogs, $country);
            $countryBlogData[$country] = $visibleBlogs;
            $countryLastMods[$country] = $this->maxBlogLastModified($visibleBlogs);
        }

        foreach ($locales as $locale) {
            $blogListingsFile = $this->buildBlogListingsSitemap((string) $locale, $locales, $countries, $countryLastMods);
            if ($blogListingsFile) {
                $files[] = $blogListingsFile;
            }
        }

        foreach ($countries as $country) {
            $visibleBlogs = $countryBlogData[$country] ?? [];
            foreach ($locales as $locale) {
                $blogFiles = $this->buildBlogPostsSitemaps((string) $locale, $country, $visibleBlogs, $locales);
                foreach ($blogFiles as $blogFile) {
                    $files[] = $blogFile;
                }
            }
        }

        $result->index = $this->buildIndex($files);
        $result->files = $files;

        return $result;
    }

    private function buildStaticSitemap(string $locale, array $locales): ?SitemapFile
    {
        $entries = [];
        $staticPaths = [
            "{$locale}",
            "{$locale}/faq",
            "{$locale}/contact-us",
            "{$locale}/business/register",
        ];

        foreach ($staticPaths as $path) {
            $url = $this->buildUrl($path);
            $alternates = $this->buildAlternateStaticUrls($path, $locales);
            $entries[] = $this->buildEntry($url, null, $alternates);
        }

        return $this->createSitemapFile("sitemaps/static-{$locale}.xml", $entries);
    }

    private function buildPagesSitemap(string $locale, array $pageTranslations): ?SitemapFile
    {
        $entries = [];

        foreach ($pageTranslations as $pageData) {
            $translation = $pageData['translations'][$locale] ?? null;
            if (! $translation || $translation['slug'] === '') {
                continue;
            }

            $path = "{$locale}/page/{$translation['slug']}";
            $url = $this->buildUrl($path);
            $lastMod = $this->maxDate([$pageData['page_updated_at'], $translation['updated_at']]);

            $alternates = [];
            foreach ($pageData['translations'] as $altLocale => $altData) {
                if (! empty($altData['slug'])) {
                    $alternates[$altLocale] = $this->buildUrl("{$altLocale}/page/{$altData['slug']}");
                }
            }

            $entries[] = $this->buildEntry($url, $lastMod, $alternates);
        }

        return $this->createSitemapFile("sitemaps/pages-{$locale}.xml", $entries);
    }

    private function buildBlogListingsSitemap(string $locale, array $locales, array $countries, array $countryLastMods): ?SitemapFile
    {
        $entries = [];

        foreach ($countries as $country) {
            $path = "{$locale}/{$country}/blog";
            $url = $this->buildUrl($path);
            $lastMod = $countryLastMods[$country] ?? null;

            $alternates = [];
            foreach ($locales as $altLocale) {
                $alternates[$altLocale] = $this->buildUrl("{$altLocale}/{$country}/blog");
            }

            $entries[] = $this->buildEntry($url, $lastMod, $alternates);
        }

        return $this->createSitemapFile("sitemaps/blog-listings-{$locale}.xml", $entries);
    }

    private function buildBlogPostsSitemaps(string $locale, string $country, array $blogs, array $locales): array
    {
        $entries = [];
        foreach ($blogs as $blog) {
            $translation = $this->findTranslation($blog['translations'], $locale);
            if (! $translation || $translation['slug'] === '') {
                continue;
            }

            $path = "{$locale}/{$country}/blog/{$translation['slug']}";
            $url = $this->buildUrl($path);
            $lastMod = $this->maxDate([$blog['updated_at'], $translation['updated_at']]);
            $alternates = $this->buildBlogAlternates($country, $blog['translations']);
            $imageUrl = $blog['image'] ?? null;
            $imageTitle = $translation['title'] ?? null;

            $entries[] = $this->buildEntry($url, $lastMod, $alternates, $imageUrl, $imageTitle);
        }

        if (empty($entries)) {
            return [];
        }

        $files = [];
        $chunks = array_chunk($entries, $this->maxUrlsPerSitemap);

        foreach ($chunks as $index => $chunkEntries) {
            $path = "sitemaps/blogs-{$locale}-{$country}-" . ($index + 1) . '.xml';
            $file = $this->createSitemapFile($path, $chunkEntries);
            if ($file) {
                $files[] = $file;
            }
        }

        return $files;
    }

    private function buildIndex(array $files): SitemapIndex
    {
        $index = SitemapIndex::create();

        foreach ($files as $file) {
            $loc = $this->buildUrl($file->path);
            $tag = SitemapTag::create($loc);
            if ($file->lastModified) {
                $tag->setLastModificationDate($file->lastModified);
            }
            $index->add($tag);
        }

        return $index;
    }

    private function createSitemapFile(string $path, array $entries): ?SitemapFile
    {
        if (empty($entries)) {
            return null;
        }

        $sitemap = Sitemap::create();
        $maxLastMod = null;

        foreach ($entries as $entry) {
            $sitemap->add($entry['url']);
            if ($entry['lastmod']) {
                $maxLastMod = $this->maxDate([$maxLastMod, $entry['lastmod']]);
            }
        }

        return new SitemapFile($path, $sitemap, $maxLastMod, count($entries));
    }

    private function buildEntry(
        string $url,
        ?Carbon $lastMod,
        array $alternates = [],
        ?string $imageUrl = null,
        ?string $imageTitle = null
    ): array {
        $this->urlPolicy->assertAllowed($url);

        $tag = Url::create($url);
        if ($lastMod) {
            $tag->setLastModificationDate($lastMod);
        }

        foreach ($alternates as $locale => $alternateUrl) {
            $this->urlPolicy->assertAllowed($alternateUrl);
            $tag->addAlternate($alternateUrl, (string) $locale);
        }

        if ($imageUrl) {
            $tag->addImage($imageUrl, '', '', (string) $imageTitle);
        }

        return [
            'url' => $tag,
            'lastmod' => $lastMod,
        ];
    }

    private function groupPageTranslations(array $rows): array
    {
        $pages = [];

        foreach ($rows as $row) {
            $pageId = (int) $row['page_id'];
            $locale = (string) $row['locale'];
            $slug = (string) $row['slug'];

            if (! isset($pages[$pageId])) {
                $pages[$pageId] = [
                    'page_updated_at' => $row['page_updated_at'] ?? null,
                    'translations' => [],
                ];
            }

            $pages[$pageId]['translations'][$locale] = [
                'slug' => $slug,
                'updated_at' => $row['updated_at'] ?? null,
            ];
        }

        return $pages;
    }

    private function extractCountries(array $blogs): array
    {
        $countries = [];

        foreach ($blogs as $blog) {
            $blogCountries = $blog['countries'] ?? null;
            if (is_array($blogCountries)) {
                foreach ($blogCountries as $country) {
                    $country = strtolower((string) $country);
                    if ($country !== '' && preg_match('/^[a-z]{2}$/', $country)) {
                        $countries[] = $country;
                    }
                }
            }
        }

        $countries = array_values(array_unique($countries));

        if (empty($countries)) {
            $countries = ['us'];
        }

        return $countries;
    }

    private function filterBlogsByCountry(array $blogs, string $country): array
    {
        $visible = [];
        foreach ($blogs as $blog) {
            $blogCountries = $blog['countries'] ?? null;
            if ($blogCountries === null) {
                $visible[] = $blog;
                continue;
            }

            if (is_array($blogCountries)) {
                $normalized = array_map(static fn ($value) => strtolower((string) $value), $blogCountries);
                if (in_array($country, $normalized, true)) {
                    $visible[] = $blog;
                }
            }
        }

        return $visible;
    }

    private function maxBlogLastModified(array $blogs): ?Carbon
    {
        $max = null;
        foreach ($blogs as $blog) {
            $max = $this->maxDate([$max, $blog['updated_at'] ?? null]);
            foreach ($blog['translations'] as $translation) {
                $max = $this->maxDate([$max, $translation['updated_at'] ?? null]);
            }
        }

        return $max;
    }

    private function findTranslation(array $translations, string $locale): ?array
    {
        foreach ($translations as $translation) {
            if (($translation['locale'] ?? null) === $locale) {
                return $translation;
            }
        }

        return null;
    }

    private function buildBlogAlternates(string $country, array $translations): array
    {
        $alternates = [];
        foreach ($translations as $translation) {
            $locale = $translation['locale'] ?? null;
            $slug = $translation['slug'] ?? null;
            if (! $locale || ! $slug) {
                continue;
            }

            $alternates[$locale] = $this->buildUrl("{$locale}/{$country}/blog/{$slug}");
        }

        return $alternates;
    }

    private function buildAlternateStaticUrls(string $path, array $locales): array
    {
        $segments = explode('/', trim($path, '/'));
        array_shift($segments);
        $suffix = implode('/', $segments);
        $alternates = [];

        foreach ($locales as $locale) {
            if ($suffix === '') {
                $alternates[$locale] = $this->buildUrl("{$locale}");
                continue;
            }

            $alternates[$locale] = $this->buildUrl("{$locale}/{$suffix}");
        }

        return $alternates;
    }

    private function buildUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    private function maxDate(array $dates): ?Carbon
    {
        $max = null;
        foreach ($dates as $date) {
            if ($date === null) {
                continue;
            }
            $carbon = $this->toCarbon($date);
            $max = $max ? ($carbon->greaterThan($max) ? $carbon : $max) : $carbon;
        }

        return $max;
    }

    private function toCarbon(mixed $value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        return Carbon::parse($value);
    }
}
