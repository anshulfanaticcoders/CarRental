<?php

namespace App\Services;

use App\Models\User;

class CheckoutIdentityGuardService
{
    public function findExistingUsers(?string $email, ?string $phone): array
    {
        $normalizedEmail = $this->normalizeEmail($email);
        $normalizedPhone = $this->normalizePhone($phone);

        return [
            'email_user' => $normalizedEmail ? User::where('email', $normalizedEmail)->first() : null,
            'phone_user' => $normalizedPhone ? User::where('phone', $normalizedPhone)->first() : null,
        ];
    }

    public function resolveCheckoutConflict(?User $authenticatedUser, ?User $emailUser, ?User $phoneUser): ?array
    {
        if ($authenticatedUser) {
            if ($emailUser && $emailUser->id !== $authenticatedUser->id) {
                return [
                    'code' => 'checkout_identity_reserved',
                    'message' => 'This email address belongs to another account. Please use your own account email.',
                    'show_login' => false,
                ];
            }

            if ($phoneUser && $phoneUser->id !== $authenticatedUser->id) {
                return [
                    'code' => 'checkout_identity_reserved',
                    'message' => 'This phone number belongs to another account. Please use your own phone number.',
                    'show_login' => false,
                ];
            }

            return null;
        }

        if ($emailUser) {
            if ($this->isCustomerLikeRole($emailUser->role ?? null)) {
                return [
                    'code' => 'checkout_login_required',
                    'message' => 'This email is already linked to an account. Please log in to continue with your booking.',
                    'show_login' => true,
                ];
            }

            return [
                'code' => 'checkout_identity_reserved',
                'message' => 'This email is already used for another account type. Please use a different email address for booking.',
                'show_login' => false,
            ];
        }

        if ($phoneUser) {
            if ($this->isCustomerLikeRole($phoneUser->role ?? null)) {
                return [
                    'code' => 'checkout_login_required',
                    'message' => 'This phone number is already linked to an account. Please log in or use a different phone number.',
                    'show_login' => true,
                ];
            }

            return [
                'code' => 'checkout_identity_reserved',
                'message' => 'This phone number is already used for another account type. Please use a different phone number for booking.',
                'show_login' => false,
            ];
        }

        return null;
    }

    public function normalizeEmail(?string $email): ?string
    {
        $normalized = strtolower(trim((string) $email));

        return $normalized !== '' ? $normalized : null;
    }

    public function normalizePhone(?string $phone): ?string
    {
        $normalized = trim((string) $phone);

        return $normalized !== '' ? $normalized : null;
    }

    private function isCustomerLikeRole(?string $role): bool
    {
        return in_array($role, [null, '', 'customer', 'user'], true);
    }
}
