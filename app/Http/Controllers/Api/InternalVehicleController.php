<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VendorLocation;
use App\Models\Vehicle;
use App\Services\CountryCodeResolver;
use App\Services\Vehicles\InternalVehicleAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InternalVehicleController extends Controller
{
    public function __construct(
        private readonly InternalVehicleAvailabilityService $internalVehicleAvailabilityService,
    ) {
    }

    /**
     * Return raw internal fleet vehicles for a grouped internal location.
     * This endpoint is consumed by the gateway's internal adapter.
     *
     * @throws ValidationException
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'integer'],
            'pickup_date' => ['required', 'date'],
            'dropoff_date' => ['required', 'date', 'after_or_equal:pickup_date'],
            'pickup_time' => ['required', 'date_format:H:i'],
            'dropoff_time' => ['required', 'date_format:H:i'],
            'driver_age' => ['nullable', 'integer'],
        ]);

        $locationId = (int) $validated['location_id'];
        $referenceLocation = VendorLocation::query()
            ->whereKey($locationId)
            ->where('is_active', true)
            ->first();

        $referenceVehicle = null;
        if (!$referenceLocation) {
            $referenceVehicle = Vehicle::query()
                ->whereKey($locationId)
                ->whereIn('status', Vehicle::searchableStatuses())
                ->first();
        }

        if (!$referenceLocation && !$referenceVehicle) {
            return response()->json(['data' => []]);
        }

        $vehicleQuery = Vehicle::query()
            ->with(['vendor.profile', 'vendor.vendorProfile', 'vendorProfileData', 'benefits', 'images', 'vendorLocation'])
            ->when(
                $referenceLocation !== null,
                fn ($query) => $query->where('vendor_location_id', $referenceLocation->id),
                function ($query) use ($referenceVehicle) {
                    return $query
                        ->where('full_vehicle_address', $referenceVehicle->full_vehicle_address)
                        ->where('location', $referenceVehicle->location)
                        ->where('location_type', $referenceVehicle->location_type)
                        ->where('city', $referenceVehicle->city)
                        ->where('country', $referenceVehicle->country)
                        ->when(
                            $referenceVehicle->state === null,
                            fn ($innerQuery) => $innerQuery->whereNull('state'),
                            fn ($innerQuery) => $innerQuery->where('state', $referenceVehicle->state)
                        )
                        ->whereRaw('ROUND(latitude, 6) = ?', [round((float) $referenceVehicle->latitude, 6)])
                        ->whereRaw('ROUND(longitude, 6) = ?', [round((float) $referenceVehicle->longitude, 6)]);
                }
            );

        $this->internalVehicleAvailabilityService->apply($vehicleQuery, [
            'pickup_date' => $validated['pickup_date'],
            'pickup_time' => $validated['pickup_time'],
            'dropoff_date' => $validated['dropoff_date'],
            'dropoff_time' => $validated['dropoff_time'],
        ]);

        $vehicles = $vehicleQuery
            ->get()
            ->map(fn (Vehicle $vehicle) => $this->transformVehicle($vehicle));

        return response()->json(['data' => $vehicles->values()]);
    }

    private function transformVehicle(Vehicle $vehicle): array
    {
        $vendor = $vehicle->vendor;
        $profile = $vendor ? $vendor->profile : null;
        $vendorProfileData = $vehicle->vendorProfileData ?: ($vendor ? $vendor->vendorProfile : null);
        $benefits = $vehicle->benefits;
        $canonicalLocation = $vehicle->vendorLocation;

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
            'vendor_location_id' => $vehicle->vendor_location_id,
            'category_id' => $vehicle->category_id,
            'brand' => $vehicle->brand,
            'model' => $vehicle->model,
            'transmission' => $vehicle->transmission,
            'fuel' => $vehicle->fuel,
            'seating_capacity' => $vehicle->seating_capacity,
            'doors' => $vehicle->number_of_doors,
            'latitude' => $canonicalLocation && $canonicalLocation->latitude !== null ? (float) $canonicalLocation->latitude : ($vehicle->latitude !== null ? (float) $vehicle->latitude : null),
            'longitude' => $canonicalLocation && $canonicalLocation->longitude !== null ? (float) $canonicalLocation->longitude : ($vehicle->longitude !== null ? (float) $vehicle->longitude : null),
            'location' => $canonicalLocation && $canonicalLocation->name ? $canonicalLocation->name : ($vehicle->full_vehicle_address ?: $vehicle->location),
            'location_type' => $canonicalLocation?->location_type ?: $vehicle->location_type,
            'location_phone' => $canonicalLocation?->phone ?: $vehicle->location_phone,
            'pickup_instructions' => $canonicalLocation?->pickup_instructions ?: $vehicle->pickup_instructions,
            'dropoff_instructions' => $canonicalLocation?->dropoff_instructions ?: $vehicle->dropoff_instructions,
            'location_details' => [
                'name' => $canonicalLocation?->name ?: ($vehicle->full_vehicle_address ?: $vehicle->location),
                'location_type' => $canonicalLocation?->location_type ?: $vehicle->location_type,
                'iata_code' => $canonicalLocation?->iata_code,
                'telephone' => $canonicalLocation?->phone ?: $vehicle->location_phone,
                'address_1' => $canonicalLocation?->address_line_1 ?: $vehicle->full_vehicle_address,
                'address_2' => $canonicalLocation?->address_line_2,
                'address_city' => $canonicalLocation?->city ?: $vehicle->city,
                'address_county' => $canonicalLocation?->state ?: $vehicle->state,
                'address_country' => $canonicalLocation?->country ?: $vehicle->country,
                'latitude' => $canonicalLocation && $canonicalLocation->latitude !== null ? (float) $canonicalLocation->latitude : ($vehicle->latitude !== null ? (float) $vehicle->latitude : null),
                'longitude' => $canonicalLocation && $canonicalLocation->longitude !== null ? (float) $canonicalLocation->longitude : ($vehicle->longitude !== null ? (float) $vehicle->longitude : null),
                'pickup_instructions' => $canonicalLocation?->pickup_instructions ?: $vehicle->pickup_instructions,
                'dropoff_instructions' => $canonicalLocation?->dropoff_instructions ?: $vehicle->dropoff_instructions,
            ],
            'security_deposit' => $vehicle->security_deposit !== null ? (float) $vehicle->security_deposit : null,
            'price_per_day' => $vehicle->price_per_day !== null ? (float) $vehicle->price_per_day : null,
            'price_per_week' => $vehicle->price_per_week !== null ? (float) $vehicle->price_per_week : null,
            'price_per_month' => $vehicle->price_per_month !== null ? (float) $vehicle->price_per_month : null,
            'features' => is_array($features) ? array_values($features) : [],
            'payment_method' => is_array($paymentMethods) ? array_values($paymentMethods) : [],
            'vendor' => [
                'profile' => [
                    'city' => $canonicalLocation?->city ?: ($profile?->city ?: $vehicle->city),
                    'country_code' => $canonicalLocation?->country_code ?: CountryCodeResolver::resolve($profile?->country ?: $vehicle->country),
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
