<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VendorProfile;
use App\Notifications\Booking\BookingCancelledNotification;
use App\Notifications\Booking\BookingCancelledCustomerNotification;
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

        // Attempt provider cancellation for external bookings
        $providerSource = $booking->provider_source ? strtolower($booking->provider_source) : null;
        $providerError = $this->cancelWithProvider($booking, $providerSource, $reason);

        if ($providerError) {
            return back()->with('error', $providerError);
        }

        // Update booking status
        $booking->booking_status = 'cancelled';
        $booking->cancellation_reason = $reason;
        $booking->save();

        // Update vehicle status for internal vehicles
        if ($vehicle) {
            $vehicle->update(['status' => 'available']);
        }

        // Send notifications
        $this->sendCancellationNotifications($booking, $customer, $vehicle, $reason);

        return back()->with('success', 'Booking #' . $booking->booking_number . ' cancelled successfully.');
    }

    /**
     * Call the provider's cancellation API if applicable.
     * Returns an error message string on failure, or null on success.
     */
    private function cancelWithProvider(Booking $booking, ?string $providerSource, string $reason): ?string
    {
        if (!$providerSource) {
            return null; // Internal booking, no provider API to call
        }

        $bookingRef = $booking->provider_booking_ref;
        $providerMetadata = $booking->provider_metadata ?? [];

        try {
            switch ($providerSource) {
                case 'greenmotion':
                case 'usave':
                    if (!$bookingRef) {
                        return 'Provider booking reference is missing.';
                    }
                    $locationId = $providerMetadata['pickup_location_id']
                        ?? $providerMetadata['dropoff_location_id']
                        ?? null;
                    if (!$locationId) {
                        return 'Provider location ID is missing.';
                    }
                    $service = app(\App\Services\GreenMotionService::class)->setProvider($providerSource);
                    $xmlResponse = $service->cancelReservation($locationId, $bookingRef, $reason);
                    if (empty($xmlResponse)) {
                        return 'Failed to cancel reservation with GreenMotion provider.';
                    }
                    // Parse XML response for success/failure
                    $xmlObject = @simplexml_load_string($xmlResponse);
                    if ($xmlObject !== false) {
                        $responseNode = $xmlObject->response ?? $xmlObject->Response ?? $xmlObject;
                        $status = strtolower(trim((string) ($responseNode->status ?? $responseNode->result ?? '')));
                        if (in_array($status, ['failed', 'fail', 'error', 'invalid', 'denied', 'false', '0'], true)) {
                            return 'GreenMotion cancellation failed: ' . trim((string) ($responseNode->message ?? $responseNode->notes ?? 'Unknown error'));
                        }
                    }
                    break;

                case 'favrica':
                    if (!$bookingRef) {
                        return 'Provider booking reference is missing.';
                    }
                    $service = app(\App\Services\FavricaService::class);
                    $response = $service->cancelReservation($bookingRef);
                    if (empty($response) || !is_array($response)) {
                        return 'Failed to cancel reservation with Favrica provider.';
                    }
                    $payload = $response[0] ?? null;
                    $statusValue = strtolower(trim((string) ($payload['success'] ?? $payload['Status'] ?? '')));
                    if ($statusValue !== 'true' && $statusValue !== '1') {
                        return 'Favrica cancellation failed: ' . ($payload['error'] ?? 'Unknown error');
                    }
                    break;

                case 'xdrive':
                    if (!$bookingRef) {
                        return 'Provider booking reference is missing.';
                    }
                    $service = app(\App\Services\XDriveService::class);
                    $response = $service->cancelReservation($bookingRef);
                    if (empty($response) || !is_array($response)) {
                        return 'Failed to cancel reservation with XDrive provider.';
                    }
                    $payload = $response[0] ?? null;
                    $statusValue = strtolower(trim((string) ($payload['success'] ?? $payload['Status'] ?? '')));
                    if ($statusValue !== 'true' && $statusValue !== '1') {
                        return 'XDrive cancellation failed: ' . ($payload['error'] ?? 'Unknown error');
                    }
                    break;

                case 'surprice':
                    if (!$bookingRef) {
                        return 'Provider booking reference is missing.';
                    }
                    $service = app(\App\Services\SurpriceService::class);
                    $response = $service->cancelReservation($bookingRef, $reason);
                    if ($response === null) {
                        return 'Failed to cancel reservation with Surprice provider.';
                    }
                    break;

                case 'renteon':
                    if (!$bookingRef) {
                        return 'Provider booking reference is missing.';
                    }
                    $service = app(\App\Services\RenteonService::class);
                    $response = $service->cancelBooking($bookingRef);
                    if ($response === null) {
                        return 'Failed to cancel reservation with Renteon provider.';
                    }
                    break;

                case 'sicilybycar':
                    if (!$bookingRef) {
                        return 'Provider booking reference is missing.';
                    }
                    $service = app(\App\Services\SicilyByCarService::class);
                    $response = $service->cancelReservation($bookingRef);
                    if (!($response['ok'] ?? false)) {
                        return 'SicilyByCar cancellation failed: ' . ($response['error'] ?? 'Unknown error');
                    }
                    break;

                default:
                    // Provider without cancellation API (RecordGo, AdobeCar, OkMobility, LocautoRent, Wheelsys)
                    // Only local status update, no provider API call
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Admin cancel - provider cancellation failed', [
                'booking_id' => $booking->id,
                'provider' => $providerSource,
                'error' => $e->getMessage(),
            ]);
            return 'Provider cancellation failed: ' . $e->getMessage();
        }

        return null;
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
            $adminEmail = env('VITE_ADMIN_EMAIL', 'default@admin.com');
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
            $query->where('plan', 'like', "%{$search}%")
                ->orWhere('booking_number', 'like', "%{$search}%")
                ->orWhereHas('customer', function ($customerQuery) use ($search) {
                    $customerQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                ->orWhereHas('vehicle', function ($vehicleQuery) use ($search) {
                    $vehicleQuery->where('brand', 'like', "%{$search}%")
                        ->orWhere('color', 'like', "%{$search}%");
                })
                ->orWhereHas('vehicle.vendorProfileData', function ($vendorQuery) use ($search) {
                    $vendorQuery->where('company_name', 'like', "%{$search}%")
                        ->orWhere('company_email', 'like', "%{$search}%");
                });
        });

        if ($statusFilter !== 'all') { // Filter by status if not 'all'
            $bookings->where('booking_status', $statusFilter);
        }

        $bookings = $bookings->orderBy('created_at', 'desc')
            ->with(['customer', 'vehicle.vendorProfileData', 'payments', 'vendorProfile', 'amounts'])
            ->paginate(7);

        // Get booking status counts
        $statusCounts = [
            'total' => Booking::count(),
            'pending' => Booking::where('booking_status', 'pending')->count(),
            'confirmed' => Booking::where('booking_status', 'confirmed')->count(),
            'completed' => Booking::where('booking_status', 'completed')->count(),
            'cancelled' => Booking::where('booking_status', 'cancelled')->count(),
        ];

        return Inertia::render('AdminDashboardPages/Bookings/Index', [
            'users' => $bookings,
            'statusCounts' => $statusCounts,
            'filters' => $request->only(['search', 'status']), // Include status filter
            'currentStatus' => $statusFilter, // Pass current status to the view
            'flash' => session()->only(['success', 'error'])
        ]);
    }
}
