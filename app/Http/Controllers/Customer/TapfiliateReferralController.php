<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Services\TapfiliateService; // Import the TapfiliateService

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
        if ($user && $user->tapfiliateMapping && $user->tapfiliateMapping->referral_code) {
            return response()->json(['referralCode' => $user->tapfiliateMapping->referral_code]);
        }

        // If user doesn't have a referral code, try to create one via API
        $tapfiliateService = app(TapfiliateService::class);
        $affiliateData = $tapfiliateService->createAffiliateApi($user); // Use the new method

        if ($affiliateData) {
            // Re-fetch user to get the updated mapping with referral code
            $user->load('tapfiliateMapping');
            return response()->json(['referralCode' => $user->tapfiliateMapping->referral_code]);
        }

        return response()->json(['referralCode' => null], 404);
    }

    /**
     * Endpoint to trigger affiliate creation for existing users.
     */
    public function generateReferralCode(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user->tapfiliateMapping && $user->tapfiliateMapping->referral_code) {
            return response()->json(['referralCode' => $user->tapfiliateMapping->referral_code, 'message' => 'Referral code already exists.']);
        }

        $tapfiliateService = app(TapfiliateService::class);
        $affiliateData = $tapfiliateService->createAffiliateApi($user);

        if ($affiliateData) {
            $user->load('tapfiliateMapping'); // Reload relationship
            return response()->json(['referralCode' => $user->tapfiliateMapping->referral_code, 'message' => 'Referral code generated successfully.']);
        }

        return response()->json(['message' => 'Failed to generate referral code.'], 500);
    }
}
