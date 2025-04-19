<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\AccountCreatedNotification;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

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
        ]);

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

        Auth::login($user);

        // Log the activity
        ActivityLogHelper::logActivity('create', 'New User Created', $user, $request);

        // Notify the admin
        // $admin = User::where('email', 'anshul@fanaticcoders.com')->first(); // Replace with your admin email
        // if ($admin) {
        //     $admin->notify(new AccountCreatedNotification($user));
        // }
        return redirect(RouteServiceProvider::HOME);
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
