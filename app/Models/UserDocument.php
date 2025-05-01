<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'driving_license_front',
        'driving_license_back',
        'passport_front',
        'passport_back',
        'verification_status',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'expires_at' => 'date',
    ];

    // Ensure document number is always uppercase
    public function setDocumentNumberAttribute($value)
    {
        $this->attributes['document_number'] = strtoupper($value);
    }

    // Get the formatted document number
    public function getFormattedDocumentNumberAttribute()
    {
        return chunk_split($this->document_number, 3, ' ');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope to find by document number (case-insensitive)
    public function scopeDocumentNumber($query, $number)
    {
        return $query->whereRaw('LOWER(document_number) = ?', [strtolower($number)]);
    }
}
