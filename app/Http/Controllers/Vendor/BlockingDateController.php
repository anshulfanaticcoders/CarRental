<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\BlockingDate;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlockingDateController extends Controller
{
    public function index(Request $request)
    {
        $vendorId = auth()->id();
        $searchQuery = $request->input('search', '');
    
        $vehicles = Vehicle::where('vendor_id', $vendorId)
            ->with('blockings','bookings') // Fetch associated blocking dates
            ->when($searchQuery, function ($query, $searchQuery) {
                $query->where(function ($q) use ($searchQuery) {
                    $q->where('brand', 'like', '%' . $searchQuery . '%')
                      ->orWhere('model', 'like', '%' . $searchQuery . '%');
                });
            })
            ->paginate(7);
    
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
    
        BlockingDate::create($validated);
    
        return response()->json(['message' => 'Blocking date added successfully.']);
    }
    

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'blocking_start_date' => 'required|date',
            'blocking_end_date' => 'required|date|after_or_equal:blocking_start_date',
        ]);
    
        $blockingDate = BlockingDate::findOrFail($id);
        $blockingDate->update($validated);
    
        return response()->json(['message' => 'Blocking date updated successfully.']);
    }
    

    public function destroy($id)
    {
        $blockingDate = BlockingDate::findOrFail($id);
        $blockingDate->delete();
    
        return response()->json(['message' => 'Blocking date removed successfully.']);
    }
    


}