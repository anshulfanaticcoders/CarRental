<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmailValidationController extends Controller
{
    public function validateEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email'),
                Rule::unique('affiliate_businesses', 'contact_email')->whereNull('deleted_at'),
            ],
        ], [
            'email.unique' => 'This email is already taken. Please use another email address.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return response()->json(['message' => 'Email is valid'], 200);
    }

    public function validateContact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email'),
                Rule::unique('affiliate_businesses', 'contact_email')->whereNull('deleted_at'),
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone'),
                Rule::unique('affiliate_businesses', 'contact_phone')->whereNull('deleted_at'),
                new PhoneNumber,
            ],
        ], [
            'email.unique' => 'This email is already taken. Please use another email address.',
            'phone.unique' => 'This phone number is already taken. Please use another phone number.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return response()->json(['message' => 'Contact information is valid'], 200);
    }
}
