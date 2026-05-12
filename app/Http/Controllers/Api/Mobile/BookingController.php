<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\StripeBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class BookingController extends Controller
{
    public function bySession(Request $request, StripeBookingService $bookingService): JsonResponse
    {
        $data = $request->validate([
            'session_id' => ['required', 'string', 'max:191'],
        ]);

        $booking = Booking::with(['vehicle.images', 'extras', 'amounts', 'payments'])
            ->where('stripe_session_id', $data['session_id'])
            ->first();

        // Fallback: webhook may not have run yet in dev, so we mirror the web
        // success page logic — fetch the session from Stripe and create the
        // booking on the fly if Stripe says the payment succeeded.
        if (! $booking) {
            try {
                Stripe::setApiKey(config('services.stripe.secret') ?: env('STRIPE_SECRET'));
                $session = StripeSession::retrieve($data['session_id']);

                if (($session->payment_status ?? null) !== 'paid') {
                    return response()->json([
                        'status' => 'pending',
                        'message' => 'Payment still processing.',
                    ], 202);
                }

                $booking = $bookingService->createBookingFromSession($session);
                $booking = Booking::with(['vehicle.images', 'extras', 'amounts', 'payments'])
                    ->find($booking->id);
            } catch (\Throwable $e) {
                Log::warning('Mobile bySession: stripe fallback failed', [
                    'session_id' => $data['session_id'],
                    'error' => $e->getMessage(),
                ]);
                return response()->json([
                    'status' => 'pending',
                    'message' => 'Confirming payment with Stripe…',
                ], 202);
            }
        }

        if (! $booking) {
            return response()->json([
                'status' => 'pending',
                'message' => 'Booking not yet finalized. Try again shortly.',
            ], 202);
        }

        // Verify ownership: booking's customer must belong to the user
        if ($booking->customer && $booking->customer->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Booking does not belong to this account.'], 403);
        }

        $paid = in_array((string) $booking->payment_status, ['paid', 'partial'], true);
        $bookingStatus = strtolower((string) ($booking->booking_status ?? ''));
        $confirmed = $paid || in_array($bookingStatus, ['confirmed', 'completed', 'active'], true);

        return response()->json([
            'status' => $confirmed ? 'confirmed' : 'pending',
            'booking' => $this->transform($booking),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $booking = Booking::with([
            'vehicle.images',
            'vehicle.category',
            'vehicle.vendorProfile.user',
            'vehicle.vendor',
            'vehicle.vendorLocation',
            'extras',
            'amounts',
            'payments',
            'customer',
            'vendorProfile.user',
        ])
            ->where('id', $id)
            ->whereHas('customer', fn ($q) => $q->where('user_id', $request->user()->id))
            ->first();

        if (! $booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        return response()->json([
            'booking' => $this->transform($booking),
        ]);
    }

    public function downloadReceipt(Request $request, int $id)
    {
        $booking = Booking::with(['payments', 'extras', 'customer', 'vendorProfile.user', 'amounts'])
            ->whereHas('customer', fn ($q) => $q->where('user_id', $request->user()->id))
            ->find($id);

        if (! $booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        if ($booking->vehicle_id) {
            $booking->load(['vehicle.images', 'vehicle.category', 'vehicle.vendorProfile.user', 'vehicle.vendor', 'vehicle.vendorLocation']);
        }

        $vehicleData = $booking->vehicle ?: (object) [
            'brand' => explode(' ', (string) $booking->vehicle_name)[0] ?? 'Vehicle',
            'model' => $booking->vehicle_name,
            'vehicle_name' => $booking->vehicle_name,
            'transmission' => 'Manual',
            'fuel' => 'Petrol',
            'seating_capacity' => 5,
            'images' => $booking->vehicle_image
                ? [['image_url' => $booking->vehicle_image, 'image_type' => 'primary']]
                : [],
            'category' => ['name' => 'Standard'],
        ];

        $vendorProfile = $booking->vehicle?->vendorProfile ?? $booking->vendorProfile;
        $vendorUser = $booking->vehicle?->vendor;
        $vendorCompany = null;
        if ($vendorUser) {
            $vendorCompany = \App\Models\VendorProfile::where('user_id', $vendorUser->id)->first();
        }

        $payment = $booking->payments->where('payment_status', 'succeeded')->first()
            ?? $booking->payments->first()
            ?? (object) [
                'payment_method' => $booking->stripe_payment_intent_id ? 'Stripe' : 'Card',
                'method' => $booking->stripe_payment_intent_id ? 'Stripe' : 'Card',
                'transaction_id' => $booking->stripe_payment_intent_id ?? $booking->stripe_session_id ?? 'N/A',
                'currency' => $booking->booking_currency ?? 'EUR',
                'amount' => $booking->amount_paid ?? $booking->total_amount,
                'payment_status' => $booking->payment_status ?? 'partial',
                'status' => $booking->payment_status ?? 'partial',
                'payment_date' => $booking->created_at,
            ];

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdfs.booking-receipt', [
            'booking' => $booking,
            'vehicle' => $vehicleData,
            'payment' => $payment,
            'vendorProfile' => $vendorProfile,
            'vendorUser' => $vendorUser,
            'vendorCompany' => $vendorCompany,
        ]);

        return $pdf->download("booking-{$booking->booking_number}.pdf");
    }

    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::with(['vehicle.images'])
            ->whereHas('customer', fn ($q) => $q->where('user_id', $request->user()->id))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'bookings' => $bookings->map(fn ($b) => $this->transformList($b))->values(),
        ]);
    }

    private function transform(Booking $b): array
    {
        $host = rtrim(request()->getSchemeAndHttpHost(), '/');
        $absolutize = function (?string $path) use ($host): ?string {
            if (! $path) return null;
            if (preg_match('#^https?://#i', $path)) return $path;
            return $host.'/'.ltrim($path, '/');
        };

        $primaryImage = null;
        $vehicleBrand = null;
        $vehicleModel = null;
        if ($b->vehicle && $b->vehicle->images) {
            $primary = $b->vehicle->images->firstWhere('image_type', 'primary') ?? $b->vehicle->images->first();
            if ($primary?->image_path) {
                $primaryImage = $absolutize('storage/'.ltrim($primary->image_path, '/'));
            }
            $vehicleBrand = $b->vehicle->brand ?? null;
            $vehicleModel = $b->vehicle->model ?? null;
        }
        if (! $vehicleBrand && $b->vehicle_name) {
            $parts = explode(' ', $b->vehicle_name, 2);
            $vehicleBrand = $parts[0] ?? null;
            $vehicleModel = $parts[1] ?? null;
        }
        if (! $primaryImage && $b->vehicle_image) {
            $primaryImage = $absolutize($b->vehicle_image);
        }

        $metadata = is_string($b->provider_metadata) ? (json_decode($b->provider_metadata, true) ?: []) : ($b->provider_metadata ?: []);
        $customer = $b->customer;
        $customerSnapshot = is_array($metadata['customer_snapshot'] ?? null) ? $metadata['customer_snapshot'] : [];

        // Build a human-readable address from customer_snapshot or profile
        $driverAddress = $customerSnapshot['address'] ?? null;
        if (! $driverAddress && $customer && $customer->user_id) {
            $profile = \App\Models\UserProfile::where('user_id', $customer->user_id)->first();
            if ($profile) {
                $parts = array_filter([
                    $profile->address_line1,
                    $profile->address_line2,
                    $profile->city,
                    $profile->postal_code,
                    $profile->country,
                ]);
                $driverAddress = $parts ? implode(', ', $parts) : null;
            }
        }

        // Mirror PDF blade fallback chain for deposit / insurance / policies
        $benefitsData = is_array($metadata['benefits'] ?? null) ? $metadata['benefits'] : [];
        $providerPricing = is_array($metadata['provider_pricing'] ?? null) ? $metadata['provider_pricing'] : [];
        $policiesData = is_array($metadata['policies'] ?? null) ? $metadata['policies'] : [];

        $depositAmount = $benefitsData['deposit_amount']
            ?? $metadata['deposit_amount']
            ?? $providerPricing['deposit_amount']
            ?? $metadata['deposit']
            ?? null;
        $securityDeposit = $benefitsData['security_deposit'] ?? null;
        $displayDeposit = $securityDeposit ?: $depositAmount;
        $depositCurrency = $benefitsData['deposit_currency']
            ?? $metadata['deposit_currency']
            ?? $providerPricing['deposit_currency']
            ?? $metadata['currency']
            ?? $b->booking_currency
            ?? 'EUR';
        $excessAmount = $benefitsData['excess_amount']
            ?? $metadata['excess_amount']
            ?? $providerPricing['excess_amount']
            ?? null;
        $excessTheftAmount = $benefitsData['excess_theft_amount']
            ?? $metadata['excess_theft_amount']
            ?? $providerPricing['excess_theft_amount']
            ?? null;
        $depositPaymentMethod = $benefitsData['deposit_payment_method'] ?? null;
        if (is_string($depositPaymentMethod)) {
            $decoded = json_decode($depositPaymentMethod, true);
            $depositPaymentMethod = is_array($decoded) ? $decoded : [$depositPaymentMethod];
        }

        $fuelPolicy = $policiesData['fuel_policy'] ?? $policiesData['fuelpolicy'] ?? $metadata['fuel_policy'] ?? null;

        $mileage = 'Unlimited';
        if (! empty($policiesData['limited_km_per_day'])) {
            $range = $policiesData['limited_km_per_day_range'] ?? null;
            $pricePerKm = $policiesData['price_per_km_per_day'] ?? null;
            $mileage = $range ? "{$range} km/day" : 'Limited';
            if ($pricePerKm) {
                $mileage .= " (+{$pricePerKm}/km)";
            }
        } elseif (isset($policiesData['unlimited_mileage']) && ! $policiesData['unlimited_mileage']) {
            $mileage = $policiesData['included_km'] ?? 'Limited';
        } elseif (! empty($policiesData['mileage']) && $policiesData['mileage'] !== 'Unlimited') {
            $mileage = $policiesData['mileage'];
        }

        $cancellation = 'Non-refundable';
        if (! empty($policiesData['cancellation_available_per_day'])) {
            $days = $policiesData['cancellation_available_per_day_date'] ?? null;
            $cancellation = $days ? "Free cancellation ({$days} days before)" : 'Free cancellation';
        }

        $minAge = $policiesData['minimum_driver_age'] ?? $metadata['min_age'] ?? null;

        // Resolve plan features + description (matches web logic)
        $planData = null;
        if ($b->plan) {
            $features = null;
            $description = null;

            if ($b->vehicle_id) {
                $vehiclePlan = \App\Models\VendorVehiclePlan::where('vehicle_id', $b->vehicle_id)
                    ->where('plan_type', $b->plan)
                    ->first();
                if ($vehiclePlan) {
                    $features = $vehiclePlan->features;
                    $description = $vehiclePlan->plan_description;
                }
            }
            if (empty($features)) {
                $globalPlan = \App\Models\Plan::where('plan_type', $b->plan)->first();
                if ($globalPlan) {
                    $features = $globalPlan->features;
                    $description = $description ?: $globalPlan->plan_description;
                }
            }
            if (is_string($features)) {
                $decoded = json_decode($features, true);
                $features = is_array($decoded) ? $decoded : null;
            }
            $planData = [
                'plan_type' => $b->plan,
                'plan_description' => $description,
                'features' => is_array($features) ? array_map(function ($f) {
                    if (is_string($f)) return $f;
                    if (is_array($f)) return $f['name'] ?? $f['label'] ?? $f['feature'] ?? null;
                    return null;
                }, $features) : [],
            ];
            $planData['features'] = array_values(array_filter($planData['features']));
        }

        $vendor = null;
        $vendorProfile = $b->vehicle?->vendorProfile ?? $b->vendorProfile;
        if ($vendorProfile) {
            if (! $vendorProfile->relationLoaded('user')) {
                $vendorProfile->load('user');
            }
            $vendor = [
                'company_name' => $vendorProfile->company_name ?? null,
                'company_email' => $vendorProfile->company_email ?? null,
                'company_phone' => $vendorProfile->company_phone_number ?? $vendorProfile->phone ?? null,
                'company_address' => $vendorProfile->company_address ?? null,
                'logo' => $vendorProfile->company_logo ?? null,
                'first_name' => $vendorProfile->user?->first_name ?? null,
                'last_name' => $vendorProfile->user?->last_name ?? null,
                'email' => $vendorProfile->user?->email ?? null,
                'phone' => $vendorProfile->user?->phone ?? null,
                'is_external' => false,
            ];
        } elseif ($b->provider_source && $b->provider_source !== 'internal') {
            // External supplier — synthesize vendor card from provider metadata
            $supplierName = (string) $b->provider_source;
            $companyName = ucwords(str_replace('_', ' ', $supplierName));
            $vendor = [
                'company_name' => $companyName,
                'company_email' => $metadata['supplier_email'] ?? $metadata['pickup_email'] ?? null,
                'company_phone' => $metadata['supplier_phone'] ?? $metadata['pickup_phone'] ?? $metadata['office_phone'] ?? null,
                'company_address' => $metadata['pickup_address'] ?? $metadata['office_address'] ?? null,
                'logo' => null,
                'first_name' => null,
                'last_name' => null,
                'email' => null,
                'phone' => null,
                'is_external' => true,
                'support_url' => $metadata['supplier_support_url'] ?? null,
            ];
        }

        $payment = null;
        if ($b->payments && $b->payments->isNotEmpty()) {
            $p = $b->payments->first();
            $payment = [
                'method' => $p->payment_method ?? $p->method ?? ($b->stripe_payment_intent_id ? 'Stripe' : 'Card'),
                'transaction_id' => $p->transaction_id ?? $b->stripe_payment_intent_id ?? $b->stripe_session_id,
                'amount' => $p->amount !== null ? (float) $p->amount : null,
                'currency' => $p->currency ?? $b->booking_currency ?? 'EUR',
                'status' => $p->payment_status ?? $p->status ?? $b->payment_status,
                'paid_at' => $p->payment_date ?? $p->created_at?->toIso8601String(),
            ];
        }

        return [
            'id' => $b->id,
            'booking_number' => $b->booking_number,
            'status' => $b->booking_status ?? $b->status ?? 'pending',
            'booking_status' => $b->booking_status,
            'payment_status' => $b->payment_status ?? null,
            'pickup_date' => $b->pickup_date,
            'pickup_time' => $b->pickup_time,
            'return_date' => $b->return_date,
            'return_time' => $b->return_time,
            'pickup_location' => $b->pickup_location,
            'return_location' => $b->return_location,
            'pickup_address' => $metadata['pickup_address'] ?? null,
            'return_address' => $metadata['dropoff_address'] ?? $metadata['return_address'] ?? null,
            'total_amount' => $b->total_amount !== null ? (float) $b->total_amount : null,
            'amount_paid' => $b->amount_paid !== null ? (float) $b->amount_paid : null,
            'amount_pending' => $b->pending_amount !== null ? (float) $b->pending_amount : null,
            'base_price' => $b->base_price !== null ? (float) $b->base_price : null,
            'extra_charges' => $b->extra_charges !== null ? (float) $b->extra_charges : 0.0,
            'tax_amount' => $b->tax_amount !== null ? (float) $b->tax_amount : 0.0,
            'currency' => $b->booking_currency ?? 'EUR',
            'total_days' => $b->total_days,
            'plan' => $b->plan,
            'driver_name' => $b->driver_name ?? ($customer ? trim(($customer->first_name ?? '').' '.($customer->last_name ?? '')) : null),
            'driver_email' => $b->driver_email ?? $customer?->email,
            'driver_phone' => $b->driver_phone ?? $customer?->phone,
            'driver_age' => $b->driver_age ?? ($customer->driver_age ?? null),
            'driver_license_number' => $customer->driver_license_number ?? null,
            'discount_code' => $b->discount_code,
            'discount_amount' => $b->discount_amount !== null ? (float) $b->discount_amount : 0,
            'stripe_session_id' => $b->stripe_session_id,
            'stripe_payment_intent_id' => $b->stripe_payment_intent_id,
            'security_deposit' => $displayDeposit,
            'security_deposit_currency' => $depositCurrency,
            'deposit_payment_methods' => is_array($depositPaymentMethod) ? array_values($depositPaymentMethod) : [],
            'excess_amount' => $excessAmount,
            'excess_theft_amount' => $excessTheftAmount,
            'mileage_policy' => $mileage,
            'fuel_policy' => $fuelPolicy,
            'cancellation_policy' => $cancellation,
            'minimum_driver_age' => $minAge,
            'flight_number' => $customerSnapshot['flight_number'] ?? $metadata['flight_number'] ?? ($customer->flight_number ?? null),
            'driver_address' => $driverAddress,
            'notes' => $b->notes ?? $customerSnapshot['notes'] ?? $metadata['notes'] ?? null,
            'paid_percentage' => $b->total_amount > 0 ? round(($b->amount_paid / $b->total_amount) * 100) : 0,
            'extras' => $b->extras?->map(fn ($e) => [
                'name' => $e->name ?? $e->extra_name ?? 'Extra',
                'quantity' => $e->quantity ?? 1,
                'amount' => $e->amount !== null ? (float) $e->amount : null,
                'currency' => $e->currency ?? $b->booking_currency,
            ])->values()->all() ?? [],
            'payment' => $payment,
            'vehicle' => [
                'id' => $b->vehicle?->id,
                'brand' => $vehicleBrand,
                'model' => $vehicleModel,
                'image' => $primaryImage,
                'transmission' => $b->vehicle?->transmission ?? $metadata['transmission'] ?? null,
                'fuel' => $b->vehicle?->fuel ?? $metadata['fuel'] ?? null,
                'seating_capacity' => $b->vehicle?->seating_capacity ?? $metadata['seating_capacity'] ?? null,
                'doors' => $b->vehicle?->number_of_doors ?? $metadata['doors'] ?? null,
                'category' => $b->vehicle?->category?->name ?? $metadata['category'] ?? null,
            ],
            'provider_source' => $b->provider_source ?? null,
            'provider_booking_ref' => $b->provider_booking_ref ?? null,
            'tax_breakdown' => is_array($metadata['tax_breakdown'] ?? null) ? $metadata['tax_breakdown'] : null,
            'included_services' => is_array($metadata['included_services'] ?? null) ? $metadata['included_services'] : [],
            'driver_policy' => is_array($metadata['driver_policy'] ?? null) ? $metadata['driver_policy'] : null,
            'cancellation_summary' => is_array($metadata['cancellation_summary'] ?? null) ? $metadata['cancellation_summary'] : null,
            'mandatory_amount' => is_numeric($metadata['mandatory_amount'] ?? null) ? (float) $metadata['mandatory_amount'] : 0.0,
            'highlights' => is_array($metadata['highlights'] ?? null) ? $metadata['highlights'] : null,
            'vendor' => $vendor,
            'plan_details' => $planData,
            'references' => [
                'booking_number' => $b->booking_number,
                'provider_booking_ref' => $b->provider_booking_ref,
                'stripe_session_id' => $b->stripe_session_id,
                'stripe_payment_intent_id' => $b->stripe_payment_intent_id,
                'provider_source' => $b->provider_source,
            ],
            'policies' => array_filter([
                'mileage' => $mileage,
                'fuel' => $fuelPolicy ? ucfirst((string) $fuelPolicy) : null,
                'cancellation' => $cancellation,
                'minimum_age' => $minAge ? "{$minAge}+ years" : null,
                'late_return' => $policiesData['late_return_policy'] ?? null,
                'damage' => $policiesData['damage_policy'] ?? null,
            ]),
            'pickup' => [
                'name' => $b->pickup_location ?? ($metadata['pickup_location_name'] ?? null),
                'address' => $metadata['pickup_address'] ?? $metadata['pickup_location'] ?? null,
                'instructions' => $metadata['pickup_instructions'] ?? null,
                'phone' => $metadata['pickup_phone'] ?? null,
                'office_hours' => $metadata['pickup_hours'] ?? $metadata['office_schedule'] ?? null,
                'date' => $b->pickup_date,
                'time' => $b->pickup_time,
            ],
            'dropoff' => [
                'name' => $b->return_location ?? $b->pickup_location ?? null,
                'address' => $metadata['dropoff_address'] ?? $metadata['return_address'] ?? $metadata['pickup_address'] ?? null,
                'instructions' => $metadata['dropoff_instructions'] ?? null,
                'phone' => $metadata['dropoff_phone'] ?? $metadata['pickup_phone'] ?? null,
                'office_hours' => $metadata['dropoff_hours'] ?? $metadata['office_schedule'] ?? null,
                'date' => $b->return_date,
                'time' => $b->return_time,
            ],
            'created_at' => $b->created_at?->toIso8601String(),
        ];
    }

    private function transformList(Booking $b): array
    {
        $host = rtrim(request()->getSchemeAndHttpHost(), '/');
        $absolutize = function (?string $path) use ($host): ?string {
            if (! $path) return null;
            if (preg_match('#^https?://#i', $path)) return $path;
            return $host.'/'.ltrim($path, '/');
        };

        $image = null;
        if ($b->vehicle?->images?->isNotEmpty()) {
            $primary = $b->vehicle->images->firstWhere('image_type', 'primary') ?? $b->vehicle->images->first();
            if ($primary?->image_path) {
                $image = $absolutize('storage/'.ltrim($primary->image_path, '/'));
            }
        }
        if (! $image && $b->vehicle_image) {
            $image = $absolutize($b->vehicle_image);
        }

        return [
            'id' => $b->id,
            'booking_number' => $b->booking_number,
            'status' => $b->booking_status ?? $b->status ?? 'pending',
            'payment_status' => $b->payment_status ?? null,
            'pickup_date' => $b->pickup_date,
            'return_date' => $b->return_date,
            'pickup_location' => $b->pickup_location,
            'total_amount' => $b->total_amount !== null ? (float) $b->total_amount : null,
            'currency' => $b->booking_currency ?? 'EUR',
            'stripe_session_id' => $b->stripe_session_id,
            'vehicle' => [
                'brand' => $b->vehicle?->brand ?? ($b->vehicle_name ? explode(' ', (string) $b->vehicle_name)[0] : null),
                'model' => $b->vehicle?->model ?? ($b->vehicle_name ? trim(substr((string) $b->vehicle_name, strpos((string) $b->vehicle_name, ' ') ?: 0)) : null),
                'image' => $image,
            ],
        ];
    }
}
