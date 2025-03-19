<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

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
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required'
        ]);

        $slug = Str::slug($request->title);
        
        // Check if slug already exists
        $existingPage = Page::where('slug', $slug)->first();
        if ($existingPage) {
            // Append a unique identifier if slug exists
            $slug = $slug . '-' . uniqid();
        }

        Page::create([
            'title' => $request->title,
            'slug' => $slug,
            'content' => $request->content
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
        return Inertia::render('AdminDashboardPages/Pages/Edit', [
            'page' => $page
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Page $page)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required'
        ]);

        // Only update slug if title has changed to prevent changing URLs of existing pages
        if ($page->title !== $request->title) {
            $slug = Str::slug($request->title);
            
            // Check if slug already exists for another page
            $existingPage = Page::where('slug', $slug)
                ->where('id', '!=', $page->id)
                ->first();
                
            if ($existingPage) {
                // Append unique identifier if slug exists
                $slug = $slug . '-' . uniqid();
            }
            
            $page->slug = $slug;
        }

        $page->title = $request->title;
        $page->content = $request->content;
        $page->save();

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
}