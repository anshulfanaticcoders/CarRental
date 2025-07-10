<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageCompressionHelper
{
    /**
     * Compress an image using GD Library and save it.
     *
     * @param UploadedFile $imageFile The uploaded image file.
     * @param string $destinationPath The path where the compressed image should be saved (e.g., 'avatars/compressed_image.jpg').
     * @param int $quality The compression quality (0-100 for JPEG, 0-9 for PNG).
     * @param int|null $maxWidth Optional: Maximum width for the image.
     * @param int|null $maxHeight Optional: Maximum height for the image.
     * @return string|false The URL of the compressed image, or false on failure.
     */
    public static function compressImage(UploadedFile $imageFile, string $folderName, int $quality = 80, ?int $maxWidth = null, ?int $maxHeight = null)
    {
        $imagePath = $imageFile->getPathname();
        $imageMime = $imageFile->getMimeType();
        $imageExtension = $imageFile->getClientOriginalExtension();

        list($width, $height) = getimagesize($imagePath);

        // Calculate new dimensions if maxWidth or maxHeight are provided
        $newWidth = $width;
        $newHeight = $height;

        if ($maxWidth && $width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) ($height * ($newWidth / $width));
        }

        if ($maxHeight && $newHeight > $maxHeight) {
            $newHeight = $maxHeight;
            $newWidth = (int) ($newWidth * ($newHeight / $height));
        }

        // Create a new true color image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        switch ($imageMime) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($imagePath);
                // Preserve transparency for PNG
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($imagePath);
                break;
            default:
                return false; // Unsupported image type
        }

        if (!$sourceImage) {
            return false;
        }

        // Resize and copy image
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Generate a unique file name
        $fileName = uniqid('compressed_') . '.' . $imageExtension;
        $tempPath = tempnam(sys_get_temp_dir(), 'compressed_image_') . '.' . $imageExtension;

        // Save the compressed image to a temporary file
        switch ($imageMime) {
            case 'image/jpeg':
                imagejpeg($newImage, $tempPath, $quality);
                break;
            case 'image/png':
                // PNG quality is 0-9, where 0 is no compression and 9 is max compression
                // We map 0-100 to 9-0 for consistency with JPEG quality
                $pngQuality = (int) round((100 - $quality) / 100 * 9);
                imagepng($newImage, $tempPath, $pngQuality);
                break;
            case 'image/gif':
                imagegif($newImage, $tempPath);
                break;
        }

        // Destroy the image resources
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        // Store the temporary file to the desired disk
        $path = Storage::disk('upcloud')->putFileAs($folderName, new UploadedFile($tempPath, $fileName, $imageMime, null, true), $fileName, 'public');
        
        // Delete the temporary file
        unlink($tempPath);

        if ($path) {
            return $path; // Return the relative path, not the full URL
        }

        return false;
    }
}
