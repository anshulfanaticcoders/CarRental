<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\TapfiliateService; // Assuming you might want to use it here for client-side tracking if needed

class TrackReferrals
{
    protected $tapfiliateService;

    public function __construct(TapfiliateService $tapfiliateService)
    {
        $this->tapfiliateService = $tapfiliateService;
    }

    public function handle(Request $request, Closure $next)
    {
        if ($request->has('ref')) {
            $referralCode = $request->get('ref');
            // Store in session for registration
            session(['referral_code' => $referralCode]);

            // Optionally, you can also fire a client-side click event here if you have a global layout
            // that includes the Tapfiliate JS, but it's often handled by Tapfiliate's own script
            // if it's present on the landing page.
            // For server-side tracking, we rely on the session.
        }

        return $next($request);
    }
}
