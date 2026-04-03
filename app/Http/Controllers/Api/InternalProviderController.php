<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiBooking;
use App\Models\ApiBookingExtra;
use App\Models\ApiConsumer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VendorVehicleAddon;
use App\Models\VendorVehiclePlan;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class InternalProviderController extends Controller
{
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

        $locationVehicle = Vehicle::find($validated['pickup_location_id']);

        if (!$locationVehicle) {
            return response()->json([
                'error' => [
                    'code' => 'LOCATION_NOT_FOUND',
                    'message' => 'The specified pickup location was not found.',
                    'status' => 404,
                ],
            ], 404);
        }

        $dropoffLocationVehicle = !empty($validated['dropoff_location_id'])
            ? Vehicle::find($validated['dropoff_location_id'])
            : null;

        $vehicles = Vehicle::whereIn('status', ['active', 'available'])
            ->where('full_vehicle_address', $locationVehicle->full_vehicle_address)
            ->with(['vendor', 'vendor.vendorProfile', 'images', 'blockings', 'category'])
            ->get();

        $available = $vehicles->filter(function ($vehicle) use ($pickupDate, $dropoffDate) {
            $isBlocked = $vehicle->blockings->contains(function ($blocking) use ($pickupDate, $dropoffDate) {
                return $blocking->blocking_start_date <= $dropoffDate
                    && $blocking->blocking_end_date >= $pickupDate;
            });

            if ($isBlocked) {
                return false;
            }

            $hasRegularBooking = $vehicle->bookings()
                ->whereNotIn('booking_status', ['cancelled', 'rejected'])
                ->where(function ($q) use ($pickupDate, $dropoffDate) {
                    $q->where('pickup_date', '<=', $dropoffDate)
                      ->where('return_date', '>=', $pickupDate);
                })->exists();

            $hasApiBooking = ApiBooking::where('vehicle_id', $vehicle->id)
                ->whereNotIn('status', ['cancelled'])
                ->where('is_test', false)
                ->where(function ($q) use ($pickupDate, $dropoffDate) {
                    $q->where('pickup_date', '<=', $dropoffDate)
                      ->where('return_date', '>=', $pickupDate);
                })->exists();

            return !$hasRegularBooking && !$hasApiBooking;
        });

        $pickupLocationName = $locationVehicle->full_vehicle_address ?: $locationVehicle->location;
        $dropoffLocationName = $dropoffLocationVehicle
            ? ($dropoffLocationVehicle->full_vehicle_address ?: $dropoffLocationVehicle->location)
            : $pickupLocationName;

        $results = $available->map(function ($vehicle) use (
            $totalDays, $currency, $pickupLocationName, $dropoffLocationName
        ) {
            $dailyRate = (float) $vehicle->price_per_day;
            $totalPrice = round($dailyRate * $totalDays, 2);

            $primaryImage = $vehicle->images->first();
            $image = $primaryImage ? ($primaryImage->image_url ?: $primaryImage->image_path) : null;

            $images = $vehicle->images->map(function ($img) {
                return $img->image_url ?: $img->image_path;
            })->values()->toArray();

            $vendorName = $vehicle->vendor?->vendorProfile?->company_name
                ?? $vehicle->vendor?->name
                ?? 'Unknown';

            return [
                'id' => $vehicle->id,
                'name' => trim($vehicle->brand . ' ' . $vehicle->model),
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'year' => $vehicle->registration_date ? Carbon::parse($vehicle->registration_date)->year : null,
                'category' => $vehicle->category?->name ?? 'Standard',
                'transmission' => $vehicle->transmission,
                'fuel_type' => $vehicle->fuel,
                'seats' => (int) $vehicle->seating_capacity,
                'doors' => (int) $vehicle->number_of_doors,
                'bags' => (int) $vehicle->luggage_capacity,
                'air_conditioning' => (bool) $vehicle->air_conditioning,
                'image' => $image,
                'images' => $images,
                'daily_rate' => $dailyRate,
                'total_price' => $totalPrice,
                'currency' => $currency,
                'total_days' => $totalDays,
                'pickup_location' => $pickupLocationName,
                'dropoff_location' => $dropoffLocationName,
                'vendor_name' => $vendorName,
                'features' => [],
                'mileage_policy' => $vehicle->limited_km ? $vehicle->limited_km . ' km' : 'unlimited',
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

        if (!$vehicle) {
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

        $vehicle = Vehicle::with(['vendor', 'vendor.vendorProfile', 'images'])->find($validated['vehicle_id']);

        if (!$vehicle || !in_array($vehicle->status, ['active', 'available'])) {
            return response()->json([
                'error' => [
                    'code' => 'VEHICLE_UNAVAILABLE',
                    'message' => 'The selected vehicle is no longer available.',
                    'status' => 409,
                ],
            ], 409);
        }

        $pickupDate = Carbon::parse($validated['pickup_date']);
        $dropoffDate = Carbon::parse($validated['dropoff_date']);

        $hasRegularBooking = $vehicle->bookings()
            ->whereNotIn('booking_status', ['cancelled', 'rejected'])
            ->where(function ($q) use ($pickupDate, $dropoffDate) {
                $q->where('pickup_date', '<=', $dropoffDate)
                  ->where('return_date', '>=', $pickupDate);
            })->exists();

        $hasApiBooking = ApiBooking::where('vehicle_id', $vehicle->id)
            ->whereNotIn('status', ['cancelled'])
            ->where('is_test', false)
            ->where(function ($q) use ($pickupDate, $dropoffDate) {
                $q->where('pickup_date', '<=', $dropoffDate)
                  ->where('return_date', '>=', $pickupDate);
            })->exists();

        if ($hasRegularBooking || $hasApiBooking) {
            return response()->json([
                'error' => [
                    'code' => 'VEHICLE_UNAVAILABLE',
                    'message' => 'The vehicle is no longer available for the selected dates.',
                    'status' => 409,
                ],
            ], 409);
        }

        $dailyRate = (float) $vehicle->price_per_day;
        $totalDays = max(1, $pickupDate->diffInDays($dropoffDate));
        $basePrice = round($dailyRate * $totalDays, 2);

        $extrasTotal = 0;
        $extrasData = [];

        if (!empty($validated['extras'])) {
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

        DB::beginTransaction();

        try {
            // Check if consumer is in sandbox mode
            $consumer = ApiConsumer::find($validated['api_consumer_id']);
            $isSandbox = $consumer && $consumer->isSandbox();

            $booking = ApiBooking::create([
                'booking_number' => ApiBooking::generateBookingNumber(),
                'api_consumer_id' => $validated['api_consumer_id'],
                'vehicle_id' => $vehicle->id,
                'vehicle_name' => trim($vehicle->brand . ' ' . $vehicle->model),
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
                'pickup_location' => $vehicle->full_vehicle_address ?: $vehicle->location,
                'return_location' => $vehicle->full_vehicle_address ?: $vehicle->location,
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
            ]);

            foreach ($extrasData as $extra) {
                ApiBookingExtra::create(array_merge($extra, [
                    'api_booking_id' => $booking->id,
                ]));
            }

            DB::commit();

            $booking->load('extras');

            // Notify vendor only — driver gets email when vendor confirms
            // Skip notifications for sandbox/test bookings
            if (!$isSandbox) {
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
                    'pickup_location' => $booking->pickup_location,
                    'return_location' => $booking->return_location,
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

        if (!$apiConsumerId) {
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
            ->with('extras')
            ->first();

        if (!$booking) {
            return response()->json([
                'error' => [
                    'code' => 'BOOKING_NOT_FOUND',
                    'message' => 'The specified booking was not found.',
                    'status' => 404,
                ],
            ], 404);
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
                'pickup_location' => $booking->pickup_location,
                'return_location' => $booking->return_location,
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

        if (!$apiConsumerId) {
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

        if (!$booking) {
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

        // Send notifications
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
