<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Notifications\VendorStatusUpdatedNotification;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VendorDocument;
use Illuminate\Http\Request;

class VendorsDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');

        // Get vendor profiles with search and join with users
        $vendorProfilesQuery = VendorProfile::with(['user', 'user.vendorDocument']);

        // Apply search filter
        if ($search) {
            $vendorProfilesQuery->where(function ($query) use ($search) {
                $query->where('company_name', 'like', "%{$search}%")
                      ->orWhere('company_address', 'like', "%{$search}%")
                      ->orWhere('company_email', 'like', "%{$search}%")
                      ->orWhere('status', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                      });
            });
        }

        // Apply status filter
        if ($status && $status !== 'all') {
            $vendorProfilesQuery->where('status', $status);
        }

        $vendorProfiles = $vendorProfilesQuery->orderBy('created_at', 'desc')->paginate(7);

       // Get accurate status counts directly from VendorProfile table
        $statusCounts = [
            'total' => VendorProfile::count(),
            'approved' => VendorProfile::where('status', 'approved')->count(),
            'pending' => VendorProfile::where('status', 'pending')->count(),
            'rejected' => VendorProfile::where('status', 'rejected')->count(),
        ];

        return Inertia::render('AdminDashboardPages/Vendors/Index', [
            'users' => $vendorProfiles, // Changed from $users to $vendorProfiles
            'statusCounts' => $statusCounts,
            'filters' => $request->only(['search', 'status']),
        ]);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {

        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);
        $vendorProfile = VendorProfile::findOrFail($id);
        // Update the vendor status
        $vendorProfile->update([
            'status' => $request->status,
        ]);

        // Notify the user
        $user = User::findOrFail($vendorProfile->user_id);
        $user->notify(new VendorStatusUpdatedNotification($vendorProfile, $user));

        return redirect()->route('vendors.index')->with('success', 'User updated successfully.');
    }

    public function destroy($id)
{
    $vendor = User::where('id', $id)->where('role', 'vendor')->firstOrFail();
    $vendor->delete();

    return redirect()->route('vendors.index')->with('success', 'Vendor deleted successfully.');
}

}
