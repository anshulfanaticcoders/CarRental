<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Redirect;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
{   
    return [
        'first_name' => ['required', 'string', 'max:255'],
        'last_name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->user()->id)],
        'phone' => ['required', 'string', 'max:20'],
        'date_of_birth' => ['nullable', 'date'],
        'address_line1' => ['nullable', 'string', 'max:255'],
        'address_line2' => ['nullable', 'string', 'max:255'],
        'city' => ['nullable', 'string', 'max:255'],
        'state' => ['nullable', 'string', 'max:255'],
        'country' => ['nullable', 'string', 'max:255'],
        'postal_code' => ['nullable', 'string', 'max:10'],
        'tax_identification' => ['nullable', 'string', 'max:50'],
        'about' => ['nullable', 'string', 'max:1000'],
        'title' => ['nullable', 'string', 'in:Mr.,Miss'], 
        'gender' => ['nullable', 'string', 'in:Male,Female'],
        'languages' => ['nullable', 'string'],
        'currency' => ['nullable', 'string'],
    ];
}

public function update(ProfileUpdateRequest $request): RedirectResponse
{
    $user = $request->user();

    // Update user fields
    $user->fill($request->validated());
    if ($user->isDirty('email')) {
        $user->email_verified_at = null;
    }
    $user->save();

    // Update or create profile
    $profileData = $request->only([
        'address_line1', 'address_line2', 'city', 'state', 'country', 'postal_code',
        'date_of_birth', 'gender', 'about', 'title', 'bio',
        'tax_identification', 'languages', 'currency',
    ]);

    $profile = $user->profile ?? new UserProfile(['user_id' => $user->id]);
    $profile->fill($profileData);
    $profile->save();

    return Redirect::route('profile.edit')->with('status', 'Profile updated successfully.');
}

}
