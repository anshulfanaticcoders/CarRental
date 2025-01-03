<?php

namespace App\Http\Controllers;

use App\Models\VendorDocument;
use Illuminate\Http\Request;

class VendorDocumentController extends Controller
{
    public function create()
    {
        // Fetch user and user profile data
        $user = auth()->user();
        $userProfile = $user->profile;

        return inertia('Auth/VendorRegister', [
            'user' => $user,
            'userProfile' => $userProfile,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'driving_license' => 'nullable|file|mimes:jpg,png,pdf',
            'passport' => 'nullable|file|mimes:jpg,png,pdf',
            'passport_photo' => 'nullable|file|mimes:jpg,png',
        ]);

        $vendor = VendorDocument::create([
            'user_id' => $request->user()->id,
            'driving_license' => $request->file('driving_license')->store('vendorDocuments', 'public'),
            'passport' => $request->file('passport')->store('vendorDocuments', 'public'),
            'passport_photo' => $request->file('passport_photo')->store('vendorDocuments', 'public'),
        ]);

        return response()->json(['message' => 'Vendor document created successfully.']);
    }
    public function sucess(Request $request)
    {

        return 'register user successfully';
    }
}
