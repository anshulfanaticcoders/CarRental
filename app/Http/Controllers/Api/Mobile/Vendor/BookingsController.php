<?php

namespace App\Http\Controllers\Api\Mobile\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\Booking\BookingStatusUpdatedCustomerNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookingsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vendorId = $request->user()->id;
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:all,pending,confirmed,active,completed,cancelled,upcoming,today'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $status = $validated['status'] ?? 'all';
        $limit = $validated['limit'] ?? 50;

        $query = Booking::with(['vehicle.images', 'customer', 'amounts'])
            ->whereHas('vehicle', fn ($q) => $q->where('vendor_id', $vendorId));

        if ($status === 'today') {
            $query->whereDate('pickup_date', now()->toDateString());
        } elseif ($status === 'upcoming') {
            $query->whereIn('booking_status', ['confirmed', 'active'])
                ->whereDate('pickup_date', '>=', now()->toDateString());
        } elseif ($status !== 'all') {
            $query->where('booking_status', $status);
        }

        $bookings = $query->orderByDesc('created_at')->limit($limit)->get();

        return response()->json([
            'bookings' => $bookings->map(fn (Booking $b) => $this->transformList($b, $request))->values(),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $vendorId = $request->user()->id;
        $booking = Booking::with(['vehicle.images', 'vehicle.category', 'customer', 'extras', 'amounts', 'payments'])
            ->where('id', $id)
            ->whereHas('vehicle', fn ($q) => $q->where('vendor_id', $vendorId))
            ->first();

        if (! $booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        return response()->json([
            'booking' => $this->transformDetail($booking, $request),
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:confirmed,active,completed,cancelled,rejected'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $vendorId = $request->user()->id;
        $booking = Booking::with(['vehicle', 'customer'])
            ->where('id', $id)
            ->whereHas('vehicle', fn ($q) => $q->where('vendor_id', $vendorId))
            ->first();

        if (! $booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        $previous = $booking->booking_status;
        $booking->booking_status = $validated['status'];
        if (! empty($validated['note'])) {
            $booking->notes = trim(($booking->notes ?? '') . "\n[vendor] " . $validated['note']);
        }
        $booking->save();

        try {
            $customer = $booking->customer;
            $customerUser = $customer?->user_id ? User::find($customer->user_id) : null;
            if ($customerUser && $customer && $booking->vehicle) {
                $customerUser->notify(new BookingStatusUpdatedCustomerNotification(
                    $booking,
                    $customer,
                    $booking->vehicle,
                    $request->user(),
                ));
            }
            unset($previous);
        } catch (\Throwable $e) {
            Log::warning('Vendor updateStatus: notify failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Booking status updated.',
            'booking' => $this->transformDetail($booking->fresh(['vehicle.images', 'vehicle.category', 'customer', 'extras', 'amounts', 'payments']), $request),
        ]);
    }

    private function vehicleImage(Booking $b, Request $request): ?string
    {
        $host = rtrim($request->getSchemeAndHttpHost(), '/');
        $absolutize = function (?string $path) use ($host): ?string {
            if (! $path) return null;
            if (preg_match('#^https?://#i', $path)) return $path;
            return $host.'/'.ltrim($path, '/');
        };

        if ($b->vehicle?->images?->isNotEmpty()) {
            $primary = $b->vehicle->images->firstWhere('image_type', 'primary') ?? $b->vehicle->images->first();
            if ($primary) {
                return ! empty($primary->image_url)
                    ? $absolutize($primary->image_url)
                    : (! empty($primary->image_path) ? $absolutize('storage/'.ltrim($primary->image_path, '/')) : null);
            }
        }
        return $b->vehicle_image ? $absolutize($b->vehicle_image) : null;
    }

    private function transformList(Booking $b, Request $request): array
    {
        $customer = $b->customer;
        $vendorAmount = $b->amounts?->first()?->vendor_total_amount ?? null;
        $vendorCurrency = $b->amounts?->first()?->vendor_currency ?? $b->booking_currency ?? 'EUR';

        return [
            'id' => $b->id,
            'booking_number' => $b->booking_number,
            'booking_status' => $b->booking_status,
            'payment_status' => $b->payment_status,
            'pickup_date' => $b->pickup_date,
            'pickup_time' => $b->pickup_time,
            'return_date' => $b->return_date,
            'return_time' => $b->return_time,
            'pickup_location' => $b->pickup_location,
            'return_location' => $b->return_location,
            'vehicle' => [
                'id' => $b->vehicle?->id,
                'brand' => $b->vehicle?->brand,
                'model' => $b->vehicle?->model,
                'image' => $this->vehicleImage($b, $request),
            ],
            'customer' => [
                'name' => $customer ? trim(($customer->first_name ?? '').' '.($customer->last_name ?? '')) : ($b->driver_name ?? null),
                'phone' => $customer?->phone ?? $b->driver_phone,
                'email' => $customer?->email ?? $b->driver_email,
            ],
            'vendor_total_amount' => $vendorAmount !== null ? (float) $vendorAmount : null,
            'vendor_currency' => $vendorCurrency,
            'created_at' => $b->created_at?->toIso8601String(),
        ];
    }

    private function transformDetail(Booking $b, Request $request): array
    {
        $base = $this->transformList($b, $request);
        $metadata = is_string($b->provider_metadata) ? (json_decode($b->provider_metadata, true) ?: []) : ($b->provider_metadata ?: []);
        $amount = $b->amounts?->first();

        return array_merge($base, [
            'plan' => $b->plan,
            'driver_name' => $b->driver_name,
            'driver_email' => $b->driver_email,
            'driver_phone' => $b->driver_phone,
            'driver_age' => $b->driver_age,
            'driver_license_number' => $b->driver_license_number,
            'flight_number' => $b->flight_number ?? ($metadata['flight_number'] ?? null),
            'notes' => $b->notes,
            'cancellation_reason' => $b->cancellation_reason,
            'extras' => ($b->extras ?? collect())->map(fn ($e) => [
                'name' => $e->extra_name ?? $e->name ?? null,
                'type' => $e->extra_type ?? null,
                'quantity' => (int) ($e->quantity ?? 1),
                'amount' => $e->price !== null ? (float) $e->price : ($e->amount !== null ? (float) $e->amount : null),
            ])->values(),
            'plan_details' => [
                'name' => $b->plan,
                'protection_code' => $metadata['protection_code'] ?? null,
                'protection_amount' => isset($metadata['protection_amount']) ? (float) $metadata['protection_amount'] : null,
                'package' => $metadata['package'] ?? null,
                'selected_deposit_type' => $metadata['selected_deposit_type'] ?? null,
                'mandatory_amount' => isset($metadata['mandatory_amount']) ? (float) $metadata['mandatory_amount'] : null,
                'fuel_policy' => $metadata['fuel_policy'] ?? null,
            ],
            'base_price' => $b->base_price !== null ? (float) $b->base_price : null,
            'extra_charges' => $b->extra_charges !== null ? (float) $b->extra_charges : null,
            'total_days' => $b->total_days,
            'amounts' => $amount ? [
                'vendor_total' => $amount->vendor_total_amount !== null ? (float) $amount->vendor_total_amount : null,
                'vendor_currency' => $amount->vendor_currency ?? null,
                'platform_commission' => $amount->platform_commission !== null ? (float) $amount->platform_commission : null,
                'admin_total' => $amount->admin_total_amount !== null ? (float) $amount->admin_total_amount : null,
                'admin_currency' => $amount->admin_currency ?? null,
            ] : null,
            'payment_summary' => [
                'total' => $b->total_amount !== null ? (float) $b->total_amount : null,
                'paid' => $b->amount_paid !== null ? (float) $b->amount_paid : null,
                'pending' => $b->pending_amount !== null ? (float) $b->pending_amount : null,
                'currency' => $b->booking_currency ?? 'EUR',
            ],
            'provider_source' => $b->provider_source,
        ]);
    }
}
