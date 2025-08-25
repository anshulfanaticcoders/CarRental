<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewContactUsPage as ContactUsPage; // Use the new model
use App\Models\SeoMeta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\App;

class ContactUsPageController extends Controller
{
    public function index()
    {
        $contactPage = ContactUsPage::first();
        $translation = null;

        if ($contactPage) {
            $locale = App::getLocale();
            $translation = $contactPage->translations()->where('locale', $locale)->first();

            if (!$translation) {
                $fallbackLocale = config('app.fallback_locale', 'en');
                if ($locale !== $fallbackLocale) {
                    $translation = $contactPage->translations()->where('locale', $fallbackLocale)->first();
                }
            }
        } else {
            $contactPage = new ContactUsPage();
        }
        
        return Inertia::render('AdminDashboardPages/ContactUs/Index', [
            'contactPage' => $contactPage,
            'translation' => $translation,
        ]);
    }

    public function edit()
    {
        $contactPage = ContactUsPage::first() ?? new ContactUsPage();
        $translations = $contactPage->translations->keyBy('locale');
        $locale = app()->getLocale();
        $allLocales = ['en', 'fr', 'nl', 'es', 'ar'];
        
        $seoMeta = SeoMeta::with('translations')->where('url_slug', 'contact-us')->first();

        $seoTranslations = [];
        if ($seoMeta) {
            foreach ($allLocales as $l) {
                $translation = $seoMeta->translations->firstWhere('locale', $l);
                $seoTranslations[$l] = [
                    'url_slug'         => $translation->url_slug ?? 'contact-us',
                    'seo_title'        => $translation->seo_title ?? null,
                    'meta_description' => $translation->meta_description ?? null,
                    'keywords'         => $translation->keywords ?? null,
                ];
            }
        }
        
        return Inertia::render('AdminDashboardPages/ContactUs/Edit', [
            'contactPage' => $contactPage,
            'translations' => $translations,
            'available_locales' => $allLocales,
            'currentLocale' => $locale,
            'seoMeta' => $seoMeta,
            'seoTranslations' => $seoTranslations,
        ]);
    }

    public function update(Request $request)
    {
        $fixedSlug = 'contact-us';

        $request->validate([
            'contact_point_icons' => 'nullable|array',
            'contact_point_icons.*' => 'nullable|string|max:1000',
            'hero_image' => 'nullable|file|image|max:5120',
            'phone_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'translations' => 'required|array',
            'translations.*.hero_title' => 'nullable|string|max:255',
            'translations.*.hero_description' => 'nullable|string',
            'translations.*.intro_text' => 'nullable|string',
            'translations.*.contact_points' => 'nullable|array',
            'translations.*.contact_points.*.title' => 'nullable|string|max:255',
            'seo_title'       => 'nullable|string|max:60',
            'meta_description'=> 'nullable|string|max:160',
            'keywords'        => 'nullable|string|max:255',
            'canonical_url'   => 'nullable|url|max:255',
            'seo_image_url'   => 'nullable|url|max:255',
        ]);

        $contactPage = ContactUsPage::first();
        if (!$contactPage) {
            $contactPage = new ContactUsPage();
        }

        $nonTranslatableData = $request->only(['phone_number', 'email', 'address', 'contact_point_icons']);

        if ($request->hasFile('hero_image')) {
            if ($contactPage->hero_image_url) {
                Storage::disk('upcloud')->delete(
                    str_replace(Storage::disk('upcloud')->url(''), '', $contactPage->hero_image_url)
                );
            }
            $path = $request->file('hero_image')->store('contact-us-images', 'upcloud');
            $nonTranslatableData['hero_image_url'] = Storage::disk('upcloud')->url($path);
        }

        if (!$contactPage->exists) {
            $contactPage->fill($nonTranslatableData);
            $contactPage->save();
        } else {
            $contactPage->update($nonTranslatableData);
        }

        $allTranslationsData = $request->input('translations', []);
        foreach ($allTranslationsData as $locale => $translatableDataForLocale) {
            $allowedTranslatableFields = [
                'hero_title', 
                'hero_description', 
                'intro_text', 
                'contact_points'
            ];
            $filteredData = array_intersect_key($translatableDataForLocale, array_flip($allowedTranslatableFields));
            
            $contactPage->translations()->updateOrCreate(
                ['locale' => $locale],
                $filteredData
            );
        }

        $seoData = $request->only(['seo_title', 'meta_description', 'keywords', 'canonical_url', 'seo_image_url']);
        
        if (array_filter($seoData) || SeoMeta::where('url_slug', $fixedSlug)->exists()) {
            $seoMeta = SeoMeta::updateOrCreate(
                ['url_slug' => $fixedSlug],
                $seoData
            );

            $seoTranslationsData = $request->input('seo_translations', []);
            $available_locales = ['en', 'fr', 'nl', 'es', 'ar'];
            foreach ($available_locales as $locale) {
                if (isset($seoTranslationsData[$locale])) {
                    $translationInput = $seoTranslationsData[$locale];
                    $request->validate([
                        "seo_translations.{$locale}.url_slug" => 'nullable|string|max:255',
                        "seo_translations.{$locale}.seo_title" => 'nullable|string|max:60',
                        "seo_translations.{$locale}.meta_description" => 'nullable|string|max:160',
                        "seo_translations.{$locale}.keywords" => 'nullable|string|max:255',
                    ]);

                    $seoMeta->translations()->updateOrCreate(
                        ['locale' => $locale],
                        [
                            'url_slug'         => \Illuminate\Support\Str::slug($translationInput['url_slug'] ?? 'contact-us'),
                            'seo_title'        => $translationInput['seo_title'] ?? null,
                            'meta_description' => $translationInput['meta_description'] ?? null,
                            'keywords'         => $translationInput['keywords'] ?? null,
                        ]
                    );
                }
            }
        }

        return redirect()->route('admin.contact-us.index')
            ->with('success', 'Contact Us page updated successfully.');
    }

    public function destroy()
    {
        $contactPage = ContactUsPage::first();
        
        if ($contactPage) {
            if ($contactPage->hero_image_url) {
                Storage::disk('upcloud')->delete(
                    str_replace(Storage::disk('upcloud')->url(''), '', $contactPage->hero_image_url)
                );
            }
            $contactPage->delete();
            SeoMeta::where('url_slug', 'contact-us')->delete();
        }

        return redirect()->route('admin.contact-us.index')
            ->with('success', 'Contact Us page content and SEO Meta removed');
    }

    public function show()
    {
        $contactPage = ContactUsPage::first();
        $pages = \App\Models\Page::with('translations')->get()->keyBy('slug');
        $seoMeta = SeoMeta::with('translations')->where('url_slug', 'contact-us')->first();

        if (!$contactPage) {
            $pageData = [
                'hero_title' => 'Contact Us Title Not Found',
                'hero_description' => '<p>Description not available.</p>',
                'intro_text' => '<p>Intro not available.</p>',
                'contact_points' => [],
                'hero_image_url' => null,
                'phone_number' => '',
                'email' => '',
                'address' => '',
            ];
            return Inertia::render('ContactUs', [
                'contactPage' => $pageData,
                'seoMeta' => $seoMeta,
                'pages' => $pages,
            ]);
        }

        $locale = App::getLocale();
        $translation = $contactPage->translations()->where('locale', $locale)->first();

        if (!$translation) {
            $defaultLocale = config('app.fallback_locale', 'en');
            $translation = $contactPage->translations()->where('locale', $defaultLocale)->first();
        }

        $pageData = [
            'hero_title' => $translation ? $translation->hero_title : 'Contact Us',
            'hero_description' => $translation ? $translation->hero_description : 'Please get in touch.',
            'intro_text' => $translation ? $translation->intro_text : '',
            'hero_image_url' => $contactPage->hero_image_url,
            'phone_number' => $contactPage->phone_number,
            'email' => $contactPage->email,
            'address' => $contactPage->address,
            'id' => $contactPage->id,
            'contact_points' => [],
        ];

        $icons = $contactPage->contact_point_icons ?: [];
        $translatedPoints = $translation ? ($translation->contact_points ?: []) : [];

        foreach ($translatedPoints as $index => $pointData) {
            $pageData['contact_points'][] = [
                'icon' => $icons[$index] ?? null,
                'title' => $pointData['title'] ?? ($pointData ?? 'N/A'),
            ];
        }

        return Inertia::render('ContactUs', [
            'contactPage' => $pageData,
            'seoMeta' => $seoMeta,
            'pages' => $pages,
        ]);
    }
}
