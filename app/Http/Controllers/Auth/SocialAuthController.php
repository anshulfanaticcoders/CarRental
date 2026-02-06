<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\AccountCreatedNotification;
use App\Notifications\AccountCreatedUserConfirmation;
use App\Services\Affiliate\AffiliateQrCodeService;
use App\Services\GeoLocationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    private const PROVIDERS = ['google', 'facebook'];

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        if (!in_array($provider, self::PROVIDERS, true)) {
            abort(404);
        }

        $locale = $request->route('locale') ?? $request->query('locale') ?? config('app.fallback_locale', 'en');
        $request->session()->put('oauth.locale', $locale);

        $driver = Socialite::driver($provider);
        if ($provider === 'facebook') {
            $driver->scopes(['email'])->fields(['name', 'email']);
        }

        return $driver->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        if (!in_array($provider, self::PROVIDERS, true)) {
            abort(404);
        }

        $locale = $request->route('locale')
            ?? $request->query('locale')
            ?? $request->session()->pull('oauth.locale', config('app.fallback_locale', 'en'));

        try {
            $driver = Socialite::driver($provider)->stateless();
            if ($provider === 'facebook') {
                $driver->scopes(['email'])->fields(['name', 'email']);
            }
            $socialUser = $driver->user();
        } catch (\Exception $exception) {
            Log::warning('Social login failed', [
                'provider' => $provider,
                'error' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('login', ['locale' => $locale])
                ->withErrors(['oauth' => 'Login failed. Please try again.']);
        }

        $email = $socialUser->getEmail();
        if (!$email) {
            return redirect()
                ->route('login', ['locale' => $locale])
                ->withErrors(['email' => 'No email returned from provider.']);
        }

        $account = SocialAccount::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();
        $wasCreated = false;

        if ($account) {
            $user = $account->user;
        } else {
            $user = User::where('email', $email)->first();

            if (!$user) {
                $user = $this->createUserFromProvider($socialUser);
                $wasCreated = true;
            }

            SocialAccount::create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'provider_email' => $email,
            ]);
        }

        $defaultCurrency = 'EUR';
        $resolvedCountry = $this->resolveCountryFromRequest($request);
        $resolvedPhoneCode = $this->resolvePhoneCodeFromCountry($resolvedCountry);

        if (!$user->profile) {
            UserProfile::create([
                'user_id' => $user->id,
                'country' => $resolvedCountry,
                'avatar' => $socialUser->getAvatar(),
                'currency' => $defaultCurrency,
            ]);
        } else {
            $profileNeedsUpdate = false;

            if (!$user->profile->currency) {
                $user->profile->currency = $defaultCurrency;
                $profileNeedsUpdate = true;
            }

            if ($user->profile->country === null || $user->profile->country === '' || $user->profile->country === 'Unknown') {
                $user->profile->country = $resolvedCountry;
                $profileNeedsUpdate = true;
            }

            if ($profileNeedsUpdate) {
                $user->profile->save();
            }
        }

        if (!$user->phone_code && $resolvedPhoneCode) {
            $user->phone_code = $resolvedPhoneCode;
            $user->save();
        }

        Auth::login($user);
        $user->last_login_at = now();
        $user->save();

        $affiliateService = new AffiliateQrCodeService();
        $affiliateService->updateCustomerInAffiliateScans($user->id);

        $statusMessage = $wasCreated
            ? 'Account created successfully. Please complete your profile.'
            : 'Welcome back!';

        return redirect()
            ->route('profile.edit', ['locale' => $locale])
            ->with('status', $statusMessage);
    }

    private function createUserFromProvider($socialUser): User
    {
        $name = trim((string) $socialUser->getName());
        $email = (string) $socialUser->getEmail();
        $firstName = '';
        $lastName = '';

        if ($name) {
            $parts = preg_split('/\s+/', $name, 2);
            $firstName = $parts[0] ?? '';
            $lastName = $parts[1] ?? '';
        }

        if (!$firstName) {
            $firstName = $email ? explode('@', $email)[0] : 'User';
        }

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName ?: 'User',
            'email' => $email,
            'phone' => $this->generateUniquePhone(),
            'phone_code' => null,
            'password' => Hash::make(Str::random(32)),
            'role' => 'customer',
            'status' => 'active',
            'email_verified_at' => now(),
            'phone_verified_at' => null,
            'last_login_at' => now(),
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'country' => $this->resolveCountryFromRequest(request()),
            'avatar' => $socialUser->getAvatar(),
            'currency' => 'EUR',
        ]);

        event(new Registered($user));

        ActivityLogHelper::logActivity('create', 'New User Created (Social)', $user, request());

        try {
            $adminEmail = env('VITE_ADMIN_EMAIL', 'default@admin.com');
            $admin = User::where('email', $adminEmail)->first();
            if ($admin) {
                $admin->notify(new AccountCreatedNotification($user));
            }

            Notification::route('mail', $user->email)
                ->notify(new AccountCreatedUserConfirmation($user));
        } catch (\Exception $exception) {
            Log::error('Social registration notification failed: ' . $exception->getMessage(), [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }

        return $user;
    }

    private function generateUniquePhone(): string
    {
        do {
            $phone = '9' . str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT);
        } while (User::where('phone', $phone)->exists());

        return $phone;
    }

    private function resolveCountryFromRequest(Request $request): string
    {
        try {
            $sessionCountry = $request->session()->get('country');
            if ($sessionCountry) {
                $normalized = strtoupper(trim((string) $sessionCountry));
                return $normalized !== '' ? $normalized : 'Unknown';
            }

            $detectedCountry = GeoLocationService::detectCountry($request);
            if ($detectedCountry) {
                return strtoupper(trim((string) $detectedCountry));
            }
        } catch (\Exception $exception) {
            Log::warning('Social login country detection failed', [
                'error' => $exception->getMessage(),
            ]);
        }

        return 'Unknown';
    }

    private function resolvePhoneCodeFromCountry(?string $countryCode): ?string
    {
        if (!$countryCode) {
            return null;
        }

        $countryCode = strtoupper(trim((string) $countryCode));
        if ($countryCode === '' || $countryCode === 'UNKNOWN') {
            return null;
        }

        $map = $this->getCountryPhoneMap();

        return $map[$countryCode] ?? null;
    }

    private function getCountryPhoneMap(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $path = base_path('public/countries.json');
        if (!is_file($path)) {
            Log::warning('Countries file not found for phone code lookup', [
                'path' => $path,
            ]);
            $cache = [];
            return $cache;
        }

        try {
            $json = file_get_contents($path);
            if ($json === false) {
                Log::warning('Failed to read countries file for phone code lookup', [
                    'path' => $path,
                ]);
                $cache = [];
                return $cache;
            }

            $data = json_decode($json, true);
            if (!is_array($data)) {
                Log::warning('Invalid countries file for phone code lookup', [
                    'path' => $path,
                ]);
                $cache = [];
                return $cache;
            }

            $map = [];
            foreach ($data as $country) {
                $code = strtoupper(trim((string) ($country['code'] ?? '')));
                $phoneCode = trim((string) ($country['phone_code'] ?? ''));

                if ($code !== '' && $phoneCode !== '') {
                    $map[$code] = $phoneCode;
                }
            }

            $cache = $map;
            return $cache;
        } catch (\Exception $exception) {
            Log::warning('Failed to parse countries file for phone code lookup', [
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);
        }

        $cache = [];
        return $cache;
    }
}
