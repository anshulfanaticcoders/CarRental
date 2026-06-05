<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Affiliate\AffiliateQrCodeService;
use App\Support\AuthReturnUrl;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): InertiaResponse
    {
        $locale = (string) ($request->route('locale') ?? app()->getLocale());
        $returnTo = AuthReturnUrl::capture(
            $request,
            $request->query('redirect') ?: url()->previous()
        );

        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'returnTo' => $returnTo,
            'seo' => [
                'title' => 'Sign in to Vrooem',
                'description' => 'Sign in to manage your Vrooem bookings, profile, and account settings.',
                'canonical' => route('login', ['locale' => $locale]),
                'image' => config('seo.defaults.image'),
                'robots' => 'noindex,follow',
                'og_type' => 'website',
                'site_name' => 'Vrooem',
            ],
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
            $affiliateService = new AffiliateQrCodeService;
            $affiliateService->updateCustomerInAffiliateScans($user->id);
        }

        $locale = $request->route('locale') ?? config('app.fallback_locale', 'en');
        $returnTo = AuthReturnUrl::pull($request, $request->input('return_to'));

        $request->session()->flash('status', 'Welcome back!');

        if ($user && $user->role === 'admin') {
            return Inertia::location(route('admin.dashboard'));
        }

        if ($user && $user->role === 'affiliate') {
            return Inertia::location(route('affiliate.dashboard', ['locale' => $locale]));
        }

        if ($returnTo) {
            return Inertia::location($returnTo);
        }

        return Inertia::location(route('profile.edit', ['locale' => $locale]));
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
