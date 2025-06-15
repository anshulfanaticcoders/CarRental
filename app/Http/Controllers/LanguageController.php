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

        $previousUrl = url()->previous();
        $previousPath = parse_url($previousUrl, PHP_URL_PATH);

        // Replace the old locale with the new one in the path
        $segments = explode('/', ltrim($previousPath, '/'));
        if (in_array($segments[0], ['en', 'fr', 'nl'])) {
            $segments[0] = $locale;
        } else {
            array_unshift($segments, $locale);
        }
        $newPath = implode('/', $segments);

        return redirect($newPath);
    }
}
