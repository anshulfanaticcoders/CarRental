<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\BookingExtra;
use App\Models\Customer;
use App\Models\StripeCheckoutPayload;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\Booking\BookingCreatedAdminNotification;
use App\Notifications\Booking\BookingCreatedCompanyNotification;
use App\Notifications\Booking\BookingCreatedCustomerNotification;
use App\Notifications\Booking\GuestBookingCreatedNotification;
use App\Notifications\Booking\BookingCreatedVendorNotification;
use App\Models\VendorProfile;
use App\Services\BookingAmountService;
use App\Services\CurrencyConversionService;
use App\Services\VrooemGatewayService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class StripeBookingService
{
    public function resolveCustomerFromCheckoutPayload(array $payload, ?int $userId = null): array
    {
        $customer = $payload['customer'] ?? [];
        $metadata = (object) [
            'customer_name' => $customer['name'] ?? null,
            'customer_email' => $customer['email'] ?? null,
            'customer_phone' => $customer['phone'] ?? null,
            'customer_driver_age' => $customer['driver_age'] ?? null,
            'flight_number' => $customer['flight_number'] ?? null,
            'customer_address' => $customer['address'] ?? null,
            'customer_city' => $customer['city'] ?? null,
            'customer_postal_code' => $customer['postal_code'] ?? null,
            'customer_country' => $customer['country'] ?? null,
            'user_id' => $userId,
        ];

        return $this->findOrCreateCustomer($metadata);
    }
    /**
     * Create a booking from a Stripe Checkout Session
     */
    public function createBookingFromSession($session)
    {
        Log::info('StripeBookingService: Starting creation', ['session_id' => $session->id]);

        $metadata = $session->metadata;

        // Merge full metadata from extras_payload (bypasses Stripe 50-key limit)
        $extrasPayloadId = $metadata->extras_payload_id ?? null;
        if ($extrasPayloadId) {
            try {
                $payloadRecord = StripeCheckoutPayload::find($extrasPayloadId);
                if ($payloadRecord && !empty($payloadRecord->payload['full_metadata'])) {
                    $fullMeta = $payloadRecord->payload['full_metadata'];
                    // Use toArray() — (array) cast on Stripe\StripeObject gives internal properties, not key-values
                    $stripeKeys = array_filter($metadata->toArray(), fn($v) => $v !== null && $v !== '');
                    // Stripe keys take precedence, full_metadata fills in the rest
                    $merged = array_merge($fullMeta, $stripeKeys);
                    $metadata = (object) $merged;
                    Log::info('StripeBookingService: Merged full_metadata from extras_payload', [
                        'extras_payload_id' => $extrasPayloadId,
                        'merged_key_count' => count($merged),
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('StripeBookingService: Failed to merge full_metadata', [
                    'extras_payload_id' => $extrasPayloadId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Idempotency check
        $existingBooking = Booking::where('stripe_session_id', $session->id)->first();
        if (!$existingBooking && !empty($metadata->booking_id)) {
            $existingBooking = Booking::find($metadata->booking_id);
        }
        if ($existingBooking && in_array($existingBooking->booking_status, ['confirmed', 'completed'], true)) {
            Log::info('StripeBookingService: Booking already confirmed', ['booking_id' => $existingBooking->id]);
            return $existingBooking;
        }

        // Start Transaction
        DB::beginTransaction();

        try {
            // Find or create customer
            $customerData = $this->findOrCreateCustomer($metadata);
            $customer = $customerData['customer'];
            Log::info('StripeBookingService: Customer processed', ['customer_id' => $customer->id]);

            $vehicleId = null;
            if (($metadata->vehicle_source ?? '') === 'internal' && !empty($metadata->vehicle_id)) {
                $vehicleId = (int) $metadata->vehicle_id;
            }

            $bookingCurrency = $this->normalizeCurrencyCode($metadata->currency ?? 'EUR');
            $bookingBasePrice = isset($metadata->vehicle_total)
                ? (float) $metadata->vehicle_total
                : (float) ($metadata->total_amount_net ?? $metadata->provider_grand_total ?? 0);
            $bookingExtraCharges = isset($metadata->extras_total)
                ? (float) $metadata->extras_total
                : 0.0;

            if ($existingBooking) {
                $booking = $existingBooking;
                $booking->update([
                    'customer_id' => $customer->id,
                    'vehicle_id' => $booking->vehicle_id ?? $vehicleId,
                    'provider_source' => $booking->provider_source ?? ($metadata->vehicle_source ?? 'greenmotion'),
                    'provider_vehicle_id' => $booking->provider_vehicle_id ?? ($metadata->vehicle_id ?? null),
                    'provider_grand_total' => (float) ($metadata->provider_grand_total ?? $metadata->total_amount_net ?? $booking->provider_grand_total ?? 0),
                    'vehicle_name' => $booking->vehicle_name ?: (($metadata->vehicle_brand ?? '') . ' ' . ($metadata->vehicle_model ?? '')),
                    'vehicle_image' => $booking->vehicle_image ?: ($metadata->vehicle_image ?? null),
                    'pickup_date' => $metadata->pickup_date ?? $booking->pickup_date,
                    'pickup_time' => $metadata->pickup_time ?? $booking->pickup_time,
                    'return_date' => $metadata->dropoff_date ?? $booking->return_date,
                    'return_time' => $metadata->dropoff_time ?? $booking->return_time,
                    'pickup_location' => $metadata->pickup_location ?? $booking->pickup_location,
                    'return_location' => $metadata->dropoff_location ?? $metadata->pickup_location ?? $booking->return_location,
                    'plan' => $metadata->package ?? $booking->plan ?? 'BAS',
                    'total_days' => (int) ($metadata->number_of_days ?? $booking->total_days ?? 1),
                    'base_price' => isset($metadata->vehicle_total)
                        ? $bookingBasePrice
                        : ($booking->base_price ?? $bookingBasePrice),
                    'extra_charges' => isset($metadata->extras_total)
                        ? $bookingExtraCharges
                        : ($booking->extra_charges ?? 0),
                    'tax_amount' => 0,
                    'total_amount' => (float) ($metadata->total_amount ?? $booking->total_amount ?? 0),
                    'amount_paid' => (float) ($metadata->payable_amount ?? $booking->amount_paid ?? 0),
                    'pending_amount' => (float) ($metadata->total_amount_net ?? $metadata->provider_grand_total ?? $metadata->pending_amount ?? $booking->pending_amount ?? 0),
                    'booking_currency' => $bookingCurrency,
                    'payment_status' => 'partial',
                    'booking_status' => 'confirmed',
                    'stripe_session_id' => $session->id,
                    'stripe_payment_intent_id' => $session->payment_intent,
                    'provider_booking_ref' => $booking->provider_booking_ref ?? ($metadata->provider_booking_ref ?? null),
                ]);
            } else {
                $booking = Booking::create([
                    'booking_number' => Booking::generateBookingNumber(),
                    'customer_id' => $customer->id,
                    'vehicle_id' => $vehicleId,
                    'provider_source' => $metadata->vehicle_source ?? 'greenmotion',
                    'provider_vehicle_id' => $metadata->vehicle_id ?? null,
                    'provider_grand_total' => (float) ($metadata->provider_grand_total ?? $metadata->total_amount_net ?? 0),
                    'vehicle_name' => ($metadata->vehicle_brand ?? '') . ' ' . ($metadata->vehicle_model ?? ''),
                    'vehicle_image' => $metadata->vehicle_image ?? null,
                    'pickup_date' => $metadata->pickup_date,
                    'pickup_time' => $metadata->pickup_time,
                    'return_date' => $metadata->dropoff_date,
                    'return_time' => $metadata->dropoff_time,
                    'pickup_location' => $metadata->pickup_location,
                    'return_location' => $metadata->dropoff_location ?? $metadata->pickup_location,
                    'plan' => $metadata->package ?? 'BAS',
                    'total_days' => (int) ($metadata->number_of_days ?? 1),
                    'base_price' => $bookingBasePrice,
                    'extra_charges' => $bookingExtraCharges,
                    'tax_amount' => 0,
                    'total_amount' => (float) ($metadata->total_amount ?? 0),
                    'amount_paid' => (float) ($metadata->payable_amount ?? 0),
                    'pending_amount' => (float) ($metadata->total_amount_net ?? $metadata->provider_grand_total ?? 0),
                    'booking_currency' => $bookingCurrency,
                    'payment_status' => 'partial',
                    'booking_status' => 'confirmed',
                    'stripe_session_id' => $session->id,
                    'stripe_payment_intent_id' => $session->payment_intent,
                    'provider_booking_ref' => $metadata->provider_booking_ref ?? null,
                    'discount_amount' => (float) ($metadata->promo_discount_amount ?? 0),
                ]);
            }

            // Update discount_amount for existing bookings too (idempotent re-processing)
            $promoDiscount = (float) ($metadata->promo_discount_amount ?? 0);
            if ($promoDiscount > 0 && (float) ($booking->discount_amount ?? 0) === 0.0) {
                $booking->update(['discount_amount' => $promoDiscount]);
            }

            if (empty($booking->provider_metadata)) {
                $providerMetadata = $this->buildProviderMetadataFromSession($metadata, $booking);
                $booking->update([
                    'provider_metadata' => $providerMetadata,
                ]);
            }

            $extrasPayload = $this->resolveExtrasPayloadFromMetadata($metadata);
            $extrasTotal = (float) ($metadata->extras_total ?? 0);
            $extrasData = $extrasPayload['detailed_extras'] ?? [];
            if ($extrasTotal <= 0 && !empty($extrasData)) {
                foreach ($extrasData as $extraItem) {
                    $extrasTotal += (float) ($extraItem['total'] ?? 0);
                }
            }

            $vendorCurrency = null;
            if ($booking->vehicle_id) {
                $vehicle = $booking->vehicle()->with('vendorProfile')->first();
                $vendorCurrency = $vehicle?->vendorProfile?->currency;
            }

            // If vendor currency not found in profile, try metadata
            if (!$vendorCurrency && !empty($metadata->provider_currency)) {
                $vendorCurrency = $this->normalizeCurrencyCode($metadata->provider_currency);
            }

            $vehicleTotal = (float) ($metadata->vehicle_total ?? 0);
            $extraAmount = $vehicleTotal > 0
                ? $vehicleTotal + $extrasTotal
                : (float) ($metadata->total_amount ?? 0);

            // Prepare provider's ORIGINAL amounts (in vendor's currency)
            $providerAmounts = null;
            $providerGrandTotal = (float) ($metadata->provider_grand_total ?? 0);
            if ($providerGrandTotal > 0) {
                $providerVehicleTotal = (float) ($metadata->provider_vehicle_total ?? $providerGrandTotal - (float)($metadata->provider_extras_total ?? 0));
                $providerExtrasTotal = (float) ($metadata->provider_extras_total ?? 0);

                $providerAmounts = [
                    'total_amount' => $providerGrandTotal,
                    'amount_paid' => 0, // Vendor paid at pickup, not now
                    'pending_amount' => $providerGrandTotal, // Full amount due at pickup
                    'extra_amount' => $providerExtrasTotal,
                ];
            }

            $adminRevenueAmount = round((float) ($metadata->payable_amount ?? 0), 2);
            $adminAmounts = [
                'total_amount' => $adminRevenueAmount,
                'amount_paid' => $adminRevenueAmount,
                'pending_amount' => 0,
                // Preserve an explicit non-zero value so BookingAmountService does not
                // reinterpret this as "full booking extra amount" fallback.
                'extra_amount' => $adminRevenueAmount,
            ];

            app(BookingAmountService::class)->createForBooking($booking, [
                'total_amount' => $booking->total_amount,
                'amount_paid' => $booking->amount_paid,
                'pending_amount' => $booking->pending_amount,
                'extra_amount' => $extraAmount,
            ], $bookingCurrency, $vendorCurrency, $providerAmounts, $adminAmounts);

            Log::info('StripeBookingService: Booking record created', ['booking_id' => $booking->id]);

            $existingPayment = BookingPayment::where('booking_id', $booking->id)
                ->where('transaction_id', $session->payment_intent)
                ->first();

            if (!$existingPayment) {
                BookingPayment::create([
                    'booking_id' => $booking->id,
                    'payment_method' => $metadata->payment_method ?? 'stripe',
                    'transaction_id' => $session->payment_intent,
                    'amount' => (float) ($metadata->payable_amount ?? 0),
                    'currency' => $bookingCurrency,
                    'payment_status' => 'succeeded',
                    'payment_date' => now(),
                ]);
            }

            if (!$booking->extras()->exists()) {
                $extrasData = $extrasPayload['detailed_extras'] ?? [];

                if (!empty($extrasData)) {
                    $providerCurrency = $metadata->provider_currency ?? $metadata->currency ?? null;
                    $bookingCurrency = $metadata->currency ?? null;
                    foreach ($extrasData as $extraItem) {
                        $price = (float) ($extraItem['total'] ?? 0);
                        if ($price > 0 && $providerCurrency && $bookingCurrency && $providerCurrency !== $bookingCurrency) {
                            $conversion = app(CurrencyConversionService::class)->convert($price, $providerCurrency, $bookingCurrency);
                            if ($conversion['success'] ?? false) {
                                $price = (float) ($conversion['converted_amount'] ?? $price);
                            }
                        }

                        BookingExtra::create([
                            'booking_id' => $booking->id,
                            'extra_type' => 'optional',
                            'extra_name' => $extraItem['name'] ?? 'Unknown Extra',
                            'provider_extra_id' => $extraItem['id'] ?? null,
                            'quantity' => (int) ($extraItem['qty'] ?? 1),
                            'price' => $price,
                        ]);
                    }
                } else {
                    $extras = $extrasPayload['extras'] ?? [];
                    if (!empty($extras)) {
                        $addonIds = array_keys($extras);
                        $addons = \App\Models\BookingAddon::whereIn('id', $addonIds)->get()->keyBy('id');

                        foreach ($extras as $extraId => $quantity) {
                            if ($quantity > 0) {
                                $addon = $addons->find($extraId);
                                $name = $addon ? $addon->extra_name : "Extra #$extraId";
                                $price = $addon ? $addon->price : 0;

                                BookingExtra::create([
                                    'booking_id' => $booking->id,
                                    'extra_type' => 'optional',
                                    'extra_name' => $name,
                                    'quantity' => (int) $quantity,
                                    'price' => (float) $price,
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();
            Log::info('StripeBookingService: Transaction committed successfully', ['booking_id' => $booking->id]);

            // Create affiliate commission if QR scan tracking data exists
            $affiliateBusinessId = $metadata->affiliate_business_id ?? null;
            if ($affiliateBusinessId) {
                $affiliateData = [
                    'business_id' => $affiliateBusinessId,
                    'customer_scan_id' => $metadata->affiliate_scan_id ?? null,
                ];
                $basePrice = (float) ($metadata->total_amount_net ?? $metadata->provider_grand_total ?? 0);
                app(\App\Services\Affiliate\ScoutCommissionService::class)->createCommission(
                    $booking->id,
                    $customer->id,
                    $basePrice,
                    'stripe',
                    $affiliateData,
                    $booking->booking_currency ?? 'EUR'
                );
            }

            $this->notifyBookingCreated($booking, $customer, $customerData['temp_password']);

            $shouldTriggerProvider = empty($booking->provider_booking_ref);

            // Unified provider flow: all external providers reserve through the gateway.
            if ($booking->provider_source !== 'internal' && $shouldTriggerProvider) {
                $this->triggerGatewayReservation($booking, $metadata);
            } elseif ($booking->provider_source === 'internal') {
                // Internal vehicles don't require external API reservation
                Log::info('StripeBookingService: Internal vehicle booking confirmed', [
                    'booking_id' => $booking->id,
                    'vehicle_id' => $booking->vehicle_id,
                    'plan' => $booking->plan,
                    'total_amount' => $booking->total_amount,
                ]);
            }

            return $booking;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('StripeBookingService: Error creating booking - Transaction Rolled Back', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function findOrCreateCustomer($metadata): array
    {
        $email = $metadata->customer_email ?? null;

        $fullName = trim($metadata->customer_name ?? 'Guest');
        $nameParts = preg_split('/\s+/', $fullName, 2);
        $firstName = $nameParts[0] ?? 'Guest';
        $lastName = $nameParts[1] ?? 'Guest';
        $userId = $metadata->user_id ?? null;
        $phone = $metadata->customer_phone ?? null;

        $tempPassword = null;
        $user = null;

        if (!$userId) {
            if ($email && $phone) {
                $user = User::where('email', $email)
                    ->orWhere('phone', $phone)
                    ->first();
            } elseif ($email) {
                $user = User::where('email', $email)->first();
            } elseif ($phone) {
                $user = User::where('phone', $phone)->first();
            }

            if (!$user) {
                $safePhone = $phone ?: 'guest_' . now()->timestamp . '_' . Str::random(6);
                $tempPassword = Str::random(10);
                $user = User::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email ?? 'guest_' . now()->timestamp . '_' . Str::random(6) . '@temp.com',
                    'phone' => $safePhone,
                    'password' => Hash::make($tempPassword),
                    'role' => 'customer',
                    'status' => 'active',
                ]);
            }

            $userId = $user->id;
        }

        $resolvedUser = $user ?? User::find($userId);
        if ($resolvedUser) {
            $this->ensureUserProfile($resolvedUser, $metadata);
        }

        if ($email) {
            $customer = Customer::where('email', $email)->first();
            if ($customer) {
                return [
                    'customer' => $customer,
                    'temp_password' => null,
                ];
            }
        }

        $customer = Customer::create([
            'user_id' => $userId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email ?? 'guest_' . time() . '@temp.com',
            'phone' => $phone,
            'flight_number' => $metadata->flight_number ?? null,
            'driver_age' => $metadata->customer_driver_age ?? null,
        ]);

        return [
            'customer' => $customer,
            'temp_password' => $tempPassword,
        ];
    }

    protected function ensureUserProfile(User $user, $metadata): void
    {
        if ($user->profile()->exists()) {
            return;
        }

        $country = $metadata->customer_country ?? $metadata->country ?? null;
        if (!$country) {
            $country = 'us';
        }

        try {
            UserProfile::firstOrCreate([
                'user_id' => $user->id,
            ], [
                'address_line1' => $metadata->customer_address ?? null,
                'city' => $metadata->customer_city ?? null,
                'postal_code' => $metadata->customer_postal_code ?? null,
                'country' => $country,
            ]);
        } catch (\Exception $e) {
            Log::warning('StripeBookingService: Failed to create user profile', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function decodeJsonArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return [];
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return [];
        }

        $decoded = json_decode($trimmed, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function resolveExtrasPayloadFromMetadata($metadata): array
    {
        if (isset($metadata->resolved_extras_payload) && is_array($metadata->resolved_extras_payload)) {
            return $metadata->resolved_extras_payload;
        }

        $resolved = [
            'payload_id' => null,
            'detailed_extras' => [],
            'extras' => [],
            'location_details' => null,
            'dropoff_location_details' => null,
            'location_instructions' => null,
            'driver_requirements' => null,
            'terms' => null,
            'vehicle_benefits' => [],
            'security_deposit' => null,
            'deposit_payment_method' => null,
            'selected_deposit_type' => null,
            'fuel_type' => null,
            'mileage' => null,
            'transmission' => null,
        ];

        $payloadId = $metadata->extras_payload_id ?? null;
        if ($payloadId) {
            $resolved['payload_id'] = (string) $payloadId;
            try {
                $payloadRecord = StripeCheckoutPayload::find($payloadId);
                if ($payloadRecord) {
                    $payload = $payloadRecord->payload ?? [];
                    $resolved['detailed_extras'] = $this->decodeJsonArray($payload['detailed_extras'] ?? []);
                    $resolved['extras'] = $this->decodeJsonArray($payload['extras'] ?? []);
                    $resolved['location_details'] = $payload['location_details'] ?? null;
                    $resolved['dropoff_location_details'] = $payload['dropoff_location_details'] ?? null;
                    $resolved['location_instructions'] = $payload['location_instructions'] ?? null;
                    $resolved['driver_requirements'] = $payload['driver_requirements'] ?? null;
                    $resolved['terms'] = $payload['terms'] ?? null;
                    // Vehicle benefits, deposit, and policy data
                    $resolved['vehicle_benefits'] = $payload['vehicle_benefits'] ?? [];
                    $resolved['security_deposit'] = $payload['security_deposit'] ?? null;
                    $resolved['deposit_payment_method'] = $payload['deposit_payment_method'] ?? null;
                    $resolved['selected_deposit_type'] = $payload['selected_deposit_type'] ?? null;
                    $resolved['fuel_type'] = $payload['fuel_type'] ?? null;
                    $resolved['mileage'] = $payload['mileage'] ?? null;
                    $resolved['transmission'] = $payload['transmission'] ?? null;
                } else {
                    Log::warning('StripeBookingService: Extras payload missing', [
                        'payload_id' => $payloadId,
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('StripeBookingService: Extras payload lookup failed', [
                    'payload_id' => $payloadId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (empty($resolved['detailed_extras'])) {
            $resolved['detailed_extras'] = $this->decodeJsonArray($metadata->extras_data ?? null);
        }

        if (empty($resolved['extras'])) {
            $resolved['extras'] = $this->decodeJsonArray($metadata->extras ?? null);
        }

        $metadata->resolved_extras_payload = $resolved;

        return $resolved;
    }

    protected function buildProviderMetadataFromSession($metadata, Booking $booking): array
    {
        $normalizeValue = static function ($value) {
            if (!is_string($value)) {
                return $value;
            }

            $trimmed = trim($value);
            if ($trimmed === '') {
                return $value;
            }

            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }

            return $value;
        };

        $providerCurrency = ($metadata->provider_currency ?? $metadata->currency)
            ? $this->normalizeCurrencyCode($metadata->provider_currency ?? $metadata->currency)
            : null;
        $bookingCurrency = $metadata->currency ? $this->normalizeCurrencyCode($metadata->currency) : null;

        $pickupLocationDetails = $normalizeValue($metadata->location_details ?? null);
        $extrasPayload = $this->resolveExtrasPayloadFromMetadata($metadata);
        if (empty($pickupLocationDetails) && !empty($extrasPayload['location_details'])) {
            $pickupLocationDetails = $extrasPayload['location_details'];
        }

        $dropoffLocationDetails = $normalizeValue($metadata->dropoff_location_details ?? null);
        if (empty($dropoffLocationDetails) && !empty($extrasPayload['dropoff_location_details'])) {
            $dropoffLocationDetails = $extrasPayload['dropoff_location_details'];
        }

        $locationInstructions = $metadata->location_instructions ?? null;
        if (empty($locationInstructions) && !empty($extrasPayload['location_instructions'])) {
            $locationInstructions = $extrasPayload['location_instructions'];
        }

        $driverRequirements = $normalizeValue($metadata->driver_requirements ?? null);
        if (empty($driverRequirements) && !empty($extrasPayload['driver_requirements'])) {
            $driverRequirements = $extrasPayload['driver_requirements'];
        }

        $terms = $normalizeValue($metadata->terms ?? null);
        if (empty($terms) && !empty($extrasPayload['terms'])) {
            $terms = $extrasPayload['terms'];
        }

        // Calculate commission amounts for customer pricing
        $totalAmount = (float) ($metadata->total_amount ?? 0);
        $totalAmountNet = (float) ($metadata->total_amount_net ?? $metadata->provider_grand_total ?? 0);
        $commissionAmount = $totalAmount > 0 && $totalAmountNet > 0
            ? round($totalAmount - $totalAmountNet, 2)
            : 0;
        $commissionRate = $totalAmountNet > 0
            ? round(($commissionAmount / $totalAmountNet) * 100, 2)
            : 15.0;

        $providerMetadata = [
            'provider' => $booking->provider_source ?? ($metadata->vehicle_source ?? null),
            'currency' => $providerCurrency,
            'booking_currency' => $bookingCurrency,
            'quoteid' => $metadata->quoteid ?? null,
            'rental_code' => $metadata->package ?? $metadata->rental_code ?? null,

            // Vendor's ORIGINAL pricing (in vendor's currency)
            'vehicle_total' => $metadata->provider_vehicle_total ?? null,
            'extras_total' => $metadata->provider_extras_total ?? null,
            'grand_total' => $metadata->provider_grand_total ?? $metadata->total_amount_net ?? null,

            'provider_pricing' => [
                'currency' => $providerCurrency,
                'vehicle_total' => $metadata->provider_vehicle_total ?? null,
                'extras_total' => $metadata->provider_extras_total ?? null,
                'grand_total' => $metadata->provider_grand_total ?? null,
                'deposit_percentage' => $metadata->deposit_percentage ?? null,
                'settlement_model' => 'commission_only',
                'deposit_total' => isset($metadata->provider_grand_total) ? 0.0 : null,
                'due_at_pickup_total' => isset($metadata->provider_grand_total)
                    ? round((float) $metadata->provider_grand_total, 2)
                    : null,
                // Deposit & excess in vendor's original currency
                'deposit_amount' => $metadata->deposit_amount ?? null,
                'excess_amount' => $metadata->excess_amount ?? null,
                'excess_theft_amount' => $metadata->excess_theft_amount ?? null,
                'deposit_currency' => $metadata->deposit_currency ?? $providerCurrency,
            ],

            // Customer-facing pricing (in customer's currency with 15% commission)
            'customer_pricing' => [
                'currency' => $bookingCurrency,
                'vehicle_total' => $metadata->vehicle_total ?? 0,
                'extras_total' => $metadata->extras_total ?? 0,
                'commission_rate' => $commissionRate / 100, // Store as decimal (0.15)
                'commission_amount' => $commissionAmount,
                'grand_total' => $totalAmount,
            ],

            // Deposit & excess (in provider's original currency for vendor display)
            'deposit_amount' => $metadata->deposit_amount ?? null,
            'excess_amount' => $metadata->excess_amount ?? null,
            'excess_theft_amount' => $metadata->excess_theft_amount ?? null,
            'deposit_currency' => $metadata->deposit_currency ?? $providerCurrency,

            // Exchange rates
            'exchange_rates' => [
                'provider_to_booking' => $metadata->exchange_rate_provider_to_booking ?? null,
                'booking_to_admin' => $metadata->exchange_rate_booking_to_admin ?? null,
            ],
        ];

        // Extract vehicle benefits/policies from extras payload
        $vehicleBenefits = $extrasPayload['vehicle_benefits'] ?? [];
        $benefits = [
            // Deposit & Excess
            'deposit_amount' => $vehicleBenefits['deposit_amount'] ?? $metadata->deposit_amount ?? null,
            'deposit_currency' => $vehicleBenefits['deposit_currency'] ?? $metadata->deposit_currency ?? $providerCurrency,
            'excess_amount' => $vehicleBenefits['excess_amount'] ?? $metadata->excess_amount ?? null,
            'excess_theft_amount' => $vehicleBenefits['excess_theft_amount'] ?? $metadata->excess_theft_amount ?? null,
            'security_deposit' => $extrasPayload['security_deposit'] ?? null,
            'deposit_payment_method' => $extrasPayload['deposit_payment_method'] ?? null,
            'selected_deposit_type' => $extrasPayload['selected_deposit_type'] ?? null,
            // Cancellation
            'cancellation_available_per_day' => $vehicleBenefits['cancellation_available_per_day'] ?? null,
            'cancellation_available_per_day_date' => $vehicleBenefits['cancellation_available_per_day_date'] ?? null,
            'cancellation_available_per_week' => $vehicleBenefits['cancellation_available_per_week'] ?? null,
            'cancellation_available_per_week_date' => $vehicleBenefits['cancellation_available_per_week_date'] ?? null,
            'cancellation_available_per_month' => $vehicleBenefits['cancellation_available_per_month'] ?? null,
            'cancellation_available_per_month_date' => $vehicleBenefits['cancellation_available_per_month_date'] ?? null,
            // Mileage / KM
            'limited_km_per_day' => $vehicleBenefits['limited_km_per_day'] ?? null,
            'limited_km_per_day_range' => $vehicleBenefits['limited_km_per_day_range'] ?? null,
            'limited_km_per_week' => $vehicleBenefits['limited_km_per_week'] ?? null,
            'limited_km_per_week_range' => $vehicleBenefits['limited_km_per_week_range'] ?? null,
            'limited_km_per_month' => $vehicleBenefits['limited_km_per_month'] ?? null,
            'limited_km_per_month_range' => $vehicleBenefits['limited_km_per_month_range'] ?? null,
            'price_per_km_per_day' => $vehicleBenefits['price_per_km_per_day'] ?? null,
            'price_per_km_per_week' => $vehicleBenefits['price_per_km_per_week'] ?? null,
            'price_per_km_per_month' => $vehicleBenefits['price_per_km_per_month'] ?? null,
            'unlimited_mileage' => $vehicleBenefits['unlimited_mileage'] ?? null,
            // Driver Age
            'minimum_driver_age' => $vehicleBenefits['minimum_driver_age'] ?? null,
            'maximum_driver_age' => $vehicleBenefits['maximum_driver_age'] ?? null,
            'young_driver_age_from' => $vehicleBenefits['young_driver_age_from'] ?? null,
            'young_driver_age_to' => $vehicleBenefits['young_driver_age_to'] ?? null,
            'senior_driver_age_from' => $vehicleBenefits['senior_driver_age_from'] ?? null,
            'senior_driver_age_to' => $vehicleBenefits['senior_driver_age_to'] ?? null,
            // Fuel & Vehicle Info
            'fuel_policy' => $vehicleBenefits['fuel_policy'] ?? $extrasPayload['fuel_type'] ?? null,
            'mileage' => $extrasPayload['mileage'] ?? null,
            'transmission' => $extrasPayload['transmission'] ?? null,
        ];

        // Merge with the rest of the metadata structure
        $additionalMetadata = [
            'benefits' => array_filter($benefits, fn($v) => $v !== null),
            'pickup_location_id' => $metadata->pickup_location_code ?? null,
            'dropoff_location_id' => $metadata->return_location_code ?? $metadata->dropoff_location_code ?? $metadata->pickup_location_code ?? null,
            'location' => $pickupLocationDetails,
            'pickup_location_details' => $pickupLocationDetails,
            'dropoff_location_details' => $dropoffLocationDetails,
            'location_instructions' => $locationInstructions,
            'driver_requirements' => $driverRequirements,
            'terms' => $terms,
            'extras_selected' => $extrasPayload['detailed_extras'] ?? ($extrasPayload['extras'] ?? null),
            'sbc' => [
                'availability_id' => $metadata->sbc_availability_id ?? null,
                'rate_id' => $metadata->sbc_rate_id ?? null,
                'vehicle_id' => $metadata->sbc_vehicle_id ?? null,
                'payment_type' => $metadata->sbc_payment_type ?? null,
                'currency' => $metadata->sbc_currency ?? null,
                'deposit' => $metadata->deposit ?? null,
                'excess' => $this->extractSicilyByCarExcessSummary($metadata),
            ],
            'surprice' => [
                'vendor_rate_id' => $metadata->surprice_vendor_rate_id ?? null,
                'rate_code' => $metadata->surprice_rate_code ?? null,
                'extended_pickup_code' => $metadata->surprice_extended_pickup_code ?? null,
                'extended_dropoff_code' => $metadata->surprice_extended_dropoff_code ?? null,
                'acriss_code' => $metadata->sipp_code ?? null,
            ],
            'recordgo' => [
                'country' => $metadata->recordgo_country ?? null,
                'sell_code' => $metadata->recordgo_sell_code ?? null,
                'sellcode_ver' => $metadata->recordgo_sellcode_ver ?? null,
                'acriss_code' => $metadata->recordgo_acriss_code ?? null,
                'product_id' => $metadata->recordgo_product_id ?? null,
                'product_ver' => $metadata->recordgo_product_ver ?? null,
                'rate_prod_ver' => $metadata->recordgo_rate_prod_ver ?? null,
                'booking_total' => $metadata->recordgo_booking_total ?? null,
                'automatic_complements' => $metadata->recordgo_automatic_complements ?? null,
            ],
            'cancellation_deadline' => $metadata->cancellation_deadline ?? null,
            'cancellation_free' => $metadata->cancellation_free ?? null,
            'cancellation_fee' => $metadata->cancellation_fee ?? null,
            'cancellation_fee_currency' => $metadata->cancellation_fee_currency ?? null,
            'customer_snapshot' => [
                'name' => $metadata->customer_name ?? null,
                'email' => $metadata->customer_email ?? null,
                'phone' => $metadata->customer_phone ?? null,
                'driver_age' => $metadata->customer_driver_age ?? null,
                'driver_license_number' => $metadata->driver_license_number ?? null,
                'address' => $metadata->customer_address ?? null,
                'city' => $metadata->customer_city ?? null,
                'postal_code' => $metadata->customer_postal_code ?? null,
                'country' => $metadata->customer_country ?? null,
                'flight_number' => $metadata->flight_number ?? null,
                'notes' => $metadata->notes ?? null,
            ],
        ];

        // Merge provider metadata with additional fields
        return array_merge($providerMetadata, $additionalMetadata);
    }

    protected function normalizeCurrencyCode($currency): string
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

    protected function notifyBookingCreated(Booking $booking, Customer $customer, ?string $tempPassword = null): void
    {
        try {
            $vehicle = $booking->vehicle ?? null;
            $isInternalBooking = ($booking->provider_source ?? null) === 'internal';

            $adminEmail = env('VITE_ADMIN_EMAIL', 'default@admin.com');
            $admin = User::where('email', $adminEmail)->first();
            if ($admin) {
                $admin->notify(new BookingCreatedAdminNotification($booking, $customer, $vehicle));
            }

            if (!$isInternalBooking) {
                Log::info('StripeBookingService: Skipping customer/vendor booking-created notifications for external provider booking', [
                    'booking_id' => $booking->id,
                    'provider_source' => $booking->provider_source,
                ]);

                return;
            }

            $vendor = null;
            $vendorProfile = null;

            if ($booking->vehicle_id && $vehicle) {
                $vendor = User::find($vehicle->vendor_id);
                $vendorProfile = VendorProfile::where('user_id', $vehicle->vendor_id)->first();
            }

            // Notify vendor (use direct notify() so notification stores with correct notifiable_id)
            if ($vendor) {
                $vendor->notify(new BookingCreatedVendorNotification($booking, $customer, $vehicle, $vendor));
            }

            // Company notification (mail only, no database storage needed)
            if ($vendorProfile && $vendorProfile->company_email) {
                Notification::route('mail', $vendorProfile->company_email)
                    ->notify(new BookingCreatedCompanyNotification($booking, $customer, $vehicle, $vendorProfile));
            }

            // Notify customer - use User model so notification stores with correct notifiable_id
            if ($tempPassword) {
                if (!empty($customer->email) && !str_starts_with($customer->email, 'guest_')) {
                    $customerUser = $customer->user;
                    if ($customerUser) {
                        $customerUser->notify(new GuestBookingCreatedNotification($booking, $customer, $vehicle, $tempPassword));
                    } else {
                        Notification::route('mail', $customer->email)
                            ->notify(new GuestBookingCreatedNotification($booking, $customer, $vehicle, $tempPassword));
                    }
                } else {
                    Log::warning('StripeBookingService: Guest account created without deliverable email', [
                        'booking_id' => $booking->id,
                        'customer_id' => $customer->id,
                        'email' => $customer->email,
                    ]);
                }
            } else {
                $customerUser = $customer->user;
                if ($customerUser) {
                    $customerUser->notify(new BookingCreatedCustomerNotification($booking, $customer, $vehicle));
                } else {
                    Notification::route('mail', $customer->email)
                        ->notify(new BookingCreatedCustomerNotification($booking, $customer, $vehicle));
                }
            }
        } catch (\Exception $e) {
            Log::warning('StripeBookingService: Failed to send booking notifications', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function extractSicilyByCarExcessSummary($metadata): array
    {
        $extrasPayload = $this->resolveExtrasPayloadFromMetadata($metadata);
        $selectedExtras = $extrasPayload['detailed_extras'] ?? [];

        $summary = [];
        foreach ($selectedExtras as $extra) {
            if (!is_array($extra)) {
                continue;
            }

            $excess = $extra['excess'] ?? null;
            if ($excess === null || $excess === '') {
                continue;
            }

            $code = $extra['code']
                ?? $extra['service_id']
                ?? $extra['option_id']
                ?? $extra['id']
                ?? null;

            $summary[] = [
                'code' => $code,
                'name' => $extra['name'] ?? $extra['label'] ?? $extra['description'] ?? null,
                'excess' => $excess,
            ];
        }

        return $summary;
    }

    /**
     * Trigger reservation via Vrooem Gateway (replaces all provider-specific trigger methods).
     */
    protected function triggerGatewayReservation($booking, $metadata)
    {
        Log::info('VrooemGateway: Triggering reservation for booking', [
            'booking_id' => $booking->id,
            'provider_source' => $booking->provider_source,
        ]);

        try {
            $gateway = app(VrooemGatewayService::class);

            $gatewayVehicleId = $metadata->gateway_vehicle_id ?? null;
            if (!$gatewayVehicleId) {
                Log::warning('VrooemGateway: No gateway_vehicle_id in metadata, falling back to vehicle_id', [
                    'booking_id' => $booking->id,
                    'vehicle_id' => $metadata->vehicle_id ?? 'N/A',
                ]);
                $gatewayVehicleId = $metadata->vehicle_id ?? '';
            }

            $nameParts = explode(' ', $metadata->customer_name ?? '', 2);

            $result = $gateway->createBooking([
                'vehicle_id' => $gatewayVehicleId,
                'search_id' => $metadata->gateway_search_id ?? ($metadata->search_session_id ?? ''),
                'driver' => [
                    'first_name' => $nameParts[0] ?? '',
                    'last_name' => $nameParts[1] ?? '',
                    'email' => $metadata->customer_email ?? '',
                    'phone' => $metadata->customer_phone ?? '',
                    'age' => (int) ($metadata->customer_driver_age ?? 30),
                    'address' => $metadata->customer_address ?? null,
                    'city' => $metadata->customer_city ?? null,
                    'postal_code' => $metadata->customer_postal_code ?? null,
                    'country' => $metadata->customer_country ?? null,
                ],
                'flight_number' => $metadata->flight_number ?? null,
                'extras' => collect($booking->extras ?? [])->map(function ($extra) {
                    return [
                        'extra_id' => $extra->provider_extra_id ?? $extra->extra_name ?? '',
                        'quantity' => $extra->quantity ?? 1,
                    ];
                })->all(),
                'pickup_date' => $metadata->pickup_date ?? null,
                'pickup_time' => $metadata->pickup_time ?? '09:00',
                'dropoff_date' => $metadata->dropoff_date ?? null,
                'dropoff_time' => $metadata->dropoff_time ?? '09:00',
                'laravel_booking_id' => $booking->id,
            ]);

            if ($result && !empty($result['supplier_booking_id'])) {
                $providerBookingRef = $result['supplier_booking_id'];
                Log::info('VrooemGateway: Reservation successful', [
                    'booking_id' => $booking->id,
                    'gateway_booking_id' => $result['gateway_booking_id'] ?? null,
                    'supplier_booking_id' => $providerBookingRef,
                ]);

                $booking->update([
                    'provider_booking_ref' => $providerBookingRef,
                    'provider_metadata' => array_merge(
                        $booking->provider_metadata ?? [],
                        [
                            'gateway_booking_id' => $result['gateway_booking_id'] ?? null,
                            'gateway_status' => $result['status'] ?? 'confirmed',
                            'gateway_supplier_id' => $result['supplier_id'] ?? null,
                        ]
                    ),
                    'notes' => ($booking->notes ? $booking->notes . "\n" : '')
                        . 'Gateway Conf: ' . $providerBookingRef,
                ]);
            } else {
                Log::error('VrooemGateway: Reservation failed - no booking ref returned', [
                    'booking_id' => $booking->id,
                    'result' => $result,
                ]);
                $booking->update([
                    'provider_metadata' => array_merge(
                        $booking->provider_metadata ?? [],
                        ['gateway_error' => 'No booking reference returned']
                    ),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('VrooemGateway: Reservation error', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $booking->update([
                'provider_metadata' => array_merge(
                    $booking->provider_metadata ?? [],
                    ['gateway_error' => $e->getMessage()]
                ),
            ]);
        }
    }
}
