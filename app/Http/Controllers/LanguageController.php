<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    public function change(Request $request)
    {
        $request->validate([
            'locale' => 'required|in:en,fr,nl',
        ]);

        $locale = $request->locale;
        App::setLocale($locale);
        Session::put('locale', $locale);

        // Always return a redirect response
        return redirect()->back()->with('locale', $locale);
    }
}