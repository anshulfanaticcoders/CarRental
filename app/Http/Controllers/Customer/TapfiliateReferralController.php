<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User; // Assuming User model is in App\Models

class TapfiliateReferralController extends Controller
{
    public function index()
    {
        return Inertia::render('Customer/Referrals/TapfiliateDashboard');
    }

    public function getReferralCode(Request $request)
    {
        $user = $request->user();
        // Ensure the user has a Tapfiliate mapping and referral code
        if ($user && $user->tapfiliateMapping) {
            return response()->json(['referralCode' => $user->tapfiliateMapping->referral_code]);
        }
        return response()->json(['referralCode' => null], 404);
    }
}
