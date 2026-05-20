<?php

namespace App\Http\Controllers;

use App\Models\CurrencyRate;
use App\Support\CurrencyRegistry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    public function index(CurrencyRegistry $currencies)
    {
        $supported = $currencies->selectableCodes();

        return response()->json([
            'current_currency' => session('currency', $currencies->defaultCurrency()),
            'supported_currencies' => $supported,
            'base_currency' => $currencies->baseCurrency(),
            'default_currency' => $currencies->defaultCurrency(),
            'currencies' => $currencies->publicPayload($supported),
            'popular_currencies' => $currencies->popularCodes($supported),
            'version' => 'currency-registry-v1',
        ]);
    }

    public function update(Request $request, CurrencyRegistry $currencies)
    {
        $request->merge([
            'currency' => $currencies->normalize($request->input('currency'), $currencies->defaultCurrency()),
        ]);

        $request->validate([
            'currency' => ['required', 'string', Rule::in($currencies->selectableCodes())],
        ]);

        $currency = $request->currency;
        $previousCurrency = session('currency', $currencies->defaultCurrency());

        session(['currency' => $currency]);
        // Track manual change time to respect user selection for 30 minutes
        session(['currency_manual_change_time' => time()]);
        session(['currency_detection_time' => time()]);

        \Log::info('Currency manually updated via dropdown:', [
            'previous_currency' => $previousCurrency,
            'new_currency' => $currency,
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'manual_selection' => true,
            'auto_update_paused_for_minutes' => 30
        ]);

        return back();
    }

    public function rates(Request $request)
    {
        $baseCurrency = app(CurrencyRegistry::class)->normalize(
            $request->query('base', config('currency.base_currency', 'EUR')),
            config('currency.base_currency', 'EUR')
        );

        $rates = CurrencyRate::query()
            ->where('base_currency', $baseCurrency)
            ->get(['target_currency', 'rate', 'fetched_at']);

        if ($rates->isEmpty()) {
            return response()->json([
                'success' => false,
                'base' => $baseCurrency,
                'rates' => [],
                'message' => 'Rates not available'
            ], 404);
        }

        $fetchedAt = $rates->max('fetched_at');
        $mappedRates = $rates->pluck('rate', 'target_currency');
        $mappedRates[$baseCurrency] = 1;

        return response()->json([
            'success' => true,
            'base' => $baseCurrency,
            'rates' => $mappedRates,
            'fetched_at' => $fetchedAt,
        ]);
    }
}
