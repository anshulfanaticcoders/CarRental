<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Helpers\ImageCompressionHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VendorDocument;
use App\Models\VendorProfile;
use App\Notifications\VendorRegisteredNotification;
use App\Notifications\VendorRegisteredUserConfirmation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class VendorRegistrationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'passport_front' => 'required|file|mimes:jpg,jpeg,png|max:5120',
            'passport_back' => 'required|file|mimes:jpg,jpeg,png|max:5120',
            'company_name' => 'required|string|max:255',
            'company_phone_number' => 'required|string|max:20',
            'company_email' => 'required|email|max:255',
            'company_address' => 'required|string|max:255',
            'company_gst_number' => 'required|string|max:32',
            'phone' => 'required|string|max:20|regex:/^[+]?[\d\s\-\(\)]+$/',
            'address' => 'required|string|min:5|max:500',
        ]);

        $user = $request->user();

        try {
            if ($user->profile) {
                $user->profile->update([
                    'phone' => $request->phone,
                    'address_line1' => $request->address,
                ]);
            }
            if ($user->phone !== $request->phone) {
                $user->update(['phone' => $request->phone]);
            }

            $folderName = 'vendor_documents';
            $passportFront = $this->uploadDocument($request->file('passport_front'), $folderName);
            $passportBack = $this->uploadDocument($request->file('passport_back'), $folderName);

            VendorDocument::create([
                'user_id' => $user->id,
                'passport_front' => $passportFront,
                'passport_back' => $passportBack,
                'status' => 'pending',
            ]);

            $vendorProfile = VendorProfile::create([
                'user_id' => $user->id,
                'company_name' => $request->company_name,
                'company_phone_number' => $request->company_phone_number,
                'company_email' => $request->company_email,
                'company_address' => $request->company_address,
                'company_gst_number' => $request->company_gst_number,
                'status' => 'pending',
            ]);

            $user->role = 'vendor';
            $user->save();

            try {
                $adminEmail = env('VITE_ADMIN_EMAIL', 'default@admin.com');
                $admin = User::where('email', $adminEmail)->first();
                if ($admin) {
                    $admin->notify(new VendorRegisteredNotification($vendorProfile, $user));
                }
                Notification::route('mail', $user->email)
                    ->notify(new VendorRegisteredUserConfirmation($vendorProfile, $user));
            } catch (\Throwable $e) {
                Log::warning('Vendor mobile registration notify failed', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'message' => 'Vendor registration submitted. Pending verification.',
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                ],
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Mobile vendor registration failed', [
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Vendor registration failed. Try again.',
            ], 500);
        }
    }

    private function uploadDocument(UploadedFile $file, string $folderName): string
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if ($ext === 'pdf') {
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $filename = $originalName . '.' . $ext;
            $counter = 1;
            while (Storage::disk('upcloud')->exists($folderName . '/' . $filename)) {
                $filename = $originalName . $counter . '.' . $ext;
                $counter++;
            }
            $path = $file->storeAs($folderName, $filename, 'upcloud');
            return Storage::disk('upcloud')->url($path);
        }

        $imageQuality = ($ext === 'png') ? 60 : 85;
        $compressedPath = ImageCompressionHelper::compressImage(
            $file,
            $folderName,
            quality: $imageQuality,
            maxWidth: 1200,
            maxHeight: 900,
        );

        if (! $compressedPath) {
            throw new \RuntimeException('Image compression failed for: ' . $file->getClientOriginalName());
        }

        return Storage::disk('upcloud')->url($compressedPath);
    }
}
