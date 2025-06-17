<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SeoMeta;

class SeoMetaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $seoMetas = SeoMeta::latest()->paginate(10);
        return Inertia::render('AdminDashboardPages/SEO/index', [
            'seoMetas' => $seoMetas,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('AdminDashboardPages/SEO/Create'); // Or a shared Form component
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'url_slug'        => ['nullable', 'string', 'max:255', 'regex:/^(\/|\/?([a-z0-9]+(?:-[a-z0-9]+)*)(?:\/[a-z0-9]+(?:-[a-z0-9]+)*)*)$/', 'unique:seo_metas,url_slug'],
            'seo_title'       => 'required|string|max:60',
            'meta_description'=> 'nullable|string|max:160',
            'keywords'        => 'nullable|string|max:255',
            'canonical_url'   => 'nullable|url|max:255',
            'seo_image_url'   => 'nullable|url|max:255',
            // Add any other fields like 'page_id' or 'entity_type' if these SEO tags are for specific items
        ]);

        // Separate translations from the main data
        $translationsData = $request->input('translations', []);

        $seoMeta = SeoMeta::create($validatedData);

        foreach (['en', 'fr', 'nl'] as $locale) {
            if (isset($translationsData[$locale])) {
                $translationInput = $translationsData[$locale];
                // Validate translation fields
                $request->validate([
                    "translations.{$locale}.seo_title" => 'nullable|string|max:60',
                    "translations.{$locale}.meta_description" => 'nullable|string|max:160',
                    "translations.{$locale}.keywords" => 'nullable|string|max:255',
                ]);

                $seoMeta->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'seo_title'        => $translationInput['seo_title'] ?? null,
                        'meta_description' => $translationInput['meta_description'] ?? null,
                        'keywords'         => $translationInput['keywords'] ?? null,
                        'url_slug'         => $translationInput['url_slug'] ?? null,
                    ]
                );
            }
        }

        return redirect()->route('admin.seo-meta.index')->with('success', 'SEO Meta created successfully!');
    }

    /**
     * Display the specified resource.
     * (Often not needed for admin CRUD if edit page shows all details)
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function show($id)
    {
        $seoMeta = SeoMeta::findOrFail($id);
        // Typically, for an admin CRUD, 'show' might redirect to 'edit' or display a read-only view.
        // For simplicity, we'll redirect to edit. If you need a separate show view, create it.
        return redirect()->route('admin.seo-meta.edit', $seoMeta->id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function edit($id)
    {
        $seoMeta = SeoMeta::with('translations')->findOrFail($id);

        // Prepare translations for the form
        $translations = [];
        foreach (['en', 'fr', 'nl'] as $locale) {
            $translation = $seoMeta->translations->firstWhere('locale', $locale);
            $translations[$locale] = [
                'seo_title'        => $translation->seo_title ?? null,
                'meta_description' => $translation->meta_description ?? null,
                'keywords'         => $translation->keywords ?? null,
                'url_slug'         => $translation->url_slug ?? null,
            ];
        }

        return Inertia::render('AdminDashboardPages/SEO/Edit', [
            'seoMeta' => $seoMeta,
            'translations' => $translations, // Pass prepared translations
        ]); // Or a shared Form component for Create/Edit
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // $seoMeta = SeoMeta::findOrFail($id);
        $validatedData = $request->validate([
            'url_slug'        => ['nullable', 'string', 'max:255', 'regex:/^(\/|\/?([a-z0-9]+(?:-[a-z0-9]+)*)(?:\/[a-z0-9]+(?:-[a-z0-9]+)*)*)$/', 'unique:seo_metas,url_slug,' . $id],
            'seo_title'       => 'required|string|max:60',
            'meta_description'=> 'nullable|string|max:160',
            'keywords'        => 'nullable|string|max:255',
            'canonical_url'   => 'nullable|url|max:255',
            'seo_image_url'   => 'nullable|url|max:255',
        ]);

        $seoMeta = SeoMeta::findOrFail($id);
        $seoMeta->update($validatedData);

        // Handle translations
        $translationsData = $request->input('translations', []);
        foreach (['en', 'fr', 'nl'] as $locale) {
            if (isset($translationsData[$locale])) {
                $translationInput = $translationsData[$locale];
                // Validate translation fields
                $request->validate([
                    "translations.{$locale}.seo_title" => 'nullable|string|max:60',
                    "translations.{$locale}.meta_description" => 'nullable|string|max:160',
                    "translations.{$locale}.keywords" => 'nullable|string|max:255',
                    "translations.{$locale}.url_slug" => 'nullable|string|max:255',
                ]);

                $seoMeta->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'seo_title'        => $translationInput['seo_title'] ?? null,
                        'meta_description' => $translationInput['meta_description'] ?? null,
                        'keywords'         => $translationInput['keywords'] ?? null,
                        'url_slug'         => $translationInput['url_slug'] ?? null,
                    ]
                );
            } else {
                // Optionally, delete translation if all its fields are empty or if locale is not present
                // $seoMeta->translations()->where('locale', $locale)->delete();
            }
        }

        return redirect()->route('admin.seo-meta.index')->with('success', 'SEO Meta updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $seoMeta = SeoMeta::findOrFail($id);
        $seoMeta->delete();

        return redirect()->route('admin.seo-meta.index')->with('success', 'SEO Meta deleted successfully!');
    }
}
