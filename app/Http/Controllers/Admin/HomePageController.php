<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Helpers\ImageCompressionHelper;
use Illuminate\Support\Facades\Storage;

class HomePageController extends Controller
{
    public function index()
    {
        $settings = DB::table('homepage_settings')->pluck('value', 'key');

        // Decode JSON values if necessary
        $settings = $settings->map(function ($value, $key) {
            if ($key === 'hero_banners' || $key === 'hero_slider_config') {
                return json_decode($value, true);
            }
            return $value;
        });

        return Inertia::render('AdminDashboardPages/HomePageSettings', [
            'settings' => $settings,
        ]);
    }

    public function updateHeroImage(Request $request)
    {
        $request->validate([
            'hero_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120', // 5MB max
        ]);

        if ($request->hasFile('hero_image')) {
            $folderName = 'homepage';

            // Generate a unique filename using timestamp to avoid browser caching issues when replacing
            $compressedImageUrl = ImageCompressionHelper::compressImage(
                $request->file('hero_image'),
                $folderName,
                quality: 80,
                maxWidth: 1920,
                maxHeight: 1080
            );

            if ($compressedImageUrl) {
                // Get old image config to delete it
                $oldImage = DB::table('homepage_settings')->where('key', 'hero_image')->value('value');

                if ($oldImage) {
                    try {
                        // Extract relative path from URL if possible
                        // Assuming URL is like: https://bucket.upcloudobjects.com/homepage/filename.jpg
                        // We need to parse it or just use the path if we stored relative path.
                        // But current code stores FULL URL. 
                        // So we need to deduce the path. 
                        // However, ImageCompressionHelper returns the relative path which we wrap with Storage::disk('upcloud')->url().
                        // So to delete, we should ideally store the path, or try to reconstruct it.
                        // Let's assume standard behavior of parse_url or string manipulation.

                        // BUT a safer way if we don't know the exact domain/bucket config structure easily
                        // is to assume the file is in 'homepage/' folder as we set it.
                        // Let's rely on the assumption that valid old images are in the same disk.

                        // Attempt to parse path from full URL
                        $parsedPath = parse_url($oldImage, PHP_URL_PATH);
                        // Removing leading slash if present
                        $relativePath = ltrim($parsedPath, '/');

                        // If the URL contains the bucket name or other prefixes, this might be tricky.
                        // A more robust way might be to save the 'path' in DB as well, but we only have 'value'.
                        // Let's try to delete. If it fails, it fails silently or logs.

                        // NOTE: If usage is S3 compatible, delete expects the key.
                        // If $oldImage is full URL, we need to extract the portion after the domain.

                        if ($relativePath) {
                            Storage::disk('upcloud')->delete($relativePath);
                        }
                    } catch (\Exception $e) {
                        // Log error but continue
                        \Log::error("Failed to delete old hero image: " . $e->getMessage());
                    }
                }

                $fullUrl = Storage::disk('upcloud')->url($compressedImageUrl);

                // Update or Insert into DB
                DB::table('homepage_settings')->updateOrInsert(
                    ['key' => 'hero_image'],
                    [
                        'value' => $fullUrl,
                        'type' => 'image',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                return redirect()->back()->with('success', 'Hero image updated successfully.');
            } else {
                return back()->withErrors(['hero_image' => 'Failed to compress image.']);
            }
        }

        return back()->withErrors(['hero_image' => 'No image file provided.']);
    }
}
