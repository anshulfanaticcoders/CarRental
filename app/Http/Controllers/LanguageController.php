<?php

namespace App\Http\Controllers;
    
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

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

    if ($request->header('X-Inertia')) {
        return Inertia::location($request->header('Referer') ?: '/');
    }

    return redirect()->back()->with('locale', $locale);
}
}