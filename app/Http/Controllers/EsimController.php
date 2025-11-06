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

    private function formatBytes($bytes, $precision = 2)
    {
        if ($bytes == 0) {
            return '0 Bytes';
        }
        $base = log($bytes, 1024);
        $suffixes = array('Bytes', 'KB', 'MB', 'GB', 'TB');

        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
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

            $processedPlans = array_map(function ($plan) {
                // Per user feedback, not modifying the price from the API for display.
                if (isset($plan['totalData'])) {
                    $plan['data_amount'] = $this->formatBytes($plan['totalData']);
                }
                if (isset($plan['totalDuration']) && isset($plan['durationUnit'])) {
                    $plan['validity'] = $plan['totalDuration'] . ' ' . ucfirst(strtolower($plan['durationUnit'])) . 's';
                }
                if (isset($plan['packageCode'])) {
                    $plan['id'] = $plan['packageCode'];
                }
                return $plan;
            }, $plans);

            return response()->json([
                'success' => true,
                'data' => $processedPlans
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch eSIM plans for country ' . $countryCode . ': ' . $e->getMessage());

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
        $validated = $request->validate([
            'country_code' => 'required|string',
            'plan_id' => 'required|string',
            'email' => 'required|email',
            'customer_name' => 'required|string|max:255',
        ]);

        try {
            // Get plan details first to calculate price
            $plans = $this->esimService->getPlansByCountry($validated['country_code']);
            $selectedPlan = collect($plans)->firstWhere('id', $validated['plan_id']);

            if (!$selectedPlan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected plan not found'
                ], 404);
            }

            // Per user feedback, treating the API price as dollars directly.
            $originalPriceInDollars = $selectedPlan['price'] ?? 0;
            $finalPriceInDollars = $originalPriceInDollars; // No markup
            $markupAmount = 0;

            // Initialize Stripe
            Stripe::setApiKey(config('services.stripe.secret'));

            // Create Stripe checkout session
            $checkoutSession = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($selectedPlan['currency'] ?? 'usd'),
                        'product_data' => [
                            'name' => "eSIM - {$selectedPlan['name']} ({$validated['country_code']})",
                            'description' => "Data: {$selectedPlan['data_amount']} | Validity: {$selectedPlan['validity']}",
                        ],
                        'unit_amount' => intval($finalPriceInDollars * 100), // Convert to cents for Stripe
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
                    'original_price' => $originalPriceInDollars,
                    'markup_amount' => $markupAmount,
                    'final_price' => $finalPriceInDollars,
                ],
            ]);

            return response()->json([
                'success' => true,
                'checkout_url' => $checkoutSession->url,
                'session_id' => $checkoutSession->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create eSIM order: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to process eSIM order'
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
                    'plan_id' => $metadata['plan_id'],
                    'customer_email' => $metadata['email'],
                    'customer_name' => $metadata['customer_name'],
                ];

                $order = $this->esimService->createOrder($orderData);

                // Store order in database (optional - you can create an EsimOrder model)
                // For now, we'll just redirect with success message

                return redirect()->route('welcome')->with('success',
                    'eSIM order completed successfully! Check your email for activation instructions.');
            }

            return redirect()->route('welcome')->with('error', 'Payment was not successful');

        } catch (\Exception $e) {
            Log::error('Error processing eSIM payment success: ' . $e->getMessage());

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
