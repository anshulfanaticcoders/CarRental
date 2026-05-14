<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\UserProfile;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function checkAvailability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:32'],
            'phone_code' => ['nullable', 'string', 'max:8'],
        ]);

        $response = [];

        if (! empty($data['email'])) {
            $response['email_taken'] = User::where('email', $data['email'])->exists();
        }

        if (! empty($data['phone'])) {
            $phone = preg_replace('/\D+/', '', $data['phone']);
            $query = User::query()->whereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', '') = ?", [$phone]);
            if (! empty($data['phone_code'])) {
                $query->where('phone_code', $data['phone_code']);
            }
            $response['phone_taken'] = $query->exists();
        }

        return response()->json($response);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:32', Rule::unique('users', 'phone')],
            'phone_code' => ['nullable', 'string', 'max:8'],
            'date_of_birth' => ['required', 'date', 'before:-18 years'],
            'address' => ['required', 'string', 'max:191'],
            'postcode' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:120'],
            'country' => ['required', 'string', 'max:80'],
            'currency' => ['nullable', 'string', 'max:8'],
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Za-z]/', 'regex:/\d/'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ], [
            'date_of_birth.before' => 'You must be at least 18 years old.',
            'password.regex' => 'Password must contain letters and numbers.',
            'email.unique' => 'This email is already registered.',
            'phone.unique' => 'This phone is already registered.',
        ]);

        $currencyMap = [
            '€' => 'EUR',
            '$' => 'USD',
            '£' => 'GBP',
            'د.إ' => 'AED',
            '₹' => 'INR',
            '¥' => 'JPY',
        ];
        $currency = $data['currency'] ?? 'EUR';
        $currency = $currencyMap[$currency] ?? strtoupper($currency);

        $user = DB::transaction(function () use ($data, $currency) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'phone_code' => $data['phone_code'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => 'customer',
                'status' => 'active',
            ]);

            UserProfile::create([
                'user_id' => $user->id,
                'address_line1' => $data['address'],
                'postal_code' => $data['postcode'],
                'city' => $data['city'],
                'country' => $data['country'],
                'date_of_birth' => $data['date_of_birth'],
                'currency' => $currency,
            ]);

            return $user;
        });

        $token = $user->createToken($data['device_name'] ?? 'mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->transformUser($user->fresh(['profile','vendorProfile'])),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        if (($user->status ?? 'active') !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active. Contact support.'],
            ]);
        }

        $token = $user->createToken($data['device_name'] ?? 'mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->transformUser($user->fresh(['profile','vendorProfile'])),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['profile','vendorProfile']);

        return response()->json([
            'user' => $this->transformUser($user),
        ]);
    }

    /**
     * Native "Sign in with Apple" — the app sends the Apple identity token (a JWT).
     * We verify its signature against Apple's published JWKS, confirm the issuer
     * and audience, then find or create the user keyed on the Apple `sub` claim.
     */
    public function appleSignIn(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identity_token' => ['required', 'string'],
            'raw_nonce' => ['required', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:160'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $clientId = config('services.apple.client_id');
        if (! $clientId) {
            Log::error('Apple Sign-In attempted but services.apple.client_id is not configured.');

            return response()->json(['message' => 'Apple Sign-In is not available right now.'], 503);
        }

        try {
            $jwks = Cache::remember('apple_auth_jwks', now()->addDay(), function () {
                $response = Http::timeout(10)->get('https://appleid.apple.com/auth/keys');
                if (! $response->successful()) {
                    throw new \RuntimeException('Unable to fetch Apple public keys.');
                }

                return $response->json();
            });

            JWT::$leeway = 60; // tolerate minor clock skew on exp/iat
            $payload = JWT::decode($data['identity_token'], JWK::parseKeySet($jwks));
        } catch (\Throwable $e) {
            // A stale cached key set can fail a freshly rotated token — drop it so the next try refetches.
            Cache::forget('apple_auth_jwks');
            Log::warning('Apple identity token verification failed', ['error' => $e->getMessage()]);

            throw ValidationException::withMessages([
                'identity_token' => ['Could not verify your Apple sign-in. Please try again.'],
            ]);
        }

        if (($payload->iss ?? null) !== 'https://appleid.apple.com') {
            throw ValidationException::withMessages([
                'identity_token' => ['Invalid Apple token issuer.'],
            ]);
        }

        $aud = $payload->aud ?? null;
        $audMatches = is_array($aud) ? in_array($clientId, $aud, true) : ($aud === $clientId);
        if (! $audMatches) {
            throw ValidationException::withMessages([
                'identity_token' => ['Apple token was not issued for this app.'],
            ]);
        }

        // Bind the token to the nonce the client generated for this request.
        // The app hashes its raw nonce with SHA-256 and passes it to Apple,
        // which echoes the hash back inside the token's `nonce` claim.
        $expectedNonce = hash('sha256', $data['raw_nonce']);
        if (! hash_equals($expectedNonce, (string) ($payload->nonce ?? ''))) {
            throw ValidationException::withMessages([
                'identity_token' => ['Apple sign-in could not be verified. Please try again.'],
            ]);
        }

        $appleId = (string) ($payload->sub ?? '');
        if ($appleId === '') {
            throw ValidationException::withMessages([
                'identity_token' => ['Apple token is missing a subject.'],
            ]);
        }

        // Trust the email ONLY from the verified token, never from the request body —
        // a client-supplied email could be used to hijack an existing account.
        $email = isset($payload->email) ? (string) $payload->email : null;
        $emailVerified = filter_var($payload->email_verified ?? false, FILTER_VALIDATE_BOOLEAN);

        $user = DB::transaction(function () use ($appleId, $email, $emailVerified, $data) {
            $account = SocialAccount::where('provider', 'apple')
                ->where('provider_id', $appleId)
                ->lockForUpdate()
                ->first();

            if ($account) {
                return User::whereKey($account->user_id)->lockForUpdate()->firstOrFail();
            }

            // Link to an existing account only when Apple itself vouches for the email.
            $user = ($email && $emailVerified)
                ? User::where('email', $email)->lockForUpdate()->first()
                : null;

            if (! $user) {
                [$firstName, $lastName] = $this->splitAppleName($data['full_name'] ?? null, $email);

                $user = User::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email ?: ('apple_' . $appleId . '@privaterelay.appleid.com'),
                    'phone' => $this->generateUniquePhone(),
                    'password' => Hash::make(Str::random(40)),
                    'role' => 'customer',
                    'status' => 'active',
                    'email_verified_at' => ($email && $emailVerified) ? now() : null,
                ]);

                UserProfile::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'country' => 'Unknown',
                        'currency' => 'EUR',
                        'address_line1' => '',
                        'address_line2' => '',
                        'city' => '',
                        'state' => '',
                        'postal_code' => '',
                    ],
                );
            }

            SocialAccount::create([
                'provider' => 'apple',
                'provider_id' => $appleId,
                'user_id' => $user->id,
                'provider_email' => $email,
            ]);

            return $user;
        });

        if (($user->status ?? 'active') !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active. Contact support.'],
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken($data['device_name'] ?? 'mobile-apple')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->transformUser($user->fresh(['profile', 'vendorProfile'])),
        ]);
    }

    private function splitAppleName(?string $fullName, ?string $email): array
    {
        $fullName = trim((string) $fullName);
        if ($fullName !== '') {
            $parts = preg_split('/\s+/', $fullName, 2);

            return [$parts[0] ?: 'Apple', $parts[1] ?? 'User'];
        }

        $first = $email ? explode('@', $email)[0] : 'Apple';

        return [$first ?: 'Apple', 'User'];
    }

    private function generateUniquePhone(): string
    {
        do {
            $phone = '9' . str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT);
        } while (User::where('phone', $phone)->exists());

        return $phone;
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:80'],
            'last_name' => ['sometimes', 'string', 'max:80'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'title' => ['sometimes', 'nullable', 'string', 'max:10'],
            'gender' => ['sometimes', 'nullable', 'in:male,female,other'],
            'date_of_birth' => ['sometimes', 'nullable', 'date'],
            'about' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'address_line1' => ['sometimes', 'nullable', 'string', 'max:191'],
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:191'],
            'city' => ['sometimes', 'nullable', 'string', 'max:120'],
            'state' => ['sometimes', 'nullable', 'string', 'max:120'],
            'country' => ['sometimes', 'nullable', 'string', 'max:80'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'tax_identification' => ['sometimes', 'nullable', 'string', 'max:60'],
            'currency' => ['sometimes', 'nullable', 'string', 'max:8'],
            'avatar' => ['sometimes', 'nullable', 'image', 'max:5120'],
        ]);

        if (! empty($validated['currency'])) {
            $currencyMap = [
                '€' => 'EUR',
                '$' => 'USD',
                '£' => 'GBP',
                'د.إ' => 'AED',
                '₹' => 'INR',
                '¥' => 'JPY',
            ];
            $validated['currency'] = $currencyMap[$validated['currency']] ?? strtoupper($validated['currency']);
        }

        if ($request->hasFile('avatar')) {
            $compressedPath = \App\Helpers\ImageCompressionHelper::compressImage(
                $request->file('avatar'),
                'avatars',
                80,
                400,
                400
            );
            if ($compressedPath) {
                $validated['avatar'] = Storage::disk('upcloud')->url($compressedPath);
            } else {
                throw ValidationException::withMessages([
                    'avatar' => ['Failed to upload avatar. Try a smaller image.'],
                ]);
            }
        } else {
            unset($validated['avatar']);
        }

        $userFields = ['first_name', 'last_name', 'phone'];
        $userInput = array_intersect_key($validated, array_flip($userFields));
        if (! empty($userInput)) {
            $user->update($userInput);
        }

        $profileFields = array_diff(array_keys($validated), $userFields);
        $profileInput = array_intersect_key($validated, array_flip($profileFields));
        if (! empty($profileInput)) {
            $user->profile()->updateOrCreate(['user_id' => $user->id], $profileInput);
        }

        return response()->json([
            'user' => $this->transformUser($user->fresh(['profile','vendorProfile'])),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'different:current_password'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->update(['password' => Hash::make($data['new_password'])]);

        return response()->json(['message' => 'Password updated.']);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();
        $hasSocialAccount = \App\Models\SocialAccount::where('user_id', $user->id)->exists();

        if (! $hasSocialAccount) {
            $data = $request->validate([
                'password' => ['required', 'string'],
            ]);

            if (! Hash::check($data['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'password' => ['Password is incorrect.'],
                ]);
            }
        }

        \App\Helpers\ActivityLogHelper::log(
            'user',
            'deleted',
            "Self-deleted account: {$user->email}",
            null,
            ['snapshot' => ['id' => $user->id, 'email' => $user->email]]
        );

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->profile()->delete();
            User::withoutEvents(function () use ($user) {
                $user->delete();
            });
        });

        return response()->json(['message' => 'Account deleted.']);
    }

    private function transformUser(User $user): array
    {
        $profile = $user->profile;
        $vendorProfile = $user->role === 'vendor' ? $user->vendorProfile : null;

        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'status' => $user->status,
            'profile' => $profile ? [
                'avatar' => $profile->avatar,
                'title' => $profile->title,
                'gender' => $profile->gender,
                'date_of_birth' => $profile->date_of_birth,
                'about' => $profile->about,
                'address_line1' => $profile->address_line1,
                'address_line2' => $profile->address_line2,
                'city' => $profile->city,
                'state' => $profile->state,
                'country' => $profile->country,
                'postal_code' => $profile->postal_code,
                'tax_identification' => $profile->tax_identification,
                'currency' => $profile->currency,
                'languages' => $profile->languages,
            ] : null,
            'vendor_profile' => $vendorProfile ? [
                'company_name' => $vendorProfile->company_name,
                'company_email' => $vendorProfile->company_email,
                'company_phone_number' => $vendorProfile->company_phone_number,
                'company_address' => $vendorProfile->company_address,
                'company_gst_number' => $vendorProfile->company_gst_number,
                'status' => $vendorProfile->status,
            ] : null,
        ];
    }
}
