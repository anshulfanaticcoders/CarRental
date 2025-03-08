<?php
namespace App\Http\Controllers;

use App\Models\VendorDocument;
use App\Models\VendorProfile;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

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
                    ? Storage::disk('upcloud')->putFile('vendorDocuments', $request->file('driving_license')) 
                    : null,
                'passport' => $request->hasFile('passport') 
                    ? Storage::disk('upcloud')->putFile('vendorDocuments', $request->file('passport')) 
                    : null,
                'passport_photo' => $request->hasFile('passport_photo') 
                    ? Storage::disk('upcloud')->putFile('vendorDocuments', $request->file('passport_photo')) 
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
    
    // Add index method to display vendor documents
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Get the vendor document with related vendor profile
        $document = VendorDocument::where('user_id', $user->id)
            ->first();
            
        $vendorProfile = VendorProfile::where('user_id', $user->id)
            ->first();
            
        // Combine document and profile data
        $documentData = null;
        if ($document) {
            $documentData = $document->toArray();
            $documentData['vendor_profile'] = $vendorProfile ? $vendorProfile->toArray() : null;
        }
        
        return Inertia::render('Vendor/Documents/Index', [
            'document' => $documentData,
        ]);
    }
    
    // Add update method to update vendor documents
    public function update(Request $request)
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

        $userId = auth()->id();
        $document = VendorDocument::where('user_id', $userId)->first();

        // Define folder path
        $folderName = 'vehicle_images';

        // Handle file uploads
        $drivingLicense = $document ? $document->driving_license : null;
        $passport = $document ? $document->passport : null;
        $passportPhoto = $document ? $document->passport_photo : null;

        if ($request->hasFile('driving_license')) {
            if ($document && $document->driving_license) {
                Storage::disk('upcloud')->delete($document->driving_license);
            }
            $path = $request->file('driving_license')->store($folderName, 'upcloud');
            $drivingLicense = Storage::disk('upcloud')->url($path);
        }

        if ($request->hasFile('passport')) {
            if ($document && $document->passport) {
                Storage::disk('upcloud')->delete($document->passport);
            }
            $path = $request->file('passport')->store($folderName, 'upcloud');
            $passport = Storage::disk('upcloud')->url($path);
        }

        if ($request->hasFile('passport_photo')) {
            if ($document && $document->passport_photo) {
                Storage::disk('upcloud')->delete($document->passport_photo);
            }
            $path = $request->file('passport_photo')->store($folderName, 'upcloud');
            $passportPhoto = Storage::disk('upcloud')->url($path);
        }

        // Update or create the vendor document
        VendorDocument::updateOrCreate(
            ['user_id' => $userId],
            [
                'driving_license' => $drivingLicense,
                'passport' => $passport,
                'passport_photo' => $passportPhoto,
                'status' => 'pending',
            ]
        );

        // Update the vendor profile
        VendorProfile::updateOrCreate(
            ['user_id' => $userId],
            [
                'company_name' => $request->company_name,
                'company_phone_number' => $request->company_phone_number,
                'company_email' => $request->company_email,
                'company_address' => $request->company_address,
                'company_gst_number' => $request->company_gst_number,
                'status' => 'pending',
            ]
        );

        return back()->with([
            'message' => 'Vendor documents updated successfully!',
            'type' => 'success'
        ]);

    } catch (\Exception $e) {
        \Log::error('Vendor Document Update Error: ' . $e->getMessage());

        return back()->with([
            'message' => 'Something went wrong during update. Please try again.',
            'type' => 'error'
        ])->withErrors([
            'error' => 'Update failed. Please try again.'
        ]);
    }
}

}