<?php

namespace App\Providers;

use App\Http\Controllers\FrontendPageController;
use App\Models\HeaderFooterScript;
use App\Services\Locations\Fetchers\AdobeLocationFetcher;
use App\Services\Locations\Fetchers\FavricaLocationFetcher;
use App\Services\Locations\Fetchers\GreenMotionLocationFetcher;
use App\Services\Locations\Fetchers\InternalLocationFetcher;
use App\Services\Locations\Fetchers\LocautoLocationFetcher;
use App\Services\Locations\Fetchers\OkMobilityLocationFetcher;
use App\Services\Locations\Fetchers\RecordGoLocationFetcher;
use App\Services\Locations\Fetchers\RenteonLocationFetcher;
use App\Services\Locations\Fetchers\SicilyByCarLocationFetcher;
use App\Services\Locations\Fetchers\SurpriceLocationFetcher;
use App\Services\Locations\Fetchers\USaveLocationFetcher;
use App\Services\Locations\Fetchers\WheelsysLocationFetcher;
use App\Services\Locations\Fetchers\XDriveLocationFetcher;
use App\Services\Locations\ProviderLocationFetchManager;
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
        $this->app->singleton(ProviderLocationFetchManager::class, function ($app) {
            return new ProviderLocationFetchManager([
                $app->make(InternalLocationFetcher::class),
                $app->make(GreenMotionLocationFetcher::class),
                $app->make(USaveLocationFetcher::class),
                $app->make(OkMobilityLocationFetcher::class),
                $app->make(AdobeLocationFetcher::class),
                $app->make(LocautoLocationFetcher::class),
                $app->make(WheelsysLocationFetcher::class),
                $app->make(RenteonLocationFetcher::class),
                $app->make(FavricaLocationFetcher::class),
                $app->make(XDriveLocationFetcher::class),
                $app->make(SicilyByCarLocationFetcher::class),
                $app->make(RecordGoLocationFetcher::class),
                $app->make(SurpriceLocationFetcher::class),
            ]);
        });
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

        // Share Organization Schema globally with Inertia and Blade
        $organizationSchema = FrontendPageController::getOrganizationSchema();

        Inertia::share('organizationSchema', function () use ($organizationSchema) {
            return $organizationSchema;
        });

        // Share with Blade views as well
        View::share('organizationSchemaForBlade', $organizationSchema);
    }
}
