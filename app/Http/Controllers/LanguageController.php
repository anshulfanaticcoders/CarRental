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
            'locale' => 'required|in:en,fr,nl,es,ar',
        ]);

        $locale = $request->locale;
        App::setLocale($locale);
        Session::put('locale', $locale);

        // AJAX calls get JSON, normal requests get redirect
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['locale' => $locale]);
        }

        $previousUrl = url()->previous();
        $previousPath = parse_url($previousUrl, PHP_URL_PATH);

        $segments = explode('/', ltrim($previousPath, '/'));
        if (in_array($segments[0], ['en', 'fr', 'nl', 'es', 'ar'])) {
            $segments[0] = $locale;
        } else {
            array_unshift($segments, $locale);
        }

        return redirect(implode('/', $segments));
    }
}
