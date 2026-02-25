<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\BookingExtra;
use App\Models\Customer;
use App\Models\PayableSetting;
use App\Models\StripeCheckoutPayload;
use App\Services\AdobeCarService;
use App\Services\StripeBookingService;
use App\Services\CurrencyConversionService;
use App\Services\PriceVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class StripeCheckoutController extends Controller
{
    /**
     * Provider APIs return net pricing (base totals).
     * We apply a markup for customer-facing totals but still send net totals to providers.
     */
    private const DEFAULT_PROVIDER_MARKUP_PERCENT = 15.0;

    public function __construct(private StripeBookingService $bookingService)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    private function isExternalProviderSource(?string $source): bool
    {
        // Apply platform fee to ALL vehicles including internal
        $normalized = strtolower(trim((string) $source));
        return $normalized !== '';
    }

    private function resolveProviderMarkupPercent(): float
    {
        $raw = env('PROVIDER_MARKUP_PERCENT');
        $percent = is_numeric($raw) ? (float) $raw : self::DEFAULT_PROVIDER_MARKUP_PERCENT;
        if ($percent < 0) {
            $percent = 0.0;
        }

        return $percent;
    }

    private function resolveProviderMarkupRate(): float
    {
        return $this->resolveProviderMarkupPercent() / 100;
    }

    private function grossUpProviderAmount(float $netAmount, ?string $vehicleSource): float
    {
        if (!$this->isExternalProviderSource($vehicleSource)) {
            return round($netAmount, 2);
        }

        $markupRate = $this->resolveProviderMarkupRate();
        if ($markupRate <= 0) {
            return round($netAmount, 2);
        }

        return round($netAmount * (1 + $markupRate), 2);
    }

    private function compactStripeMetadata(array $metadata): array
    {
        $filtered = array_filter(
            $metadata,
            static function ($value): bool {
                if ($value === null) {
                    return false;
                }
                if (is_string($value)) {
                    return $value !== '';
                }
                if (is_array($value)) {
                    return !empty($value);
                }
                return true;
            }
        );

        if (count($filtered) <= 50) {
            return $filtered;
        }

        $dropPriority = [
            'notes',
            'driver_license_number',
            'customer_address',
            'customer_city',
            'customer_postal_code',
            'customer_country',
            'favrica_rez_id',
            'favrica_cars_park_id',
            'favrica_group_id',
            'favrica_car_web_id',
            'favrica_reservation_source_id',
            'favrica_drop_fee',
            'xdrive_rez_id',
            'xdrive_cars_park_id',
            'xdrive_group_id',
            'xdrive_car_web_id',
            'xdrive_reservation_source_id',
            'xdrive_reservation_source',
            'xdrive_drop_fee',
            'ok_mobility_token',
            'ok_mobility_group_id',
            'ok_mobility_rate_code',
        ];

        foreach ($dropPriority as $key) {
            if (isset($filtered[$key])) {
                unset($filtered[$key]);
                if (count($filtered) <= 50) {
                    break;
                }
            }
        }

        if (count($filtered) > 50) {
            Log::warning('Stripe metadata still exceeds limit after compaction', [
                'metadata_count' => count($filtered),
                'metadata_keys' => array_keys($filtered),
            ]);
        }

        return $filtered;
    }

    private function stripOversizedMetadata(array $metadata): array
    {
        $maxLength = 500;
        $oversizedKeys = [];

        foreach (['location_details', 'dropoff_location_details', 'location_instructions', 'driver_requirements', 'terms'] as $key) {
            if (!isset($metadata[$key])) {
                continue;
            }
            if (is_string($metadata[$key]) && strlen($metadata[$key]) > $maxLength) {
                unset($metadata[$key]);
                $oversizedKeys[] = $key;
            }
        }

        if (!empty($oversizedKeys)) {
            Log::info('Stripe metadata oversized values moved to payload', [
                'keys' => $oversizedKeys,
            ]);
        }

        return $metadata;
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
                'optional_extras' => 'nullable|array',
                'protection_code' => 'nullable|string',
                'protection_amount' => 'nullable|numeric',
                'quoteid' => 'nullable|string',
                'rentalCode' => 'nullable|string',
                'vehicle_total' => 'nullable|numeric',
                'payment_method' => 'nullable|string',
                'selected_deposit_type' => 'nullable|string',
                'location_details' => 'nullable|array',
                'location_instructions' => 'nullable|string',
                'driver_requirements' => 'nullable|array',
                'terms' => 'nullable|array',
            ]);

            $vehicle = $validated['vehicle'] ?? [];
            $providerSource = strtolower((string) ($vehicle['source'] ?? ''));
            $pickupLocationDetails = $validated['location_details'] ?? null;
            $dropoffLocationDetails = $validated['dropoff_location_details'] ?? null;

            if ($providerSource === 'sicily_by_car') {
                $vehicleId = $vehicle['provider_vehicle_id'] ?? null;
                $rateId = $vehicle['rate_id'] ?? null;
                $pickupId = $vehicle['provider_pickup_id'] ?? null;

                if ((!$vehicleId || !$rateId || !$pickupId) && !empty($vehicle['id'])) {
                    $rawId = (string) $vehicle['id'];
                    $prefix = 'sicily_by_car_';
                    if (str_starts_with($rawId, $prefix)) {
                        $rest = substr($rawId, strlen($prefix));
                        $parts = explode('_', $rest, 3);
                        if (count($parts) === 3) {
                            [$pickupIdParsed, $vehicleIdParsed, $rateIdParsed] = $parts;
                            $pickupId = $pickupId ?: $pickupIdParsed;
                            $vehicleId = $vehicleId ?: $vehicleIdParsed;
                            $rateId = $rateId ?: $rateIdParsed;
                        }
                    }
                }

                if ($pickupId) {
                    $vehicle['provider_pickup_id'] = $pickupId;
                }
                if ($vehicleId) {
                    $vehicle['provider_vehicle_id'] = $vehicleId;
                }
                if ($rateId) {
                    $vehicle['rate_id'] = $rateId;
                }

                $validated['vehicle'] = $vehicle;
            }

            if ($providerSource === 'recordgo') {
                $missing = [];
                $vehicle = $validated['vehicle'] ?? [];
                $countryCode = strtoupper((string) ($validated['country'] ?? ''));
                if ($countryCode === '') {
                    $countryCode = 'IT';
                }

                $recordgoProducts = $vehicle['recordgo_products'] ?? [];
                $selectedRecordGo = null;
                if (is_array($recordgoProducts)) {
                    foreach ($recordgoProducts as $product) {
                        if (($product['type'] ?? null) === ($validated['package'] ?? null)) {
                            $selectedRecordGo = $product;
                            break;
                        }
                    }
                    if (!$selectedRecordGo && !empty($recordgoProducts)) {
                        $selectedRecordGo = $recordgoProducts[0];
                    }
                }

                $vehicle['recordgo_selected_product'] = $selectedRecordGo;
                $validated['vehicle'] = $vehicle;

                if (empty($vehicle['provider_pickup_id'])) {
                    $missing[] = 'vehicle.provider_pickup_id';
                }
                if (empty($vehicle['provider_dropoff_id'])) {
                    $missing[] = 'vehicle.provider_dropoff_id';
                }
                if (empty($vehicle['sipp_code'])) {
                    $missing[] = 'vehicle.sipp_code';
                }

                if (!$selectedRecordGo || empty($selectedRecordGo['product_id']) || empty($selectedRecordGo['product_ver']) || empty($selectedRecordGo['rate_prod_ver'])) {
                    $missing[] = 'recordgo.product_id/product_ver/rate_prod_ver';
                }
                if (empty($vehicle['recordgo_sellcode_ver'])) {
                    $missing[] = 'vehicle.recordgo_sellcode_ver';
                }

                if (!empty($missing)) {
                    return response()->json([
                        'error' => 'Missing required Record Go booking details. Please refresh and try again.',
                        'missing_fields' => $missing,
                    ], 422);
                }
            }

            if ($providerSource === 'renteon') {
                if (empty($pickupLocationDetails) && !empty($vehicle['pickup_office']) && is_array($vehicle['pickup_office'])) {
                    $pickupLocationDetails = $vehicle['pickup_office'];
                }
                if (empty($dropoffLocationDetails) && !empty($vehicle['dropoff_office']) && is_array($vehicle['dropoff_office'])) {
                    $dropoffLocationDetails = $vehicle['dropoff_office'];
                }
            }
            if (in_array($providerSource, ['greenmotion', 'usave'], true)) {
                $missing = [];
                $customer = $validated['customer'] ?? [];

                if (empty($customer['driver_license_number'])) {
                    $missing[] = 'driver_license_number';
                }
                if (empty($customer['address'])) {
                    $missing[] = 'address';
                }
                if (empty($customer['city'])) {
                    $missing[] = 'city';
                }
                if (empty($customer['postal_code'])) {
                    $missing[] = 'postal_code';
                }
                if (empty($customer['country'])) {
                    $missing[] = 'country';
                }

                if (!empty($missing)) {
                    return response()->json([
                        'error' => 'Please complete the required driver details before checkout.',
                        'missing_fields' => $missing,
                    ], 422);
                }

                $quoteId = $validated['quoteid'] ?? ($validated['vehicle']['quoteid'] ?? null);
                if (!$quoteId) {
                    return response()->json([
                        'error' => 'Quote expired or missing. Please search again.',
                    ], 422);
                }

                if (empty($validated['package'])) {
                    return response()->json([
                        'error' => 'Please select a package before checkout.',
                    ], 422);
                }
            }

            if ($providerSource === 'sicily_by_car') {
                $missing = [];
                $customer = $validated['customer'] ?? [];

                if (empty($customer['name'])) {
                    $missing[] = 'customer.name';
                }
                if (empty($customer['email'])) {
                    $missing[] = 'customer.email';
                }
                if (empty($customer['phone'])) {
                    $missing[] = 'customer.phone';
                }
                if (empty($customer['driver_age'])) {
                    $missing[] = 'customer.driver_age';
                }

                if (empty($vehicle['provider_pickup_id'])) {
                    $missing[] = 'vehicle.provider_pickup_id';
                }
                if (empty($vehicle['provider_vehicle_id'])) {
                    $missing[] = 'vehicle.provider_vehicle_id';
                }
                if (empty($vehicle['rate_id'])) {
                    $missing[] = 'vehicle.rate_id';
                }
                if (!empty($missing)) {
                    return response()->json([
                        'error' => 'Missing required Sicily By Car booking details. Please refresh and try again.',
                        'missing_fields' => $missing,
                    ], 422);
                }
            }

            // Get payment percentage from settings
            $payableSetting = PayableSetting::first();
            $paymentPercentage = $payableSetting ? $payableSetting->payment_percentage : 15;

            // SECURITY: Verify prices against server-side stored values
            $searchSessionId = $request->input('search_session_id') ?? $request->header('X-Search-Session');
            if (!$searchSessionId) {
                // Try to get from session
                $searchSessionId = session()->get('search_session_id');
            }

            $priceVerificationService = app(PriceVerificationService::class);

            if ($searchSessionId) {
                $verification = $priceVerificationService->verifyPrices($searchSessionId, $vehicle);

                if (!$verification['valid']) {
                    Log::warning('Price verification failed during checkout', [
                        'error' => $verification['error'],
                        'vehicle_id' => $vehicle['id'] ?? $vehicle['provider_vehicle_id'] ?? null,
                        'search_session' => $searchSessionId,
                        'ip' => $request->ip(),
                    ]);

                    return response()->json([
                        'error' => $verification['error'] ?? 'Price verification failed. Please refresh search results and try again.',
                    ], 422);
                }

                // Use verified prices from server cache instead of client-sent values
                $verifiedPrices = $verification['original_prices'];
                Log::info('Price verification successful', [
                    'vehicle_id' => $vehicle['id'] ?? $vehicle['provider_vehicle_id'] ?? null,
                    'verified_total' => $verifiedPrices['original_total'] ?? null,
                ]);

                // Override client-sent prices with verified server prices
                if (isset($verifiedPrices['original_total'])) {
                    $vehicle['total'] = $verifiedPrices['original_total'];
                }
                if (isset($verifiedPrices['products']) && !empty($verifiedPrices['products'])) {
                    $vehicle['products'] = $verifiedPrices['products'];
                }
                if (isset($verifiedPrices['price_per_day'])) {
                    $vehicle['price_per_day'] = $verifiedPrices['price_per_day'];
                }

                $validated['vehicle'] = $vehicle;
            } else {
                Log::warning('Checkout attempted without search_session_id', [
                    'vehicle_id' => $vehicle['id'] ?? $vehicle['provider_vehicle_id'] ?? null,
                    'ip' => $request->ip(),
                ]);
                // Continue anyway for backward compatibility, but log warning
            }

            // Normalize currency (symbols to ISO codes)
            $currency = $validated['currency'] ?? 'EUR';
            $currencyCode = $this->normalizeCurrencyCode($currency);
            $providerCurrency = $this->resolveProviderCurrency($validated, $currencyCode);

            $computedTotals = $this->computeServerTotals($validated, $currencyCode, $providerCurrency, $paymentPercentage);

            if (!$computedTotals['success']) {
                return response()->json([
                    'error' => $computedTotals['error'] ?? 'Unable to validate pricing.',
                ], 422);
            }

            $totalAmount = $computedTotals['booking_total'];
            $payableAmount = $computedTotals['booking_deposit'];
            $pendingAmount = $computedTotals['booking_pending'];

            $isProviderVehicle = $this->isExternalProviderSource($providerSource);
            $commissionRate = $isProviderVehicle
                ? ($computedTotals['provider_commission_rate'] ?? $this->resolveProviderMarkupRate())
                : 0.0;
            $bookingTotalNet = $computedTotals['booking_total_net'] ?? null;
            $commissionAmount = 0.0;
            if ($isProviderVehicle) {
                if ($bookingTotalNet !== null) {
                    $commissionAmount = round(((float) $totalAmount) - (float) $bookingTotalNet, 2);
                } else {
                    $commissionAmount = round(((float) $totalAmount) * $commissionRate, 2);
                }
            }

            $extrasPayloadId = null;

            // Extract vehicle benefits/policies for storage in provider_metadata
            $vehicleBenefits = $validated['vehicle']['benefits'] ?? [];
            if (is_object($vehicleBenefits)) {
                $vehicleBenefits = (array) $vehicleBenefits;
            }

            $extrasPayload = [
                'detailed_extras' => $validated['detailed_extras'] ?? [],
                'extras' => $validated['extras'] ?? [],
                'vehicle_source' => $validated['vehicle']['source'] ?? null,
                'vehicle_id' => $validated['vehicle']['id'] ?? null,
                'provider_currency' => $providerCurrency,
                'booking_currency' => $currencyCode,
                'location_details' => $pickupLocationDetails,
                'dropoff_location_details' => $dropoffLocationDetails,
                'location_instructions' => $validated['location_instructions'] ?? null,
                'driver_requirements' => $validated['driver_requirements'] ?? null,
                'terms' => $validated['terms'] ?? null,
                // Vehicle benefits, policies, and deposit/excess info
                'vehicle_benefits' => $vehicleBenefits,
                'security_deposit' => $validated['vehicle']['security_deposit'] ?? null,
                'deposit_payment_method' => $validated['vehicle']['payment_method'] ?? null,
                'selected_deposit_type' => $validated['selected_deposit_type'] ?? null,
                'fuel_type' => $validated['vehicle']['fuel_type'] ?? $validated['vehicle']['fuel'] ?? null,
                'mileage' => $validated['vehicle']['mileage'] ?? null,
                'transmission' => $validated['vehicle']['transmission'] ?? null,
            ];

            // Always save payload since it now contains vehicle benefits
            $payloadHasContent = true;

            if ($payloadHasContent) {
                $payloadRecord = StripeCheckoutPayload::create([
                    'payload' => $extrasPayload,
                ]);
                $extrasPayloadId = $payloadRecord->id;
            }

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
                'total_amount' => $totalAmount,
                'total_amount_net' => $computedTotals['booking_total_net'] ?? $totalAmount,
                'payable_amount' => $payableAmount,
                'pending_amount' => $pendingAmount,
                'currency' => $currencyCode,
                'provider_currency' => $providerCurrency,
                'provider_vehicle_total' => $computedTotals['provider_vehicle_total'] ?? null,
                'provider_extras_total' => $computedTotals['provider_options_total'] ?? null,
                'provider_grand_total' => $computedTotals['provider_grand_total'] ?? null,
                'provider_protection_total' => $computedTotals['provider_protection_total'] ?? null,
                'provider_commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'deposit_percentage' => $paymentPercentage,
                'deposit_amount' => $validated['vehicle']['benefits']['deposit_amount'] ?? $validated['vehicle']['security_deposit'] ?? null,
                'excess_amount' => $validated['vehicle']['benefits']['excess_amount'] ?? null,
                'excess_theft_amount' => $validated['vehicle']['benefits']['excess_theft_amount'] ?? null,
                'deposit_currency' => $validated['vehicle']['benefits']['deposit_currency'] ?? $providerCurrency,
                // Multi-currency exchange rate tracking
                'exchange_rate_provider_to_booking' => $this->calculateExchangeRate($providerCurrency, $currencyCode, $computedTotals['provider_grand_total'] ?? null),
                'exchange_rate_booking_to_admin' => $this->calculateExchangeRate($currencyCode, config('currency.base_currency', 'EUR'), $totalAmount),
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
                'quoteid' => !empty($validated['quoteid']) ? $validated['quoteid'] : ($validated['vehicle']['quoteid'] ?? ''),
                'rental_code' => $validated['package'] ?? ($validated['vehicle']['rentalCode'] ?? ''),
                'vehicle_total' => $computedTotals['booking_vehicle_total'],
                'extras_total' => $computedTotals['booking_options_total'],
                'payment_method' => $validated['payment_method'] ?? 'card',
                'renteon_connector_id' => $validated['vehicle']['connector_id'] ?? null,
                'renteon_pickup_office_id' => $validated['vehicle']['provider_pickup_office_id'] ?? null,
                'renteon_dropoff_office_id' => $validated['vehicle']['provider_dropoff_office_id'] ?? null,
                'renteon_pricelist_id' => $validated['vehicle']['pricelist_id'] ?? null,
                'renteon_price_date' => $validated['vehicle']['price_date'] ?? null,
                'renteon_prepaid' => $validated['vehicle']['prepaid']
                    ?? (($validated['vehicle']['source'] ?? '') === 'renteon' ? false : true),
                'sbc_vehicle_id' => $validated['vehicle']['provider_vehicle_id'] ?? null,
                'sbc_rate_id' => $validated['vehicle']['rate_id'] ?? null,
                'sbc_availability_id' => $validated['vehicle']['availability_id'] ?? null,
                'sbc_payment_type' => $validated['vehicle']['payment_type'] ?? null,
                'sbc_currency' => $validated['vehicle']['currency'] ?? null,
                'recordgo_country' => $validated['vehicle']['recordgo_country'] ?? ($validated['country'] ?? null),
                'recordgo_sell_code' => app(\App\Services\RecordGoService::class)->resolveSellCode($validated['country'] ?? null),
                'recordgo_sellcode_ver' => $validated['vehicle']['recordgo_sellcode_ver'] ?? null,
                'recordgo_acriss_code' => $validated['vehicle']['sipp_code'] ?? null,
                'recordgo_product_id' => $validated['vehicle']['recordgo_selected_product']['product_id'] ?? null,
                'recordgo_product_ver' => $validated['vehicle']['recordgo_selected_product']['product_ver'] ?? null,
                'recordgo_rate_prod_ver' => $validated['vehicle']['recordgo_selected_product']['rate_prod_ver'] ?? null,
                'recordgo_product_name' => $validated['vehicle']['recordgo_selected_product']['name'] ?? null,
                'recordgo_product_description' => $validated['vehicle']['recordgo_selected_product']['description'] ?? null,
                'recordgo_product_subtitle' => $validated['vehicle']['recordgo_selected_product']['subtitle'] ?? null,
                'recordgo_booking_total' => $validated['vehicle']['recordgo_selected_product']['total'] ?? null,
                'recordgo_automatic_complements' => $validated['vehicle']['recordgo_selected_product']['complements_automatic']
                    ?? $validated['vehicle']['recordgo_selected_product']['complements_autom']
                    ?? null,
                'ok_mobility_token' => $validated['vehicle']['ok_mobility_token'] ?? null,
                'ok_mobility_group_id' => $validated['vehicle']['ok_mobility_group_id'] ?? null,
                'ok_mobility_rate_code' => $validated['vehicle']['ok_mobility_rate_code'] ?? null,
                'favrica_rez_id' => $validated['vehicle']['favrica_rez_id'] ?? null,
                'favrica_cars_park_id' => $validated['vehicle']['favrica_cars_park_id'] ?? null,
                'favrica_group_id' => $validated['vehicle']['favrica_group_id'] ?? null,
                'favrica_car_web_id' => $validated['vehicle']['favrica_car_web_id'] ?? null,
                'favrica_reservation_source_id' => $validated['vehicle']['favrica_reservation_source_id'] ?? null,
                'favrica_drop_fee' => $validated['vehicle']['favrica_drop_fee'] ?? null,
                'xdrive_rez_id' => $validated['vehicle']['xdrive_rez_id'] ?? null,
                'xdrive_cars_park_id' => $validated['vehicle']['xdrive_cars_park_id'] ?? null,
                'xdrive_group_id' => $validated['vehicle']['xdrive_group_id'] ?? null,
                'xdrive_car_web_id' => $validated['vehicle']['xdrive_car_web_id'] ?? null,
                'xdrive_reservation_source_id' => $validated['vehicle']['xdrive_reservation_source_id'] ?? null,
                'xdrive_reservation_source' => $validated['vehicle']['xdrive_reservation_source'] ?? null,
                'xdrive_drop_fee' => $validated['vehicle']['xdrive_drop_fee'] ?? null,
                'driver_license_number' => $validated['customer']['driver_license_number'] ?? null,
                'notes' => $validated['customer']['notes'] ?? null,
            ];

            if ($extrasPayloadId) {
                $metadata['extras_payload_id'] = (string) $extrasPayloadId;
            }

            $customerMetadata = array_filter([
                'customer_address' => $validated['customer']['address'] ?? null,
                'customer_city' => $validated['customer']['city'] ?? null,
                'customer_postal_code' => $validated['customer']['postal_code'] ?? null,
                'customer_country' => $validated['customer']['country'] ?? null,
            ], static fn($value) => $value !== null && $value !== '');

            if (!empty($customerMetadata)) {
                $metadata = array_merge($metadata, $customerMetadata);
            }

            if (!empty($pickupLocationDetails)) {
                $metadata['location_details'] = json_encode($pickupLocationDetails);
                if (!empty($dropoffLocationDetails)) {
                    $metadata['dropoff_location_details'] = json_encode($dropoffLocationDetails);
                } elseif (!empty($metadata['pickup_location_code'])
                    && !empty($metadata['return_location_code'])
                    && $metadata['pickup_location_code'] === $metadata['return_location_code']) {
                    $metadata['dropoff_location_details'] = $metadata['location_details'];
                }
            }
            if (!empty($validated['location_instructions'])) {
                $metadata['location_instructions'] = $validated['location_instructions'];
            }
            if (!empty($validated['driver_requirements'])) {
                $metadata['driver_requirements'] = json_encode($validated['driver_requirements']);
            }
            if (!empty($validated['terms'])) {
                $metadata['terms'] = json_encode($validated['terms']);
            }

            $metadata = $this->stripOversizedMetadata($metadata);
            $metadata = $this->compactStripeMetadata($metadata);


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

            if ($extrasPayloadId) {
                try {
                    StripeCheckoutPayload::whereKey($extrasPayloadId)->update([
                        'stripe_session_id' => $session->id,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Stripe Checkout: Failed to update payload session id', [
                        'payload_id' => $extrasPayloadId,
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

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

    private function computeServerTotals(array $validated, string $bookingCurrency, string $providerCurrency, float $paymentPercentage): array
    {
        $vehicle = $validated['vehicle'] ?? [];
        $days = max(1, (int) ($validated['number_of_days'] ?? 1));
        $package = $validated['package'] ?? null;
        $vehicleSource = strtolower((string) ($vehicle['source'] ?? ''));
        $markupRate = $this->resolveProviderMarkupRate();
        $commissionRate = $this->isExternalProviderSource($vehicleSource) ? $markupRate : 0.0;
        $useCommissionOnly = $this->isExternalProviderSource($vehicleSource);

        if (in_array($vehicleSource, ['greenmotion', 'usave'], true)) {
            return $this->computeGreenMotionTotals($validated, $bookingCurrency, $providerCurrency, $paymentPercentage, $days);
        }

        $baseTotal = $this->resolvePackageTotal($vehicle, $package, $days, $vehicleSource, $validated);
        if ($baseTotal === null) {
            return [
                'success' => false,
                'error' => 'Unable to resolve package pricing. Please refresh and try again.',
            ];
        }

        $baseTotalConverted = $baseTotal;
        if ($providerCurrency && $providerCurrency !== $bookingCurrency) {
            $conversion = app(CurrencyConversionService::class)->convert($baseTotal, $providerCurrency, $bookingCurrency);
            if (!($conversion['success'] ?? false)) {
                Log::warning('StripeCheckout: base total conversion failed', [
                    'provider_currency' => $providerCurrency,
                    'booking_currency' => $bookingCurrency,
                    'base_total' => $baseTotal,
                    'error' => $conversion['error'] ?? null,
                ]);
            } else {
                $baseTotalConverted = (float) ($conversion['converted_amount'] ?? $baseTotal);
            }
        }

        $extrasTotalRaw = $this->resolveExtrasTotal($validated['detailed_extras'] ?? [], $days);

        // Locauto protection amount is a daily rate; treat it as provider-priced add-on (net) and multiply by days.
        $providerProtectionTotal = 0.0;
        if ($vehicleSource === 'locauto_rent') {
            $protectionDaily = (float) ($validated['protection_amount'] ?? 0);
            if ($protectionDaily > 0) {
                $providerProtectionTotal = round($protectionDaily * $days, 2);
            }
        }

        $providerOptionsTotal = round($extrasTotalRaw + $providerProtectionTotal, 2);

        $extrasTotalConverted = $extrasTotalRaw;
        if ($providerCurrency && $providerCurrency !== $bookingCurrency && $extrasTotalConverted > 0) {
            $conversion = app(CurrencyConversionService::class)->convert($extrasTotalConverted, $providerCurrency, $bookingCurrency);
            if (!($conversion['success'] ?? false)) {
                Log::warning('StripeCheckout: extras total conversion failed', [
                    'provider_currency' => $providerCurrency,
                    'booking_currency' => $bookingCurrency,
                    'extras_total' => $extrasTotalConverted,
                    'error' => $conversion['error'] ?? null,
                ]);
            } else {
                $extrasTotalConverted = (float) ($conversion['converted_amount'] ?? $extrasTotalConverted);
            }
        }

        $protectionTotalConverted = 0.0;
        if ($providerProtectionTotal > 0) {
            $protectionTotalConverted = $providerProtectionTotal;
            if ($vehicleSource !== 'internal' && $providerCurrency && $providerCurrency !== $bookingCurrency) {
                $conversion = app(CurrencyConversionService::class)->convert($providerProtectionTotal, $providerCurrency, $bookingCurrency);
                if (!($conversion['success'] ?? false)) {
                    return [
                        'success' => false,
                        'error' => 'Unable to convert protection amount. Please try again later.',
                    ];
                }
                $protectionTotalConverted = (float) ($conversion['converted_amount'] ?? $providerProtectionTotal);
            }
        }

        // Booking totals (customer-facing) are marked up for external providers.
        $bookingVehicleTotalNet = round($baseTotalConverted, 2);
        $bookingOptionsTotalNet = round($extrasTotalConverted + $protectionTotalConverted, 2);
        $bookingTotalNet = round($bookingVehicleTotalNet + $bookingOptionsTotalNet, 2);

        $bookingVehicleTotal = $this->grossUpProviderAmount($bookingVehicleTotalNet, $vehicleSource);
        $bookingOptionsTotal = $this->grossUpProviderAmount($bookingOptionsTotalNet, $vehicleSource);
        $bookingTotal = $this->grossUpProviderAmount($bookingTotalNet, $vehicleSource);

        // Deposit/pending are based on the gross booking total (customer-facing).
        if ($useCommissionOnly && $commissionRate > 0) {
            $depositBooking = round(max($bookingTotal - $bookingTotalNet, 0), 2);
        } else {
            $depositBooking = round($bookingTotal * ($paymentPercentage / 100), 2);
        }
        $bookingPending = round($bookingTotal - $depositBooking, 2);

        $clientTotal = isset($validated['total_amount']) ? (float) $validated['total_amount'] : null;
        if ($clientTotal !== null && $clientTotal > 0) {
            $delta = abs($clientTotal - $bookingTotal);

            // Skip client-total validation for internal vehicles
            // Price verification is already done via price_hash, and client/server
            // exchange rates may differ causing false mismatches
            if ($vehicleSource !== 'internal') {
                $tolerance = 0.5;
                if ($delta > $tolerance) {
                    Log::warning('StripeCheckout: total mismatch', [
                        'client_total' => $clientTotal,
                        'server_total' => $bookingTotal,
                        'delta' => $delta,
                        'vehicle_source' => $vehicleSource,
                        'booking_currency' => $bookingCurrency,
                        'provider_currency' => $providerCurrency,
                    ]);

                    return [
                        'success' => false,
                        'error' => 'Pricing has changed. Please refresh and try again.',
                    ];
                }
            }
        }

        return [
            'success' => true,
            'booking_vehicle_total' => $bookingVehicleTotal,
            'booking_options_total' => $bookingOptionsTotal,
            'booking_total' => $bookingTotal,
            'booking_total_net' => $bookingTotalNet,
            'booking_deposit' => $depositBooking,
            'booking_pending' => $bookingPending,
            'provider_vehicle_total' => $baseTotal,
            'provider_options_total' => $providerOptionsTotal,
            'provider_grand_total' => round($baseTotal + $providerOptionsTotal, 2),
            'provider_protection_total' => $providerProtectionTotal,
            'provider_commission_rate' => $commissionRate,
        ];
    }

    private function computeGreenMotionTotals(
        array $validated,
        string $bookingCurrency,
        string $providerCurrency,
        float $paymentPercentage,
        int $days
    ): array {
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

        if (!$product || empty($product['total'])) {
            return [
                'success' => false,
                'error' => 'Unable to resolve selected package. Please search again.',
            ];
        }

        $providerVehicleTotal = (float) $product['total'];

        $options = array_merge(
            $vehicle['options'] ?? [],
            $vehicle['insurance_options'] ?? [],
            $vehicle['optional_extras'] ?? [],
            $validated['optional_extras'] ?? []
        );
        $optionsById = [];
        foreach ($options as $option) {
            $optionId = $option['option_id'] ?? $option['optionID'] ?? $option['id'] ?? null;
            if ($optionId === null) {
                continue;
            }
            $optionsById[(string) $optionId] = $option;
        }

        $selectedExtras = $validated['detailed_extras'] ?? [];
        $selectedMap = [];
        foreach ($selectedExtras as $extra) {
            if (!is_array($extra)) {
                continue;
            }
            $optionId = $extra['option_id'] ?? $extra['optionID'] ?? $extra['id'] ?? null;
            if ($optionId === null) {
                continue;
            }
            $qty = (int) ($extra['qty'] ?? $extra['quantity'] ?? 1);
            $selectedMap[(string) $optionId] = max($qty, 1);
        }

        $missingRequired = [];
        foreach ($optionsById as $optionId => $option) {
            $required = strtolower((string) ($option['required'] ?? '')) === 'required';
            if ($required && !isset($selectedMap[$optionId])) {
                $missingRequired[] = $optionId;
            }
        }

        if (!empty($missingRequired)) {
            return [
                'success' => false,
                'error' => 'Required extras are missing. Please review your selection.',
            ];
        }

        $providerOptionsTotal = 0.0;
        foreach ($selectedMap as $optionId => $qty) {
            $option = $optionsById[$optionId] ?? null;
            if (!$option) {
                continue;
            }
            $max = $option['numberAllowed'] ?? null;
            if ($max !== null && (int) $max > 0 && $qty > (int) $max) {
                return [
                    'success' => false,
                    'error' => 'Selected extras exceed the allowed quantity.',
                ];
            }

            $optionTotal = $option['total_for_booking'] ?? $option['Total_for_this_booking'] ?? null;
            if ($optionTotal === null) {
                $dailyRate = $option['daily_rate'] ?? $option['Daily_rate'] ?? null;
                if ($dailyRate !== null) {
                    $optionTotal = (float) $dailyRate * $days;
                } else {
                    $optionTotal = 0.0;
                }
            }

            $providerOptionsTotal += (float) $optionTotal * $qty;
        }

        $providerGrandTotal = $providerVehicleTotal + $providerOptionsTotal;

        $conversionService = app(CurrencyConversionService::class);
        $bookingVehicleTotal = $providerVehicleTotal;
        if ($providerCurrency && $providerCurrency !== $bookingCurrency) {
            $conversion = $conversionService->convert($providerVehicleTotal, $providerCurrency, $bookingCurrency);
            if (!($conversion['success'] ?? false)) {
                return [
                    'success' => false,
                    'error' => 'Unable to convert provider totals. Please try again later.',
                ];
            }
            $bookingVehicleTotal = (float) ($conversion['converted_amount'] ?? $providerVehicleTotal);
        }

        $bookingOptionsTotal = $providerOptionsTotal;
        if ($providerCurrency && $providerCurrency !== $bookingCurrency) {
            $conversion = $conversionService->convert($providerOptionsTotal, $providerCurrency, $bookingCurrency);
            if (!($conversion['success'] ?? false)) {
                return [
                    'success' => false,
                    'error' => 'Unable to convert provider options. Please try again later.',
                ];
            }
            $bookingOptionsTotal = (float) ($conversion['converted_amount'] ?? $providerOptionsTotal);
        }

        $bookingTotalNet = round($bookingVehicleTotal + $bookingOptionsTotal, 2);

        $bookingVehicleTotalGross = $this->grossUpProviderAmount(round($bookingVehicleTotal, 2), $vehicle['source'] ?? null);
        $bookingOptionsTotalGross = $this->grossUpProviderAmount(round($bookingOptionsTotal, 2), $vehicle['source'] ?? null);
        $bookingTotal = $this->grossUpProviderAmount($bookingTotalNet, $vehicle['source'] ?? null);

        $markupRate = $this->resolveProviderMarkupRate();
        $commissionRate = $this->isExternalProviderSource($vehicle['source'] ?? null) ? $markupRate : 0.0;

        if ($commissionRate > 0) {
            $bookingDeposit = round(max($bookingTotal - $bookingTotalNet, 0), 2);
        } else {
            $bookingDeposit = round($bookingTotal * ($paymentPercentage / 100), 2);
        }
        $bookingPending = round($bookingTotal - $bookingDeposit, 2);

        return [
            'success' => true,
            'booking_vehicle_total' => $bookingVehicleTotalGross,
            'booking_options_total' => $bookingOptionsTotalGross,
            'booking_total' => $bookingTotal,
            'booking_total_net' => $bookingTotalNet,
            'booking_deposit' => round($bookingDeposit, 2),
            'booking_pending' => $bookingPending,
            'provider_vehicle_total' => round($providerVehicleTotal, 2),
            'provider_options_total' => round($providerOptionsTotal, 2),
            'provider_grand_total' => round($providerGrandTotal, 2),
            'provider_commission_rate' => $commissionRate,
        ];
    }

    private function resolvePackageTotal(array $vehicle, ?string $package, int $days, string $source, array $validated): ?float
    {
        if (!empty($vehicle['products']) && is_array($vehicle['products']) && $package) {
            foreach ($vehicle['products'] as $product) {
                if (!is_array($product)) {
                    continue;
                }
                if (($product['type'] ?? null) === $package && isset($product['total'])) {
                    return (float) $product['total'];
                }
            }
        }

        if ($source === 'internal') {
            // For internal vehicles: ALWAYS calculate from DB price, ignore frontend values
            // Frontend sends converted values for display, but we need original vendor pricing
            $vehicleId = $vehicle['id'] ?? null;
            if ($vehicleId) {
                $vehicleModel = \App\Models\Vehicle::find($vehicleId);
                if ($vehicleModel) {
                    $pricePerDay = $vehicleModel->price_per_day ?? null;
                    if ($pricePerDay !== null) {
                        return (float) $pricePerDay * $days;
                    }
                }
            }

            // Fallback if vehicle not found
            $pricePerDay = $vehicle['price_per_day'] ?? null;
            if ($pricePerDay !== null) {
                return (float) $pricePerDay * $days;
            }
        }

        if (isset($vehicle['total_price'])) {
            return (float) $vehicle['total_price'];
        }

        if (isset($validated['vehicle_total'])) {
            return (float) $validated['vehicle_total'];
        }

        if (isset($validated['total_amount'])) {
            return (float) $validated['total_amount'];
        }

        return null;
    }

    private function resolveExtrasTotal(array $extras, int $days): float
    {
        $total = 0.0;
        foreach ($extras as $extra) {
            if (!is_array($extra)) {
                continue;
            }
            $isFree = isset($extra['isFree']) ? (bool) $extra['isFree'] : false;
            if ($isFree) {
                continue;
            }

            $qty = (int) ($extra['qty'] ?? $extra['quantity'] ?? 1);
            $totalForBooking = $extra['total_for_booking']
                ?? $extra['Total_for_this_booking']
                ?? $extra['total_for_this_booking']
                ?? null;
            if ($totalForBooking !== null) {
                $extraTotal = (float) $totalForBooking * max(1, $qty);
            } else {
                $extraTotal = $extra['total'] ?? null;
                if ($extraTotal === null) {
                    $dailyRate = $extra['daily_rate'] ?? $extra['dailyRate'] ?? null;
                    $price = $extra['price'] ?? $extra['amount'] ?? null;
                    if ($dailyRate !== null) {
                        $extraTotal = (float) $dailyRate * $days * max(1, $qty);
                    } elseif ($price !== null) {
                        $extraTotal = (float) $price * max(1, $qty);
                    }
                }
            }

            if ($extraTotal !== null) {
                $total += (float) $extraTotal;
            }
        }

        return round($total, 2);
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
     * Calculate exchange rate between two currencies for a given amount
     */
    private function calculateExchangeRate(?string $fromCurrency, ?string $toCurrency, ?float $amount): ?float
    {
        if (!$fromCurrency || !$toCurrency || $fromCurrency === $toCurrency) {
            return 1.0;
        }

        $amount = $amount ?? 0;
        if ($amount <= 0) {
            return 1.0;
        }

        $conversionService = app(CurrencyConversionService::class);
        $result = $conversionService->convert($amount, $fromCurrency, $toCurrency);

        if (!($result['success'] ?? false)) {
            Log::warning('Failed to get exchange rate', [
                'from' => $fromCurrency,
                'to' => $toCurrency,
                'amount' => $amount,
                'error' => $result['error'] ?? null,
            ]);
            return null;
        }

        $convertedAmount = (float) ($result['converted_amount'] ?? 0);
        if ($convertedAmount > 0) {
            return round($convertedAmount / $amount, 6);
        }

        return 1.0;
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
