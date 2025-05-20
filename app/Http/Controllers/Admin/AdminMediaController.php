<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminMediaController extends Controller
{
    protected $diskName = 'upcloud'; // Configurable disk name
    protected $mediaDirectory = 'my_media'; // As requested by user

    /**
     * Display a listing of the media.
     */
    public function index(Request $request)
    {
        $query = Media::where('disk', $this->diskName)
                      // ->where('directory', $this->mediaDirectory) // Optional: filter by directory if needed
                      ->latest();

        // Paginate and transform for both Inertia and JSON responses
        $mediaItems = $query->paginate(15)->through(fn ($item) => [
            'id' => $item->id,
            'filename' => $item->filename,
            'title' => $item->title,
            'url' => $item->public_url, // Use the accessor from the model
            'mime_type' => $item->mime_type,
            'size' => round($item->size / 1024, 2) . ' KB', // Format size
            'created_at' => $item->created_at->toFormattedDateString(),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['mediaItems' => $mediaItems]);
        }

        return Inertia::render('AdminDashboardPages/Media/Index', [
            'mediaItems' => $mediaItems,
            'uploadDisk' => $this->diskName,
            'uploadDirectory' => $this->mediaDirectory,
        ]);
    }

    /**
     * Store a newly created media file in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|file|mimes:jpg,jpeg,png,gif,svg,webp|max:5120', // Max 5MB per file
            // Title handling might need to be per file if desired, or a generic one if not provided per file.
            // For simplicity, we'll use the original filename as title if a specific title array isn't sent.
        ]);

        $uploadedMedia = [];
        $errors = [];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $index => $file) {
                try {
                    $originalFilename = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    // Create a unique filename to avoid conflicts
                    $filename = Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)) . '-' . time() . '-' . Str::random(5) . '.' . $extension;
                    
                    // Store the file
                    $path = $file->storeAs($this->mediaDirectory, $filename, $this->diskName);

                    if (!$path) {
                        $errors[] = "Could not upload file: {$originalFilename}";
                        continue;
                    }

                    $publicUrl = Storage::disk($this->diskName)->url($path);

                    // Create media record
                    $media = Media::create([
                        'user_id' => auth()->id(),
                        'disk' => $this->diskName,
                        'directory' => $this->mediaDirectory,
                        'filename' => $filename,
                        'extension' => $extension,
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'url' => $publicUrl,
                        'title' => pathinfo($originalFilename, PATHINFO_FILENAME), // Default title from filename
                    ]);
                    $uploadedMedia[] = $media;
                } catch (\Exception $e) {
                    $errors[] = "Error processing file {$originalFilename}: " . $e->getMessage();
                }
            }
        }

        if (count($errors) > 0 && count($uploadedMedia) === 0) {
            return back()->with('error', 'Failed to upload all files. Errors: ' . implode(', ', $errors));
        }
        if (count($errors) > 0) {
            return back()->with('success', count($uploadedMedia) . ' file(s) uploaded successfully. Some files failed: ' . implode(', ', $errors));
        }

        return back()->with('success', count($uploadedMedia) . ' file(s) uploaded successfully.');
    }

    /**
     * Remove the specified media file from storage.
     */
    public function destroy(Media $medium) // Route model binding
    {
        // Ensure the media item belongs to the configured disk and directory (optional check)
        if ($medium->disk !== $this->diskName) {
             return back()->with('error', 'Invalid media item for deletion.');
        }

        // Delete file from storage
        $filePath = ($medium->directory ? $medium->directory . '/' : '') . $medium->filename;
        if (Storage::disk($this->diskName)->exists($filePath)) {
            Storage::disk($this->diskName)->delete($filePath);
        }

        // Delete record from database
        $medium->delete();

        return back()->with('success', 'Media deleted successfully.');
    }
}
