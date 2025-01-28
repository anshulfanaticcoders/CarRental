<?php
namespace App\Http\Controllers;

use App\Models\VendorDocument;
use App\Models\VendorProfile;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use App\Models\User;

class VendorController extends Controller
{
    public function create()
    {
        $user = auth()->user();
        $userProfile = $user->profile;

        return inertia('Auth/VendorRegister', [
            'user' => $user,
            'userProfile' => $userProfile,
        ]);
    }

    public function store(Request $request)
    {
        try {
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
                'driving_license' => $request->hasFile('driving_license') 
                    ? $request->file('driving_license')->store('vendorDocuments', 'public') 
                    : null,
                'passport' => $request->hasFile('passport') 
                    ? $request->file('passport')->store('vendorDocuments', 'public') 
                    : null,
                'passport_photo' => $request->hasFile('passport_photo') 
                    ? $request->file('passport_photo')->store('vendorDocuments', 'public') 
                    : null,
                'status' => 'pending',
            ]);

            // Create or update the vendor profile
            VendorProfile::updateOrCreate(
                ['user_id' => $request->user()->id],
                [
                    'company_name' => $request->company_name,
                    'company_phone_number' => $request->company_phone_number,
                    'company_email' => $request->company_email,
                    'company_address' => $request->company_address,
                    'company_gst_number' => $request->company_gst_number,
                    'status' => 'pending',
                ]
            );

            $user = User::find($request->user()->id);
            $user->role = 'vendor';
            $user->save();

            // Return a JSON response for Inertia
            return redirect(RouteServiceProvider::HOMEPAGE)->with([
                'message' => 'Vendor registration completed successfully!',
                'type' => 'success'
            ]);

        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Vendor Registration Error: ' . $e->getMessage());

            // Return error response
            return back()->with([
                'message' => 'Something went wrong during registration. Please try again.',
                'type' => 'error'
            ])->withErrors([
                'error' => 'Registration failed. Please try again.'
            ]);
        }
    }
}