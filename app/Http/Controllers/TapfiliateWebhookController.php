<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TapfiliateWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('Tapfiliate Webhook Received', $request->all());

        // You might want to implement webhook signature verification here for security
        // Tapfiliate typically sends a signature in a header (e.g., X-Tapfiliate-Signature)
        // and you'd verify it against a shared secret.

        $eventType = $request->input('event');
        $data = $request->input('data');

        if (!$eventType || !$data) {
            Log::warning('Tapfiliate Webhook: Missing event or data.', $request->all());
            return response()->json(['message' => 'Missing event or data'], 400);
        }

        switch ($eventType) {
            case 'affiliate.new':
                return $this->handleNewAffiliate($data);
            case 'affiliate.update':
                return $this->handleUpdateAffiliate($data);
            // Add other event types as needed (e.g., conversion.new, conversion.update)
            default:
                Log::info('Tapfiliate Webhook: Unhandled event type: ' . $eventType);
                return response()->json(['message' => 'Event type not handled'], 200);
        }
    }

    protected function handleNewAffiliate(array $data)
    {
        $tapfiliateId = $data['id'] ?? null;
        $email = $data['email'] ?? null;
        $firstname = $data['firstname'] ?? null;
        $lastname = $data['lastname'] ?? null;
        $referralCode = $data['referral_code'] ?? Str::random(10); // Tapfiliate usually provides this

        if (!$tapfiliateId || !$email) {
            Log::error('Tapfiliate Webhook: Missing data for new affiliate.', $data);
            return response()->json(['message' => 'Missing data for new affiliate'], 400);
        }

        // Check if user already exists by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Create a new user if they don't exist
            $user = User::create([
                'first_name' => $firstname,
                'last_name' => $lastname,
                'email' => $email,
                'password' => Hash::make(Str::random(16)), // Generate a random password
                'role' => 'customer', // Default role
                'is_affiliate' => true,
                'email_verified_at' => now(), // Assume verified if coming from Tapfiliate
            ]);
            Log::info('Tapfiliate Webhook: Created new user for affiliate.', ['user_id' => $user->id]);
        } else {
            // If user exists, ensure they are marked as affiliate
            if (!$user->is_affiliate) {
                $user->update(['is_affiliate' => true]);
                Log::info('Tapfiliate Webhook: Updated existing user to affiliate.', ['user_id' => $user->id]);
            }
        }

        // Create or update affiliate record
        $affiliate = Affiliate::updateOrCreate(
            ['tapfiliate_id' => $tapfiliateId],
            [
                'user_id' => $user->id,
                'referral_code' => $referralCode,
                'commission_rate' => 10.00, // Default commission rate, adjust as needed
            ]
        );

        Log::info('Tapfiliate Webhook: New affiliate processed.', ['affiliate_id' => $affiliate->id, 'tapfiliate_id' => $tapfiliateId]);
        return response()->json(['message' => 'New affiliate processed'], 200);
    }

    protected function handleUpdateAffiliate(array $data)
    {
        $tapfiliateId = $data['id'] ?? null;
        $email = $data['email'] ?? null;

        if (!$tapfiliateId || !$email) {
            Log::error('Tapfiliate Webhook: Missing data for update affiliate.', $data);
            return response()->json(['message' => 'Missing data for update affiliate'], 400);
        }

        $affiliate = Affiliate::where('tapfiliate_id', $tapfiliateId)->first();

        if ($affiliate) {
            // Update affiliate details if needed
            $affiliate->update([
                'commission_rate' => $data['commission_rate'] ?? $affiliate->commission_rate,
                // Update other fields as necessary
            ]);
            Log::info('Tapfiliate Webhook: Affiliate updated.', ['affiliate_id' => $affiliate->id, 'tapfiliate_id' => $tapfiliateId]);
        } else {
            Log::warning('Tapfiliate Webhook: Affiliate not found for update.', ['tapfiliate_id' => $tapfiliateId]);
            // Optionally, call handleNewAffiliate if an update comes for a non-existent local affiliate
            // return $this->handleNewAffiliate($data);
        }

        return response()->json(['message' => 'Affiliate updated'], 200);
    }
}
