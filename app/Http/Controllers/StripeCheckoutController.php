<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\BookingExtra;
use App\Models\Customer;
use App\Models\PayableSetting;
use App\Services\AdobeCarService;
use App\Services\StripeBookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class StripeCheckoutController extends Controller
{
    public function __construct(private StripeBookingService $bookingService)
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
                'payment_method' => 'nullable|string',
                'location_details' => 'nullable|array',
                'location_instructions' => 'nullable|string',
                'driver_requirements' => 'nullable|array',
                'terms' => 'nullable|array',
            ]);

            // Get payment percentage from settings
            $payableSetting = PayableSetting::first();
            $paymentPercentage = $payableSetting ? $payableSetting->payment_percentage : 15;

            // Calculate payable amount (percentage of total)
            $payableAmount = round($validated['total_amount'] * ($paymentPercentage / 100), 2);
            $pendingAmount = round($validated['total_amount'] - $payableAmount, 2);

            // Get vehicle image (handle internal vehicles with images array)
            $vehicleImage = null;
            if ($validated['vehicle']['source'] === 'internal' && !empty($validated['vehicle']['images'])) {
                // Find primary image from images array
                foreach ($validated['vehicle']['images'] as $img) {
                    if (isset($img['image_type']) && $img['image_type'] === 'primary') {
                        $vehicleImage = $img['image_url'] ?? null;
                        break;
                    }
                }
                // Fallback to first gallery image
                if (!$vehicleImage) {
                    foreach ($validated['vehicle']['images'] as $img) {
                        if (isset($img['image_type']) && $img['image_type'] === 'gallery') {
                            $vehicleImage = $img['image_url'] ?? null;
                            break;
                        }
                    }
                }
            } else {
                $vehicleImage = $validated['vehicle']['image'] ?? null;
            }

            // Normalize currency (symbols to ISO codes)
            $currency = $validated['currency'] ?? 'EUR';
            $currencyCode = $this->normalizeCurrencyCode($currency);
            $providerCurrency = $this->resolveProviderCurrency($validated, $currencyCode);

            // Build line items for Stripe
            $lineItems = [
                [
                    'price_data' => [
                        'currency' => strtolower($currencyCode),
                        'product_data' => [
                            'name' => $validated['vehicle']['brand'] . ' ' . $validated['vehicle']['model'],
                            'description' => $validated['package'] . ' Package - ' . $validated['number_of_days'] . ' day(s)',
                            'images' => $vehicleImage ? [$vehicleImage] : [],
                        ],
                        'unit_amount' => (int) ($payableAmount * 100), // Stripe uses cents
                    ],
                    'quantity' => 1,
                ],
            ];

            // Prepare metadata for webhook
            $userId = $request->user()?->id;
            $metadata = [
                'user_id' => $userId,
                'vehicle_id' => $validated['vehicle']['id'] ?? '',
                'vehicle_source' => $validated['vehicle']['source'] ?? 'greenmotion',
                'vehicle_brand' => $validated['vehicle']['brand'] ?? '',
                'vehicle_model' => $validated['vehicle']['model'] ?? '',
                'vehicle_image' => $vehicleImage ?? '',
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
                'currency' => $currencyCode,
                'provider_currency' => $providerCurrency,
                'customer_name' => $validated['customer']['name'] ?? '',
                'customer_email' => $validated['customer']['email'] ?? '',
                'customer_phone' => $validated['customer']['phone'] ?? '',
                'customer_driver_age' => $validated['customer']['driver_age'] ?? '',
                'flight_number' => $validated['customer']['flight_number'] ?? '',
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
                'payment_method' => $validated['payment_method'] ?? 'card',
                'renteon_connector_id' => $validated['vehicle']['connector_id'] ?? null,
                'renteon_pickup_office_id' => $validated['vehicle']['provider_pickup_office_id'] ?? null,
                'renteon_dropoff_office_id' => $validated['vehicle']['provider_dropoff_office_id'] ?? null,
                'renteon_pricelist_id' => $validated['vehicle']['pricelist_id'] ?? null,
                'renteon_price_date' => $validated['vehicle']['price_date'] ?? null,
                'renteon_prepaid' => $validated['vehicle']['prepaid'] ?? true,
                'ok_mobility_token' => $validated['vehicle']['ok_mobility_token'] ?? null,
                'ok_mobility_group_id' => $validated['vehicle']['ok_mobility_group_id'] ?? null,
                'ok_mobility_rate_code' => $validated['vehicle']['ok_mobility_rate_code'] ?? null,
                'driver_license_number' => $validated['customer']['driver_license_number'] ?? null,
                'notes' => $validated['customer']['notes'] ?? null,
            ];

            $customerMetadata = array_filter([
                'customer_address' => $validated['customer']['address'] ?? null,
                'customer_city' => $validated['customer']['city'] ?? null,
                'customer_postal_code' => $validated['customer']['postal_code'] ?? null,
                'customer_country' => $validated['customer']['country'] ?? null,
            ], static fn($value) => $value !== null && $value !== '');

            if (!empty($customerMetadata)) {
                $metadata = array_merge($metadata, $customerMetadata);
            }


            // Create Stripe Checkout Session
            $currentLocale = app()->getLocale();
            $supportedLocales = ['en', 'fr', 'nl', 'es', 'ar'];
            if (!in_array($currentLocale, $supportedLocales)) {
                $currentLocale = 'en';
            }

            // Determine supported payment method types based on currency
            $availableMethods = ['card'];
            
            // Bancontact only supports EUR
            if (strtoupper($currency) === 'EUR') {
                $availableMethods[] = 'bancontact';
            }
            
            // Klarna supports multiple currencies
            $klarnaCurrencies = ['EUR', 'USD', 'GBP', 'DKK', 'NOK', 'SEK', 'CHF'];
            if (in_array(strtoupper($currency), $klarnaCurrencies)) {
                $availableMethods[] = 'klarna';
            }

            // Respect the selected payment method if it's available for the current currency
            // To avoid redundancy on the Stripe page, we only send the selected method.
            $requestedMethod = $validated['payment_method'] ?? 'card';
            if (in_array($requestedMethod, $availableMethods)) {
                $paymentMethodTypes = [$requestedMethod];
            } else {
                // Fallback to all available if the requested one is invalid for the currency
                $paymentMethodTypes = $availableMethods;
            }

            $session = StripeSession::create([
                'payment_method_types' => $paymentMethodTypes,
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

    private function buildProviderMetadata(array $validated): array
    {
        $vehicle = $validated['vehicle'] ?? [];
        $package = $validated['package'] ?? null;
        $product = null;
        if (!empty($vehicle['products']) && is_array($vehicle['products'])) {
            foreach ($vehicle['products'] as $entry) {
                if (($entry['type'] ?? null) === $package) {
                    $product = $entry;
                    break;
                }
            }
        }

        return [
            'provider' => $vehicle['source'] ?? null,
            'quoteid' => $validated['quoteid'] ?? ($vehicle['quoteid'] ?? null),
            'rental_code' => $package,
            'currency' => $validated['currency'] ?? ($vehicle['currency'] ?? null),
            'vehicle_total' => $validated['vehicle_total'] ?? null,
            'pickup_location_id' => $vehicle['provider_pickup_id'] ?? null,
            'dropoff_location_id' => $vehicle['provider_return_id'] ?? null,
            'location' => $validated['location_details'] ?? null,
            'location_instructions' => $validated['location_instructions'] ?? null,
            'driver_requirements' => $validated['driver_requirements'] ?? null,
            'terms' => $validated['terms'] ?? null,
            'selected_product' => [
                'type' => $product['type'] ?? $package,
                'total' => $product['total'] ?? ($validated['vehicle_total'] ?? null),
                'currency' => $product['currency'] ?? ($validated['currency'] ?? null),
                'deposit' => $product['deposit'] ?? null,
                'excess' => $product['excess'] ?? null,
                'mileage' => $product['mileage'] ?? null,
                'costperextradistance' => $product['costperextradistance'] ?? null,
                'fuelpolicy' => $product['fuelpolicy'] ?? null,
                'minage' => $product['minage'] ?? null,
            ],
            'extras_selected' => $validated['detailed_extras'] ?? [],
        ];
    }

    private function resolveProviderCurrency(array $validated, string $fallback): string
    {
        $vehicle = $validated['vehicle'] ?? [];
        $providerCurrency = $vehicle['currency'] ?? null;

        if (!$providerCurrency && !empty($vehicle['products']) && is_array($vehicle['products'])) {
            $package = $validated['package'] ?? null;
            foreach ($vehicle['products'] as $product) {
                if (!is_array($product)) {
                    continue;
                }
                if ($package !== null && ($product['type'] ?? null) !== $package) {
                    continue;
                }
                $providerCurrency = $product['currency'] ?? null;
                if ($providerCurrency) {
                    break;
                }
            }

            if (!$providerCurrency) {
                foreach ($vehicle['products'] as $product) {
                    if (is_array($product) && !empty($product['currency'])) {
                        $providerCurrency = $product['currency'];
                        break;
                    }
                }
            }
        }

        return $this->normalizeCurrencyCode($providerCurrency ?: $fallback);
    }

    private function normalizeCurrencyCode($currency): string
    {
        $value = $currency ?? 'EUR';

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed !== '') {
                $value = $trimmed;
            }
        }

        $symbolMap = [
            "\u{20AC}" => 'EUR',
            "\u{00A3}" => 'GBP',
            "\u{20B9}" => 'INR',
            "\u{20BD}" => 'RUB',
            'A$' => 'AUD',
            '$' => 'USD',
        ];

        if (is_string($value) && array_key_exists($value, $symbolMap)) {
            return $symbolMap[$value];
        }

        $upper = strtoupper((string) $value);
        return $upper !== '' ? $upper : 'EUR';
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

            $session = null;

            if ($booking) {
                $needsUpdate = !in_array($booking->booking_status, ['confirmed', 'completed'], true)
                    || !in_array($booking->payment_status, ['partial', 'paid'], true);

                if ($needsUpdate) {
                    Log::info('Booking pending after success, attempting Stripe fetch', ['booking_id' => $booking->id, 'session_id' => $sessionId]);
                    $session = StripeSession::retrieve($sessionId);

                    if ($session->payment_status === 'paid') {
                        Log::info('Session paid, updating booking via service', ['session_id' => $sessionId]);
                        $booking = $bookingService->createBookingFromSession($session);
                    } else {
                        Log::warning('Session not paid', ['session_id' => $sessionId, 'status' => $session->payment_status]);
                        return redirect('/')->with('error', 'Payment not completed.');
                    }
                } else {
                    Log::info('Booking found locally', ['booking_id' => $booking->id]);
                }
            } else {
                // Fallback: If webhook didn't run, fetch from Stripe and create it now
                Log::info('Booking not found locally, attempting fetch from Stripe', ['session_id' => $sessionId]);

                $session = StripeSession::retrieve($sessionId);

                if ($session->payment_status === 'paid') {
                    Log::info('Session paid, creating booking via service', ['session_id' => $sessionId]);
                    $booking = $bookingService->createBookingFromSession($session);
                } else {
                    Log::warning('Session not paid', ['session_id' => $sessionId, 'status' => $session->payment_status]);
                    return redirect('/')->with('error', 'Payment not completed.');
                }
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
