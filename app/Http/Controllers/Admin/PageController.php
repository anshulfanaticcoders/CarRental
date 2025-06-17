<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\SeoMeta; // Added for SEO Meta
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\App; // Added for locale access

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = Page::with('translations');

        // Apply search filter if provided
        if ($search) {
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%");
        }

        $pages = $query->latest()->paginate(8)->withQueryString();

        return Inertia::render('AdminDashboardPages/Pages/Index', [
            'pages' => $pages,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('AdminDashboardPages/Pages/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $available_locales = ['en', 'fr', 'nl'];
        $validationRules = [
            'translations' => 'required|array',
            // SEO Meta Validation Rules
            'seo_title'       => 'nullable|string|max:60',
            'meta_description'=> 'nullable|string|max:160',
            'keywords'        => 'nullable|string|max:255',
            'canonical_url'   => 'nullable|url|max:255',
            'seo_image_url'   => 'nullable|url|max:255',
        ];

        foreach ($available_locales as $locale) {
            $validationRules["translations.{$locale}.title"] = 'required|string|max:255';
            $validationRules["translations.{$locale}.slug"] = 'required|string|max:255';
            $validationRules["translations.{$locale}.content"] = 'required|string';
        }
        $request->validate($validationRules);

        $translationsData = $request->input('translations');
        
        $slugTitle = $translationsData['en']['title'] ?? $translationsData[array_key_first($translationsData)]['title'];
        $mainSlug = Str::slug($slugTitle);
        $existingPage = Page::where('slug', $mainSlug)->first();
        if ($existingPage) {
            $mainSlug = $mainSlug . '-' . uniqid();
        }

        $page = Page::create([
            'slug' => $mainSlug,
        ]);

        foreach ($translationsData as $locale => $data) {
            if (in_array($locale, $available_locales) && !empty($data['title']) && !empty($data['content'])) {
                 $page->translations()->create([
                    'locale' => $locale,
                    'title' => $data['title'],
                    'slug' => Str::slug($data['slug']),
                    'content' => $data['content'],
                ]);
            }
        }

        // Create or Update SEO Meta with prefixed url_slug
        $seoData = $request->only(['seo_title', 'meta_description', 'keywords', 'canonical_url', 'seo_image_url']);
        $seoUrlSlug = 'page/' . $page->slug; // SEO url_slug IS prefixed

        if (array_filter($seoData) || !empty($request->input('seo_title'))) { // Save if any SEO data or at least a title
            if (empty($seoData['seo_title']) && !empty($title)) { // Default seo_title from page title
                $seoData['seo_title'] = Str::limit($title, 60);
            }
            $seoMeta = SeoMeta::updateOrCreate(
                ['url_slug' => $seoUrlSlug], 
                array_filter($seoData, fn($value) => !is_null($value))
            );

            // Handle SEO translations
            $seoTranslationsData = $request->input('seo_translations', []);
            $available_locales = ['en', 'fr', 'nl']; // Or from config
            foreach ($available_locales as $l) {
                if (isset($seoTranslationsData[$l])) {
                    $translationInput = $seoTranslationsData[$l];
                    $request->validate([
                        "seo_translations.{$l}.seo_title" => 'nullable|string|max:60',
                        "seo_translations.{$l}.meta_description" => 'nullable|string|max:160',
                        "seo_translations.{$l}.keywords" => 'nullable|string|max:255',
                    ]);

                    $pageTranslation = $page->translations->where('locale', $l)->first();
                    $seoMeta->translations()->updateOrCreate(
                        ['locale' => $l],
                        [
                            'url_slug'         => $pageTranslation ? $pageTranslation->slug : null,
                            'seo_title'        => $translationInput['seo_title'] ?? null,
                            'meta_description' => $translationInput['meta_description'] ?? null,
                            'keywords'         => $translationInput['keywords'] ?? null,
                        ]
                    );
                }
            }
        }


        return redirect()->route('admin.pages.index')
            ->with('success', 'Page created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Page $page)
    {
        return Inertia::render('AdminDashboardPages/Pages/Show', [
            'page' => $page
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Page $page)
    {
        $translations = $page->translations->keyBy('locale');
        $locale = app()->getLocale();
        $allLocales = ['en', 'fr', 'nl']; // Or from config
        $seoUrlSlug = 'page/' . $page->slug; // SEO url_slug IS prefixed
        
        $seoMeta = SeoMeta::with('translations') // Eager load translations
                            ->where('url_slug', $seoUrlSlug)
                            ->orWhere('url_slug', $page->slug) // Check for old non-prefixed too
                            ->orderByRaw("CASE WHEN url_slug = ? THEN 0 ELSE 1 END", [$seoUrlSlug]) // Prioritize prefixed
                            ->first();

        $seoTranslations = [];
        if ($seoMeta) {
            foreach ($allLocales as $l) {
                $translation = $seoMeta->translations->firstWhere('locale', $l);
                $seoTranslations[$l] = [
                    'seo_title'        => $translation->seo_title ?? null,
                    'meta_description' => $translation->meta_description ?? null,
                    'keywords'         => $translation->keywords ?? null,
                ];
            }
        }

        return Inertia::render('AdminDashboardPages/Pages/Edit', [
            'page' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'translations' => $translations,
                'locale' => $locale, 
            ],
            'available_locales' => $allLocales,
            'seoMeta' => $seoMeta,
            'seoTranslations' => $seoTranslations,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Page $page)
    {
        $available_locales = ['en', 'fr', 'nl'];
        $validationRules = [
            'translations' => 'required|array',
            // SEO Meta Validation Rules
            'seo_title'       => 'nullable|string|max:60',
            'meta_description'=> 'nullable|string|max:160',
            'keywords'        => 'nullable|string|max:255',
            'canonical_url'   => 'nullable|url|max:255',
            'seo_image_url'   => 'nullable|url|max:255',
        ];
        foreach ($available_locales as $locale) {
            $validationRules["translations.{$locale}.title"] = 'required|string|max:255';
            $validationRules["translations.{$locale}.slug"] = 'required|string|max:255';
            $validationRules["translations.{$locale}.content"] = 'required|string';
        }
        $request->validate($validationRules);

        $translationsData = $request->input('translations');
        foreach ($translationsData as $locale => $data) {
             if (in_array($locale, $available_locales) && !empty($data['title']) && !empty($data['content'])) {
                $page->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'title' => $data['title'],
                        'slug' => Str::slug($data['slug']),
                        'content' => $data['content'],
                    ]
                );
            }
        }

        // Page model is saved if translations are updated.
        // No direct save of $page needed here unless other $page attributes were changed.

        // Update or Create SEO Meta
        $seoData = $request->only(['seo_title', 'meta_description', 'keywords', 'canonical_url', 'seo_image_url']);
        $seoUrlSlug = 'page/' . $page->slug; // SEO url_slug IS prefixed

        // Delete any old SeoMeta that might exist with the non-prefixed slug
        SeoMeta::where('url_slug', $page->slug)->where('url_slug', '!=', $seoUrlSlug)->delete();
        
        if (array_filter($seoData) || !empty($request->input('seo_title')) || SeoMeta::where('url_slug', $seoUrlSlug)->exists()) {
            $primaryTitleForSeo = $request->input("translations.en.title", $page->translations()->where('locale', 'en')->first()?->title);
            if (empty($seoData['seo_title']) && !empty($primaryTitleForSeo)) {
                $seoData['seo_title'] = Str::limit($primaryTitleForSeo, 60);
            }
            
            $seoMeta = SeoMeta::updateOrCreate(
                ['url_slug' => $seoUrlSlug], // Use the prefixed slug for SEO
                array_filter($seoData, fn($value) => !is_null($value)) 
            );

            // Handle SEO translations
            $seoTranslationsData = $request->input('seo_translations', []);
            $available_locales = ['en', 'fr', 'nl']; // Or from config
            foreach ($available_locales as $l) {
                if (isset($seoTranslationsData[$l])) {
                    $translationInput = $seoTranslationsData[$l];
                    $request->validate([
                        "seo_translations.{$l}.seo_title" => 'nullable|string|max:60',
                        "seo_translations.{$l}.meta_description" => 'nullable|string|max:160',
                        "seo_translations.{$l}.keywords" => 'nullable|string|max:255',
                    ]);

                    $pageTranslation = $page->translations->where('locale', $l)->first();
                    $seoMeta->translations()->updateOrCreate(
                        ['locale' => $l],
                        [
                            'url_slug'         => $pageTranslation ? $pageTranslation->slug : null,
                            'seo_title'        => $translationInput['seo_title'] ?? null,
                            'meta_description' => $translationInput['meta_description'] ?? null,
                            'keywords'         => $translationInput['keywords'] ?? null,
                        ]
                    );
                }
            }
        }


        return redirect()->route('admin.pages.index')
            ->with('success', 'Page updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page)
    {
        // Delete associated SEO Meta first (using prefixed slug)
        $seoUrlSlug = 'page/' . $page->slug;
        SeoMeta::where('url_slug', $seoUrlSlug)->delete();
        // Also delete any potential old non-prefixed one, just in case
        SeoMeta::where('url_slug', $page->slug)->delete(); 
        
        $page->delete();

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page and associated SEO Meta deleted successfully.');
    }

    /**
     * Display the specified page on the frontend.
     */
    public function showPublic($locale, $slug)
    {
        App::setLocale($locale);

        $translation = \App\Models\PageTranslation::where('slug', $slug)->where('locale', $locale)->firstOrFail();
        $page = $translation->page;

        if (!$translation) {
            $defaultLocale = config('app.fallback_locale', 'en');
            $translation = $page->translations()->where('locale', $defaultLocale)->first();
        }

        $pageData = [
            'title' => $translation ? $translation->title : 'Page Title Not Found',
            'content' => $translation ? $translation->content : '<p>Content not available in this language.</p>',
            'slug' => $page->slug,
        ];

        $seoMeta = null;
        $seoMetaTranslation = \App\Models\SeoMetaTranslation::where('url_slug', $slug)->where('locale', $locale)->first();

        if ($seoMetaTranslation) {
            $seoMeta = \App\Models\SeoMeta::with('translations')->find($seoMetaTranslation->seo_meta_id);
        }

        $pages = Page::with('translations')->get()->keyBy('slug');

        return Inertia::render('Frontend/Page', [
            'page' => $pageData,
            'seoMeta' => $seoMeta,
            'locale' => $locale,
            'pages' => $pages,
        ]);
    }
}
