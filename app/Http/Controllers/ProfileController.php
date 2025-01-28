<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\UserProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request; // Make sure to import this
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        // Get the authenticated user with profile relationship
        $user = Auth::user()->load('profile');
        // Or alternatively:
        // $user = $request->user()->load('profile');

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'user' => $user,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // print_r($request->all());
        // die();
        try {
            DB::beginTransaction();

            // Get the authenticated user
            $user = Auth::user();

            $validated = $request->validated();

            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $validated['avatar'] = $avatarPath;
            }
            // Update user basic info
            $userFields = ['first_name', 'last_name', 'email', 'phone'];
            $userInput = array_intersect_key($validated, array_flip($userFields));

            if ($user->isDirty('email')) {
                $userInput['email_verified_at'] = null;
            }

            $user->update($userInput);

            // Update or create profile
            $profileFields = array_diff(array_keys($validated), $userFields);
            $profileInput = array_intersect_key($validated, array_flip($profileFields));

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileInput
            );

            DB::commit();

            return Redirect::route('profile.edit')
                ->with('status', 'Profile updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('success', 'Profile updated successfully!');
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'password' => ['required', 'current_password'],
            ]);

            $user = Auth::user();
            // Or alternatively:
            // $user = $request->user();

            Auth::logout();
            $user->delete();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            DB::commit();

            return Redirect::to('/');

        } catch (\Exception $e) {
            DB::rollBack();

            return Redirect::back()
                ->withErrors(['error' => 'Failed to delete account. Please try again.']);
        }
    }

    // public function show($id)
    // {
    //     try {
    //         // Fetch the user by the ID from the route
    //         $user = User::with('profile')->findOrFail($id); // Will fetch the user by the ID provided in the route

    //         // Return the user data with profile details as JSON
    //         return response()->json([
    //             'status' => 'success',
    //             'data' => $user
    //         ], 200);

    //     } catch (\Exception $e) {
    //         // Return error response if something goes wrong
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Failed to fetch user profile. Please try again.',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    //this public function is for fetting current user and user profile data
    public function show(Request $request)
    {
        try {
            $user = $request->user();
            $userProfile = $user->load('profile');

            return response()->json([
                'status' => 'success',
                'data' => $userProfile,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch user profile.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    

}