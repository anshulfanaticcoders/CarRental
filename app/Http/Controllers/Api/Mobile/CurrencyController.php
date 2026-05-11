<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CurrencyRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'supported' => config('currency.supported_currencies', ['EUR', 'USD', 'GBP']),
            'base' => config('currency.base_currency', 'USD'),
        ]);
    }

    public function rates(Request $request): JsonResponse
    {
        $base = strtoupper($request->query('base', config('currency.base_currency', 'USD')));

        $rates = CurrencyRate::where('base_currency', $base)->get(['target_currency', 'rate']);

        if ($rates->isEmpty()) {
            return response()->json([
                'base' => $base,
                'rates' => [$base => 1],
                'message' => 'Rates not yet synced.',
            ], 200);
        }

        $map = $rates->pluck('rate', 'target_currency')->map(fn ($r) => (float) $r)->toArray();
        $map[$base] = 1.0;

        return response()->json([
            'base' => $base,
            'rates' => $map,
        ]);
    }
}
