<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    // Tell Laravel to use the correct table
    protected $table = 'message_notifications';

    protected $fillable = ['user_id', 'type', 'title', 'message', 'read_at', 'booking_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

