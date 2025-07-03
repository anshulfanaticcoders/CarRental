<?php

namespace App\Http\Controllers;

use App\Models\AdminProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class AdminProfileController extends Controller
{
    /**
     * Display the admin profile form.
     */
    public function index(Request $request): Response
    {
        // Get the authenticated admin user with profile relationship
        $user = Auth::user()->load('adminProfile');

        return Inertia::render('AdminDashboardPages/Settings/Index', [
            'user' => $user,
        ]);
    }

    /**
     * Update the admin profile information.
     */
    public function update(Request $request): RedirectResponse
{
    DB::beginTransaction();

    try {
        $user = Auth::user();

        // Validate all fields
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'current_password' => 'nullable|required_with:password|string',
            'password' => 'nullable|string|min:8|confirmed',
            'password_confirmation' => 'nullable|string',
        ]);

        // Update user data
        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        // Handle password update
        if ($request->filled('password')) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                throw new \Exception('The provided current password does not match your actual password.');
            }
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        // Handle avatar upload
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $filePath = $request->file('avatar')->getClientOriginalName();
            $folderName = 'avatars';
            $path = $request->file('avatar')->store($folderName, 'upcloud');
            Storage::disk('upcloud')->setVisibility($path, 'public');
            $avatarPath = Storage::disk('upcloud')->url($path);
        }

        // Update or create admin profile
        $user->adminProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'company_name' => $validated['company_name'] ?? null,
                'avatar' => $avatarPath ?? $user->adminProfile->avatar ?? null,
            ]
        );

        DB::commit();

        return Redirect::route('admin.settings.profile')
            ->with('success', 'Profile updated successfully.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Failed to update profile: ' . $e->getMessage());
    }
}

    /**
     * Get the admin profile data.
     */
    public function getAdminProfile(Request $request)
    {
        $user = Auth::user()->load('adminProfile');

        $adminProfileData = [
            'avatar' => $user->adminProfile ? $user->adminProfile->avatar : null,
            'company_name' => $user->adminProfile ? $user->adminProfile->company_name : null,
            'email' => $user->email,
        ];

        return response()->json($adminProfileData);
    }
}
