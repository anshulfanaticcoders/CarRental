<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Testimonial;
use App\Models\VehicleCategory;
use App\Models\PopularPlace;
use App\Models\Faq; // Added Faq model
use App\Models\SeoMeta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Helpers\SchemaBuilder; // Added for Schema
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; // Added for slug generation
use Illuminate\Support\Facades\App; // Added for locale access
use Illuminate\Support\Facades\Route; // For Route::has()
use Illuminate\Foundation\Application; // For Application::VERSION

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
                  ->orWhere('content', 'like', "%{$search}%");
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
            'available_locales' => ['en', 'fr', 'nl'], // Or from config
            'current_locale' => App::getLocale(),
        ]);
    }

    public function store(Request $request)
    {
        $available_locales = ['en', 'fr', 'nl']; // Or from config
        $validationRules = [
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'is_published' => 'sometimes|boolean',
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
            $validationRules["translations.{$locale}.content"] = 'required|string';
        }
        $request->validate($validationRules);

        $translationsData = $request->input('translations');
        
        // Generate slug from the 'en' title or the first available title
        $slugTitle = $translationsData['en']['title'] ?? $translationsData[array_key_first($translationsData)]['title'];
        $slug = Str::slug($slugTitle);
        $existingBlog = Blog::where('slug', $slug)->first();
        if ($existingBlog) {
            $slug = $slug . '-' . uniqid();
        }

        $blogData = [
            'slug' => $slug,
            'is_published' => $request->input('is_published', true),
        ];

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('blogs', 'upcloud');
            $blogData['image'] = Storage::disk('upcloud')->url($imagePath);
        }

        $blog = Blog::create($blogData);

        foreach ($translationsData as $locale => $data) {
            if (in_array($locale, $available_locales) && !empty($data['title']) && !empty($data['content'])) {
                 $blog->translations()->create([
                    'locale' => $locale,
                    'title' => $data['title'],
                    'content' => $data['content'],
                ]);
            }
        }

        // Create or Update SEO Meta
        $seoTitleInput = $request->input('seo_title');
        $primaryTitleForSeo = $translationsData['en']['title'] ?? $translationsData[array_key_first($translationsData)]['title'];

        $seoMetaToSave = [
            'seo_title' => !empty($seoTitleInput) ? $seoTitleInput : $primaryTitleForSeo, // Default to blog title if seo_title not provided
            'meta_description' => $request->input('meta_description'),
            'keywords' => $request->input('keywords'),
            'canonical_url' => $request->input('canonical_url'),
            'seo_image_url' => $request->input('seo_image_url'),
        ];
        
        // Only save if there's actual data, or if we want to ensure a record exists even if empty
        // For blogs, it's good to at least have the title.
        $seoUrlSlug = 'blog/' . $blog->slug; // SEO url_slug IS prefixed
        $seoMeta = SeoMeta::updateOrCreate(
            ['url_slug' => $seoUrlSlug],
            array_filter($seoMetaToSave, fn($value) => !is_null($value) && $value !== '') 
        );

        // Handle SEO translations
        $seoTranslationsData = $request->input('seo_translations', []);
        foreach ($available_locales as $locale) {
            if (isset($seoTranslationsData[$locale])) {
                $translationInput = $seoTranslationsData[$locale];
                // Basic validation for translation fields
                $request->validate([
                    "seo_translations.{$locale}.seo_title" => 'nullable|string|max:60',
                    "seo_translations.{$locale}.meta_description" => 'nullable|string|max:160',
                    "seo_translations.{$locale}.keywords" => 'nullable|string|max:255',
                ]);

                $seoMeta->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'seo_title'        => $translationInput['seo_title'] ?? null,
                        'meta_description' => $translationInput['meta_description'] ?? null,
                        'keywords'         => $translationInput['keywords'] ?? null,
                    ]
                );
            }
        }


        return redirect()->route('admin.blogs.index')->with('success', 'Blog created successfully.');
    }

    public function edit(Blog $blog)
    {
        $translations = $blog->translations->keyBy('locale');
        $allLocales = ['en', 'fr', 'nl']; // Or from config
        
        $blogData = [
            'id' => $blog->id,
            'slug' => $blog->slug,
            'image' => $blog->image,
            'is_published' => $blog->is_published,
            'translations' => [],
        ];

        foreach ($allLocales as $locale) {
            $translation = $translations->get($locale);
            $blogData['translations'][$locale] = [
                'title' => $translation ? $translation->title : '',
                'content' => $translation ? $translation->content : '',
            ];
        }
        
        $seoMeta = SeoMeta::with('translations') // Eager load translations
                            ->where('url_slug', 'blog/' . $blog->slug)
                            ->orWhere('url_slug', $blog->slug)
                            ->orderByRaw("CASE WHEN url_slug = ? THEN 0 ELSE 1 END", ['blog/' . $blog->slug])
                            ->first();

        $seoTranslations = [];
        if ($seoMeta) {
            foreach ($allLocales as $locale) {
                $translation = $seoMeta->translations->firstWhere('locale', $locale);
                $seoTranslations[$locale] = [
                    'seo_title'        => $translation->seo_title ?? null,
                    'meta_description' => $translation->meta_description ?? null,
                    'keywords'         => $translation->keywords ?? null,
                ];
            }
        }

        return Inertia::render('AdminDashboardPages/Blogs/Edit', [
            'blog' => $blogData,
            'available_locales' => $allLocales,
            'current_locale' => App::getLocale(), 
            'seoMeta' => $seoMeta,
            'seoTranslations' => $seoTranslations, // Pass SEO translations to the component
        ]);
    }

    public function update(Request $request, Blog $blog)
    {
        $available_locales = ['en', 'fr', 'nl']; // Or from config
        $validationRules = [
            'slug' => 'required|string|max:255|unique:blogs,slug,' . $blog->id,
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'is_published' => 'sometimes|boolean',
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
            $validationRules["translations.{$locale}.content"] = 'required|string';
        }
        $request->validate($validationRules);

        $blogData = [
            'slug' => $request->input('slug'), // Slug can be edited here, ensure it's unique
            'is_published' => $request->input('is_published', $blog->is_published),
        ];

        if ($request->hasFile('image')) {
            if ($blog->image) {
                $oldImagePath = str_replace(Storage::disk('upcloud')->url(''), '', $blog->image);
                if ($oldImagePath) { // Ensure path is not empty
                    Storage::disk('upcloud')->delete($oldImagePath);
                }
            }
            $imagePath = $request->file('image')->store('blogs', 'upcloud');
            $blogData['image'] = Storage::disk('upcloud')->url($imagePath);
        }
        
        $blog->update($blogData);

        $translationsData = $request->input('translations');
        foreach ($translationsData as $locale => $data) {
             if (in_array($locale, $available_locales) && !empty($data['title']) && !empty($data['content'])) {
                $blog->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'title' => $data['title'],
                        'content' => $data['content'],
                    ]
                );
            }
        }

        // Update or Create SEO Meta
        $seoTitleInput = $request->input('seo_title');
        // Use the 'en' title from the submitted translations for default SEO title, or the first available.
        $primaryTitleForSeo = $translationsData['en']['title'] ?? $translationsData[array_key_first($translationsData)]['title'];
        
        $seoMetaToSave = [
            'seo_title' => !empty($seoTitleInput) ? $seoTitleInput : $primaryTitleForSeo,
            'meta_description' => $request->input('meta_description'),
            'keywords' => $request->input('keywords'),
            'canonical_url' => $request->input('canonical_url'),
            'seo_image_url' => $request->input('seo_image_url'),
        ];

        // If the slug was changed, we might need to delete the old SEO meta record if it existed under the old slug.
        // However, the current logic uses $blog->slug which is the *new* slug if it was updated.
        // This means updateOrCreate will correctly target the new slug.
        // If an old SEO record needs to be deleted, that logic would be more complex (store old slug before update).
        // For now, this handles creating/updating SEO for the current slug.
        
        $newSlugFromRequest = $request->input('slug'); // This is the unprefixed blog slug from form
        $newSeoUrlSlug = 'blog/' . $newSlugFromRequest;
        $oldSeoUrlSlug = 'blog/' . $blog->getOriginal('slug'); // Prefixed version of the slug before potential update

        // If the blog's own slug changed, delete the SeoMeta associated with the OLD prefixed slug
        if ($newSlugFromRequest !== $blog->getOriginal('slug') && SeoMeta::where('url_slug', $oldSeoUrlSlug)->exists()) {
            SeoMeta::where('url_slug', $oldSeoUrlSlug)->delete();
        }
        
        // Also, ensure any SeoMeta with a non-prefixed slug (matching new or old blog slug) is removed
        SeoMeta::where('url_slug', $newSlugFromRequest)->where('url_slug', '!=', $newSeoUrlSlug)->delete();
        if ($newSlugFromRequest !== $blog->getOriginal('slug')) { // If slug changed, also check old non-prefixed
             SeoMeta::where('url_slug', $blog->getOriginal('slug'))->where('url_slug', '!=', $newSeoUrlSlug)->delete();
        }

        if (array_filter($seoMetaToSave) || !empty($seoMetaToSave['seo_title']) || SeoMeta::where('url_slug', $newSeoUrlSlug)->exists()) {
            $seoMeta = SeoMeta::updateOrCreate(
                ['url_slug' => $newSeoUrlSlug], 
                array_filter($seoMetaToSave, fn($value) => !is_null($value) && $value !== '')
            );

            // Handle SEO translations
            $seoTranslationsData = $request->input('seo_translations', []);
            foreach ($available_locales as $locale) {
                if (isset($seoTranslationsData[$locale])) {
                    $translationInput = $seoTranslationsData[$locale];
                    // Basic validation for translation fields
                    $request->validate([
                        "seo_translations.{$locale}.seo_title" => 'nullable|string|max:60',
                        "seo_translations.{$locale}.meta_description" => 'nullable|string|max:160',
                        "seo_translations.{$locale}.keywords" => 'nullable|string|max:255',
                    ]);

                    $seoMeta->translations()->updateOrCreate(
                        ['locale' => $locale],
                        [
                            'seo_title'        => $translationInput['seo_title'] ?? null,
                            'meta_description' => $translationInput['meta_description'] ?? null,
                            'keywords'         => $translationInput['keywords'] ?? null,
                        ]
                    );
                }
            }
        }


        return redirect()->route('admin.blogs.index')->with('success', 'Blog updated successfully.');
    }

    public function destroy(Blog $blog)
    {
        if ($blog->image) {
            $oldImagePath = str_replace(Storage::disk('upcloud')->url(''), '', $blog->image);
            Storage::disk('upcloud')->delete($oldImagePath);
        }
        
        // Delete associated SEO Meta (prefixed and non-prefixed versions)
        $seoUrlSlug = 'blog/' . $blog->slug;
        SeoMeta::where('url_slug', $seoUrlSlug)->delete();
        SeoMeta::where('url_slug', $blog->slug)->delete(); // Non-prefixed, just in case

        $blog->delete();

        return redirect()->route('admin.blogs.index')->with('success', 'Blog deleted successfully.');
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
        $blogs = Blog::with('translations')->where('is_published', true)->latest()->take(4)->get();
        $blogListSchema = [];
        if ($blogs->isNotEmpty()) {
            $blogListSchema = SchemaBuilder::blogList($blogs, 'Latest Blog Posts');
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
            // The Faq model's accessors should provide the correct 'question' and 'answer' attributes
            // in the current locale, so we can pass the collection directly if SchemaBuilder::faqPage can handle it,
            // or convert to a simple array if needed. SchemaBuilder::faqPage expects an array.
            $faqItems = $faqs->map(function ($faq) {
                return ['question' => $faq->question, 'answer' => $faq->answer];
            })->all();
            $faqPageSchema = SchemaBuilder::faqPage($faqItems);
            if (!empty($faqPageSchema)) {
                $pageSchemas[] = $faqPageSchema;
            }
        }

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'blogs' => $blogs,
            'testimonials' => $testimonials,
            'categories' => $categories,
            'popularPlaces' => $popularPlaces,
            'faqs' => $faqs, // Pass FAQ data
            'schema' => $pageSchemas,
        ]);
    }

    public function show(Blog $blog)
    {
        if (!$blog->is_published && !(auth()->check() && auth()->user()->hasRole('admin'))) {
             abort(404);
        }
        $blog->load('translations'); // Eager load translations for the single blog

        // Generate BlogPosting schema
        $blogSchema = SchemaBuilder::blog($blog);

        return Inertia::render('SingleBlog', [
            'blog' => $blog,
            'schema' => $blogSchema, // Pass schema to the Vue component
        ]);
    }

    public function showBlogPage(Request $request)
    {
        $blogs = Blog::with('translations')->where('is_published', true)->latest()->paginate(9);
        return Inertia::render('BlogPage', [
            'blogs' => $blogs,
        ]);
    }
    
    public function getRecentBlogs(Request $request) // Added Request
    {
        // If a locale is passed in the request, set it for this request's lifecycle
        // This helps the Blog model's accessors pick up the correct language
        if ($request->has('locale') && in_array($request->input('locale'), ['en', 'fr', 'nl'])) { // Validate locale
            App::setLocale($request->input('locale'));
        }

        $recentBlogs = Blog::with('translations')->where('is_published', true)->latest()->take(5)->get();
        return response()->json($recentBlogs);
    }
}
