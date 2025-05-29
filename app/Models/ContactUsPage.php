<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Corrected namespace
use Illuminate\Database\Eloquent\Model;

class ContactUsPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'hero_title',
        'hero_description',
        'hero_image_url',
        'contact_points', // This might be kept for non-translated parts like icons
        'intro_text',
        'phone_number',
        'email',
        'address'
    ];

    protected $casts = [
        'contact_points' => 'array' // For original structure if needed
    ];

    /**
     * Get the translations for the contact us page.
     */
    public function translations()
    {
        return $this->hasMany(ContactUsPageTranslation::class);
    }
}
