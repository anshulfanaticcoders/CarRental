<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $users = User::whereHas('vendorProfile', function ($query) use ($search) {
            if ($search) {
                $query->where('company_name', 'like', "%{$search}%")
                    ->orWhere('company_address', 'like', "%{$search}%")
                    ->orWhere('company_email', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            }
        })
        ->orderBy('created_at', 'desc')
            ->with(['vendorProfile', 'vendorDocument'])
            ->paginate(4);

        return Inertia::render('AdminDashboardPages/Vendors/Index', [
            'users' => $users,
            'filters' => $request->only(['search']),
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

        return redirect()->route('vendors.index')->with('success', 'User updated successfully.');
    }

    public function destroy($id)
{
    $vendor = User::where('id', $id)->where('role', 'vendor')->firstOrFail();
    $vendor->delete();

    return redirect()->route('vendors.index')->with('success', 'Vendor deleted successfully.');
}

}
