<?php

namespace App\Http\Controllers;

use App\Models\CurrencyRate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    public function index()
    {
        return response()->json([
            'current_currency' => session('currency', 'USD'),
            'supported_currencies' => config('currency.supported_currencies', [])
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'currency' => ['required', 'string', Rule::in(config('currency.supported_currencies', []))],
        ]);

        $previousCurrency = session('currency', 'USD');

        session(['currency' => $request->currency]);
        // Track manual change time to respect user selection for 30 minutes
        session(['currency_manual_change_time' => time()]);
        session(['currency_detection_time' => time()]);

        \Log::info('Currency manually updated via dropdown:', [
            'previous_currency' => $previousCurrency,
            'new_currency' => $request->currency,
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'manual_selection' => true,
            'auto_update_paused_for_minutes' => 30
        ]);

        return back();
    }

    public function rates(Request $request)
    {
        $baseCurrency = strtoupper($request->query('base', config('currency.base_currency', 'USD')));

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
