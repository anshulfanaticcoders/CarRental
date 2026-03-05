<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Affiliate\AffiliateBusiness;
use App\Models\User;
use App\Notifications\Affiliate\BusinessRegistrationAdminNotification;
use App\Notifications\Affiliate\BusinessRegistrationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AffiliateRegisteredController extends Controller
{
    public function create(Request $request)
    {
        $locale = $request->route('locale', 'en');

        return Inertia::render('Affiliate/Register', [
            'locale' => $locale,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|string|max:100',
            'contact_phone' => 'nullable|string|max:30',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_iban' => 'nullable|string|max:50',
            'bank_bic' => 'nullable|string|max:20',
            'bank_account_name' => 'nullable|string|max:200',
            'currency' => 'nullable|string|in:EUR,GBP,USD,CHF,SEK,NOK,DKK,PLN,CZK,HUF,TRY',
        ]);

        $user = null;

        DB::transaction(function () use ($validated, &$user) {
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['contact_phone'] ?? 'AF-' . Str::random(10),
                'password' => Hash::make($validated['password']),
                'role' => 'affiliate',
                'status' => 'active',
            ]);

            AffiliateBusiness::create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'name' => $validated['business_name'],
                'business_type' => $validated['business_type'],
                'contact_email' => $validated['email'],
                'contact_phone' => $validated['contact_phone'] ?? null,
                'city' => $validated['city'] ?? null,
                'country' => $validated['country'] ?? null,
                'currency' => $validated['currency'] ?? 'EUR',
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_iban' => $validated['bank_iban'] ?? null,
                'bank_bic' => $validated['bank_bic'] ?? null,
                'bank_account_name' => $validated['bank_account_name'] ?? null,
                'verification_status' => 'pending',
                'status' => 'active',
            ]);
        });

        Auth::login($user);

        // Send notifications - wrapped in try/catch so mail failures don't block registration
        try {
            $business = $user->affiliateBusiness;
            $user->notify(new BusinessRegistrationNotification($business));

            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new BusinessRegistrationAdminNotification($business));
            }
        } catch (\Exception $e) {
            Log::warning('Affiliate registration notifications failed: ' . $e->getMessage());
        }

        $locale = $request->route('locale', 'en');

        return redirect()->route('affiliate.dashboard', ['locale' => $locale])
            ->with('success', 'Welcome! Your affiliate account has been created.');
    }
}
