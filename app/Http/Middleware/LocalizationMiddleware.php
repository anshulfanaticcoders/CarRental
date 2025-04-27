<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class LocalizationMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if locale is set in session
        if (Session::has('locale')) {
            App::setLocale(Session::get('locale'));
        } else {
            // Default language
            App::setLocale('en');
        }

        return $next($request);
    }
}