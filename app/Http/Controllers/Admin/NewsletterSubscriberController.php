<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscription;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NewsletterSubscriberController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $query = NewsletterSubscription::query();

        if ($search !== '') {
            $query->where('email', 'like', '%' . $search . '%');
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $subscriptions = $query
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $statusCounts = [
            'total' => NewsletterSubscription::count(),
            'pending' => NewsletterSubscription::where('status', 'pending')->count(),
            'subscribed' => NewsletterSubscription::where('status', 'subscribed')->count(),
            'unsubscribed' => NewsletterSubscription::where('status', 'unsubscribed')->count(),
        ];

        return Inertia::render('AdminDashboardPages/Newsletter/Index', [
            'subscriptions' => $subscriptions,
            'statusCounts' => $statusCounts,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'flash' => session()->only(['success', 'error']),
        ]);
    }

    public function cancel(NewsletterSubscription $subscription)
    {
        if ($subscription->status !== 'unsubscribed') {
            $subscription->status = 'unsubscribed';
            $subscription->unsubscribed_at = now();
            $subscription->save();
        }

        return redirect()->back()->with('success', 'Subscription cancelled successfully.');
    }

    public function destroy(NewsletterSubscription $subscription)
    {
        $subscription->delete();

        return redirect()->back()->with('success', 'Subscriber deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:newsletter_subscriptions,id'],
        ]);

        $ids = array_values(array_unique($validated['ids']));
        $deleted = NewsletterSubscription::whereIn('id', $ids)->delete();

        return redirect()->back()->with('success', "{$deleted} subscriber(s) deleted successfully.");
    }

    public function bulkCancel(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:newsletter_subscriptions,id'],
        ]);

        $ids = array_values(array_unique($validated['ids']));
        $cancelled = NewsletterSubscription::whereIn('id', $ids)
            ->where('status', '!=', 'unsubscribed')
            ->update([
                'status' => 'unsubscribed',
                'unsubscribed_at' => now(),
            ]);

        return redirect()->back()->with('success', "{$cancelled} subscription(s) cancelled successfully.");
    }
}
