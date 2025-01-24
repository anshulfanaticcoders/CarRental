<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingAddon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VehicleAddonsController extends Controller
{
    public function index()
    {
        $addons = BookingAddon::paginate(10);
        
        return Inertia::render('AdminDashboardPages/VehicleAddons/Index', [
            'addons' => $addons
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