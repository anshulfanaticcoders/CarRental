<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\UserDocument;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

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

            // Handle file uploads
            $drivingLicenseFront = null;
            $drivingLicenseBack = null;
            $passportFront = null;
            $passportBack = null;

            if ($request->hasFile('driving_license_front')) {
                $path = $request->file('driving_license_front')->store($folderName, 'upcloud');
                $drivingLicenseFront = Storage::disk('upcloud')->url($path);
            }
            if ($request->hasFile('driving_license_back')) {
                $path = $request->file('driving_license_back')->store($folderName, 'upcloud');
                $drivingLicenseBack = Storage::disk('upcloud')->url($path);
            }
            if ($request->hasFile('passport_front')) {
                $path = $request->file('passport_front')->store($folderName, 'upcloud');
                $passportFront = Storage::disk('upcloud')->url($path);
            }
            if ($request->hasFile('passport_back')) {
                $path = $request->file('passport_back')->store($folderName, 'upcloud');
                $passportBack = Storage::disk('upcloud')->url($path);
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

            return redirect()->route('user.documents.index')->with([
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
    public function update(Request $request, UserDocument $document)
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

            // Retain existing URLs if no new file is uploaded
            $drivingLicenseFront = $document->driving_license_front;
            $drivingLicenseBack = $document->driving_license_back;
            $passportFront = $document->passport_front;
            $passportBack = $document->passport_back;

            // Handle file uploads
            if ($request->hasFile('driving_license_front')) {
                if ($document->driving_license_front) {
                    Storage::disk('upcloud')->delete(parse_url($document->driving_license_front, PHP_URL_PATH));
                }
                $path = $request->file('driving_license_front')->store($folderName, 'upcloud');
                $drivingLicenseFront = Storage::disk('upcloud')->url($path);
            }
            if ($request->hasFile('driving_license_back')) {
                if ($document->driving_license_back) {
                    Storage::disk('upcloud')->delete(parse_url($document->driving_license_back, PHP_URL_PATH));
                }
                $path = $request->file('driving_license_back')->store($folderName, 'upcloud');
                $drivingLicenseBack = Storage::disk('upcloud')->url($path);
            }
            if ($request->hasFile('passport_front')) {
                if ($document->passport_front) {
                    Storage::disk('upcloud')->delete(parse_url($document->passport_front, PHP_URL_PATH));
                }
                $path = $request->file('passport_front')->store($folderName, 'upcloud');
                $passportFront = Storage::disk('upcloud')->url($path);
            }
            if ($request->hasFile('passport_back')) {
                if ($document->passport_back) {
                    Storage::disk('upcloud')->delete(parse_url($document->passport_back, PHP_URL_PATH));
                }
                $path = $request->file('passport_back')->store($folderName, 'upcloud');
                $passportBack = Storage::disk('upcloud')->url($path);
            }

            // Update the document record
            $document->update([
                'driving_license_front' => $drivingLicenseFront,
                'driving_license_back' => $drivingLicenseBack,
                'passport_front' => $passportFront,
                'passport_back' => $passportBack,
                'verification_status' => 'pending',
            ]);

            return redirect()->route('user.documents.index')->with([
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
    public function destroy(UserDocument $document)
    {
        try {
            $this->authorize('delete', $document);

            // Delete associated files
            if ($document->driving_license_front) {
                Storage::disk('upcloud')->delete(parse_url($document->driving_license_front, PHP_URL_PATH));
            }
            if ($document->driving_license_back) {
                Storage::disk('upcloud')->delete(parse_url($document->driving_license_back, PHP_URL_PATH));
            }
            if ($document->passport_front) {
                Storage::disk('upcloud')->delete(parse_url($document->passport_front, PHP_URL_PATH));
            }
            if ($document->passport_back) {
                Storage::disk('upcloud')->delete(parse_url($document->passport_back, PHP_URL_PATH));
            }

            $document->delete();

            return redirect()->route('user.documents.index')->with([
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
}