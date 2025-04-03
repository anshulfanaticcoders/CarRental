<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function store(Request $request)
    {

        // print_r($request->all());
        // die();
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'required|string|min:10',
            'vendor_profile_id' => 'required|exists:vendor_profiles,id',
        ]);

        // Add user_id to the validated data
        $validated['user_id'] = auth()->id();
        $validated['status'] = 'pending';

        // Check if a review already exists for this booking
        $existingReview = Review::where('booking_id', $validated['booking_id'])
            ->where('user_id', $validated['user_id'])
            ->first();

        if ($existingReview) {
            return back()->with('error', 'You have already submitted a review for this booking.');
        }

        // Create the review
        $review = Review::create($validated);

        return redirect()->route('profile.bookings.completed')->with('success', 'Review submitted successfully!');

    }


    public function getApprovedReviews($vendorProfileId)
{
    if (!$vendorProfileId) {
        return response()->json(['error' => 'Vendor profile ID is required'], 400);
    }

    $approvedReviews = Review::where('vendor_profile_id', $vendorProfileId) // Fix: Correct field
        ->where('status', 'approved')
        ->with(['user.profile'])
        ->get();

    return response()->json(['reviews' => $approvedReviews]);
}


    public function vendorReviews(Request $request)
    {
        $vendor = auth()->user();
        $searchQuery = $request->input('search', '');
    
        // Fetch vendor reviews
        $reviews = Review::whereHas('vehicle.vendorProfileData', function ($query) use ($vendor) {
                $query->where('user_id', $vendor->id);
            })
            ->with(['user.profile', 'booking', 'vehicle'])
            ->when($searchQuery, function ($query, $searchQuery) {
                $query->where(function ($q) use ($searchQuery) {
                    $q->where('review_text', 'like', '%' . $searchQuery . '%')
                      ->orWhereHas('user', function ($q) use ($searchQuery) {
                          $q->where('first_name', 'like', '%' . $searchQuery . '%')
                            ->orWhere('last_name', 'like', '%' . $searchQuery . '%');
                      })
                      ->orWhereHas('vehicle', function ($q) use ($searchQuery) {
                          $q->where('brand', 'like', '%' . $searchQuery . '%')
                            ->orWhere('model', 'like', '%' . $searchQuery . '%');
                      });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(5);
    
        // Calculate statistics
        $totalReviews = Review::whereHas('vehicle.vendorProfile', function ($query) use ($vendor) {
            $query->where('user_id', $vendor->id);
        })->count();
    
        $averageRating = Review::whereHas('vehicle.vendorProfile', function ($query) use ($vendor) {
            $query->where('user_id', $vendor->id);
        })->avg('rating') ?? 0;
    
        return Inertia::render('Vendor/Review/Index', [
            'reviews' => $reviews,
            'statistics' => [
                'total_reviews' => $totalReviews,
                'average_rating' => round($averageRating, 1),
            ],
            'filters' => $request->only('search')
        ]);
    }
    


    // ReviewController.php
    public function userReviews()
    {
        $user = auth()->user();
    
        $reviews = Review::where('user_id', $user->id)
            ->with([
                'vendorProfileData', 
                'booking', 
                'user', 
                'vehicle.category',  // Include vehicle category
                'vehicle.user',      // Include vehicle owner
                'vehicle.images',    // Include vehicle images
                'vehicle.vendorProfile'  // Include vendor profile
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(8);
    
        $averageRating = Review::where('user_id', $user->id)->avg('rating');
    
        return Inertia::render('Profile/Review/Index', [
            'reviews' => $reviews,
            'statistics' => [
                'total_reviews' => $reviews->total(),
                'average_rating' => $averageRating !== null ? $averageRating : 0,
            ]
        ]);
    }

    public function updateStatus(Review $review, Request $request)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        // Verify the review belongs to one of the vendor's vehicles
        $isVendorReview = $review->vehicle->vendor_id === auth()->id();

        if (!$isVendorReview) {
            return response()->json([
                'message' => 'Unauthorized action.'
            ], 403);
        }

        $review->update([
            'status' => $request->status
        ]);

        return back()->with('success', 'Review status updated successfully');
    }
}