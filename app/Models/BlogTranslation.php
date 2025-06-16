<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'blog_id',
        'locale',
        'title',
        'slug',
        'content',
    ];

    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }
}
