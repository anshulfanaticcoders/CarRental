<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactUsPageTranslation extends Model // Renamed from NewContactUsPageTranslation
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contact_us_page_translation'; // New singular table name

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'contact_us_page_id',
        'locale',
        'hero_title',
        'hero_description',
        'intro_text',
        'contact_points', // JSON array of textual parts, e.g., [{'title': 'Sales', 'detail': '...'}, ...]
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'contact_points' => 'array',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Get the parent contact us page model.
     */
    public function contactUsPage()
    {
        // Ensure this points to the correctly named main model
        return $this->belongsTo(ContactUsPage::class, 'contact_us_page_id');
    }
}
