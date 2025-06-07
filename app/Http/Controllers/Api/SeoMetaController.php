<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SeoMeta;
use Illuminate\Support\Facades\Cache;

class SeoMetaController extends Controller
{
    /**
     * Fetch SEO meta data by URL slug.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMetaBySlug(Request $request)
    {
        $slug = $request->query('slug');
        // Prioritize locale from request, fallback to app locale
        $locale = $request->input('locale', app()->getLocale());

        if (empty($slug)) {
            return response()->json(['error' => 'Slug parameter is required.'], 400);
        }

        $cacheKey = 'seo_meta_' . md5($slug) . '_' . $locale;
        
        $seoData = Cache::remember($cacheKey, 60 * 60, function () use ($slug, $locale) { // Cache for 60 minutes
            $query = SeoMeta::with('translations');

            // Normalize homepage slug
            if ($slug === '/') {
                $seoMeta = $query->where('url_slug', '/')->first();
            } else {
                // For other pages, remove leading/trailing slashes for consistency
                $normalizedSlug = trim($slug, '/');
                $seoMeta = $query->where('url_slug', $normalizedSlug)
                                  ->orWhere('url_slug', '/' . $normalizedSlug) // Check with leading slash
                                  ->first();
            }

            if (!$seoMeta) {
                return null;
            }

            // Get translation for the current locale
            $translation = $seoMeta->translations->firstWhere('locale', $locale);

            return [
                'url_slug'         => $seoMeta->url_slug,
                'seo_title'        => $translation->seo_title ?? $seoMeta->seo_title,
                'meta_description' => $translation->meta_description ?? $seoMeta->meta_description,
                'keywords'         => $translation->keywords ?? $seoMeta->keywords,
                'canonical_url'    => $seoMeta->canonical_url,
                'seo_image_url'    => $seoMeta->seo_image_url,
            ];
        });

        if ($seoData) {
            return response()->json($seoData);
        } else {
            // No specific SEO meta data found for this slug.
            return response()->noContent(); // 204 No Content
        }
    }
}
