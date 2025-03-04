<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\UserDocument;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class UserDocumentController extends Controller
{
    // Display all user documents
    public function index(): Response
    {
        $documents = UserDocument::where('user_id', Auth::id())->get();

        return Inertia::render('Profile/Documents/Index', [
            'documents' => $documents
        ]);
    }

    // Show create document form
    public function create(): Response
    {
        return Inertia::render('Profile/Documents/Create');
    }

    // Store a new document
    public function store(Request $request)
    {
        $request->validate([
            'document_type' => 'required|in:id_proof,address_proof,driving_license',
            'document_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $file = $request->file('document_file');
        $path = $file->store('documents', 'public');

        UserDocument::create([
            'user_id' => Auth::id(),
            'document_type' => $request->document_type,
            'document_number' => Str::upper(Str::random(10)),
            'document_file' => $path,
            'verification_status' => 'pending',
        ]);

        return redirect()->route('user.documents.index')->with('success', 'Document uploaded successfully.');
    }

    // Show edit document form
    public function edit(UserDocument $document): Response
    {
        $this->authorize('update', $document);

        return Inertia::render('Profile/Documents/Edit', [
            'document' => $document
        ]);
    }

    // Update an existing document
    public function update(Request $request, UserDocument $document)
    {
        $this->authorize('update', $document);

        $request->validate([
            'document_file' => 'file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        if ($request->hasFile('document_file')) {
            Storage::disk('public')->delete($document->document_file);
            $path = $request->file('document_file')->store('documents', 'public');
            $document->update([
                'document_file' => $path,
                'verification_status' => 'pending'
            ]);
        }

        return redirect()->route('user.documents.index')->with('success', 'Document updated successfully.');
    }

    // Delete a document
    public function destroy(UserDocument $document)
    {
        $this->authorize('delete', $document);

        Storage::disk('public')->delete($document->document_file);
        $document->delete();

        return redirect()->route('user.documents.index')->with('success', 'Document deleted successfully.');
    }

    // Bulk upload documents
    public function bulkUpload(Request $request)
    {
        $request->validate([
            'document_type_*' => 'required|in:id_proof,address_proof,driving_license',
            'document_file_*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        foreach ($request->file() as $key => $file) {
            if (strpos($key, 'document_file_') === 0) {
                $index = str_replace('document_file_', '', $key);
                $typeKey = 'document_type_' . $index;

                UserDocument::create([
                    'user_id' => Auth::id(),
                    'document_type' => $request->$typeKey,
                    'document_number' => Str::upper(Str::random(10)),
                    'document_file' => $file->store('documents', 'public'),
                    'verification_status' => 'pending',
                ]);
            }
        }

        return redirect()->route('user.documents.index')->with('success', 'Documents uploaded successfully.');
    }
}