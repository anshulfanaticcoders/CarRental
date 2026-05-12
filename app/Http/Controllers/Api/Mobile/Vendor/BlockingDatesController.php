<?php

namespace App\Http\Controllers\Api\Mobile\Vendor;

use App\Http\Controllers\Controller;
use App\Models\BlockingDate;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockingDatesController extends Controller
{
    public function index(Request $request, int $vehicleId): JsonResponse
    {
        $vendorId = $request->user()->id;
        $vehicle = Vehicle::where('id', $vehicleId)->where('vendor_id', $vendorId)->first();
        if (! $vehicle) {
            return response()->json(['message' => 'Vehicle not found.'], 404);
        }

        $items = BlockingDate::where('vehicle_id', $vehicleId)
            ->orderBy('blocking_start_date')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'start' => $b->blocking_start_date,
                'end' => $b->blocking_end_date,
            ])
            ->values();

        return response()->json(['blocking_dates' => $items]);
    }

    public function store(Request $request, int $vehicleId): JsonResponse
    {
        $validated = $request->validate([
            'blocking_start_date' => ['required', 'date_format:Y-m-d'],
            'blocking_end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:blocking_start_date'],
        ]);

        $vendorId = $request->user()->id;
        $vehicle = Vehicle::where('id', $vehicleId)->where('vendor_id', $vendorId)->first();
        if (! $vehicle) {
            return response()->json(['message' => 'Vehicle not found.'], 404);
        }

        $block = BlockingDate::create([
            'vehicle_id' => $vehicleId,
            'blocking_start_date' => $validated['blocking_start_date'],
            'blocking_end_date' => $validated['blocking_end_date'],
        ]);

        return response()->json([
            'message' => 'Blocking date added.',
            'blocking_date' => [
                'id' => $block->id,
                'start' => $block->blocking_start_date,
                'end' => $block->blocking_end_date,
            ],
        ], 201);
    }

    public function destroy(Request $request, int $vehicleId, int $id): JsonResponse
    {
        $vendorId = $request->user()->id;
        $vehicle = Vehicle::where('id', $vehicleId)->where('vendor_id', $vendorId)->first();
        if (! $vehicle) {
            return response()->json(['message' => 'Vehicle not found.'], 404);
        }

        $block = BlockingDate::where('id', $id)->where('vehicle_id', $vehicleId)->first();
        if (! $block) {
            return response()->json(['message' => 'Blocking date not found.'], 404);
        }

        $block->delete();

        return response()->json(['message' => 'Blocking date removed.']);
    }
}
