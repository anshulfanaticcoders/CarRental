<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class UserDocument extends Model
{
    use HasFactory;

    /** The private document file fields (stored as object keys on the upcloud disk). */
    public const FILE_FIELDS = [
        'driving_license_front',
        'driving_license_back',
        'passport_front',
        'passport_back',
    ];

    /** Signed document links expire this many minutes after they are generated. */
    public const SIGNED_URL_MINUTES = 15;

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
    ];

    /**
     * Normalize a stored document value (a new relative key or a legacy full URL)
     * down to the object key on the upcloud disk. Returns null when empty.
     */
    public static function storageKey(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (! str_contains($value, '://')) {
            return ltrim($value, '/');
        }

        return ltrim((string) parse_url($value, PHP_URL_PATH), '/');
    }

    /**
     * Short-lived signed URL that streams one document field through the
     * authorized secure-documents route, or null when the field is empty.
     */
    public function signedDocumentUrl(string $field): ?string
    {
        if (! in_array($field, self::FILE_FIELDS, true) || empty($this->{$field})) {
            return null;
        }

        return URL::temporarySignedRoute(
            'secure-documents.show',
            now()->addMinutes(self::SIGNED_URL_MINUTES),
            ['userDocument' => $this->id, 'field' => $field]
        );
    }

    /**
     * Field => signed URL (null when absent) for all document fields, plus the
     * verification status. Safe to hand to any authorized viewing surface.
     */
    public function toSignedArray(): array
    {
        $data = [
            'id' => $this->id,
            'verification_status' => $this->verification_status,
            'verified_at' => $this->verified_at,
        ];
        foreach (self::FILE_FIELDS as $field) {
            $data[$field] = $this->signedDocumentUrl($field);
        }

        return $data;
    }

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
