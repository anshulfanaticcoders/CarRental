<?php

namespace App\Http\Middleware;

use App\Models\VendorProfile;
use App\Services\OfferService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Middleware;
use App\Models\UserDocument;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected function shouldUseSsr(Request $request): bool
    {
        $route = $request->route();

        if (!$route) {
            return false;
        }

        $routeName = (string) $route->getName();
        $middlewares = $route->gatherMiddleware();

        $hasAuthMiddleware = collect($middlewares)->contains(function ($middleware) {
            return $middleware === 'auth' || Str::startsWith($middleware, 'auth:');
        });

        if ($hasAuthMiddleware) {
            return false;
        }

        return !Str::startsWith($routeName, 'admin.');
    }

    public function handle(Request $request, Closure $next)
    {
        $ssrEnabled = (bool) config('inertia.ssr.enabled', false);
        config(['inertia.ssr.enabled' => $ssrEnabled && $this->shouldUseSsr($request)]);

        return parent::handle($request, $next);
    }

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
            $user = $request->user()->load('profile');
            // Fetch the user document (single record)
            $document = UserDocument::where('user_id', $user->id)
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
                    'user' => $user,
                    'name' => $user->first_name,
                    'documents' => $documentData, // Share the single document record
                ],
                'vendorStatus' => function () {
                    $user = auth()->user();
                    if ($user) {
                        $vendorProfile = VendorProfile::where('user_id', $user->id)->first();
                        return $vendorProfile ? $vendorProfile->status : 'pending';
                    }
                    return 'pending';
                },
                'affiliateVerificationStatus' => function () use ($user) {
                    if ($user->role === 'affiliate' && $user->affiliateBusiness) {
                        return $user->affiliateBusiness->verification_status;
                    }
                    return null;
                },
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

        $sharedData['currency'] = session('currency', 'USD');

        $rawMarkup = env('PROVIDER_MARKUP_PERCENT');
        $markupPercent = is_numeric($rawMarkup) ? (float) $rawMarkup : 15.0;
        if ($markupPercent < 0) {
            $markupPercent = 0.0;
        }
        $sharedData['provider_markup_percent'] = $markupPercent;
        $sharedData['provider_markup_rate'] = $markupPercent / 100;
        $sharedData['awin_test_mode'] = config('awin.test_mode', true) ? '1' : '0';

        $sharedData['active_offers'] = function () {
            $offerService = app(OfferService::class);

            return $offerService->getDisplayOffers('search')
                ->map(fn ($offer) => $offerService->summarizeHomepageOffer($offer))
                ->values()
                ->all();
        };

        $sharedData['homepage_offers'] = function () {
            $offerService = app(OfferService::class);

            return $offerService->getDisplayOffers('homepage')
                ->map(fn ($offer) => $offerService->summarizeHomepageOffer($offer))
                ->values()
                ->all();
        };

        $sharedData['active_price_offer'] = function () {
            $resolved = app(OfferService::class)->resolveAppliedOffers(['placement' => 'search']);
            $monetary = $resolved['monetary_offer'];

            if (!$monetary) {
                return null;
            }

            return [
                'id' => $monetary['id'],
                'title' => $monetary['title'],
                'description' => $monetary['description'],
                'discount_percentage' => (float) ($monetary['effect_payload']['percentage'] ?? 0),
                'offer_rate' => (float) $resolved['price_discount_rate'],
            ];
        };

        $sharedData['active_promo'] = function () {
            $resolved = app(OfferService::class)->resolveAppliedOffers(['placement' => 'search']);
            $monetary = $resolved['monetary_offer'];

            if (!$monetary) {
                return null;
            }

            return [
                'id' => $monetary['id'],
                'title' => $monetary['title'],
                'description' => $monetary['description'],
                'discount_percentage' => (float) ($monetary['effect_payload']['percentage'] ?? 0),
                'promo_markup_rate' => (float) $resolved['price_discount_rate'],
            ];
        };

        $sharedData['flash'] = function () use ($request) {
            return [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'info' => $request->session()->get('info'),
                'status' => $request->session()->get('status'),
            ];
        };

        if ($this->shouldUseSsr($request)) {
            $sharedData['ziggy'] = function () use ($request) {
                return array_merge((new Ziggy())->toArray(), [
                    'location' => $request->url(),
                ]);
            };
        }

        return $sharedData;
    }
}
