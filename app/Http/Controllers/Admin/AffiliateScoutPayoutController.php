<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate\AffiliateBusiness;
use App\Models\Affiliate\AffiliateCommission;
use App\Models\Affiliate\AffiliatePayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AffiliateScoutPayoutController extends Controller
{
    public function index(Request $request)
    {
        $payouts = AffiliatePayout::with('business:id,name,currency', 'paidBy:id,first_name,last_name')
            ->latest()
            ->paginate(20);

        // Summary of pending commissions per business
        $pendingSummary = AffiliateCommission::where('status', 'approved')
            ->whereNull('payout_id')
            ->select('business_id', DB::raw('SUM(commission_amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('business_id')
            ->with('business:id,name,currency,bank_name,bank_iban')
            ->get();

        return Inertia::render('AdminDashboardPages/Affiliate/Payouts', [
            'payouts' => $payouts,
            'pendingSummary' => $pendingSummary,
        ]);
    }

    public function createPayout(Request $request)
    {
        $validated = $request->validate([
            'business_id' => 'required|exists:affiliate_businesses,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $business = AffiliateBusiness::findOrFail($validated['business_id']);

        // Get approved commissions without a payout
        $commissions = AffiliateCommission::where('business_id', $business->id)
            ->where('status', 'approved')
            ->whereNull('payout_id')
            ->whereBetween('created_at', [$validated['period_start'], $validated['period_end']])
            ->get();

        if ($commissions->isEmpty()) {
            return back()->with('error', 'No approved commissions found for this period.');
        }

        $totalAmount = $commissions->sum('commission_amount');

        DB::transaction(function () use ($business, $validated, $commissions, $totalAmount) {
            $payout = AffiliatePayout::create([
                'uuid' => (string) Str::uuid(),
                'business_id' => $business->id,
                'total_amount' => $totalAmount,
                'currency' => $business->currency ?? 'EUR',
                'status' => 'pending',
                'period_start' => $validated['period_start'],
                'period_end' => $validated['period_end'],
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);

            // Link commissions to this payout
            AffiliateCommission::whereIn('id', $commissions->pluck('id'))
                ->update(['payout_id' => $payout->id]);
        });

        return back()->with('success', "Payout of {$totalAmount} {$business->currency} created for {$business->name}.");
    }

    public function markAsPaid(Request $request, AffiliatePayout $payout)
    {
        $validated = $request->validate([
            'bank_transfer_reference' => 'required|string|max:255',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $payout->update([
            'status' => 'paid',
            'bank_transfer_reference' => $validated['bank_transfer_reference'],
            'paid_at' => now(),
            'paid_by' => $request->user()->id,
            'admin_notes' => $validated['admin_notes'] ?? $payout->admin_notes,
        ]);

        // Mark associated commissions as paid
        $payout->commissions()->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => 'bank_transfer',
            'transaction_reference' => $validated['bank_transfer_reference'],
        ]);

        return back()->with('success', 'Payout marked as paid.');
    }
}
