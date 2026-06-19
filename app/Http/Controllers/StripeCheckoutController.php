<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingHold;
use App\Models\Customer;
use App\Models\PayableSetting;
use App\Models\StripeCheckoutPayload;
use App\Models\Vehicle;
use App\Models\VehicleOperatingHour;
use App\Services\Bookings\LocationDetailsNormalizer;
use App\Services\Bookings\ProviderBookingContract;
use App\Services\CheckoutIdentityGuardService;
use App\Services\CurrencyConversionService;
use App\Services\OfferService;
use App\Services\PriceVerificationService;
use App\Services\StripeBookingService;
use App\Services\Trabber\TrabberAttributionService;
use App\Services\Vehicles\InternalVehicleAvailabilityService;
use App\Support\CurrencyRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

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

    private function bookingStatusRedirect(string $state, ?string $sessionId = null)
    {
        $params = ['locale' => app()->getLocale(), 'state' => $state];
        if ($sessionId) {
            $params['session_id'] = $sessionId;
        }

        return redirect()->route('booking.status', $params);
    }

    private function buildCheckoutAttemptHash(
        Request $request,
        array $validated,
        ?string $searchSessionId,
        float $payableAmount,
        string $currencyCode
    ): string {
        $vehicle = $validated['vehicle'] ?? [];
        $fingerprint = [
            'user_id' => $request->user()?->id,
            'customer_email' => strtolower((string) ($validated['customer']['email'] ?? '')),
            'search_session_id' => $searchSessionId,
            'vehicle_id' => $vehicle['id'] ?? $vehicle['gateway_vehicle_id'] ?? $vehicle['provider_vehicle_id'] ?? null,
            'vehicle_source' => $vehicle['source'] ?? null,
            'package' => $validated['package'] ?? null,
            'pickup_date' => $validated['pickup_date'] ?? null,
            'pickup_time' => $validated['pickup_time'] ?? null,
            'dropoff_date' => $validated['dropoff_date'] ?? null,
            'dropoff_time' => $validated['dropoff_time'] ?? null,
            'pickup_location' => $validated['pickup_location'] ?? null,
            'dropoff_location' => $validated['dropoff_location'] ?? null,
            'payable_amount' => number_format($payableAmount, 2, '.', ''),
            'currency' => strtoupper($currencyCode),
            'extras' => $this->normalizeCheckoutExtrasForFingerprint($validated['detailed_extras'] ?? []),
        ];

        return hash('sha256', json_encode($fingerprint, JSON_UNESCAPED_SLASHES));
    }

    private function normalizeCheckoutExtrasForFingerprint(array $extras): array
    {
        $normalized = [];
        foreach ($extras as $extra) {
            if (! is_array($extra)) {
                continue;
            }

            $id = $extra['id']
                ?? $extra['option_id']
                ?? $extra['optionID']
                ?? $extra['extra_id']
                ?? $extra['extraId']
                ?? $extra['code']
                ?? null;

            if ($id === null || trim((string) $id) === '') {
                continue;
            }

            $normalized[] = [
                'id' => trim((string) $id),
                'quantity' => max(1, (int) ($extra['qty'] ?? $extra['quantity'] ?? 1)),
            ];
        }

        usort($normalized, fn (array $a, array $b) => [$a['id'], $a['quantity']] <=> [$b['id'], $b['quantity']]);

        return $normalized;
    }

    private function reserveInternalVehicleForCheckout(Request $request, array $validated, ?string $searchSessionId): array
    {
        $vehicle = $validated['vehicle'] ?? [];
        if (($vehicle['source'] ?? null) !== 'internal' || empty($vehicle['id'])) {
            return ['success' => true, 'hold' => null];
        }

        $vehicleId = (int) $vehicle['id'];
        $window = [
            'pickup_date' => $validated['pickup_date'],
            'pickup_time' => $validated['pickup_time'],
            'dropoff_date' => $validated['dropoff_date'],
            'dropoff_time' => $validated['dropoff_time'],
        ];

        $existingHold = BookingHold::active()
            ->where('vehicle_id', $vehicleId)
            ->where('search_session_id', $searchSessionId)
            ->where('customer_email', strtolower((string) ($validated['customer']['email'] ?? '')))
            ->whereDate('pickup_date', $window['pickup_date'])
            ->where('pickup_time', $window['pickup_time'])
            ->whereDate('dropoff_date', $window['dropoff_date'])
            ->where('dropoff_time', $window['dropoff_time'])
            ->first();

        if ($existingHold) {
            return ['success' => true, 'hold' => $existingHold];
        }

        $lock = Cache::lock("booking-hold-vehicle-{$vehicleId}", 10);
        $lockAcquired = false;

        try {
            $lock->block(5);
            $lockAcquired = true;

            $vehicleModel = Vehicle::find($vehicleId);
            if (! $vehicleModel) {
                return [
                    'success' => false,
                    'error' => 'This vehicle is no longer available. Please refresh your search.',
                    'code' => 'VEHICLE_UNAVAILABLE',
                ];
            }

            $available = app(InternalVehicleAvailabilityService::class)->isVehicleAvailable($vehicleModel, $window);
            if (! $available) {
                return [
                    'success' => false,
                    'error' => 'This vehicle is no longer available for the selected dates. Please choose another vehicle.',
                    'code' => 'VEHICLE_UNAVAILABLE',
                ];
            }

            $hold = BookingHold::create([
                'vehicle_id' => $vehicleId,
                'user_id' => $request->user()?->id,
                'search_session_id' => $searchSessionId,
                'customer_email' => strtolower((string) ($validated['customer']['email'] ?? '')),
                'pickup_date' => $window['pickup_date'],
                'pickup_time' => $window['pickup_time'],
                'dropoff_date' => $window['dropoff_date'],
                'dropoff_time' => $window['dropoff_time'],
                'expires_at' => now()->addMinutes(15),
                'status' => 'active',
            ]);

            return ['success' => true, 'hold' => $hold];
        } catch (\Throwable $e) {
            Log::warning('Checkout hold could not be created', [
                'vehicle_id' => $vehicleId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'We could not reserve this vehicle in time. Please try again.',
                'code' => 'HOLD_UNAVAILABLE',
            ];
        } finally {
            if ($lockAcquired) {
                try {
                    $lock->release();
                } catch (\Throwable) {
                    // Lock may already be released by the cache driver.
                }
            }
        }
    }

    private function isExternalProviderSource(?string $source): bool
    {
        $normalized = strtolower(trim((string) $source));

        return $normalized !== '' && $normalized !== 'internal';
    }

    private function resolveProviderMarkupPercent(): float
    {
        $raw = config('services.pricing.provider_markup_percent');
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
        if (! $this->isExternalProviderSource($vehicleSource)) {
            return round($netAmount, 2);
        }

        $markupRate = $this->resolveProviderMarkupRate();
        if ($markupRate <= 0) {
            return round($netAmount, 2);
        }

        return round($netAmount * (1 + $markupRate), 2);
    }

    private function shouldApplyTrabberAttribution(?string $searchSessionId, array $validated): bool
    {
        $candidates = [
            $searchSessionId,
            $validated['search_session_id'] ?? null,
            $validated['quoteid'] ?? null,
            $validated['vehicle']['quoteid'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $normalized = strtolower(trim((string) $candidate));
            if ($normalized !== '' && str_starts_with($normalized, 'trabber_offer_')) {
                return true;
            }
        }

        return false;
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
                    return ! empty($value);
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
            'emr_rez_id',
            'emr_cars_park_id',
            'emr_group_id',
            'emr_car_web_id',
            'emr_reservation_source_id',
            'emr_drop_fee',
            'click2rent_car_id',
            'click2rent_package_id',
            'click2rent_hire_point_id',
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

    private function normalizeLocationCoordinate(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $numeric = (float) $value;

        return $numeric === 0.0 ? null : $numeric;
    }

    private function buildFallbackLocationDetailsFromVehicle(array $vehicle, string $leg = 'pickup'): ?array
    {
        $isDropoff = $leg === 'dropoff';
        $officeKey = $isDropoff ? 'dropoff_office' : 'pickup_office';
        $office = (! empty($vehicle[$officeKey]) && is_array($vehicle[$officeKey])) ? $vehicle[$officeKey] : [];

        // Gateway vehicles expose per-leg location data in two shapes:
        //   - legacy flat: $vehicle['pickup_location'] / $vehicle['dropoff_location'] (VehicleLocation)
        //   - canonical:   $vehicle['location']['pickup'] / $vehicle['location']['dropoff'] (SearchVehicleLocationPointPayload)
        // Prefer whichever contains coords over copying pickup values into the dropoff entry.
        $nestedKey = $isDropoff ? 'dropoff_location' : 'pickup_location';
        $legPoint = $isDropoff ? 'dropoff' : 'pickup';
        $nested = (! empty($vehicle[$nestedKey]) && is_array($vehicle[$nestedKey])) ? $vehicle[$nestedKey] : [];
        $canonical = (isset($vehicle['location'][$legPoint]) && is_array($vehicle['location'][$legPoint]))
            ? $vehicle['location'][$legPoint]
            : [];
        if (empty($nested)) {
            $nested = $canonical;
        }

        $stationName = $isDropoff
            ? ($vehicle['dropoff_station_name'] ?? null)
            : ($vehicle['pickup_station_name'] ?? null);
        $directAddress = $isDropoff
            ? ($vehicle['dropoff_address'] ?? null)
            : ($vehicle['pickup_address'] ?? null);

        $addressLine = $directAddress
            ?: ($office['address'] ?? null)
            ?: ($nested['address'] ?? null)
            ?: ($vehicle['office_address'] ?? null)
            ?: ($vehicle['full_vehicle_address'] ?? null);

        $name = $stationName
            ?: ($office['name'] ?? null)
            ?: ($nested['name'] ?? null)
            ?: ($vehicle['full_vehicle_address'] ?? null)
            ?: ($vehicle['pickup_location'] ?? null)
            ?: ($vehicle['location'] ?? null);

        // Only fall back to the pickup-level coords for the PICKUP leg. For the dropoff leg,
        // leave lat/lng null if the provider didn't give us a distinct dropoff â€” so the
        // BookingDetails map can correctly distinguish round-trip from one-way.
        if ($isDropoff) {
            $latitude = $this->normalizeLocationCoordinate($vehicle['dropoff_latitude'] ?? null)
                ?? $this->normalizeLocationCoordinate($nested['latitude'] ?? null)
                ?? $this->normalizeLocationCoordinate($office['latitude'] ?? null);
            $longitude = $this->normalizeLocationCoordinate($vehicle['dropoff_longitude'] ?? null)
                ?? $this->normalizeLocationCoordinate($nested['longitude'] ?? null)
                ?? $this->normalizeLocationCoordinate($office['longitude'] ?? null);
        } else {
            $latitude = $this->normalizeLocationCoordinate($vehicle['latitude'] ?? null)
                ?? $this->normalizeLocationCoordinate($nested['latitude'] ?? null)
                ?? $this->normalizeLocationCoordinate($office['latitude'] ?? null);
            $longitude = $this->normalizeLocationCoordinate($vehicle['longitude'] ?? null)
                ?? $this->normalizeLocationCoordinate($nested['longitude'] ?? null)
                ?? $this->normalizeLocationCoordinate($office['longitude'] ?? null);
        }

        $details = array_filter([
            'name' => $name,
            'address_1' => $addressLine,
            'address_city' => $office['town'] ?? ($nested['city'] ?? null),
            'address_postcode' => $office['postal_code'] ?? null,
            'telephone' => $office['phone'] ?? ($vehicle['office_phone'] ?? null),
            'email' => $office['email'] ?? null,
            'collection_details' => $isDropoff
                ? ($office['dropoff_instructions'] ?? null)
                : ($office['pickup_instructions'] ?? null),
            'latitude' => $latitude,
            'longitude' => $longitude,
        ], static fn ($value) => $value !== null && $value !== '');

        return ! empty($details) ? $details : null;
    }

    private function resolveCheckoutLocationDetails(array $validated): array
    {
        $vehicle = $validated['vehicle'] ?? [];
        $providerSource = strtolower((string) ($vehicle['source'] ?? ''));
        $isOneWayRental = $this->isOneWayRentalRequest($validated);

        $pickupLocationDetails = $validated['location_details'] ?? ($vehicle['location_details'] ?? null);
        $dropoffLocationDetails = $validated['dropoff_location_details'] ?? ($vehicle['dropoff_location_details'] ?? null);

        // Preserve existing Renteon-specific fallback behavior first.
        if ($providerSource === 'renteon') {
            if (empty($pickupLocationDetails) && ! empty($vehicle['pickup_office']) && is_array($vehicle['pickup_office'])) {
                $pickupLocationDetails = $vehicle['pickup_office'];
            }
            if (empty($dropoffLocationDetails) && ! empty($vehicle['dropoff_office']) && is_array($vehicle['dropoff_office'])) {
                $dropoffLocationDetails = $vehicle['dropoff_office'];
            }
        }

        // Generic fallback for providers that only expose address fields on the vehicle payload.
        if (empty($pickupLocationDetails)) {
            $pickupLocationDetails = $this->buildFallbackLocationDetailsFromVehicle($vehicle, 'pickup');
        }
        if (empty($dropoffLocationDetails)) {
            $dropoffLocationDetails = $this->buildFallbackLocationDetailsFromVehicle($vehicle, 'dropoff');
        }
        if (! $isOneWayRental && empty($dropoffLocationDetails) && ! empty($pickupLocationDetails)) {
            $dropoffLocationDetails = $pickupLocationDetails;
        }

        $pickupLocationDetails = $this->enrichLocationDetailsFromVehicle($pickupLocationDetails, $vehicle, 'pickup');
        $dropoffLocationDetails = $this->enrichLocationDetailsFromVehicle($dropoffLocationDetails, $vehicle, 'dropoff');

        // Normalize to the canonical shape before persistence so every downstream
        // reader (admin, vendor, customer, PDF, notifications) sees the same keys.
        $pickupLocationDetails = LocationDetailsNormalizer::normalize($pickupLocationDetails);
        $dropoffLocationDetails = LocationDetailsNormalizer::normalize($dropoffLocationDetails);

        if (! $isOneWayRental && ! empty($pickupLocationDetails) && ! LocationDetailsNormalizer::isDistinctLocation($pickupLocationDetails, $dropoffLocationDetails)) {
            $dropoffLocationDetails = $pickupLocationDetails;
        } elseif ($isOneWayRental && $this->locationDetailsLookSame($pickupLocationDetails, $dropoffLocationDetails)) {
            $dropoffLocationDetails = null;
        }

        return [$pickupLocationDetails, $dropoffLocationDetails];
    }

    private function isOneWayRentalRequest(array $validated): bool
    {
        $pickup = $this->normalizeLocationText($validated['pickup_location'] ?? null);
        $dropoff = $this->normalizeLocationText($validated['dropoff_location'] ?? null);

        return $pickup !== '' && $dropoff !== '' && $pickup !== $dropoff;
    }

    private function locationDetailsLookSame(?array $pickup, ?array $dropoff): bool
    {
        if (empty($pickup) || empty($dropoff)) {
            return false;
        }

        $pickupLat = $pickup['latitude'] ?? null;
        $pickupLng = $pickup['longitude'] ?? null;
        $dropoffLat = $dropoff['latitude'] ?? null;
        $dropoffLng = $dropoff['longitude'] ?? null;
        if (is_numeric($pickupLat) && is_numeric($pickupLng) && is_numeric($dropoffLat) && is_numeric($dropoffLng)) {
            return abs((float) $pickupLat - (float) $dropoffLat) <= 0.0001
                && abs((float) $pickupLng - (float) $dropoffLng) <= 0.0001;
        }

        $pickupAddress = $this->normalizeLocationText($pickup['address_1'] ?? null);
        $dropoffAddress = $this->normalizeLocationText($dropoff['address_1'] ?? null);
        if ($pickupAddress !== '' && $dropoffAddress !== '' && $pickupAddress === $dropoffAddress) {
            return true;
        }

        $pickupName = $this->normalizeLocationText($pickup['name'] ?? null);
        $dropoffName = $this->normalizeLocationText($dropoff['name'] ?? null);

        return $pickupName !== '' && $dropoffName !== '' && $pickupName === $dropoffName;
    }

    private function normalizeLocationText(mixed $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', (string) ($value ?? '')) ?? ''));
    }

    private function enrichLocationDetailsFromVehicle(mixed $details, array $vehicle, string $leg = 'pickup'): mixed
    {
        if (! is_array($details)) {
            return $details;
        }

        $fallback = $this->buildFallbackLocationDetailsFromVehicle($vehicle, $leg) ?? [];
        if (empty($fallback)) {
            return $details;
        }

        foreach (['name', 'address_1', 'address_city', 'address_postcode', 'telephone', 'email', 'collection_details', 'latitude', 'longitude'] as $field) {
            if ((! isset($details[$field]) || $details[$field] === '' || $details[$field] === null) && array_key_exists($field, $fallback)) {
                $details[$field] = $fallback[$field];
            }
        }

        return $details;
    }

    /**
     * Create a Stripe Checkout Session
     */
    public function createSession(Request $request)
    {
        // Mobile clients authenticate via Sanctum bearer tokens; the route is
        // not behind auth middleware so we must resolve the user manually.
        if (strtolower((string) $request->header('X-Client')) === 'mobile' && ! $request->user()) {
            $sanctumUser = auth('sanctum')->user();
            if ($sanctumUser) {
                auth()->setUser($sanctumUser);
                $request->setUserResolver(fn () => $sanctumUser);
            }
        }

        // Only regular users can make bookings
        $user = auth()->user();
        if ($user && ! in_array($user->role, ['user', 'customer', null], true)) {
            return response()->json([
                'error' => 'Only customer accounts can make bookings. Please use a regular account.',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'vehicle' => 'required|array',
                'package' => 'required|string',
                'extras' => 'nullable|array',
                'customer' => 'required|array',
                'pickup_date' => 'required|date_format:Y-m-d',
                'pickup_time' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
                'dropoff_date' => 'required|date_format:Y-m-d|after_or_equal:pickup_date',
                'dropoff_time' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
                'pickup_location' => 'required|string',
                'dropoff_location' => 'nullable|string',
                'total_amount' => 'required|numeric',
                'currency' => 'required|string',
                'number_of_days' => 'required|integer|min:1',
                'detailed_extras' => 'nullable|array',
                'optional_extras' => 'nullable|array',
                'protection_code' => 'nullable',
                'protection_amount' => 'nullable|numeric',
                'quoteid' => 'nullable|string',
                'rentalCode' => 'nullable|string',
                'vehicle_total' => 'nullable|numeric',
                'payment_method' => 'nullable|string',
                'selected_deposit_type' => 'nullable|string',
                'location_details' => 'nullable|array',
                'dropoff_location_details' => 'nullable|array',
                'location_instructions' => 'nullable|string',
                'driver_requirements' => 'nullable|array',
                'terms' => 'nullable|array',
                'gateway_search_id' => 'nullable|string',
            ]);

            $start = \Carbon\Carbon::parse("{$validated['pickup_date']} {$validated['pickup_time']}");
            $end = \Carbon\Carbon::parse("{$validated['dropoff_date']} {$validated['dropoff_time']}");
            if ($start->lessThanOrEqualTo(now())) {
                return response()->json([
                    'error' => 'Pickup time must be in the future.',
                    'errors' => ['pickup_date' => ['Pickup time must be in the future.']],
                ], 422);
            }

            if ($end->lessThanOrEqualTo($start)) {
                return response()->json([
                    'error' => 'Drop-off time must be after pickup time.',
                    'errors' => ['dropoff_time' => ['Drop-off must be after pickup.']],
                ], 422);
            }

            $validated['number_of_days'] = max(1, (int) ceil($start->diffInMinutes($end) / 1440));

            $vehicle = $validated['vehicle'] ?? [];
            $providerSource = strtolower((string) ($vehicle['source'] ?? ''));

            $identityConflict = $this->resolveCheckoutIdentityConflict(
                $request,
                $validated['customer'] ?? []
            );

            if ($identityConflict) {
                return response()->json($identityConflict, 409);
            }

            // Validate operating hours for internal vehicles
            if ($providerSource === 'internal' && ! empty($vehicle['id'])) {
                $internalVehicle = Vehicle::with('operatingHours')->find($vehicle['id']);
                if ($internalVehicle && $internalVehicle->operatingHours->isNotEmpty()) {
                    $pickupDay = \Carbon\Carbon::parse($validated['pickup_date'])->dayOfWeekIso - 1;
                    $returnDay = \Carbon\Carbon::parse($validated['dropoff_date'])->dayOfWeekIso - 1;

                    if (! $internalVehicle->isAvailableAt($pickupDay, $validated['pickup_time'])) {
                        $hours = $internalVehicle->getOperatingHoursForDay($pickupDay);
                        $dayName = VehicleOperatingHour::DAY_NAMES[$pickupDay] ?? 'this day';
                        $msg = $hours && $hours->is_open
                            ? "Pickup is not available on {$dayName} at {$validated['pickup_time']}. Operating hours: {$hours->open_time}â€“{$hours->close_time}."
                            : "This vehicle is not available for pickup on {$dayName}s.";

                        return response()->json(['error' => $msg], 422);
                    }

                    if (! $internalVehicle->isAvailableAt($returnDay, $validated['dropoff_time'])) {
                        $hours = $internalVehicle->getOperatingHoursForDay($returnDay);
                        $dayName = VehicleOperatingHour::DAY_NAMES[$returnDay] ?? 'this day';
                        $msg = $hours && $hours->is_open
                            ? "Return is not available on {$dayName} at {$validated['dropoff_time']}. Operating hours: {$hours->open_time}â€“{$hours->close_time}."
                            : "This vehicle is not available for return on {$dayName}s.";

                        return response()->json(['error' => $msg], 422);
                    }
                }
            }

            if ($providerSource === 'sicily_by_car') {
                $vehicleId = $vehicle['provider_vehicle_id'] ?? null;
                $rateId = $vehicle['rate_id'] ?? null;
                $pickupId = $vehicle['provider_pickup_id'] ?? null;

                if ((! $vehicleId || ! $rateId || ! $pickupId) && ! empty($vehicle['id'])) {
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

            $vehicle = $validated['vehicle'] ?? [];
            [$pickupLocationDetails, $dropoffLocationDetails] = $this->resolveCheckoutLocationDetails($validated);

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

                if (! empty($missing)) {
                    return response()->json([
                        'error' => 'Please complete the required driver details before checkout.',
                        'missing_fields' => $missing,
                    ], 422);
                }
            }

            // Get payment percentage from settings
            $payableSetting = PayableSetting::first();
            $paymentPercentage = $payableSetting ? $payableSetting->payment_percentage : 15;

            // SECURITY: Verify prices against server-side stored values
            $searchSessionId = $request->input('search_session_id') ?? $request->header('X-Search-Session');
            if (! $searchSessionId) {
                // Try to get from session
                $searchSessionId = session()->get('search_session_id');
            }

            $priceVerificationService = app(PriceVerificationService::class);
            $verifiedGatewayVehicleContext = [];

            if ($searchSessionId) {
                $verification = $priceVerificationService->verifyPrices($searchSessionId, $vehicle);

                if (! $verification['valid']) {
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
                if (isset($verifiedPrices['products']) && ! empty($verifiedPrices['products'])) {
                    $vehicle['products'] = $verifiedPrices['products'];
                }
                if (isset($verifiedPrices['price_per_day'])) {
                    $vehicle['price_per_day'] = $verifiedPrices['price_per_day'];
                }
                if (isset($verifiedPrices['extras']) && is_array($verifiedPrices['extras'])) {
                    $vehicle['extras'] = $verifiedPrices['extras'];
                    if (in_array($providerSource, ['greenmotion', 'usave'], true)) {
                        $vehicle['options'] = $verifiedPrices['extras'];
                        $vehicle['insurance_options'] = [];
                        $vehicle['optional_extras'] = [];
                    }
                }
                $vehicle = $this->mergeVerifiedVehicleContext($vehicle, $verifiedPrices['vehicle_context'] ?? null);
                if (is_array($verifiedPrices['gateway_vehicle_context'] ?? null)) {
                    $verifiedGatewayVehicleContext = $verifiedPrices['gateway_vehicle_context'];
                }
                if (empty($validated['gateway_search_id']) && ! empty($vehicle['gateway_search_id'])) {
                    $validated['gateway_search_id'] = $vehicle['gateway_search_id'];
                }

                $extrasVerification = $priceVerificationService->verifyAndResolveExtras(
                    $validated['detailed_extras'] ?? [],
                    $verifiedPrices,
                    $validated['package'] ?? null
                );

                if (! $extrasVerification['valid']) {
                    Log::warning('Extras verification failed during checkout', [
                        'error' => $extrasVerification['error'] ?? 'unknown',
                        'vehicle_id' => $vehicle['id'] ?? $vehicle['provider_vehicle_id'] ?? null,
                        'search_session' => $searchSessionId,
                        'ip' => $request->ip(),
                    ]);

                    return response()->json([
                        'error' => $extrasVerification['error'] ?? 'Extras verification failed. Please refresh search results and try again.',
                    ], 422);
                }

                $validated['detailed_extras'] = $extrasVerification['extras'] ?? [];
                $validated['optional_extras'] = [];

                $validated['vehicle'] = $vehicle;
            } else {
                Log::warning('Checkout attempted without search_session_id', [
                    'vehicle_id' => $vehicle['id'] ?? $vehicle['provider_vehicle_id'] ?? null,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'error' => 'Pricing context expired. Please refresh your search and try again.',
                    'code' => 'MISSING_SEARCH_SESSION',
                ], 422);
            }

            [$pickupLocationDetails, $dropoffLocationDetails] = $this->resolveCheckoutLocationDetails($validated);

            if ($providerSource === 'recordgo') {
                $vehicle = $validated['vehicle'] ?? [];
                $vehicle['recordgo_selected_product'] = $this->resolveRecordGoSelectedProduct(
                    $vehicle,
                    $validated['package'] ?? null
                );
                $validated['vehicle'] = $vehicle;
            }

            $providerContract = app(ProviderBookingContract::class);
            $contractValidation = $providerContract->validateCheckout($validated);
            if (! ($contractValidation['valid'] ?? false)) {
                Log::error('Provider checkout blocked: missing reservation context', [
                    'provider_source' => $providerSource,
                    'missing_fields' => $contractValidation['missing_fields'] ?? [],
                    'vehicle_id' => $validated['vehicle']['id'] ?? null,
                    'gateway_vehicle_id' => $providerContract->gatewayVehicleId($validated),
                    'gateway_search_id' => $validated['gateway_search_id'] ?? null,
                ]);

                return response()->json([
                    'error' => $contractValidation['message'] ?? 'This supplier quote is missing reservation details. Please refresh search results and try again.',
                    'code' => $contractValidation['code'] ?? 'PROVIDER_BOOKING_CONTEXT_MISSING',
                    'missing_fields' => $contractValidation['missing_fields'] ?? [],
                ], 422);
            }

            // Normalize currency (symbols to ISO codes)
            // display_currency = user's preferred currency (what they see on screen)
            // currency = original source currency (what the vehicle price was stored in)
            $displayCurrency = $request->input('display_currency');
            $currency = $displayCurrency ?? ($validated['currency'] ?? 'EUR');
            $currencyCode = $this->normalizeCurrencyCode($currency);
            $providerCurrency = $this->resolveProviderCurrency($validated, $currencyCode);

            $computedTotals = $this->computeServerTotals($validated, $currencyCode, $providerCurrency, $paymentPercentage);

            if (! $computedTotals['success']) {
                return response()->json([
                    'error' => $computedTotals['error'] ?? 'Unable to validate pricing.',
                ], 422);
            }

            $totalAmount = $computedTotals['booking_total'];
            $payableAmount = $computedTotals['booking_deposit'];
            $pendingAmount = $computedTotals['booking_pending'];

            $offerService = app(OfferService::class);
            $resolvedOffers = $offerService->resolveAppliedOffers([
                'placement' => 'checkout',
            ]);
            $offerDiscountAmount = 0;
            $bookingTotalDisplay = $totalAmount; // default: same as actual
            $monetaryOfferId = null;
            $offerRate = 0;
            $appliedOffers = $resolvedOffers['applied_offers'] ?? [];
            $freeEsimIncluded = (bool) ($resolvedOffers['free_esim_included'] ?? false);

            if (! empty($resolvedOffers['monetary_offer'])) {
                $monetaryOfferId = $resolvedOffers['monetary_offer']['id'] ?? null;
                $offerRate = (float) ($resolvedOffers['price_discount_rate'] ?? 0);
                $offerPricing = $offerService->computePricing($totalAmount, $resolvedOffers);
                $bookingTotalDisplay = $offerPricing['display_total'];
                $offerDiscountAmount = $offerPricing['discount_amount'];
                $appliedOffers = collect($appliedOffers)
                    ->map(function (array $offer) use ($offerDiscountAmount, $monetaryOfferId) {
                        if (($offer['id'] ?? null) === $monetaryOfferId && ($offer['effect_type'] ?? null) === 'price_discount_percentage') {
                            $offer['discount_amount'] = $offerDiscountAmount;
                        }

                        return $offer;
                    })
                    ->values()
                    ->all();
            }

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

            $holdResult = $this->reserveInternalVehicleForCheckout($request, $validated, $searchSessionId ?? null);
            if (! ($holdResult['success'] ?? false)) {
                return response()->json([
                    'error' => $holdResult['error'] ?? 'Vehicle is no longer available.',
                    'code' => $holdResult['code'] ?? 'VEHICLE_UNAVAILABLE',
                ], 422);
            }
            $bookingHold = $holdResult['hold'] ?? null;

            $extrasPayloadId = null;

            // Extract vehicle benefits/policies for storage in provider_metadata
            $vehicleBenefits = $this->resolveVehicleBenefitsForCheckout($validated['vehicle'] ?? []);
            if (is_object($vehicleBenefits)) {
                $vehicleBenefits = (array) $vehicleBenefits;
            }

            $vehicleSource = $validated['vehicle']['source'] ?? 'greenmotion';

            // Resolve vehicle image early so it's available for fullMetadata and Stripe
            $vehicleImage = null;
            $checkoutVehicleImages = $this->resolveVehicleImagesForCheckout($validated['vehicle'] ?? []);
            if (($validated['vehicle']['source'] ?? null) === 'internal' && ! empty($checkoutVehicleImages)) {
                foreach ($checkoutVehicleImages as $img) {
                    if (isset($img['image_type']) && $img['image_type'] === 'primary') {
                        $vehicleImage = $img['image_url'] ?? null;
                        break;
                    }
                }
                if (! $vehicleImage) {
                    foreach ($checkoutVehicleImages as $img) {
                        if (isset($img['image_type']) && $img['image_type'] === 'gallery') {
                            $vehicleImage = $img['image_url'] ?? null;
                            break;
                        }
                    }
                }
            } else {
                $vehicleImage = $validated['vehicle']['image'] ?? null;
            }

            $selectedVehicleContext = $this->resolveSelectedVehicleContext(
                $validated['vehicle'] ?? [],
                $validated['package'] ?? null
            );
            $gatewayVehicleContext = $this->prepareGatewayVehicleContextForReservation(
                $verifiedGatewayVehicleContext,
                $validated['vehicle'] ?? [],
                $selectedVehicleContext,
                $validated['gateway_search_id'] ?? null
            );
            $surpriceContext = $this->resolveSurpriceReservationContext(
                $validated['vehicle'] ?? [],
                $validated['package'] ?? null
            );
            $vehicleProviderPayload = $this->resolveVehicleLegacyPayload($validated['vehicle'] ?? []);
            $trabberAttribution = $this->shouldApplyTrabberAttribution($searchSessionId ?? null, $validated)
                ? app(TrabberAttributionService::class)->fromRequest($request)
                : [];

            // Build the FULL metadata (no Stripe key limit here â€” stored in our DB)
            $fullMetadata = [
                // Core
                'user_id' => $request->user()?->id,
                'vehicle_id' => $validated['vehicle']['id'] ?? '',
                'booking_hold_id' => $bookingHold?->id,
                'gateway_vehicle_id' => $selectedVehicleContext['gateway_vehicle_id'] ?? ($validated['vehicle']['gateway_vehicle_id'] ?? null),
                'gateway_search_id' => $validated['gateway_search_id'] ?? null,
                'vehicle_source' => $vehicleSource,
                'vehicle_brand' => $validated['vehicle']['brand'] ?? '',
                'vehicle_model' => $validated['vehicle']['model'] ?? '',
                'vehicle_image' => $vehicleImage,
                'vehicle_category' => $validated['vehicle']['category'] ?? $validated['vehicle']['vehicle_category'] ?? '',
                'vehicle_class' => $validated['vehicle']['class'] ?? '',
                'adobe_category' => $validated['vehicle']['adobe_category'] ?? '',
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
                'deposit_amount' => $selectedVehicleContext['deposit_amount'] ?? null,
                'excess_amount' => $selectedVehicleContext['excess_amount'] ?? null,
                'excess_theft_amount' => $selectedVehicleContext['excess_theft_amount'] ?? null,
                'deposit_currency' => $selectedVehicleContext['deposit_currency'] ?? $providerCurrency,
                'exchange_rate_provider_to_booking' => $this->calculateExchangeRate($providerCurrency, $currencyCode, $computedTotals['provider_grand_total'] ?? null),
                'exchange_rate_booking_to_admin' => $this->calculateExchangeRate($currencyCode, config('currency.base_currency', 'EUR'), $totalAmount),
                'customer_name' => $validated['customer']['name'] ?? '',
                'customer_email' => $validated['customer']['email'] ?? '',
                'customer_phone' => $validated['customer']['phone'] ?? '',
                'customer_driver_age' => $validated['customer']['driver_age'] ?? '',
                'customer_address' => $validated['customer']['address'] ?? null,
                'customer_city' => $validated['customer']['city'] ?? null,
                'customer_postal_code' => $validated['customer']['postal_code'] ?? null,
                'customer_country' => $validated['customer']['country'] ?? null,
                'flight_number' => $validated['customer']['flight_number'] ?? '',
                'protection_code' => is_array($validated['protection_code'] ?? null) ? json_encode($validated['protection_code']) : ($validated['protection_code'] ?? ''),
                'protection_amount' => $validated['protection_amount'] ?? 0,
                'sipp_code' => $validated['vehicle']['sipp_code'] ?? '',
                'pickup_location_code' => $validated['vehicle']['provider_pickup_id'] ?? '',
                'return_location_code' => $validated['vehicle']['provider_return_id'] ?? $validated['vehicle']['provider_pickup_id'] ?? '',
                'extras' => json_encode($validated['extras'] ?? []),
                'quoteid' => ! empty($validated['quoteid']) ? $validated['quoteid'] : ($validated['vehicle']['quoteid'] ?? ''),
                'rental_code' => $validated['package'] ?? ($validated['vehicle']['rentalCode'] ?? ''),
                'vehicle_total' => $computedTotals['booking_vehicle_total'],
                'extras_total' => $computedTotals['booking_options_total'],
                'payment_method' => $validated['payment_method'] ?? 'card',
                'driver_license_number' => $validated['customer']['driver_license_number'] ?? null,
                'notes' => $validated['customer']['notes'] ?? null,
                // Provider-specific
                'renteon_connector_id' => $selectedVehicleContext['connector_id'] ?? null,
                'renteon_pickup_office_id' => $selectedVehicleContext['provider_pickup_office_id'] ?? null,
                'renteon_dropoff_office_id' => $selectedVehicleContext['provider_dropoff_office_id'] ?? null,
                'renteon_pricelist_id' => $selectedVehicleContext['pricelist_id'] ?? null,
                'renteon_price_date' => $selectedVehicleContext['price_date'] ?? null,
                'renteon_prepaid' => $selectedVehicleContext['prepaid']
                    ?? (($validated['vehicle']['source'] ?? '') === 'renteon' ? false : true),
                'sbc_vehicle_id' => $validated['vehicle']['provider_vehicle_id'] ?? null,
                'sbc_rate_id' => $validated['vehicle']['rate_id'] ?? ($vehicleProviderPayload['rate_id'] ?? null),
                'sbc_availability_id' => $validated['vehicle']['availability_id'] ?? ($vehicleProviderPayload['availability_id'] ?? null),
                'sbc_payment_type' => $validated['vehicle']['payment_type'] ?? ($vehicleProviderPayload['rate_payment'] ?? null),
                'sbc_currency' => $validated['vehicle']['currency'] ?? ($vehicleProviderPayload['currency'] ?? null),
                'sbc_request_id' => $vehicleProviderPayload['request_id'] ?? null,
                'sbc_vehicle_category_id' => $validated['vehicle']['vehicle_category_id'] ?? ($vehicleProviderPayload['vehicle_category_id'] ?? null),
                'sbc_pickup_location_id' => $validated['vehicle']['provider_pickup_id']
                    ?? ($vehicleProviderPayload['pickup_location_id'] ?? null),
                'sbc_dropoff_location_id' => $validated['vehicle']['provider_return_id']
                    ?? $validated['vehicle']['provider_dropoff_id']
                    ?? ($vehicleProviderPayload['dropoff_location_id'] ?? null)
                    ?? ($validated['vehicle']['provider_pickup_id'] ?? null),
                'recordgo_country' => $validated['vehicle']['recordgo_country'] ?? ($validated['country'] ?? null),
                'recordgo_sell_code' => $this->resolveRecordGoSellCode($validated['country'] ?? null),
                'recordgo_sellcode_ver' => $validated['vehicle']['recordgo_sellcode_ver'] ?? null,
                'recordgo_acriss_code' => $validated['vehicle']['sipp_code'] ?? null,
                'recordgo_product_id' => $validated['vehicle']['recordgo_selected_product']['product_id'] ?? null,
                'recordgo_product_ver' => $validated['vehicle']['recordgo_selected_product']['product_ver'] ?? null,
                'recordgo_rate_prod_ver' => $validated['vehicle']['recordgo_selected_product']['rate_prod_ver'] ?? null,
                'recordgo_product_name' => $validated['vehicle']['recordgo_selected_product']['name'] ?? null,
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
                'emr_rez_id' => $validated['vehicle']['emr_rez_id'] ?? null,
                'emr_cars_park_id' => $validated['vehicle']['emr_cars_park_id'] ?? null,
                'emr_group_id' => $validated['vehicle']['emr_group_id'] ?? null,
                'emr_car_web_id' => $validated['vehicle']['emr_car_web_id'] ?? null,
                'emr_reservation_source_id' => $validated['vehicle']['emr_reservation_source_id'] ?? null,
                'emr_drop_fee' => $validated['vehicle']['emr_drop_fee'] ?? null,
                'click2rent_car_id' => $validated['vehicle']['click2rent_car_id'] ?? null,
                'click2rent_package_id' => $validated['vehicle']['click2rent_package_id'] ?? null,
                'click2rent_hire_point_id' => $validated['vehicle']['click2rent_hire_point_id'] ?? null,
                'surprice_vendor_rate_id' => $surpriceContext['vendor_rate_id'] ?? null,
                'surprice_rate_code' => $surpriceContext['rate_code'] ?? null,
                'surprice_extended_pickup_code' => $surpriceContext['extended_pickup_code'] ?? null,
                'surprice_extended_dropoff_code' => $surpriceContext['extended_dropoff_code'] ?? null,
                // Cancellation policy
                'cancellation_deadline' => $validated['vehicle']['cancellation']['deadline'] ?? null,
                'cancellation_free' => $validated['vehicle']['cancellation']['available'] ?? null,
                'cancellation_fee' => $validated['vehicle']['cancellation']['amount'] ?? null,
                'cancellation_fee_currency' => $validated['vehicle']['cancellation']['currency'] ?? null,
                // Promo pricing
                'applied_offers' => $appliedOffers,
                'free_esim_included' => $freeEsimIncluded,
                'offer_id' => $monetaryOfferId,
                'offer_rate' => $offerRate > 0 ? (string) $offerRate : null,
                'offer_discount_amount' => $offerDiscountAmount > 0 ? (string) $offerDiscountAmount : null,
                'promo_id' => $monetaryOfferId,
                'promo_rate' => $offerRate > 0 ? (string) $offerRate : null,
                'promo_discount_amount' => $offerDiscountAmount > 0 ? (string) $offerDiscountAmount : null,
                'booking_total_display' => $bookingTotalDisplay != $totalAmount ? (string) $bookingTotalDisplay : null,
                // Affiliate tracking
                'affiliate_business_id' => session('affiliate_data.business_id'),
                'affiliate_scan_id' => session('affiliate_data.customer_scan_id'),
                // Trabber attribution
                'partner_source' => $trabberAttribution['partner_source'] ?? null,
                'trabber_clickid' => $trabberAttribution['trabber_clickid'] ?? null,
                'trabber_offer_id' => $trabberAttribution['trabber_offer_id'] ?? null,
                'trabber_commission_rate' => $trabberAttribution['trabber_commission_rate'] ?? null,
                'trabber_clicked_at' => $trabberAttribution['trabber_clicked_at'] ?? null,
            ];

            $extrasPayload = [
                'detailed_extras' => $validated['detailed_extras'] ?? [],
                'extras' => $validated['extras'] ?? [],
                'vehicle_source' => $vehicleSource,
                'vehicle_id' => $validated['vehicle']['id'] ?? null,
                'booking_hold_id' => $bookingHold?->id,
                'provider_currency' => $providerCurrency,
                'booking_currency' => $currencyCode,
                'location_details' => $pickupLocationDetails,
                'dropoff_location_details' => $dropoffLocationDetails,
                'location_instructions' => $validated['location_instructions'] ?? null,
                'driver_requirements' => $validated['driver_requirements'] ?? null,
                'terms' => $validated['terms'] ?? null,
                'applied_offers' => $appliedOffers,
                'free_esim_included' => $freeEsimIncluded,
                // Vehicle benefits, policies, and deposit/excess info
                'vehicle_benefits' => $vehicleBenefits,
                'security_deposit' => $this->resolveVehicleSecurityDepositForCheckout($validated['vehicle'] ?? []),
                'deposit_payment_method' => $this->resolveVehicleDepositPaymentMethodForCheckout($validated['vehicle'] ?? []),
                'selected_deposit_type' => $validated['selected_deposit_type'] ?? null,
                'fuel_type' => $validated['vehicle']['fuel_type'] ?? $validated['vehicle']['fuel'] ?? ($validated['vehicle']['specs']['fuel'] ?? null),
                'mileage' => $validated['vehicle']['mileage'] ?? ($validated['vehicle']['policies']['mileage_limit_km'] ?? null),
                'transmission' => $validated['vehicle']['transmission'] ?? ($validated['vehicle']['specs']['transmission'] ?? null),
                'gateway_vehicle_context' => $gatewayVehicleContext ?: null,
                // Store full metadata here â€” StripeBookingService merges this back
                'full_metadata' => array_filter($fullMetadata, fn ($v) => $v !== null && $v !== ''),
            ];

            // Always save payload since it now contains vehicle benefits
            $payloadHasContent = true;
            $checkoutAttemptHash = $this->buildCheckoutAttemptHash($request, $validated, $searchSessionId ?? null, $payableAmount, $currencyCode);
            $checkoutAttemptCacheKey = "stripe_checkout_attempt:{$checkoutAttemptHash}";

            if ($payloadHasContent) {
                $attemptLock = Cache::lock("stripe_checkout_attempt_lock:{$checkoutAttemptHash}", 15);
                $lockAcquired = false;
                try {
                    $attemptLock->block(5);
                    $lockAcquired = true;
                    $cachedPayloadId = Cache::get($checkoutAttemptCacheKey);
                    $payloadRecord = $cachedPayloadId ? StripeCheckoutPayload::find($cachedPayloadId) : null;

                    if ($payloadRecord && $payloadRecord->stripe_session_id) {
                        try {
                            $existingSession = StripeSession::retrieve($payloadRecord->stripe_session_id);
                            if (($existingSession->status ?? null) === 'open' && ! empty($existingSession->url)) {
                                return response()->json([
                                    'success' => true,
                                    'session_id' => $existingSession->id,
                                    'url' => $existingSession->url,
                                ]);
                            }
                        } catch (\Exception $e) {
                            Log::warning('Stripe Checkout: Existing session lookup failed, creating a new session', [
                                'payload_id' => $payloadRecord->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    if (! $payloadRecord) {
                        $payloadRecord = StripeCheckoutPayload::create([
                            'payload' => $extrasPayload,
                        ]);
                        Cache::put($checkoutAttemptCacheKey, $payloadRecord->id, now()->addMinutes(30));
                    }

                    $extrasPayloadId = $payloadRecord->id;
                } catch (\Throwable $e) {
                    Log::warning('Stripe Checkout: Attempt lock failed', [
                        'attempt_hash' => $checkoutAttemptHash,
                        'error' => $e->getMessage(),
                    ]);

                    return response()->json([
                        'error' => 'Checkout is already being prepared. Please wait a moment and try again.',
                        'code' => 'checkout_in_progress',
                    ], 409);
                } finally {
                    try {
                        if ($lockAcquired) {
                            $attemptLock?->release();
                        }
                    } catch (\Throwable) {
                        // Lock may already be released by the cache driver.
                    }
                }
            }

            $vehicleCheckoutName = $this->resolveVehicleCheckoutName($validated['vehicle']);

            // Build line items for Stripe
            $lineItems = [
                [
                    'price_data' => [
                        'currency' => strtolower($currencyCode),
                        'product_data' => [
                            'name' => $vehicleCheckoutName,
                            'description' => $validated['package'].' Package - '.$validated['number_of_days'].' day(s)',
                            'images' => $vehicleImage ? [$vehicleImage] : [],
                        ],
                        'unit_amount' => $this->toStripeMinorUnits($payableAmount, $currencyCode),
                    ],
                    'quantity' => 1,
                ],
            ];

            // Prepare LEAN metadata for Stripe webhook (50 key limit)
            // All provider-specific keys are stored in extras_payload.full_metadata
            $metadata = [
                'user_id' => $request->user()?->id,
                'search_session_id' => $searchSessionId ?? null,
                'vehicle_id' => $validated['vehicle']['id'] ?? '',
                'booking_hold_id' => $bookingHold?->id,
                'vehicle_source' => $validated['vehicle']['source'] ?? 'greenmotion',
                'vehicle_brand' => $validated['vehicle']['brand'] ?? '',
                'vehicle_model' => $validated['vehicle']['model'] ?? '',
                'vehicle_image' => $vehicleImage ?? '',
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
                'provider_grand_total' => $computedTotals['provider_grand_total'] ?? null,
                'provider_vehicle_total' => $computedTotals['provider_vehicle_total'] ?? null,
                'provider_extras_total' => $computedTotals['provider_options_total'] ?? null,
                'customer_name' => $validated['customer']['name'] ?? '',
                'customer_email' => $validated['customer']['email'] ?? '',
                'customer_phone' => $validated['customer']['phone'] ?? '',
                'customer_driver_age' => $validated['customer']['driver_age'] ?? '',
                'payment_method' => $validated['payment_method'] ?? 'card',
                'extras_payload_id' => $extrasPayloadId ? (string) $extrasPayloadId : null,
                'offer_id' => $monetaryOfferId,
                'offer_rate' => $offerRate > 0 ? (string) $offerRate : null,
                'offer_discount_amount' => $offerDiscountAmount > 0 ? (string) $offerDiscountAmount : null,
                'free_esim_included' => $freeEsimIncluded ? '1' : null,
                'promo_id' => $monetaryOfferId,
                'promo_rate' => $offerRate > 0 ? (string) $offerRate : null,
                'promo_discount_amount' => $offerDiscountAmount > 0 ? (string) $offerDiscountAmount : null,
                'booking_total_display' => $bookingTotalDisplay != $totalAmount ? (string) $bookingTotalDisplay : null,
                'affiliate_business_id' => session('affiliate_data.business_id'),
                'affiliate_scan_id' => session('affiliate_data.customer_scan_id'),
                'awc' => $request->cookie('awc'),
                'partner_source' => $trabberAttribution['partner_source'] ?? null,
                'trabber_clickid' => $trabberAttribution['trabber_clickid'] ?? null,
                'trabber_offer_id' => $trabberAttribution['trabber_offer_id'] ?? null,
            ];

            $metadata = $this->compactStripeMetadata($metadata);

            // Create Stripe Checkout Session
            $currentLocale = app()->getLocale();
            $supportedLocales = ['en', 'fr', 'nl', 'es', 'ar'];
            if (! in_array($currentLocale, $supportedLocales)) {
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

            $isMobileClient = strtolower((string) $request->header('X-Client')) === 'mobile';
            $successUrl = $isMobileClient
                ? 'vrooem://booking-confirmed?session_id={CHECKOUT_SESSION_ID}'
                : route('booking.success', ['locale' => $currentLocale]).'?session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = $isMobileClient
                ? 'vrooem://booking-cancel'
                : route('booking.status', ['locale' => $currentLocale, 'state' => 'payment_cancelled']);

            $idempotencyKey = 'co_'.$checkoutAttemptHash;

            $session = StripeSession::create([
                'payment_method_types' => $paymentMethodTypes,
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'customer_email' => $validated['customer']['email'] ?? null,
                'metadata' => $metadata,
                'payment_intent_data' => [
                    'metadata' => $metadata,
                ],
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);

            if ($extrasPayloadId) {
                try {
                    StripeCheckoutPayload::whereKey($extrasPayloadId)->update([
                        'stripe_session_id' => $session->id,
                    ]);
                    if ($bookingHold) {
                        $bookingHold->update(['stripe_session_id' => $session->id]);
                    }
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
                'vehicle' => $vehicleCheckoutName,
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
                'error' => 'Checkout could not be started. Please try again.',
            ], 500);
        }
    }

    private function buildProviderMetadata(array $validated): array
    {
        $vehicle = $validated['vehicle'] ?? [];
        $package = $validated['package'] ?? null;
        $product = $this->resolveSelectedProduct($vehicle, $package);

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
                'deposit_currency' => $product['deposit_currency'] ?? null,
                'excess' => $product['excess'] ?? null,
                'excess_theft_amount' => $product['excess_theft_amount'] ?? null,
                'mileage' => $product['mileage'] ?? null,
                'costperextradistance' => $product['costperextradistance'] ?? null,
                'fuelpolicy' => $product['fuelpolicy'] ?? null,
                'minage' => $product['minage'] ?? null,
                'gateway_vehicle_id' => $product['gateway_vehicle_id'] ?? null,
                'connector_id' => $product['connector_id'] ?? null,
                'provider_pickup_office_id' => $product['provider_pickup_office_id'] ?? null,
                'provider_dropoff_office_id' => $product['provider_dropoff_office_id'] ?? null,
                'pricelist_id' => $product['pricelist_id'] ?? null,
                'pricelist_code' => $product['pricelist_code'] ?? null,
                'price_date' => $product['price_date'] ?? null,
                'prepaid' => $product['prepaid'] ?? null,
            ],
            'extras_selected' => $validated['detailed_extras'] ?? [],
        ];
    }

    private function resolveSelectedProduct(array $vehicle, ?string $package): ?array
    {
        if (empty($vehicle['products']) || ! is_array($vehicle['products'])) {
            return null;
        }

        foreach ($vehicle['products'] as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            if (($entry['type'] ?? null) === $package) {
                return $entry;
            }
        }

        return collect($vehicle['products'])->first(fn ($entry) => is_array($entry)) ?: null;
    }

    private function resolveRecordGoSelectedProduct(array $vehicle, ?string $package): ?array
    {
        $products = $vehicle['recordgo_products'] ?? [];
        if (! is_array($products) || $products === []) {
            return null;
        }

        foreach ($products as $product) {
            if (is_array($product) && $package !== null && ($product['type'] ?? null) === $package) {
                return $product;
            }
        }

        return collect($products)->first(fn ($product) => is_array($product)) ?: null;
    }

    private function mergeVerifiedVehicleContext(array $vehicle, mixed $context): array
    {
        if (! is_array($context) || $context === []) {
            return $vehicle;
        }

        foreach ($context as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $vehicle[$key] = $value;
        }

        return $vehicle;
    }

    private function prepareGatewayVehicleContextForReservation(
        mixed $context,
        array $vehicle,
        array $selectedVehicleContext,
        ?string $gatewaySearchId
    ): array {
        if (! is_array($context) || $context === []) {
            return [];
        }

        $gatewayVehicleId = $this->firstFilled(
            $selectedVehicleContext['gateway_vehicle_id'] ?? null,
            $context['id'] ?? null,
            $context['gateway_vehicle_id'] ?? null,
            $vehicle['gateway_vehicle_id'] ?? null,
            $vehicle['id'] ?? null
        );
        if (! $gatewayVehicleId) {
            return [];
        }

        $context['id'] = $gatewayVehicleId;
        $context['gateway_vehicle_id'] = $gatewayVehicleId;

        $resolvedGatewaySearchId = $this->firstFilled(
            $gatewaySearchId,
            $context['search_id'] ?? null,
            $context['gateway_search_id'] ?? null,
            $vehicle['gateway_search_id'] ?? null
        );
        if ($resolvedGatewaySearchId) {
            $context['search_id'] = $resolvedGatewaySearchId;
            $context['gateway_search_id'] = $resolvedGatewaySearchId;
        }

        $context['supplier_id'] = $this->firstFilled($context['supplier_id'] ?? null, $vehicle['source'] ?? null);
        $context['supplier_vehicle_id'] = $this->firstFilled(
            $context['supplier_vehicle_id'] ?? null,
            $vehicle['supplier_vehicle_id'] ?? null,
            $vehicle['provider_vehicle_id'] ?? null,
            $gatewayVehicleId
        );

        $supplierData = is_array($context['supplier_data'] ?? null) ? $context['supplier_data'] : [];
        foreach ([
            'connector_id' => 'connector_id',
            'provider_pickup_office_id' => 'pickup_office_id',
            'provider_dropoff_office_id' => 'dropoff_office_id',
            'pricelist_id' => 'pricelist_id',
            'price_date' => 'price_date',
            'prepaid' => 'prepaid',
        ] as $from => $to) {
            if (array_key_exists($from, $selectedVehicleContext)
                && $selectedVehicleContext[$from] !== null
                && $selectedVehicleContext[$from] !== ''
            ) {
                $supplierData[$to] = $selectedVehicleContext[$from];
            }
        }
        $context['supplier_data'] = $supplierData;

        if (empty($context['context_valid_until'])) {
            $context['context_valid_until'] = now()->addHours(2)->toIso8601String();
        }

        return $this->removeEmptyContextValues($context);
    }

    private function removeEmptyContextValues(array $values): array
    {
        $clean = [];
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $value = $this->removeEmptyContextValues($value);
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    private function resolveVehicleCheckoutName(array $vehicle): string
    {
        $brandModel = trim(implode(' ', array_filter([
            trim((string) ($vehicle['brand'] ?? '')),
            trim((string) ($vehicle['model'] ?? '')),
        ])));

        foreach ([
            $brandModel,
            $vehicle['display_name'] ?? null,
            $vehicle['name'] ?? null,
            $vehicle['provider_vehicle_id'] ?? null,
            $vehicle['sipp_code'] ?? null,
            $vehicle['id'] ?? null,
        ] as $candidate) {
            $candidate = trim((string) $candidate);

            if ($candidate !== '') {
                return $candidate;
            }
        }

        return 'Rental vehicle';
    }

    private function resolveSurpriceReservationContext(array $vehicle, ?string $package = null): array
    {
        $supplierData = is_array($vehicle['supplier_data'] ?? null) ? $vehicle['supplier_data'] : [];
        $useFdw = $package === 'FDW';
        $pickupId = $this->firstFilled($vehicle['provider_pickup_id'] ?? null, $supplierData['pickup_code'] ?? null);
        $dropoffId = $this->firstFilled(
            $vehicle['provider_return_id'] ?? null,
            $vehicle['provider_dropoff_id'] ?? null,
            $supplierData['dropoff_code'] ?? null,
            $pickupId
        );

        return [
            'vendor_rate_id' => $useFdw
                ? $this->firstFilled($supplierData['fdw_vendor_rate_id'] ?? null)
                : $this->firstFilled(
                    $vehicle['surprice_vendor_rate_id'] ?? null,
                    $vehicle['provider_rate_id'] ?? null,
                    $supplierData['vendor_rate_id'] ?? null
                ),
            'rate_code' => $useFdw
                ? $this->firstFilled($supplierData['fdw_rate_code'] ?? null)
                : $this->firstFilled(
                    $vehicle['surprice_rate_code'] ?? null,
                    $supplierData['rate_code'] ?? null
                ),
            'extended_pickup_code' => $this->firstFilled(
                $vehicle['surprice_extended_pickup_code'] ?? null,
                $supplierData['pickup_ext_code'] ?? null,
                $this->extendedSurpriceLocationCode($pickupId)
            ),
            'extended_dropoff_code' => $this->firstFilled(
                $vehicle['surprice_extended_dropoff_code'] ?? null,
                $supplierData['dropoff_ext_code'] ?? null,
                $this->extendedSurpriceLocationCode($dropoffId),
                $this->extendedSurpriceLocationCode($pickupId)
            ),
        ];
    }

    private function extendedSurpriceLocationCode(?string $providerLocationId): ?string
    {
        if (! $providerLocationId || ! str_contains($providerLocationId, ':')) {
            return null;
        }

        return $this->firstFilled(explode(':', $providerLocationId, 2)[1] ?? null);
    }

    private function firstFilled(...$values): ?string
    {
        foreach ($values as $value) {
            if ($value === null) {
                continue;
            }

            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function resolveSelectedVehicleContext(array $vehicle, ?string $package): array
    {
        $selectedProduct = $this->resolveSelectedProduct($vehicle, $package);
        $benefits = $this->resolveVehicleBenefitsForCheckout($vehicle);
        $defaultCurrency = $benefits['deposit_currency']
            ?? ($vehicle['pricing']['deposit_currency'] ?? null)
            ?? ($vehicle['pricing']['currency'] ?? null)
            ?? ($vehicle['currency'] ?? null);

        $context = [
            'gateway_vehicle_id' => $vehicle['gateway_vehicle_id'] ?? null,
            'connector_id' => $vehicle['connector_id'] ?? null,
            'provider_pickup_office_id' => $vehicle['provider_pickup_office_id'] ?? null,
            'provider_dropoff_office_id' => $vehicle['provider_dropoff_office_id'] ?? null,
            'pricelist_id' => $vehicle['pricelist_id'] ?? null,
            'price_date' => $vehicle['price_date'] ?? null,
            'prepaid' => $vehicle['prepaid'] ?? (($vehicle['source'] ?? '') === 'renteon' ? false : true),
            'deposit_amount' => $benefits['deposit_amount']
                ?? ($vehicle['pricing']['deposit_amount'] ?? null)
                ?? ($vehicle['security_deposit'] ?? $this->resolveVehicleSecurityDepositForCheckout($vehicle)),
            'deposit_currency' => $defaultCurrency,
            'excess_amount' => $benefits['excess_amount'] ?? ($vehicle['pricing']['excess_amount'] ?? null),
            'excess_theft_amount' => $benefits['excess_theft_amount'] ?? ($vehicle['pricing']['excess_theft_amount'] ?? null),
        ];

        if (($vehicle['source'] ?? null) === 'surprice' && $package === 'FDW') {
            $supplierData = is_array($vehicle['supplier_data'] ?? null) ? $vehicle['supplier_data'] : [];

            return [
                ...$context,
                'deposit_amount' => $supplierData['fdw_deposit_amount'] ?? $context['deposit_amount'],
                'excess_amount' => $supplierData['fdw_excess_amount'] ?? 0,
                'excess_theft_amount' => $supplierData['fdw_excess_amount'] ?? 0,
            ];
        }

        if (($vehicle['source'] ?? null) !== 'renteon' || ! $selectedProduct) {
            return $context;
        }

        return [
            'gateway_vehicle_id' => $selectedProduct['gateway_vehicle_id'] ?? $context['gateway_vehicle_id'],
            'connector_id' => $selectedProduct['connector_id'] ?? $context['connector_id'],
            'provider_pickup_office_id' => $selectedProduct['provider_pickup_office_id'] ?? $context['provider_pickup_office_id'],
            'provider_dropoff_office_id' => $selectedProduct['provider_dropoff_office_id'] ?? $context['provider_dropoff_office_id'],
            'pricelist_id' => $selectedProduct['pricelist_id'] ?? $context['pricelist_id'],
            'price_date' => $selectedProduct['price_date'] ?? $context['price_date'],
            'prepaid' => $selectedProduct['prepaid'] ?? $context['prepaid'],
            'deposit_amount' => $selectedProduct['deposit'] ?? $context['deposit_amount'],
            'deposit_currency' => $selectedProduct['deposit_currency'] ?? $context['deposit_currency'],
            'excess_amount' => array_key_exists('excess', $selectedProduct) ? $selectedProduct['excess'] : $context['excess_amount'],
            'excess_theft_amount' => $selectedProduct['excess_theft_amount'] ?? $context['excess_theft_amount'],
        ];
    }

    private function resolveProviderCurrency(array $validated, string $fallback): string
    {
        $vehicle = $validated['vehicle'] ?? [];
        $providerCurrency = $vehicle['currency'] ?? ($vehicle['pricing']['currency'] ?? null);

        if (! $providerCurrency && ! empty($vehicle['products']) && is_array($vehicle['products'])) {
            $package = $validated['package'] ?? null;
            foreach ($vehicle['products'] as $product) {
                if (! is_array($product)) {
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

            if (! $providerCurrency) {
                foreach ($vehicle['products'] as $product) {
                    if (is_array($product) && ! empty($product['currency'])) {
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
            if (! ($conversion['success'] ?? false)) {
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

        // Protection amount: provider-priced add-on (net).
        // Locauto: daily rate Ã— days. Adobe: total amount (PLI + selected protections).
        $providerProtectionTotal = 0.0;
        if ($vehicleSource === 'locauto_rent') {
            $protectionDaily = (float) ($validated['protection_amount'] ?? 0);
            if ($protectionDaily > 0) {
                $providerProtectionTotal = round($protectionDaily * $days, 2);
            }
        } elseif ($vehicleSource === 'adobe') {
            $providerProtectionTotal = (float) ($validated['protection_amount'] ?? 0);
        }

        $providerOptionsTotal = round($extrasTotalRaw + $providerProtectionTotal, 2);

        $extrasTotalConverted = $extrasTotalRaw;
        if ($providerCurrency && $providerCurrency !== $bookingCurrency && $extrasTotalConverted > 0) {
            $conversion = app(CurrencyConversionService::class)->convert($extrasTotalConverted, $providerCurrency, $bookingCurrency);
            if (! ($conversion['success'] ?? false)) {
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
                if (! ($conversion['success'] ?? false)) {
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

            // Skip client-total validation when currencies differ (client/server
            // exchange rates may differ causing false mismatches).
            // Price integrity is already guaranteed by price_hash verification.
            $currenciesDiffer = $providerCurrency && $providerCurrency !== $bookingCurrency;
            if (! $currenciesDiffer) {
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
        if (! empty($vehicle['products']) && is_array($vehicle['products'])) {
            foreach ($vehicle['products'] as $entry) {
                if (($entry['type'] ?? null) === $package) {
                    $product = $entry;
                    break;
                }
            }
        }

        if (! $product || empty($product['total'])) {
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
            if (! is_array($extra)) {
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
            if ($required && ! isset($selectedMap[$optionId])) {
                $missingRequired[] = $optionId;
            }
        }

        if (! empty($missingRequired)) {
            return [
                'success' => false,
                'error' => 'Required extras are missing. Please review your selection.',
            ];
        }

        $providerOptionsTotal = 0.0;
        foreach ($selectedMap as $optionId => $qty) {
            $option = $optionsById[$optionId] ?? null;
            if (! $option) {
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
            if (! ($conversion['success'] ?? false)) {
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
            if (! ($conversion['success'] ?? false)) {
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
        if ($source === 'surprice' && $package === 'FDW') {
            $supplierData = is_array($vehicle['supplier_data'] ?? null) ? $vehicle['supplier_data'] : [];
            $fdwTotal = $supplierData['fdw_total_amount'] ?? null;

            if ($fdwTotal !== null && is_numeric($fdwTotal) && (float) $fdwTotal > 0) {
                return (float) $fdwTotal;
            }
        }

        if (! empty($vehicle['products']) && is_array($vehicle['products']) && $package) {
            foreach ($vehicle['products'] as $product) {
                if (! is_array($product)) {
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
            $pricePerDay = $vehicle['price_per_day'] ?? ($vehicle['pricing']['price_per_day'] ?? null);
            if ($pricePerDay !== null) {
                return (float) $pricePerDay * $days;
            }
        }

        if (isset($vehicle['total_price']) || isset($vehicle['pricing']['total_price'])) {
            $vehicleTotal = $vehicle['total_price'] ?? ($vehicle['pricing']['total_price'] ?? null);

            return (float) $vehicleTotal;
        }

        if (isset($validated['vehicle_total'])) {
            return (float) $validated['vehicle_total'];
        }

        if (isset($validated['total_amount'])) {
            return (float) $validated['total_amount'];
        }

        return null;
    }

    private function resolveVehicleLegacyPayload(array $vehicle): array
    {
        $payload = $vehicle['booking_context']['provider_payload'] ?? [];

        return is_array($payload) ? $payload : [];
    }

    private function resolveVehicleBenefitsForCheckout(array $vehicle): array
    {
        $benefits = $vehicle['benefits'] ?? $this->resolveVehicleLegacyPayload($vehicle)['benefits'] ?? [];

        return is_array($benefits) ? $benefits : [];
    }

    private function resolveVehicleSecurityDepositForCheckout(array $vehicle): mixed
    {
        return $vehicle['security_deposit']
            ?? ($vehicle['pricing']['deposit_amount'] ?? null)
            ?? ($this->resolveVehicleLegacyPayload($vehicle)['security_deposit'] ?? null)
            ?? ($this->resolveVehicleBenefitsForCheckout($vehicle)['deposit_amount'] ?? null);
    }

    private function resolveVehicleDepositPaymentMethodForCheckout(array $vehicle): mixed
    {
        return $vehicle['payment_method']
            ?? ($this->resolveVehicleLegacyPayload($vehicle)['payment_method'] ?? null);
    }

    private function resolveVehicleImagesForCheckout(array $vehicle): array
    {
        $images = $vehicle['images'] ?? ($this->resolveVehicleLegacyPayload($vehicle)['images'] ?? []);

        return is_array($images) ? $images : [];
    }

    private function resolveExtrasTotal(array $extras, int $days): float
    {
        $total = 0.0;
        foreach ($extras as $extra) {
            if (! is_array($extra)) {
                continue;
            }
            $extraId = (string) ($extra['id'] ?? $extra['option_id'] ?? '');
            if (($extra['purpose'] ?? null) === 'protection' || str_starts_with($extraId, 'locauto_protection_')) {
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

    /**
     * Convert a major-unit amount (e.g. 12.34 EUR) to Stripe's minor-unit integer
     * with correct exponent per currency. JPY/KRW/etc. are zero-decimal; BHD/KWD/etc.
     * are three-decimal and Stripe requires rounding to the nearest 10.
     */
    private function toStripeMinorUnits(float $amount, string $currencyCode): int
    {
        $minorUnit = app(CurrencyRegistry::class)->minorUnit($currencyCode);

        if ($minorUnit === 0) {
            return (int) round($amount);
        }

        if ($minorUnit === 3) {
            return (int) (round($amount * 100) * 10);
        }

        return (int) round($amount * 100);
    }

    private function normalizeCurrencyCode($currency): string
    {
        return app(CurrencyRegistry::class)->normalize((string) ($currency ?? ''), 'EUR');
    }

    /**
     * Calculate exchange rate between two currencies for a given amount
     */
    private function calculateExchangeRate(?string $fromCurrency, ?string $toCurrency, ?float $amount): ?float
    {
        if (! $fromCurrency || ! $toCurrency || $fromCurrency === $toCurrency) {
            return 1.0;
        }

        $amount = $amount ?? 0;
        if ($amount <= 0) {
            return 1.0;
        }

        $conversionService = app(CurrencyConversionService::class);
        $result = $conversionService->convert($amount, $fromCurrency, $toCurrency);

        if (! ($result['success'] ?? false)) {
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

        if (! $sessionId) {
            Log::warning('Success page accessed without session_id');

            return $this->bookingStatusRedirect('invalid_session');
        }

        Log::info('Success page accessed', ['session_id' => $sessionId]);

        try {
            // Find existing booking
            $booking = Booking::where('stripe_session_id', $sessionId)
                ->with(['customer', 'payments', 'vehicle']) // Corrected relationships
                ->first();

            $session = null;

            if ($booking) {
                $needsUpdate = ! in_array($booking->booking_status, ['confirmed', 'completed'], true)
                    || ! in_array($booking->payment_status, ['partial', 'paid'], true);

                if ($needsUpdate) {
                    Log::info('Booking pending after success, attempting Stripe fetch', ['booking_id' => $booking->id, 'session_id' => $sessionId]);
                    $session = StripeSession::retrieve($sessionId);

                    if ($session->payment_status === 'paid') {
                        Log::info('Session paid, updating booking via service', ['session_id' => $sessionId]);
                        $booking = $bookingService->createBookingFromSession($session);
                    } else {
                        Log::warning('Session not paid', ['session_id' => $sessionId, 'status' => $session->payment_status]);

                        return $this->bookingStatusRedirect('payment_not_completed', $sessionId);
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

                    return $this->bookingStatusRedirect('payment_not_completed', $sessionId);
                }
            }

            if (! $booking) {
                Log::warning('Paid session did not produce a booking', ['session_id' => $sessionId]);

                return $this->bookingStatusRedirect('refund_pending', $sessionId);
            }

            // Re-fetch with relations to be sure
            $booking = Booking::where('id', $booking->id)->with(['customer', 'payments', 'extras', 'offers'])->first();

            // Authz: only block when an authenticated user tries to view a booking
            // that belongs to a different registered user. Anonymous visitors hitting
            // the Stripe-issued success_url (with the unguessable session_id) are by
            // design â€” guest checkouts and just-finished payments must reach this
            // page without being bounced to login.
            $bookingOwnerId = $booking->customer?->user_id;
            $authUserId = auth()->id();
            if ($bookingOwnerId !== null && $authUserId !== null && $bookingOwnerId !== $authUserId) {
                Log::warning('Booking success page accessed by non-owner', [
                    'booking_id' => $booking->id,
                    'owner_user_id' => $bookingOwnerId,
                    'requesting_user_id' => $authUserId,
                ]);
                abort(403);
            }

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

            return $this->bookingStatusRedirect('support_review', $sessionId);
        }
    }

    public function status(Request $request)
    {
        $state = (string) $request->query('state', 'support_review');
        $sessionId = (string) $request->query('session_id', '');
        $booking = null;

        if ($sessionId !== '') {
            $booking = Booking::where('stripe_session_id', $sessionId)
                ->with(['customer', 'payments'])
                ->first();
        }

        if ($booking) {
            $bookingOwnerId = $booking->customer?->user_id;
            $authUserId = auth()->id();
            if ($bookingOwnerId !== null && $authUserId !== null && $bookingOwnerId !== $authUserId) {
                Log::warning('Booking status page accessed by non-owner', [
                    'booking_id' => $booking->id,
                    'owner_user_id' => $bookingOwnerId,
                    'requesting_user_id' => $authUserId,
                ]);
                abort(403);
            }

            $state = $this->resolveBookingOutcomeState($booking, $state);
        }

        return inertia('Booking/Status', [
            'state' => $state,
            'session_id' => $sessionId !== '' ? $sessionId : null,
            'booking' => $booking ? [
                'booking_number' => $booking->booking_number,
                'booking_status' => $booking->booking_status,
                'payment_status' => $booking->payment_status,
                'provider_source' => $booking->provider_source,
                'provider_booking_ref' => $booking->provider_booking_ref,
                'total_amount' => $booking->total_amount,
                'amount_paid' => $booking->amount_paid,
                'booking_currency' => $booking->booking_currency,
            ] : null,
        ]);
    }

    private function resolveBookingOutcomeState(Booking $booking, string $fallback): string
    {
        if ($booking->booking_status === 'cancelled' && $booking->payment_status === 'payment_cancelled') {
            return 'payment_cancelled';
        }

        if ($booking->booking_status === 'reservation_failed') {
            return 'reservation_failed';
        }

        if ($booking->payment_status === 'refund_pending' || $booking->booking_status === 'rejected') {
            return 'refund_pending';
        }

        if (
            $booking->provider_source
            && $booking->provider_source !== 'internal'
            && empty($booking->provider_booking_ref)
            && in_array($booking->booking_status, ['confirmed', 'pending'], true)
        ) {
            return 'pending_supplier_confirmation';
        }

        if (in_array($booking->booking_status, ['confirmed', 'completed'], true)) {
            return 'confirmed';
        }

        return $fallback;
    }

    /**
     * Cancel redirect page
     */
    private function resolveRecordGoSellCode(?string $country): ?string
    {
        $country = strtoupper(trim((string) $country));
        $sellCodes = (array) config('services.recordgo.sellcodes', []);

        if ($country === '') {
            return $sellCodes['default'] ?? null;
        }

        return $sellCodes[$country] ?? ($sellCodes['default'] ?? null);
    }

    public function cancel()
    {
        return inertia('Booking/Cancel', [
            'message' => 'Your payment was cancelled. You can try again anytime.',
        ]);
    }

    private function resolveCheckoutIdentityConflict(Request $request, array $customer): ?array
    {
        $identityGuard = app(CheckoutIdentityGuardService::class);
        $matches = $identityGuard->findExistingUsers(
            $customer['email'] ?? null,
            $customer['phone'] ?? null
        );

        $conflict = $identityGuard->resolveCheckoutConflict(
            $request->user(),
            $matches['email_user'] ?? null,
            $matches['phone_user'] ?? null
        );

        if (! $conflict) {
            return null;
        }

        $payload = [
            'success' => false,
            'error' => $conflict['message'],
            'error_code' => $conflict['code'],
        ];

        if (! empty($conflict['show_login'])) {
            $payload['login_url'] = route('login', ['locale' => app()->getLocale()]);
        }

        return $payload;
    }
}
