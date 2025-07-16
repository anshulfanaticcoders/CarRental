<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\User; // Assuming User model is used for authentication

class AffiliateController extends Controller
{
    /**
     * Fetch affiliate conversions from Tapfiliate API.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response|\Illuminate\Http\JsonResponse
     */
    public function getConversions(Request $request)
    {
        // Ensure the user is authenticated and has an associated customer_id or affiliate_id
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Assuming the user's customer_id (from your database) is used as the affiliate_id in Tapfiliate
        // You might need to adjust this based on how your user IDs map to Tapfiliate affiliate IDs.
        $affiliateId = $user->id; // Using user ID as affiliate ID for now, adjust if needed

        $tapfiliateApiKey = config('services.tapfiliate.api_key'); // Get API key from config/services.php

        if (!$tapfiliateApiKey) {
            Log::error('Tapfiliate API key is not set in config/services.php');
            return response()->json(['error' => 'Tapfiliate API key not configured'], 500);
        }

        try {
            $response = Http::withHeaders([
                'Api-Key' => $tapfiliateApiKey,
                'Content-Type' => 'application/json',
            ])->get("https://api.tapfiliate.com/v2/conversions/", [
                'affiliate_id' => $affiliateId,
                // Add other filters if needed, e.g., 'status' => 'approved'
            ]);

            $response->throw(); // Throw an exception if a client or server error occurred

            $conversions = $response->json();

            // You can process the conversions data here before sending to frontend
            // For example, calculate total earnings, filter by status, etc.
            $totalEarnings = collect($conversions)->sum('amount');

            return Inertia::render('Affiliate/Dashboard', [
                'conversions' => $conversions,
                'totalEarnings' => $totalEarnings,
                'affiliateId' => $affiliateId,
            ]);

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Tapfiliate API request failed: ' . $e->getMessage(), ['response' => $e->response ? $e->response->json() : null]);
            return response()->json(['error' => 'Failed to fetch affiliate data from Tapfiliate. Please try again later.'], 500);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred while fetching affiliate data: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }
}
