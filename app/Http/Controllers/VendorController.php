<?php
namespace App\Http\Controllers;

use App\Models\VendorDocument;
use App\Models\VendorProfile;
use App\Notifications\VendorRegisteredNotification;
use App\Notifications\VendorRegisteredUserConfirmation;
use Illuminate\Support\Facades\Notification;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
                // User profile validation
                'phone' => 'required|string|max:20|regex:/^[+]?[\d\s\-\(\)]+$/',
                'address' => 'required|string|min:5|max:500',
            ]);

            $userId = auth()->id();

            // Update UserProfile with changed data
            $user = User::find($userId);
            $userProfile = $user->profile;

            if ($userProfile) {
                $userProfile->update([
                    'phone' => $request->phone,
                    'address_line1' => $request->address,
                ]);
            }

            // Update User phone if changed
            if ($user->phone !== $request->phone) {
                $user->update(['phone' => $request->phone]);
            }

            // Define folder path
            $folderName = 'vendor_documents';

            // Handle file uploads with filename preservation and compression
            $drivingLicenseFront = null;
            $drivingLicenseBack = null;
            if ($request->hasFile('driving_license_front')) {
                $drivingLicenseFront = $this->handleDocumentUpload($request->file('driving_license_front'), $folderName);
            }
            if ($request->hasFile('driving_license_back')) {
                $drivingLicenseBack = $this->handleDocumentUpload($request->file('driving_license_back'), $folderName);
            }

            $passport_front = null;
            if ($request->hasFile('passport_front')) {
                $passport_front = $this->handleDocumentUpload($request->file('passport_front'), $folderName);
            }

            $passport_back = null;
            if ($request->hasFile('passport_back')) {
                $passport_back = $this->handleDocumentUpload($request->file('passport_back'), $folderName);
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
            $vendorProfile = VendorProfile::create([
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
            $adminEmail = env('VITE_ADMIN_EMAIL', 'default@admin.com');
            $admin = User::where('email', $adminEmail)->first(); // Replace with your admin email
            if ($admin) {
                $admin->notify(new VendorRegisteredNotification($vendorProfile, $user));
            }

            // Notify the user
            Notification::route('mail', $user->email)
                ->notify(new VendorRegisteredUserConfirmation($vendorProfile, $user));

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
            $folderName = 'vendor_documents';

            // Handle file uploads with proper cleanup and filename preservation
            $drivingLicenseFront = $document ? $document->driving_license_front : null;
            $drivingLicenseBack = $document ? $document->driving_license_back : null;
            $passport_front = $document ? $document->passport_front : null;
            $passport_back = $document ? $document->passport_back : null;

            if ($request->hasFile('driving_license_front')) {
                if ($document && $document->driving_license_front) {
                    $this->deleteOldDocument($document->driving_license_front);
                }
                $drivingLicenseFront = $this->handleDocumentUpload($request->file('driving_license_front'), $folderName);
            }
            if ($request->hasFile('driving_license_back')) {
                if ($document && $document->driving_license_back) {
                    $this->deleteOldDocument($document->driving_license_back);
                }
                $drivingLicenseBack = $this->handleDocumentUpload($request->file('driving_license_back'), $folderName);
            }

            if ($request->hasFile('passport_front')) {
                if ($document && $document->passport_front) {
                    $this->deleteOldDocument($document->passport_front);
                }
                $passport_front = $this->handleDocumentUpload($request->file('passport_front'), $folderName);
            }

            if ($request->hasFile('passport_back')) {
                if ($document && $document->passport_back) {
                    $this->deleteOldDocument($document->passport_back);
                }
                $passport_back = $this->handleDocumentUpload($request->file('passport_back'), $folderName);
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

    /**
     * Handle document upload with image compression and filename preservation
     *
     * @param UploadedFile $file
     * @param string $folderName
     * @return string
     * @throws \Exception
     */
    private function handleDocumentUpload(UploadedFile $file, string $folderName): string
    {
        $fileExtension = strtolower($file->getClientOriginalExtension());

        // Handle PDF files without compression
        if ($fileExtension === 'pdf') {
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $filename = $originalName . '.' . $fileExtension;

            // Handle duplicate filenames
            $counter = 1;
            while (Storage::disk('upcloud')->exists($folderName . '/' . $filename)) {
                $filename = $originalName . $counter . '.' . $fileExtension;
                $counter++;
            }

            $path = $file->storeAs($folderName, $filename, 'upcloud');
            return Storage::disk('upcloud')->url($path);
        }

        // Handle image files with compression
        // Use lower quality for PNG to ensure compression works
        $imageQuality = ($fileExtension === 'png') ? 60 : 85;

        $compressedPath = \App\Helpers\ImageCompressionHelper::compressImage(
            $file,
            $folderName,
            quality: $imageQuality,        // Lower quality for PNG, moderate for JPEG
            maxWidth: 1200,     // Smaller dimensions for file size
            maxHeight: 900
        );

        if (!$compressedPath) {
            throw new \Exception('Image compression failed for: ' . $file->getClientOriginalName());
        }

        return Storage::disk('upcloud')->url($compressedPath);
    }

    /**
     * Delete old document file from storage
     *
     * @param string $fileUrl
     * @return void
     */
    private function deleteOldDocument(string $fileUrl): void
    {
        try {
            $filePath = parse_url($fileUrl, PHP_URL_PATH);
            if ($filePath && Storage::disk('upcloud')->exists($filePath)) {
                Storage::disk('upcloud')->delete($filePath);
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to delete old vendor document: ' . $fileUrl . ' - ' . $e->getMessage());
        }
    }


}