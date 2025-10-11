<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Added import

class Message extends Model
{
    use HasFactory, SoftDeletes; // Added SoftDeletes trait

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'booking_id',
        'message',
        'read_at',
        'parent_id',
        'reminder_sent_at',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'voice_note_path', // New fillable attribute for voice notes
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at', 'reminder_sent_at']; // Ensure deleted_at and reminder_sent_at are Carbon instances

    protected $appends = ['file_url', 'voice_note_url']; // Append file_url and voice_note_url accessors to JSON output

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function replies()
    {
        return $this->hasMany(Message::class, 'parent_id');
    }

    public function readReceipts()
    {
        return $this->hasMany(MessageReadReceipt::class);
    }

    // Accessor for file_url
    public function getFileUrlAttribute()
    {
        if ($this->file_path) {
            return \Illuminate\Support\Facades\Storage::disk('upcloud')->url($this->file_path);
        }
        return null;
    }

    // Accessor for voice_note_url
    public function getVoiceNoteUrlAttribute()
    {
        if ($this->voice_note_path) {
            return \Illuminate\Support\Facades\Storage::disk('upcloud')->url($this->voice_note_path);
        }
        return null;
    }
}
