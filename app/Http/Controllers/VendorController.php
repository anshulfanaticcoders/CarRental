<?php
namespace App\Http\Controllers;

use App\Models\VendorDocument;
use App\Models\VendorProfile; // Assuming you have a VendorProfile model
use Illuminate\Http\Request;
use App\Models\User;

class VendorController extends Controller
{
    public function store(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'driving_license' => 'nullable|file|mimes:jpg,png,pdf',
            'passport' => 'nullable|file|mimes:jpg,png,pdf',
            'passport_photo' => 'nullable|file|mimes:jpg,png',
            'company_name' => 'required|string|max:255',
            'company_phone_number' => 'required|string|max:15',
            'company_email' => 'required|email|max:255',
            'company_address' => 'required|string|max:255',
            'company_gst_number' => 'required|string|max:15',
        ]);

        // Handle file uploads
        $vendorDocument = VendorDocument::create([
            'user_id' => $request->user()->id,
            'driving_license' => $request->hasFile('driving_license') ? $request->file('driving_license')->store('vendorDocuments', 'public') : null,
            'passport' => $request->hasFile('passport') ? $request->file('passport')->store('vendorDocuments', 'public') : null,
            'passport_photo' => $request->hasFile('passport_photo') ? $request->file('passport_photo')->store('vendorDocuments', 'public') : null,
        ]);

        // Create or update the vendor profile
        VendorProfile::updateOrCreate(
            ['user_id' => $request->user()->id], 
            $request->only(['company_name', 'company_phone_number', 'company_email', 'company_address', 'company_gst_number'])
        );

        $user = User::find($request->user()->id);
        $user->role = 'vendor';
        $user->save();

        return response()->json(['success' => true, 'message' => 'Vendor documents and profile created successfully.']);
    }
}