<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageSectionTranslation extends Model
{
    protected $fillable = [
        'page_section_id',
        'locale',
        'title',
        'content',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function section()
    {
        return $this->belongsTo(PageSection::class, 'page_section_id');
    }
}
