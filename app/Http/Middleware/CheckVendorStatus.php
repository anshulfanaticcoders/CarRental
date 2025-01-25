<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckVendorStatus
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Check if the user has a vendor profile
        $vendorProfile = $user->vendorProfile; // Assuming a relationship exists

        if (!$vendorProfile) {
            return redirect('/');
        }

        // Allow only if the vendor profile is approved
        if ($vendorProfile->status !== 'approved') {
            return redirect('/');
        }

        return $next($request);
    }
}
