<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\UserProfile;
use Http;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $profile = $user->profile; // Get the user's profile

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'user' => $user,  
            'profile' => $profile,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user(); //From here we get current user data from the table in database(mysql)

        // Update the user's basic information (User table)
        $user->fill($request->validated());
        if ($user->isDirty('email')) {
            $user->email_verified_at = null; // Nullify email verification if email changes
        }
        $user->save();

        // Update the user's profile information (UserProfile table)
        $profileData = $request->only([
            'address_line1',
            'address_line2',
            'city',
            'state',
            'country',
            'postal_code',
            'date_of_birth',
            'gender',
            'about',
            'title',
            'bio',
            'tax_identification',
            'languages',
            'currency',
        ]);
        

        $profile = $user->profile ?? new UserProfile();

        // Fill the profile data and associate it with the user
        $profile->fill($profileData);
        $profile->user_id = $user->id; 
        $profile->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
