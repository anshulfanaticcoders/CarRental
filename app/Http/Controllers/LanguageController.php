<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Redirect;

class LanguageController extends Controller
{
    public function switch($locale)
    {
        if (!in_array($locale, ['en', 'fr', 'nl'])) {
            abort(400);
        }

        // Store the locale in session
        session()->put('locale', $locale);

        // Redirect back to the previous page
        return Redirect::back();
    }
}