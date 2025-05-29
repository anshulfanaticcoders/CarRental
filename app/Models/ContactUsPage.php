<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactUsPage extends Model // Renamed from NewContactUsPage
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contact_us_page'; // New singular table name

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'hero_image_url',
        'contact_point_icons', // Array of icon URLs or identifiers
        'phone_number',
        'email',
        'address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'contact_point_icons' => 'array',
    ];

    /**
     * Get the translations for the contact us page.
     */
    public function translations()
    {
        // Ensure this points to the correctly named translation model
        return $this->hasMany(ContactUsPageTranslation::class, 'contact_us_page_id');
    }

    /**
     * Get the translation for a specific locale.
     */
    public function translation($locale = null)
    {
        if (is_null($locale)) {
            $locale = app()->getLocale();
        }
        return $this->translations()->where('locale', $locale)->first();
    }
}
