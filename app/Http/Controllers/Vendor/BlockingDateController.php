<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlockingDateController extends Controller
{
    public function index()
    {
        $vendorId = auth()->id();
        $vehicles = Vehicle::where('vendor_id', $vendorId)->get();

        return Inertia::render('Vendor/BlockingDates/Index', [
            'vehicles' => $vehicles,
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

        return redirect()->route('vendor.blocking-dates.index')
            ->with('success', 'Blocking dates added successfully.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'blocking_start_date' => 'required|date',
            'blocking_end_date' => 'required|date|after_or_equal:blocking_start_date',
        ]);

        $vehicle = Vehicle::where('vendor_id', auth()->id())->findOrFail($id);
        $vehicle->update($validated);

        return redirect()->route('vendor.blocking-dates.index')
            ->with('success', 'Blocking dates updated successfully.');
    }

    public function destroy($id)
    {
        $vehicle = Vehicle::where('vendor_id', auth()->id())->findOrFail($id);
        $vehicle->update([
            'blocking_start_date' => null,
            'blocking_end_date' => null,
        ]);

        return redirect()->route('vendor.blocking-dates.index')
            ->with('success', 'Blocking dates removed successfully.');
    }
}