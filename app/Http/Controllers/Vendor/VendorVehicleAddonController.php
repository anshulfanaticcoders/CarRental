<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorVehicleAddon;
use Illuminate\Http\Request;

class VendorVehicleAddonController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:users,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'addon_id' => 'required|exists:booking_addons,id',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'description' => 'required|string',
            'extra_type' => 'required|string', 
            'extra_name' => 'required|string', 
        ]);

        VendorVehicleAddon::create($request->all());

        return response()->json(['message' => 'Addon added successfully.'], 201);
    }

    public function index(Request $request)
    {
        $addons = VendorVehicleAddon::with('addon')
            ->where('vendor_id', $request->user()->id)
            ->get();

        return response()->json($addons);
    }

}
