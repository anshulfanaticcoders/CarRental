<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\App; // Added for locale access

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = Page::query();

        // Apply search filter if provided
        if ($search) {
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%");
        }

        $pages = $query->latest()->paginate(8)->withQueryString();

        return Inertia::render('AdminDashboardPages/Pages/Index', [
            'pages' => $pages,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('AdminDashboardPages/Pages/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $locale = $request->input('locale');

        $request->validate([
            'locale' => 'required|in:en,fr,nl',
        ]);

        if ($locale === 'en') {
            $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required',
            ]);
        } elseif ($locale === 'fr') {
            $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required',
            ]);
        } elseif ($locale === 'nl') {
            $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required',
            ]);
        }

        $title = $request->input('title');
        $content = $request->input('content');
        $slug = Str::slug($title);

        // Check if slug already exists
        $existingPage = Page::where('slug', $slug)->first();
        if ($existingPage) {
            // Append a unique identifier if slug exists
            $slug = $slug . '-' . uniqid();
        }

        $page = Page::create([
            'slug' => $slug,
        ]);

        $page->translations()->create([
            'locale' => $locale,
            'title' => $title,
            'content' => $content,
        ]);

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Page $page)
    {
        return Inertia::render('AdminDashboardPages/Pages/Show', [
            'page' => $page
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Page $page)
    {
         $translations = $page->translations->keyBy('locale');
        $locale = app()->getLocale();

        return Inertia::render('AdminDashboardPages/Pages/Edit', [
            'page' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'translations' => $translations,
                'locale' => $locale,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Page $page)
    {
        $request->validate([
            'locale' => 'required|in:en,fr,nl',
            'title' => 'required|string|max:255',
            'content' => 'required'
        ]);

        $locale = $request->input('locale');
        $title = $request->input('title');
        $content = $request->input('content');

        $translation = $page->translations()->where('locale', $locale)->first();

        if ($translation) {
            $translation->title = $title;
            $translation->content = $content;
            $translation->save();
        } else {
            $page->translations()->create([
                'locale' => $locale,
                'title' => $title,
                'content' => $content,
            ]);
        }

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page deleted successfully.');
    }

    /**
     * Display the specified page on the frontend.
     */
    public function showPublic(Request $request, $slug)
    {
        $locale = App::getLocale();
        $page = Page::where('slug', $slug)->firstOrFail();

        $translation = $page->translations()->where('locale', $locale)->first();

        if (!$translation) {
            $defaultLocale = config('app.fallback_locale', 'en');
            $translation = $page->translations()->where('locale', $defaultLocale)->first();
        }

        $pageData = [
            'title' => $translation ? $translation->title : 'Page Title Not Found',
            'content' => $translation ? $translation->content : '<p>Content not available in this language.</p>',
            'slug' => $page->slug,
        ];

        return Inertia::render('Frontend/Page', [
            'page' => $pageData,
        ]);
    }
}
