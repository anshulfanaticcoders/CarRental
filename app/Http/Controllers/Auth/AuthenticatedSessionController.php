<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use App\Services\Affiliate\AffiliateQrCodeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): InertiaResponse
    {
        // Store the previous URL as the intended destination, but only if it's not an auth route.
        $previousUrl = url()->previous();
        if ($previousUrl && !str_contains($previousUrl, '/login') && !str_contains($previousUrl, '/register')) {
            session(['url.intended' => $previousUrl]);
        }

        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): Response
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::user();

        // Update last_login_at *after* successful authentication
        if ($user) {
            $user->last_login_at = Carbon::now();
            $user->save();
        }

        // Update affiliate customer scan with customer_id if affiliate data exists in session
        if ($user) {
            $affiliateService = new AffiliateQrCodeService();
            $affiliateService->updateCustomerInAffiliateScans($user->id);
        }

        // Redirect based on role
        if ($user->role === 'admin') {
            return Inertia::location('/admin-dashboard');
        }

        // Use the intended URL, falling back to the HOME constant.
        return Inertia::location(session()->pull('url.intended', RouteServiceProvider::HOME));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return Inertia::location('/');
    }
}
