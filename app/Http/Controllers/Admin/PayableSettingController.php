<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\PayableSetting;
use Inertia\Inertia;
use App\Http\Controllers\Controller;

class PayableSettingController extends Controller
{
    public function index()
    {
        $setting = PayableSetting::firstOrCreate(
            [], // Attributes to find
            ['payment_percentage' => 0.00] // Attributes to create if not found
        );
        return Inertia::render('AdminDashboardPages/Settings/PayableAmount/Index', [
            'paymentPercentage' => $setting->payment_percentage,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'payment_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $setting = PayableSetting::firstOrCreate(
            [], // Attributes to find
            ['payment_percentage' => 0.00] // Attributes to create if not found
        );
        $setting->update(['payment_percentage' => $request->payment_percentage]);

        return redirect()->back()->with('success', 'Payable percentage updated successfully.');
    }

    public function getPercentage()
    {
        $setting = PayableSetting::firstOrCreate(
            [], // Attributes to find
            ['payment_percentage' => 0.00] // Attributes to create if not found
        );
        return response()->json(['payment_percentage' => $setting->payment_percentage]);
    }
}
