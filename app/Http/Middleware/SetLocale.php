<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;

class SetLocale
{
    public function handle(Request $request, Closure $next)
{
    $locale = session('locale', app()->getLocale());
    App::setLocale($locale);

    // Share all translations with Inertia
    Inertia::share([
        'locale' => $locale,
        'translations' => [
            'messages' => trans('messages'), // Common translations
            'homepage' => trans('homepage'),
            'how_it_works' => trans('how_it_works'),
            'header' => trans('header'),
            'registerUser' => trans('registerUser'),
            'login' => trans('login'),
            'forgetpassword' => trans('forgetpassword'),
            'resetpassword' => trans('resetpassword'),
            'common' => trans('common'),
            'customerprofile' => trans('customerprofile'),
            'customerprofilepages' => trans('customerprofilepages'),
            'authpages' => trans('authpages'),
            'customerbooking' => trans('customerbooking'),
        ],
    ]);

    return $next($request);
}

    protected function getAllTranslations()
    {
        // Load all message translations (which includes all other files)
        $messages = $this->loadTranslationsSafely('messages');
        
        // You can still load page-specific translations if needed
        // $pageSpecific = $this->loadTranslationsSafely('homepage');
        
        return [
            'messages' => $messages,
            // 'homepage' => $pageSpecific,
        ];
    }

    protected function loadTranslationsSafely($file)
    {
        $translations = trans($file);
        
        if ($translations === null || !is_array($translations)) {
            \Log::warning("Translation file '$file' is missing or did not return an array for locale '" . app()->getLocale() . "'.");
            return [];
        }

        foreach ($translations as $key => $value) {
            if (!$this->isSerializable($value)) {
                \Log::warning("Non-serializable translation in file '$file' for key '$key':", ['value' => $value]);
                $translations[$key] = null;
            }
        }

        return $translations;
    }

    protected function isSerializable($value)
    {
        try {
            json_encode($value, JSON_THROW_ON_ERROR);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}