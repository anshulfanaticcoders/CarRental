<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CurrencyRate;
use App\Support\CurrencyRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index(CurrencyRegistry $currencies): JsonResponse
    {
        $supported = $currencies->selectableCodes();

        return response()->json([
            'supported' => $supported,
            'base' => $currencies->baseCurrency(),
            'currencies' => $currencies->publicPayload($supported),
            'popular' => $currencies->popularCodes($supported),
            'default' => $currencies->defaultCurrency(),
            'version' => 'currency-registry-v1',
        ]);
    }

    public function rates(Request $request): JsonResponse
    {
        $base = app(CurrencyRegistry::class)->normalize(
            $request->query('base', config('currency.base_currency', 'EUR')),
            config('currency.base_currency', 'EUR')
        );

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
