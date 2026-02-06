<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscription;
use App\Notifications\NewsletterSubscriptionConfirmation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class NewsletterSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email:rfc,dns|max:254',
            'source' => 'nullable|string|max:50',
            'locale' => 'nullable|string|in:en,fr,nl,es,ar',
        ]);

        $email = NewsletterSubscription::normalizeEmail($validated['email']);
        $locale = $validated['locale'] ?? config('app.locale', 'en');

        $subscription = NewsletterSubscription::where('email', $email)->first();

        if (!$subscription) {
            $subscription = NewsletterSubscription::create([
                'email' => $email,
                'status' => 'pending',
                'source' => $validated['source'] ?? 'footer',
                'locale' => $locale,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 512),
            ]);
        } elseif ($subscription->status === 'subscribed') {
            return response()->json([
                'message' => 'This email is already subscribed.'
            ], 409);
        } else {
            $subscription->status = 'pending';
            $subscription->confirmed_at = null;
            $subscription->unsubscribed_at = null;
            $subscription->source = $validated['source'] ?? $subscription->source;
            $subscription->locale = $locale;
            $subscription->ip_address = $request->ip();
            $subscription->user_agent = substr((string) $request->userAgent(), 0, 512);
            $subscription->save();
        }

        if ($subscription->status !== 'subscribed') {
            try {
                Notification::route('mail', $email)
                    ->notify(new NewsletterSubscriptionConfirmation($subscription, $locale));
            } catch (\Throwable $e) {
                \Log::error('Newsletter confirmation email failed', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'message' => 'If this email can be subscribed, a confirmation link has been sent.'
        ], 202);
    }

    public function confirm(Request $request, NewsletterSubscription $subscription)
    {
        if ($subscription->status !== 'subscribed') {
            $subscription->status = 'subscribed';
            $subscription->confirmed_at = now();
            $subscription->unsubscribed_at = null;
            $subscription->save();
        }

        $locale = $request->route('locale') ?? config('app.locale', 'en');

        return redirect()
            ->route('welcome', ['locale' => $locale])
            ->with('success', 'Newsletter subscription confirmed.');
    }
}
