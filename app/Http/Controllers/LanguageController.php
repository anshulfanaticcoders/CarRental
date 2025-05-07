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

        App::setLocale($request->locale);
        Session::put('locale', $request->locale);

        return back();
    }
}