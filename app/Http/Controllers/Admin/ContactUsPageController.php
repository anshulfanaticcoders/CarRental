<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewContactUsPage as ContactUsPage; // Use the new model
use App\Models\NewContactUsPageTranslation as ContactUsPageTranslation; // Use the new translation model
use App\Models\SeoMeta; // Added for SEO Meta
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\App; // Added for locale access

class ContactUsPageController extends Controller
{
    /**
     * Display the contact us page content
     */
    public function index()
    {
        $contactPage = ContactUsPage::first();
        $translation = null;

        if ($contactPage) {
            // Fetch translation for the default/current locale for display purposes
            $locale = App::getLocale(); // Or config('app.fallback_locale', 'en');
            $translation = $contactPage->translations()->where('locale', $locale)->first();

            // If no translation for current locale, try fallback
            if (!$translation) {
                $fallbackLocale = config('app.fallback_locale', 'en');
                if ($locale !== $fallbackLocale) { // Avoid re-querying if current is already fallback
                    $translation = $contactPage->translations()->where('locale', $fallbackLocale)->first();
                }
            }
        } else {
            $contactPage = new ContactUsPage(); // Ensure $contactPage is an object
        }
        
        return Inertia::render('AdminDashboardPages/ContactUs/Index', [
            'contactPage' => $contactPage, // Contains non-translatable fields like id, hero_image_url, phone, email, address
            'translation' => $translation, // Contains translatable fields for the chosen locale
        ]);
    }

    /**
     * Show the form for editing the contact us page
     */
    public function edit()
    {
        $contactPage = ContactUsPage::first() ?? new ContactUsPage();
        $translations = $contactPage->translations->keyBy('locale');
        $locale = app()->getLocale();
        $seoMeta = SeoMeta::where('url_slug', 'contact-us')->first();
        
        return Inertia::render('AdminDashboardPages/ContactUs/Edit', [
            'contactPage' => $contactPage,
            'translations' => $translations,
            'currentLocale' => $locale, // Renamed to avoid conflict with form's locale
            'seoMeta' => $seoMeta,
        ]);
    }

    /**
     * Update the contact us page content
     */
    public function update(Request $request)
    {
        $fixedSlug = 'contact-us'; // Define the fixed slug

        $request->validate([
            // Non-translatable fields validation
            'contact_point_icons' => 'nullable|array',
            'contact_point_icons.*' => 'nullable|string|max:1000', // Max length for icon URL/class
            'hero_image' => 'nullable|file|image|max:5120',
            'phone_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',

            // Validation for the structure of translations
            'translations' => 'required|array',
            'translations.*.hero_title' => 'nullable|string|max:255',
            'translations.*.hero_description' => 'nullable|string',
            'translations.*.intro_text' => 'nullable|string',
            'translations.*.contact_points' => 'nullable|array',
            'translations.*.contact_points.*.title' => 'nullable|string|max:255', // If contact_points is an array of objects
            // Add validation for other fields within contact_points if necessary

            // SEO Meta Validation Rules
            'seo_title'       => 'nullable|string|max:60',
            'meta_description'=> 'nullable|string|max:160',
            'keywords'        => 'nullable|string|max:255',
            'canonical_url'   => 'nullable|url|max:255',
            'seo_image_url'   => 'nullable|url|max:255',
        ]);

        // Find or create the main ContactUsPage record
        $contactPage = ContactUsPage::first();
        if (!$contactPage) {
            $contactPage = new ContactUsPage();
        }

        // Handle non-translatable fields first
        $nonTranslatableData = $request->only(['phone_number', 'email', 'address', 'contact_point_icons']);

        // Handle hero image upload to UpCloud
        if ($request->hasFile('hero_image')) {
            if ($contactPage->hero_image_url) {
                Storage::disk('upcloud')->delete(
                    str_replace(Storage::disk('upcloud')->url(''), '', $contactPage->hero_image_url)
                );
            }
            $path = $request->file('hero_image')->store('contact-us-images', 'upcloud');
            $nonTranslatableData['hero_image_url'] = Storage::disk('upcloud')->url($path);
        } elseif ($request->filled('hero_image_url_existing') && !$request->hasFile('hero_image')) {
            // This case handles if we allow removing image or keeping existing one explicitly
            // For now, if no new image, existing one is kept unless explicitly cleared.
            // If hero_image_url is not in $request->only, it won't be overwritten by fill.
        }


        // Fill and save non-translatable data on the main model
        // We need to ensure $contactPage has an ID before creating translations if it's new.
        if (!$contactPage->exists) {
             // If it's a brand new ContactUsPage, save it first to get an ID
            $contactPage->fill($nonTranslatableData); // Fill with any non-translatable data available
            $contactPage->save(); // Save to get an ID
        } else {
            // If it exists, update its non-translatable fields
            $contactPage->update($nonTranslatableData);
        }

        // Handle translatable fields by iterating through the submitted translations
        $allTranslationsData = $request->input('translations', []);

        foreach ($allTranslationsData as $locale => $translatableDataForLocale) {
            // Ensure locale is valid if necessary (e.g., in_array($locale, ['en', 'fr', 'nl']))
            // For now, assuming frontend sends valid locales as keys

            // Filter only the allowed translatable fields for safety
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

        // Update or Create SEO Meta for Contact Us page
        $seoData = $request->only(['seo_title', 'meta_description', 'keywords', 'canonical_url', 'seo_image_url']);
        
        if (array_filter($seoData) || SeoMeta::where('url_slug', $fixedSlug)->exists()) {
            // Update if SEO data is provided OR if an SEO record already exists (to allow clearing fields)
            SeoMeta::updateOrCreate(
                ['url_slug' => $fixedSlug],
                $seoData
            );
        }

        return redirect()->route('admin.contact-us.index')
            ->with('success', 'Contact Us page updated successfully.');
    }

    /**
     * Delete the contact us page content
     */
    public function destroy()
    {
        $contactPage = ContactUsPage::first();
        
        if ($contactPage) {
            // If hero image exists, delete from UpCloud
            if ($contactPage->hero_image_url) {
                Storage::disk('upcloud')->delete(
                    str_replace(Storage::disk('upcloud')->url(''), '', $contactPage->hero_image_url)
                );
            }

            $contactPage->delete(); // This will also trigger deletion of translations via model events if set up, or handle manually.
            
            // Delete associated SEO Meta
            SeoMeta::where('url_slug', 'contact-us')->delete();
        }

        return redirect()->route('admin.contact-us.index')
            ->with('success', 'Contact Us page content and SEO Meta removed');
    }


    public function show()
    {
        $contactPage = ContactUsPage::first();

        if (!$contactPage) {
            // Optionally, handle case where no contact page content exists at all
            // For now, we'll assume it should exist or render with empty/default data
            $pageData = [
                'hero_title' => 'Contact Us Title Not Found',
                'hero_description' => '<p>Description not available.</p>',
                'intro_text' => '<p>Intro not available.</p>',
                'contact_points' => [],
                // Non-translatable fields can be empty or default
                'hero_image_url' => null,
                'phone_number' => '',
                'email' => '',
                'address' => '',
            ];
            return Inertia::render('ContactUs', ['contactPage' => $pageData]);
        }

        $locale = App::getLocale();
        $translation = $contactPage->translations()->where('locale', $locale)->first();

        if (!$translation) {
            $defaultLocale = config('app.fallback_locale', 'en');
            $translation = $contactPage->translations()->where('locale', $defaultLocale)->first();
        }

        // Prepare data for the view
        $pageData = [
            // Translatable fields from translation
            'hero_title' => $translation ? $translation->hero_title : 'Contact Us',
            'hero_description' => $translation ? $translation->hero_description : 'Please get in touch.',
            'intro_text' => $translation ? $translation->intro_text : '',
            
            // Non-translatable fields from the main model
            'hero_image_url' => $contactPage->hero_image_url,
            'phone_number' => $contactPage->phone_number,
            'email' => $contactPage->email,
            'address' => $contactPage->address,
            'id' => $contactPage->id, // Pass ID if needed by frontend
            
            // Combine icons with translated text for contact_points
            'contact_points' => [], // Initialize
        ];

        $icons = $contactPage->contact_point_icons ?: [];
        $translatedPoints = $translation ? ($translation->contact_points ?: []) : [];

        // Assuming translatedPoints is an array of objects like [{title: '...'}, {title: '...'}]
        // And icons is an array of strings ['icon1.svg', 'icon2.svg']
        // We need to merge them. The structure of translatedPoints will dictate how.
        // For simplicity, let's assume a 1:1 mapping by index.
        foreach ($translatedPoints as $index => $pointData) {
            $pageData['contact_points'][] = [
                'icon' => $icons[$index] ?? null, // Get icon by index, or null if not enough icons
                'title' => $pointData['title'] ?? ($pointData ?? 'N/A'), // Adapt based on $pointData structure
                // Add other textual fields from $pointData if they exist (e.g., 'details')
            ];
        }
        // If there are more icons than translated points, they won't be shown unless handled differently.

        return Inertia::render('ContactUs', [
            'contactPage' => $pageData
        ]);
    }
}
