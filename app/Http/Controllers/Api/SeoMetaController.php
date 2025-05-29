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

        if (empty($slug)) {
            return response()->json(['error' => 'Slug parameter is required.'], 400);
        }

        $cacheKey = 'seo_meta_' . md5($slug);
        $seoMetaData = Cache::remember($cacheKey, 60 * 60, function () use ($slug) { // Cache for 60 minutes
            // Normalize homepage slug
            if ($slug === '/') {
                return SeoMeta::where('url_slug', '/')->first();
            } else {
                // For other pages, remove leading/trailing slashes for consistency if needed
                $normalizedSlug = trim($slug, '/');
                return SeoMeta::where('url_slug', $normalizedSlug)
                                    ->orWhere('url_slug', '/' . $normalizedSlug) // Check with leading slash
                                    ->first();
            }
        });

        if ($seoMetaData) {
            return response()->json($seoMetaData);
        } else {
            // No specific SEO meta data found for this slug.
            // Return 204 No Content, so app.js knows not to override existing tags.
            return response()->noContent();
        }
    }
}
