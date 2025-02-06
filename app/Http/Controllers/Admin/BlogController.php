<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/blogs'), $imageName);
            $blog->image = 'images/blogs/' . $imageName;
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
            // Delete old image if exists
            if ($blog->image && file_exists(public_path($blog->image))) {
                unlink(public_path($blog->image));
            }

            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/blogs'), $imageName);
            $blog->image = 'images/blogs/' . $imageName;
        }

        $blog->save();

        return redirect()->route('blogs.index')->with('success', 'Blog updated successfully');
    }

    public function destroy(Blog $blog)
    {
        if ($blog->image && file_exists(public_path($blog->image))) {
            unlink(public_path($blog->image));
        }

        $blog->delete();

        return redirect()->route('blogs.index')->with('success', 'Blog deleted successfully');
    }
    public function homeBlogs()
    {
        $blogs = Blog::latest()->take(4)->get()->map(function ($blog) {
            if ($blog->image) {
                $blog->image = asset($blog->image); // Ensure full image URL
            }
            return $blog;
        });

        return Inertia::render('Welcome', [
            'blogs' => $blogs
        ]);
    }

    public function show(Blog $blog)
    {
        if ($blog->image) {
            $blog->image = asset($blog->image);
        }

        return Inertia::render('SingleBlog', [
            'blog' => $blog
        ]);
    }

    public function showBlogPage(Request $request)
    {
        $blogs = Blog::latest()->paginate(10);

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