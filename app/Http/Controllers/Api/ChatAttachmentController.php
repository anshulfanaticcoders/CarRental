<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BookingChat;
use App\Models\ChatAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Validation\Rule;

class ChatAttachmentController extends Controller
{
    /**
     * Upload a file for chat attachment.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:' . $this->getMaxFileSize()],
            'booking_chat_id' => ['required', 'exists:booking_chats,id'],
        ]);

        $user = Auth::user();
        $chat = BookingChat::findOrFail($request->booking_chat_id);

        // Check if user is a participant
        if (!$chat->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to upload files to this chat',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $file = $request->file('file');
            $fileType = $this->getFileType($file);
            $fileName = $this->generateFileName($file);
            $filePath = $this->getFilePath($fileType, $fileName);

            // Store the file
            $storedFile = $file->storeAs($filePath, $fileName, 'public');

            if (!$storedFile) {
                throw new \Exception('Failed to store file');
            }

            // Get file information
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();
            $originalName = $file->getClientOriginalName();

            // Process file (generate thumbnail, extract metadata)
            $thumbnailPath = null;
            $metadata = [];

            if ($fileType === ChatAttachment::TYPE_IMAGE) {
                $thumbnailPath = $this->generateImageThumbnail($storedFile, $fileName);
                $metadata = $this->extractImageMetadata($storedFile);
            } elseif ($fileType === ChatAttachment::TYPE_VIDEO) {
                $thumbnailPath = $this->generateVideoThumbnail($storedFile, $fileName);
                $metadata = $this->extractVideoMetadata($storedFile);
            } elseif ($fileType === ChatAttachment::TYPE_AUDIO) {
                $metadata = $this->extractAudioMetadata($storedFile);
            }

            // Create attachment record
            $attachment = ChatAttachment::create([
                'booking_chat_id' => $chat->id,
                'sender_id' => $user->id,
                'file_name' => $fileName,
                'original_name' => $originalName,
                'file_path' => $storedFile,
                'file_type' => $fileType,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'thumbnail_path' => $thumbnailPath,
                'metadata' => $metadata,
                'status' => ChatAttachment::STATUS_COMPLETED,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $attachment->load(['sender.profile']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific attachment.
     */
    public function show($id): JsonResponse
    {
        $user = Auth::user();
        $attachment = ChatAttachment::with(['bookingChat', 'sender.profile'])->findOrFail($id);

        // Check if user is a participant in the chat
        if (!$attachment->bookingChat->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view this attachment',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $attachment,
        ]);
    }

    /**
     * Download an attachment.
     */
    public function download($id): JsonResponse
    {
        $user = Auth::user();
        $attachment = ChatAttachment::findOrFail($id);

        // Check if user is a participant in the chat
        if (!$attachment->bookingChat->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to download this attachment',
            ], 403);
        }

        try {
            $filePath = storage_path('app/public/' . $attachment->file_path);

            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'download_url' => $attachment->getUrl(),
                    'file_name' => $attachment->original_name,
                    'file_size' => $attachment->file_size,
                    'mime_type' => $attachment->mime_type,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to prepare download',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an attachment.
     */
    public function destroy($id): JsonResponse
    {
        $user = Auth::user();
        $attachment = ChatAttachment::findOrFail($id);

        // Check if user owns this attachment
        if ($attachment->sender_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own attachments',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Delete the attachment (this will also delete files via model event)
            $attachment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attachment deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete attachment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get thumbnail for an attachment.
     */
    public function thumbnail($id): JsonResponse
    {
        $user = Auth::user();
        $attachment = ChatAttachment::findOrFail($id);

        // Check if user is a participant in the chat
        if (!$attachment->bookingChat->hasParticipant($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view this thumbnail',
            ], 403);
        }

        if (!$attachment->thumbnail_path) {
            return response()->json([
                'success' => false,
                'message' => 'No thumbnail available for this attachment',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'thumbnail_url' => $attachment->getThumbnailUrl(),
            ],
        ]);
    }

    /**
     * Get upload progress for an attachment.
     */
    public function uploadProgress($id): JsonResponse
    {
        $user = Auth::user();
        $attachment = ChatAttachment::findOrFail($id);

        // Check if user owns this attachment
        if ($attachment->sender_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only check progress for your own attachments',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $attachment->id,
                'status' => $attachment->status,
                'progress' => $attachment->status === ChatAttachment::STATUS_COMPLETED ? 100 :
                               ($attachment->status === ChatAttachment::STATUS_PROCESSING ? 50 : 0),
                'error_message' => $attachment->error_message,
            ],
        ]);
    }

    /**
     * Get allowed file types for upload.
     */
    public function allowedFileTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'allowed_types' => [
                    'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                    'videos' => ['mp4', 'webm', 'mov', 'avi'],
                    'audio' => ['mp3', 'wav', 'ogg', 'm4a'],
                    'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
                ],
                'max_file_sizes' => [
                    'images' => '10MB',
                    'videos' => '100MB',
                    'audio' => '10MB',
                    'documents' => '25MB',
                ],
            ],
        ]);
    }

    /**
     * Determine file type based on mime type.
     */
    private function getFileType($file): string
    {
        $mimeType = $file->getMimeType();

        if (str_starts_with($mimeType, 'image/')) {
            return ChatAttachment::TYPE_IMAGE;
        } elseif (str_starts_with($mimeType, 'video/')) {
            return ChatAttachment::TYPE_VIDEO;
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return ChatAttachment::TYPE_AUDIO;
        } else {
            return ChatAttachment::TYPE_DOCUMENT;
        }
    }

    /**
     * Get maximum file size in bytes.
     */
    private function getMaxFileSize(): int
    {
        // 100MB in kilobytes
        return 102400;
    }

    /**
     * Generate unique file name.
     */
    private function generateFileName($file): string
    {
        $extension = $file->getClientOriginalExtension();
        return Str::uuid() . '.' . $extension;
    }

    /**
     * Get file storage path.
     */
    private function getFilePath($fileType, $fileName): string
    {
        $year = date('Y');
        $month = date('m');

        switch ($fileType) {
            case ChatAttachment::TYPE_IMAGE:
                return "chat-uploads/images/{$year}/{$month}";
            case ChatAttachment::TYPE_VIDEO:
                return "chat-uploads/videos/{$year}/{$month}";
            case ChatAttachment::TYPE_AUDIO:
                return "chat-uploads/audio/{$year}/{$month}";
            default:
                return "chat-uploads/documents/{$year}/{$month}";
        }
    }

    /**
     * Generate thumbnail for images.
     */
    private function generateImageThumbnail($filePath, $fileName): ?string
    {
        try {
            $fullPath = storage_path('app/public/' . $filePath);
            $image = Image::make($fullPath);

            // Create thumbnail
            $thumbnail = $image->fit(300, 300, function ($constraint) {
                $constraint->upsize();
            });

            $thumbnailPath = "chat-uploads/thumbnails/" . $fileName;
            $thumbnail->save(storage_path('app/public/' . $thumbnailPath));

            return $thumbnailPath;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate thumbnail for videos.
     */
    private function generateVideoThumbnail($filePath, $fileName): ?string
    {
        try {
            $fullPath = storage_path('app/public/' . $filePath);
            $thumbnailName = pathinfo($fileName, PATHINFO_FILENAME) . '_thumb.jpg';
            $thumbnailPath = "chat-uploads/thumbnails/" . $thumbnailName;

            // This would require ffmpeg to be installed
            // For now, we'll return null
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract image metadata.
     */
    private function extractImageMetadata($filePath): array
    {
        try {
            $fullPath = storage_path('app/public/' . $filePath);
            $image = Image::make($fullPath);

            return [
                'dimensions' => [
                    'width' => $image->width(),
                    'height' => $image->height(),
                ],
                'orientation' => $image->orientate(),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Extract video metadata.
     */
    private function extractVideoMetadata($filePath): array
    {
        try {
            // This would require ffmpeg to be installed
            return [
                'duration' => null,
                'dimensions' => null,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Extract audio metadata.
     */
    private function extractAudioMetadata($filePath): array
    {
        try {
            // This would require a library like getID3
            return [
                'duration' => null,
                'bitrate' => null,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}
