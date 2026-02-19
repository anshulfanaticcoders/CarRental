<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogTag;
use App\Models\Testimonial;
use App\Models\VehicleCategory;
use App\Models\PopularPlace;
use App\Models\Faq; // Added Faq model
use App\Models\SeoMeta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Helpers\SchemaBuilder; // Added for Schema
use App\Helpers\ImageCompressionHelper;
use App\Helpers\LocaleHelper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; // Added for slug generation
use Illuminate\Support\Facades\App; // Added for locale access
use Illuminate\Support\Facades\Route; // For Route::has()
use Illuminate\Support\Facades\Log; // Added for logging
use Illuminate\Foundation\Application; // For Application::VERSION
use Stevebauman\Location\Facades\Location;
use Illuminate\Support\Facades\DB;
use App\Services\Seo\SeoMetaResolver;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        // The Blog model's title accessor will handle the current locale
        $search = $request->input('search');
        $query = Blog::query();

        if ($search) {
            // Search in translation
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            })->orWhere('slug', 'like', "%{$search}%");
        }

        $blogs = $query->latest()->paginate(6)->withQueryString();

        // The $blogs collection will automatically use the title accessor for the current locale
        return Inertia::render('AdminDashboardPages/Blogs/Index', [
            'blogs' => $blogs,
            'filters' => ['search' => $search],
        ]);
    }

    public function create()
    {
        return Inertia::render('AdminDashboardPages/Blogs/Create', [
            'available_locales' => config('app.available_locales', ['en']),
            'current_locale' => App::getLocale(),
            'allTags' => \App\Models\BlogTag::orderBy('name')->pluck('name')->toArray(),
        ]);
    }

    public function store(Request $request)
    {
        $available_locales = config('app.available_locales', ['en']);
        $primaryLocale = 'en';
        if (!in_array($primaryLocale, $available_locales, true)) {
            $primaryLocale = $available_locales[0] ?? 'en';
        }

        $validationRules = [
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'is_published' => 'sometimes|boolean',
            'countries' => 'array|min:0',
            'countries.*' => 'nullable|string|size:2',
            'canonical_country' => 'nullable|string|size:2',
            'translations' => 'required|array',
            // SEO Meta Validation Rules
            'seo_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'keywords' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|url|max:255',
        ];

        // Only primary locale is required; other locales are optional.
        $validationRules["translations.{$primaryLocale}.title"] = 'required|string|max:255';
        $validationRules["translations.{$primaryLocale}.slug"] = 'nullable|string|max:255';
        $validationRules["translations.{$primaryLocale}.content"] = 'required|string';

        foreach ($available_locales as $locale) {
            if ($locale === $primaryLocale) {
                continue;
            }
            $validationRules["translations.{$locale}.title"] = 'nullable|string|max:255';
            $validationRules["translations.{$locale}.slug"] = 'nullable|string|max:255';
            $validationRules["translations.{$locale}.content"] = 'nullable|string';
        }
        $request->validate($validationRules);

        $translationsData = (array) $request->input('translations', []);

        // Additional validation: if a non-primary locale is started, require title+content.
        $translationErrors = [];
        foreach ($available_locales as $locale) {
            $t = isset($translationsData[$locale]) && is_array($translationsData[$locale]) ? $translationsData[$locale] : [];
            $title = isset($t['title']) ? trim((string) $t['title']) : '';
            $content = isset($t['content']) ? trim((string) $t['content']) : '';
            $slug = isset($t['slug']) ? trim((string) $t['slug']) : '';

            $hasAny = ($title !== '' || $content !== '' || $slug !== '');
            if (!$hasAny) {
                continue;
            }

            if ($locale !== $primaryLocale) {
                if ($title === '') {
                    $translationErrors["translations.{$locale}.title"] = "Title is required when adding a {$locale} translation.";
                }
                if ($content === '') {
                    $translationErrors["translations.{$locale}.content"] = "Content is required when adding a {$locale} translation.";
                }
            }

            // Generate slug if missing (admin can still override).
            if ($slug === '' && $title !== '') {
                $translationsData[$locale]['slug'] = Str::slug($title);
            }
        }

        if (!empty($translationErrors)) {
            throw ValidationException::withMessages($translationErrors);
        }

        // Generate main internal slug from primary locale title.
        $slugTitle = (string) ($translationsData[$primaryLocale]['title'] ?? '');
        $mainSlug = Str::slug($slugTitle);
        $existingBlog = Blog::where('slug', $mainSlug)->first();
        if ($existingBlog) {
            $mainSlug = $mainSlug . '-' . uniqid();
        }

        $countries = $request->input('countries', []);
        // Default to US if no countries selected
        if (empty($countries)) {
            $countries = ['us'];
        }

        $countries = array_values(array_filter(array_map(static fn ($c) => strtolower((string) $c), $countries)));
        if (empty($countries)) {
            $countries = ['us'];
        }

        $requestedCanonical = $request->input('canonical_country');
        $requestedCanonical = is_string($requestedCanonical) ? strtolower(trim($requestedCanonical)) : null;
        $canonicalCountry = ($requestedCanonical && in_array($requestedCanonical, $countries, true))
            ? $requestedCanonical
            : $countries[0];

        $blogData = [
            'slug' => $mainSlug,
            'is_published' => $request->input('is_published', true),
            'countries' => $countries,
            // SEO canonicalization: index a single canonical country per blog.
            'canonical_country' => $canonicalCountry,
        ];

        if ($request->hasFile('image')) {
            $folderName = 'blogs';
            $compressedImageUrl = ImageCompressionHelper::compressImage(
                $request->file('image'),
                $folderName,
                quality: 80, // Adjust quality as needed (0-100)
                maxWidth: 1200, // Optional: Set max width for blog images
                maxHeight: 800 // Optional: Set max height for blog images
            );

            if ($compressedImageUrl) {
                $blogData['image'] = Storage::disk('upcloud')->url($compressedImageUrl);
            } else {
                // Handle compression failure, e.g., log error or return an error response
                return back()->withErrors(['image' => 'Failed to compress image.']);
            }
        }

        $blog = Blog::create($blogData);

        foreach ($translationsData as $locale => $data) {
            if (!in_array($locale, $available_locales, true) || !is_array($data)) {
                continue;
            }

            $title = isset($data['title']) ? trim((string) $data['title']) : '';
            $content = isset($data['content']) ? trim((string) $data['content']) : '';
            if ($title !== '' && $content !== '') {
                $slugValue = isset($data['slug']) ? trim((string) $data['slug']) : '';
                if ($slugValue === '') {
                    $slugValue = Str::slug($title);
                }
                if ($slugValue === '') {
                    $slugValue = strtolower($locale) . '-' . uniqid();
                }

                $blog->translations()->create([
                    'locale' => $locale,
                    'title' => $title,
                    'slug' => Str::slug($slugValue),
                    'content' => $content,
                    'excerpt' => $data['excerpt'] ?? null,
                ]);
            }
        }

        // Create or update SEO meta bound to this blog (not slug-based).
        $primaryTitleForSeo = (string) (($translationsData['en']['title'] ?? null) ?: ($translationsData[$primaryLocale]['title'] ?? null) ?: $slugTitle);
        $seoImageUrl = $blog->image ?: null;
        $seoMetaToSave = [
            'seo_title' => $request->input('seo_title') ?: $primaryTitleForSeo,
            'meta_description' => $request->input('meta_description'),
            'keywords' => $request->input('keywords'),
            'canonical_url' => $request->input('canonical_url'),
            'seo_image_url' => $seoImageUrl,
        ];

        $seoMeta = SeoMeta::query()
            ->with('translations')
            ->where(function ($q) use ($blog) {
                $q->where('seoable_type', $blog->getMorphClass())
                    ->where('seoable_id', $blog->getKey());
            })
            ->orWhere('url_slug', 'blog/' . $blog->slug)
            ->first();

        if (! $seoMeta) {
            $seoMeta = new SeoMeta();
        }

        $seoMeta->forceFill(array_filter($seoMetaToSave, fn ($v) => !is_null($v) && $v !== ''));
        $seoMeta->seoable_type = $blog->getMorphClass();
        $seoMeta->seoable_id = $blog->getKey();
        $seoMeta->save();

        $seoTranslationsData = $request->input('seo_translations', []);
        foreach ($available_locales as $locale) {
            if (! isset($seoTranslationsData[$locale])) {
                continue;
            }

            $translationInput = $seoTranslationsData[$locale];
            $request->validate([
                "seo_translations.{$locale}.seo_title" => 'nullable|string|max:60',
                "seo_translations.{$locale}.meta_description" => 'nullable|string|max:160',
                "seo_translations.{$locale}.keywords" => 'nullable|string|max:255',
            ]);

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

        // Handle tags
        $tagNames = $request->input('tags', []);
        if (!empty($tagNames)) {
            $tagIds = [];
            foreach ($tagNames as $tagName) {
                $tagName = trim($tagName);
                if ($tagName) {
                    $tag = \App\Models\BlogTag::firstOrCreate(
                        ['slug' => Str::slug($tagName)],
                        ['name' => $tagName]
                    );
                    $tagIds[] = $tag->id;
                }
            }
            $blog->tags()->sync($tagIds);
        }

        // Make changes visible immediately on the frontend.
        foreach ($available_locales as $locale) {
            Cache::forget('seo:model:' . get_class($blog) . ':' . $blog->getKey() . ':' . $locale);
        }

        return redirect()->route('admin.blogs.index', ['locale' => App::getLocale()])->with('success', 'Blog created successfully.');
    }

    public function edit(Blog $blog)
    {
        $translations = $blog->translations->keyBy('locale');
        $allLocales = config('app.available_locales', ['en']);

        $blogData = [
            'id' => $blog->id,
            'slug' => $blog->slug,
            'image' => $blog->image,
            'is_published' => $blog->is_published,
            'countries' => $blog->countries ?? ['us'], // Include countries, default to US
            'canonical_country' => $blog->canonical_country ?? (($blog->countries[0] ?? null) ?: 'us'),
            'translations' => [],
        ];

        foreach ($allLocales as $locale) {
            $translation = $translations->get($locale);
            $blogData['translations'][$locale] = [
                'title' => $translation ? $translation->title : '',
                'slug' => $translation ? $translation->slug : '',
                'content' => $translation ? $translation->content : '',
            ];
        }

        $seoMeta = SeoMeta::with('translations')
            ->where(function ($q) use ($blog) {
                $q->where('seoable_type', $blog->getMorphClass())
                    ->where('seoable_id', $blog->getKey());
            })
            ->orWhere('url_slug', 'blog/' . $blog->slug)
            ->first();

        $seoTranslations = [];
        if ($seoMeta) {
            foreach ($allLocales as $locale) {
                $translation = $seoMeta->translations->firstWhere('locale', $locale);
                $seoTranslations[$locale] = [
                    'seo_title' => $translation->seo_title ?? null,
                    'meta_description' => $translation->meta_description ?? null,
                    'keywords' => $translation->keywords ?? null,
                ];
            }
        }

        return Inertia::render('AdminDashboardPages/Blogs/Edit', [
            'blog' => $blogData,
            'available_locales' => $allLocales,
            'current_locale' => App::getLocale(),
            'seoMeta' => $seoMeta,
            'seoTranslations' => $seoTranslations,
            'tags' => $blog->tags->pluck('name')->toArray(),
            'allTags' => \App\Models\BlogTag::orderBy('name')->pluck('name')->toArray(),
        ]);
    }

    public function update(Request $request, Blog $blog)
    {
        $available_locales = config('app.available_locales', ['en']);
        $primaryLocale = 'en';
        if (!in_array($primaryLocale, $available_locales, true)) {
            $primaryLocale = $available_locales[0] ?? 'en';
        }

        $validationRules = [
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'is_published' => 'sometimes|boolean',
            'countries' => 'array|min:0',
            'countries.*' => 'nullable|string|size:2',
            'canonical_country' => 'nullable|string|size:2',
            'translations' => 'required|array',
            // SEO Meta Validation Rules
            'seo_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'keywords' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|url|max:255',
        ];

        // Only primary locale is required; other locales are optional.
        $validationRules["translations.{$primaryLocale}.title"] = 'required|string|max:255';
        $validationRules["translations.{$primaryLocale}.slug"] = 'nullable|string|max:255';
        $validationRules["translations.{$primaryLocale}.content"] = 'required|string';

        foreach ($available_locales as $locale) {
            if ($locale === $primaryLocale) {
                continue;
            }
            $validationRules["translations.{$locale}.title"] = 'nullable|string|max:255';
            $validationRules["translations.{$locale}.slug"] = 'nullable|string|max:255';
            $validationRules["translations.{$locale}.content"] = 'nullable|string';
        }
        $request->validate($validationRules);

        $translationsData = (array) $request->input('translations', []);

        // Additional validation: if a non-primary locale is started, require title+content.
        $translationErrors = [];
        foreach ($available_locales as $locale) {
            $t = isset($translationsData[$locale]) && is_array($translationsData[$locale]) ? $translationsData[$locale] : [];
            $title = isset($t['title']) ? trim((string) $t['title']) : '';
            $content = isset($t['content']) ? trim((string) $t['content']) : '';
            $slug = isset($t['slug']) ? trim((string) $t['slug']) : '';

            $hasAny = ($title !== '' || $content !== '' || $slug !== '');
            if (!$hasAny) {
                continue;
            }

            if ($locale !== $primaryLocale) {
                if ($title === '') {
                    $translationErrors["translations.{$locale}.title"] = "Title is required when adding a {$locale} translation.";
                }
                if ($content === '') {
                    $translationErrors["translations.{$locale}.content"] = "Content is required when adding a {$locale} translation.";
                }
            }

            if ($slug === '' && $title !== '') {
                $translationsData[$locale]['slug'] = Str::slug($title);
            }
        }

        if (!empty($translationErrors)) {
            throw ValidationException::withMessages($translationErrors);
        }

        $countries = $request->input('countries', []);
        // Default to US if no countries selected
        if (empty($countries)) {
            $countries = ['us'];
        }

        $countries = array_values(array_filter(array_map(static fn ($c) => strtolower((string) $c), $countries)));
        if (empty($countries)) {
            $countries = ['us'];
        }

        $requestedCanonical = $request->input('canonical_country');
        $requestedCanonical = is_string($requestedCanonical) ? strtolower(trim($requestedCanonical)) : null;
        $existingCanonical = is_string($blog->canonical_country) ? strtolower($blog->canonical_country) : null;
        $canonicalCountry = $requestedCanonical && in_array($requestedCanonical, $countries, true)
            ? $requestedCanonical
            : (($existingCanonical && in_array($existingCanonical, $countries, true)) ? $existingCanonical : $countries[0]);

        $blogData = [
            'is_published' => $request->input('is_published', $blog->is_published),
            'countries' => $countries,
            'canonical_country' => $canonicalCountry,
        ];

        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($blog->image) {
                $oldImagePath = str_replace(Storage::disk('upcloud')->url(''), '', $blog->image);
                if ($oldImagePath) {
                    Storage::disk('upcloud')->delete($oldImagePath);
                }
            }

            $folderName = 'blogs';
            $compressedImageUrl = ImageCompressionHelper::compressImage(
                $request->file('image'),
                $folderName,
                quality: 80, // Adjust quality as needed (0-100)
                maxWidth: 1200, // Optional: Set max width for blog images
                maxHeight: 800 // Optional: Set max height for blog images
            );

            if ($compressedImageUrl) {
                $blogData['image'] = Storage::disk('upcloud')->url($compressedImageUrl);
            } else {
                // Handle compression failure, e.g., log error or return an error response
                return back()->withErrors(['image' => 'Failed to compress image.']);
            }
        }

        // Keep internal/admin slug in sync with EN title (does not affect public blog URL).
        $enTitleForSlug = isset($translationsData['en']['title']) ? trim((string) $translationsData['en']['title']) : '';
        if ($enTitleForSlug !== '') {
            $newMainSlug = Str::slug($enTitleForSlug);
            if ($newMainSlug !== '' && $newMainSlug !== $blog->slug) {
                $existingBlog = Blog::where('slug', $newMainSlug)->where('id', '!=', $blog->id)->first();
                if ($existingBlog) {
                    $newMainSlug = $newMainSlug . '-' . uniqid();
                }
                $blogData['slug'] = $newMainSlug;
            }
        }

        $blog->update($blogData);

        // Upsert translations (optional locales).
        foreach ($translationsData as $locale => $data) {
            if (!in_array($locale, $available_locales, true) || !is_array($data)) {
                continue;
            }

            $title = isset($data['title']) ? trim((string) $data['title']) : '';
            $content = isset($data['content']) ? trim((string) $data['content']) : '';
            if ($title === '' || $content === '') {
                continue;
            }

            $slugValue = isset($data['slug']) ? trim((string) $data['slug']) : '';
            if ($slugValue === '') {
                $slugValue = Str::slug($title);
            }
            if ($slugValue === '') {
                $slugValue = strtolower($locale) . '-' . uniqid();
            }

            $blog->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'title' => $title,
                    'slug' => Str::slug($slugValue),
                    'content' => $content,
                    'excerpt' => $data['excerpt'] ?? null,
                ]
            );
        }

        // Handle tags
        $tagNames = $request->input('tags', []);
        $tagIds = [];
        foreach ($tagNames as $tagName) {
            $tagName = trim($tagName);
            if ($tagName) {
                $tag = \App\Models\BlogTag::firstOrCreate(
                    ['slug' => Str::slug($tagName)],
                    ['name' => $tagName]
                );
                $tagIds[] = $tag->id;
            }
        }
        $blog->tags()->sync($tagIds);

        // SEO meta bound to this blog (not slug-based).
        $primaryTitleForSeo = (string) (($translationsData['en']['title'] ?? null) ?: ($translationsData[$primaryLocale]['title'] ?? null) ?: '');
        $seoImageUrl = $blog->image ?: null;
        $seoMetaToSave = [
            'seo_title' => $request->input('seo_title') ?: $primaryTitleForSeo,
            'meta_description' => $request->input('meta_description'),
            'keywords' => $request->input('keywords'),
            'canonical_url' => $request->input('canonical_url'),
            'seo_image_url' => $seoImageUrl,
        ];

        $seoMeta = SeoMeta::query()
            ->with('translations')
            ->where(function ($q) use ($blog) {
                $q->where('seoable_type', $blog->getMorphClass())
                    ->where('seoable_id', $blog->getKey());
            })
            ->orWhere('url_slug', 'blog/' . $blog->slug)
            ->first();

        if (! $seoMeta) {
            $seoMeta = new SeoMeta();
        }

        $seoMeta->forceFill(array_filter($seoMetaToSave, fn ($v) => !is_null($v) && $v !== ''));
        $seoMeta->seoable_type = $blog->getMorphClass();
        $seoMeta->seoable_id = $blog->getKey();
        $seoMeta->save();

        $seoTranslationsData = $request->input('seo_translations', []);
        foreach ($available_locales as $locale) {
            if (! isset($seoTranslationsData[$locale])) {
                continue;
            }
            $translationInput = $seoTranslationsData[$locale];
            $request->validate([
                "seo_translations.{$locale}.seo_title" => 'nullable|string|max:60',
                "seo_translations.{$locale}.meta_description" => 'nullable|string|max:160',
                "seo_translations.{$locale}.keywords" => 'nullable|string|max:255',
            ]);

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

        foreach ($available_locales as $locale) {
            Cache::forget('seo:model:' . get_class($blog) . ':' . $blog->getKey() . ':' . $locale);
        }


        return redirect()->route('admin.blogs.index', ['locale' => App::getLocale()])->with('success', 'Blog updated successfully.');
    }

    public function destroy(Blog $blog)
    {
        $available_locales = config('app.available_locales', ['en']);

        if ($blog->image) {
            $oldImagePath = str_replace(Storage::disk('upcloud')->url(''), '', $blog->image);
            Storage::disk('upcloud')->delete($oldImagePath);
        }

        // Delete associated SEO Meta
        SeoMeta::where('seoable_type', $blog->getMorphClass())
            ->where('seoable_id', $blog->getKey())
            ->delete();

        // Backward compatibility: clean up old slug-keyed records
        SeoMeta::where('url_slug', 'blog/' . $blog->slug)->delete();
        SeoMeta::where('url_slug', $blog->slug)->delete();

        $blog->delete();

        foreach ($available_locales as $locale) {
            Cache::forget('seo:model:' . get_class($blog) . ':' . $blog->getKey() . ':' . $locale);
        }

        return redirect()->route('admin.blogs.index', ['locale' => App::getLocale()])->with('success', 'Blog deleted successfully.');
    }

    public function togglePublish(Request $request, Blog $blog)
    {
        $blog->is_published = !$blog->is_published;
        // If you have a 'published_at' timestamp, you might want to update it here:
        // if ($blog->is_published && is_null($blog->published_at)) {
        //     $blog->published_at = now();
        // } elseif (!$blog->is_published) {
        //     // Option 1: Clear published_at when unpublishing
        //     // $blog->published_at = null; 
        //     // Option 2: Keep published_at as the date it was first published (do nothing here)
        // }
        $blog->save();

        return redirect()->back()->with('success', 'Blog publish status updated successfully.');
    }

    public function homeBlogs()
    {
        // Always get country from session (set by middleware)
        $currentCountry = session('country');
        \Log::info('homeBlogs: Session country - ' . ($currentCountry ?: 'NULL') . ' | Full session: ' . json_encode(session()->all()));

        // If no country detected, don't show any blogs (this will show the problem)
        if (!$currentCountry) {
            \Log::error('homeBlogs: No country detected - cannot filter blogs');
            $blogs = collect(); // Empty collection
        } else {
            // Filter blogs by country
            $blogs = Blog::with('translations')
                ->where('is_published', true)
                ->where(function ($query) use ($currentCountry) {
                    $query->whereJsonContains('countries', $currentCountry)
                        ->orWhereNull('countries');
                })
                ->latest()
                ->take(4)
                ->get();

            \Log::info('homeBlogs: Found ' . $blogs->count() . ' blogs for country ' . $currentCountry);
        }

        $blogListSchema = [];
        if ($blogs->isNotEmpty()) {
            $blogListSchema = SchemaBuilder::blogList($blogs, 'Latest Blog Posts', $currentCountry);
        }

        // Fetch latest 5 testimonials
        $testimonials = Testimonial::latest()->take(5)->get();
        $testimonialListSchema = [];
        if ($testimonials->isNotEmpty()) {
            $testimonialListSchema = SchemaBuilder::testimonialList($testimonials, 'Customer Testimonials');
        }

        // Combine schemas into an array
        // The Welcome.vue component will need to handle an array of schemas
        $pageSchemas = [];
        if (!empty($blogListSchema)) {
            $pageSchemas[] = $blogListSchema;
        }
        if (!empty($testimonialListSchema)) {
            $pageSchemas[] = $testimonialListSchema;
        }

        // Fetch vehicle categories (e.g., all active ones, or a specific number)
        $categories = VehicleCategory::where('status', true)->get(); // Assuming 'status' field indicates active
        $categoryListSchema = [];
        if ($categories->isNotEmpty()) {
            $categoryListSchema = SchemaBuilder::vehicleCategoryList($categories, 'Our Vehicle Categories');
            if (!empty($categoryListSchema)) {
                $pageSchemas[] = $categoryListSchema;
            }
        }

        // Fetch popular places (e.g., all of them, or a specific number)
        $popularPlaces = PopularPlace::all(); // You might want to limit this, e.g., ->take(10)->get()
        $popularPlaceListSchema = [];
        if ($popularPlaces->isNotEmpty()) {
            $popularPlaceListSchema = SchemaBuilder::popularPlaceList($popularPlaces, 'Popular Destinations');
            if (!empty($popularPlaceListSchema)) {
                $pageSchemas[] = $popularPlaceListSchema;
            }
        }

        // Fetch FAQs (e.g., all of them, or a limited number for the homepage)
        $faqs = Faq::with('translations')->get(); // Fetches all FAQs with their translations
        // The accessors 'question' and 'answer' will provide localized values.
        // SchemaBuilder::faqPage expects an array of ['question' => ..., 'answer' => ...]
        $faqPageSchema = [];
        if ($faqs->isNotEmpty()) {
            $faqItems = $faqs->map(function ($faq) {
                return ['question' => $faq->question, 'answer' => $faq->answer];
            })->all();
            $faqPageSchema = SchemaBuilder::faqPage($faqItems);
            if (!empty($faqPageSchema)) {
                $pageSchemas[] = $faqPageSchema;
            }
        }

        $seo = app(SeoMetaResolver::class)->resolveForRoute(
            'welcome',
            [],
            App::getLocale(),
            route('welcome', ['locale' => App::getLocale()])
        )->toArray();
        $pages = \App\Models\Page::with('translations')->get()->keyBy('slug');

        // Fetch Hero Image from Settings
        $heroImage = DB::table('homepage_settings')->where('key', 'hero_image')->value('value');

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'blogs' => LocaleHelper::sanitizeUtf8($blogs),
            'testimonials' => LocaleHelper::sanitizeUtf8($testimonials),
            'categories' => LocaleHelper::sanitizeUtf8($categories),
            'popularPlaces' => LocaleHelper::sanitizeUtf8($popularPlaces),
            'faqs' => LocaleHelper::sanitizeUtf8($faqs), // Pass FAQ data
            'schema' => LocaleHelper::sanitizeUtf8($pageSchemas),
            'seo' => LocaleHelper::sanitizeUtf8($seo),
            'pages' => LocaleHelper::sanitizeUtf8($pages),
            'country' => session('country'),
            'heroImage' => $heroImage,
        ]);
    }


    // public function show($locale, $slug)
    // {
    //     App::setLocale($locale);

    //     $translation = \App\Models\BlogTranslation::where('slug', $slug)->where('locale', $locale)->firstOrFail();
    //     $blog = $translation->blog;

    //     if (!$blog->is_published && !(auth()->check() && auth()->user()->hasRole('admin'))) {
    //          abort(404);
    //     }
    //     $blog->load('translations'); // Eager load translations for the single blog

    //     // Generate BlogPosting schema
    //     $blogSchema = SchemaBuilder::blog($blog);

    //     $seoMeta = null;
    //     $seoMetaTranslation = \App\Models\SeoMetaTranslation::where('url_slug', $slug)->where('locale', $locale)->first();

    //     if ($seoMetaTranslation) {
    //         $seoMeta = SeoMeta::with('translations')->find($seoMetaTranslation->seo_meta_id);
    //     }


    //     $pages = \App\Models\Page::with('translations')->get()->keyBy('slug');

    //     return Inertia::render('SingleBlog', [
    //         'blog' => $blog,
    //         'schema' => $blogSchema, // Pass schema to the Vue component
    //         'seoMeta' => $seoMeta,
    //         'locale' => $locale,
    //         'pages' => $pages,
    //     ]);
    // }

    // public function showBlogPage(Request $request)
    // {
    //     $blogs = Blog::with('translations')->where('is_published', true)->latest()->paginate(9);
    //     $pages = \App\Models\Page::with('translations')->get()->keyBy('slug');

    //     // Fetch SEO meta for the blog page (assuming its url_slug is '/blog')
    //     $seoMeta = SeoMeta::with('translations')->where('url_slug', '/blog')->first();

    //     return Inertia::render('BlogPage', [
    //         'blogs' => $blogs,
    //         'pages' => $pages,
    //         'seoMeta' => $seoMeta, // Pass SEO meta to the component
    //         'locale' => App::getLocale(), // Pass current locale
    //     ]);
    // }


    public function showBlogPage(Request $request, $locale, $country)
    {
        $country = strtolower($country);

        // Store the country in session for future use
        session(['country' => $country]);
        \Log::info('showBlogPage: Country stored in session - ' . $country);

        $blogsQuery = Blog::with(['translations', 'tags:id,name,slug'])
            ->where('is_published', true)
            ->where(function ($q) use ($country) {
                $q->whereJsonContains('countries', $country)
                    ->orWhereNull('countries');
            });

        // Filter by tag if provided
        if ($request->filled('tag')) {
            $blogsQuery->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->input('tag'));
            });
        }

        $blogs = $blogsQuery->latest()->paginate(9)->withQueryString();

        // Get all tags that have at least one published blog
        $tags = BlogTag::whereHas('blogs', function ($q) use ($country) {
            $q->where('is_published', true)
                ->where(function ($sq) use ($country) {
                    $sq->whereJsonContains('countries', $country)
                        ->orWhereNull('countries');
                });
        })->orderBy('name')->get(['id', 'name', 'slug']);

        $pages = \App\Models\Page::with('translations')->get()->keyBy('slug');

        $seo = app(SeoMetaResolver::class)->resolveForRoute(
            'blog',
            ['country' => $country],
            App::getLocale(),
            url("/{$locale}/{$country}/blog")
        )->toArray();

        return Inertia::render('BlogPage', [
            'blogs' => $blogs,
            'pages' => $pages,
            'locale' => App::getLocale(),
            'country' => $country,
            'seo' => $seo,
            'tags' => $tags,
            'activeTag' => $request->input('tag', ''),
        ]);
    }

    public function show($locale, $country, $slug)
    {
        App::setLocale($locale);
        $country = strtolower($country);

        // Store the country in session for future use
        session(['country' => $country]);
        \Log::info('SingleBlog show: Country stored in session - ' . $country);

        // Fetch blog with country filter in query
        $blog = \App\Models\Blog::with('translations')
            ->where(function ($q) use ($country) {
                $q->whereJsonContains('countries', $country)
                    ->orWhereNull('countries');
            })
            ->whereHas('translations', function ($q) use ($slug, $locale) {
                $q->where('slug', $slug)
                    ->where('locale', $locale);
            })
            ->firstOrFail();

        // Check if published or admin
        if (!$blog->is_published && !(auth()->check() && auth()->user()->hasRole('admin'))) {
            abort(404);
        }

        $blog->load('translations');

        $canonicalCountry = $blog->canonical_country;
        if (!is_string($canonicalCountry) || trim($canonicalCountry) === '') {
            $canonicalCountry = is_array($blog->countries) && !empty($blog->countries) ? strtolower((string) $blog->countries[0]) : 'us';
        }
        $canonicalCountry = strtolower((string) $canonicalCountry);

        if (is_array($blog->countries) && !empty($blog->countries) && !in_array($canonicalCountry, array_map('strtolower', $blog->countries), true)) {
            $canonicalCountry = strtolower((string) $blog->countries[0]);
        }

        // Single-canonical policy: redirect non-canonical country URLs to canonical.
        if ($canonicalCountry !== $country) {
            return redirect()->to("/{$locale}/{$canonicalCountry}/blog/{$slug}", 301);
        }

        // Generate schema
        $blogSchema = SchemaBuilder::blog($blog);

        $seo = app(SeoMetaResolver::class)->resolveForModel(
            $blog,
            $locale,
            url("/{$locale}/{$canonicalCountry}/blog/{$slug}")
        )->toArray();

        $pages = \App\Models\Page::with('translations')->get()->keyBy('slug');

        // Compute reading time and get related blogs
        $readingTime = $this->calculateReadingTime($translation->content ?? '');
        $relatedBlogs = $this->getRelatedBlogs($blog, $locale, $country);

        return Inertia::render('SingleBlog', [
            'blog' => array_merge($blog->toArray(), [
                'excerpt' => $translation->excerpt ?? null,
            ]),
            'schema' => $blogSchema,
            'seo' => $seo,
            'locale' => $locale,
            'country' => $country,
            'pages' => $pages,
            'tags' => $blog->tags->map(fn($t) => ['name' => $t->name, 'slug' => $t->slug]),
            'relatedBlogs' => $relatedBlogs,
            'readingTime' => $readingTime,
        ]);
    }


    public function getRecentBlogs(Request $request) // Added Request
    {
        // If a locale is passed in the request, set it for this request's lifecycle
        // This helps the Blog model's accessors pick up the correct language
        if ($request->has('locale') && in_array($request->input('locale'), ['en', 'fr', 'nl', 'es', 'ar'])) { // Validate locale
            App::setLocale($request->input('locale'));
        }

        // Prioritize country from request, fallback to session
        $country = $request->input('country', session('country'));
        if ($country) {
            $country = strtolower($country);
        }

        \Log::info('getRecentBlogs: Using country - ' . $country . ' | Request country param: ' . $request->input('country'));

        $query = Blog::with('translations')
            ->where('is_published', true);

        if ($country) {
            $query->where(function ($query) use ($country) {
                $query->whereJsonContains('countries', $country)
                    ->orWhereNull('countries');
            });
        }

        $recentBlogs = $query->latest()->offset(1)->take(5)->get();


        \Log::info('getRecentBlogs: Found ' . $recentBlogs->count() . ' recent blogs for country ' . $country);

        return response()->json($recentBlogs);
    }

    private function calculateReadingTime(string $content): int
    {
        $text = strip_tags($content);
        $wordCount = str_word_count($text);
        return max(1, (int) ceil($wordCount / 200));
    }

    private function getRelatedBlogs(Blog $blog, string $locale, string $country): array
    {
        $tagIds = $blog->tags->pluck('id')->toArray();

        $query = Blog::where('id', '!=', $blog->id)
            ->where('is_published', true)
            ->with(['translations' => function ($q) use ($locale) {
                $q->where('locale', $locale);
            }]);

        if (!empty($tagIds)) {
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('blog_tags.id', $tagIds);
            });
        }

        $related = $query->latest()->limit(3)->get();

        if ($related->count() < 3) {
            $existingIds = $related->pluck('id')->merge([$blog->id])->toArray();
            $more = Blog::whereNotIn('id', $existingIds)
                ->where('is_published', true)
                ->with(['translations' => fn($q) => $q->where('locale', $locale)])
                ->latest()
                ->limit(3 - $related->count())
                ->get();
            $related = $related->merge($more);
        }

        return $related->map(function ($b) use ($locale) {
            $t = $b->translations->first();
            return [
                'title' => $t?->title ?? '',
                'slug' => $t?->slug ?? $b->slug,
                'image' => $b->image,
                'date' => $b->created_at?->format('M j, Y') ?? '',
            ];
        })->toArray();
    }
}
