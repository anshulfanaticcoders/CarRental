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
        $damageRecordsPaginator = DamageProtection::with([
            'booking.vehicle',          // For booking_number and vehicle.vendor_id
            'booking.vendorProfile',    // Loads UserProfile of the vendor via Booking->vendorProfile()
            'booking.customer.profile', // Loads Customer, then UserProfile of the customer via Customer->profile()
        ])->latest()->paginate(10); // Using paginate instead of get

        // Transform the items within the paginator
        $formattedRecords = $damageRecordsPaginator->through(function ($record) {
            $booking = $record->booking;

            $vendorUserProfile = $booking ? $booking->vendorProfile : null;
            $customerModel = $booking ? $booking->customer : null;
            $customerUserProfile = $customerModel ? $customerModel->profile : null;

            $vendorName = 'N/A';
            $vendorId = null;
            if ($vendorUserProfile) {
                if (!empty($vendorUserProfile->company_name)) {
                    $vendorName = $vendorUserProfile->company_name;
                } elseif (!empty($vendorUserProfile->first_name) && !empty($vendorUserProfile->last_name)) {
                    $vendorName = trim($vendorUserProfile->first_name . ' ' . $vendorUserProfile->last_name);
                } elseif ($vendorUserProfile->user && !empty($vendorUserProfile->user->name)) { // Fallback to user's name
                    $vendorName = $vendorUserProfile->user->name;
                }
                $vendorId = $vendorUserProfile->user_id;
            } elseif ($booking && $booking->vehicle) {
                $vendorId = $booking->vehicle->vendor_id;
            }
            
            $customerName = 'N/A';
            $customerId = $booking ? $booking->customer_id : null; 

            if ($customerUserProfile) {
                if (!empty($customerUserProfile->first_name) && !empty($customerUserProfile->last_name)) {
                    $customerName = trim($customerUserProfile->first_name . ' ' . $customerUserProfile->last_name);
                } elseif ($customerUserProfile->user && !empty($customerUserProfile->user->name)) { // Fallback to user's name
                    $customerName = $customerUserProfile->user->name;
                }
            } elseif ($customerModel && $customerModel->user && !empty($customerModel->user->first_name) && !empty($customerModel->user->last_name)) {
                 $customerName = trim($customerModel->user->first_name . ' ' . $customerModel->user->last_name);
            } elseif ($customerModel && $customerModel->user && !empty($customerModel->user->name)) { // Fallback if Customer->user->name exists
                $customerName = $customerModel->user->name;
            } elseif ($customerModel && !empty($customerModel->first_name) && !empty($customerModel->last_name)) { // Fallback if Customer model has first_name and last_name
                 $customerName = trim($customerModel->first_name . ' ' . $customerModel->last_name);
            } elseif ($customerModel && !empty($customerModel->name)) { // Fallback if Customer model has a 'name' attribute
                $customerName = $customerModel->name;
            }

            return [
                'id' => $record->id,
                'booking_id' => $booking ? $booking->id : null,
                'booking_number' => $booking && $booking->vehicle ? $booking->booking_number : 'N/A',
                'vendor_id' => $vendorId,
                'vendor_name' => $vendorName,
                'customer_id' => $customerId, // FK from bookings table to customers table
                'customer_name' => $customerName,
                'before_images' => $record->before_images ?? [],
                'after_images' => $record->after_images ?? [],
            ];
        });

        return Inertia::render('AdminDashboardPages/DamageProtection/Index', [
            'damageRecords' => $formattedRecords, // This will now be a paginator instance
        ]);
    }
}
