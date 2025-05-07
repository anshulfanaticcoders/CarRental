<?php

namespace App\Http\Middleware;

use App\Models\VendorProfile;
use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\UserDocument; // Import the UserDocument model

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request)
    {
        $sharedData = parent::share($request);

        if ($request->user()) {
            // Fetch the user document (single record)
            $document = UserDocument::where('user_id', $request->user()->id)
                ->first([
                    'driving_license_front',
                    'driving_license_back',
                    'passport_front',
                    'passport_back',
                    'verification_status'
                ]);

            // Prepare document data
            $documentData = $document ? [
                'driving_license_front' => $document->driving_license_front,
                'driving_license_back' => $document->driving_license_back,
                'passport_front' => $document->passport_front,
                'passport_back' => $document->passport_back,
                'verification_status' => $document->verification_status,
            ] : null;

            // Merge user and document into shared data
            $sharedData = array_merge($sharedData, [
                'auth' => [
                    'user' => $request->user(),
                    'name' => $request->user()->first_name,
                    'documents' => $documentData, // Share the single document record
                ],
                'vendorStatus' => function () {
                    $user = auth()->user();
                    if ($user) {
                        $vendorProfile = VendorProfile::where('user_id', $user->id)->first();
                        return $vendorProfile ? $vendorProfile->status : 'pending';
                    }
                    return 'pending';
                }
            ]);
        } else {
            // If the user is not authenticated
            $sharedData = array_merge($sharedData, [
                'auth' => [
                    'user' => null,
                    'documents' => null,
                ],
            ]);
        }

        // Share locale and translations
    $sharedData['locale'] = fn () => app()->getLocale();
    $sharedData['translations'] = fn () => trans('messages');

        return $sharedData;
    }
}