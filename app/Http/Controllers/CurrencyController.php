<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index()
    {
        return response()->json([
            'current_currency' => session('currency', 'USD'),
            'supported_currencies' => [
                'USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNH', 'HKD', 'SGD',
                'SEK', 'KRW', 'NOK', 'NZD', 'INR', 'MXN', 'BRL', 'RUB', 'ZAR', 'AED',
                'MAD', 'TRY', 'JOD', 'ISK', 'AZN', 'MYR', 'OMR', 'UGX', 'NIO'
            ]
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'currency' => 'required|string|in:USD,EUR,GBP,JPY,AUD,CAD,CHF,CNH,HKD,SGD,SEK,KRW,NOK,NZD,INR,MXN,BRL,RUB,ZAR,AED,MAD,TRY,JOD,ISK,AZN,MYR,OMR,UGX,NIO',
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
}
