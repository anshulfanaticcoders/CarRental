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

        $query = Page::query();

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
        $locale = $request->input('locale');

        $baseValidationRules = [
            'locale' => 'required|in:en,fr,nl',
            // SEO Meta Validation Rules
            'seo_title'       => 'nullable|string|max:60',
            'meta_description'=> 'nullable|string|max:160',
            'keywords'        => 'nullable|string|max:255',
            'canonical_url'   => 'nullable|url|max:255',
            'seo_image_url'   => 'nullable|url|max:255',
        ];

        if ($locale === 'en') {
            $request->validate(array_merge($baseValidationRules, [
                'title' => 'required|string|max:255',
                'content' => 'required',
            ]));
        } elseif ($locale === 'fr') {
            $request->validate(array_merge($baseValidationRules, [
                'title' => 'required|string|max:255',
                'content' => 'required',
            ]));
        } elseif ($locale === 'nl') {
            $request->validate(array_merge($baseValidationRules, [
                'title' => 'required|string|max:255',
                'content' => 'required',
            ]));
        } else {
             $request->validate($baseValidationRules); // Should not happen if locale is required in:en,fr,nl
        }


        $title = $request->input('title');
        $content = $request->input('content');
        $slug = Str::slug($title); // Generate slug from the title of the current locale being saved

        // Check if slug already exists
        $existingPage = Page::where('slug', $slug)->first();
        if ($existingPage) {
            // Append a unique identifier if slug exists
            $slug = $slug . '-' . uniqid();
        }

        $page = Page::create([
            'slug' => $slug, // Slug is based on the first title provided
        ]);

        $page->translations()->create([
            'locale' => $locale,
            'title' => $title,
            'content' => $content,
        ]);

        // Create or Update SEO Meta
        $seoData = $request->only(['seo_title', 'meta_description', 'keywords', 'canonical_url', 'seo_image_url']);
        // Ensure url_slug is not mass-assignable or is handled correctly if present in $seoData
        // SeoMeta model's $fillable should not include url_slug if we set it manually here.
        // Or, ensure it's unique. The SeoMetaController handles uniqueness for its own form.
        // Here, the slug is derived from the page.

        if (array_filter($seoData)) { // Check if there's any SEO data provided
            SeoMeta::updateOrCreate(
                ['url_slug' => $slug], // Use the original slug for SEO
                $seoData
            );
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
        $seoMeta = SeoMeta::where('url_slug', $page->slug)->first();

        return Inertia::render('AdminDashboardPages/Pages/Edit', [
            'page' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'translations' => $translations,
                'locale' => $locale, // Current app locale
            ],
            'seoMeta' => $seoMeta,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Page $page)
    {
        $request->validate([
            'locale' => 'required|in:en,fr,nl',
            'title' => 'required|string|max:255',
            'content' => 'required',
            // SEO Meta Validation Rules
            'seo_title'       => 'nullable|string|max:60',
            'meta_description'=> 'nullable|string|max:160',
            'keywords'        => 'nullable|string|max:255',
            'canonical_url'   => 'nullable|url|max:255',
            'seo_image_url'   => 'nullable|url|max:255',
        ]);

        $locale = $request->input('locale');
        $title = $request->input('title');
        $content = $request->input('content');

        // Note: Slug update logic is not present. If title changes, slug currently does not.
        // If slug could change, SEO meta association would need to track old slug or use page_id.
        // For now, assuming slug is fixed after creation.

        $translation = $page->translations()->where('locale', $locale)->first();

        if ($translation) {
            $translation->title = $title;
            $translation->content = $content;
            $translation->save();
        } else {
            $page->translations()->create([
                'locale' => $locale,
                'title' => $title,
                'content' => $content,
            ]);
        }

        // Update or Create SEO Meta
        $seoData = $request->only(['seo_title', 'meta_description', 'keywords', 'canonical_url', 'seo_image_url']);
        
        if (array_filter($seoData) || SeoMeta::where('url_slug', $page->slug)->exists()) {
            // Update if SEO data is provided OR if an SEO record already exists (to allow clearing fields)
            SeoMeta::updateOrCreate(
                ['url_slug' => $page->slug],
                $seoData
            );
        }


        return redirect()->route('admin.pages.index')
            ->with('success', 'Page updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page)
    {
        // Delete associated SEO Meta first
        SeoMeta::where('url_slug', $page->slug)->delete();
        
        // Then delete the page and its translations (translations should cascade or be handled by model events)
        $page->delete();

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page and associated SEO Meta deleted successfully.');
    }

    /**
     * Display the specified page on the frontend.
     */
    public function showPublic(Request $request, $slug)
    {
        $locale = App::getLocale();
        $page = Page::where('slug', $slug)->firstOrFail();

        $translation = $page->translations()->where('locale', $locale)->first();

        if (!$translation) {
            $defaultLocale = config('app.fallback_locale', 'en');
            $translation = $page->translations()->where('locale', $defaultLocale)->first();
        }

        $pageData = [
            'title' => $translation ? $translation->title : 'Page Title Not Found',
            'content' => $translation ? $translation->content : '<p>Content not available in this language.</p>',
            'slug' => $page->slug,
        ];

        return Inertia::render('Frontend/Page', [
            'page' => $pageData,
        ]);
    }
}
