<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'currency' => 'required|string|in:USD,EUR,GBP,JPY,AUD,CAD,CHF,CNH,HKD,SGD,SEK,KRW,NOK,NZD,INR,MXN,BRL,RUB,ZAR,AED,MAD,TRY,JOD,ISK,AZN,MYR,OMR,UGX,NIO',
        ]);

        session(['currency' => $request->currency]);

        return back();
    }
}
