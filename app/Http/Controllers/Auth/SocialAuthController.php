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
        $callbackRoute = $request->route('locale') ? 'oauth.callback' : 'oauth.callback.global';
        $redirectUrl = route($callbackRoute, ['locale' => $locale, 'provider' => $provider]);

        $driver = Socialite::driver($provider)->redirectUrl($redirectUrl);
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

        $locale = $request->route('locale') ?? $request->query('locale') ?? config('app.fallback_locale', 'en');
        $callbackRoute = $request->route('locale') ? 'oauth.callback' : 'oauth.callback.global';
        $redirectUrl = route($callbackRoute, ['locale' => $locale, 'provider' => $provider]);

        try {
            $driver = Socialite::driver($provider)->redirectUrl($redirectUrl)->stateless();
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

        if ($account) {
            $user = $account->user;
        } else {
            $user = User::where('email', $email)->first();

            if (!$user) {
                $user = $this->createUserFromProvider($socialUser);
            }

            SocialAccount::create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'provider_email' => $email,
            ]);
        }

        if (!$user->profile) {
            UserProfile::create([
                'user_id' => $user->id,
                'country' => 'Unknown',
                'avatar' => $socialUser->getAvatar(),
            ]);
        }

        Auth::login($user);
        $user->last_login_at = now();
        $user->save();

        $affiliateService = new AffiliateQrCodeService();
        $affiliateService->updateCustomerInAffiliateScans($user->id);

        return redirect()
            ->route('profile.edit', ['locale' => $locale])
            ->with('status', 'Please complete your profile.');
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
            'country' => 'Unknown',
            'avatar' => $socialUser->getAvatar(),
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
}
