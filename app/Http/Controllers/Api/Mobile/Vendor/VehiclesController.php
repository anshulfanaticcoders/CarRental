<?php

namespace App\Http\Controllers\Api\Mobile\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehiclesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vendorId = $request->user()->id;
        $vendorCurrency = $this->vendorCurrency($request);
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:all,available,rented,maintenance,unavailable'],
        ]);
        $status = $validated['status'] ?? 'all';

        $host = rtrim($request->getSchemeAndHttpHost(), '/');
        $absolutize = function (?string $path) use ($host): ?string {
            if (! $path) return null;
            if (preg_match('#^https?://#i', $path)) return $path;
            return $host.'/'.ltrim($path, '/');
        };

        // Derive "rented" from active bookings — vehicle is treated as rented when it has
        // any booking in confirmed/active status whose return_date hasn't passed.
        $today = now()->toDateString();
        $rentedVehicleIds = Booking::query()
            ->whereHas('vehicle', fn ($q) => $q->where('vendor_id', $vendorId))
            ->whereIn('booking_status', ['confirmed', 'active'])
            ->whereDate('return_date', '>=', $today)
            ->pluck('vehicle_id')
            ->unique()
            ->all();
        $rentedSet = array_flip($rentedVehicleIds);

        $vehicles = Vehicle::with(['images', 'category'])
            ->where('vendor_id', $vendorId)
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        $derive = function (Vehicle $v) use ($rentedSet): string {
            $raw = strtolower((string) ($v->status ?? 'available'));
            if (isset($rentedSet[$v->id]) && in_array($raw, ['available', 'rented'], true)) {
                return 'rented';
            }
            return $raw;
        };

        $items = $vehicles
            ->map(function (Vehicle $v) use ($absolutize, $vendorCurrency, $derive) {
                $first = $v->images->firstWhere('image_type', 'primary') ?? $v->images->first();
                $image = null;
                if ($first) {
                    $image = ! empty($first->image_url)
                        ? $absolutize($first->image_url)
                        : (! empty($first->image_path) ? $absolutize('storage/'.ltrim($first->image_path, '/')) : null);
                }
                return [
                    'id' => $v->id,
                    'brand' => $v->brand,
                    'model' => $v->model,
                    'status' => $derive($v),
                    'category' => $v->category?->name,
                    'city' => $v->city,
                    'country' => $v->country,
                    'price_per_day' => $v->price_per_day !== null ? (float) $v->price_per_day : null,
                    'currency' => $vendorCurrency,
                    'image' => $image,
                    'transmission' => $v->transmission,
                    'fuel' => $v->fuel,
                    'seating_capacity' => $v->seating_capacity,
                    'created_at' => $v->created_at?->toIso8601String(),
                ];
            })
            ->filter(fn ($item) => $status === 'all' ? true : $item['status'] === $status);

        $counts = ['all' => 0, 'available' => 0, 'rented' => 0, 'maintenance' => 0, 'unavailable' => 0];
        foreach ($vehicles as $v) {
            $effective = $derive($v);
            $counts['all']++;
            if (isset($counts[$effective])) $counts[$effective]++;
        }

        return response()->json([
            'vehicles' => $items->values(),
            'counts' => $counts,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $vendorId = $request->user()->id;
        $vendorCurrency = $this->vendorCurrency($request);
        $vehicle = Vehicle::with(['images', 'category', 'benefits', 'blockings'])
            ->where('id', $id)
            ->where('vendor_id', $vendorId)
            ->first();

        if (! $vehicle) {
            return response()->json(['message' => 'Vehicle not found.'], 404);
        }

        $host = rtrim($request->getSchemeAndHttpHost(), '/');
        $absolutize = function (?string $path) use ($host): ?string {
            if (! $path) return null;
            if (preg_match('#^https?://#i', $path)) return $path;
            return $host.'/'.ltrim($path, '/');
        };

        $images = $vehicle->images->map(function ($img) use ($absolutize) {
            $url = ! empty($img->image_url)
                ? $absolutize($img->image_url)
                : (! empty($img->image_path) ? $absolutize('storage/'.ltrim($img->image_path, '/')) : null);
            return ['id' => $img->id, 'url' => $url, 'type' => $img->image_type];
        })->filter(fn ($i) => $i['url'])->values();

        $blockings = $vehicle->blockings->map(fn ($b) => [
            'id' => $b->id,
            'start' => $b->blocking_start_date,
            'end' => $b->blocking_end_date,
        ])->values();

        $today = now()->toDateString();
        $isRented = Booking::query()
            ->where('vehicle_id', $vehicle->id)
            ->whereIn('booking_status', ['confirmed', 'active'])
            ->whereDate('return_date', '>=', $today)
            ->exists();
        $rawStatus = strtolower((string) ($vehicle->status ?? 'available'));
        $effectiveStatus = ($isRented && in_array($rawStatus, ['available', 'rented'], true)) ? 'rented' : $rawStatus;

        return response()->json([
            'vehicle' => [
                'id' => $vehicle->id,
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'status' => $effectiveStatus,
                'category' => $vehicle->category?->name,
                'city' => $vehicle->city,
                'country' => $vehicle->country,
                'price_per_day' => $vehicle->price_per_day !== null ? (float) $vehicle->price_per_day : null,
                'currency' => $vendorCurrency,
                'transmission' => $vehicle->transmission,
                'fuel' => $vehicle->fuel,
                'seating_capacity' => $vehicle->seating_capacity,
                'images' => $images,
                'blocking_dates' => $blockings,
            ],
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:available,rented,maintenance,unavailable'],
        ]);

        $vendorId = $request->user()->id;
        $vehicle = Vehicle::where('id', $id)->where('vendor_id', $vendorId)->first();
        if (! $vehicle) {
            return response()->json(['message' => 'Vehicle not found.'], 404);
        }

        $vehicle->status = $validated['status'];
        $vehicle->save();

        return response()->json([
            'message' => 'Vehicle status updated.',
            'status' => $vehicle->status,
        ]);
    }

    private function vendorCurrency(Request $request): string
    {
        $raw = $request->user()->profile?->currency ?? 'EUR';
        $map = [
            '€' => 'EUR',
            '$' => 'USD',
            '£' => 'GBP',
            'د.إ' => 'AED',
            '₹' => 'INR',
            '¥' => 'JPY',
        ];
        return $map[$raw] ?? strtoupper($raw);
    }
}
