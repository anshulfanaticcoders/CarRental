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
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'required|string|min:10',
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

        return back()->with('success', 'Review submitted successfully!');
    }


    public function getApprovedReviews(Vehicle $vehicle)
    {
        $reviews = $vehicle->reviews()->with(['user.profile'])->where('status', 'approved')->get();
        return response()->json(['reviews' => $reviews]);
    }
}