<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\User;
use App\Services\TapfiliateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AffiliateController extends Controller
{
    protected $tapfiliateService;

    public function __construct(TapfiliateService $tapfiliateService)
    {
        $this->tapfiliateService = $tapfiliateService;
    }


    public function dashboard()
    {
        $affiliate = Auth::user()->affiliate;

        if (!$affiliate) {
            return redirect()->route('home')->withErrors(['error' => 'You are not registered as an affiliate.']);
        }

        $totalEarnings = $affiliate->referrals()->where('status', 'approved')->sum('commission_amount');
        $pendingCommissions = $affiliate->referrals()->where('status', 'pending')->sum('commission_amount');
        $paidCommissions = $affiliate->referrals()->where('status', 'paid')->sum('commission_amount');
        $referrals = $affiliate->referrals()->with('referredUser', 'order')->latest()->get();

        $referralLink = url('/') . '/?tap_a=' . $affiliate->tapfiliate_id; // Or use referral_code if preferred

        return view('affiliate.dashboard', compact('affiliate', 'totalEarnings', 'pendingCommissions', 'paidCommissions', 'referrals', 'referralLink'));
    }
}
