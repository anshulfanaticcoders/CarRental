<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\BookingExtra;
use App\Models\Customer;
use App\Models\PayableSetting;
use App\Services\AdobeCarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class StripeCheckoutController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe Checkout Session
     */
    public function createSession(Request $request)
    {
        try {
            $validated = $request->validate([
                'vehicle' => 'required|array',
                'package' => 'required|string',
                'extras' => 'nullable|array',
                'customer' => 'required|array',
                'pickup_date' => 'required|string',
                'pickup_time' => 'required|string',
                'dropoff_date' => 'required|string',
                'dropoff_time' => 'required|string',
                'pickup_location' => 'required|string',
                'dropoff_location' => 'nullable|string',
                'total_amount' => 'required|numeric',
                'currency' => 'required|string',
                'number_of_days' => 'required|integer|min:1',
                'detailed_extras' => 'nullable|array',
                'protection_code' => 'nullable|string',
                'protection_amount' => 'nullable|numeric',
                'quoteid' => 'nullable|string',
                'rentalCode' => 'nullable|string',
                'vehicle_total' => 'nullable|numeric',
            ]);

            // Get payment percentage from settings
            $payableSetting = PayableSetting::first();
            $paymentPercentage = $payableSetting ? $payableSetting->payment_percentage : 15;

            // Calculate payable amount (percentage of total)
            $payableAmount = round($validated['total_amount'] * ($paymentPercentage / 100), 2);
            $pendingAmount = round($validated['total_amount'] - $payableAmount, 2);

            // Build line items for Stripe
            $lineItems = [
                [
                    'price_data' => [
                        'currency' => strtolower($validated['currency']),
                        'product_data' => [
                            'name' => $validated['vehicle']['brand'] . ' ' . $validated['vehicle']['model'],
                            'description' => $validated['package'] . ' Package - ' . $validated['number_of_days'] . ' day(s)',
                            'images' => $validated['vehicle']['image'] ? [$validated['vehicle']['image']] : [],
                        ],
                        'unit_amount' => (int) ($payableAmount * 100), // Stripe uses cents
                    ],
                    'quantity' => 1,
                ],
            ];

            // Prepare metadata for webhook
            $metadata = [
                'vehicle_id' => $validated['vehicle']['id'] ?? '',
                'vehicle_source' => $validated['vehicle']['source'] ?? 'greenmotion',
                'vehicle_brand' => $validated['vehicle']['brand'] ?? '',
                'vehicle_model' => $validated['vehicle']['model'] ?? '',
                'vehicle_image' => $validated['vehicle']['image'] ?? '',
                'vehicle_category' => $validated['vehicle']['category'] ?? $validated['vehicle']['vehicle_category'] ?? '',
                'vehicle_class' => $validated['vehicle']['class'] ?? '',
                'adobe_category' => $validated['vehicle']['adobe_category'] ?? '', // Adobe single-letter category code
                'package' => $validated['package'],
                'pickup_date' => $validated['pickup_date'],
                'pickup_time' => $validated['pickup_time'],
                'dropoff_date' => $validated['dropoff_date'],
                'dropoff_time' => $validated['dropoff_time'],
                'pickup_location' => $validated['pickup_location'],
                'dropoff_location' => $validated['dropoff_location'] ?? $validated['pickup_location'],
                'number_of_days' => $validated['number_of_days'],
                'total_amount' => $validated['total_amount'],
                'payable_amount' => $payableAmount,
                'pending_amount' => $pendingAmount,
                'currency' => $validated['currency'],
                'customer_name' => $validated['customer']['name'] ?? '',
                'customer_email' => $validated['customer']['email'] ?? '',
                'customer_phone' => $validated['customer']['phone'] ?? '',
                'customer_driver_age' => $validated['customer']['driver_age'] ?? '',
                'protection_code' => $validated['protection_code'] ?? '',
                'protection_amount' => $validated['protection_amount'] ?? 0,
                'sipp_code' => $validated['vehicle']['sipp_code'] ?? '',
                'pickup_location_code' => $validated['vehicle']['provider_pickup_id'] ?? '', // For Locauto, this holds the XML location code
                'return_location_code' => $validated['vehicle']['provider_return_id'] ?? $validated['vehicle']['provider_pickup_id'] ?? '',
                'extras' => json_encode($validated['extras'] ?? []),
                'extras_data' => json_encode($validated['detailed_extras'] ?? []), // Encode detailed extras
                'quoteid' => !empty($validated['quoteid']) ? $validated['quoteid'] : ($validated['vehicle']['quoteid'] ?? ''),
                'rental_code' => !empty($validated['rentalCode']) ? $validated['rentalCode'] : ($validated['vehicle']['rentalCode'] ?? ''),
                'vehicle_total' => $validated['vehicle_total'] ?? $validated['total_amount'],
            ];


            // Create Stripe Checkout Session
            $currentLocale = app()->getLocale();
            $supportedLocales = ['en', 'fr', 'nl', 'es', 'ar'];
            if (!in_array($currentLocale, $supportedLocales)) {
                $currentLocale = 'en';
            }

            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                // Use route() to generate the correct URL with locale, but we need to append the session_id placeholder manually
                // to avoid URL encoding issues with the curly braces.
                'success_url' => route('booking.success', ['locale' => $currentLocale]) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('booking.cancel', ['locale' => $currentLocale]),
                'customer_email' => $validated['customer']['email'] ?? null,
                'metadata' => $metadata,
                'payment_intent_data' => [
                    'metadata' => $metadata,
                ],
            ]);

            Log::info('Stripe Checkout Session created', [
                'session_id' => $session->id,
                'vehicle' => $validated['vehicle']['brand'] . ' ' . $validated['vehicle']['model'],
                'amount' => $payableAmount,
            ]);

            return response()->json([
                'success' => true,
                'session_id' => $session->id,
                'url' => $session->url,
            ]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe API Error', [
                'message' => $e->getMessage(),
                'code' => $e->getStripeCode(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Payment initialization failed. Please try again.',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Checkout Session Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Success redirect page
     */
    public function success(Request $request, \App\Services\StripeBookingService $bookingService)
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            Log::warning('Success page accessed without session_id');
            return redirect('/')->with('error', 'Invalid session.');
        }

        Log::info('Success page accessed', ['session_id' => $sessionId]);

        try {
            // Find existing booking
            $booking = Booking::where('stripe_session_id', $sessionId)
                ->with(['customer', 'payments', 'vehicle']) // Corrected relationships
                ->first();

            // Fallback: If webhook didn't run, fetch from Stripe and create it now
            if (!$booking) {
                Log::info('Booking not found locally, attempting fetch from Stripe', ['session_id' => $sessionId]);

                $session = StripeSession::retrieve($sessionId);

                if ($session->payment_status === 'paid') {
                    Log::info('Session paid, creating booking via service', ['session_id' => $sessionId]);
                    $booking = $bookingService->createBookingFromSession($session);
                } else {
                    Log::warning('Session not paid', ['session_id' => $sessionId, 'status' => $session->payment_status]);
                    return redirect('/')->with('error', 'Payment not completed.');
                }
            } else {
                Log::info('Booking found locally', ['booking_id' => $booking->id]);
            }

            // Re-fetch with relations to be sure
            $booking = Booking::where('id', $booking->id)->with(['customer', 'payments', 'extras'])->first();

            // Prepare props for Success.vue
            $vehicleData = [
                'brand' => explode(' ', $booking->vehicle_name)[0] ?? '',
                'model' => $booking->vehicle_name, // fallback
                'image' => $booking->vehicle_image,
                'images' => $booking->vehicle_image ? [['image_url' => $booking->vehicle_image, 'image_type' => 'primary']] : [],
                'transmission' => 'Manual', // fallback
                'fuel' => 'Petrol', // fallback
                'seating_capacity' => 5, // fallback
                'price_per_day' => $booking->base_price / ($booking->total_days ?: 1), // avoid div by zero
                'currency' => $booking->booking_currency,
            ];

            return inertia('Booking/Success', [
                'booking' => $booking,
                'customer' => $booking->customer,
                'payment' => $booking->payments->first(), // Pass the first payment object
                'vehicle' => $vehicleData,
                'session_id' => $sessionId,
            ]);

        } catch (\Exception $e) {
            Log::error('Success page error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect('/')->with('error', 'Could not retrieve booking details. Please contact support with reference: ' . $sessionId);
        }
    }

    /**
     * Cancel redirect page
     */
    public function cancel()
    {
        return inertia('Booking/Cancel', [
            'message' => 'Your payment was cancelled. You can try again anytime.',
        ]);
    }
}
