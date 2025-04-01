<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'driving_license_front',
        'driving_license_back',

        'passport_front',
        'passport_back',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
