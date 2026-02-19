<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SeoMeta;
use App\Services\Seo\SeoMetaResolver;
use Illuminate\Support\Facades\Cache;

class SeoMetaController extends Controller
{
    private function forgetRouteSeoCache(string $routeName, string $routeParamsHash): void
    {
        foreach (config('app.available_locales', ['en']) as $locale) {
            Cache::forget('seo:route:' . $routeName . ':' . $routeParamsHash . ':' . $locale);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $seoMetas = SeoMeta::query()
            ->whereNotNull('route_name')
            ->latest()
            ->paginate(10);
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
        return Inertia::render('AdminDashboardPages/SEO/Create', [
            'routeTargets' => config('seo.route_targets', []),
            'available_locales' => config('app.available_locales', ['en']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, SeoMetaResolver $resolver)
    {
        $routeTargets = collect(config('seo.route_targets', []));
        $targetKey = $request->input('target_key');
        $target = $routeTargets->firstWhere('key', $targetKey);

        if (! $target) {
            return back()->withErrors(['target_key' => 'Invalid SEO target.']);
        }

        $country = $request->input('country');
        if ($targetKey === 'blog_listing') {
            $request->validate([
                'country' => 'required|string|size:2',
            ]);
            $country = strtolower((string) $country);
        }

        $routeName = (string) $target['route_name'];
        $routeParams = (array) ($target['params'] ?? []);
        if (array_key_exists('country', $routeParams)) {
            $routeParams['country'] = $country;
        }
        $routeParamsHash = $resolver->hashRouteParams($routeParams);

        $validatedData = $request->validate([
            'seo_title' => 'required|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'keywords' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|url|max:255',
            'seo_image_url' => 'nullable|url|max:255',
        ]);

        $seoMeta = SeoMeta::create(array_merge($validatedData, [
            'url_slug' => null,
            'route_name' => $routeName,
            'route_params' => $routeParams,
            'route_params_hash' => $routeParamsHash,
        ]));

        $translationsData = $request->input('translations', []);
        foreach (config('app.available_locales', ['en']) as $locale) {
            if (! isset($translationsData[$locale])) {
                continue;
            }
            $translationInput = $translationsData[$locale];

            $request->validate([
                "translations.{$locale}.seo_title" => 'nullable|string|max:60',
                "translations.{$locale}.meta_description" => 'nullable|string|max:160',
                "translations.{$locale}.keywords" => 'nullable|string|max:255',
            ]);

            $hasAnyValue = ! empty($translationInput['seo_title'])
                || ! empty($translationInput['meta_description'])
                || ! empty($translationInput['keywords']);

            // Avoid creating empty translation rows (Laravel may convert empty strings to null).
            if (! $hasAnyValue) {
                continue;
            }

            $seoMeta->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'url_slug' => null,
                    'seo_title' => $translationInput['seo_title'] ?? null,
                    'meta_description' => $translationInput['meta_description'] ?? null,
                    'keywords' => $translationInput['keywords'] ?? null,
                ]
            );
        }

        // Make changes visible immediately on the frontend.
        $this->forgetRouteSeoCache($routeName, $routeParamsHash);

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
        foreach (config('app.available_locales', ['en']) as $locale) {
            $translation = $seoMeta->translations->firstWhere('locale', $locale);
            $translations[$locale] = [
                'seo_title' => $translation?->seo_title,
                'meta_description' => $translation?->meta_description,
                'keywords' => $translation?->keywords,
            ];
        }

        $routeTargets = collect(config('seo.route_targets', []));
        $targetKey = $routeTargets->first(fn ($t) => ($t['route_name'] ?? null) === $seoMeta->route_name && empty($t['params']))['key'] ?? null;
        $country = null;
        if ($seoMeta->route_name === 'blog') {
            $targetKey = 'blog_listing';
            $country = $seoMeta->route_params['country'] ?? null;
        }

        return Inertia::render('AdminDashboardPages/SEO/Edit', [
            'seoMeta' => $seoMeta,
            'translations' => $translations, // Pass prepared translations
            'routeTargets' => config('seo.route_targets', []),
            'available_locales' => config('app.available_locales', ['en']),
            'targetKey' => $targetKey,
            'country' => $country,
        ]); // Or a shared Form component for Create/Edit
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id, SeoMetaResolver $resolver)
    {
        $seoMeta = SeoMeta::findOrFail($id);

        $oldRouteName = (string) ($seoMeta->route_name ?? '');
        $oldRouteParamsHash = (string) ($seoMeta->route_params_hash ?? '');

        $routeTargets = collect(config('seo.route_targets', []));
        $targetKey = $request->input('target_key');
        $target = $routeTargets->firstWhere('key', $targetKey);

        if (! $target) {
            return back()->withErrors(['target_key' => 'Invalid SEO target.']);
        }

        $country = $request->input('country');
        if ($targetKey === 'blog_listing') {
            $request->validate([
                'country' => 'required|string|size:2',
            ]);
            $country = strtolower((string) $country);
        }

        $routeName = (string) $target['route_name'];
        $routeParams = (array) ($target['params'] ?? []);
        if (array_key_exists('country', $routeParams)) {
            $routeParams['country'] = $country;
        }
        $routeParamsHash = $resolver->hashRouteParams($routeParams);

        $validatedData = $request->validate([
            'seo_title' => 'required|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'keywords' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|url|max:255',
            'seo_image_url' => 'nullable|url|max:255',
        ]);

        $seoMeta->update(array_merge($validatedData, [
            'url_slug' => null,
            'route_name' => $routeName,
            'route_params' => $routeParams,
            'route_params_hash' => $routeParamsHash,
        ]));

        // Handle translations
        $translationsData = $request->input('translations', []);
        foreach (config('app.available_locales', ['en']) as $locale) {
            if (! isset($translationsData[$locale])) {
                continue;
            }

            $translationInput = $translationsData[$locale];
            $request->validate([
                "translations.{$locale}.seo_title" => 'nullable|string|max:60',
                "translations.{$locale}.meta_description" => 'nullable|string|max:160',
                "translations.{$locale}.keywords" => 'nullable|string|max:255',
            ]);

            $hasAnyValue = ! empty($translationInput['seo_title'])
                || ! empty($translationInput['meta_description'])
                || ! empty($translationInput['keywords']);

            if (! $hasAnyValue) {
                // Keep table clean: if translation exists but all fields cleared, remove row.
                $seoMeta->translations()->where('locale', $locale)->delete();
                continue;
            }

            $seoMeta->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'seo_title' => $translationInput['seo_title'] ?? null,
                    'meta_description' => $translationInput['meta_description'] ?? null,
                    'keywords' => $translationInput['keywords'] ?? null,
                    'url_slug' => null,
                ]
            );
        }

        // Make changes visible immediately on the frontend.
        if ($oldRouteName !== '' && $oldRouteParamsHash !== '') {
            $this->forgetRouteSeoCache($oldRouteName, $oldRouteParamsHash);
        }
        $this->forgetRouteSeoCache($routeName, $routeParamsHash);

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

        $routeName = (string) ($seoMeta->route_name ?? '');
        $routeParamsHash = (string) ($seoMeta->route_params_hash ?? '');
        $seoMeta->delete();

        if ($routeName !== '' && $routeParamsHash !== '') {
            $this->forgetRouteSeoCache($routeName, $routeParamsHash);
        }

        return redirect()->route('admin.seo-meta.index')->with('success', 'SEO Meta deleted successfully!');
    }
}
