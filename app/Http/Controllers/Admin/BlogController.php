<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; // Added for slug generation
use Illuminate\Support\Facades\App; // Added for locale access

class BlogController extends Controller
{
    public function index(Request $request)
    {
        // The Blog model's title accessor will handle the current locale
        $search = $request->input('search');
        $query = Blog::query();

        if ($search) {
            // Search in translations
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            })->orWhere('slug', 'like', "%{$search}%");
        }
        
        $blogs = $query->latest()->paginate(8)->withQueryString();

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
        
        return Inertia::render('AdminDashboardPages/Blogs/Edit', [
            'blog' => $blogData,
            'available_locales' => $allLocales,
            'current_locale' => App::getLocale(), // Can be used to set default active tab
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
        ];
        foreach ($available_locales as $locale) {
            $validationRules["translations.{$locale}.title"] = 'required|string|max:255';
            $validationRules["translations.{$locale}.content"] = 'required|string';
        }
        $request->validate($validationRules);

        $blogData = [
            'slug' => $request->input('slug'),
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
        return redirect()->route('admin.blogs.index')->with('success', 'Blog updated successfully.');
    }

    public function destroy(Blog $blog)
    {
        if ($blog->image) {
            $oldImagePath = str_replace(Storage::disk('upcloud')->url(''), '', $blog->image);
            Storage::disk('upcloud')->delete($oldImagePath);
        }
        // Translations will be deleted by cascade constraint
        $blog->delete();

        return redirect()->route('admin.blogs.index')->with('success', 'Blog deleted successfully.');
    }

    public function homeBlogs()
    {
        $blogs = Blog::with('translations')->where('is_published', true)->latest()->take(4)->get();
        return Inertia::render('Welcome', [
            'blogs' => $blogs
        ]);
    }

    public function show(Blog $blog)
    {
        if (!$blog->is_published && !(auth()->check() && auth()->user()->hasRole('admin'))) {
             abort(404);
        }
        $blog->load('translations'); // Eager load translations for the single blog
        return Inertia::render('SingleBlog', [
            'blog' => $blog
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
