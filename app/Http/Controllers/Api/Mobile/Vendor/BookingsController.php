<?php

namespace App\Http\Controllers\Api\Mobile\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Models\VendorVehiclePlan;
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

    public function calendar(Request $request): JsonResponse
    {
        $vendorId = $request->user()->id;
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $from = isset($validated['from']) ? \Carbon\Carbon::createFromFormat('Y-m-d', $validated['from']) : \Carbon\Carbon::today();
        $to = isset($validated['to']) ? \Carbon\Carbon::createFromFormat('Y-m-d', $validated['to']) : $from->copy()->addMonths(3);
        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $bookings = Booking::with(['vehicle:id,brand,model'])
            ->whereHas('vehicle', fn ($q) => $q->where('vendor_id', $vendorId))
            ->whereIn('booking_status', ['pending', 'confirmed', 'active'])
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('pickup_date', [$from, $to])
                    ->orWhereBetween('return_date', [$from, $to])
                    ->orWhere(function ($q2) use ($from, $to) {
                        $q2->where('pickup_date', '<', $from)->where('return_date', '>', $to);
                    });
            })
            ->orderBy('pickup_date')
            ->get();

        $items = $bookings->map(function (Booking $b) {
            return [
                'id' => $b->id,
                'booking_number' => $b->booking_number,
                'booking_status' => $b->booking_status,
                'pickup_date' => $b->pickup_date instanceof \Carbon\Carbon ? $b->pickup_date->toDateString() : substr((string) $b->pickup_date, 0, 10),
                'pickup_time' => substr((string) $b->pickup_time, 0, 5),
                'return_date' => $b->return_date instanceof \Carbon\Carbon ? $b->return_date->toDateString() : substr((string) $b->return_date, 0, 10),
                'return_time' => substr((string) $b->return_time, 0, 5),
                'vehicle' => [
                    'id' => $b->vehicle?->id,
                    'brand' => $b->vehicle?->brand,
                    'model' => $b->vehicle?->model,
                ],
                'driver_name' => $b->driver_name,
            ];
        })->values();

        return response()->json([
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'bookings' => $items,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $vendorId = $request->user()->id;
        $booking = Booking::with(['vehicle.images', 'vehicle.category', 'vehicle.benefits', 'customer', 'extras', 'amounts', 'payments'])
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

    private function parsePaymentMethods($raw): array
    {
        $list = $raw;
        if (is_string($list)) {
            $decoded = json_decode($list, true);
            $list = is_array($decoded) ? $decoded : [$list];
        }
        if (! is_array($list)) return [];
        $labels = [
            'credit_card' => 'Credit card',
            'debit_card' => 'Debit card',
            'card' => 'Card',
            'cash' => 'Cash',
            'bank_wire' => 'Bank transfer',
            'bank_transfer' => 'Bank transfer',
            'upi' => 'UPI',
            'crypto' => 'Crypto',
            'paypal' => 'PayPal',
            'stripe' => 'Stripe',
        ];
        return array_values(array_filter(array_map(function ($m) use ($labels) {
            $key = strtolower(trim((string) $m));
            if ($key === '') return null;
            return $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
        }, $list)));
    }

    private function fuelPolicyLabel(?string $code): ?string
    {
        if (! $code) return null;
        return match ($code) {
            'full_to_full' => 'Full to full',
            'same_to_same' => 'Same to same',
            'pre_purchase' => 'Pre-purchase',
            default => ucfirst(str_replace('_', ' ', $code)),
        };
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
        $amount = $b->amounts;
        $vehicle = $b->vehicle;
        $benefit = $vehicle?->benefits;

        // Lookup plan features (internal vehicles only — external plans aren't stored locally)
        $planFeatures = [];
        $planDescription = null;
        if ($b->vehicle_id && $b->plan) {
            $vendorPlan = VendorVehiclePlan::query()
                ->where('vehicle_id', $b->vehicle_id)
                ->where('plan_type', $b->plan)
                ->first();
            if ($vendorPlan) {
                $rawFeatures = $vendorPlan->features;
                // Some rows are double-JSON-encoded (cast decodes once, value is still a JSON string).
                if (is_string($rawFeatures)) {
                    $decoded = json_decode($rawFeatures, true);
                    $rawFeatures = is_array($decoded) ? $decoded : [];
                }
                $planFeatures = is_array($rawFeatures)
                    ? array_values(array_filter(array_map(fn ($v) => trim((string) $v), $rawFeatures), fn ($v) => $v !== ''))
                    : [];
                $planDescription = $vendorPlan->plan_description;
            }
        }

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
                'description' => $planDescription,
                'features' => $planFeatures,
                'protection_code' => $metadata['protection_code'] ?? null,
                'protection_amount' => isset($metadata['protection_amount']) ? (float) $metadata['protection_amount'] : null,
                'package' => $metadata['package'] ?? null,
                'selected_deposit_type' => $metadata['selected_deposit_type'] ?? null,
                'mandatory_amount' => isset($metadata['mandatory_amount']) ? (float) $metadata['mandatory_amount'] : null,
                'fuel_policy' => $this->fuelPolicyLabel($vehicle?->fuel_policy ?? ($metadata['fuel_policy'] ?? null)),
            ],
            'vehicle_details' => [
                'transmission' => $vehicle?->transmission,
                'fuel' => $vehicle?->fuel,
                'seating_capacity' => $vehicle?->seating_capacity,
                'luggage_capacity' => $vehicle?->luggage_capacity,
                'mileage' => $vehicle?->mileage !== null ? (float) $vehicle->mileage : null,
                'mileage_unit' => 'km/litre',
                'security_deposit' => $vehicle?->security_deposit !== null ? (float) $vehicle->security_deposit : null,
                'pickup_instructions' => $vehicle?->pickup_instructions,
                'dropoff_instructions' => $vehicle?->dropoff_instructions,
                'guidelines' => $vehicle?->guidelines,
                'rental_policy' => $vehicle?->rental_policy,
                'terms_policy' => $vehicle?->terms_policy,
                'minimum_driver_age' => $benefit?->minimum_driver_age ?? null,
                'cancellation_available' => (bool) ($vehicle?->cancellation_available ?? false),
                'limited_km_per_day' => (bool) ($benefit?->limited_km_per_day ?? false),
                'limited_km_per_day_range' => $benefit?->limited_km_per_day_range !== null ? (float) $benefit->limited_km_per_day_range : null,
                'price_per_km_per_day' => $benefit?->price_per_km_per_day !== null ? (float) $benefit->price_per_km_per_day : null,
                'cancellation_fee_per_day' => $benefit?->cancellation_fee_per_day !== null ? (float) $benefit->cancellation_fee_per_day : null,
                'cancellation_deadline_hours' => $benefit?->cancellation_available_per_day_date,
            ],
            'pickup_payment_summary' => [
                'rental_balance' => $amount?->vendor_total_amount !== null ? (float) $amount->vendor_total_amount : null,
                'security_deposit' => $vehicle?->security_deposit !== null ? (float) $vehicle->security_deposit : null,
                'currency' => $amount?->vendor_currency ?? $b->booking_currency ?? 'EUR',
                'deposit_payment_methods' => $this->parsePaymentMethods($vehicle?->payment_method),
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
