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
use App\Models\VendorVehiclePlan;
use App\Services\Bookings\InternalBookingSnapshotService;
use App\Services\Vehicles\InternalVehicleAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        $pickupDate = Carbon::parse($validated['pickup_date']);
        $dropoffDate = Carbon::parse($validated['dropoff_date']);
        $totalDays = max(1, $pickupDate->diffInDays($dropoffDate));
        $currency = $validated['currency'] ?? 'EUR';

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

        // Get global insurance plans and addons
        $globalPlans = \App\Models\Plan::all();
        $globalAddons = \App\Models\BookingAddon::all();

        $results = $available->map(function ($vehicle) use (
            $totalDays, $currency, $pickupLocationName, $dropoffLocationName, $globalPlans, $globalAddons
        ) {
            $dailyRate = (float) $vehicle->price_per_day;
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

            // Insurance plans (global + vendor-specific)
            $insurancePlans = $globalPlans->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->plan_type,
                'daily_rate' => (float) $p->plan_value,
                'total_price' => round((float) $p->plan_value * $totalDays, 2),
                'description' => $p->plan_description,
                'features' => is_string($p->features) ? json_decode($p->features, true) : ($p->features ?? []),
            ])->values()->toArray();

            // Vehicle-specific vendor plans (if any)
            $vendorPlans = \App\Models\VendorVehiclePlan::where('vendor_id', $vehicle->vendor_id)
                ->where('vehicle_id', $vehicle->id)
                ->get();
            foreach ($vendorPlans as $vp) {
                $insurancePlans[] = [
                    'id' => $vp->id,
                    'name' => $vp->plan_type,
                    'daily_rate' => (float) $vp->price,
                    'total_price' => round((float) $vp->price * $totalDays, 2),
                    'description' => $vp->plan_description,
                    'features' => is_string($vp->features) ? json_decode($vp->features, true) : ($vp->features ?? []),
                ];
            }

            // Extras (global + vehicle-specific addons)
            $extras = $globalAddons->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->extra_name,
                'type' => $a->extra_type,
                'daily_rate' => (float) $a->price,
                'total_price' => round((float) $a->price * $totalDays, 2),
                'description' => $a->description,
                'max_quantity' => (int) ($a->quantity ?: 1),
            ])->values()->toArray();

            // Vehicle-specific addons
            foreach ($vehicle->addons as $addon) {
                $extras[] = [
                    'id' => $addon->id,
                    'name' => $addon->extra_name,
                    'type' => $addon->extra_type,
                    'daily_rate' => (float) $addon->price,
                    'total_price' => round((float) $addon->price * $totalDays, 2),
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
        })->values();

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
                'price_per_day' => (float) $addon->price,
                'max_quantity' => (int) ($addon->quantity ?? 1),
                'description' => $addon->description,
            ]);

        $insuranceOptions = VendorVehiclePlan::where('vendor_id', $vehicle->vendor_id)
            ->where(function ($q) use ($vehicle) {
                $q->where('vehicle_id', $vehicle->id)
                    ->orWhereNull('vehicle_id');
            })
            ->get()
            ->map(fn ($plan) => [
                'insurance_id' => $plan->id,
                'name' => $plan->plan_type ?? ($plan->plan?->name ?? 'Insurance'),
                'price_per_day' => (float) $plan->price,
                'description' => $plan->plan_description,
                'features' => $plan->features ?? [],
            ]);

        return response()->json([
            'data' => [
                'extras' => $extras->values(),
                'insurance_options' => $insuranceOptions->values(),
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
            'driver.age' => ['required', 'integer', 'min:18'],
            'driver.driving_license_number' => ['required', 'string', 'max:100'],
            'driver.driving_license_country' => ['required', 'string', 'max:10'],
            'extras' => ['nullable', 'array'],
            'extras.*.extra_id' => ['required_with:extras', 'integer'],
            'extras.*.quantity' => ['required_with:extras', 'integer', 'min:1'],
            'insurance_id' => ['nullable', 'integer'],
            'flight_number' => ['nullable', 'string', 'max:20'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
            'pickup_date' => ['required', 'date'],
            'pickup_time' => ['required', 'date_format:H:i'],
            'dropoff_date' => ['required', 'date', 'after_or_equal:pickup_date'],
            'dropoff_time' => ['required', 'date_format:H:i'],
        ]);

        $vehicle = Vehicle::with(['vendor', 'vendor.vendorProfile', 'images', 'vendorLocation', 'vendorProfileData'])->find($validated['vehicle_id']);

        if (! $vehicle || ! in_array($vehicle->status, Vehicle::searchableStatuses(), true)) {
            return response()->json([
                'error' => [
                    'code' => 'VEHICLE_UNAVAILABLE',
                    'message' => 'The selected vehicle is no longer available.',
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
            return response()->json([
                'error' => [
                    'code' => 'VEHICLE_UNAVAILABLE',
                    'message' => 'The vehicle is no longer available for the selected dates.',
                    'status' => 409,
                ],
            ], 409);
        }

        $pickupDate = Carbon::parse($validated['pickup_date']);
        $dropoffDate = Carbon::parse($validated['dropoff_date']);
        $dailyRate = (float) $vehicle->price_per_day;
        $totalDays = max(1, $pickupDate->diffInDays($dropoffDate));
        $basePrice = round($dailyRate * $totalDays, 2);

        $extrasTotal = 0;
        $extrasData = [];

        if (! empty($validated['extras'])) {
            foreach ($validated['extras'] as $extraInput) {
                $addon = VendorVehicleAddon::where('id', $extraInput['extra_id'])
                    ->where('vendor_id', $vehicle->vendor_id)
                    ->first();

                if ($addon) {
                    $unitPrice = (float) $addon->price;
                    $quantity = (int) $extraInput['quantity'];
                    $extraTotal = round($unitPrice * $quantity * $totalDays, 2);
                    $extrasTotal += $extraTotal;

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
        }

        $totalAmount = round($basePrice + $extrasTotal, 2);

        $primaryImage = $vehicle->images->first();
        $vehicleImage = $primaryImage ? ($primaryImage->image_url ?: $primaryImage->image_path) : null;
        $locationSnapshot = $this->internalBookingSnapshotService->buildForVehicle($vehicle, [
            'pickup_location' => $vehicle->vendorLocation?->name ?: ($vehicle->full_vehicle_address ?: $vehicle->location),
            'return_location' => $vehicle->vendorLocation?->name ?: ($vehicle->full_vehicle_address ?: $vehicle->location),
            'booking_currency' => 'EUR',
        ]);

        DB::beginTransaction();

        try {
            // Check if consumer is in sandbox mode
            $consumer = ApiConsumer::find($validated['api_consumer_id']);
            $isSandbox = $consumer && $consumer->isSandbox();

            $booking = ApiBooking::create([
                'booking_number' => ApiBooking::generateBookingNumber(),
                'api_consumer_id' => $validated['api_consumer_id'],
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

        if (in_array($booking->status, ['cancelled', 'completed'])) {
            return response()->json([
                'error' => [
                    'code' => 'BOOKING_NOT_CANCELLABLE',
                    'message' => "Booking cannot be cancelled. Current status: {$booking->status}.",
                    'status' => 422,
                ],
            ], 422);
        }

        $booking->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->input('reason'),
            'cancelled_at' => now(),
        ]);
        $this->dispatchBookingCancelledNotificationsAfterResponse($booking);

        return response()->json([
            'data' => [
                'booking_number' => $booking->booking_number,
                'status' => 'cancelled',
                'cancelled_at' => $booking->cancelled_at->toIso8601String(),
                'message' => 'Booking has been successfully cancelled.',
            ],
        ]);
    }
}
