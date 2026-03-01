<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendNewsletterCampaignJob;
use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterCampaignLog;
use App\Models\NewsletterSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class NewsletterCampaignController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $query = NewsletterCampaign::with('creator');

        if ($search !== '') {
            $query->where('subject', 'like', '%' . $search . '%');
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $campaigns = $query
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        // Append computed rates
        $campaigns->getCollection()->transform(function ($campaign) {
            $campaign->open_rate = $campaign->open_rate;
            $campaign->click_rate = $campaign->click_rate;
            return $campaign;
        });

        $statusCounts = [
            'draft' => NewsletterCampaign::draft()->count(),
            'scheduled' => NewsletterCampaign::scheduled()->count(),
            'sending' => NewsletterCampaign::sending()->count(),
            'sent' => NewsletterCampaign::sent()->count(),
            'activeSubscribers' => NewsletterSubscription::where('status', 'subscribed')->count(),
        ];

        return Inertia::render('AdminDashboardPages/Newsletter/Campaigns/Index', [
            'campaigns' => $campaigns,
            'statusCounts' => $statusCounts,
            'filters' => ['search' => $search, 'status' => $status],
            'flash' => session()->only(['success', 'error']),
        ]);
    }

    public function create()
    {
        $subscriberCount = NewsletterSubscription::where('status', 'subscribed')->count();

        return Inertia::render('AdminDashboardPages/Newsletter/Campaigns/Create', [
            'subscriberCount' => $subscriberCount,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        NewsletterCampaign::create([
            ...$validated,
            'status' => NewsletterCampaign::STATUS_DRAFT,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.newsletter-campaigns.index')
            ->with('success', 'Campaign saved as draft.');
    }

    public function show(NewsletterCampaign $campaign)
    {
        $campaign->load('creator');
        $campaign->open_rate = $campaign->open_rate;
        $campaign->click_rate = $campaign->click_rate;

        $logs = $campaign->logs()
            ->with('subscription')
            ->orderByDesc('sent_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('AdminDashboardPages/Newsletter/Campaigns/Show', [
            'campaign' => $campaign,
            'logs' => $logs,
            'flash' => session()->only(['success', 'error']),
        ]);
    }

    public function edit(NewsletterCampaign $campaign)
    {
        if (! $campaign->isEditable()) {
            return redirect()->route('admin.newsletter-campaigns.index')
                ->with('error', 'This campaign cannot be edited.');
        }

        $subscriberCount = NewsletterSubscription::where('status', 'subscribed')->count();

        return Inertia::render('AdminDashboardPages/Newsletter/Campaigns/Edit', [
            'campaign' => $campaign,
            'subscriberCount' => $subscriberCount,
        ]);
    }

    public function update(Request $request, NewsletterCampaign $campaign)
    {
        if (! $campaign->isEditable()) {
            return redirect()->route('admin.newsletter-campaigns.index')
                ->with('error', 'This campaign cannot be edited.');
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $campaign->update($validated);

        return redirect()->route('admin.newsletter-campaigns.index')
            ->with('success', 'Campaign updated.');
    }

    public function destroy(NewsletterCampaign $campaign)
    {
        if (! $campaign->isDraft()) {
            return redirect()->route('admin.newsletter-campaigns.index')
                ->with('error', 'Only draft campaigns can be deleted.');
        }

        $campaign->delete();

        return redirect()->route('admin.newsletter-campaigns.index')
            ->with('success', 'Campaign deleted.');
    }

    public function send(NewsletterCampaign $campaign)
    {
        if (! $campaign->isDraft() && $campaign->status !== NewsletterCampaign::STATUS_SCHEDULED) {
            return redirect()->back()->with('error', 'This campaign cannot be sent.');
        }

        SendNewsletterCampaignJob::dispatch($campaign);

        return redirect()->route('admin.newsletter-campaigns.show', $campaign)
            ->with('success', 'Campaign is being sent.');
    }

    public function schedule(Request $request, NewsletterCampaign $campaign)
    {
        if (! $campaign->isEditable()) {
            return redirect()->back()->with('error', 'This campaign cannot be scheduled.');
        }

        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $campaign->update([
            'status' => NewsletterCampaign::STATUS_SCHEDULED,
            'scheduled_at' => $validated['scheduled_at'],
        ]);

        return redirect()->route('admin.newsletter-campaigns.show', $campaign)
            ->with('success', 'Campaign scheduled for ' . $campaign->scheduled_at->format('M d, Y H:i'));
    }

    public function cancel(NewsletterCampaign $campaign)
    {
        if (! $campaign->isCancellable()) {
            return redirect()->back()->with('error', 'This campaign cannot be cancelled.');
        }

        $campaign->update([
            'status' => NewsletterCampaign::STATUS_CANCELLED,
        ]);

        return redirect()->route('admin.newsletter-campaigns.show', $campaign)
            ->with('success', 'Campaign cancelled.');
    }

    public function testEmail(NewsletterCampaign $campaign)
    {
        $adminEmail = Auth::user()->email;

        try {
            $tempSubscription = NewsletterSubscription::where('status', 'subscribed')->first()
                ?? new NewsletterSubscription(['email' => $adminEmail, 'status' => 'subscribed', 'id' => 0]);

            $tempLog = new NewsletterCampaignLog([
                'campaign_id' => $campaign->id,
                'subscription_id' => $tempSubscription->id ?? 0,
            ]);
            $tempLog->id = 0;
            $tempLog->setRelation('campaign', $campaign);
            $tempLog->setRelation('subscription', $tempSubscription);

            Mail::to($adminEmail)->send(new NewsletterCampaignMail($tempLog, $tempSubscription));

            return redirect()->back()->with('success', 'Test email sent to ' . $adminEmail);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Failed to send test email: ' . $e->getMessage());
        }
    }

    public function exportSubscribers()
    {
        $subscribers = NewsletterSubscription::where('status', 'subscribed')
            ->orderBy('email')
            ->get(['email', 'status', 'confirmed_at', 'source', 'locale', 'created_at']);

        $csv = "Email,Status,Confirmed At,Source,Locale,Subscribed At\n";
        foreach ($subscribers as $sub) {
            $csv .= implode(',', [
                $sub->email,
                $sub->status,
                $sub->confirmed_at?->toDateTimeString() ?? '',
                $sub->source ?? '',
                $sub->locale ?? '',
                $sub->created_at->toDateTimeString(),
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="newsletter-subscribers-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }
}
