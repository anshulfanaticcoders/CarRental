<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Affiliate\AffiliateBusiness;
use App\Models\User;
use App\Notifications\Affiliate\BusinessRegistrationAdminNotification;
use App\Notifications\Affiliate\BusinessRegistrationNotification;
use App\Rules\PhoneNumber;
use App\Services\Seo\SeoMetaResolver;
use App\Support\CurrencyRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AffiliateRegisteredController extends Controller
{
    public function create(Request $request)
    {
        $locale = $request->route('locale', 'en');
        $seo = app(SeoMetaResolver::class)->resolveForRoute(
            'affiliate.register',
            [],
            $locale,
            route('affiliate.register', ['locale' => $locale])
        )->toArray();

        return Inertia::render('Affiliate/Register', [
            'locale' => $locale,
            'seo' => $seo,
        ]);
    }

    public function store(Request $request)
    {
        $selectableCurrencies = app(CurrencyRegistry::class)->selectableCodes();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email'), Rule::unique('affiliate_businesses', 'contact_email')],
            'password' => 'required|string|min:8|confirmed',
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|string|max:100',
            'contact_phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone'), Rule::unique('affiliate_businesses', 'contact_phone'), new PhoneNumber],
            'city' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_iban' => 'nullable|string|max:50',
            'bank_bic' => 'nullable|string|max:20',
            'bank_account_name' => 'nullable|string|max:200',
            'currency' => ['nullable', 'string', 'size:3', Rule::in($selectableCurrencies)],
        ], [
            'email.unique' => 'This email is already taken. Please use another email address.',
            'contact_phone.unique' => 'This phone number is already taken. Please use another phone number.',
        ]);

        $user = null;

        try {
            DB::transaction(function () use ($validated, &$user) {
                $user = User::create([
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'email' => $validated['email'],
                    'phone' => $validated['contact_phone'] ?? 'AF-'.Str::random(10),
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
        } catch (QueryException $e) {
            $this->throwAffiliateRegistrationValidationException($e);
        }

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
            Log::warning('Affiliate registration notifications failed: '.$e->getMessage());
        }

        $locale = $request->route('locale', 'en');

        return redirect()->route('affiliate.dashboard', ['locale' => $locale])
            ->with('success', 'Welcome! Your affiliate account has been created.');
    }

    private function throwAffiliateRegistrationValidationException(QueryException $e): never
    {
        $message = $e->getMessage();
        $driverCode = $e->errorInfo[1] ?? null;

        if ($driverCode === 1062 || str_contains($message, 'Duplicate entry')) {
            if (str_contains($message, 'email') || str_contains($message, 'contact_email')) {
                throw ValidationException::withMessages([
                    'email' => 'This email is already taken. Please use another email address.',
                ]);
            }

            if (str_contains($message, 'phone') || str_contains($message, 'contact_phone')) {
                throw ValidationException::withMessages([
                    'contact_phone' => 'This phone number is already taken. Please use another phone number.',
                ]);
            }
        }

        Log::error('Affiliate registration failed during database write.', [
            'driver_code' => $driverCode,
            'sql_state' => $e->getCode(),
        ]);

        throw ValidationException::withMessages([
            'registration' => 'We could not create your partner account right now. Please check your details and try again.',
        ]);
    }
}
