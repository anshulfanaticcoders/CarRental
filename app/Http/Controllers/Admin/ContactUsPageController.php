<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactUsPage;
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
        
        return Inertia::render('AdminDashboardPages/ContactUs/Edit', [
            'contactPage' => $contactPage,
            'translations' => $translations,
            'currentLocale' => $locale, // Renamed to avoid conflict with form's locale
        ]);
    }

    /**
     * Update the contact us page content
     */
    public function update(Request $request)
    {
        $request->validate([
            'locale' => 'required|in:en,fr,nl',
            // Translatable fields
            'hero_title' => 'nullable|string|max:255',
            'hero_description' => 'nullable|string',
            'intro_text' => 'nullable|string',
            'contact_points' => 'nullable|array', // Assuming this structure comes from the form for the current locale
            // Non-translatable fields
            'hero_image' => 'nullable|file|image|max:5120', // 5MB max
            'phone_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        $locale = $request->input('locale');

        // Find or create the main ContactUsPage record
        // Since it's a single page, we always fetch or create the first one.
        $contactPage = ContactUsPage::first();
        if (!$contactPage) {
            $contactPage = new ContactUsPage();
            // Potentially set default non-translatable values if creating for the first time
            // For now, we assume it's mainly for updating existing or creating if absolutely no record.
        }

        // Handle non-translatable fields first
        $nonTranslatableData = $request->only(['phone_number', 'email', 'address']);

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


        // Handle translatable fields
        $translatableData = $request->only(['hero_title', 'hero_description', 'intro_text', 'contact_points']);
        
        $translation = $contactPage->translations()->updateOrCreate(
            ['locale' => $locale],
            $translatableData
        );

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

            $contactPage->delete();
        }

        return redirect()->route('admin.contact-us.index')
            ->with('success', 'Contact Us page content removed');
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
            // Translatable fields from translation or defaults
            'hero_title' => $translation ? $translation->hero_title : ($contactPage->hero_title ?: 'Contact Us'),
            'hero_description' => $translation ? $translation->hero_description : ($contactPage->hero_description ?: 'Please get in touch.'),
            'intro_text' => $translation ? $translation->intro_text : ($contactPage->intro_text ?: ''),
            'contact_points' => $translation ? $translation->contact_points : ($contactPage->contact_points ?: []),
            
            // Non-translatable fields from the main model
            'hero_image_url' => $contactPage->hero_image_url,
            'phone_number' => $contactPage->phone_number,
            'email' => $contactPage->email,
            'address' => $contactPage->address,
            'id' => $contactPage->id // Pass ID if needed by frontend
        ];
        
        return Inertia::render('ContactUs', [
            'contactPage' => $pageData
        ]);
    }
}
