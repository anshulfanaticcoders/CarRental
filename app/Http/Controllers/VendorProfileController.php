<?php

namespace App\Http\Controllers;

use App\Models\VendorProfile;
use Illuminate\Http\Request;

class VendorProfileController extends Controller
{
    public function create()
    {
        // Fetch user data if needed
        $user = auth()->user();

        return inertia('Auth/VendorProfile', [
            'user' => $user,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_phone_number' => 'required|string|max:15',
            'company_email' => 'required|email|unique:vendor_profiles,company_email',
            'company_address' => 'required|string',
            'company_gst_number' => 'required|string|unique:vendor_profiles,company_gst_number',
        ]);

        VendorProfile::create([
            'user_id' => $request->user()->id,
            'company_name' => $request->company_name,
            'company_phone_number' => $request->company_phone_number,
            'company_email' => $request->company_email,
            'company_address' => $request->company_address,
            'company_gst_number' => $request->company_gst_number,
        ]);

        return response()->json(['success' => true, 'message' => 'Vendor profile created successfully.']);
    }
}
