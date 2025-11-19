<?php

namespace App\Http\Controllers\Admin;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');

        // Build the query
        $query = User::where('role', '!=', 'admin');

        // Apply search filter
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(7);

        // Get accurate status counts
        $statusCounts = [
            'total' => User::where('role', '!=', 'admin')->count(),
            'active' => User::where('role', '!=', 'admin')->where('status', 'active')->count(),
            'inactive' => User::where('role', '!=', 'admin')->where('status', 'inactive')->count(),
            'suspended' => User::where('role', '!=', 'admin')->where('status', 'suspended')->count(),
        ];

        return Inertia::render('AdminDashboardPages/Users/Index', [
            'users' => $users,
            'statusCounts' => $statusCounts,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|unique:users,phone',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:admin,vendor,customer',
            'status' => 'required|in:active,inactive,suspended',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'last_login_at' => now(),
            'created_at' => now(),
        ]);

        $data = $request->all();
        $data['password'] = Hash::make($request->password);

        $user = User::create($data);

        // Create UserProfile with country
        UserProfile::create([
            'user_id' => $user->id,
            'country' => $request->country,
            'address_line1' => 'N/A',
            'city' => 'N/A',
            'postal_code' => '00000',
            'date_of_birth' => now()->subYears(18)->toDateString(),
        ]);

        // Log the activity
        ActivityLogHelper::logActivity('create', 'Created a new user', $user, $request);

        return redirect()->route('users.index', ['locale' => app()->getLocale()])->with('success', 'User created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'required|unique:users,phone,' . $user->id,
            // 'role' => 'required|in:admin,vendor,customer',
            'status' => 'required|in:active,inactive,suspended',
            'password' => 'nullable|min:8|confirmed',
        ]);

        $data = $request->all();

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        // Log the activity
        ActivityLogHelper::logActivity('update', 'Updated a user', $user, $request);

        return redirect()->route('users.index', ['locale' => app()->getLocale()])->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Log the activity
        ActivityLogHelper::logActivity('delete', 'User Deleted', $user);
        $user->delete();
        return redirect()->route('users.index', ['locale' => app()->getLocale()])->with('success', 'User deleted successfully.');
    }
}
