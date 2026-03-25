<?php

namespace App\Providers;

use App\Http\Controllers\FrontendPageController;
use App\Models\Blog;
use App\Models\BlogTranslation;
use App\Models\HeaderFooterScript;
use App\Models\PageTranslation;
use App\Observers\BlogObserver;
use App\Observers\BlogTranslationObserver;
use App\Observers\PageTranslationObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blog::observe(BlogObserver::class);
        BlogTranslation::observe(BlogTranslationObserver::class);
        PageTranslation::observe(PageTranslationObserver::class);
        \App\Models\Vehicle::observe(\App\Observers\VehicleObserver::class);

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

        // Share Organization Schema globally with Inertia and Blade
        $organizationSchema = FrontendPageController::getOrganizationSchema();

        Inertia::share('organizationSchema', function () use ($organizationSchema) {
            return $organizationSchema;
        });

        // Share with Blade views as well
        View::share('organizationSchemaForBlade', $organizationSchema);
    }
}
