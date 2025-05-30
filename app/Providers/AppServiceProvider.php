<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Support\Facades\View;
use App\Models\HeaderFooterScript;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https'); // Force HTTPS for all routes
        }

        Inertia::share('flash', function () {
            return [
                'success' => session('success'),
                'error' => session('error'),
            ];
        });

        // Share Header/Footer scripts with all views
        // Check if the table exists to prevent errors during migrations
        if (Schema::hasTable('header_footer_scripts')) {
            $latestScript = HeaderFooterScript::orderBy('id', 'desc')->first();
            View::share('headerScript', $latestScript ? $latestScript->header_script : '');
            View::share('footerScript', $latestScript ? $latestScript->footer_script : '');
        } else {
            View::share('headerScript', '');
            View::share('footerScript', '');
        }
    }
}
