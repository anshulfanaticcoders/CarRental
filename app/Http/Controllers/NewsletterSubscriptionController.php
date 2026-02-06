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

    public function confirm(Request $request, $locale, $subscription)
    {
        $locale = $locale ?: config('app.locale', 'en');

        $subscriptionModel = NewsletterSubscription::find($subscription);

        if (!$subscriptionModel) {
            \Log::warning('Newsletter confirm: subscription not found', [
                'subscription' => $subscription,
                'route_params' => $request->route()?->parameters() ?? [],
            ]);
            return redirect()
                ->route('welcome', ['locale' => $locale])
                ->with('error', 'Invalid or expired confirmation link.');
        }

        if ($subscriptionModel->status !== 'subscribed') {
            $updated = NewsletterSubscription::whereKey($subscriptionModel->id)->update([
                'status' => 'subscribed',
                'confirmed_at' => now(),
                'unsubscribed_at' => null,
            ]);

            if ($updated === 0) {
                \Log::error('Newsletter confirm: update failed', [
                    'subscription_id' => $subscriptionModel->id,
                ]);
            }
        }

        $subscriptionModel->refresh();

        if ($subscriptionModel->status !== 'subscribed') {
            \Log::error('Newsletter confirm: status not updated', [
                'subscription_id' => $subscriptionModel->id,
                'status' => $subscriptionModel->status,
            ]);
            return redirect()
                ->route('welcome', ['locale' => $locale])
                ->with('error', 'Unable to confirm subscription. Please try again.');
        }

        return redirect()
            ->route('welcome', ['locale' => $locale])
            ->with('success', 'Newsletter subscription confirmed.');
    }
}
