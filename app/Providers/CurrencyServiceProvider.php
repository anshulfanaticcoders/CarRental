<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CurrencyDetectionService;
use App\Services\CurrencyConversionService;
use Illuminate\Support\Facades\Log;

class CurrencyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Currency Detection Service as singleton
        $this->app->singleton(CurrencyDetectionService::class, function ($app) {
            return new CurrencyDetectionService();
        });

        // Register Currency Conversion Service as singleton
        $this->app->singleton(CurrencyConversionService::class, function ($app) {
            return new CurrencyConversionService();
        });

        // Register currency configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/currency.php',
            'currency'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish currency configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/currency.php' => config_path('currency.php'),
            ], 'currency-config');
        }

        // Log currency service initialization
        Log::info('Currency service provider initialized', [
            'detection_service_enabled' => config('currency.geolocation.enabled', true),
            'conversion_service_enabled' => config('currency.exchange_providers.primary.enabled', true),
            'default_currency' => config('currency.default', 'USD'),
            'cache_ttl' => config('currency.cache_ttl', 3600)
        ]);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            CurrencyDetectionService::class,
            CurrencyConversionService::class,
        ];
    }
}