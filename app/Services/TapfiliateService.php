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

    /**
     * Creates an affiliate in Tapfiliate via API and fetches their referral code.
     * This method is primarily for existing users who need a code generated.
     */
    public function createAffiliateApi(User $user)
    {
        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . 'affiliates/', [
                'firstname' => $user->first_name,
                'lastname' => $user->last_name,
                'email' => $user->email,
                'customer_id' => $user->id,
            ]);

            if ($response->successful()) {
                $affiliateData = $response->json();
                $tapfiliateAffiliateId = $affiliateData['id'];

                // Fetch the full affiliate details to get the referral_code
                $referralCode = $this->getAffiliateReferralCode($tapfiliateAffiliateId);

                TapfiliateUserMapping::create([
                    'user_id' => $user->id,
                    'tapfiliate_affiliate_id' => $tapfiliateAffiliateId,
                    'referral_code' => $referralCode,
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

    /**
     * Fetches an affiliate's referral code from Tapfiliate API.
     */
    public function getAffiliateReferralCode(string $tapfiliateAffiliateId): ?string
    {
        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->apiUrl . 'affiliates/' . $tapfiliateAffiliateId);

            if ($response->successful()) {
                $fullAffiliateData = $response->json();
                return data_get($fullAffiliateData, 'referral_code');
            } else {
                Log::error('Failed to fetch referral code for affiliate ' . $tapfiliateAffiliateId, [
                    'status' => $response->status(),
                    'response_body' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception fetching referral code for affiliate ' . $tapfiliateAffiliateId . ': ' . $e->getMessage(), [
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
                'external_id' => $externalId,
                'amount' => $amount,
                'customer_id' => $customerId,
                'referral_code' => $referralCode,
                'program_id' => $programId ?? env('TAPFILIATE_PROGRAM_ID'),
                'metadata' => $metadata,
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
