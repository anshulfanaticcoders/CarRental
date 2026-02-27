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
use App\Services\AdobeCarService;
use App\Services\BookingAmountService;
use App\Services\CurrencyConversionService;
use App\Services\SicilyByCarService;
use App\Services\RecordGoService;
use App\Services\SurpriceService;
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
    protected $adobeCarService;
    protected $renteonService;
    protected $sicilyByCarService;
    protected $recordGoService;
    protected $surpriceService;

    public function __construct(AdobeCarService $adobeCarService, RenteonService $renteonService, SicilyByCarService $sicilyByCarService, RecordGoService $recordGoService, SurpriceService $surpriceService)
    {
        $this->adobeCarService = $adobeCarService;
        $this->renteonService = $renteonService;
        $this->sicilyByCarService = $sicilyByCarService;
        $this->recordGoService = $recordGoService;
        $this->surpriceService = $surpriceService;
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
                    'base_price' => (float) ($metadata->total_amount_net ?? $metadata->provider_grand_total ?? $booking->base_price ?? 0),
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
                    'base_price' => (float) ($metadata->total_amount_net ?? $metadata->provider_grand_total ?? 0),
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
                ]);
            }

            if (empty($booking->provider_metadata)) {
                $providerMetadata = $this->buildProviderMetadataFromSession($metadata, $booking);
                $booking->update([
                    'provider_metadata' => $providerMetadata,
                ]);
            }

            $this->ensureProviderLocationDetails($booking);

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

            app(BookingAmountService::class)->createForBooking($booking, [
                'total_amount' => $booking->total_amount,
                'amount_paid' => $booking->amount_paid,
                'pending_amount' => $booking->pending_amount,
                'extra_amount' => $extraAmount,
            ], $bookingCurrency, $vendorCurrency, $providerAmounts);

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

            $this->notifyBookingCreated($booking, $customer, $customerData['temp_password']);

            $shouldTriggerProvider = empty($booking->provider_booking_ref);

            // Phase 2: Trigger Provider Reservations
            if ($booking->provider_source === 'locauto_rent' && $shouldTriggerProvider) {
                $this->triggerLocautoReservation($booking, $metadata);
            } elseif (($booking->provider_source === 'greenmotion' || $booking->provider_source === 'usave') && $shouldTriggerProvider) {
                $this->triggerGreenMotionReservation($booking, $metadata);
            } elseif ($booking->provider_source === 'adobe' && $shouldTriggerProvider) {
                $this->triggerAdobeReservation($booking, $metadata);
            } elseif ($booking->provider_source === 'internal') {
                // Internal vehicles don't require external API reservation
                Log::info('StripeBookingService: Internal vehicle booking confirmed', [
                    'booking_id' => $booking->id,
                    'vehicle_id' => $booking->vehicle_id,
                    'plan' => $booking->plan,
                    'total_amount' => $booking->total_amount,
                ]);
            } elseif ($booking->provider_source === 'renteon' && $shouldTriggerProvider) {
                $this->triggerRenteonReservation($booking, $metadata);
            } elseif ($booking->provider_source === 'okmobility' && $shouldTriggerProvider) {
                $this->triggerOkMobilityReservation($booking, $metadata);
            } elseif ($booking->provider_source === 'favrica' && $shouldTriggerProvider) {
                $this->triggerFavricaReservation($booking, $metadata);
            } elseif ($booking->provider_source === 'xdrive' && $shouldTriggerProvider) {
                $this->triggerXDriveReservation($booking, $metadata);
            } elseif ($booking->provider_source === 'sicily_by_car' && $shouldTriggerProvider) {
                $this->triggerSicilyByCarReservation($booking, $metadata);
            } elseif ($booking->provider_source === 'recordgo' && $shouldTriggerProvider) {
                $this->triggerRecordGoReservation($booking, $metadata);
            } elseif ($booking->provider_source === 'surprice' && $shouldTriggerProvider) {
                $this->triggerSurpriceReservation($booking, $metadata);
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
                'deposit_total' => isset($metadata->provider_grand_total, $metadata->deposit_percentage)
                    ? round((float) $metadata->provider_grand_total * ((float) $metadata->deposit_percentage / 100), 2)
                    : null,
                'due_at_pickup_total' => isset($metadata->provider_grand_total, $metadata->deposit_percentage)
                    ? round((float) $metadata->provider_grand_total - ((float) $metadata->provider_grand_total * ((float) $metadata->deposit_percentage / 100)), 2)
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

    private function ensureProviderLocationDetails(Booking $booking): void
    {
        if (!in_array($booking->provider_source, ['greenmotion', 'usave'], true)) {
            return;
        }

        $metadata = $booking->provider_metadata ?? [];
        $pickupId = $metadata['pickup_location_id'] ?? null;
        $dropoffId = $metadata['dropoff_location_id'] ?? null;

        if (!$pickupId) {
            return;
        }

        $updated = false;

        $pickupDetails = $metadata['pickup_location_details'] ?? $metadata['location'] ?? null;
        if (!$pickupDetails) {
            $pickupDetails = $this->fetchGreenMotionLocationDetails($pickupId, $booking->provider_source);
            if ($pickupDetails) {
                $metadata['pickup_location_details'] = $pickupDetails;
                $metadata['location'] = $pickupDetails;
                $updated = true;
            }
        }

        if ($dropoffId) {
            if ($dropoffId === $pickupId) {
                if (empty($metadata['dropoff_location_details']) && $pickupDetails) {
                    $metadata['dropoff_location_details'] = $pickupDetails;
                    $updated = true;
                }
            } elseif (empty($metadata['dropoff_location_details'])) {
                $dropoffDetails = $this->fetchGreenMotionLocationDetails($dropoffId, $booking->provider_source);
                if ($dropoffDetails) {
                    $metadata['dropoff_location_details'] = $dropoffDetails;
                    $updated = true;
                }
            }
        }

        if ($updated) {
            $booking->update([
                'provider_metadata' => $metadata,
            ]);
        }
    }

    private function fetchGreenMotionLocationDetails(string $locationId, string $provider): ?array
    {
        try {
            $service = app(\App\Services\GreenMotionService::class);
            $service->setProvider($provider);
            $response = $service->getLocationInfo($locationId);
            if (!$response) {
                return null;
            }

            return $this->parseGreenMotionLocationXml($response, $locationId);
        } catch (\Exception $e) {
            Log::warning('StripeBookingService: Failed to fetch provider location details', [
                'provider' => $provider,
                'location_id' => $locationId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function parseGreenMotionLocationXml(string $xml, string $locationId): ?array
    {
        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xml);
        libxml_clear_errors();

        if (!$xmlObject || !isset($xmlObject->response->location_info)) {
            return null;
        }

        $loc = $xmlObject->response->location_info;
        $openingHours = [];
        if (isset($loc->opening_hours->day)) {
            foreach ($loc->opening_hours->day as $day) {
                $openingHours[] = [
                    'name' => (string) $day['name'],
                    'is24hrs' => (string) $day['is24hrs'],
                    'open' => (string) $day['open'],
                    'close' => (string) $day['close'],
                ];
            }
        }

        $officeOpeningHours = [];
        if (isset($loc->office_opening_hours->day)) {
            foreach ($loc->office_opening_hours->day as $day) {
                $officeOpeningHours[] = [
                    'name' => (string) $day['name'],
                    'is24hrs' => (string) $day['is24hrs'],
                    'open' => (string) $day['open'],
                    'close' => (string) $day['close'],
                ];
            }
        }

        $outOfHours = [];
        if (isset($loc->out_of_hours->day)) {
            foreach ($loc->out_of_hours->day as $day) {
                $outOfHours[] = [
                    'name' => (string) $day['name'],
                    'open' => (string) $day['open'],
                    'close' => (string) $day['close'],
                ];
            }
        }

        $outOfHoursDropoff = [];
        if (isset($loc->out_of_hours_dropoff->day)) {
            foreach ($loc->out_of_hours_dropoff->day as $day) {
                $outOfHoursDropoff[] = [
                    'name' => (string) $day['name'],
                    'start' => (string) $day['start'],
                    'end' => (string) $day['end'],
                    'start2' => (string) $day['start2'],
                    'end2' => (string) $day['end2'],
                ];
            }
        }

        $daytimeClosuresHours = [];
        if (isset($loc->daytime_closures_hours->day)) {
            foreach ($loc->daytime_closures_hours->day as $day) {
                $daytimeClosuresHours[] = [
                    'name' => (string) $day['name'],
                    'start' => (string) $day['start'],
                    'end' => (string) $day['end'],
                ];
            }
        }

        return [
            'id' => $locationId,
            'name' => (string) $loc->location_name,
            'address_1' => (string) $loc->address_1,
            'address_2' => (string) $loc->address_2,
            'address_3' => (string) $loc->address_3,
            'address_city' => (string) $loc->address_city,
            'address_county' => (string) $loc->address_county,
            'address_postcode' => (string) $loc->address_postcode,
            'telephone' => (string) $loc->telephone,
            'fax' => (string) $loc->fax,
            'email' => (string) $loc->email,
            'latitude' => (string) $loc->latitude,
            'longitude' => (string) $loc->longitude,
            'iata' => (string) $loc->iata,
            'opening_hours' => $openingHours,
            'office_opening_hours' => $officeOpeningHours,
            'out_of_hours' => $outOfHours,
            'out_of_hours_dropoff' => $outOfHoursDropoff,
            'daytime_closures_hours' => $daytimeClosuresHours,
            'out_of_hours_charge' => (string) $loc->out_of_hours_charge,
            'charge_both_ways' => (string) $loc->charge_both_ways,
            'extra' => (string) $loc->extra,
            'collection_details' => (string) $loc->collectiondetails,
        ];
    }

    protected function notifyBookingCreated(Booking $booking, Customer $customer, ?string $tempPassword = null): void
    {
        try {
            $vehicle = $booking->vehicle ?? null;

            $adminEmail = env('VITE_ADMIN_EMAIL', 'default@admin.com');
            $admin = User::where('email', $adminEmail)->first();
            if ($admin) {
                $admin->notify(new BookingCreatedAdminNotification($booking, $customer, $vehicle));
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

    /**
     * Trigger reservation on Locauto API
     */
    protected function triggerLocautoReservation($booking, $metadata)
    {
        Log::info('Locauto: Triggering reservation for booking', ['booking_id' => $booking->id]);

        try {
            $locautoService = app(\App\Services\LocautoRentService::class);

            // Split name into first and last
            $nameParts = explode(' ', $metadata->customer_name ?? 'Guest User', 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? 'Guest';

            // Prepare extras (including protection plan)
            $extras = [];

            // Add protection code(s) if present
            if (!empty($metadata->protection_code)) {
                $protectionCodes = json_decode($metadata->protection_code, true);
                if (is_array($protectionCodes)) {
                    foreach ($protectionCodes as $code) {
                        $extras[] = ['code' => $code, 'quantity' => 1];
                    }
                } else {
                    // Single code (legacy / non-array)
                    $extras[] = ['code' => $metadata->protection_code, 'quantity' => 1];
                }
            }

            // Add other extras
            $rawExtras = json_decode($metadata->extras ?? '[]', true);
            foreach ($rawExtras as $code => $qty) {
                if ($qty > 0) {
                    $realCode = str_replace('locauto_extra_', '', $code);
                    $extras[] = [
                        'code' => $realCode,
                        'quantity' => $qty
                    ];
                }
            }

            $reservationData = [
                'pickup_date' => $metadata->pickup_date,
                'pickup_time' => $metadata->pickup_time,
                'return_date' => $metadata->dropoff_date,
                'return_time' => $metadata->dropoff_time,
                'pickup_location_code' => $metadata->pickup_location_code,
                'return_location_code' => $metadata->return_location_code ?? $metadata->pickup_location_code,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'sipp_code' => $metadata->sipp_code,
                'extras' => $extras,
                'driver_age' => $metadata->customer_driver_age ?? 35,
                'email' => $metadata->customer_email ?? '',
                'phone' => $metadata->customer_phone ?? '',
            ];

            Log::info('Locauto: Sending reservation request', ['data' => $reservationData]);
            $xmlResponse = $locautoService->makeReservation($reservationData);

            if ($xmlResponse) {
                libxml_use_internal_errors(true);
                $xmlObject = simplexml_load_string($xmlResponse);

                if ($xmlObject !== false) {
                    $xmlObject->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
                    $xmlObject->registerXPathNamespace('ota', 'http://www.opentravel.org/OTA/2003/05');
                    $xmlObject->registerXPathNamespace('locauto', 'https://nextrent.locautorent.com');

                    // Standard OTA Success check
                    $success = $xmlObject->xpath('//ota:Success');
                    $uniqueId = $xmlObject->xpath('//ota:UniqueID');
                    
                    $confirmationNumber = null;
                    if (!empty($uniqueId)) {
                        $confirmationNumber = (string) $uniqueId[0]['ID'];
                    }

                    if ($confirmationNumber) {
                        Log::info('Locauto: Reservation successful', ['conf' => $confirmationNumber]);

                        $booking->update([
                            'provider_booking_ref' => $confirmationNumber,
                            'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Locauto Conf: " . $confirmationNumber
                        ]);
                    } else {
                        // Extract error
                        $errors = $xmlObject->xpath('//ota:Error');
                        $errorMessage = 'Confirmation number missing in response.';
                        if (!empty($errors)) {
                            $errorMessage = (string) $errors[0];
                            if (empty($errorMessage)) {
                                $errorMessage = (string) $errors[0]['ShortText'];
                            }
                        }
                        
                        Log::error('Locauto: Reservation failed', ['error' => $errorMessage, 'response' => $xmlResponse]);
                        $booking->update([
                            'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Locauto Reservation failed: " . $errorMessage
                        ]);
                    }
                } else {
                    Log::error('Locauto: Failed to parse XML response', ['response' => $xmlResponse]);
                    $booking->update([
                        'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Locauto Reservation failed: Invalid XML response."
                    ]);
                }
                libxml_clear_errors();
            } else {
                Log::error('Locauto: Empty response from API');
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Locauto Reservation failed: No response from API."
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Locauto: Exception during reservation trigger', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $booking->update([
                'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Locauto Reservation Error: " . $e->getMessage()
            ]);
        }
    }

    /**
     * Trigger reservation on GreenMotion API
     */
    protected function triggerGreenMotionReservation($booking, $metadata)
    {
        Log::info('GreenMotion: Triggering reservation for booking', [
            'booking_id' => $booking->id,
            'metadata' => (array) $metadata
        ]);

        try {
            $greenMotionService = app(\App\Services\GreenMotionService::class);
            $greenMotionService->setProvider($booking->provider_source); // greenmotion or usave

            // Split name into first and last
            $nameParts = explode(' ', $metadata->customer_name ?? 'Guest User', 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? 'Guest';

            $customerDetails = [
                'firstname' => $firstName,
                'surname' => $lastName,
                'phone' => $metadata->customer_phone,
                'email' => $metadata->customer_email,
                'flight_number' => $metadata->flight_number ?? '',
                'address1' => $metadata->customer_address ?? '',
                'town' => $metadata->customer_city ?? '',
                'postcode' => $metadata->customer_postal_code ?? '',
                'country' => $metadata->customer_country ?? '',
                'driver_licence_number' => $metadata->driver_license_number ?? '',
            ];

            // Parse detailed extras if available
            $extras = [];
            $extrasPayload = $this->resolveExtrasPayloadFromMetadata($metadata);
            $rawExtras = $extrasPayload['detailed_extras'] ?? [];
            foreach ($rawExtras as $ex) {
                if (($ex['qty'] ?? 0) > 0) {
                    $optionId = $ex['option_id'] ?? $ex['optionID'] ?? $ex['id'] ?? null;
                    $optionTotal = $ex['total'] ?? $ex['total_for_booking'] ?? null;
                    if ($optionId === null || $optionTotal === null) {
                        continue;
                    }

                    $extras[] = [
                        'id' => $optionId,
                        'option_qty' => $ex['qty'],
                        'option_total' => $optionTotal,
                        'pre_pay' => 'No',
                    ];
                }
            }

            // Get Vehicle ID - strip provider prefix if present
            $vehicleId = $metadata->vehicle_id;
            if (strpos($vehicleId, $booking->provider_source . '_') === 0) {
                $vehicleId = substr($vehicleId, strlen($booking->provider_source . '_'));
            }

            $providerVehicleTotal = $metadata->provider_vehicle_total ?? $metadata->vehicle_total ?? $metadata->total_amount_net ?? $metadata->total_amount;
            $providerGrandTotal = $metadata->provider_grand_total
                ?? (($metadata->provider_vehicle_total ?? null) !== null && ($metadata->provider_extras_total ?? null) !== null
                    ? (float) $metadata->provider_vehicle_total + (float) $metadata->provider_extras_total
                    : ($metadata->total_amount_net ?? $metadata->total_amount ?? null));

            $xmlResponse = $greenMotionService->makeReservation(
                $metadata->pickup_location_code,
                $metadata->pickup_date,
                $metadata->pickup_time,
                $metadata->dropoff_date,
                $metadata->dropoff_time,
                $metadata->customer_driver_age ?? 35,
                $customerDetails,
                $vehicleId,
                $providerVehicleTotal, // vehicleTotal (provider currency)
                $this->normalizeCurrencyCode($metadata->provider_currency ?? $metadata->currency ?? 'EUR'),
                $providerGrandTotal, // grandTotal (provider currency)
                $booking->stripe_session_id,
                $metadata->quoteid,
                $extras,
                $metadata->return_location_code ?? $metadata->dropoff_location_code ?? $metadata->pickup_location_code,
                'POA', // Payment type
                $metadata->package ?? $metadata->rental_code ?? '1', // rentalCode (Product Type)
                $metadata->notes ?? null
            );

            if ($xmlResponse) {
                libxml_use_internal_errors(true);
                $xmlObject = simplexml_load_string($xmlResponse);
                $confirmationNumber = (string) ($xmlObject->response->booking_ref ?? $xmlObject->response->bookingReference ?? '');

                if ($xmlObject !== false && !empty($confirmationNumber)) {
                    Log::info('GreenMotion: Reservation successful', ['conf' => $confirmationNumber]);

                    $booking->update([
                        'provider_booking_ref' => $confirmationNumber,
                        'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "GreenMotion Conf: " . $confirmationNumber
                    ]);
                } else {
                    $errorDetails = $this->extractGreenMotionErrorDetails($xmlObject);
                    $errorCode = $errorDetails['code'] ?? null;
                    $errorMessage = $errorDetails['message'] ?? 'Confirmation number missing in response.';
                    if ($xmlObject === false) {
                        $errorMessage = 'Failed to parse provider response.';
                    }
                    $friendlyNote = $this->mapGreenMotionReservationErrorNote($errorCode, $errorMessage);
                    $notePrefix = $errorCode ? "GreenMotion Reservation failed (Code {$errorCode}): " : 'GreenMotion Reservation failed: ';

                    Log::error('GreenMotion: Reservation failed', [
                        'booking_id' => $booking->id,
                        'error_code' => $errorCode,
                        'error_message' => $errorMessage,
                    ]);
                    $booking->update([
                        'notes' => ($booking->notes ? $booking->notes . "\n" : "") . $notePrefix . $friendlyNote
                    ]);
                }
                libxml_clear_errors();
            } else {
                Log::error('GreenMotion: Empty response from API', [
                    'booking_id' => $booking->id,
                ]);
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . 'GreenMotion Reservation failed: No response from API.'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('GreenMotion: Exception during reservation trigger', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $booking->update([
                'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "GreenMotion Reservation Error: " . $e->getMessage()
            ]);
        }
    }

    private function extractGreenMotionErrorDetails($xmlObject): array
    {
        if (!$xmlObject || !isset($xmlObject->response)) {
            return ['code' => null, 'message' => null];
        }

        $errors = $xmlObject->response->errors ?? null;
        if (!$errors) {
            return ['code' => null, 'message' => null];
        }

        $primaryError = isset($errors->error) ? $errors->error[0] : $errors;

        $message = null;
        if (isset($primaryError->message)) {
            $message = (string) $primaryError->message;
        } elseif (isset($primaryError->Message)) {
            $message = (string) $primaryError->Message;
        } elseif (isset($errors->message)) {
            $message = (string) $errors->message;
        } elseif (isset($errors->Message)) {
            $message = (string) $errors->Message;
        }

        if ($message === null || trim($message) === '') {
            $message = trim((string) $primaryError);
        }

        $code = (string) (
            $primaryError['code']
            ?? $primaryError['errorCode']
            ?? $primaryError->code
            ?? $primaryError->errorCode
            ?? $errors['code']
            ?? $errors->code
            ?? ''
        );
        $code = trim($code) !== '' ? trim($code) : null;

        return [
            'code' => $code,
            'message' => $message ?: null,
        ];
    }

    private function mapGreenMotionReservationErrorNote(?string $code, ?string $message): string
    {
        $code = $code ? trim($code) : null;

        switch ($code) {
            case '0414':
            case '0415':
                return 'Vehicle no longer available. Please search again or choose another vehicle.';
            case '0417':
            case '0418':
                return 'Quote expired or pricing changed. Please refresh and try again.';
            case '0422':
                return 'Provider pricing mismatch. Please refresh and try again.';
            case '0603':
                return 'Customer details are incomplete. Please update and try again.';
            default:
                return $message ?: 'Reservation failed. Please contact support.';
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
     * Trigger reservation on Sicily By Car API
     */
    protected function triggerSicilyByCarReservation($booking, $metadata)
    {
        Log::info('SicilyByCar: Triggering reservation for booking', ['booking_id' => $booking->id]);

        try {
            $customerName = trim((string) ($metadata->customer_name ?? 'Guest'));
            $nameParts = preg_split('/\s+/', $customerName, 2);
            $firstName = $nameParts[0] ?? 'Guest';
            $lastName = $nameParts[1] ?? 'Guest';

            $pickupDateTime = ($metadata->pickup_date ?? '') && ($metadata->pickup_time ?? '')
                ? $metadata->pickup_date . 'T' . $metadata->pickup_time
                : null;
            $dropoffDateTime = ($metadata->dropoff_date ?? '') && ($metadata->dropoff_time ?? '')
                ? $metadata->dropoff_date . 'T' . $metadata->dropoff_time
                : null;

            $extrasPayload = $this->resolveExtrasPayloadFromMetadata($metadata);
            $rawExtras = $extrasPayload['detailed_extras'] ?? [];
            $include = [];
            foreach ($rawExtras as $extra) {
                if (!is_array($extra)) {
                    continue;
                }
                $qty = (int) ($extra['qty'] ?? $extra['quantity'] ?? 1);
                if ($qty <= 0) {
                    continue;
                }
                $code = $extra['code']
                    ?? $extra['service_id']
                    ?? $extra['option_id']
                    ?? $extra['id']
                    ?? null;
                if ($code !== null && $code !== '') {
                    $include[] = (string) $code;
                }
            }
            $include = array_values(array_unique($include));

            $posCountry = null;
            $customerCountry = $metadata->customer_country ?? null;
            if ($customerCountry && is_string($customerCountry)) {
                $candidate = strtoupper(trim($customerCountry));
                if (strlen($candidate) === 2) {
                    $posCountry = $candidate;
                }
            }

            $voucherNumber = $booking->booking_number ?: ('VROOEM-' . $booking->id);
            $voucherAmount = null;
            if (isset($metadata->provider_grand_total)) {
                $voucherAmount = (float) $metadata->provider_grand_total;
            }

            $missing = [];
            if (!$pickupDateTime) {
                $missing[] = 'pickupDateTime';
            }
            if (!$dropoffDateTime) {
                $missing[] = 'dropoffDateTime';
            }
            if (empty($metadata->pickup_location_code)) {
                $missing[] = 'pickupLocationId';
            }
            if (empty($metadata->sbc_vehicle_id)) {
                $missing[] = 'vehicleId';
            }
            if (empty($metadata->sbc_rate_id)) {
                $missing[] = 'rateId';
            }

            if (!empty($missing)) {
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : '')
                        . 'SicilyByCar Reservation failed: missing fields (' . implode(', ', $missing) . ').',
                ]);
                Log::error('SicilyByCar: Missing required fields for reservation', [
                    'booking_id' => $booking->id,
                    'missing' => $missing,
                ]);
                return;
            }

            $payload = [
                'availabilityId' => $metadata->sbc_availability_id ?? null,
                'pickupLocationId' => $metadata->pickup_location_code ?? null,
                'pickupDateTime' => $pickupDateTime,
                'dropoffLocationId' => $metadata->return_location_code ?? $metadata->pickup_location_code ?? null,
                'dropoffDateTime' => $dropoffDateTime,
                'posCountry' => $posCountry,
                'vehicleId' => $metadata->sbc_vehicle_id ?? null,
                'rateId' => $metadata->sbc_rate_id ?? null,
                'include' => !empty($include) ? $include : null,
                'voucher' => [
                    'number' => $voucherNumber,
                    'amount' => $voucherAmount,
                ],
                'driver' => [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'age' => isset($metadata->customer_driver_age) ? (int) $metadata->customer_driver_age : null,
                    'email' => $metadata->customer_email ?? null,
                    'phone' => $metadata->customer_phone ?? null,
                ],
                'flights' => !empty($metadata->flight_number)
                    ? ['pickupFlightNumber' => (string) $metadata->flight_number]
                    : null,
                'notes' => $metadata->notes ?? null,
            ];

            $payload = array_filter($payload, static function ($value) {
                if ($value === null) {
                    return false;
                }
                if (is_string($value)) {
                    return trim($value) !== '';
                }
                if (is_array($value)) {
                    return !empty($value);
                }
                return true;
            });

            Log::info('SicilyByCar: Sending create reservation', [
                'booking_id' => $booking->id,
                'pickup_location' => $payload['pickupLocationId'] ?? null,
                'dropoff_location' => $payload['dropoffLocationId'] ?? null,
                'vehicle_id' => $payload['vehicleId'] ?? null,
                'rate_id' => $payload['rateId'] ?? null,
            ]);

            $createResponse = $this->sicilyByCarService->createReservation($payload);
            if (!($createResponse['ok'] ?? false)) {
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : '') . 'SicilyByCar Reservation failed: ' . json_encode($createResponse['errors'] ?? []),
                ]);
                Log::error('SicilyByCar: create reservation failed', [
                    'booking_id' => $booking->id,
                    'errors' => $createResponse['errors'] ?? null,
                ]);
                return;
            }

            $data = $createResponse['data'] ?? [];
            $reservation = $data['reservation'] ?? $data;
            $reservationId = $reservation['id'] ?? $data['reservationId'] ?? null;
            $status = $reservation['status'] ?? null;
            $confirmation = $reservation['confirmationNumber'] ?? null;

            if (!$reservationId) {
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : '') . 'SicilyByCar Reservation failed: missing reservation id.',
                ]);
                Log::error('SicilyByCar: reservation id missing', [
                    'booking_id' => $booking->id,
                    'data' => $data,
                ]);
                return;
            }

            $booking->update([
                'provider_booking_ref' => $confirmation ?: $reservationId,
                'notes' => ($booking->notes ? $booking->notes . "\n" : '')
                    . 'SicilyByCar Reservation: ' . ($confirmation ?: $reservationId)
                    . ($status ? ' (' . $status . ')' : ''),
            ]);

            $commitResponse = $this->sicilyByCarService->commitReservation($reservationId);
            if (!($commitResponse['ok'] ?? false)) {
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : '') . 'SicilyByCar Commit failed: ' . json_encode($commitResponse['errors'] ?? []),
                ]);
                Log::error('SicilyByCar: commit failed', [
                    'booking_id' => $booking->id,
                    'errors' => $commitResponse['errors'] ?? null,
                ]);
                return;
            }

            $commitData = $commitResponse['data'] ?? [];
            $commitReservation = $commitData['reservation'] ?? $commitData;
            $commitStatus = $commitReservation['status'] ?? null;
            $commitConfirmation = $commitReservation['confirmationNumber'] ?? $confirmation;

            if ($commitConfirmation && $commitConfirmation !== $booking->provider_booking_ref) {
                $booking->update([
                    'provider_booking_ref' => $commitConfirmation,
                ]);
            }

            if ($commitStatus && $commitStatus !== $status) {
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : '')
                        . 'SicilyByCar Commit status: ' . $commitStatus,
                ]);
            }

            Log::info('SicilyByCar: Reservation committed', [
                'booking_id' => $booking->id,
                'reservation_id' => $reservationId,
                'confirmation' => $commitConfirmation,
                'status' => $commitStatus,
            ]);
        } catch (\Exception $e) {
            Log::error('SicilyByCar: Exception during reservation trigger', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $booking->update([
                'notes' => ($booking->notes ? $booking->notes . "\n" : '') . 'SicilyByCar Reservation Error: ' . $e->getMessage(),
            ]);
        }
    }

    protected function triggerRecordGoReservation($booking, $metadata)
    {
        Log::info('RecordGo: Triggering booking_store for booking', ['booking_id' => $booking->id]);

        try {
            $country = strtoupper((string) ($metadata->recordgo_country ?? $metadata->customer_country ?? ''));
            if ($country === '') {
                $country = 'IT';
            }

            $sellCode = $metadata->recordgo_sell_code ?? $this->recordGoService->resolveSellCode($country);
            $sellCodeVer = $metadata->recordgo_sellcode_ver ?? null;

            $pickupBranch = $metadata->pickup_location_code ?? null;
            $dropoffBranch = $metadata->return_location_code ?? $pickupBranch;

            $pickupDateTime = ($metadata->pickup_date ?? '') && ($metadata->pickup_time ?? '')
                ? $metadata->pickup_date . 'T' . $metadata->pickup_time . ':00'
                : null;
            $dropoffDateTime = ($metadata->dropoff_date ?? '') && ($metadata->dropoff_time ?? '')
                ? $metadata->dropoff_date . 'T' . $metadata->dropoff_time . ':00'
                : null;

            $productId = $metadata->recordgo_product_id ?? null;
            $productVer = $metadata->recordgo_product_ver ?? null;
            $rateProdVer = $metadata->recordgo_rate_prod_ver ?? null;
            $acrissCode = $metadata->recordgo_acriss_code ?? $metadata->sipp_code ?? null;

            $bookingTotalAmount = $metadata->recordgo_booking_total
                ?? $metadata->provider_vehicle_total
                ?? null;
            $finalCustomerTotalAmount = $metadata->provider_grand_total ?? $bookingTotalAmount;

            $missing = [];
            foreach ([
                'sellCode' => $sellCode,
                'sellCodeVer' => $sellCodeVer,
                'pickupBranch' => $pickupBranch,
                'dropoffBranch' => $dropoffBranch,
                'pickupDateTime' => $pickupDateTime,
                'dropoffDateTime' => $dropoffDateTime,
                'productId' => $productId,
                'productVer' => $productVer,
                'rateProdVer' => $rateProdVer,
                'acrissCode' => $acrissCode,
                'bookingTotalAmount' => $bookingTotalAmount,
                'finalCustomerTotalAmount' => $finalCustomerTotalAmount,
            ] as $field => $value) {
                if ($value === null || $value === '') {
                    $missing[] = $field;
                }
            }

            if (!empty($missing)) {
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : '')
                        . 'RecordGo booking_store missing fields: ' . implode(', ', $missing),
                ]);
                Log::error('RecordGo booking_store missing fields', [
                    'booking_id' => $booking->id,
                    'missing' => $missing,
                ]);
                return;
            }

            $extrasPayload = $this->resolveExtrasPayloadFromMetadata($metadata);
            $rawExtras = $extrasPayload['detailed_extras'] ?? [];

            $mapComplement = static function (array $raw) {
                $complementId = $raw['complementId'] ?? $raw['complement_id'] ?? $raw['id'] ?? null;
                $complementVer = $raw['complementVer'] ?? $raw['complementView'] ?? $raw['complement_ver'] ?? null;
                $rateComplVer = $raw['rateComplVer'] ?? $raw['rate_compl_ver'] ?? null;
                $taxApplied = $raw['taxApplied'] ?? $raw['tax_applied'] ?? 0;
                $units = $raw['complementUnits'] ?? $raw['units'] ?? $raw['complement_units'] ?? 1;
                $priceTotal = $raw['priceTaxIncComplementDiscount']
                    ?? $raw['priceTaxIncComplement']
                    ?? $raw['finalPriceTaxIncComplement']
                    ?? $raw['finalPriceTaxIncComplementDiscount']
                    ?? 0;

                return [
                    'complementId' => $complementId,
                    'complementView' => $complementVer,
                    'rateComplVer' => $rateComplVer,
                    'taxApplied' => $taxApplied,
                    'complementUnits' => $units,
                    'priceTaxIncComplement' => $priceTotal,
                    'finalPriceTaxIncComplement' => $priceTotal,
                    'priceTaxIncComplementDiscount' => $priceTotal,
                    'finalPriceTaxIncComplementDiscount' => $priceTotal,
                    'billToCustomer' => 1,
                ];
            };

            $associatedComplements = [];
            foreach ($rawExtras as $extra) {
                if (!is_array($extra)) {
                    continue;
                }
                $payload = $extra['recordgo_payload'] ?? null;
                if (!is_array($payload)) {
                    continue;
                }
                $associatedComplements[] = $mapComplement($payload);
            }

            $automaticComplements = [];
            $autoRaw = $metadata->recordgo_automatic_complements ?? [];
            if (is_array($autoRaw)) {
                foreach ($autoRaw as $raw) {
                    if (!is_array($raw)) {
                        continue;
                    }
                    $automaticComplements[] = $mapComplement($raw);
                }
            }

            $payload = [
                'partnerUser' => $this->recordGoService->getPartnerUser(),
                'country' => $country,
                'sellCode' => $sellCode,
                'sellCodeVer' => $sellCodeVer,
                'partnerBookingCode' => $booking->booking_number,
                'bookingDate' => now()->format('Y-m-d\TH:i:s'),
                'pickupBranch' => (int) $pickupBranch,
                'dropoffBranch' => (int) $dropoffBranch,
                'pickupDateTime' => $pickupDateTime,
                'dropoffDateTime' => $dropoffDateTime,
                'driverAge' => $metadata->customer_driver_age ? (int) $metadata->customer_driver_age : null,
                'travelInfoCode' => $metadata->flight_number ?? null,
                'customer' => [
                    'name' => $metadata->customer_name ?? 'Customer',
                    'email' => $metadata->customer_email ?? null,
                    'phone' => $metadata->customer_phone ?? null,
                ],
                'productId' => $productId,
                'productVer' => $productVer,
                'rateProdVer' => $rateProdVer,
                'acrissCode' => $acrissCode,
                'productAutomaticComplements' => $automaticComplements,
                'productAssociatedComplements' => $associatedComplements,
                'bookingTotalAmount' => (float) $bookingTotalAmount,
                'finalCustomerTotalAmount' => (float) $finalCustomerTotalAmount,
            ];

            $payload = array_filter($payload, static function ($value) {
                if ($value === null) {
                    return false;
                }
                if (is_string($value)) {
                    return trim($value) !== '';
                }
                if (is_array($value)) {
                    return !empty($value);
                }
                return true;
            });

            $response = $this->recordGoService->bookingStore($payload);
            if (!($response['ok'] ?? false)) {
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : '')
                        . 'RecordGo booking_store failed: ' . json_encode($response['errors'] ?? []),
                ]);
                Log::error('RecordGo booking_store failed', [
                    'booking_id' => $booking->id,
                    'errors' => $response['errors'] ?? null,
                ]);
                return;
            }

            $data = $response['data'] ?? [];
            $voucher = $data['numVoucher'] ?? $data['voucherNumber'] ?? null;
            if ($voucher) {
                $booking->update([
                    'provider_booking_ref' => $voucher,
                    'notes' => ($booking->notes ? $booking->notes . "\n" : '')
                        . 'RecordGo Voucher: ' . $voucher,
                ]);
            }

            Log::info('RecordGo booking_store success', [
                'booking_id' => $booking->id,
                'voucher' => $voucher,
            ]);
        } catch (\Exception $e) {
            Log::error('RecordGo booking_store exception', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $booking->update([
                'notes' => ($booking->notes ? $booking->notes . "\n" : '') . 'RecordGo booking_store error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Trigger reservation on Adobe API
     * 
     * IMPORTANT: Adobe API requires:
     * 1. Lowercase field names: startdate, enddate, pickupoffice, returnoffice
     * 2. The `items` array MUST be populated with protections/extras from GetCategoryWithFare
     * 3. Separate name fields: name, lastName, fullName
     */
    protected function triggerAdobeReservation($booking, $metadata)
    {
        Log::info('Adobe: Triggering reservation for booking', ['booking_id' => $booking->id]);

        try {
            $pickupOffice = $metadata->pickup_location_code ?? '';
            $returnOffice = $metadata->return_location_code ?? $pickupOffice;
            $startDate = $metadata->pickup_date . ' ' . $metadata->pickup_time;
            $endDate = ($metadata->dropoff_date ?? $metadata->return_date ?? '') . ' ' . ($metadata->dropoff_time ?? $metadata->return_time ?? '');
            
            // IMPORTANT: Use adobe_category (single-letter code like 'e', 'n') NOT vehicle_category (descriptive like 'Sedan')
            $category = $metadata->adobe_category ?? '';
            if (empty($category)) {
                // Fallback: try to extract from vehicle_id (format: adobe_OCO_e)
                $vehicleId = $metadata->vehicle_id ?? '';
                if (strpos($vehicleId, 'adobe_') === 0) {
                    $parts = explode('_', $vehicleId);
                    $category = $parts[2] ?? '';
                }
            }
            if (empty($category)) {
                $category = $metadata->sipp_code ?? $metadata->vehicle_category ?? '';
            }
            
            Log::info('Adobe: Using category for booking', ['category' => $category, 'vehicle_id' => $metadata->vehicle_id ?? '']);


            // Fetch protections and extras from Adobe API - THIS IS MANDATORY
            $categoryItems = $this->adobeCarService->getProtectionsAndExtras(
                $pickupOffice,
                $category,
                ['startdate' => $startDate, 'enddate' => $endDate]
            );

            // Build the items array for booking payload
            $bookingItems = [];
            $allItems = array_merge($categoryItems['protections'] ?? [], $categoryItems['extras'] ?? []);
            
            // Check for user-selected protections from metadata
            $selectedProtectionCodes = [];
            if (!empty($metadata->protection_code)) {
                $raw = $metadata->protection_code;
                // Try JSON decode first (array format from multi-select)
                $decoded = json_decode($raw, true);
                $codes = is_array($decoded) ? $decoded : explode(',', $raw);
                foreach ($codes as $code) {
                    $code = trim($code);
                    // Strip adobe_protection_ prefix if present
                    if (strpos($code, 'adobe_protection_') === 0) {
                        $code = substr($code, strlen('adobe_protection_'));
                    }
                    if ($code) {
                        $selectedProtectionCodes[] = $code;
                    }
                }
            }
            
            Log::info('Adobe: Selected protection codes', ['codes' => $selectedProtectionCodes]);
            
            // Parse extras from both 'extras' and 'extras_data' fields
            $extrasPayload = $this->resolveExtrasPayloadFromMetadata($metadata);
            $extrasData = $extrasPayload['detailed_extras'] ?? [];
            $extrasSimple = $extrasPayload['extras'] ?? [];
            
            // Merge both sources
            if (!empty($extrasSimple) && is_array($extrasSimple)) {
                $extrasData = array_merge($extrasData ?? [], $extrasSimple);
            }
            
            $selectedExtraCodes = [];
            if (!empty($extrasData) && is_array($extrasData)) {
                foreach ($extrasData as $extra) {
                    // Handle various key formats from frontend
                    $code = $extra['id'] ?? $extra['Code'] ?? $extra['code'] ?? $extra['extraCode'] ?? '';
                    // Strip adobe_ prefix if present
                    if (strpos($code, 'adobe_extra_') === 0) {
                        $code = substr($code, strlen('adobe_extra_'));
                    }
                    if ($code) {
                        $selectedExtraCodes[$code] = $extra['qty'] ?? $extra['Quantity'] ?? $extra['quantity'] ?? 1;
                    }
                }
            }
            
            Log::info('Adobe: Extracted extras from metadata', [
                'protection_codes' => $selectedProtectionCodes,
                'extra_codes' => $selectedExtraCodes,
                'raw_extras_data' => $extrasPayload['detailed_extras'] ?? 'null',
                'raw_extras' => $extrasPayload['extras'] ?? 'null'
            ]);


            // IMPORTANT: Only send items that should be INCLUDED in the booking
            // Adobe ignores quantity for items - only counts items where included=true
            foreach ($allItems as $item) {
                $code = $item['code'] ?? '';
                $isRequired = $item['required'] ?? false;
                $isProtection = ($item['type'] ?? '') === 'Proteccion';
                $isExtra = ($item['type'] ?? '') === 'Adicionales';

                // Determine if this item should be included
                $shouldInclude = false;
                $quantity = 0;
                
                if ($isRequired) {
                    $shouldInclude = true;
                    $quantity = 1;
                } elseif ($isProtection && in_array($code, $selectedProtectionCodes)) {
                    $shouldInclude = true;
                    $quantity = 1;
                } elseif ($isExtra && isset($selectedExtraCodes[$code])) {
                    $shouldInclude = true;
                    $quantity = $selectedExtraCodes[$code];
                }

                // Only add items that should be included (minimal items approach)
                if ($shouldInclude) {
                    $bookingItems[] = [
                        'code' => $code,
                        'quantity' => $quantity,
                        'total' => $item['total'] ?? 0,
                        'order' => $item['order'] ?? 0,
                        'type' => $item['type'] ?? '',
                        'included' => true, // MUST be true for Adobe to charge it
                        'description' => $item['description'] ?? '',
                        'information' => $item['information'] ?? '',
                        'name' => $item['name'] ?? '',
                        'required' => $isRequired
                    ];
                }
            }
            
            Log::info('Adobe: Built items array for booking (minimal)', [
                'items_count' => count($bookingItems),
                'items' => array_map(fn($i) => $i['code'] . ':qty=' . $i['quantity'], $bookingItems)
            ]);


            // Construct detailed comment
            $comment = "Website Booking (Post-Payment). ";
            if (isset($metadata->notes) && $metadata->notes) {
                $comment .= "Notes: " . $metadata->notes;
            }

            $nameParts = explode(' ', $metadata->customer_name ?? 'Guest User', 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '.';

            // Build payload with CORRECT Swagger schema field names
            $adobeParams = [
                'bookingNumber' => 0, // Required for new bookings
                'category' => $category,
                'startdate' => $startDate, // lowercase!
                'pickupoffice' => $pickupOffice, // lowercase!
                'enddate' => $endDate, // lowercase!
                'returnoffice' => $returnOffice, // lowercase!
                'customerCode' => $this->adobeCarService->getCustomerCode(),
                'name' => $firstName,
                'lastName' => $lastName,
                'fullName' => $metadata->customer_name ?? 'Guest User',
                'email' => $metadata->customer_email ?? '',
                'phone' => $metadata->customer_phone ?? '',
                'country' => 'CR',
                'language' => 'en',
                'customerComment' => $comment,
                'flightNumber' => $metadata->flight_number ?? '',
                'items' => $bookingItems // MANDATORY items array
            ];

            Log::info('Adobe: Sending reservation request', ['data' => $adobeParams]);
            $adobeResponse = $this->adobeCarService->createBooking($adobeParams);

            if (isset($adobeResponse['result']) && $adobeResponse['result'] && isset($adobeResponse['data']['bookingNumber'])) {
                $providerBookingRef = $adobeResponse['data']['bookingNumber'];
                Log::info('Adobe: Reservation successful', ['booking_ref' => $providerBookingRef]);

                $booking->update([
                    'provider_booking_ref' => $providerBookingRef,
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Adobe Conf: " . $providerBookingRef
                ]);
            } else {
                Log::error('Adobe: Reservation failed', ['response' => $adobeResponse]);
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Adobe Reservation failed: " . json_encode($adobeResponse)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Adobe: Exception during reservation trigger', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $booking->update([
                'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Adobe Reservation Error: " . $e->getMessage()
            ]);
        }
    }
    /**
     * Trigger reservation on Renteon API
     */
    protected function triggerRenteonReservation($booking, $metadata)
    {
        Log::info('Renteon: Triggering reservation for booking', ['booking_id' => $booking->id]);

        try {
            $nameParts = explode(' ', $metadata->customer_name ?? 'Guest User', 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? 'Guest';

            // Prepare Vehicle Data
            $vehicleData = [
                'sipp_code' => $metadata->sipp_code,
                'vehicle_id' => $metadata->vehicle_id,
                'provider_code' => $booking->provider_code ?? str_replace('renteon_', '', explode('_', $metadata->vehicle_id)[1] ?? ''), // Attempt to extract provider code from vehicle ID
                'pickup_location' => $metadata->pickup_location_code,
                'dropoff_location' => $metadata->return_location_code ?? $metadata->pickup_location_code,
                'pickup_date' => $metadata->pickup_date,
                'pickup_time' => $metadata->pickup_time,
                'dropoff_date' => $metadata->dropoff_date,
                'dropoff_time' => $metadata->dropoff_time,
                'connector_id' => $metadata->renteon_connector_id ?? null,
                'pickup_office_id' => $metadata->renteon_pickup_office_id ?? null,
                'dropoff_office_id' => $metadata->renteon_dropoff_office_id ?? null,
                'pricelist_id' => $metadata->renteon_pricelist_id ?? null,
                'price_date' => $metadata->renteon_price_date ?? null,
                'prepaid' => $metadata->renteon_prepaid ?? true,
            ];

            // Prepare Customer Data
            $customerData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $metadata->customer_email,
                'phone' => $metadata->customer_phone,
                'driver_age' => $metadata->customer_driver_age ?? 30,
                'flight_number' => $metadata->flight_number ?? '',
            ];

            // Prepare Booking Data
            $prepaidValue = $metadata->renteon_prepaid ?? false;
            $prepaid = filter_var($prepaidValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($prepaid === null) {
                $prepaid = (bool) $prepaidValue;
            }

            $bookingData = [
                'price' => $metadata->provider_grand_total ?? $metadata->total_amount_net ?? $metadata->payable_amount,
                'currency' => $metadata->provider_currency ?? $metadata->currency,
                'notes' => $metadata->notes ?? '',
                'payment_method' => 'pay_at_desk',
                'reference' => 'WEB-' . $booking->booking_number,
                'voucher_number' => 'WEB-' . $booking->booking_number,
                'prepaid' => $prepaid,
            ];

            // Extras
            $extras = [];
            // Parse extras similar to other providers if needed
            $extrasPayload = $this->resolveExtrasPayloadFromMetadata($metadata);
            if (!empty($extrasPayload['detailed_extras'])) {
                $extras = $extrasPayload['detailed_extras'];
            }

            $normalizedExtras = [];
            if (is_array($extras)) {
                foreach ($extras as $extra) {
                    if (($extra['qty'] ?? 0) <= 0) {
                        continue;
                    }
                    $serviceId = $extra['service_id'] ?? $extra['id'] ?? null;
                    if (!$serviceId && !empty($extra['code'])) {
                        $serviceId = preg_replace('/^renteon_extra_/', '', (string) $extra['code']);
                    }

                    $normalizedExtras[] = [
                        'id' => $serviceId,
                        'qty' => (int) ($extra['qty'] ?? 1),
                        'isSelected' => true,
                        'code' => $extra['code'] ?? null,
                    ];
                }
            }

            $bookingData['extras'] = $normalizedExtras;

            Log::info('Renteon: Sending reservation request', [
                'vehicle' => $vehicleData,
                'customer' => $customerData
            ]);

            $response = $this->renteonService->createBooking($vehicleData, $customerData, $bookingData);

            $extractConfirmation = static function ($payload) use (&$extractConfirmation) {
                if (!is_array($payload)) {
                    return null;
                }

                $candidates = [
                    $payload['Number'] ?? null,
                    $payload['ReservationNo'] ?? null,
                    $payload['ReservationNumber'] ?? null,
                    $payload['ReservationId'] ?? null,
                    $payload['ReservationID'] ?? null,
                    $payload['ConfirmationNumber'] ?? null,
                    $payload['ConfirmationNo'] ?? null,
                    $payload['BookingNumber'] ?? null,
                    $payload['BookingRef'] ?? null,
                    $payload['BookingReference'] ?? null,
                    $payload['Reference'] ?? null,
                    $payload['id'] ?? null,
                ];

                foreach ($candidates as $value) {
                    if ($value !== null && $value !== '') {
                        return $value;
                    }
                }

                foreach (['Data', 'data', 'Result', 'result'] as $key) {
                    if (!empty($payload[$key]) && is_array($payload[$key])) {
                        $value = $extractConfirmation($payload[$key]);
                        if ($value !== null) {
                            return $value;
                        }
                    }
                }

                return null;
            };

            $confNumber = $extractConfirmation($response);
            if ($confNumber === null && is_array($response)) {
                $confNumber = $extractConfirmation($response['_renteon_create'] ?? null)
                    ?? $extractConfirmation($response['_renteon_save'] ?? null);
            }

            if ($confNumber) {
                Log::info('Renteon: Reservation successful', ['conf' => $confNumber]);

                $booking->update([
                    'provider_booking_ref' => $confNumber,
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Renteon Conf: " . $confNumber
                ]);
            } else {
                Log::error('Renteon: Reservation failed', ['response' => $response]);
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Renteon Reservation failed: " . json_encode($response)
                ]);
            }


        } catch (\Exception $e) {
            Log::error('Renteon: Exception during reservation trigger', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $booking->update([
                'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Renteon Reservation Error: " . $e->getMessage()
            ]);
        }
    }

    /**
     * Trigger reservation on OK Mobility API
     */
    protected function triggerOkMobilityReservation($booking, $metadata)
    {
        Log::info('OK Mobility: Triggering reservation for booking', ['booking_id' => $booking->id]);

        try {
            $token = $metadata->ok_mobility_token ?? null;
            $groupId = $metadata->ok_mobility_group_id ?? null;
            $rateCode = trim((string) ($metadata->ok_mobility_rate_code ?? ''));

            if (!$token || !$groupId || $rateCode === '') {
                Log::error('OK Mobility: Missing token, group ID, or rate code in metadata', [
                    'booking_id' => $booking->id,
                    'token_present' => (bool) $token,
                    'group_id_present' => (bool) $groupId,
                    'rate_code_present' => $rateCode !== '',
                ]);
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "OK Mobility Reservation failed: Missing token, group ID, or rate code."
                ]);
                return;
            }

            $customerName = trim((string) ($metadata->customer_name ?? 'Guest User'));
            $nameParts = preg_split('/\s+/', $customerName, 2);
            $firstName = $nameParts[0] ?? 'Guest';
            $lastName = $nameParts[1] ?? 'Guest';

            $extras = [];
            $extrasPayload = $this->resolveExtrasPayloadFromMetadata($metadata);
            $extrasData = $extrasPayload['detailed_extras'] ?? [];
            if (is_array($extrasData)) {
                foreach ($extrasData as $extra) {
                    $code = $extra['code'] ?? $extra['id'] ?? null;
                    if (!$code) {
                        continue;
                    }
                    $extras[] = [
                        'id' => $code,
                        'quantity' => (int) ($extra['qty'] ?? 1),
                    ];
                }
            }

            $reservationData = [
                'reference' => 'WEB-' . $booking->booking_number,
                'group_code' => $groupId,
                'token' => $token,
                'rate_code' => $rateCode,
                'pickup_date' => $metadata->pickup_date,
                'pickup_time' => $metadata->pickup_time,
                'pickup_station_id' => $metadata->pickup_location_code,
                'dropoff_date' => $metadata->dropoff_date,
                'dropoff_time' => $metadata->dropoff_time,
                'dropoff_station_id' => $metadata->return_location_code ?? $metadata->pickup_location_code,
                'driver_name' => $firstName . ' ' . $lastName,
                'driver_surname' => $lastName,
                'driver_email' => $metadata->customer_email ?? '',
                'driver_phone' => $metadata->customer_phone ?? '',
                'driver_address' => $metadata->customer_address ?? '',
                'driver_city' => $metadata->customer_city ?? '',
                'driver_postal_code' => $metadata->customer_postal_code ?? '',
                'driver_country' => $metadata->customer_country ?? '',
                'driver_license_number' => $metadata->driver_license_number ?? '',
                'driver_age' => $metadata->customer_driver_age ?? 25,
                'extras' => $extras,
                'remarks' => $metadata->notes ?? null,
                'flight_number' => $metadata->flight_number ?? null,
            ];

            $okMobilityService = app(\App\Services\OkMobilityService::class);
            $xmlResponse = $okMobilityService->makeReservation($reservationData);

            if (!$xmlResponse) {
                Log::error('OK Mobility: Empty response from API');
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "OK Mobility Reservation failed: No response from API."
                ]);
                return;
            }

            libxml_use_internal_errors(true);
            $xmlObject = simplexml_load_string($xmlResponse);
            if ($xmlObject === false) {
                Log::error('OK Mobility: Failed to parse XML response', ['response' => $xmlResponse]);
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "OK Mobility Reservation failed: Invalid XML response."
                ]);
                libxml_clear_errors();
                return;
            }

            $bookingReferenceNodes = $xmlObject->xpath('//*[local-name()="Reservation_Nr"]');
            $statusNodes = $xmlObject->xpath('//*[local-name()="Status"]');
            $bookingReference = $bookingReferenceNodes ? (string) $bookingReferenceNodes[0] : null;
            $status = $statusNodes ? (string) $statusNodes[0] : 'pending';

            if ($bookingReference) {
                Log::info('OK Mobility: Reservation successful', ['conf' => $bookingReference]);
                $booking->update([
                    'provider_booking_ref' => $bookingReference,
                    'booking_status' => $status ?: $booking->booking_status,
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "OK Mobility Conf: " . $bookingReference
                ]);
            } else {
                Log::error('OK Mobility: Reservation failed, reference missing', ['response' => $xmlResponse]);
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "OK Mobility Reservation failed: Missing booking reference."
                ]);
            }

            libxml_clear_errors();
        } catch (\Exception $e) {
            Log::error('OK Mobility: Exception during reservation trigger', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $booking->update([
                'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "OK Mobility Reservation Error: " . $e->getMessage()
            ]);
        }
    }

    /**
     * Trigger reservation on Favrica API
     */
    protected function triggerFavricaReservation($booking, $metadata)
    {
        Log::info('Favrica: Triggering reservation for booking', ['booking_id' => $booking->id]);

        try {
            $rezId = $metadata->favrica_rez_id ?? null;
            $carsParkId = $metadata->favrica_cars_park_id ?? null;
            $groupId = $metadata->favrica_group_id ?? null;
            $pickupId = $metadata->pickup_location_code ?? null;
            $dropoffId = $metadata->return_location_code ?? $pickupId;

            if (!$rezId || !$carsParkId || !$groupId || !$pickupId) {
                Log::error('Favrica: Missing identifiers in metadata', [
                    'booking_id' => $booking->id,
                    'rez_id_present' => (bool) $rezId,
                    'cars_park_id_present' => (bool) $carsParkId,
                    'group_id_present' => (bool) $groupId,
                    'pickup_id_present' => (bool) $pickupId,
                ]);
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Favrica Reservation failed: Missing identifiers."
                ]);
                return;
            }

            $pickupParts = $this->splitDateTimeForFavrica($metadata->pickup_date ?? null, $metadata->pickup_time ?? null);
            $dropoffParts = $this->splitDateTimeForFavrica($metadata->dropoff_date ?? null, $metadata->dropoff_time ?? null);

            $customerName = trim((string) ($metadata->customer_name ?? 'Guest User'));
            $nameParts = preg_split('/\s+/', $customerName, 2);
            $firstName = $nameParts[0] ?? 'Guest';
            $lastName = $nameParts[1] ?? 'Guest';

            $currency = $this->normalizeFavricaRequestCurrency(
                $metadata->provider_currency ?? $metadata->currency ?? null
            );

            $rentPrice = $this->formatFavricaAmount(
                $metadata->provider_vehicle_total ?? $metadata->vehicle_total ?? $metadata->total_amount_net ?? 0
            );
            $extraPrice = $this->formatFavricaAmount(
                $metadata->provider_extras_total ?? $metadata->extras_total ?? 0
            );
            $dropPrice = $this->formatFavricaAmount($metadata->favrica_drop_fee ?? 0);

            $extrasPayload = $this->resolveExtrasPayloadFromMetadata($metadata);
            $flags = $this->resolveFavricaServiceFlags($extrasPayload['detailed_extras'] ?? []);

            $payload = [
                'Pickup_ID' => $pickupId,
                'Drop_Off_ID' => $dropoffId,
                'Pickup_Day' => $pickupParts['day'],
                'Pickup_Month' => $pickupParts['month'],
                'Pickup_Year' => $pickupParts['year'],
                'Pickup_Hour' => $pickupParts['hour'],
                'Pickup_Min' => $pickupParts['minute'],
                'Drop_Off_Day' => $dropoffParts['day'],
                'Drop_Off_Month' => $dropoffParts['month'],
                'Drop_Off_Year' => $dropoffParts['year'],
                'Drop_Off_Hour' => $dropoffParts['hour'],
                'Drop_Off_Min' => $dropoffParts['minute'],
                'Currency' => $currency,
                'Rez_ID' => $rezId,
                'Cars_Park_ID' => $carsParkId,
                'Group_ID' => $groupId,
                'Name' => $firstName,
                'SurName' => $lastName,
                'MobilePhone' => $metadata->customer_phone ?? '',
                'Mail_Adress' => $metadata->customer_email ?? '',
                'Rental_ID' => $metadata->driver_license_number ?? $booking->booking_number,
                'Adress' => $metadata->customer_address ?? '',
                'District' => $metadata->customer_city ?? '',
                'City' => $metadata->customer_city ?? '',
                'Country' => $metadata->customer_country ?? '',
                'Flight_Number' => $metadata->flight_number ?? '',
                'Baby_Seat' => $flags['Baby_Seat'] ? 'ON' : 'OFF',
                'Navigation' => $flags['Navigation'] ? 'ON' : 'OFF',
                'Additional_Driver' => $flags['Additional_Driver'] ? 'ON' : 'OFF',
                'CDW' => $flags['CDW'] ? 'ON' : 'OFF',
                'SCDW' => $flags['SCDW'] ? 'ON' : 'OFF',
                'LCF' => $flags['LCF'] ? 'ON' : 'OFF',
                'PAI' => $flags['PAI'] ? 'ON' : 'OFF',
                'Your_Rez_ID' => $booking->booking_number,
                'Your_Rent_Price' => $rentPrice,
                'Your_Extra_Price' => $extraPrice,
                'Your_Drop_Price' => $dropPrice,
                'Payment_Type' => 1,
            ];

            $favricaService = app(\App\Services\FavricaService::class);
            $response = $favricaService->createReservation($payload);

            if (empty($response) || !is_array($response)) {
                Log::error('Favrica: Empty reservation response', ['booking_id' => $booking->id]);
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Favrica Reservation failed: No response from API."
                ]);
                return;
            }

            $payloadResponse = $response[0] ?? null;
            if (!is_array($payloadResponse)) {
                Log::error('Favrica: Reservation response malformed', ['booking_id' => $booking->id, 'response' => $response]);
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Favrica Reservation failed: Invalid response format."
                ]);
                return;
            }

            $status = strtolower(trim((string) ($payloadResponse['success'] ?? $payloadResponse['Status'] ?? '')));
            $providerRef = $payloadResponse['rez_id'] ?? $payloadResponse['Rez_ID'] ?? null;
            $providerId = $payloadResponse['id'] ?? $payloadResponse['ID'] ?? null;

            if ($status === 'true' && $providerRef) {
                Log::info('Favrica: Reservation successful', ['conf' => $providerRef]);
                $note = "Favrica Conf: {$providerRef}";
                if ($providerId) {
                    $note .= " (ID: {$providerId})";
                }
                $booking->update([
                    'provider_booking_ref' => $providerRef,
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . $note,
                ]);
                return;
            }

            $errorMessage = $payloadResponse['error'] ?? 'Reservation failed';
            Log::error('Favrica: Reservation failed', ['error' => $errorMessage, 'response' => $payloadResponse]);
            $booking->update([
                'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Favrica Reservation failed: {$errorMessage}"
            ]);
        } catch (\Exception $e) {
            Log::error('Favrica: Exception during reservation trigger', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $booking->update([
                'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Favrica Reservation Error: " . $e->getMessage()
            ]);
        }
    }

    /**
     * Trigger reservation on XDrive API
     */
    protected function triggerXDriveReservation($booking, $metadata)
    {
        Log::info('XDrive: Triggering reservation for booking', ['booking_id' => $booking->id]);

        try {
            $rezId = $metadata->xdrive_rez_id ?? null;
            $carsParkId = $metadata->xdrive_cars_park_id ?? null;
            $groupId = $metadata->xdrive_group_id ?? null;
            $carWebId = $metadata->xdrive_car_web_id ?? null;
            $reservationSourceId = $metadata->xdrive_reservation_source_id ?? null;
            $pickupId = $metadata->pickup_location_code ?? null;
            $dropoffId = $metadata->return_location_code ?? $pickupId;

            if (!$rezId || !$carsParkId || !$groupId || !$carWebId || !$reservationSourceId || !$pickupId) {
                Log::error('XDrive: Missing identifiers in metadata', [
                    'booking_id' => $booking->id,
                    'rez_id_present' => (bool) $rezId,
                    'cars_park_id_present' => (bool) $carsParkId,
                    'group_id_present' => (bool) $groupId,
                    'car_web_id_present' => (bool) $carWebId,
                    'reservation_source_id_present' => (bool) $reservationSourceId,
                    'pickup_id_present' => (bool) $pickupId,
                ]);
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "XDrive Reservation failed: Missing identifiers."
                ]);
                return;
            }

            $pickupParts = $this->splitDateTimeForFavrica($metadata->pickup_date ?? null, $metadata->pickup_time ?? null);
            $dropoffParts = $this->splitDateTimeForFavrica($metadata->dropoff_date ?? null, $metadata->dropoff_time ?? null);

            $customerName = trim((string) ($metadata->customer_name ?? 'Guest User'));
            $nameParts = preg_split('/\s+/', $customerName, 2);
            $firstName = $nameParts[0] ?? 'Guest';
            $lastName = $nameParts[1] ?? 'Guest';

            $currency = $this->normalizeFavricaRequestCurrency(
                $metadata->provider_currency ?? $metadata->currency ?? null
            );

            $rentAmount = (float) ($metadata->provider_vehicle_total ?? $metadata->vehicle_total ?? $metadata->total_amount_net ?? 0);
            $extraAmount = (float) ($metadata->provider_extras_total ?? $metadata->extras_total ?? 0);
            $dropAmount = (float) ($metadata->xdrive_drop_fee ?? 0);
            $totalAmount = $rentAmount + $extraAmount + $dropAmount;

            $rentPrice = $this->formatFavricaAmount($rentAmount);
            $extraPrice = $this->formatFavricaAmount($extraAmount);
            $totalPrice = $this->formatFavricaAmount($totalAmount);

            $extrasPayload = $this->resolveExtrasPayloadFromMetadata($metadata);
            $flags = $this->resolveXDriveServiceFlags($extrasPayload['detailed_extras'] ?? []);

            $payload = [
                'Pickup_ID' => $pickupId,
                'Drop_Off_ID' => $dropoffId,
                'Pickup_Day' => $pickupParts['day'],
                'Pickup_Month' => $pickupParts['month'],
                'Pickup_Year' => $pickupParts['year'],
                'Pickup_Hour' => $pickupParts['hour'],
                'Pickup_Min' => $pickupParts['minute'],
                'Drop_Off_Day' => $dropoffParts['day'],
                'Drop_Off_Month' => $dropoffParts['month'],
                'Drop_Off_Year' => $dropoffParts['year'],
                'Drop_Off_Hour' => $dropoffParts['hour'],
                'Drop_Off_Min' => $dropoffParts['minute'],
                'Currency' => $currency,
                'Car_Web_ID' => $carWebId,
                'Rez_ID' => $rezId,
                'Cars_Park_ID' => $carsParkId,
                'Group_ID' => $groupId,
                'Reservation_Source_ID' => $reservationSourceId,
                'Name' => $firstName,
                'SurName' => $lastName,
                'Mail_Adress' => $metadata->customer_email ?? '',
                'MobilePhone' => $metadata->customer_phone ?? '',
                'Fly_No' => $metadata->flight_number ?? '',
                'Baby_Seat' => $flags['Baby_Seat'] ? 'ON' : 'OFF',
                'Navigation' => $flags['Navigation'] ? 'ON' : 'OFF',
                'Addition_Drive' => $flags['Addition_Drive'] ? 'ON' : 'OFF',
                'Your_Rent_Price' => $rentPrice,
                'Your_Extra_Price' => $extraPrice,
                'Your_Total_Price' => $totalPrice,
                'Payment_Type' => 1,
            ];

            $xdriveService = app(\App\Services\XDriveService::class);
            $response = $xdriveService->createReservation($payload);

            if (empty($response) || !is_array($response)) {
                Log::error('XDrive: Empty reservation response', ['booking_id' => $booking->id]);
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "XDrive Reservation failed: No response from API."
                ]);
                return;
            }

            $payloadResponse = $response[0] ?? null;
            if (!is_array($payloadResponse)) {
                Log::error('XDrive: Reservation response malformed', ['booking_id' => $booking->id, 'response' => $response]);
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "XDrive Reservation failed: Invalid response format."
                ]);
                return;
            }

            $status = strtolower(trim((string) ($payloadResponse['success'] ?? $payloadResponse['Status'] ?? '')));
            $providerRef = $payloadResponse['rez_id'] ?? $payloadResponse['Rez_ID'] ?? null;
            $providerId = $payloadResponse['id'] ?? $payloadResponse['ID'] ?? null;

            if ($status === 'true' && $providerRef) {
                Log::info('XDrive: Reservation successful', ['conf' => $providerRef]);
                $note = "XDrive Conf: {$providerRef}";
                if ($providerId) {
                    $note .= " (ID: {$providerId})";
                }
                $booking->update([
                    'provider_booking_ref' => $providerRef,
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . $note,
                ]);
                return;
            }

            $errorMessage = $payloadResponse['error'] ?? 'Reservation failed';
            Log::error('XDrive: Reservation failed', ['error' => $errorMessage, 'response' => $payloadResponse]);
            $booking->update([
                'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "XDrive Reservation failed: {$errorMessage}"
            ]);
        } catch (\Exception $e) {
            Log::error('XDrive: Exception during reservation trigger', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $booking->update([
                'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "XDrive Reservation Error: " . $e->getMessage()
            ]);
        }
    }

    private function splitDateTimeForFavrica(?string $date, ?string $time): array
    {
        $stamp = trim((string) $date . ' ' . (string) $time);
        try {
            $dt = \Carbon\Carbon::parse($stamp);
        } catch (\Exception $e) {
            $dt = \Carbon\Carbon::now();
        }

        return [
            'day' => $dt->format('d'),
            'month' => $dt->format('m'),
            'year' => $dt->format('Y'),
            'hour' => $dt->format('H'),
            'minute' => $dt->format('i'),
        ];
    }

    private function normalizeFavricaRequestCurrency(?string $currency): string
    {
        $value = strtoupper(trim((string) $currency));
        if ($value === '' || $value === 'EUR') {
            return 'EURO';
        }
        if ($value === 'TRY') {
            return 'TL';
        }
        return $value;
    }

    private function formatFavricaAmount($value): string
    {
        $numeric = (float) ($value ?? 0);
        return number_format($numeric, 2, '.', '');
    }

    private function resolveFavricaServiceFlags($extrasData): array
    {
        $flags = [
            'Baby_Seat' => false,
            'Navigation' => false,
            'Additional_Driver' => false,
            'CDW' => false,
            'SCDW' => false,
            'LCF' => false,
            'PAI' => false,
        ];

        if (empty($extrasData)) {
            return $flags;
        }

        $extras = is_array($extrasData) ? $extrasData : $this->decodeJsonArray($extrasData);

        foreach ($extras as $extra) {
            if (!is_array($extra)) {
                continue;
            }
            $qty = (int) ($extra['qty'] ?? $extra['quantity'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $code = strtoupper(trim((string) ($extra['code'] ?? $extra['service_id'] ?? $extra['id'] ?? '')));
            $name = strtolower(trim((string) ($extra['name'] ?? $extra['description'] ?? '')));

            if ($code === 'BABY_SEAT' || str_contains($name, 'baby')) {
                $flags['Baby_Seat'] = true;
            }
            if ($code === 'NAVIGATION' || str_contains($name, 'nav')) {
                $flags['Navigation'] = true;
            }
            if (in_array($code, ['ADDITION_DRIVE', 'ADDITIONAL_DRIVER'], true) || str_contains($name, 'driver')) {
                $flags['Additional_Driver'] = true;
            }
            if ($code === 'CDW') {
                $flags['CDW'] = true;
            }
            if ($code === 'SCDW') {
                $flags['SCDW'] = true;
            }
            if ($code === 'LCF') {
                $flags['LCF'] = true;
            }
            if ($code === 'PAI') {
                $flags['PAI'] = true;
            }
        }

        return $flags;
    }

    private function resolveXDriveServiceFlags($extrasData): array
    {
        $flags = [
            'Baby_Seat' => false,
            'Navigation' => false,
            'Addition_Drive' => false,
        ];

        if (empty($extrasData)) {
            return $flags;
        }

        $extras = is_array($extrasData) ? $extrasData : $this->decodeJsonArray($extrasData);

        foreach ($extras as $extra) {
            if (!is_array($extra)) {
                continue;
            }
            $qty = (int) ($extra['qty'] ?? $extra['quantity'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $code = strtoupper(trim((string) ($extra['code'] ?? $extra['service_id'] ?? $extra['id'] ?? '')));
            $name = strtolower(trim((string) ($extra['name'] ?? $extra['description'] ?? '')));

            if ($code === 'BABY_SEAT' || str_contains($name, 'baby')) {
                $flags['Baby_Seat'] = true;
            }
            if ($code === 'NAVIGATION' || str_contains($name, 'nav')) {
                $flags['Navigation'] = true;
            }
            if (in_array($code, ['ADDITION_DRIVE', 'ADDITIONAL_DRIVER'], true) || str_contains($name, 'driver')) {
                $flags['Addition_Drive'] = true;
            }
        }

        return $flags;
    }

    protected function triggerSurpriceReservation($booking, $metadata)
    {
        Log::info('Surprice: Triggering reservation for booking', ['booking_id' => $booking->id]);

        try {
            $vendorRateId = $metadata->surprice_vendor_rate_id ?? null;
            $rateCode = $metadata->surprice_rate_code ?? config('services.surprice.rate_code', 'Vrooem');
            $acrissCode = $metadata->sipp_code ?? null;
            $pickupCode = $metadata->pickup_location_code ?? null;
            $pickupExtCode = $metadata->surprice_extended_pickup_code ?? $pickupCode;
            $dropoffCode = $metadata->return_location_code ?? $pickupCode;
            $dropoffExtCode = $metadata->surprice_extended_dropoff_code ?? $pickupExtCode;

            $pickupDateTime = ($metadata->pickup_date ?? '') && ($metadata->pickup_time ?? '')
                ? $metadata->pickup_date . 'T' . $metadata->pickup_time . ':00'
                : null;
            $dropoffDateTime = ($metadata->dropoff_date ?? '') && ($metadata->dropoff_time ?? '')
                ? $metadata->dropoff_date . 'T' . $metadata->dropoff_time . ':00'
                : null;

            $missing = [];
            foreach ([
                'vendorRateId' => $vendorRateId,
                'acrissCode' => $acrissCode,
                'pickupCode' => $pickupCode,
                'pickupDateTime' => $pickupDateTime,
                'dropoffDateTime' => $dropoffDateTime,
            ] as $field => $value) {
                if ($value === null || $value === '') {
                    $missing[] = $field;
                }
            }

            if (!empty($missing)) {
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : '')
                        . 'Surprice reservation missing fields: ' . implode(', ', $missing),
                ]);
                Log::error('Surprice reservation missing fields', [
                    'booking_id' => $booking->id,
                    'missing' => $missing,
                ]);
                return;
            }

            $customerName = $metadata->customer_name ?? 'Guest';
            $customerEmail = $metadata->customer_email ?? '';
            $customerPhone = $metadata->customer_phone ?? '';

            $payload = array_filter([
                'pickUpDateTime' => $pickupDateTime,
                'returnDateTime' => $dropoffDateTime,
                'pickUpLocationCode' => $pickupCode,
                'pickUpExtendedLocationCode' => $pickupExtCode,
                'returnLocationCode' => $dropoffCode,
                'returnExtendedLocationCode' => $dropoffExtCode,
                'vehicleGroupPrefAccriss' => $acrissCode,
                'rateCode' => $rateCode,
                'vendorRateID' => $vendorRateId,
                'flightNo' => $metadata->flight_number ?? null,
                'notes' => $metadata->notes ?? null,
                'customerInfo' => [
                    'customer' => array_filter([
                        'name' => $customerName,
                        'email' => $customerEmail,
                        'phone' => $customerPhone,
                        'addressLine' => $metadata->customer_address ?? null,
                        'city' => $metadata->customer_city ?? null,
                        'country' => $metadata->customer_country ?? null,
                        'postalCode' => $metadata->customer_postal_code ?? null,
                    ], static fn($v) => $v !== null && $v !== ''),
                ],
            ], static fn($v) => $v !== null && $v !== '');

            // Add selected extras as equipment preferences
            $extrasPayload = $this->resolveExtrasPayloadFromMetadata($metadata);
            $selectedExtras = $extrasPayload['detailed_extras'] ?? [];
            $equipPrefs = [];
            foreach ($selectedExtras as $extra) {
                if (!is_array($extra)) {
                    continue;
                }
                $purpose = $extra['purpose'] ?? $extra['surprice_purpose'] ?? null;
                if ($purpose !== null && (int) $purpose > 0) {
                    $equipPrefs[] = [
                        'equipType' => (int) $purpose,
                        'quantity' => (int) ($extra['qty'] ?? $extra['quantity'] ?? 1),
                    ];
                }
            }
            if (!empty($equipPrefs)) {
                $payload['specialEquipmentPreferences'] = $equipPrefs;
            }

            Log::info('Surprice: Sending reservation request', [
                'booking_id' => $booking->id,
                'pickup' => $pickupCode,
                'acriss' => $acrissCode,
                'vendor_rate_id' => $vendorRateId,
            ]);

            $response = $this->surpriceService->createReservation($payload);

            if ($response && isset($response['orderInfo'])) {
                $orderInfo = $response['orderInfo'];
                $corporateOrderId = $orderInfo['corporateOrderId'] ?? $orderInfo['id'] ?? null;
                $brokerOrderId = $orderInfo['brokerOrderId'] ?? null;

                $booking->update([
                    'provider_booking_ref' => $corporateOrderId ?: ($brokerOrderId ?? null),
                    'provider_metadata' => array_merge(
                        $booking->provider_metadata ?? [],
                        [
                            'surprice_order_id' => $orderInfo['id'] ?? null,
                            'surprice_corporate_order_id' => $corporateOrderId,
                            'surprice_broker_order_id' => $brokerOrderId,
                            'surprice_status' => $orderInfo['status'] ?? 'committed',
                            'surprice_reservation_date' => $orderInfo['reservationDate'] ?? null,
                            'surprice_vehicle' => $response['vehicleInfo'] ?? null,
                            'surprice_total_charge' => $response['totalCharge'] ?? null,
                            'surprice_pickup_station' => $response['pickupStationInfo']['name'] ?? null,
                            'surprice_return_station' => $response['returnStationInfo']['name'] ?? null,
                            'surprice_pickup_instructions' => $response['pickupStationInfo']['additionalInfo']['text'] ?? null,
                            'surprice_pricedEquips' => $response['pricedEquips'] ?? [],
                            'surprice_pricedCoverages' => $response['pricedCoverages'] ?? [],
                            'surprice_rental_rate' => $response['rentalRate'] ?? null,
                        ]
                    ),
                ]);

                Log::info('Surprice: Reservation created successfully', [
                    'booking_id' => $booking->id,
                    'corporate_order_id' => $corporateOrderId,
                    'broker_order_id' => $brokerOrderId,
                    'status' => $orderInfo['status'] ?? null,
                ]);
            } else {
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : '')
                        . 'Surprice reservation API returned no orderInfo',
                    'provider_metadata' => array_merge(
                        $booking->provider_metadata ?? [],
                        ['surprice_error_response' => $response]
                    ),
                ]);
                Log::error('Surprice: Reservation response missing orderInfo', [
                    'booking_id' => $booking->id,
                    'response' => $response,
                ]);
            }
        } catch (\Exception $e) {
            $booking->update([
                'notes' => ($booking->notes ? $booking->notes . "\n" : '')
                    . 'Surprice reservation exception: ' . $e->getMessage(),
            ]);
            Log::error('Surprice: Reservation exception', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
