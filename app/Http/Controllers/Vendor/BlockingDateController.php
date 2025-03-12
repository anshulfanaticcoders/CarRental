<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlockingDateController extends Controller
{
    public function index(Request $request)
    {
        $vendorId = auth()->id();
        $searchQuery = $request->input('search', '');
    
        // Apply pagination and search to the vehicles query
        $vehicles = Vehicle::where('vendor_id', $vendorId)
            ->when($searchQuery, function ($query, $searchQuery) {
                $query->where(function ($q) use ($searchQuery) {
                    $q->where('brand', 'like', '%' . $searchQuery . '%')
                      ->orWhere('model', 'like', '%' . $searchQuery . '%')
                      ->orWhere('blocking_start_date', 'like', '%' . $searchQuery . '%')
                      ->orWhere('blocking_end_date', 'like', '%' . $searchQuery . '%');
                });
            })
            ->paginate(7); // 10 items per page
    
        return Inertia::render('Vendor/BlockingDates/Index', [
            'vehicles' => $vehicles->items(),
            'pagination' => [
                'current_page' => $vehicles->currentPage(),
                'last_page' => $vehicles->lastPage(),
                'per_page' => $vehicles->perPage(),
                'total' => $vehicles->total(),
            ],
            'filters' => $request->all(),
        ]);
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'vehicle_id' => 'required|exists:vehicles,id',
        'blocking_start_date' => 'required|date',
        'blocking_end_date' => 'required|date|after_or_equal:blocking_start_date',
    ]);

    $vehicle = Vehicle::findOrFail($request->vehicle_id);
    $vehicle->update($validated);

    return response()->json(['message' => 'Blocking dates added successfully.']);
}

public function update(Request $request, $id)
{
    $validated = $request->validate([
        'blocking_start_date' => 'required|date',
        'blocking_end_date' => 'required|date|after_or_equal:blocking_start_date',
    ]);

    $vehicle = Vehicle::where('vendor_id', auth()->id())->findOrFail($id);
    $vehicle->update($validated);

    return response()->json(['message' => 'Blocking dates updated successfully.']);
}

public function destroy($id)
{
    $vehicle = Vehicle::where('vendor_id', auth()->id())->findOrFail($id);
    $vehicle->update([
        'blocking_start_date' => null,
        'blocking_end_date' => null,
    ]);

    return response()->json(['message' => 'Blocking dates removed successfully.']);
}


}