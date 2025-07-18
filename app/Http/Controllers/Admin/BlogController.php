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
use App\Helpers\ImageCompressionHelper;
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
            $validationRules["translations.{$locale}.slug"] = 'required|string|max:255';
            $validationRules["translations.{$locale}.content"] = 'required|string';
        }
        $request->validate($validationRules);

        $translationsData = $request->input('translations');
        
        // Generate slug from the 'en' title or the first available title
        // Generate main slug from the 'en' title or the first available title
        $slugTitle = $translationsData['en']['title'] ?? $translationsData[array_key_first($translationsData)]['title'];
        $mainSlug = Str::slug($slugTitle);
        $existingBlog = Blog::where('slug', $mainSlug)->first();
        if ($existingBlog) {
            $mainSlug = $mainSlug . '-' . uniqid();
        }

        $blogData = [
            'slug' => $mainSlug,
            'is_published' => $request->input('is_published', true),
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
            if (in_array($locale, $available_locales) && !empty($data['title']) && !empty($data['content'])) {
                 $blog->translations()->create([
                    'locale' => $locale,
                    'title' => $data['title'],
                    'slug' => Str::slug($data['slug']),
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
                        'url_slug'         => Str::slug($translationsData[$locale]['slug']),
                        'seo_title'        => $translationInput['seo_title'] ?? null,
                        'meta_description' => $translationInput['meta_description'] ?? null,
                        'keywords'         => $translationInput['keywords'] ?? null,
                    ]
                );
            }
        }


        return redirect()->route('admin.blogs.index', ['locale' => App::getLocale()])->with('success', 'Blog created successfully.');
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
                'slug' => $translation ? $translation->slug : '',
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
                    'url_slug'         => $translation->url_slug ?? null,
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
            $validationRules["translations.{$locale}.slug"] = 'required|string|max:255';
            $validationRules["translations.{$locale}.content"] = 'required|string';
        }
        $request->validate($validationRules);

        $blogData = [
            'is_published' => $request->input('is_published', $blog->is_published),
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
        
        $blog->update($blogData);

        $translationsData = $request->input('translations');
        foreach ($translationsData as $locale => $data) {
             if (in_array($locale, $available_locales) && !empty($data['title']) && !empty($data['content'])) {
                $blog->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'title' => $data['title'],
                        'slug' => Str::slug($data['slug']),
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
        
        // If the english title is updated, we update the main slug of the blog post
        if (isset($translationsData['en']['title'])) {
            $newMainSlug = Str::slug($translationsData['en']['title']);
            if ($newMainSlug !== $blog->slug) {
                $existingBlog = Blog::where('slug', $newMainSlug)->where('id', '!=', $blog->id)->first();
                if ($existingBlog) {
                    $newMainSlug = $newMainSlug . '-' . uniqid();
                }
                $blogData['slug'] = $newMainSlug;
            }
        }

        $blog->update($blogData);

        // SEO Meta URL Slug Management
        $newSeoUrlSlug = 'blog/' . $blog->slug;
        $oldSeoUrlSlug = 'blog/' . $blog->getOriginal('slug');

        // If the main slug changed, we need to move the SEO meta to the new slug.
        if ($newSeoUrlSlug !== $oldSeoUrlSlug) {
            SeoMeta::where('url_slug', $oldSeoUrlSlug)->update(['url_slug' => $newSeoUrlSlug]);
        }

        // Now, update or create the SEO meta with the new data for the correct slug.
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
                            'url_slug'         => Str::slug($translationsData[$locale]['slug']),
                            'seo_title'        => $translationInput['seo_title'] ?? null,
                            'meta_description' => $translationInput['meta_description'] ?? null,
                            'keywords'         => $translationInput['keywords'] ?? null,
                        ]
                    );
                }
            }
        }


        return redirect()->route('admin.blogs.index', ['locale' => App::getLocale()])->with('success', 'Blog updated successfully.');
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

        $seoMeta = SeoMeta::with('translations')->where('url_slug', '/')->first();
        $pages = \App\Models\Page::with('translations')->get()->keyBy('slug');

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
            'seoMeta' => $seoMeta,
            'pages' => $pages,
        ]);
    }

    public function show($locale, $slug)
    {
        App::setLocale($locale);

        $translation = \App\Models\BlogTranslation::where('slug', $slug)->where('locale', $locale)->firstOrFail();
        $blog = $translation->blog;

        if (!$blog->is_published && !(auth()->check() && auth()->user()->hasRole('admin'))) {
             abort(404);
        }
        $blog->load('translations'); // Eager load translations for the single blog

        // Generate BlogPosting schema
        $blogSchema = SchemaBuilder::blog($blog);
        
        $seoMeta = null;
        $seoMetaTranslation = \App\Models\SeoMetaTranslation::where('url_slug', $slug)->where('locale', $locale)->first();

        if ($seoMetaTranslation) {
            $seoMeta = SeoMeta::with('translations')->find($seoMetaTranslation->seo_meta_id);
        }


        $pages = \App\Models\Page::with('translations')->get()->keyBy('slug');

        return Inertia::render('SingleBlog', [
            'blog' => $blog,
            'schema' => $blogSchema, // Pass schema to the Vue component
            'seoMeta' => $seoMeta,
            'locale' => $locale,
            'pages' => $pages,
        ]);
    }
    
    public function showBlogPage(Request $request)
    {
        $blogs = Blog::with('translations')->where('is_published', true)->latest()->paginate(1);
        $pages = \App\Models\Page::with('translations')->get()->keyBy('slug');

        // Fetch SEO meta for the blog page (assuming its url_slug is '/blog')
        $seoMeta = SeoMeta::with('translations')->where('url_slug', '/blog')->first();
        
        return Inertia::render('BlogPage', [
            'blogs' => $blogs,
            'pages' => $pages,
            'seoMeta' => $seoMeta, // Pass SEO meta to the component
            'locale' => App::getLocale(), // Pass current locale
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
