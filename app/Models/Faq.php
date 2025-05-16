<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;

    protected $fillable = []; // No direct fillable attributes now

    protected $appends = ['question', 'answer'];

    public function translations()
    {
        return $this->hasMany(FaqTranslation::class);
    }

    public function getTranslation(string $locale = null, bool $useFallback = true)
    {
        $currentLocale = $locale ?? app()->getLocale();
        $translation = $this->translations()->where('locale', $currentLocale)->first();

        if (!$translation && $useFallback) {
            // Try fallback locale
            $fallbackLocale = config('app.fallback_locale', 'en');
            if ($currentLocale !== $fallbackLocale) {
                $translation = $this->translations()->where('locale', $fallbackLocale)->first();
            }

            // If still no translation, try the first available one
            if (!$translation && $this->translations()->exists()) {
                $translation = $this->translations()->first();
            }
        }
        return $translation;
    }

    public function getQuestionAttribute()
    {
        $translation = $this->getTranslation(app()->getLocale());
        return $translation ? $translation->question : '[No Question]';
    }

    public function getAnswerAttribute()
    {
        $translation = $this->getTranslation(app()->getLocale());
        return $translation ? $translation->answer : '[No Answer]';
    }

    // Helper to get question for a specific locale, useful for admin forms
    public function getQuestionForLocale(string $locale)
    {
        return $this->translations()->where('locale', $locale)->value('question');
    }

    // Helper to get answer for a specific locale, useful for admin forms
    public function getAnswerForLocale(string $locale)
    {
        return $this->translations()->where('locale', $locale)->value('answer');
    }
}
