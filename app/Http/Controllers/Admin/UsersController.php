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

        $users = $query->with('profile')->orderBy('created_at', 'desc')->paginate(7);

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
            'phone_code' => 'required|string|max:10',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:admin,vendor,customer',
            'status' => 'required|in:active,inactive,suspended',

            // New profile fields validation
            'date_of_birth' => 'required|date|before:18 years ago',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:2',
        ]);

        // Extract phone code and number from combined phone field
        $phoneData = explode(' ', $request->phone, 2);
        $phoneCode = $phoneData[0] ?? '';
        $phoneNumber = $phoneData[1] ?? '';

        $data = $request->except(['date_of_birth', 'address', 'city', 'postal_code', 'country']);
        $data['password'] = Hash::make($request->password);
        $data['phone_code'] = $phoneCode;
        $data['phone'] = $phoneNumber;
        $data['email_verified_at'] = now();
        $data['phone_verified_at'] = now();
        $data['last_login_at'] = now();

        $user = User::create($data);

        // Create UserProfile with provided data instead of defaults
        UserProfile::create([
            'user_id' => $user->id,
            'country' => $request->country,
            'address_line1' => $request->address,
            'city' => $request->city,
            'postal_code' => $request->postal_code,
            'date_of_birth' => $request->date_of_birth,
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
            'phone_code' => 'nullable|string|max:10',
            // 'role' => 'required|in:admin,vendor,customer',
            'status' => 'required|in:active,inactive,suspended',
            'password' => 'nullable|min:8|confirmed',

            // New profile fields validation - make them optional for existing users
            'date_of_birth' => 'nullable|date|before:18 years ago',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:2',
        ]);

        // Extract phone code and number from combined phone field
        $phoneData = explode(' ', $request->phone, 2);
        $phoneCode = $phoneData[0] ?? '';
        $phoneNumber = $phoneData[1] ?? '';

        $data = $request->except(['date_of_birth', 'address', 'city', 'postal_code', 'country']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }

        $data['phone_code'] = $phoneCode;
        $data['phone'] = $phoneNumber;

        $user->update($data);

        // Update or create UserProfile with provided data
        $profileData = [];

        if ($request->filled('country')) {
            $profileData['country'] = $request->country;
        }
        if ($request->filled('address')) {
            $profileData['address_line1'] = $request->address;
        }
        if ($request->filled('city')) {
            $profileData['city'] = $request->city;
        }
        if ($request->filled('postal_code')) {
            $profileData['postal_code'] = $request->postal_code;
        }
        if ($request->filled('date_of_birth')) {
            $profileData['date_of_birth'] = $request->date_of_birth;
        }

        if (!empty($profileData)) {
            if ($user->profile) {
                $user->profile->update($profileData);
            } else {
                UserProfile::create(array_merge(['user_id' => $user->id], $profileData));
            }
        }

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
