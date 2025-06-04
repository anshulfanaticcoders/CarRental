<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminReviewController extends Controller
{
    /**
     * Display a listing of the reviews.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $searchQuery = $request->input('search', '');
        $statusFilter = $request->input('status', '');

        $reviews = Review::with([
                'user.profile', 
                'vehicle.category',
                'vehicle.user',
                'vehicle.images',
                'vehicle.vendorProfile',
                'booking'
            ])
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
            ->when($statusFilter, function ($query, $statusFilter) {
                $query->where('status', $statusFilter);
            })
            ->orderByDesc('created_at')
            ->paginate(5);

        // Calculate statistics
        $totalReviews = Review::count();
        $averageRating = Review::avg('rating') ?? 0;
        $pendingReviews = Review::where('status', 'pending')->count();
        $approvedReviews = Review::where('status', 'approved')->count();
        $rejectedReviews = Review::where('status', 'rejected')->count();

        return Inertia::render('AdminDashboardPages/Reviews/Index', [
            'reviews' => $reviews,
            'statistics' => [
                'total_reviews' => $totalReviews,
                'average_rating' => round($averageRating, 1),
                'pending_reviews' => $pendingReviews,
                'approved_reviews' => $approvedReviews,
                'rejected_reviews' => $rejectedReviews,
            ],
            'filters' => $request->only(['search', 'status'])
        ]);
    }

    /**
     * Delete the specified review.
     *
     * @param Review $review
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Review $review)
    {
        // Delete the review
        $review->delete();

        return redirect()->back()->with('success', 'Review deleted successfully!');
    }

    /**
     * Update the status of the specified review.
     *
     * @param Review $review
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Review $review, Request $request)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected,pending'
        ]);

        $review->update([
            'status' => $request->status
        ]);

        return back()->with('success', 'Review status updated successfully!');
    }
}