<?php

namespace App\Http\Controllers;

use App\Models\CurrencyRate;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index()
    {
        return response()->json([
            'current_currency' => session('currency', 'USD'),
            'supported_currencies' => [
                'USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'HKD', 'SGD',
                'SEK', 'KRW', 'NOK', 'NZD', 'INR', 'MXN', 'BRL', 'RUB', 'ZAR', 'AED',
                'MAD', 'TRY', 'JOD', 'ISK', 'AZN', 'MYR', 'OMR', 'UGX', 'NIO',
                // North America
                'CRC', 'PAB', 'GTQ', 'HNL', 'SVC', 'BZD', 'JMD', 'BBD', 'TTD', 'DOP', 'HTG', 'XCD',
                // Europe
                'PLN', 'CZK', 'HUF', 'RON', 'BGN',
                // Asia Pacific
                'IDR', 'THB', 'PHP', 'VND', 'TWD', 'BDT', 'PKR', 'LKR', 'MMK', 'KHR', 'LAK', 'NPR', 'BTN',
                // Oceania
                'FJD', 'PGK', 'SBD', 'VUV', 'WST', 'TOP', 'KID',
                // Middle East & Africa
                'SAR', 'ILS', 'QAR', 'KWD', 'BHD', 'LBP', 'SYP', 'IQD', 'IRR', 'AFN', 'EGP', 'LYD', 'TND', 'DZD', 'SDG', 'YER',
                'NGN', 'GHS', 'TZS', 'ZMW', 'ZWL', 'MWK', 'MZN', 'AOA', 'BWP', 'NAD', 'SZL', 'LSL', 'LRD', 'SLL', 'GNF',
                'XOF', 'XAF', 'CDF', 'RWF', 'BIF', 'DJF', 'ERN', 'ETB', 'SOS', 'GMD', 'CVE', 'STN', 'SHP', 'SCR', 'MUR', 'MGA', 'KMF',
                // South America
                'ARS', 'CLP', 'COP', 'PEN', 'VES', 'UYU', 'PYG', 'BOB', 'GYD', 'SRD',
                // Former Soviet Union
                'UAH', 'BYN', 'MDL', 'GEL', 'AMD', 'KZT', 'KGS', 'UZS', 'TJS', 'TMT',
                // Others
                'CUP', 'ANG', 'AWG', 'CWG', 'BMD', 'KYD', 'BYN', 'MVR'
            ]
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'currency' => 'required|string|in:USD,EUR,GBP,JPY,AUD,CAD,CHF,CNY,HKD,SGD,SEK,KRW,NOK,NZD,INR,MXN,BRL,RUB,ZAR,AED,MAD,TRY,JOD,ISK,AZN,MYR,OMR,UGX,NIO,CRC,PAB,GTQ,HNL,SVC,BZD,JMD,BBD,TTD,DOP,HTG,XCD,PLN,CZK,HUF,RON,BGN,IDR,THB,PHP,VND,TWD,BDT,PKR,LKR,MMK,KHR,LAK,NPR,BTN,FJD,PGK,SBD,VUV,WST,TOP,KID,SAR,ILS,QAR,KWD,BHD,LBP,SYP,IQD,IRR,AFN,EGP,LYD,TND,DZD,SDG,YER,NGN,GHS,TZS,ZMW,ZWL,MWK,MZN,AOA,BWP,NAD,SZL,LSL,LRD,SLL,GNF,XOF,XAF,CDF,RWF,BIF,DJF,ERN,ETB,SOS,GMD,CVE,STN,SHP,SCR,MUR,MGA,KMF,ARS,CLP,COP,PEN,VES,UYU,PYG,BOB,GYD,SRD,UAH,BYN,MDL,GEL,AMD,KZT,KGS,UZS,TJS,TMT,CUP,ANG,AWG,CWG,BMD,KYD,MVR',
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
