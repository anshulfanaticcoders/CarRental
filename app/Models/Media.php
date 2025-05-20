<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'user_id',
        'disk',
        'directory',
        'filename',
        'extension',
        'mime_type',
        'size',
        'url',
        'title',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'size' => 'integer',
    ];

    /**
     * Get the user who uploaded the media.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full path to the file on its disk.
     * Example: 'my_media/image.jpg'
     */
    public function getFullPathAttribute(): string
    {
        return ($this->directory ? $this->directory . '/' : '') . $this->filename;
    }

    /**
     * Get the public URL if not stored, or return stored URL.
     * This might need adjustment based on how UpCloud URLs are generated/stored.
     * If 'url' is already populated during upload, this can just return $this->url.
     */
    public function getPublicUrlAttribute(): ?string
    {
        if ($this->url) {
            return $this->url;
        }
        // If URL is not stored, try to generate it (assuming 'upcloud' disk is public)
        // This is a common pattern but might need specific UpCloud logic.
        if (config("filesystems.disks.{$this->disk}.visibility") === 'public' || 
            config("filesystems.disks.{$this->disk}.driver") === 'public') { // A bit of a guess for 'public' disk
            return Storage::disk($this->disk)->url($this->getFullPathAttribute());
        }
        return null; // Or throw an exception if URL cannot be determined
    }
}
