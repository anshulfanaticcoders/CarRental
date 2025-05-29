<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SeoMeta;

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

        // Normalize homepage slug
        if ($slug === '/') {
            // Assuming homepage slug in DB is '/' or 'home'
            // If you use a different slug for homepage in DB, adjust here
            // For example, if it's 'home', then:
            // $seoMetaData = SeoMeta::where('url_slug', 'home')->first();
            $seoMetaData = SeoMeta::where('url_slug', '/')->first();
        } else {
            // For other pages, remove leading/trailing slashes for consistency if needed
            $normalizedSlug = trim($slug, '/');
            $seoMetaData = SeoMeta::where('url_slug', $normalizedSlug)
                                ->orWhere('url_slug', '/' . $normalizedSlug) // Check with leading slash
                                ->first();
        }

        if ($seoMetaData) {
            return response()->json($seoMetaData);
        } else {
            // No specific SEO meta data found for this slug.
            // Return 204 No Content, so app.js knows not to override existing tags.
            return response()->noContent();
        }
    }
}
