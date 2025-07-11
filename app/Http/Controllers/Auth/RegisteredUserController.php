<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\AccountCreatedNotification;
use App\Notifications\AccountCreatedUserConfirmation;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\TapfiliateService; // Added for Tapfiliate integration

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'phone_code' => 'nullable|string',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'date_of_birth' => 'required|date',
            'address' => 'required|string|max:255',
            'postcode' => 'required|string|max:10',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255',
        ]);

        // Create user
        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'phone_code' => $validated['phone_code'],
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
            'status' => 'active',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'last_login_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            'referred_by_user_id' => null, // Initialize as null
        ]);

        // Check for referral code in session and update referred_by_user_id
        if (session('referral_code')) {
            $referrerUserMapping = \App\Models\TapfiliateUserMapping::where('referral_code', session('referral_code'))->first();
            if ($referrerUserMapping) {
                $user->update(['referred_by_user_id' => $referrerUserMapping->user_id]);
            }
        }

        // Create user profile
        UserProfile::create([
            'user_id' => $user->id,
            'address_line1' => $validated['address'],
            'postal_code' => $validated['postcode'],
            'city' => $validated['city'],
            'country' => $validated['country'],
            'date_of_birth' => $validated['date_of_birth'],
        ]);

        event(new Registered($user));

        // Pass user ID and referrer code to the frontend for client-side Tapfiliate tracking
        // We will handle Tapfiliate customer tracking and affiliate creation via JavaScript
        $referredByCode = session('referral_code');
        session()->forget('referral_code'); // Clear session

        Auth::login($user);

        // Redirect with user data for client-side Tapfiliate tracking
        return redirect(RouteServiceProvider::HOME)->with([
            'newlyRegisteredUserId' => $user->id,
            'referredByCode' => $referredByCode,
        ]);

        // Log the activity
        ActivityLogHelper::logActivity('create', 'New User Created', $user, $request);

        // Notify the admin
        $adminEmail = env('VITE_ADMIN_EMAIL', 'default@admin.com');
        $admin = User::where('email', $adminEmail)->first();
        if ($admin) {
            $admin->notify(new AccountCreatedNotification($user));
        }

        // Notify the user
        Notification::route('mail', $user->email)
            ->notify(new AccountCreatedUserConfirmation($user));
        // The redirect is now handled above to pass data to frontend
        // return redirect(RouteServiceProvider::HOME);
    }

    public function getUserWithRelations()
    {
        $user = Auth::user();

        $userWithRelations = User::with([
            'profile',
            'vendorProfile',
            'vendorDocument',
            'vehicles'
        ])->find($user->id);

        return response()->json([
            'status' => 'success',
            'data' => $userWithRelations
        ]);
    }

}
