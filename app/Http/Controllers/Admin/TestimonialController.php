<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class TestimonialController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $rating = $request->query('rating');

        // Build the testimonials query
        $testimonialsQuery = Testimonial::query();

        // Apply search filter
        if ($search) {
            $testimonialsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('review', 'like', "%{$search}%")
                      ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        // Apply rating filter
        if ($rating && $rating !== 'all') {
            $testimonialsQuery->where('ratings', $rating);
        }

        $testimonials = $testimonialsQuery->orderBy('created_at', 'desc')->paginate(7)->withQueryString();

        // Get testimonial statistics
        $statusCounts = [
            'total' => Testimonial::count(),
            'five_star' => Testimonial::where('ratings', 5)->count(),
            'four_star' => Testimonial::where('ratings', 4)->count(),
            'three_star' => Testimonial::where('ratings', 3)->count(),
        ];

        return Inertia::render('AdminDashboardPages/Testimonials/Index', [
            'testimonials' => $testimonials,
            'statusCounts' => $statusCounts,
            'filters' => $request->only(['search', 'rating']),
            'flash' => session()->only(['success', 'error'])
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'name' => 'required|string|max:255',
            'review' => 'required|string',
            'ratings' => 'required|numeric|min:1|max:5',
            'designation' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        if ($request->hasFile('avatar')) {
            $image = $request->file('avatar');
            $originalName = $image->getClientOriginalName();
            $folderName = 'avatars';
            $path = $image->storeAs($folderName, $originalName, 'upcloud');
            $data['avatar'] = Storage::disk('upcloud')->url($path);
        }

        $testimonial = Testimonial::create($data);
        // return response()->json($testimonial, 201);
        return redirect()->route('testimonials.index')->with('success', 'Testimonial created successfully.');
    }

    public function show($id)
    {
        $testimonial = Testimonial::findOrFail($id);
        return response()->json($testimonial);
    }

    public function update(Request $request, $id)
    {
        $testimonial = Testimonial::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'name' => 'required|string|max:255',
            'review' => 'required|string',
            'ratings' => 'required|numeric|min:1|max:5',
            'designation' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        if ($request->hasFile('avatar')) {
            if ($testimonial->avatar) {
                $oldPath = str_replace(Storage::disk('upcloud')->url(''), '', $testimonial->avatar);
                Storage::disk('upcloud')->delete($oldPath);
            }
            $image = $request->file('avatar');
            $originalName = $image->getClientOriginalName();
            $folderName = 'avatars';
            $path = $image->storeAs($folderName, $originalName, 'upcloud');
            $data['avatar'] = Storage::disk('upcloud')->url($path);
        }

        $testimonial->update($data);
        // return response()->json($testimonial);
        return redirect()->route('testimonials.index')->with('success', 'Testimonial updated successfully.');
    }

    public function destroy($id)
    {
        $testimonial = Testimonial::findOrFail($id);
        if ($testimonial->avatar) {
            $path = str_replace(Storage::disk('upcloud')->url(''), '', $testimonial->avatar);
            Storage::disk('upcloud')->delete($path);
        }
        $testimonial->delete();
        // return response()->json(['message' => 'Testimonial deleted']);
        return redirect()->route('testimonials.index')->with('success', 'Testimonial deleted successfully.');
    }

    /**
     * Get testimonials for frontend display
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFrontendTestimonials(Request $request)
    {
        // Get limit parameter or default to all
        $limit = $request->input('limit', null);
        
        // Get testimonials with highest ratings first
        $query = Testimonial::orderBy('ratings', 'desc');
        
        // Apply limit if specified
        if ($limit) {
            $query->limit($limit);
        }
        
        $testimonials = $query->get()->map(function ($testimonial) {
            return [
                'comment' => $testimonial->review,
                'author' => $testimonial->name,
                'title' => $testimonial->designation,
                'rating' => (float)$testimonial->ratings,
                'avatar' => $testimonial->avatar ?? null,
            ];
        });
        
        return response()->json([
            'success' => true,
            'testimonials' => $testimonials
        ]);
    }
}
