<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Models\VendorProfile;
use App\Notifications\Review\ReviewSubmittedAdminNotification;
use App\Notifications\Review\ReviewSubmittedCompanyNotification;
use App\Notifications\Review\ReviewSubmittedVendorNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $host = rtrim($request->getSchemeAndHttpHost(), '/');
        $absolutize = function (?string $path) use ($host): ?string {
            if (! $path) return null;
            if (preg_match('#^https?://#i', $path)) return $path;
            return $host.'/'.ltrim($path, '/');
        };

        $reviews = Review::where('user_id', $userId)
            ->with(['vehicle.images', 'vehicle.vendorProfileData', 'booking'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $items = $reviews->map(function (Review $r) use ($absolutize) {
            $vehicle = $r->vehicle;
            $image = null;
            if ($vehicle) {
                $first = $vehicle->images->first();
                if ($first && ! empty($first->image_url)) {
                    $image = $absolutize($first->image_url);
                }
            }

            return [
                'id' => $r->id,
                'rating' => (int) $r->rating,
                'review_text' => $r->review_text,
                'status' => $r->status,
                'created_at' => $r->created_at?->toIso8601String(),
                'booking_id' => $r->booking_id,
                'booking_number' => $r->booking?->booking_number,
                'vehicle' => $vehicle ? [
                    'id' => $vehicle->id,
                    'brand' => $vehicle->brand,
                    'model' => $vehicle->model,
                    'image' => $image,
                ] : null,
                'vendor' => $vehicle?->vendorProfileData ? [
                    'company_name' => $vehicle->vendorProfileData->company_name,
                ] : null,
            ];
        });

        $approvedRatings = $reviews->where('status', 'approved')->pluck('rating');
        $averageRating = $approvedRatings->count() > 0
            ? round($approvedRatings->avg(), 1)
            : null;

        return response()->json([
            'reviews' => $items->values(),
            'statistics' => [
                'total' => $reviews->count(),
                'approved' => $approvedRatings->count(),
                'average_rating' => $averageRating,
            ],
        ]);
    }

    public function store(Request $request, int $bookingId): JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review_text' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $booking = Booking::with(['vehicle', 'customer'])
            ->where('id', $bookingId)
            ->whereHas('customer', fn ($q) => $q->where('user_id', $request->user()->id))
            ->first();

        if (! $booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        $vehicle = $booking->vehicle;
        if (! $vehicle) {
            return response()->json(['message' => 'Vehicle not available for review.'], 422);
        }

        $providerSource = $booking->provider_source ? strtolower($booking->provider_source) : null;
        if ($providerSource && $providerSource !== 'internal') {
            return response()->json([
                'message' => 'Reviews are available for internal vendor bookings only.',
            ], 422);
        }

        $vendorProfile = VendorProfile::where('user_id', $vehicle->vendor_id)->first();
        if (! $vendorProfile) {
            return response()->json(['message' => 'Vendor profile not found.'], 422);
        }

        $existing = Review::where('booking_id', $booking->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'You have already submitted a review for this booking.'], 409);
        }

        $review = Review::create([
            'booking_id' => $booking->id,
            'user_id' => $request->user()->id,
            'vehicle_id' => $vehicle->id,
            'vendor_profile_id' => $vendorProfile->id,
            'rating' => (int) $validated['rating'],
            'review_text' => $validated['review_text'],
            'status' => 'pending',
        ]);

        try {
            $adminEmail = config('admin.email');
            $admin = User::where('email', $adminEmail)->first();
            if ($admin) {
                $admin->notify(new ReviewSubmittedAdminNotification($review, $vehicle));
            }

            if ($vendorProfile->company_email) {
                $companyUser = User::where('email', $vendorProfile->company_email)->first();
                if ($companyUser) {
                    $companyUser->notify(new ReviewSubmittedCompanyNotification($review, $vehicle));
                }
            }

            $vendor = User::find($vehicle->vendor_id);
            if ($vendor) {
                $vendor->notify(new ReviewSubmittedVendorNotification($review, $vehicle));
            }
        } catch (\Throwable $e) {
            Log::error('Mobile review notify failed', [
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Review submitted. It will be visible after approval.',
            'review' => [
                'id' => $review->id,
                'rating' => $review->rating,
                'review_text' => $review->review_text,
                'status' => $review->status,
                'created_at' => $review->created_at?->toIso8601String(),
            ],
        ], 201);
    }
}
