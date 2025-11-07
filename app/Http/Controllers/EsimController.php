<?php

namespace App\Http\Controllers;

use App\Services\EsimAccessService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class EsimController extends Controller
{
    private EsimAccessService $esimService;

    public function __construct(EsimAccessService $esimService)
    {
        $this->esimService = $esimService;
    }

    
    /**
     * Get available countries for eSIM plans
     */
    public function getCountries(string $locale): JsonResponse
    {
        try {
            $countries = $this->esimService->getCountries();

            return response()->json([
                'success' => true,
                'data' => $countries
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch eSIM countries: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available countries'
            ], 500);
        }
    }

    /**
     * Get eSIM plans for a specific country
     */
    public function getPlansByCountry(string $locale, string $countryCode): JsonResponse
    {
        try {
            $plans = $this->esimService->getPlansByCountry($countryCode);

            // Return raw API data without any manipulation
            return response()->json([
                'success' => true,
                'data' => $plans
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch plans for selected country'
            ], 500);
        }
    }

    /**
     * Create eSIM order and initiate Stripe payment
     */
    public function createOrder(string $locale, Request $request): JsonResponse
    {
        // Debug: Log incoming request
        error_log('eSIM Order Request Data: ' . json_encode($request->all()));

        $validated = $request->validate([
            'country_code' => 'required|string',
            'plan_id' => 'required|string',
            'email' => 'required|email',
            'customer_name' => 'required|string|max:255',
        ]);

        error_log('eSIM Validated Data: ' . json_encode($validated));

        try {
            // Get plan details from API (raw data)
            $plans = $this->esimService->getPlansByCountry($validated['country_code']);
            error_log('eSIM Plans Retrieved: ' . json_encode(array_slice($plans, 0, 2)));

            $selectedPlan = collect($plans)->firstWhere('packageCode', $validated['plan_id']);

            if (!$selectedPlan) {
                $selectedPlan = collect($plans)->firstWhere('code', $validated['plan_id']);
            }

            if (!$selectedPlan) {
                error_log('eSIM Plan not found. Plan ID: ' . $validated['plan_id']);
                error_log('Available plan codes: ' . json_encode(array_column($plans, 'packageCode')));
                return response()->json([
                    'success' => false,
                    'message' => 'Selected plan not found'
                ], 404);
            }

            // Use raw price and currency from API
            $rawPrice = $selectedPlan['price'] ?? 0;

            // Convert price: divide by 10000 as per eSIM Access documentation
            $price = $rawPrice / 10000;

            // Stripe needs amount in cents and currency must be 3-letter lowercase
            $amountInCents = round($price * 100);
            $currency = 'usd'; // Force USD since we're converting to USD

            // Initialize Stripe
            $stripeSecret = config('services.stripe.secret');
            if (!$stripeSecret) {
                throw new \Exception('Stripe secret key not configured');
            }
            Stripe::setApiKey($stripeSecret);

            // Create Stripe checkout session
            $checkoutSession = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => "eSIM - {$selectedPlan['name']}",
                            'description' => "Plan: {$selectedPlan['name']}",
                        ],
                        'unit_amount' => $amountInCents,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('esim.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('esim.cancel'),
                'customer_email' => $validated['email'],
                'metadata' => [
                    'type' => 'esim',
                    'country_code' => $validated['country_code'],
                    'plan_id' => $validated['plan_id'],
                    'customer_name' => $validated['customer_name'],
                    'email' => $validated['email'],
                ],
            ]);

            return response()->json([
                'success' => true,
                'checkout_url' => $checkoutSession->url,
                'session_id' => $checkoutSession->id
            ]);

        } catch (\Exception $e) {
            // Log the actual error for debugging
            error_log('eSIM Order Error: ' . $e->getMessage());
            error_log('Stripe Error: ' . ($e->getMessage()));

            return response()->json([
                'success' => false,
                'message' => 'Failed to process eSIM order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle successful payment
     */
    public function paymentSuccess(string $locale, Request $request)
    {
        $sessionId = $request->get('session_id');

        if (!$sessionId) {
            return redirect()->route('welcome')->with('error', 'Invalid payment session');
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $session = Session::retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                $metadata = $session->metadata->toArray();

                // Create eSIM order with eSIM Access API
                $orderData = [
                    'customer_email' => $metadata['email'],
                    'plan_id' => $metadata['plan_id'],
                    'quantity' => 1,
                    'referenceCode' => $sessionId,
                ];

                $order = $this->esimService->createOrder($orderData);

                return redirect()->route('welcome')->with('success',
                    'eSIM order completed successfully! Check your email for activation instructions.');
            }

            return redirect()->route('welcome')->with('error', 'Payment was not successful');

        } catch (\Exception $e) {
            return redirect()->route('welcome')->with('error',
                'Payment was successful but there was an issue processing your eSIM order. Please contact support.');
        }
    }

    /**
     * Handle cancelled payment
     */
    public function paymentCancel(string $locale)
    {
        return redirect()->route('welcome')->with('info',
            'eSIM purchase was cancelled. You can try again anytime.');
    }
}
