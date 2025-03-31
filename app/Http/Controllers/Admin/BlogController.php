<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::latest()->get();
        return Inertia::render('AdminDashboardPages/Blogs/Index', [
            'blogs' => $blogs
        ]);
    }

    public function create()
    {
        return Inertia::render('AdminDashboardPages/Blogs/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $blog = new Blog();
        $blog->title = $request->title;
        $blog->content = $request->content;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('blogs', 'upcloud'); // Store in UpCloud
            $blog->image = Storage::disk('upcloud')->url($imagePath); // Get full image URL
        }

        $blog->save();

        return redirect()->route('blogs.index')->with('success', 'Blog created successfully');
    }

    public function edit(Blog $blog)
    {
        return Inertia::render('AdminDashboardPages/Blogs/Update', [
            'blog' => $blog
        ]);
    }

    public function update(Request $request, Blog $blog)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $blog->title = $request->title;
        $blog->content = $request->content;

        if ($request->hasFile('image')) {
            // Delete old image from UpCloud if exists
            if ($blog->image) {
                $oldImagePath = str_replace(Storage::disk('upcloud')->url(''), '', $blog->image);
                Storage::disk('upcloud')->delete($oldImagePath);
            }

            $imagePath = $request->file('image')->store('blogs', 'upcloud');
            $blog->image = Storage::disk('upcloud')->url($imagePath);
        }

        $blog->save();

        return redirect()->route('blogs.index')->with('success', 'Blog updated successfully');
    }

    public function destroy(Blog $blog)
    {
        if ($blog->image) {
            $oldImagePath = str_replace(Storage::disk('upcloud')->url(''), '', $blog->image);
            Storage::disk('upcloud')->delete($oldImagePath);
        }

        $blog->delete();

        return redirect()->route('blogs.index')->with('success', 'Blog deleted successfully');
    }

    public function homeBlogs()
    {
        $blogs = Blog::latest()->take(4)->get();
        // No need to modify image URLs as they are already full URLs from UpCloud

        return Inertia::render('Welcome', [
            'blogs' => $blogs
        ]);
    }

    public function show(Blog $blog)
    {
        // No need to modify image URL as it's already a full URL from UpCloud
        return Inertia::render('SingleBlog', [
            'blog' => $blog
        ]);
    }

    public function showBlogPage(Request $request)
    {
        $blogs = Blog::latest()->paginate(9);

        return Inertia::render('BlogPage', [
            'blogs' => $blogs,
        ]);
    }
    
    public function getRecentBlogs()
    {
        $recentBlogs = Blog::latest()->take(5)->get();

        return response()->json($recentBlogs);
    }
}