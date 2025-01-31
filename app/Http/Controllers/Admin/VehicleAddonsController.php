<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingAddon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VehicleAddonsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $addons = BookingAddon::when($search, function ($query, $search) {
            return $query->where(function ($query) use ($search) {
                $query->where('extra_type', 'like', "%{$search}%")
                    ->orWhere('extra_name', 'like', "%{$search}%")
                    ->orWhere('quantity', 'like', "%{$search}%");
            });
        })
            ->paginate(10); // Add pagination
        return Inertia::render('AdminDashboardPages/VehicleAddons/Index', [
            'users' => $addons,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'extra_type' => 'required|string|max:255',
            'extra_name' => 'required|string|max:255',
            'description' => 'required|string',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0'
        ]);

        BookingAddon::create($request->all());

        return redirect()->route('booking-addons.index')->with('success', 'Addon created successfully.');
    }

    public function update(Request $request, BookingAddon $bookingAddon)
    {
        $request->validate([
            'extra_type' => 'required|string|max:255',
            'extra_name' => 'required|string|max:255',
            'description' => 'required|string',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0'
        ]);

        $bookingAddon->update($request->all());

        return redirect()->route('booking-addons.index')->with('success', 'Addon updated successfully.');
    }

    public function destroy(BookingAddon $bookingAddon)
    {
        $bookingAddon->delete();
        return redirect()->route('booking-addons.index')->with('success', 'Addon deleted successfully.');
    }
}
