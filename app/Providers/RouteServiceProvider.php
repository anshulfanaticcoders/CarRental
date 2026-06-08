<?php

namespace App\Providers;

use App\Models\NewsletterSubscription;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/';

    public const HOMEPAGE = '/';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('newsletter', function (Request $request) {
            $email = NewsletterSubscription::normalizeEmail((string) $request->input('email', 'missing'));
            $email = $email === '' ? 'missing' : $email;

            return [
                Limit::perMinute(5)->by('ip:'.$request->ip()),
                Limit::perHour(3)->by('email:'.$email),
                Limit::perHour(10)->by('email-ip:'.$email.'|'.$request->ip()),
            ];
        });

        // General mobile-app traffic — keyed by Sanctum user id when authenticated,
        // otherwise by IP. 120/min handles a busy search session (filters, refresh)
        // while still throttling abusive clients.
        RateLimiter::for('web-auth', function (Request $request) {
            $identifier = strtolower(trim((string) $request->input('email', 'missing')));

            return [
                Limit::perMinute(10)->by($request->ip()),
                Limit::perMinute(10)->by($identifier.'|'.$request->ip()),
            ];
        });

        RateLimiter::for('checkout', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('message-send', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('form-validation', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('mobile', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Tighter limit for auth endpoints to slow credential stuffing.
        RateLimiter::for('mobile-auth', function (Request $request) {
            $identifier = strtolower(trim((string) $request->input('email', '')));
            if ($identifier === '') {
                $phone = preg_replace('/\D+/', '', (string) $request->input('phone', ''));
                $phoneCode = preg_replace('/\D+/', '', (string) $request->input('phone_code', ''));
                $identifier = $phone !== '' ? $phoneCode.':'.$phone : 'missing';
            }

            return [
                Limit::perMinute(10)->by($request->ip()),
                Limit::perMinute(10)->by($identifier.'|'.$request->ip()),
            ];
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/skyscanner.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/trabber.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
