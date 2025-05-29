<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewContactUsPageTranslation extends Model
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
    public $timestamps = true; // Ensure timestamps are managed

    /**
     * Get the parent contact us page model.
     */
    public function contactUsPage()
    {
        return $this->belongsTo(NewContactUsPage::class, 'contact_us_page_id');
    }
}
