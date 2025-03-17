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
            // Fetch user documents
            $documents = UserDocument::where('user_id', $request->user()->id)
                ->get(['document_type', 'document_file']);

            // Format the documents for easier access
            $formattedDocuments = [];
            foreach ($documents as $document) {
                $formattedDocuments[$document->document_type] = $document->document_file; // Store the file path
            }

            // Merge user and documents into shared data
            $sharedData = array_merge($sharedData, [
                'auth' => [
                    'user' => $request->user(),
                    'name' => $request->user()->first_name,
                    'documents' => $formattedDocuments, // Add documents to shared data
                ],
                'vendorStatus' => function () {
                $user = auth()->user();
                if ($user) {
                    $vendorProfile = VendorProfile::where('user_id', $user->id)->first();
                    return $vendorProfile ? $vendorProfile->status : 'pending';
                }
            }
            ]);
        } else {
            // If the user is not authenticated
            $sharedData = array_merge($sharedData, [
                'auth' => [
                    'user' => null,
                ],
            ]);
        }

        return $sharedData;
    }
}