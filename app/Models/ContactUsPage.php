<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactUsPage extends Model
{
    protected $table = 'contact_us_pages';

    protected $fillable = [
        'hero_title',
        'hero_description',
        'hero_image_url',
        'contact_points',
        'intro_text',
        'phone_number',
        'email',
        'address'
    ];

    protected $casts = [
        'contact_points' => 'array'
    ];
}