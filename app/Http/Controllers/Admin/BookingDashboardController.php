<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VendorProfile;
use App\Notifications\Booking\BookingCancelledCustomerNotification;
use App\Notifications\Booking\BookingCancelledNotification;
use App\Services\ProviderBookingCancellationService;
use App\Services\VrooemGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class BookingDashboardController extends Controller
{
    public function index(Request $request)
    {
        return $this->getBookings($request, 'all');
    }

    public function pending(Request $request)
    {
        return $this->getBookings($request, 'pending');
    }

    public function confirmed(Request $request)
    {
        return $this->getBookings($request, 'confirmed');
    }

    public function completed(Request $request)
    {
        return $this->getBookings($request, 'completed');
    }

    public function cancelled(Request $request)
    {
        return $this->getBookings($request, 'cancelled');
    }

    /**
     * Admin cancels a booking (internal or external provider).
     */
    public function cancelBooking(Request $request, $id)
    {
        $validated = $request->validate([
            'cancellation_reason' => 'required|string|min:3|max:500',
        ]);

        $booking = Booking::with(['customer', 'vehicle', 'amounts'])->findOrFail($id);

        if ($booking->booking_status === 'cancelled') {
            return back()->with('error', 'Booking is already cancelled.');
        }

        $customer = $booking->customer;
        $vehicle = $booking->vehicle_id ? Vehicle::find($booking->vehicle_id) : null;
        $reason = $validated['cancellation_reason'];

        $result = app(ProviderBookingCancellationService::class)->cancel($booking->id, $reason, 'Admin');
        if (! ($result['success'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'Booking cancellation failed.');
        }
        $booking = $result['booking'];

        // Send notifications
        $this->sendCancellationNotifications($booking, $customer, $vehicle, $reason);

        return back()->with('success', 'Booking #'.$booking->booking_number.' cancelled successfully.');
    }

    /**
     * Admin manually re-queues the supplier reservation for a booking stuck
     * without one. The 15-minute sweep is the automatic path; this is the
     * on-demand version for the rescue queue, and the only retry available
     * once a booking is flagged reservation_manual_check.
     */
    public function retryReservation(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        if (! empty($booking->provider_booking_ref)) {
            return back()->with('error', 'Booking #'.$booking->booking_number.' already has a supplier reservation.');
        }
        if (! $booking->provider_source || strtolower($booking->provider_source) === 'internal') {
            return back()->with('error', 'Internal bookings have no supplier reservation to retry.');
        }
        if (in_array($booking->booking_status, ['cancelled', 'rejected', 'expired', 'completed'], true)) {
            return back()->with('error', 'Booking #'.$booking->booking_number.' is '.$booking->booking_status.' — not eligible for a reservation retry.');
        }
        if (! in_array($booking->payment_status, ['partial', 'paid'], true)) {
            return back()->with('error', 'Booking #'.$booking->booking_number.' is not paid — a supplier reservation cannot be created.');
        }

        // An unknown outcome means the supplier MAY already hold this
        // reservation (the confirmation timed out before the reference was
        // stored). Blind redispatch would book a second car — the admin must
        // confirm they checked the supplier portal first.
        $outcomeUnknown = ! empty($booking->provider_metadata['reservation_manual_check'])
            || ! empty($booking->provider_metadata['reservation_unknown_at']);
        if ($outcomeUnknown && ! $request->boolean('supplier_checked')) {
            return back()->with('error', 'The supplier may already hold this reservation (the outcome was unknown). Check the supplier portal for booking #'.$booking->booking_number.' first, then confirm the retry.');
        }

        $metadata = app(\App\Services\StripeBookingService::class)->recoverReservationMetadata($booking);
        if ($metadata === null) {
            return back()->with('error', 'Checkout metadata could not be recovered for booking #'.$booking->booking_number.'. Rebook manually via the supplier portal.');
        }

        $updates = [
            'provider_metadata' => array_merge($booking->provider_metadata ?? [], [
                'manual_retry_at' => now()->toIso8601String(),
                'manual_retry_by' => $request->user()?->id,
                'reservation_manual_check' => false,
            ]),
        ];
        // An explicit admin retry un-fails the booking: the reservation job
        // refuses to reserve for a reservation_failed status (by design).
        if ($booking->booking_status === 'reservation_failed') {
            $updates['booking_status'] = 'confirmed';
        }
        $booking->update($updates);

        \App\Jobs\TriggerProviderReservationJob::dispatch($booking->id, $metadata);

        Log::info('Admin queued a manual reservation retry', [
            'booking_id' => $booking->id,
            'admin_id' => $request->user()?->id,
        ]);

        return back()->with('success', 'Reservation retry queued for booking #'.$booking->booking_number.'.');
    }

    /**
     * Call the provider's cancellation API if applicable.
     * Returns an error message string on failure, or null on success.
     */
    private function cancelWithProvider(Booking $booking, ?string $providerSource, string $reason): ?string
    {
        if (! $providerSource || $providerSource === 'internal') {
            return null;
        }

        $bookingRef = $booking->provider_booking_ref;
        $providerMetadata = $booking->provider_metadata ?? [];
        $gatewayBookingId = (string) ($providerMetadata['gateway_booking_id'] ?? '');
        $gatewaySupplierId = (string) ($providerMetadata['gateway_supplier_id'] ?? $this->mapProviderSourceToGatewaySupplierId($providerSource));

        // No supplier reservation reference — nothing to cancel upstream, and
        // blocking here made exactly the broken bookings permanently
        // uncancellable. Safe against an in-flight retry: the reservation job
        // skips bookings whose status is no longer eligible. Caveat: after an
        // UNKNOWN outcome the supplier may hold a reservation we never got the
        // reference for — the note directs the admin to verify at the portal.
        if (empty($bookingRef)) {
            $outcomeUnknown = ! empty($providerMetadata['reservation_manual_check'])
                || ! empty($providerMetadata['reservation_unknown_at']);
            $booking->notes = trim(($booking->notes ? $booking->notes."\n" : '')
                .($outcomeUnknown
                    ? 'Admin close-out: cancelled without a supplier call. WARNING: the reservation outcome was unknown — verify at the supplier portal that no reservation exists, and cancel it there if it does.'
                    : 'Admin close-out: cancelled without a supplier call (no provider reservation existed).'));

            return null;
        }

        if ($gatewayBookingId === '' || $gatewaySupplierId === '') {
            return 'Provider gateway cancellation metadata is missing.';
        }

        try {
            $service = app(VrooemGatewayService::class);
            $response = $service->cancelBooking($gatewayBookingId, $gatewaySupplierId, (string) $bookingRef, $reason);

            if ($response === null) {
                return 'Failed to cancel reservation with provider gateway.';
            }

            $booking->notes = trim(($booking->notes ? $booking->notes."\n" : '').'Gateway Cancel: cancellation requested.');
        } catch (\Exception $e) {
            Log::error('Admin cancel - provider cancellation failed', [
                'booking_id' => $booking->id,
                'provider' => $providerSource,
                'gateway_booking_id' => $gatewayBookingId,
                'gateway_supplier_id' => $gatewaySupplierId,
                'error' => $e->getMessage(),
            ]);

            return 'Provider cancellation failed: '.$e->getMessage();
        }

        return null;
    }

    private function mapProviderSourceToGatewaySupplierId(?string $providerSource): string
    {
        return match ($providerSource) {
            'greenmotion' => 'green_motion',
            'usave' => 'usave',
            'adobe' => 'adobe_car',
            'okmobility' => 'ok_mobility',
            'recordgo' => 'recordgo',
            default => (string) ($providerSource ?? ''),
        };
    }

    /**
     * Send cancellation notifications to customer, vendor, company, and admin.
     */
    private function sendCancellationNotifications(Booking $booking, ?Customer $customer, ?Vehicle $vehicle, string $reason): void
    {
        try {
            // Notify Customer (email + DB notification)
            if ($customer) {
                $customerUser = $customer->user_id ? User::find($customer->user_id) : null;
                if ($customerUser) {
                    $customerUser->notify(new BookingCancelledCustomerNotification($booking, $customer, $vehicle, $reason));
                } else {
                    Notification::route('mail', $customer->email)
                        ->notify(new BookingCancelledCustomerNotification($booking, $customer, $vehicle, $reason));
                }
            }

            // Notify Admin (DB notification for in-app dashboard)
            $adminEmail = config('admin.email');
            $admin = User::where('email', $adminEmail)->first();
            if ($admin) {
                $admin->notify(new BookingCancelledNotification($booking, $customer, $vehicle, 'admin'));
            }

            // Notify Vendor
            if ($vehicle) {
                $vendor = User::find($vehicle->vendor_id);
                if ($vendor) {
                    $vendor->notify(new BookingCancelledNotification($booking, $customer, $vehicle, 'vendor'));
                }

                // Notify Company
                $vendorProfile = VendorProfile::where('user_id', $vehicle->vendor_id)->first();
                if ($vendorProfile && $vendorProfile->company_email) {
                    $companyUser = User::where('email', $vendorProfile->company_email)->first();
                    if ($companyUser) {
                        $companyUser->notify(new BookingCancelledNotification($booking, $customer, $vehicle, 'company'));
                    } else {
                        Notification::route('mail', $vendorProfile->company_email)
                            ->notify(new BookingCancelledNotification($booking, $customer, $vehicle, 'company'));
                    }
                }
            }
        } catch (\Exception $e) {
            // Mail/notification failures (e.g. Mailtrap billing) must not block the cancellation
            Log::warning('Admin cancel - notifications failed (booking still cancelled)', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getBookings(Request $request, $status)
    {
        $search = $request->query('search');
        $statusFilter = $request->query('status', $status); // Get status from query or use method status

        $bookings = Booking::when($search, function ($query) use ($search) {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('plan', 'like', "%{$search}%")
                    ->orWhere('booking_number', 'like', "%{$search}%")
                    ->orWhere('vehicle_name', 'like', "%{$search}%")
                    ->orWhere('provider_source', 'like', "%{$search}%")
                    ->orWhere('provider_vehicle_id', 'like', "%{$search}%")
                    ->orWhere('provider_booking_ref', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('vehicle', function ($vehicleQuery) use ($search) {
                        $vehicleQuery->where('brand', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%")
                            ->orWhere('color', 'like', "%{$search}%");
                    })
                    ->orWhereHas('vehicle.vendorProfileData', function ($vendorQuery) use ($search) {
                        $vendorQuery->where('company_name', 'like', "%{$search}%")
                            ->orWhere('company_email', 'like', "%{$search}%");
                    });
            });
        });

        if ($statusFilter === 'provider_pending') {
            $bookings
                ->whereNotNull('provider_source')
                ->where('provider_source', '!=', 'internal')
                ->whereNull('provider_booking_ref')
                ->whereIn('booking_status', ['pending', 'confirmed']);
        } elseif ($statusFilter === 'rescue') {
            // Everything the rescue banner counts — union of all problem states.
            $bookings->where(function ($query) {
                $query->whereIn('booking_status', ['reservation_failed', 'rejected'])
                    ->orWhere('payment_status', 'refund_pending')
                    ->orWhere('provider_metadata->needs_correction', true)
                    ->orWhere(function ($providerQuery) {
                        $providerQuery->whereNotNull('provider_source')
                            ->where('provider_source', '!=', 'internal')
                            ->whereNull('provider_booking_ref')
                            ->whereIn('booking_status', ['pending', 'confirmed']);
                    });
            });
        } elseif ($statusFilter === 'refund_pending') {
            $bookings->where('payment_status', 'refund_pending');
        } elseif ($statusFilter === 'needs_correction') {
            $bookings->where('provider_metadata->needs_correction', true);
        } elseif ($statusFilter !== 'all') { // Filter by status if not 'all'
            $bookings->where('booking_status', $statusFilter);
        }

        $bookings = $bookings->orderBy('created_at', 'desc')
            ->with(['customer', 'vehicle.vendorProfileData', 'payments', 'vendorProfile', 'amounts'])
            ->paginate(7);

        $bookings->through(function (Booking $booking) {
            $booking->trip_from_date = optional($booking->pickup_date)->toDateString();
            $booking->trip_from_time = $booking->pickup_time;
            $booking->trip_to_date = optional($booking->return_date)->toDateString();
            $booking->trip_to_time = $booking->return_time;

            return $booking;
        });

        // Get booking status counts
        $statusCounts = [
            'total' => Booking::count(),
            'pending' => Booking::where('booking_status', 'pending')->count(),
            'confirmed' => Booking::where('booking_status', 'confirmed')->count(),
            'completed' => Booking::where('booking_status', 'completed')->count(),
            'cancelled' => Booking::where('booking_status', 'cancelled')->count(),
            'reservation_failed' => Booking::where('booking_status', 'reservation_failed')->count(),
            'rejected' => Booking::where('booking_status', 'rejected')->count(),
            'expired' => Booking::where('booking_status', 'expired')->count(),
            'refund_pending' => Booking::where('payment_status', 'refund_pending')->count(),
            'needs_correction' => Booking::where('provider_metadata->needs_correction', true)->count(),
            'provider_pending' => Booking::whereNotNull('provider_source')
                ->where('provider_source', '!=', 'internal')
                ->whereNull('provider_booking_ref')
                ->whereIn('booking_status', ['pending', 'confirmed'])
                ->count(),
            'rescue_total' => Booking::where(function ($query) {
                $query->whereIn('booking_status', ['reservation_failed', 'rejected'])
                    ->orWhere('payment_status', 'refund_pending')
                    ->orWhere('provider_metadata->needs_correction', true)
                    ->orWhere(function ($providerQuery) {
                        $providerQuery->whereNotNull('provider_source')
                            ->where('provider_source', '!=', 'internal')
                            ->whereNull('provider_booking_ref')
                            ->whereIn('booking_status', ['pending', 'confirmed']);
                    });
            })->count(),
        ];

        return Inertia::render('AdminDashboardPages/Bookings/Index', [
            'users' => $bookings,
            'statusCounts' => $statusCounts,
            'filters' => $request->only(['search', 'status']), // Include status filter
            'currentStatus' => $statusFilter, // Pass current status to the view
            'flash' => session()->only(['success', 'error']),
        ]);
    }

    public function show(Booking $booking)
    {
        $booking->load(['customer', 'vehicle.vendorProfileData', 'payments', 'amounts', 'extras', 'offers']);
        $booking->trip_from_date = optional($booking->pickup_date)->toDateString();
        $booking->trip_from_time = $booking->pickup_time;
        $booking->trip_to_date = optional($booking->return_date)->toDateString();
        $booking->trip_to_time = $booking->return_time;

        return Inertia::render('AdminDashboardPages/Bookings/Show', [
            'booking' => $booking,
            'flash' => session()->only(['success', 'error']),
        ]);
    }
}
