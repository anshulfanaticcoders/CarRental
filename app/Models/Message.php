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
        'reminder_sent_at', // Added
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at', 'reminder_sent_at']; // Ensure deleted_at and reminder_sent_at are Carbon instances

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
}
