<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\CountryCodeResolver;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InternalVehicleController extends Controller
{
    /**
     * Return raw internal fleet vehicles for a grouped internal location.
     * This endpoint is consumed by the gateway's internal adapter.
     *
     * @throws ValidationException
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => ['required'],
            'pickup_date' => ['required', 'date'],
            'dropoff_date' => ['required', 'date', 'after_or_equal:pickup_date'],
            'pickup_time' => ['required', 'date_format:H:i'],
            'dropoff_time' => ['required', 'date_format:H:i'],
            'driver_age' => ['nullable', 'integer'],
        ]);

        $referenceVehicle = Vehicle::query()
            ->whereKey($validated['location_id'])
            ->whereIn('status', Vehicle::searchableStatuses())
            ->first();

        if (!$referenceVehicle) {
            return response()->json(['data' => []]);
        }

        $pickupDayOfWeek = Carbon::parse($validated['pickup_date'])->dayOfWeekIso - 1;
        $dropoffDayOfWeek = Carbon::parse($validated['dropoff_date'])->dayOfWeekIso - 1;

        $vehicles = Vehicle::query()
            ->whereIn('status', Vehicle::searchableStatuses())
            ->where('full_vehicle_address', $referenceVehicle->full_vehicle_address)
            ->where('location', $referenceVehicle->location)
            ->where('location_type', $referenceVehicle->location_type)
            ->where('city', $referenceVehicle->city)
            ->where('country', $referenceVehicle->country)
            ->when(
                $referenceVehicle->state === null,
                fn ($query) => $query->whereNull('state'),
                fn ($query) => $query->where('state', $referenceVehicle->state)
            )
            ->whereRaw('ROUND(latitude, 6) = ?', [round((float) $referenceVehicle->latitude, 6)])
            ->whereRaw('ROUND(longitude, 6) = ?', [round((float) $referenceVehicle->longitude, 6)])
            ->whereDoesntHave('bookings', function ($query) use ($validated) {
                $query->where(function ($nested) use ($validated) {
                    $nested->whereBetween('bookings.pickup_date', [$validated['pickup_date'], $validated['dropoff_date']])
                        ->orWhereBetween('bookings.return_date', [$validated['pickup_date'], $validated['dropoff_date']])
                        ->orWhere(function ($overlap) use ($validated) {
                            $overlap->where('bookings.pickup_date', '<=', $validated['pickup_date'])
                                ->where('bookings.return_date', '>=', $validated['dropoff_date']);
                        });
                });
            })
            ->whereDoesntHave('blockings', function ($query) use ($validated) {
                $query->where(function ($nested) use ($validated) {
                    $nested->whereBetween('blocking_start_date', [$validated['pickup_date'], $validated['dropoff_date']])
                        ->orWhereBetween('blocking_end_date', [$validated['pickup_date'], $validated['dropoff_date']])
                        ->orWhere(function ($overlap) use ($validated) {
                            $overlap->where('blocking_start_date', '<=', $validated['pickup_date'])
                                ->where('blocking_end_date', '>=', $validated['dropoff_date']);
                        });
                });
            })
            ->whereHas('operatingHours', function ($query) use ($pickupDayOfWeek, $validated) {
                $query->where('day_of_week', $pickupDayOfWeek)
                    ->where('is_open', true)
                    ->where('open_time', '<=', $validated['pickup_time'])
                    ->where('close_time', '>=', $validated['pickup_time']);
            })
            ->whereHas('operatingHours', function ($query) use ($dropoffDayOfWeek, $validated) {
                $query->where('day_of_week', $dropoffDayOfWeek)
                    ->where('is_open', true)
                    ->where('open_time', '<=', $validated['dropoff_time'])
                    ->where('close_time', '>=', $validated['dropoff_time']);
            })
            ->with(['vendor.profile', 'vendor.vendorProfile', 'vendorProfileData', 'benefits', 'images'])
            ->get()
            ->map(fn (Vehicle $vehicle) => $this->transformVehicle($vehicle));

        return response()->json(['data' => $vehicles->values()]);
    }

    private function transformVehicle(Vehicle $vehicle): array
    {
        $profile = $vehicle->vendor?->profile;
        $vendorProfileData = $vehicle->vendorProfileData ?: $vehicle->vendor?->vendorProfile;
        $benefits = $vehicle->benefits;

        $cancellation = '';
        if ($benefits && (int) ($benefits->cancellation_available_per_day ?? 0) === 1) {
            $days = (int) ($benefits->cancellation_available_per_day_date ?? 0);
            $cancellation = $days > 0
                ? "Free cancellation up to {$days} days before pickup"
                : 'Free cancellation available';
        }

        $features = $vehicle->features;
        if (is_string($features)) {
            $decoded = json_decode($features, true);
            $features = is_array($decoded) ? $decoded : [];
        }

        $paymentMethods = $vehicle->payment_method;
        if (is_string($paymentMethods)) {
            $decoded = json_decode($paymentMethods, true);
            $paymentMethods = is_array($decoded) ? $decoded : [$paymentMethods];
        }

        $vendorProfilePayload = [
            'company_name' => $vendorProfileData?->company_name,
            'company_email' => $vendorProfileData?->company_email,
            'company_phone_number' => $vendorProfileData?->company_phone_number,
            'company_address' => $vendorProfileData?->company_address,
            'currency' => $profile?->currency,
        ];

        return [
            'id' => $vehicle->id,
            'vendor_id' => $vehicle->vendor_id,
            'category_id' => $vehicle->category_id,
            'brand' => $vehicle->brand,
            'model' => $vehicle->model,
            'transmission' => $vehicle->transmission,
            'fuel' => $vehicle->fuel,
            'seating_capacity' => $vehicle->seating_capacity,
            'doors' => $vehicle->number_of_doors,
            'latitude' => $vehicle->latitude !== null ? (float) $vehicle->latitude : null,
            'longitude' => $vehicle->longitude !== null ? (float) $vehicle->longitude : null,
            'location' => $vehicle->full_vehicle_address ?: $vehicle->location,
            'security_deposit' => $vehicle->security_deposit !== null ? (float) $vehicle->security_deposit : null,
            'price_per_day' => $vehicle->price_per_day !== null ? (float) $vehicle->price_per_day : null,
            'price_per_week' => $vehicle->price_per_week !== null ? (float) $vehicle->price_per_week : null,
            'price_per_month' => $vehicle->price_per_month !== null ? (float) $vehicle->price_per_month : null,
            'features' => is_array($features) ? array_values($features) : [],
            'payment_method' => is_array($paymentMethods) ? array_values($paymentMethods) : [],
            'vendor' => [
                'profile' => [
                    'city' => $profile?->city ?: $vehicle->city,
                    'country_code' => CountryCodeResolver::resolve($profile?->country ?: $vehicle->country),
                    'company_name' => $vendorProfileData?->company_name,
                ],
            ],
            'vendorProfileData' => $vendorProfilePayload,
            'vendor_profile_data' => $vendorProfilePayload,
            'benefits' => [
                'km_per_day' => $benefits && (int) ($benefits->limited_km_per_day ?? 0) === 1
                    ? (int) ($benefits->limited_km_per_day_range ?? 0)
                    : null,
                'km_per_month' => $benefits && (int) ($benefits->limited_km_per_month ?? 0) === 1
                    ? (int) ($benefits->limited_km_per_month_range ?? 0)
                    : null,
                'min_driver_age' => $benefits?->minimum_driver_age !== null ? (int) $benefits->minimum_driver_age : null,
                'cancellation' => $cancellation,
                'price_per_extra_km' => $benefits?->price_per_km_per_day !== null ? (float) $benefits->price_per_km_per_day : null,
            ],
            'images' => $vehicle->images->map(function ($image) {
                return [
                    'image_type' => $image->image_type,
                    'image_url' => $image->image_url,
                ];
            })->values()->all(),
        ];
    }
}
