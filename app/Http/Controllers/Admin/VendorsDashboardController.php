<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VendorDocument;
use Illuminate\Http\Request;

class VendorsDashboardController extends Controller
{
    public function index()
{
    $users = User::whereHas('vendorProfile')
        ->with(['vendorProfile', 'vendorDocument'])
        ->get();

    return Inertia::render('AdminDashboardPages/Vendors/Index', [
        'users' => $users,
    ]);
}

    public function updateStatus(Request $request, VendorProfile $vendorProfile)
    {
        // Validate the request
        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        // Update the vendor status
        $vendorProfile->update([
            'status' => $request->status,
        ]);

        return redirect()->route('vendors.index')->with('success', 'Vendor');
    }
}
