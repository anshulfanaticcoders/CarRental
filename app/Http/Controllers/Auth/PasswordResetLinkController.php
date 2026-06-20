<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Affiliate\AffiliateBusiness;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = Str::lower(trim((string) $request->input('email')));
        $resetEmail = $this->resolvePasswordResetEmail($email);

        try {
            $status = Password::sendResetLink(['email' => $resetEmail]);
        } catch (Throwable $e) {
            Log::error('Password reset email failed to send.', [
                'email' => $email,
                'message' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'email' => ['We could not send the reset email right now. Please try again or contact support.'],
            ]);
        }

        if ($status == Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    private function resolvePasswordResetEmail(string $email): string
    {
        if (User::where('email', $email)->exists()) {
            return $email;
        }

        $business = AffiliateBusiness::with('user')
            ->where('contact_email', $email)
            ->first();

        if (! $business) {
            return $email;
        }

        if ($business->user) {
            return $business->user->email;
        }

        try {
            return DB::transaction(function () use ($business, $email) {
                $freshBusiness = AffiliateBusiness::whereKey($business->id)->lockForUpdate()->firstOrFail();

                if ($freshBusiness->user) {
                    return $freshBusiness->user->email;
                }

                $user = User::create([
                    'first_name' => $this->partnerFirstName($freshBusiness->name),
                    'last_name' => 'Partner',
                    'email' => $email,
                    'phone' => $this->uniqueAffiliatePhone($freshBusiness->contact_phone),
                    'password' => Hash::make(Str::random(40)),
                    'role' => 'affiliate',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]);

                $freshBusiness->update([
                    'user_id' => $user->id,
                    'status' => $freshBusiness->status ?: 'active',
                ]);

                return $user->email;
            });
        } catch (QueryException $e) {
            Log::error('Affiliate password reset user provisioning failed.', [
                'affiliate_business_id' => $business->id,
                'email' => $email,
                'driver_code' => $e->errorInfo[1] ?? null,
            ]);

            throw ValidationException::withMessages([
                'email' => ['This partner account exists, but it needs support to reconnect login access. Please contact support.'],
            ]);
        } catch (Throwable $e) {
            Log::error('Affiliate password reset user provisioning failed.', [
                'affiliate_business_id' => $business->id,
                'email' => $email,
                'message' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'email' => ['This partner account exists, but it needs support to reconnect login access. Please contact support.'],
            ]);
        }
    }

    private function partnerFirstName(?string $businessName): string
    {
        $first = trim(Str::before((string) $businessName, ' '));

        return $first !== '' ? Str::limit($first, 255, '') : 'Vrooem';
    }

    private function uniqueAffiliatePhone(?string $phone): string
    {
        $candidate = trim((string) $phone);

        if ($candidate !== '' && ! User::where('phone', $candidate)->exists()) {
            return $candidate;
        }

        do {
            $candidate = 'AFF-'.Str::upper(Str::random(16));
        } while (User::where('phone', $candidate)->exists());

        return $candidate;
    }
}
