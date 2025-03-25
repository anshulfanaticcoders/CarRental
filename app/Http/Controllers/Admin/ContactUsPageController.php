<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactUsPage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class ContactUsPageController extends Controller
{
    /**
     * Display the contact us page content
     */
    public function index()
    {
        $contactPage = ContactUsPage::first() ?? new ContactUsPage();
        
        return Inertia::render('AdminDashboardPages/ContactUs/Index', [
            'contactPage' => $contactPage
        ]);
    }

    /**
     * Show the form for editing the contact us page
     */
    public function edit()
    {
        $contactPage = ContactUsPage::first() ?? new ContactUsPage();
        
        return Inertia::render('AdminDashboardPages/ContactUs/Edit', [
            'contactPage' => $contactPage
        ]);
    }

    /**
     * Update the contact us page content
     */
    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'hero_title' => 'nullable|string|max:255',
            'hero_description' => 'nullable|string',
            'hero_image' => 'nullable|file|image|max:5120', // 5MB max
            'contact_points' => 'nullable|array',
            'intro_text' => 'nullable|string',
            'phone_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500'
        ]);

        // Find or create a new record
        $contactPage = ContactUsPage::first() ?? new ContactUsPage();

        // Handle hero image upload to UpCloud
        if ($request->hasFile('hero_image')) {
            // Delete existing image if it exists
            if ($contactPage->hero_image_url) {
                Storage::disk('upcloud')->delete(
                    str_replace(Storage::disk('upcloud')->url(''), '', $contactPage->hero_image_url)
                );
            }

            // Store new image
            $path = $request->file('hero_image')->store('contact-us-images', 'upcloud');
            $url = Storage::disk('upcloud')->url($path);

            $validatedData['hero_image_url'] = $url;
        }

        // Update other fields
        $contactPage->fill($validatedData);
        $contactPage->save();

        return redirect()->route('admin.contact-us.index')
            ->with('success', 'Contact Us page updated successfully');
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
        $contactPage = ContactUsPage::first() ?? new ContactUsPage();
        
        return Inertia::render('ContactUs', [
            'contactPage' => $contactPage
        ]);
    }
}