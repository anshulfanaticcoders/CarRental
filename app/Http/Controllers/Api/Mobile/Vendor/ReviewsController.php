<?php

namespace App\Http\Controllers\Api\Mobile\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vendorId = $request->user()->id;
        $host = rtrim($request->getSchemeAndHttpHost(), '/');
        $absolutize = function (?string $path) use ($host): ?string {
            if (! $path) return null;
            if (preg_match('#^https?://#i', $path)) return $path;
            return $host.'/'.ltrim($path, '/');
        };

        $reviews = Review::with(['user.profile', 'vehicle.images', 'booking'])
            ->whereHas('vehicle', fn ($q) => $q->where('vendor_id', $vendorId))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $items = $reviews->map(function (Review $r) use ($absolutize) {
            $vehicle = $r->vehicle;
            $image = null;
            if ($vehicle) {
                $primary = $vehicle->images->firstWhere('image_type', 'primary') ?? $vehicle->images->first();
                if ($primary) {
                    $image = ! empty($primary->image_url)
                        ? $absolutize($primary->image_url)
                        : (! empty($primary->image_path) ? $absolutize('storage/'.ltrim($primary->image_path, '/')) : null);
                }
            }
            $user = $r->user;
            $avatar = $user?->profile?->avatar;
            return [
                'id' => $r->id,
                'rating' => (int) $r->rating,
                'review_text' => $r->review_text,
                'reply_text' => $r->reply_text,
                'status' => $r->status,
                'created_at' => $r->created_at?->toIso8601String(),
                'reviewer' => $user ? [
                    'name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')),
                    'avatar' => $avatar ? $absolutize($avatar) : null,
                ] : null,
                'vehicle' => $vehicle ? [
                    'id' => $vehicle->id,
                    'brand' => $vehicle->brand,
                    'model' => $vehicle->model,
                    'image' => $image,
                ] : null,
                'booking_number' => $r->booking?->booking_number,
            ];
        });

        $approved = $reviews->where('status', 'approved');
        $avg = $approved->count() > 0 ? round($approved->avg('rating'), 1) : null;

        return response()->json([
            'reviews' => $items->values(),
            'statistics' => [
                'total' => $reviews->count(),
                'approved' => $approved->count(),
                'pending' => $reviews->where('status', 'pending')->count(),
                'rejected' => $reviews->where('status', 'rejected')->count(),
                'average_rating' => $avg,
            ],
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:approved,rejected'],
        ]);

        $vendorId = $request->user()->id;
        $review = Review::whereHas('vehicle', fn ($q) => $q->where('vendor_id', $vendorId))
            ->where('id', $id)
            ->first();

        if (! $review) {
            return response()->json(['message' => 'Review not found.'], 404);
        }

        $review->status = $validated['status'];
        $review->save();

        return response()->json([
            'message' => 'Review status updated.',
            'status' => $review->status,
        ]);
    }

    public function reply(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reply_text' => ['required', 'string', 'min:2', 'max:1000'],
        ]);

        $vendorId = $request->user()->id;
        $review = Review::whereHas('vehicle', fn ($q) => $q->where('vendor_id', $vendorId))
            ->where('id', $id)
            ->first();

        if (! $review) {
            return response()->json(['message' => 'Review not found.'], 404);
        }

        $review->reply_text = $validated['reply_text'];
        $review->save();

        return response()->json([
            'message' => 'Reply saved.',
            'reply_text' => $review->reply_text,
        ]);
    }
}
