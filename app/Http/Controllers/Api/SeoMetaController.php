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
            // Return default values or a 404 if no specific meta found
            // For now, let's return a 404, but you might want to return defaults
            return response()->json([
                'seo_title' => config('app.name', 'Default Application Title'),
                'meta_description' => 'Default meta description for the application.',
                // Add other default fields as needed
            ], 200); // Returning 200 with defaults, or 404 if no defaults desired
        }
    }
}
