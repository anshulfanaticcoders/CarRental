<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Services\CurrencyDetectionService;
use Symfony\Component\HttpFoundation\Response;

class CurrencyDetectionMiddleware
{
    private CurrencyDetectionService $currencyDetectionService;

    public function __construct(CurrencyDetectionService $currencyDetectionService)
    {
        $this->currencyDetectionService = $currencyDetectionService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only run on GET requests for web routes
        if ($request->isMethod('GET') && !$request->is('api/*') && !$request->is('admin/*')) {
            $this->detectAndSetCurrency($request);
        }

        return $next($request);
    }

    /**
     * Detect currency and set it in session
     */
    private function detectAndSetCurrency(Request $request): void
    {
        // Skip if currency is already set in this session
        if (Session::has('detected_currency')) {
            return;
        }

        // Skip if user has manually set currency preference
        if ($request->user() && $request->user()->currency_preference) {
            return;
        }

        // Skip if currency is in request query
        if ($request->has('currency')) {
            return;
        }

        try {
            // Try to detect currency from IP
            $result = $this->currencyDetectionService->detectCurrencyByIp();

            if ($result['success'] && $result['currency']) {
                Session::put('detected_currency', $result['currency']);
                Session::put('currency_detection_info', [
                    'method' => $result['detection_method'],
                    'confidence' => $result['confidence'],
                    'country_code' => $result['country_code'],
                    'detected_at' => now()->toISOString()
                ]);

                // Share detected currency with all views
                view()->share('detected_currency', $result['currency']);
            }

        } catch (\Exception $e) {
            // Log error but don't break the request
            \Log::warning('Currency detection failed in middleware', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
        }
    }
}