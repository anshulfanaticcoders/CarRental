<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function bySession(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_id' => ['required', 'string', 'max:191'],
        ]);

        $booking = Booking::with(['vehicle.images', 'plans', 'extras', 'addons', 'amount', 'payment'])
            ->where('stripe_session_id', $data['session_id'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $booking) {
            return response()->json([
                'status' => 'pending',
                'message' => 'Booking not yet finalized. Try again shortly.',
            ], 202);
        }

        return response()->json([
            'status' => $booking->payment_status === 'paid' ? 'confirmed' : 'pending',
            'booking' => $this->transform($booking),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $booking = Booking::with(['vehicle.images', 'plans', 'extras', 'addons', 'amount', 'payment'])
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        return response()->json([
            'booking' => $this->transform($booking),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::with(['vehicle.images'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'bookings' => $bookings->map(fn ($b) => $this->transformList($b))->values(),
        ]);
    }

    private function transform(Booking $b): array
    {
        $primaryImage = null;
        if (method_exists($b, 'vehicle') && $b->vehicle && $b->vehicle->images) {
            $primary = $b->vehicle->images->firstWhere('image_type', 'primary') ?? $b->vehicle->images->first();
            $primaryImage = $primary?->image_path ? ('/storage/'.ltrim($primary->image_path, '/')) : null;
        }

        return [
            'id' => $b->id,
            'booking_number' => $b->booking_number,
            'status' => $b->booking_status ?? $b->status ?? 'pending',
            'payment_status' => $b->payment_status ?? null,
            'pickup_date' => $b->pickup_date,
            'pickup_time' => $b->pickup_time,
            'return_date' => $b->return_date,
            'return_time' => $b->return_time,
            'pickup_location' => $b->pickup_location,
            'return_location' => $b->return_location,
            'total_amount' => $b->total_amount !== null ? (float) $b->total_amount : null,
            'amount_paid' => $b->amount_paid !== null ? (float) $b->amount_paid : null,
            'amount_pending' => $b->pending_amount !== null ? (float) $b->pending_amount : null,
            'currency' => $b->booking_currency ?? $b->currency ?? 'EUR',
            'driver_name' => $b->driver_name,
            'driver_email' => $b->driver_email,
            'driver_phone' => $b->driver_phone,
            'discount_code' => $b->discount_code,
            'discount_amount' => $b->discount_amount !== null ? (float) $b->discount_amount : 0,
            'stripe_session_id' => $b->stripe_session_id,
            'vehicle' => $b->vehicle ? [
                'id' => $b->vehicle->id,
                'brand' => $b->vehicle->brand ?? null,
                'model' => $b->vehicle->model ?? null,
                'image' => $primaryImage,
            ] : [
                'brand' => $b->vehicle_brand ?? null,
                'model' => $b->vehicle_model ?? null,
                'image' => null,
            ],
            'provider_source' => $b->provider_source ?? null,
            'provider_metadata' => $b->provider_metadata ?? null,
            'created_at' => $b->created_at?->toIso8601String(),
        ];
    }

    private function transformList(Booking $b): array
    {
        return [
            'id' => $b->id,
            'booking_number' => $b->booking_number,
            'status' => $b->booking_status ?? $b->status ?? 'pending',
            'payment_status' => $b->payment_status ?? null,
            'pickup_date' => $b->pickup_date,
            'return_date' => $b->return_date,
            'pickup_location' => $b->pickup_location,
            'total_amount' => $b->total_amount !== null ? (float) $b->total_amount : null,
            'currency' => $b->booking_currency ?? 'EUR',
            'vehicle' => [
                'brand' => $b->vehicle?->brand ?? $b->vehicle_brand ?? null,
                'model' => $b->vehicle?->model ?? $b->vehicle_model ?? null,
                'image' => $b->vehicle?->images?->first()?->image_path
                    ? ('/storage/'.ltrim($b->vehicle->images->first()->image_path, '/'))
                    : null,
            ],
        ];
    }
}
