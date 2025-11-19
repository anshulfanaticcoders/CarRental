<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DamageProtection;
use App\Models\Booking;
use App\Models\User;
use Inertia\Inertia;

class DamageProtectionController extends Controller
{
    public function index()
    {
        // Get damage protection records that have valid booking_ids and the bookings actually exist
        $damageRecordsPaginator = DamageProtection::whereNotNull('booking_id')
            ->whereIn('booking_id', function($query) {
                $query->select('id')->from('bookings');
            })
            ->latest()
            ->paginate(10);

        // Transform the items within the paginator
        $formattedRecords = $damageRecordsPaginator->through(function ($record) {
            // Default values
            $vendorName = 'N/A';
            $vehicleBrand = 'N/A';
            $customerName = 'N/A';
            $customerEmail = 'N/A';
            $pickupLocation = 'N/A';
            $bookingNumber = 'N/A';
            $bookingId = null;

            // Get the booking_id directly from the damage protection record
            $directBookingId = $record->booking_id;

            if ($directBookingId) {
                // Find the booking directly using the booking_id from damage_protection table
                try {
                    $booking = \App\Models\Booking::find($directBookingId);
                    if ($booking) {
                        $bookingId = $booking->id;
                        $bookingNumber = $booking->booking_number ?? 'N/A';
                        $pickupLocation = $booking->pickup_location ?? 'N/A';

                        // Get customer info
                        if ($booking->customer_id) {
                            $customer = \App\Models\Customer::find($booking->customer_id);
                            if ($customer) {
                                $customerName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
                                $customerEmail = $customer->email ?? 'N/A';
                                if (empty($customerName) && !empty($customer->name)) {
                                    $customerName = $customer->name;
                                }
                            }
                        }

                        // Get vehicle info
                        if ($booking->vehicle_id) {
                            $vehicle = \App\Models\Vehicle::find($booking->vehicle_id);
                            if ($vehicle) {
                                $vehicleBrand = $vehicle->brand ?? 'N/A';
                                // Get vendor name directly from users table
                                if ($vehicle->vendor_id) {
                                    $vendorUser = \App\Models\User::find($vehicle->vendor_id);
                                    if ($vendorUser) {
                                        $vendorName = trim($vendorUser->first_name . ' ' . $vendorUser->last_name);
                                    } else {
                                        $vendorName = 'Vendor #' . $vehicle->vendor_id;
                                    }
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Log error but continue with default values
                    \Log::error('Error fetching booking data: ' . $e->getMessage());
                }
            }

            return [
                'id' => $record->id,
                'booking_id' => $bookingId,
                'booking_number' => $bookingNumber,
                'customer_name' => $customerName ?: 'N/A',
                'customer_id' => $booking ? $booking->customer_id : null,
                'customer_email' => $customerEmail ?: 'N/A',
                'vendor_name' => $vendorName,
                'vendor_id' => isset($vehicle) && $vehicle ? $vehicle->vendor_id : null,
                'vehicle_brand' => $vehicleBrand,
                'pickup_location' => $pickupLocation,
                'before_images' => $record->before_images ?? [],
                'after_images' => $record->after_images ?? [],
                'created_at' => $record->created_at,
            ];
        });

        return Inertia::render('AdminDashboardPages/DamageProtection/Index', [
            'damageRecords' => $formattedRecords,
        ]);
    }

    /**
     * Helper method to get vendor name
     */
    private function getVendorName($vendorId)
    {
        try {
            // First try to get user profile
            $vendorProfile = \App\Models\UserProfile::where('user_id', $vendorId)->first();
            if ($vendorProfile) {
                if (!empty($vendorProfile->company_name)) {
                    return $vendorProfile->company_name;
                } elseif (!empty($vendorProfile->first_name) && !empty($vendorProfile->last_name)) {
                    return trim($vendorProfile->first_name . ' ' . $vendorProfile->last_name);
                }
            }

            // Fallback to user name
            $user = \App\Models\User::find($vendorId);
            if ($user) {
                return $user->name ?: 'Vendor #' . $vendorId;
            }

            return 'Vendor #' . $vendorId;
        } catch (\Exception $e) {
            return 'Vendor #' . $vendorId . ' (Error: ' . $e->getMessage() . ')';
        }
    }
}
