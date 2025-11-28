<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatAttachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_chat_id',
        'sender_id',
        'file_name',
        'original_name',
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
        'thumbnail_path',
        'metadata',
        'status',
        'error_message',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'status' => 'uploading',
    ];

    // File type constants
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_AUDIO = 'audio';
    const TYPE_DOCUMENT = 'document';

    // Status constants
    const STATUS_UPLOADING = 'uploading';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * Get the booking chat that owns the attachment.
     */
    public function bookingChat(): BelongsTo
    {
        return $this->belongsTo(BookingChat::class);
    }

    /**
     * Get the sender (user) that owns the attachment.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Scope a query to only include attachments of a specific type.
     */
    public function ofType($query, $type)
    {
        return $query->where('file_type', $type);
    }

    /**
     * Scope a query to only include completed attachments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope a query to only include attachments that are being processed.
     */
    public function scopeProcessing($query)
    {
        return $query->whereIn('status', [self::STATUS_UPLOADING, self::STATUS_PROCESSING]);
    }

    /**
     * Scope a query to only include failed uploads.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Get the human-readable file size.
     */
    public function getFileSizeAttribute()
    {
        if (!$this->attributes['file_size']) {
            return '0 bytes';
        }

        $bytes = $this->attributes['file_size'];
        $units = ['bytes', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the file extension.
     */
    public function getFileExtensionAttribute()
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }

    /**
     * Check if the attachment is an image.
     */
    public function isImage()
    {
        return $this->file_type === self::TYPE_IMAGE;
    }

    /**
     * Check if the attachment is a video.
     */
    public function isVideo()
    {
        return $this->file_type === self::TYPE_VIDEO;
    }

    /**
     * Check if the attachment is an audio file.
     */
    public function isAudio()
    {
        return $this->file_type === self::TYPE_AUDIO;
    }

    /**
     * Check if the attachment is a document.
     */
    public function isDocument()
    {
        return $this->file_type === self::TYPE_DOCUMENT;
    }

    /**
     * Get the image dimensions from metadata.
     */
    public function getImageDimensions()
    {
        return $this->metadata['dimensions'] ?? null;
    }

    /**
     * Get the video duration from metadata.
     */
    public function getVideoDuration()
    {
        return $this->metadata['duration'] ?? null;
    }

    /**
     * Get the audio duration from metadata.
     */
    public function getAudioDuration()
    {
        return $this->metadata['duration'] ?? null;
    }

    /**
     * Mark the attachment as completed.
     */
    public function markAsCompleted()
    {
        return $this->update(['status' => self::STATUS_COMPLETED]);
    }

    /**
     * Mark the attachment as failed with an error message.
     */
    public function markAsFailed($errorMessage = null)
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Get the public URL for the file.
     */
    public function getUrl()
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Get the public URL for the thumbnail.
     */
    public function getThumbnailUrl()
    {
        if ($this->thumbnail_path) {
            return asset('storage/' . $this->thumbnail_path);
        }

        return null;
    }

    /**
     * Get the MIME type icon.
     */
    public function getMimeIcon()
    {
        if ($this->isImage()) {
            return '📷';
        } elseif ($this->isVideo()) {
            return '🎥';
        } elseif ($this->isAudio()) {
            return '🎵';
        } elseif ($this->isDocument()) {
            switch ($this->file_extension) {
                case 'pdf':
                    return '📄';
                case 'doc':
                case 'docx':
                    return '📝';
                case 'xls':
                case 'xlsx':
                    return '📊';
                case 'ppt':
                case 'pptx':
                    return '📑';
                default:
                    return '📄';
            }
        }

        return '📎';
    }

    /**
     * Check if the file is currently being processed.
     */
    public function isProcessing()
    {
        return in_array($this->status, [self::STATUS_UPLOADING, self::STATUS_PROCESSING]);
    }

    /**
     * Delete the actual file from storage when the model is deleted.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($attachment) {
            if ($attachment->file_path) {
                \Storage::disk('public')->delete($attachment->file_path);
            }

            if ($attachment->thumbnail_path) {
                \Storage::disk('public')->delete($attachment->thumbnail_path);
            }
        });
    }
}
