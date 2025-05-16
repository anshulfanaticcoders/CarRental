<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\App; // Added for locale access

class FaqController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $query = Faq::query()->with('translations'); // Eager load translations

        if ($search) {
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                  ->orWhere('answer', 'like', "%{$search}%");
            });
        }
        
        $faqs = $query->latest()->get();

        // The Faq model's accessors will handle the current locale for question/answer
        return Inertia::render('AdminDashboardPages/Settings/Faq/Index', [
            'faqs' => $faqs,
            'search' => $search,
            'available_locales' => ['en', 'fr', 'nl'], // Or from config
            'current_locale' => App::getLocale(),
        ]);
    }

    public function store(Request $request)
    {
        $available_locales = ['en', 'fr', 'nl']; // Or from config
        $validationRules = [
            'translations' => 'required|array',
        ];
        foreach ($available_locales as $locale) {
            $validationRules["translations.{$locale}.question"] = 'required|string|max:255';
            $validationRules["translations.{$locale}.answer"] = 'required|string';
        }
        $request->validate($validationRules);

        $faq = Faq::create([]); // Create the main FAQ entry (it has no direct fields now)

        $translationsData = $request->input('translations');
        foreach ($translationsData as $locale => $data) {
            if (in_array($locale, $available_locales) && !empty($data['question']) && !empty($data['answer'])) {
                 $faq->translations()->create([
                    'locale' => $locale,
                    'question' => $data['question'],
                    'answer' => $data['answer'],
                ]);
            }
        }
        return redirect()->route('admin.settings.faq.index')->with('success', 'FAQ created successfully.');
    }

    public function update(Request $request, Faq $faq)
    {
        $available_locales = ['en', 'fr', 'nl']; // Or from config
        $validationRules = [
            'translations' => 'required|array',
        ];
        foreach ($available_locales as $locale) {
            $validationRules["translations.{$locale}.question"] = 'required|string|max:255';
            $validationRules["translations.{$locale}.answer"] = 'required|string';
        }
        $request->validate($validationRules);

        // $faq->update([]); // No direct fields to update on Faq model itself

        $translationsData = $request->input('translations');
        foreach ($translationsData as $locale => $data) {
            if (in_array($locale, $available_locales) && !empty($data['question']) && !empty($data['answer'])) {
                $faq->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'question' => $data['question'],
                        'answer' => $data['answer'],
                    ]
                );
            }
        }
        return redirect()->route('admin.settings.faq.index')->with('success', 'FAQ updated successfully.');
    }

    public function destroy(Faq $faq)
    {
        // Translations will be deleted by cascade constraint
        $faq->delete();
        return redirect()->route('admin.settings.faq.index')->with('success', 'FAQ deleted successfully.');
    }

    // This method is likely used by the public Faq.vue page
    public function getFaqs(Request $request)
    {
        // If a locale is passed in the request, set it for this request's lifecycle
        if ($request->has('locale') && in_array($request->input('locale'), ['en', 'fr', 'nl'])) {
            App::setLocale($request->input('locale'));
        }
        // Eager load translations. The accessors in Faq model will handle the rest.
        $faqs = Faq::with('translations')->orderBy('created_at', 'asc')->get();
        return response()->json($faqs);
    }


}
