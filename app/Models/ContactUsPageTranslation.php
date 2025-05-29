<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactUsPageTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_us_page_id',
        'locale',
        'hero_title',
        'hero_description',
        'intro_text',
        'contact_points',
    ];

    protected $casts = [
        'contact_points' => 'array',
    ];

    public function contactUsPage()
    {
        return $this->belongsTo(ContactUsPage::class);
    }
}
