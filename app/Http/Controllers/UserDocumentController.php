<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserDocumentController extends Controller
{
    private function generateUniqueDocumentNumber(): string
    {
        do {
            $letters1 = Str::upper(Str::random(3));
            $numbers = rand(100, 999);
            $letters2 = Str::upper(Str::random(3));
            $documentNumber = $letters1 . $numbers . $letters2;
        } while (UserDocument::where('document_number', $documentNumber)->exists());

        return $documentNumber;
    }

    public function uploadDocuments(Request $request)
    {
        $request->validate([
            'driving_license' => 'nullable|file|mimes:jpeg,png,pdf|max:2048',
            'id_proof' => 'nullable|file|mimes:jpeg,png,pdf|max:2048',
            'address_proof' => 'nullable|file|mimes:jpeg,png,pdf|max:2048',
        ]);

        $user = auth()->user();

        foreach (['driving_license', 'id_proof', 'address_proof'] as $type) {
            if ($request->hasFile($type)) {
                $file = $request->file($type);
                $path = $file->store('documents', 'public');

                // Find existing document
                $existingDocument = UserDocument::where([
                    'user_id' => $user->id,
                    'document_type' => $type
                ])->first();

                // Get document number (either existing or generate new)
                $documentNumber = $existingDocument?->document_number ?? $this->generateUniqueDocumentNumber();

                UserDocument::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'document_type' => $type
                    ],
                    [
                        'document_file' => $path,
                        'verification_status' => 'pending',
                        'document_number' => $documentNumber
                    ]
                );
            }
        }

        return redirect()->back()->with('success', 'Documents uploaded successfully.');
    }
    public function getUserDocuments()
    {
        $documents = UserDocument::where('user_id', 4) // Dynamically pass user ID
            ->get();
    
        return response()->json($documents);
    }

}