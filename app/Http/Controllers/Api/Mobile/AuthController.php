<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
            'user' => $this->transformUser($user->fresh('profile')),
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
            'user' => $this->transformUser($user->fresh('profile')),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('profile');

        return response()->json([
            'user' => $this->transformUser($user),
        ]);
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
            'user' => $this->transformUser($user->fresh('profile')),
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
        ];
    }
}
