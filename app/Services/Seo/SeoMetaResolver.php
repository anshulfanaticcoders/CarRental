<?php

namespace App\Services\Seo;

use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class SeoMetaResolver
{
    public function resolveForRoute(
        string $routeName,
        array $routeParams,
        string $locale,
        string $canonicalUrl,
        ?string $robots = null
    ): SeoData {
        $hash = $this->hashRouteParams($routeParams);

        // Intentionally no caching: admin SEO changes should reflect immediately.
        $resolved = SeoMeta::query()
            ->with('translations')
            ->where('route_name', $routeName)
            ->where('route_params_hash', $hash)
            ->first();

        return $this->buildSeoData($resolved, $locale, $canonicalUrl, $robots);
    }

    public function resolveForModel(
        Model $model,
        string $locale,
        string $canonicalUrl,
        ?string $robots = null
    ): SeoData {
        // Intentionally no caching: admin SEO changes should reflect immediately.
        $resolved = SeoMeta::query()
            ->with('translations')
            ->where('seoable_type', $model->getMorphClass())
            ->where('seoable_id', $model->getKey())
            ->first();

        return $this->buildSeoData($resolved, $locale, $canonicalUrl, $robots);
    }

    public function hashRouteParams(array $routeParams): string
    {
        $normalized = $this->normalizeRouteParams($routeParams);
        $json = json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Failed to encode route params for SEO hashing.');
        }
        return sha1($json);
    }

    private function normalizeRouteParams(array $routeParams): array
    {
        // Ensure deterministic order and normalization.
        $normalized = Arr::dot($routeParams);
        ksort($normalized);

        foreach ($normalized as $k => $v) {
            if (is_string($v)) {
                $normalized[$k] = trim($v);
            }
        }

        return $normalized;
    }

    private function buildSeoData(?SeoMeta $seoMeta, string $locale, string $canonicalUrl, ?string $robots): SeoData
    {
        $canonicalUrl = $this->normalizeAbsoluteUrl($canonicalUrl);

        $title = config('seo.defaults.title', config('app.name', 'Vrooem'));
        $description = config('seo.defaults.description');
        $image = config('seo.defaults.image');
        $finalRobots = $robots;

        if ($seoMeta) {
            $translation = $seoMeta->translations->firstWhere('locale', $locale);
            $title = $translation?->seo_title ?: $seoMeta->seo_title;
            $description = $translation?->meta_description ?: $seoMeta->meta_description;
            $image = $seoMeta->seo_image_url ?: $image;

            if (! empty($seoMeta->canonical_url)) {
                $canonicalUrl = $this->normalizeAbsoluteUrl($seoMeta->canonical_url);
            }
        }

        $title = $this->limitLength((string) $title, 60);
        if ($description !== null) {
            $description = $this->limitLength((string) $description, 160);
        }

        return new SeoData((string) $title, $description, $canonicalUrl, $image, $finalRobots);
    }

    private function normalizeAbsoluteUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return rtrim((string) config('app.url'), '/');
        }

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        return rtrim((string) config('app.url'), '/') . '/' . ltrim($url, '/');
    }

    private function limitLength(string $value, int $max): string
    {
        $value = trim($value);
        if ($value === '') {
            return $value;
        }

        return Str::limit($value, $max, '');
    }
}
