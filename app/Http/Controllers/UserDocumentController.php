<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\UserDocument;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\UploadedFile;

class UserDocumentController extends Controller
{
    // Display user documents
    public function index(): Response
    {
        $document = UserDocument::where('user_id', Auth::id())->first();

        return Inertia::render('Profile/Documents/Index', [
            'document' => $document,
        ]);
    }

    // Show create document form
    public function create(): Response
    {
        return Inertia::render('Profile/Documents/Create');
    }

    // Store new documents
    public function store(Request $request)
    {
        try {
            $request->validate([
                'driving_license_front' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'driving_license_back' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'passport_front' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'passport_back' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            ]);

            $userId = Auth::id();
            $folderName = 'documents';

            // Handle file uploads with image compression
            $drivingLicenseFront = null;
            $drivingLicenseBack = null;
            $passportFront = null;
            $passportBack = null;

            if ($request->hasFile('driving_license_front')) {
                $drivingLicenseFront = $this->handleDocumentUpload($request->file('driving_license_front'), $folderName);
            }
            if ($request->hasFile('driving_license_back')) {
                $drivingLicenseBack = $this->handleDocumentUpload($request->file('driving_license_back'), $folderName);
            }
            if ($request->hasFile('passport_front')) {
                $passportFront = $this->handleDocumentUpload($request->file('passport_front'), $folderName);
            }
            if ($request->hasFile('passport_back')) {
                $passportBack = $this->handleDocumentUpload($request->file('passport_back'), $folderName);
            }

            // Create or update the document record
            UserDocument::updateOrCreate(
                ['user_id' => $userId],
                [
                    'driving_license_front' => $drivingLicenseFront,
                    'driving_license_back' => $drivingLicenseBack,
                    'passport_front' => $passportFront,
                    'passport_back' => $passportBack,
                    'verification_status' => 'pending',
                ]
            );

            return redirect()->route('user.documents.index', ['locale' => app()->getLocale()])->with([
                'message' => 'Documents uploaded successfully!',
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            \Log::error('Document Upload Error: ' . $e->getMessage());
            return back()->with([
                'message' => 'Something went wrong during upload. Please try again.',
                'type' => 'error',
            ])->withErrors(['error' => 'Upload failed. Please try again.']);
        }
    }

    // Show edit document form
    public function edit(UserDocument $document): Response
    {
        $this->authorize('update', $document);

        return Inertia::render('Profile/Documents/Edit', [
            'document' => $document,
        ]);
    }

    // Update existing documents
    public function update(Request $request, $locale, UserDocument $document)
    {
        try {
            $this->authorize('update', $document);

            $request->validate([
                'driving_license_front' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'driving_license_back' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'passport_front' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'passport_back' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            ]);

            $folderName = 'documents';
            $dataToUpdate = [];
            $fields = ['driving_license_front', 'driving_license_back', 'passport_front', 'passport_back'];

            foreach ($fields as $field) {
                $currentFileUrl = $document->$field; // Get current URL from the model instance
                $newFileUrl = $currentFileUrl; // Default to current URL

                if ($request->hasFile($field)) {
                    // New file uploaded - delete old file and upload new compressed file
                    if ($currentFileUrl) {
                        $this->deleteOldDocument($currentFileUrl);
                    }
                    $newFileUrl = $this->handleDocumentUpload($request->file($field), $folderName);
                } elseif ($request->exists($field) && ($request->input($field) === '' || $request->input($field) === null) && $currentFileUrl) {
                    // Field was sent as an empty string or is null after validation (due to nullable), indicating removal
                    $this->deleteOldDocument($currentFileUrl);
                    $newFileUrl = null;
                }
                // If field not in request or not an empty string/null, $newFileUrl remains $currentFileUrl (no change)
                $dataToUpdate[$field] = $newFileUrl;
            }
            
            $dataToUpdate['verification_status'] = 'pending'; // Always reset to pending on update

            // Update the document record
            $document->update($dataToUpdate);

            return redirect()->route('user.documents.index', ['locale' => app()->getLocale()])->with([
                'message' => 'Documents updated successfully!',
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            \Log::error('Document Update Error: ' . $e->getMessage());
            return back()->with([
                'message' => 'Something went wrong during update. Please try again.',
                'type' => 'error',
            ])->withErrors(['error' => 'Update failed. Please try again.']);
        }
    }

    // Delete a document
    public function destroy($locale, UserDocument $document)
    {
        try {
            $this->authorize('delete', $document);

            // Delete associated files
            if ($document->driving_license_front) {
                $this->deleteOldDocument($document->driving_license_front);
            }
            if ($document->driving_license_back) {
                $this->deleteOldDocument($document->driving_license_back);
            }
            if ($document->passport_front) {
                $this->deleteOldDocument($document->passport_front);
            }
            if ($document->passport_back) {
                $this->deleteOldDocument($document->passport_back);
            }

            $document->delete();

            return redirect()->route('user.documents.index', ['locale' => app()->getLocale()])->with([
                'message' => 'Document deleted successfully!',
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            \Log::error('Document Deletion Error: ' . $e->getMessage());
            return back()->with([
                'message' => 'Something went wrong during deletion. Please try again.',
                'type' => 'error',
            ]);
        }
    }

    /**
     * Handle document upload with image compression
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
            $path = $file->store($folderName, 'upcloud');
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
            \Log::warning('Failed to delete old document: ' . $fileUrl . ' - ' . $e->getMessage());
        }
    }
}
