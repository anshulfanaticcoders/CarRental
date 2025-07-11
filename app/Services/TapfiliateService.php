<?php

namespace App\Services;

use App\Models\User;
use App\Models\TapfiliateUserMapping;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TapfiliateService
{
    protected $apiKey;
    protected $apiUrl = 'https://api.tapfiliate.com/1.6/';

    public function __construct()
    {
        $this->apiKey = env('TAPFILIATE_API_KEY');
    }

    public function createAffiliate(User $user)
    {
        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . 'affiliates/', [
                'firstname' => $user->first_name, // Assuming User model has first_name
                'lastname' => $user->last_name,   // Assuming User model has last_name
                'email' => $user->email,
                'customer_id' => $user->id, // Your internal user ID
            ]);

            if ($response->successful()) {
                $affiliateData = $response->json();
                $tapfiliateAffiliateId = $affiliateData['id'];

                // Now, fetch the full affiliate details to get the referral_code
                $affiliateDetailsResponse = Http::withHeaders([
                    'Api-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->get($this->apiUrl . 'affiliates/' . $tapfiliateAffiliateId);

                $referralCode = null;
                if ($affiliateDetailsResponse->successful()) {
                    $fullAffiliateData = $affiliateDetailsResponse->json();
                    $referralCode = data_get($fullAffiliateData, 'referral_code');
                    Log::info('Fetched referral code for affiliate ' . $tapfiliateAffiliateId . ': ' . $referralCode);
                } else {
                    Log::error('Failed to fetch full affiliate details for ' . $tapfiliateAffiliateId, [
                        'status' => $affiliateDetailsResponse->status(),
                        'response_body' => $affiliateDetailsResponse->body()
                    ]);
                }

                TapfiliateUserMapping::create([
                    'user_id' => $user->id,
                    'tapfiliate_affiliate_id' => $tapfiliateAffiliateId,
                    'referral_code' => $referralCode, // Use the fetched referral code
                    'is_active' => true,
                ]);
                Log::info('Tapfiliate affiliate created and mapped for user: ' . $user->id);
                return $affiliateData;
            } else {
                Log::error('Failed to create Tapfiliate affiliate for user: ' . $user->id, [
                    'status' => $response->status(),
                    'response_body' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception creating Tapfiliate affiliate: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    public function trackConversion($externalId, $amount, $customerId, $referralCode = null, $programId = null, $metadata = [])
    {
        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . 'conversions/', [
                'external_id' => $externalId, // Your unique ID for this conversion (e.g., user ID for signup, booking ID for booking)
                'amount' => $amount, // 0 for signup, booking total for booking
                'customer_id' => $customerId, // The ID of the user who converted
                'referral_code' => $referralCode, // The referral code used (if available)
                'program_id' => $programId ?? env('TAPFILIATE_PROGRAM_ID'), // Your Tapfiliate program ID
                'metadata' => $metadata, // Any additional data you want to send
            ]);

            if ($response->successful()) {
                Log::info('Tapfiliate conversion tracked: ' . $externalId);
                return $response->json();
            } else {
                Log::error('Failed to track Tapfiliate conversion: ' . $externalId, ['response' => $response->body()]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception tracking Tapfiliate conversion: ' . $e->getMessage(), ['external_id' => $externalId]);
            return null;
        }
    }
}
