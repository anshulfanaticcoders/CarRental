<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserRole
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
        // Check if the user is authenticated and has one of the allowed roles
        if (Auth::check() && in_array(Auth::user()->role, ['admin', 'vendor', 'customer'])) {
            return $next($request);
        }

        // Redirect or abort if the user does not have the required role
        return redirect('/'); // Change this to your desired redirect
    }
}
