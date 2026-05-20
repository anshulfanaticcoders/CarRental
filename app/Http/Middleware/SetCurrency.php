<?php

namespace App\Http\Middleware;

use App\Services\CurrencyDetectionService;
use App\Support\CurrencyRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrency
{
    private CurrencyDetectionService $currencyDetectionService;

    public function __construct(
        CurrencyDetectionService $currencyDetectionService,
        private CurrencyRegistry $currencies
    )
    {
        $this->currencyDetectionService = $currencyDetectionService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $currentCurrency = session('currency');
            $lastManualChangeTime = session('currency_manual_change_time', 0);
            $currentTime = time();
            $currentIP = $this->resolveRealIp($request);
            $lastDetectedIP = session('last_detected_ip', '');

            $detection = $this->currencyDetectionService->detectCurrencyByIp($currentIP);
            $detectedCurrency = $detection['currency'] ?? ($currentCurrency ?: $this->currencies->defaultCurrency());
            $detectedCountry = $detection['success'] ? strtoupper((string) ($detection['country_code'] ?? '')) : '';

            $shouldUpdateCurrency = false;

            if (!$currentCurrency) {
                $shouldUpdateCurrency = true;
            } elseif ($currentIP !== $lastDetectedIP) {
                $shouldUpdateCurrency = true;
            } elseif (session('last_detected_country', '') !== $detectedCountry) {
                $shouldUpdateCurrency = true;
            } elseif (($currentTime - $lastManualChangeTime) > 1800) {
                $shouldUpdateCurrency = true;
            }

            if ($detectedCountry !== '') {
                session(['country' => strtolower($detectedCountry)]);
            }

            if ($shouldUpdateCurrency && $currentCurrency !== $detectedCurrency) {
                session(['currency' => $detectedCurrency]);
                session(['currency_detection_time' => $currentTime]);
            }

            session(['last_detected_ip' => $currentIP]);
            session(['last_detected_country' => $detectedCountry]);

        } catch (\Exception $e) {
            if (!session('currency')) {
                session(['currency' => $this->currencies->defaultCurrency()]);
                session(['currency_detection_time' => time()]);
            }
            if (!session('country')) {
                session(['country' => 'us']);
            }
        }

        return $next($request);
    }

    private function resolveRealIp(Request $request): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        foreach ($ipKeys as $key) {
            $rawIp = $request->server($key);

            if (!$rawIp) {
                continue;
            }

            foreach (explode(',', $rawIp) as $candidateIp) {
                $candidateIp = trim($candidateIp);

                if (filter_var($candidateIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $candidateIp;
                }
            }
        }

        return (string) $request->ip();
    }

}

