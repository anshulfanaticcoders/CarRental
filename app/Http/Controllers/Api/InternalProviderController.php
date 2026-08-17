<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiBooking;
use App\Models\ApiBookingExtra;
use App\Models\ApiConsumer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VendorLocation;
use App\Models\VendorVehicleAddon;
use App\Services\Bookings\InternalBookingSnapshotService;
use App\Services\Vehicles\InternalVehicleAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class InternalProviderController extends Controller
{
    public function __construct(
        private readonly InternalVehicleAvailabilityService $internalVehicleAvailabilityService,
        private readonly InternalBookingSnapshotService $internalBookingSnapshotService,
    ) {}

    private function dispatchBookingCreatedNotificationsAfterResponse(
        ApiBooking $booking,
        Vehicle $vehicle,
        ?ApiConsumer $consumer,
        bool $isSandbox,
    ): void {
        if ($isSandbox) {
            return;
        }

        dispatch(function () use ($booking, $vehicle, $consumer) {
            try {
                $vendor = $vehicle->vendor_id ? User::find($vehicle->vendor_id) : null;

                if ($vendor && $consumer) {
                    $vendor->notify(new \App\Notifications\ApiBooking\ApiBookingCreatedVendorNotification($booking, $consumer));
                }
            } catch (\Exception $e) {
                Log::warning('Failed to send API booking creation notifications', [
                    'booking_number' => $booking->booking_number,
                    'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();
    }

    private function dispatchBookingCancelledNotificationsAfterResponse(ApiBooking $booking): void
    {
        dispatch(function () use ($booking) {
            try {
                $vehicle = Vehicle::find($booking->vehicle_id);
                $vendor = $vehicle && $vehicle->vendor_id ? User::find($vehicle->vendor_id) : null;

                Notification::route('mail', $booking->driver_email)
                    ->notify(new \App\Notifications\ApiBooking\ApiBookingCancelledDriverNotification($booking));

                if ($vendor) {
                    $vendor->notify(new \App\Notifications\ApiBooking\ApiBookingCancelledVendorNotification($booking));
                }
            } catch (\Exception $e) {
                Log::warning('Failed to send API booking cancellation notifications', [
                    'booking_number' => $booking->booking_number,
                    'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();
    }

    public function searchVehicles(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pickup_location_id' => ['required', 'integer'],
            'dropoff_location_id' => ['nullable', 'integer'],
            'pickup_date' => ['required', 'date'],
            'pickup_time' => ['required', 'date_format:H:i'],
            'dropoff_date' => ['required', 'date', 'after_or_equal:pickup_date'],
            'dropoff_time' => ['required', 'date_format:H:i'],
            'driver_age' => ['nullable', 'integer', 'min:18'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        // Rental days from the FULL datetimes — same billing rule as booking.
        $pickupAt = Carbon::parse($validated['pickup_date'].' '.$validated['pickup_time']);
        $dropoffAt = Carbon::parse($validated['dropoff_date'].' '.$validated['dropoff_time']);
        if ($pickupAt->lessThanOrEqualTo(now()) || $dropoffAt->lessThanOrEqualTo($pickupAt)) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_RENTAL_WINDOW',
                    'message' => 'Pickup must be in the future and drop-off must be after pickup.',
                    'status' => 422,
                ],
            ], 422);
        }
        $totalDays = max(1, (int) ceil($pickupAt->diffInMinutes($dropoffAt) / 1440));
        // Prices are stored and booked in EUR and this endpoint performs no
        // conversion — labeling raw amounts with a requested currency was a
        // lie the partner resold. Reject anything else honestly.
        $currency = strtoupper((string) ($validated['currency'] ?? 'EUR'));
        if ($currency !== 'EUR') {
            return response()->json([
                'error' => [
                    'code' => 'UNSUPPORTED_CURRENCY',
                    'message' => 'Prices are quoted and booked in EUR only.',
                    'status' => 422,
                ],
            ], 422);
        }

        $pickupLocation = VendorLocation::query()
            ->whereKey($validated['pickup_location_id'])
            ->where('is_active', true)
            ->first();
        $locationVehicle = $pickupLocation ? null : Vehicle::find($validated['pickup_location_id']);

        if (! $pickupLocation && ! $locationVehicle) {
            return response()->json([
                'error' => [
                    'code' => 'LOCATION_NOT_FOUND',
                    'message' => 'The specified pickup location was not found.',
                    'status' => 404,
                ],
            ], 404);
        }

        $dropoffLocation = ! empty($validated['dropoff_location_id'])
            ? VendorLocation::query()
                ->whereKey($validated['dropoff_location_id'])
                ->where('is_active', true)
                ->first()
            : null;
        $dropoffLocationVehicle = (! $dropoffLocation && ! empty($validated['dropoff_location_id']))
            ? Vehicle::find($validated['dropoff_location_id'])
            : null;

        $vehicles = Vehicle::query()
            ->when(
                $pickupLocation !== null,
                fn ($query) => $query->where('vendor_location_id', $pickupLocation->id),
                fn ($query) => $query->where('full_vehicle_address', $locationVehicle->full_vehicle_address)
            )
            ->with(['vendor', 'vendor.vendorProfile', 'images', 'blockings', 'category', 'benefits', 'operatingHours', 'addons', 'vendorLocation']);

        $this->internalVehicleAvailabilityService->apply($vehicles, [
            'pickup_date' => $validated['pickup_date'],
            'pickup_time' => $validated['pickup_time'],
            'dropoff_date' => $validated['dropoff_date'],
            'dropoff_time' => $validated['dropoff_time'],
        ]);

        $available = $vehicles
            ->get();

        $pickupLocationName = ($pickupLocation && $pickupLocation->name)
            ? $pickupLocation->name
            : ($locationVehicle->full_vehicle_address ?: $locationVehicle->location);
        $dropoffLocationName = $dropoffLocation
            ? $dropoffLocation->name
            : ($dropoffLocationVehicle
                ? ($dropoffLocationVehicle->full_vehicle_address ?: $dropoffLocationVehicle->location)
                : $pickupLocationName);

        $results = $available->map(function ($vehicle) use (
            $totalDays, $currency, $pickupLocationName, $dropoffLocationName
        ) {
            // Quoted price must equal the booked price: the same partner
            // markup rate applies here as in createBooking.
            $partnerRate = max(0.0, (float) config('vrooem.partner_markup_percent', 0)) / 100;
            $dailyRate = round((float) $vehicle->price_per_day * (1 + $partnerRate), 2);
            $totalPrice = round($dailyRate * $totalDays, 2);

            $primaryImage = $vehicle->images->sortBy('sort_order')->first();
            $image = $primaryImage ? ($primaryImage->image_url ?: $primaryImage->image_path) : null;

            $images = $vehicle->images->sortBy('sort_order')->map(function ($img) {
                return $img->image_url ?: $img->image_path;
            })->values()->toArray();

            $vendorName = $vehicle->vendor?->vendorProfile?->company_name
                ?? $vehicle->vendor?->name
                ?? 'Unknown';
            $canonicalLocation = $vehicle->vendorLocation;
            $locationSnapshot = $this->internalBookingSnapshotService->buildForVehicle($vehicle, [
                'pickup_location' => $pickupLocationName,
                'return_location' => $dropoffLocationName,
                'booking_currency' => $currency,
            ]);

            // Parse features JSON
            $features = [];
            if ($vehicle->features) {
                $decoded = is_string($vehicle->features) ? json_decode($vehicle->features, true) : $vehicle->features;
                $features = is_array($decoded) ? $decoded : [];
            }

            // Benefits data (mileage, cancellation, driver age)
            $benefits = $vehicle->benefits;
            $mileagePolicy = [
                'type' => ($benefits && $benefits->limited_km_per_day) ? 'limited' : 'unlimited',
                'km_per_day' => $benefits ? (float) $benefits->limited_km_per_day_range : null,
                'price_per_extra_km' => $benefits ? (float) $benefits->price_per_km_per_day : null,
            ];

            $cancellationPolicy = [
                'free_cancellation' => $benefits ? (bool) $benefits->cancellation_available_per_day : false,
                'cancel_before_days' => $benefits ? (int) $benefits->cancellation_available_per_day_date : 0,
                'cancellation_fee' => $benefits ? (float) ($benefits->cancellation_fee_per_day ?? 0) : 0,
            ];

            $minimumDriverAge = $benefits ? (int) $benefits->minimum_driver_age : 18;

            // Operating hours
            $operatingHours = $vehicle->operatingHours->map(fn ($h) => [
                'day' => (int) $h->day_of_week,
                'is_open' => (bool) $h->is_open,
                'open_time' => $h->open_time,
                'close_time' => $h->close_time,
            ])->values()->toArray();

            // Payment methods
            $paymentMethods = [];
            if ($vehicle->payment_method) {
                $decoded = is_string($vehicle->payment_method) ? json_decode($vehicle->payment_method, true) : $vehicle->payment_method;
                $paymentMethods = is_array($decoded) ? $decoded : [];
            }

            // Publish only inventory this endpoint can actually book. Insurance
            // remains omitted until createBooking supports it end-to-end.
            $insurancePlans = [];
            $extras = [];
            $bookableAddons = VendorVehicleAddon::where('vendor_id', $vehicle->vendor_id)
                ->where(fn ($query) => $query->where('vehicle_id', $vehicle->id)->orWhereNull('vehicle_id'))
                ->get();
            foreach ($bookableAddons as $addon) {
                $addonDailyRate = round((float) $addon->price * (1 + $partnerRate), 2);
                $extras[] = [
                    'id' => $addon->id,
                    'name' => $addon->extra_name,
                    'type' => $addon->extra_type,
                    'daily_rate' => $addonDailyRate,
                    'total_price' => round($addonDailyRate * $totalDays, 2),
                    'description' => $addon->description,
                    'max_quantity' => (int) ($addon->quantity ?: 1),
                ];
            }

            return [
                'id' => $vehicle->id,
                'name' => trim($vehicle->brand.' '.$vehicle->model),
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'year' => $vehicle->registration_date ? Carbon::parse($vehicle->registration_date)->year : null,
                'category' => $vehicle->category?->name ?? 'Standard',
                'transmission' => $vehicle->transmission,
                'fuel_type' => $vehicle->fuel,
                'fuel_policy' => $vehicle->fuel_policy ?? 'full_to_full',
                'seats' => (int) $vehicle->seating_capacity,
                'doors' => (int) $vehicle->number_of_doors,
                'bags' => (int) $vehicle->luggage_capacity,
                'air_conditioning' => (bool) $vehicle->air_conditioning,
                'color' => $vehicle->color,
                'image' => $image,
                'images' => $images,
                'daily_rate' => $dailyRate,
                'total_price' => $totalPrice,
                'currency' => $currency,
                'total_days' => $totalDays,
                'security_deposit' => (float) ($vehicle->security_deposit ?? 0),
                'pickup_location_id' => $canonicalLocation?->id,
                'dropoff_location_id' => $canonicalLocation?->id,
                'pickup_location' => $pickupLocationName,
                'dropoff_location' => $dropoffLocationName,
                'pickup_location_details' => $locationSnapshot['pickup_location_details'] ?? null,
                'dropoff_location_details' => $locationSnapshot['dropoff_location_details'] ?? null,
                'location_type' => $canonicalLocation?->location_type ?: $vehicle->location_type,
                'location_phone' => $vehicle->location_phone ?: $canonicalLocation?->phone,
                'pickup_instructions' => $vehicle->pickup_instructions ?: $canonicalLocation?->pickup_instructions,
                'dropoff_instructions' => $vehicle->dropoff_instructions ?: $canonicalLocation?->dropoff_instructions,
                'vendor_name' => $vendorName,
                'features' => $features,
                'mileage_policy' => $mileagePolicy,
                'cancellation_policy' => $cancellationPolicy,
                'minimum_driver_age' => $minimumDriverAge,
                'operating_hours' => $operatingHours,
                'payment_methods' => $paymentMethods,
                'insurance_plans' => $insurancePlans,
                'extras' => $extras,
                'guidelines' => $vehicle->guidelines,
                'terms_policy' => $vehicle->terms_policy,
                'rental_policy' => $vehicle->rental_policy,
            ];
        })
            ->filter(function (array $result) use ($validated): bool {
                return ! isset($validated['driver_age'])
                    || (int) $validated['driver_age'] >= (int) ($result['minimum_driver_age'] ?? 18);
            })
            ->values();

        return response()->json([
            'data' => $results,
            'meta' => [
                'total' => $results->count(),
                'pickup_date' => $validated['pickup_date'],
                'dropoff_date' => $validated['dropoff_date'],
                'total_days' => $totalDays,
                'currency' => $currency,
            ],
        ]);
    }

    public function getVehicleExtras(int $vehicleId): JsonResponse
    {
        $vehicle = Vehicle::find($vehicleId);

        if (! $vehicle) {
            return response()->json([
                'error' => [
                    'code' => 'VEHICLE_NOT_FOUND',
                    'message' => 'The specified vehicle was not found.',
                    'status' => 404,
                ],
            ], 404);
        }

        $partnerRate = max(0.0, (float) config('vrooem.partner_markup_percent', 0)) / 100;
        $extras = VendorVehicleAddon::where('vendor_id', $vehicle->vendor_id)
            ->where(function ($q) use ($vehicle) {
                $q->where('vehicle_id', $vehicle->id)
                    ->orWhereNull('vehicle_id');
            })
            ->get()
            ->map(fn ($addon) => [
                'extra_id' => $addon->id,
                'name' => $addon->extra_name ?: ($addon->addon?->name ?? 'Extra'),
                'type' => $addon->extra_type ?? 'optional',
                'price_per_day' => round((float) $addon->price * (1 + $partnerRate), 2),
                'max_quantity' => (int) ($addon->quantity ?? 1),
                'description' => $addon->description,
            ]);

        return response()->json([
            'data' => [
                'extras' => $extras->values(),
                'insurance_options' => [],
            ],
        ]);
    }

    public function createBooking(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'api_consumer_id' => ['required', 'integer'],
            'api_consumer_name' => ['nullable', 'string'],
            'vehicle_id' => ['required', 'integer'],
            'driver.first_name' => ['required', 'string', 'max:255'],
            'driver.last_name' => ['required', 'string', 'max:255'],
            'driver.email' => ['required', 'email', 'max:255'],
            'driver.phone' => ['required', 'string', 'max:50'],
            // Limits mirror the schema (varchar(50) / char(2) / tinyint) — the
            // old looser rules let valid-looking input through to a MySQL
            // truncation error surfaced as an opaque 500.
            'driver.age' => ['required', 'integer', 'min:18', 'max:99'],
            'driver.driving_license_number' => ['required', 'string', 'max:50'],
            'driver.driving_license_country' => ['required', 'string', 'size:2'],
            'extras' => ['nullable', 'array'],
            'extras.*.extra_id' => ['required_with:extras', 'integer'],
            'extras.*.quantity' => ['required_with:extras', 'integer', 'min:1'],
            'insurance_id' => ['nullable', 'integer'],
            'flight_number' => ['nullable', 'string', 'max:20'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'pickup_time' => ['required', 'date_format:H:i'],
            'dropoff_date' => ['required', 'date', 'after_or_equal:pickup_date'],
            'dropoff_time' => ['required', 'date_format:H:i'],
        ]);

        // Consumer identity: the api.consumer middleware resolves it from the
        // API key; the body value is only trusted on legacy keyless calls.
        // Either way a suspended consumer cannot book real cars.
        $consumer = $request->attributes->get('api_consumer')
            ?: ApiConsumer::find($validated['api_consumer_id']);
        if (! $consumer) {
            return response()->json([
                'error' => [
                    'code' => 'UNKNOWN_CONSUMER',
                    'message' => 'The api_consumer_id does not match a registered consumer.',
                    'status' => 422,
                ],
            ], 422);
        }
        if (! $consumer->isActive()) {
            return response()->json([
                'error' => [
                    'code' => 'CONSUMER_SUSPENDED',
                    'message' => 'This API consumer is suspended.',
                    'status' => 403,
                ],
            ], 403);
        }

        // The rule engine can't compare across date+time pairs: a same-day
        // 17:00 → 09:00 request used to pass and get priced as one day.
        $pickupAt = Carbon::parse($validated['pickup_date'].' '.$validated['pickup_time']);
        $dropoffAt = Carbon::parse($validated['dropoff_date'].' '.$validated['dropoff_time']);
        if ($pickupAt->lessThanOrEqualTo(now()) || $dropoffAt->lessThanOrEqualTo($pickupAt)) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_RENTAL_WINDOW',
                    'message' => 'Pickup must be in the future and drop-off must be after pickup.',
                    'status' => 422,
                ],
            ], 422);
        }

        $vehicle = Vehicle::with(['vendor', 'vendor.vendorProfile', 'images', 'vendorLocation', 'vendorProfileData', 'benefits'])->find($validated['vehicle_id']);

        if (! $vehicle || ! in_array($vehicle->status, Vehicle::searchableStatuses(), true)) {
            return response()->json([
                'error' => [
                    'code' => 'VEHICLE_UNAVAILABLE',
                    'message' => 'The selected vehicle is no longer available.',
                    'status' => 409,
                ],
            ], 409);
        }

        $minimumDriverAge = max(18, (int) ($vehicle->benefits?->minimum_driver_age ?? 18));
        if ((int) $validated['driver']['age'] < $minimumDriverAge) {
            return response()->json([
                'error' => [
                    'code' => 'DRIVER_AGE_NOT_ELIGIBLE',
                    'message' => "Driver must be at least {$minimumDriverAge} years old for this vehicle.",
                    'status' => 422,
                ],
            ], 422);
        }

        // Shared namespace with the customer checkout paths — see
        // StripeCheckoutController::reserveInternalVehicleForCheckout.
        $availabilityLock = Cache::lock("vehicle-lock-{$vehicle->id}", 30);
        $lockAcquired = false;
        try {
            $availabilityLock->block(10);
            $lockAcquired = true;
        } catch (\Throwable $e) {
            Log::warning('Internal provider booking lock timeout', [
                'vehicle_id' => $vehicle->id,
                'api_consumer_id' => $validated['api_consumer_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => [
                    'code' => 'VEHICLE_LOCK_TIMEOUT',
                    'message' => 'The vehicle is being booked by another customer. Please retry shortly.',
                    'status' => 409,
                ],
            ], 409);
        }

        if (! $this->internalVehicleAvailabilityService->isVehicleAvailable($vehicle, [
            'pickup_date' => $validated['pickup_date'],
            'pickup_time' => $validated['pickup_time'],
            'dropoff_date' => $validated['dropoff_date'],
            'dropoff_time' => $validated['dropoff_time'],
        ])) {
            if ($lockAcquired) {
                $this->releaseAvailabilityLockQuietly($availabilityLock, $vehicle->id);
                $lockAcquired = false;
            }

            return response()->json([
                'error' => [
                    'code' => 'VEHICLE_UNAVAILABLE',
                    'message' => 'The vehicle is no longer available for the selected dates.',
                    'status' => 409,
                ],
            ], 409);
        }

        $vendorDailyRate = (float) $vehicle->price_per_day;
        $partnerRate = max(0.0, (float) config('vrooem.partner_markup_percent', 0)) / 100;
        $dailyRate = round($vendorDailyRate * (1 + $partnerRate), 2);
        // Rental days from the FULL datetimes — date-only diffing billed
        // Mon 09:00 → Wed 18:00 as 2 days while the vendor supplied 3.
        $totalDays = max(1, (int) ceil($pickupAt->diffInMinutes($dropoffAt) / 1440));
        $basePrice = round($dailyRate * $totalDays, 2);
        $vendorBasePrice = round($vendorDailyRate * $totalDays, 2);

        $extrasTotal = 0;
        $vendorExtrasTotal = 0;
        $extrasData = [];

        if (! empty($validated['extras'])) {
            foreach ($validated['extras'] as $extraInput) {
                $addon = VendorVehicleAddon::where('id', $extraInput['extra_id'])
                    ->where('vendor_id', $vehicle->vendor_id)
                    ->where(fn ($query) => $query->where('vehicle_id', $vehicle->id)->orWhereNull('vehicle_id'))
                    ->first();

                // Reject unknown ids loudly. Silently dropping them created
                // bookings WITHOUT extras the partner's customer already paid
                // for — and an id clash with another table could book the
                // wrong extra entirely.
                if (! $addon) {
                    $this->releaseAvailabilityLockQuietly($availabilityLock, $vehicle->id);
                    $lockAcquired = false;

                    return response()->json([
                        'error' => [
                            'code' => 'UNKNOWN_EXTRA',
                            'message' => 'Extra '.$extraInput['extra_id'].' does not exist for this vehicle. Re-fetch /vehicles/{id}/extras and use the returned ids.',
                            'status' => 422,
                        ],
                    ], 422);
                }

                $vendorUnitPrice = (float) $addon->price;
                $quantity = (int) $extraInput['quantity'];
                $maxQuantity = max(1, (int) ($addon->quantity ?? 1));
                if ($quantity > $maxQuantity) {
                    $this->releaseAvailabilityLockQuietly($availabilityLock, $vehicle->id);
                    $lockAcquired = false;

                    return response()->json([
                        'error' => [
                            'code' => 'EXTRA_QUANTITY_EXCEEDED',
                            'message' => 'Extra '.$addon->id.' allows a maximum quantity of '.$maxQuantity.'.',
                            'status' => 422,
                        ],
                    ], 422);
                }

                $unitPrice = round($vendorUnitPrice * (1 + $partnerRate), 2);
                $extraTotal = round($unitPrice * $quantity * $totalDays, 2);
                $extrasTotal += $extraTotal;
                $vendorExtrasTotal += round($vendorUnitPrice * $quantity * $totalDays, 2);

                $extrasData[] = [
                    'extra_id' => $addon->id,
                    'extra_name' => $addon->extra_name ?: ($addon->addon?->name ?? 'Extra'),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $extraTotal,
                    'currency' => 'EUR',
                ];
            }
        }

        // insurance_id used to be stored unvalidated and unpriced — every
        // partner insurance upsell was revenue we never collected. Until
        // insurance pricing is wired, refuse ids we can't verify and price.
        if (! empty($validated['insurance_id'])) {
            $this->releaseAvailabilityLockQuietly($availabilityLock, $vehicle->id);
            $lockAcquired = false;

            return response()->json([
                'error' => [
                    'code' => 'INSURANCE_NOT_SUPPORTED',
                    'message' => 'Insurance selection is not supported on this endpoint yet. Book without insurance_id.',
                    'status' => 422,
                ],
            ], 422);
        }

        // Settlement: vendor_net is what the vendor is owed; the platform
        // commission (PARTNER_API_MARKUP_PERCENT, default 0) sits on top.
        // Partner bookings used to pass through at 100% vendor price with no
        // settlement record at all.
        $vendorNet = round($vendorBasePrice + $vendorExtrasTotal, 2);
        $totalAmount = round($basePrice + $extrasTotal, 2);
        $platformCommission = round($totalAmount - $vendorNet, 2);

        $primaryImage = $vehicle->images->first();
        $vehicleImage = $primaryImage ? ($primaryImage->image_url ?: $primaryImage->image_path) : null;
        $locationSnapshot = $this->internalBookingSnapshotService->buildForVehicle($vehicle, [
            'pickup_location' => $vehicle->vendorLocation?->name ?: ($vehicle->full_vehicle_address ?: $vehicle->location),
            'return_location' => $vehicle->vendorLocation?->name ?: ($vehicle->full_vehicle_address ?: $vehicle->location),
            'booking_currency' => 'EUR',
        ]);

        DB::beginTransaction();

        try {
            $isSandbox = $consumer->isSandbox();

            $booking = ApiBooking::create([
                'booking_number' => ApiBooking::generateBookingNumber(),
                'api_consumer_id' => $consumer->id,
                'vehicle_id' => $vehicle->id,
                'pickup_vendor_location_id' => $vehicle->vendor_location_id,
                'dropoff_vendor_location_id' => $vehicle->vendor_location_id,
                'vehicle_name' => trim($vehicle->brand.' '.$vehicle->model),
                'vehicle_image' => $vehicleImage,
                'driver_first_name' => $validated['driver']['first_name'],
                'driver_last_name' => $validated['driver']['last_name'],
                'driver_email' => $validated['driver']['email'],
                'driver_phone' => $validated['driver']['phone'],
                'driver_age' => $validated['driver']['age'],
                'driver_license_number' => $validated['driver']['driving_license_number'],
                'driver_license_country' => $validated['driver']['driving_license_country'],
                'pickup_date' => $validated['pickup_date'],
                'pickup_time' => $validated['pickup_time'],
                'return_date' => $validated['dropoff_date'],
                'return_time' => $validated['dropoff_time'],
                'pickup_location' => $locationSnapshot['pickup_location_details']['name'] ?? ($vehicle->full_vehicle_address ?: $vehicle->location),
                'return_location' => $locationSnapshot['dropoff_location_details']['name'] ?? ($vehicle->full_vehicle_address ?: $vehicle->location),
                'total_days' => $totalDays,
                'daily_rate' => $dailyRate,
                'base_price' => $basePrice,
                'extras_total' => $extrasTotal,
                'total_amount' => $totalAmount,
                'platform_commission' => $platformCommission,
                'vendor_net' => $vendorNet,
                'currency' => 'EUR',
                'status' => 'pending',
                'is_test' => $isSandbox,
                'flight_number' => $validated['flight_number'] ?? null,
                'special_requests' => $validated['special_requests'] ?? null,
                'insurance_id' => $validated['insurance_id'] ?? null,
                'provider_metadata' => $locationSnapshot,
            ]);

            foreach ($extrasData as $extra) {
                ApiBookingExtra::create(array_merge($extra, [
                    'api_booking_id' => $booking->id,
                ]));
            }

            DB::commit();
            if ($lockAcquired) {
                $this->releaseAvailabilityLockQuietly($availabilityLock, $vehicle->id);
                $lockAcquired = false;
            }

            $booking->load('extras');
            $this->dispatchBookingCreatedNotificationsAfterResponse($booking, $vehicle, $consumer, $isSandbox);

            return response()->json([
                'data' => [
                    'booking_number' => $booking->booking_number,
                    'status' => $booking->status,
                    'vehicle_id' => $booking->vehicle_id,
                    'vehicle_name' => $booking->vehicle_name,
                    'vehicle_image' => $booking->vehicle_image,
                    'driver' => [
                        'first_name' => $booking->driver_first_name,
                        'last_name' => $booking->driver_last_name,
                        'email' => $booking->driver_email,
                        'phone' => $booking->driver_phone,
                        'age' => $booking->driver_age,
                    ],
                    'pickup_date' => $booking->pickup_date->toDateString(),
                    'pickup_time' => $booking->pickup_time,
                    'dropoff_date' => $booking->return_date->toDateString(),
                    'dropoff_time' => $booking->return_time,
                    'pickup_location_id' => $booking->pickup_vendor_location_id,
                    'dropoff_location_id' => $booking->dropoff_vendor_location_id,
                    'pickup_location' => $booking->pickup_location,
                    'return_location' => $booking->return_location,
                    'is_one_way' => trim((string) $booking->pickup_location) !== trim((string) $booking->return_location)
                        && $booking->return_location !== null,
                    'pickup_location_details' => $booking->provider_metadata['pickup_location_details'] ?? null,
                    'dropoff_location_details' => $booking->provider_metadata['dropoff_location_details'] ?? null,
                    'pickup_instructions' => $booking->provider_metadata['pickup_location_details']['pickup_instructions']
                        ?? ($booking->provider_metadata['pickup_instructions'] ?? null),
                    'dropoff_instructions' => $booking->provider_metadata['dropoff_location_details']['dropoff_instructions']
                        ?? ($booking->provider_metadata['dropoff_instructions'] ?? null),
                    'total_days' => $booking->total_days,
                    'daily_rate' => (float) $booking->daily_rate,
                    'base_price' => (float) $booking->base_price,
                    'extras_total' => (float) $booking->extras_total,
                    'total_amount' => (float) $booking->total_amount,
                    'currency' => $booking->currency,
                    'extras' => $booking->extras->map(fn ($e) => [
                        'extra_id' => $e->extra_id,
                        'extra_name' => $e->extra_name,
                        'quantity' => $e->quantity,
                        'unit_price' => (float) $e->unit_price,
                        'total_price' => (float) $e->total_price,
                    ])->values(),
                    'flight_number' => $booking->flight_number,
                    'special_requests' => $booking->special_requests,
                    'created_at' => $booking->created_at->toIso8601String(),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($lockAcquired) {
                $this->releaseAvailabilityLockQuietly($availabilityLock, $vehicle->id);
            }
            Log::error('Internal provider booking creation failed', [
                'error' => $e->getMessage(),
                'vehicle_id' => $validated['vehicle_id'],
                'api_consumer_id' => $validated['api_consumer_id'],
            ]);

            return response()->json([
                'error' => [
                    'code' => 'BOOKING_FAILED',
                    'message' => 'Failed to create booking. Please try again.',
                    'status' => 500,
                ],
            ], 500);
        }
    }

    private function releaseAvailabilityLockQuietly(mixed $lock, int $vehicleId): void
    {
        try {
            $lock->release();
        } catch (\Throwable $e) {
            Log::warning('Internal provider booking lock release failed', [
                'vehicle_id' => $vehicleId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getBooking(string $bookingNumber, Request $request): JsonResponse
    {
        $apiConsumerId = $request->query('api_consumer_id');

        if (! $apiConsumerId) {
            return response()->json([
                'error' => [
                    'code' => 'MISSING_CONSUMER_ID',
                    'message' => 'The api_consumer_id query parameter is required.',
                    'status' => 400,
                ],
            ], 400);
        }

        $booking = ApiBooking::where('booking_number', $bookingNumber)
            ->where('api_consumer_id', $apiConsumerId)
            ->with(['extras', 'vehicle.vendorLocation', 'vehicle.vendorProfileData', 'vehicle.vendor'])
            ->first();

        if (! $booking) {
            return response()->json([
                'error' => [
                    'code' => 'BOOKING_NOT_FOUND',
                    'message' => 'The specified booking was not found.',
                    'status' => 404,
                ],
            ], 404);
        }

        if ($booking->vehicle) {
            $booking->provider_metadata = $this->internalBookingSnapshotService->mergeMissingIntoMetadata(
                $booking->provider_metadata,
                $booking->vehicle,
                [
                    'pickup_location' => $booking->pickup_location,
                    'return_location' => $booking->return_location,
                    'booking_currency' => $booking->currency,
                ]
            );
        }

        return response()->json([
            'data' => [
                'booking_number' => $booking->booking_number,
                'status' => $booking->status,
                'vehicle_id' => $booking->vehicle_id,
                'vehicle_name' => $booking->vehicle_name,
                'vehicle_image' => $booking->vehicle_image,
                'driver' => [
                    'first_name' => $booking->driver_first_name,
                    'last_name' => $booking->driver_last_name,
                    'email' => $booking->driver_email,
                    'phone' => $booking->driver_phone,
                    'age' => $booking->driver_age,
                ],
                'pickup_date' => $booking->pickup_date->toDateString(),
                'pickup_time' => $booking->pickup_time,
                'dropoff_date' => $booking->return_date->toDateString(),
                'dropoff_time' => $booking->return_time,
                'pickup_location_id' => $booking->pickup_vendor_location_id,
                'dropoff_location_id' => $booking->dropoff_vendor_location_id,
                'pickup_location' => $booking->pickup_location,
                'return_location' => $booking->return_location,
                'pickup_location_details' => $booking->provider_metadata['pickup_location_details'] ?? null,
                'dropoff_location_details' => $booking->provider_metadata['dropoff_location_details'] ?? null,
                'total_days' => $booking->total_days,
                'daily_rate' => (float) $booking->daily_rate,
                'base_price' => (float) $booking->base_price,
                'extras_total' => (float) $booking->extras_total,
                'total_amount' => (float) $booking->total_amount,
                'currency' => $booking->currency,
                'extras' => $booking->extras->map(fn ($e) => [
                    'extra_id' => $e->extra_id,
                    'extra_name' => $e->extra_name,
                    'quantity' => $e->quantity,
                    'unit_price' => (float) $e->unit_price,
                    'total_price' => (float) $e->total_price,
                ])->values(),
                'flight_number' => $booking->flight_number,
                'special_requests' => $booking->special_requests,
                'cancellation_reason' => $booking->cancellation_reason,
                'cancelled_at' => $booking->cancelled_at?->toIso8601String(),
                'created_at' => $booking->created_at->toIso8601String(),
            ],
        ]);
    }

    public function cancelBooking(string $bookingNumber, Request $request): JsonResponse
    {
        $apiConsumerId = $request->query('api_consumer_id');

        if (! $apiConsumerId) {
            return response()->json([
                'error' => [
                    'code' => 'MISSING_CONSUMER_ID',
                    'message' => 'The api_consumer_id query parameter is required.',
                    'status' => 400,
                ],
            ], 400);
        }

        $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $booking = ApiBooking::where('booking_number', $bookingNumber)
            ->where('api_consumer_id', $apiConsumerId)
            ->first();

        if (! $booking) {
            return response()->json([
                'error' => [
                    'code' => 'BOOKING_NOT_FOUND',
                    'message' => 'The specified booking was not found.',
                    'status' => 404,
                ],
            ], 404);
        }

        // Idempotent: a partner whose first cancel timed out at the network
        // layer WILL retry. A 4xx on the retry reads as "cancel failed" while
        // it actually succeeded — echo the cancelled state instead.
        if ($booking->status === 'cancelled') {
            return response()->json([
                'data' => [
                    'booking_number' => $booking->booking_number,
                    'status' => 'cancelled',
                    'cancelled_at' => optional($booking->cancelled_at)->toIso8601String(),
                    'message' => 'Booking was already cancelled.',
                ],
            ]);
        }

        if ($booking->status === 'completed') {
            return response()->json([
                'error' => [
                    'code' => 'BOOKING_NOT_CANCELLABLE',
                    'message' => "Booking cannot be cancelled. Current status: {$booking->status}.",
                    'status' => 422,
                ],
            ], 422);
        }

        // Enforce the cancellation policy search advertises — it published a
        // free-cancellation window and a fee, then cancelBooking applied
        // neither: peak-season cancels 2 hours before pickup were free and
        // the vendor got nothing. The fee is a recorded settlement line
        // (this API has no payment rails to charge it with).
        $cancellationFee = $this->cancellationFeeFor($booking);

        $booking->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->input('reason'),
            'cancellation_fee' => $cancellationFee,
            'cancelled_at' => now(),
        ]);
        $this->dispatchBookingCancelledNotificationsAfterResponse($booking);

        return response()->json([
            'data' => [
                'booking_number' => $booking->booking_number,
                'status' => 'cancelled',
                'cancelled_at' => $booking->cancelled_at->toIso8601String(),
                'cancellation_fee' => $cancellationFee,
                'currency' => $booking->currency,
                'message' => $cancellationFee > 0
                    ? 'Booking cancelled. A cancellation fee applies per the advertised policy.'
                    : 'Booking has been successfully cancelled.',
            ],
        ]);
    }

    /**
     * The same policy search advertises (vehicle benefits): free when
     * cancelled at least cancel_before_days before pickup, otherwise the
     * configured fee, capped at the booking total.
     */
    private function cancellationFeeFor(ApiBooking $booking): float
    {
        $benefits = $booking->vehicle?->benefits;
        if (! $benefits) {
            return 0.0;
        }

        $freeCancellation = (bool) $benefits->cancellation_available_per_day;
        $freeBeforeDays = (int) $benefits->cancellation_available_per_day_date;
        $fee = (float) ($benefits->cancellation_fee_per_day ?? 0);

        $pickupAt = Carbon::parse(
            $booking->pickup_date->toDateString().' '.($booking->pickup_time ?: '00:00')
        );
        $withinFreeWindow = $freeCancellation && now()->lessThanOrEqualTo($pickupAt->copy()->subDays($freeBeforeDays));

        if ($withinFreeWindow || $fee <= 0) {
            return 0.0;
        }

        return round(min($fee, (float) $booking->total_amount), 2);
    }
}
