<?php

namespace App\Http\Controllers\Api\Mobile\Vendor;

use App\Http\Controllers\Controller;
use App\Models\ApiBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ExternalBookingsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vendorId = $request->user()->id;
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:all,pending,confirmed,completed,cancelled'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $status = $validated['status'] ?? 'all';
        $limit = $validated['limit'] ?? 50;

        $query = ApiBooking::with(['consumer', 'vehicle'])
            ->whereHas('vehicle', fn ($q) => $q->where('vendor_id', $vendorId))
            ->where('is_test', false);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $rows = $query->orderByDesc('created_at')->limit($limit)->get();

        $items = $rows->map(function (ApiBooking $b) {
            return [
                'id' => $b->id,
                'booking_number' => $b->booking_number,
                'status' => $b->status,
                'pickup_date' => $b->pickup_date,
                'return_date' => $b->return_date,
                'pickup_location' => $b->pickup_location,
                'return_location' => $b->return_location,
                'total_amount' => $b->total_amount !== null ? (float) $b->total_amount : null,
                'currency' => $b->currency ?? 'EUR',
                'driver_name' => trim(($b->driver_first_name ?? '').' '.($b->driver_last_name ?? '')) ?: null,
                'driver_email' => $b->driver_email,
                'driver_phone' => $b->driver_phone,
                'consumer_name' => $b->consumer?->name,
                'vehicle' => $b->vehicle ? [
                    'id' => $b->vehicle->id,
                    'brand' => $b->vehicle->brand,
                    'model' => $b->vehicle->model,
                ] : null,
                'created_at' => $b->created_at?->toIso8601String(),
            ];
        });

        return response()->json(['bookings' => $items->values()]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $vendorId = $request->user()->id;
        $booking = ApiBooking::with(['consumer', 'extras', 'vehicle'])
            ->where('id', $id)
            ->whereHas('vehicle', fn ($q) => $q->where('vendor_id', $vendorId))
            ->first();

        if (! $booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        return response()->json([
            'booking' => [
                'id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'status' => $booking->status,
                'pickup_date' => $booking->pickup_date,
                'pickup_time' => $booking->pickup_time,
                'return_date' => $booking->return_date,
                'return_time' => $booking->return_time,
                'pickup_location' => $booking->pickup_location,
                'return_location' => $booking->return_location,
                'total_amount' => $booking->total_amount !== null ? (float) $booking->total_amount : null,
                'currency' => $booking->currency ?? 'EUR',
                'driver_name' => trim(($booking->driver_first_name ?? '').' '.($booking->driver_last_name ?? '')) ?: null,
                'driver_email' => $booking->driver_email,
                'driver_phone' => $booking->driver_phone,
                'driver_age' => $booking->driver_age,
                'consumer_name' => $booking->consumer?->name,
                'vehicle' => $booking->vehicle ? [
                    'id' => $booking->vehicle->id,
                    'brand' => $booking->vehicle->brand,
                    'model' => $booking->vehicle->model,
                ] : null,
                'extras' => ($booking->extras ?? collect())->map(fn ($e) => [
                    'name' => $e->name,
                    'quantity' => $e->quantity,
                    'amount' => $e->amount !== null ? (float) $e->amount : null,
                ])->values(),
                'created_at' => $booking->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:confirmed,completed'],
        ]);

        $vendorId = $request->user()->id;
        $booking = ApiBooking::with('vehicle')
            ->where('id', $id)
            ->whereHas('vehicle', fn ($q) => $q->where('vendor_id', $vendorId))
            ->first();

        if (! $booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        $booking->update(['status' => $validated['status']]);

        if ($validated['status'] === 'confirmed' && $booking->driver_email) {
            try {
                Notification::route('mail', $booking->driver_email)
                    ->notify(new \App\Notifications\ApiBooking\ApiBookingCreatedDriverNotification($booking));
            } catch (\Throwable $e) {
                Log::warning('Vendor external booking confirm: notify driver failed', [
                    'booking_number' => $booking->booking_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Status updated.',
            'status' => $booking->status,
        ]);
    }
}
