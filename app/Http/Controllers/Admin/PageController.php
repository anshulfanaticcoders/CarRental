<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\SeoMeta; // Added for SEO Meta
use App\Services\Seo\SeoMetaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\App; // Added for locale access
use Illuminate\Support\Facades\Cache;

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
        return Inertia::render('AdminDashboardPages/Pages/Create', [
            'available_locales' => ['en', 'fr', 'nl', 'es', 'ar'],
            'current_locale' => App::getLocale(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $available_locales = ['en', 'fr', 'nl', 'es', 'ar'];
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

        // Create SEO meta bound to the page entity (not slug-based).
        $primaryTitleForSeo = $translationsData['en']['title'] ?? $translationsData[array_key_first($translationsData)]['title'];
        $seoData = [
            'seo_title' => $request->input('seo_title') ?: Str::limit($primaryTitleForSeo, 60),
            'meta_description' => $request->input('meta_description'),
            'keywords' => $request->input('keywords'),
            'canonical_url' => $request->input('canonical_url'),
            'seo_image_url' => $request->input('seo_image_url'),
        ];

        $seoMeta = SeoMeta::query()
            ->with('translations')
            ->where(function ($q) use ($page) {
                $q->where('seoable_type', $page->getMorphClass())
                    ->where('seoable_id', $page->getKey());
            })
            ->orWhere('url_slug', 'page/' . $page->slug)
            ->first();

        if (! $seoMeta) {
            $seoMeta = new SeoMeta();
        }

        $seoMeta->forceFill(array_filter($seoData, fn ($v) => !is_null($v) && $v !== ''));
        $seoMeta->seoable_type = $page->getMorphClass();
        $seoMeta->seoable_id = $page->getKey();
        $seoMeta->save();

        $seoTranslationsData = $request->input('seo_translations', []);
        foreach ($available_locales as $l) {
            if (! isset($seoTranslationsData[$l])) {
                continue;
            }
            $translationInput = $seoTranslationsData[$l];
            $request->validate([
                "seo_translations.{$l}.seo_title" => 'nullable|string|max:60',
                "seo_translations.{$l}.meta_description" => 'nullable|string|max:160',
                "seo_translations.{$l}.keywords" => 'nullable|string|max:255',
            ]);

            $seoMeta->translations()->updateOrCreate(
                ['locale' => $l],
                [
                    'url_slug' => null,
                    'seo_title' => $translationInput['seo_title'] ?? null,
                    'meta_description' => $translationInput['meta_description'] ?? null,
                    'keywords' => $translationInput['keywords'] ?? null,
                ]
            );
        }

        foreach ($available_locales as $locale) {
            Cache::forget('seo:model:' . get_class($page) . ':' . $page->getKey() . ':' . $locale);
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
        $allLocales = ['en', 'fr', 'nl', 'es', 'ar']; // Or from config

        $seoMeta = SeoMeta::with('translations')
            ->where(function ($q) use ($page) {
                $q->where('seoable_type', $page->getMorphClass())
                    ->where('seoable_id', $page->getKey());
            })
            ->orWhere('url_slug', 'page/' . $page->slug)
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
        $available_locales = ['en', 'fr', 'nl', 'es', 'ar'];
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

        // SEO meta bound to the page entity (not slug-based).
        $primaryTitleForSeo = $request->input("translations.en.title", $page->translations()->where('locale', 'en')->first()?->title);
        $seoData = [
            'seo_title' => $request->input('seo_title') ?: (is_string($primaryTitleForSeo) ? Str::limit($primaryTitleForSeo, 60) : null),
            'meta_description' => $request->input('meta_description'),
            'keywords' => $request->input('keywords'),
            'canonical_url' => $request->input('canonical_url'),
            'seo_image_url' => $request->input('seo_image_url'),
        ];

        $seoMeta = SeoMeta::query()
            ->with('translations')
            ->where(function ($q) use ($page) {
                $q->where('seoable_type', $page->getMorphClass())
                    ->where('seoable_id', $page->getKey());
            })
            ->orWhere('url_slug', 'page/' . $page->slug)
            ->first();

        if (! $seoMeta) {
            $seoMeta = new SeoMeta();
        }

        $seoMeta->forceFill(array_filter($seoData, fn ($v) => !is_null($v) && $v !== ''));
        $seoMeta->seoable_type = $page->getMorphClass();
        $seoMeta->seoable_id = $page->getKey();
        $seoMeta->save();

        $seoTranslationsData = $request->input('seo_translations', []);
        foreach ($available_locales as $l) {
            if (! isset($seoTranslationsData[$l])) {
                continue;
            }
            $translationInput = $seoTranslationsData[$l];
            $request->validate([
                "seo_translations.{$l}.seo_title" => 'nullable|string|max:60',
                "seo_translations.{$l}.meta_description" => 'nullable|string|max:160',
                "seo_translations.{$l}.keywords" => 'nullable|string|max:255',
            ]);

            $seoMeta->translations()->updateOrCreate(
                ['locale' => $l],
                [
                    'url_slug' => null,
                    'seo_title' => $translationInput['seo_title'] ?? null,
                    'meta_description' => $translationInput['meta_description'] ?? null,
                    'keywords' => $translationInput['keywords'] ?? null,
                ]
            );
        }

        foreach ($available_locales as $locale) {
            Cache::forget('seo:model:' . get_class($page) . ':' . $page->getKey() . ':' . $locale);
        }


        return redirect()->route('admin.pages.index')
            ->with('success', 'Page updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page)
    {
        $available_locales = ['en', 'fr', 'nl', 'es', 'ar'];

        SeoMeta::where('seoable_type', $page->getMorphClass())
            ->where('seoable_id', $page->getKey())
            ->delete();

        // Backward compatibility: clean up old slug-keyed records
        SeoMeta::where('url_slug', 'page/' . $page->slug)->delete();
        SeoMeta::where('url_slug', $page->slug)->delete();
        
        $page->delete();

        foreach ($available_locales as $locale) {
            Cache::forget('seo:model:' . get_class($page) . ':' . $page->getKey() . ':' . $locale);
        }

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

        $seo = app(SeoMetaResolver::class)->resolveForModel(
            $page,
            $locale,
            url("/{$locale}/page/{$slug}")
        )->toArray();

        $pages = Page::with('translations')->get()->keyBy('slug');

        // Find the 'About Us' page based on its English translation title
        $aboutUsPage = Page::whereHas('translations', function ($query) {
            $query->where('locale', 'en')->where('title', 'About Us');
        })->first();

        $aboutUsTranslatedSlug = null;
        if ($aboutUsPage) {
            // Get the translated slug for the current locale
            $aboutUsTranslation = $aboutUsPage->translations->firstWhere('locale', $locale);
            $aboutUsTranslatedSlug = $aboutUsTranslation ? $aboutUsTranslation->slug : null;
        }

        return Inertia::render('Frontend/Page', [
            'page' => $pageData,
            'seo' => $seo,
            'locale' => $locale,
            'pages' => $pages,
            'aboutUsTranslatedSlug' => $aboutUsTranslatedSlug, // Pass the dynamic slug
        ]);
    }
}
