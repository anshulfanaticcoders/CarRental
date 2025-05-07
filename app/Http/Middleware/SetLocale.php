<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the user has a locale preference in the session
        if (Session::has('locale')) {
            $locale = Session::get('locale');
        } 
        // Fallback to cookie if session is not available
        else if ($request->cookie('locale')) {
            $locale = $request->cookie('locale');
        } 
        // Default to the app's default locale
        else {
            $locale = config('app.locale');
        }

        // Set the application locale
        App::setLocale($locale);

        return $next($request);
    }
}