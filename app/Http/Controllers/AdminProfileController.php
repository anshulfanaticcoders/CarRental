<?php

namespace App\Http\Controllers;

use App\Models\AdminProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
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
        try {
            DB::beginTransaction();

            // Get the authenticated admin user
            $user = Auth::user();

            $validated = $request->validate([
                'company_name' => 'nullable|string|max:255',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
                'phone' => 'nullable|string|max:20',
            ]);

            if ($request->hasFile('avatar')) {
                $filePath = $request->file('avatar')->getClientOriginalName();
                $folderName = 'avatars';
                $path = $request->file('avatar')->store($folderName, 'upcloud');

                // Set the object to be publicly accessible
                Storage::disk('upcloud')->setVisibility($path, 'public');

                $url = Storage::disk('upcloud')->url($path);
                $validated['avatar'] = $url;
            }

            // Update user basic info
            $userFields = ['first_name', 'last_name', 'email', 'phone'];
            $userInput = array_intersect_key($validated, array_flip($userFields));

            if ($user->isDirty('email')) {
                $userInput['email_verified_at'] = null;
            }

            $user->update($userInput);

            // Update or create admin profile
            $profileFields = ['company_name', 'avatar'];
            $profileInput = array_intersect_key($validated, array_flip($profileFields));

            $user->adminProfile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileInput
            );

            DB::commit();

            return Redirect::route('admin.settings.profile')
                ->with('status', 'Profile updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to update profile. Please try again.']);
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
