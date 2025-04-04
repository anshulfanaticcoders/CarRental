<?php
namespace App\Http\Controllers;

use App\Models\VendorDocument;
use App\Models\VendorProfile;
use App\Notifications\VendorRegisteredNotification;
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
                'driving_license_front' => 'nullable|file|mimes:jpg,png,pdf',
                'driving_license_back' => 'nullable|file|mimes:jpg,png,pdf',
                'passport_front' => 'nullable|file|mimes:jpg,png,pdf',
                'passport_back' => 'nullable|file|mimes:jpg,png,pdf',
                'company_name' => 'required|string|max:255',
                'company_phone_number' => 'required|string|max:15',
                'company_email' => 'required|email|max:255',
                'company_address' => 'required|string|max:255',
                'company_gst_number' => 'required|string|max:15',
            ]);

            $userId = auth()->id();

            // Define folder path - use the same folder as in update method
            $folderName = 'vehicle_images';

            // Handle file uploads with the same pattern as update method
            $drivingLicenseFront = null;
            $drivingLicenseBack = null;
            if ($request->hasFile('driving_license_front')) {
                $path = $request->file('driving_license_front')->store($folderName, 'upcloud');
                $drivingLicenseFront = Storage::disk('upcloud')->url($path);
            }
            if ($request->hasFile('driving_license_back')) {
                $path = $request->file('driving_license_back')->store($folderName, 'upcloud');
                $drivingLicenseBack = Storage::disk('upcloud')->url($path);
            }

            

            $passport_front = null;
            if ($request->hasFile('passport_front')) {
                $path = $request->file('passport_front')->store($folderName, 'upcloud');
                $passport_front = Storage::disk('upcloud')->url($path);
            }

            $passport_back = null;
            if ($request->hasFile('passport_back')) {
                $path = $request->file('passport_back')->store($folderName, 'upcloud');
                $passport_back = Storage::disk('upcloud')->url($path);
            }

            // Create the vendor document
            VendorDocument::create([
                'user_id' => $userId,
                'driving_license_front' => $drivingLicenseFront,
                'driving_license_back' => $drivingLicenseBack,
                'passport_front' => $passport_front,
                'passport_back' => $passport_back,
                'status' => 'pending',
            ]);

            // Create the vendor profile
            VendorProfile::create([
                'user_id' => $userId,
                'company_name' => $request->company_name,
                'company_phone_number' => $request->company_phone_number,
                'company_email' => $request->company_email,
                'company_address' => $request->company_address,
                'company_gst_number' => $request->company_gst_number,
                'status' => 'pending',
            ]);

            // Update user role to vendor
            $user = User::find($userId);
            $user->role = 'vendor';
            $user->save();

            // Notify the admin
            $admin = User::where('email', 'anshul@fanaticcoders.com')->first(); // Replace with your admin email
            if ($admin) {
                $admin->notify(new VendorRegisteredNotification($vendorProfile, $user));
            }

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
                'driving_license_front' => 'nullable|file|mimes:jpg,png,pdf',
                'driving_license_back' => 'nullable|file|mimes:jpg,png,pdf',
                'passport_front' => 'nullable|file|mimes:jpg,png,pdf',
                'passport_back' => 'nullable|file|mimes:jpg,png,pdf',
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
            $drivingLicenseFront = $document ? $document->driving_license_front : null;
            $drivingLicenseBack = $document ? $document->driving_license_back : null;
            $passport_front = $document ? $document->passport_front : null;
            $passport_back = $document ? $document->passport_back : null;
            // $passportPhoto = $document ? $document->passport_photo : null;

            if ($request->hasFile('driving_license_front')) {
                if ($document && $document->driving_license_front) {
                    Storage::disk('upcloud')->delete($document->driving_license_front);
                }
                $path = $request->file('driving_license_front')->store($folderName, 'upcloud');
                $drivingLicenseFront = Storage::disk('upcloud')->url($path);
            }
            if ($request->hasFile('driving_license_back')) {
                if ($document && $document->driving_license_back) {
                    Storage::disk('upcloud')->delete($document->driving_license_back);
                }
                $path = $request->file('driving_license_back')->store($folderName, 'upcloud');
                $drivingLicenseBack = Storage::disk('upcloud')->url($path);
            }

            

            if ($request->hasFile('passport_front')) {
                if ($document && $document->passport_front) {
                    Storage::disk('upcloud')->delete($document->passport_front);
                }
                $path = $request->file('passport_front')->store($folderName, 'upcloud');
                $passport_front = Storage::disk('upcloud')->url($path);
            }

            if ($request->hasFile('passport_back')) {
                if ($document && $document->passport_back) {
                    Storage::disk('upcloud')->delete($document->passport_back);
                }
                $path = $request->file('passport_back')->store($folderName, 'upcloud');
                $passport_back = Storage::disk('upcloud')->url($path);
            }
            // Update or create the vendor document
            VendorDocument::updateOrCreate(
                ['user_id' => $userId],
                [
                    'driving_license_front' => $drivingLicenseFront,
                    'driving_license_back' => $drivingLicenseBack,
                    'passport_front' => $passport_front,
                    'passport_back' => $passport_back,
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


    public function status()
    {
        $user = auth()->user();

        // Get vendor profile and document
        $vendorProfile = VendorProfile::where('user_id', $user->id)->first();
        $vendorDocument = VendorDocument::where('user_id', $user->id)->first();

        // Determine status (default to pending if no profile found)
        $status = $vendorProfile ? $vendorProfile->status : 'pending';

        return Inertia::render('Vendor/Status/Index', [
            'status' => $status,
            'vendorProfile' => $vendorProfile,
            'vendorDocument' => $vendorDocument,
        ]);
    }


}