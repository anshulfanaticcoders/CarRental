<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Confirmation - {{ $booking->booking_number }}</title>
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #1a1a2e;
            background: #fff;
        }
        .page { max-width: 800px; margin: 0 auto; }

        /* ── Header ── */
        .header {
            background: #153B4F;
            padding: 28px 36px;
            color: #fff;
            position: relative;
        }
        .header::after {
            content: '';
            position: absolute; top: 0; right: 0; bottom: 0;
            width: 200px;
            background: linear-gradient(135deg, transparent 0%, rgba(46,167,173,0.12) 100%);
        }
        .header-inner { position: relative; z-index: 1; }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .brand-name {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        .doc-type {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: rgba(255,255,255,0.6);
            margin-top: 2px;
        }
        .header-right { text-align: right; }
        .booking-num {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.03em;
        }
        .booking-date {
            font-size: 10px;
            color: rgba(255,255,255,0.7);
            margin-top: 3px;
        }
        .status-pill {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-top: 6px;
        }
        .status-confirmed { background: rgba(16,185,129,0.25); color: #6ee7b7; border: 1px solid rgba(16,185,129,0.4); }
        .status-pending { background: rgba(245,158,11,0.25); color: #fcd34d; border: 1px solid rgba(245,158,11,0.4); }
        .status-completed { background: rgba(59,130,246,0.25); color: #93c5fd; border: 1px solid rgba(59,130,246,0.4); }
        .status-cancelled { background: rgba(239,68,68,0.25); color: #fca5a5; border: 1px solid rgba(239,68,68,0.4); }

        /* ── Quick Summary Strip ── */
        .summary-strip {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 14px 36px;
        }
        .summary-strip table { width: 100%; border-collapse: collapse; }
        .summary-strip td { padding: 0 8px; vertical-align: top; }
        .summary-strip td:first-child { padding-left: 0; }
        .summary-strip td:last-child { padding-right: 0; }
        .sum-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #94a3b8;
            font-weight: 600;
        }
        .sum-value {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 1px;
        }

        /* ── Content Area ── */
        .content { padding: 24px 36px; }

        /* ── Section Headers ── */
        .section { margin-bottom: 22px; }
        .section-head {
            font-size: 11px;
            font-weight: 700;
            color: #153B4F;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            padding-bottom: 6px;
            border-bottom: 2px solid #153B4F;
            margin-bottom: 12px;
        }

        /* ── Two-column grid ── */
        .grid-2 { width: 100%; border-collapse: collapse; }
        .grid-2 td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }
        .grid-2 td:first-child { padding-right: 10px; }
        .grid-2 td:last-child { padding-left: 10px; }

        /* ── Info blocks ── */
        .info-block {
            padding: 8px 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 8px;
        }
        .info-block-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .info-block-value {
            font-size: 11px;
            font-weight: 600;
            color: #0f172a;
        }
        .info-block-full {
            padding: 8px 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 8px;
        }

        /* ── Location timeline ── */
        .loc-row { display: flex; margin-bottom: 10px; }
        .loc-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            margin-top: 3px;
            margin-right: 10px;
            flex-shrink: 0;
        }
        .loc-dot-pickup { background: #10b981; border: 2px solid #d1fae5; }
        .loc-dot-return { background: #ef4444; border: 2px solid #fee2e2; }
        .loc-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }
        .loc-label-pickup { color: #059669; }
        .loc-label-return { color: #dc2626; }
        .loc-datetime {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 1px;
        }
        .loc-place {
            font-size: 10px;
            color: #64748b;
            margin-top: 1px;
        }

        /* ── Pricing table ── */
        .price-table {
            width: 100%;
            border-collapse: collapse;
        }
        .price-table th {
            text-align: left;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            font-weight: 600;
            padding: 6px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .price-table th:last-child { text-align: right; }
        .price-table td {
            padding: 7px 0;
            font-size: 11px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }
        .price-table td:last-child {
            text-align: right;
            font-weight: 600;
            color: #0f172a;
        }
        .price-table .subtotal-row td {
            border-top: 1px solid #e2e8f0;
            border-bottom: none;
            padding-top: 8px;
        }

        /* ── Grand total bar ── */
        .grand-total-bar {
            background: #153B4F;
            padding: 12px 14px;
            border-radius: 8px;
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .grand-total-bar table { width: 100%; border-collapse: collapse; }
        .grand-total-bar td { padding: 0; color: #fff; vertical-align: middle; }
        .gt-label {
            font-size: 11px;
            font-weight: 600;
            color: rgba(255,255,255,0.75);
        }
        .gt-amount {
            font-size: 20px;
            font-weight: 800;
            color: #fff;
            text-align: right;
            letter-spacing: -0.02em;
        }

        /* ── Payment split ── */
        .payment-split { margin-top: 10px; }
        .payment-split table { width: 100%; border-collapse: collapse; }
        .payment-split td { padding: 6px 10px; vertical-align: top; }
        .pay-card {
            border-radius: 6px;
            padding: 8px 12px;
        }
        .pay-card-paid { background: #ecfdf5; border: 1px solid #a7f3d0; }
        .pay-card-due { background: #fffbeb; border: 1px solid #fde68a; }
        .pay-card-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }
        .pay-card-label-paid { color: #059669; }
        .pay-card-label-due { color: #d97706; }
        .pay-card-amount {
            font-size: 16px;
            font-weight: 800;
            margin-top: 2px;
        }
        .pay-card-amount-paid { color: #065f46; }
        .pay-card-amount-due { color: #92400e; }
        .pay-card-note {
            font-size: 9px;
            margin-top: 2px;
        }
        .pay-card-note-paid { color: #6ee7b7; }
        .pay-card-note-due { color: #fbbf24; }

        /* ── Extras table ── */
        .extras-table {
            width: 100%;
            border-collapse: collapse;
        }
        .extras-table td {
            padding: 5px 0;
            font-size: 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        .extras-table td:last-child { text-align: right; font-weight: 600; }

        /* ── Contact grid ── */
        .contact-grid { width: 100%; border-collapse: collapse; }
        .contact-grid td { padding: 0 8px; vertical-align: top; width: 50%; }
        .contact-grid td:first-child { padding-left: 0; }
        .contact-grid td:last-child { padding-right: 0; }
        .contact-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
        }
        .contact-card-title {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .contact-name {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .contact-detail {
            font-size: 10px;
            color: #64748b;
            margin-bottom: 2px;
        }

        /* ── Policies ── */
        .policy-grid { width: 100%; border-collapse: collapse; }
        .policy-grid td {
            padding: 4px 6px;
            vertical-align: top;
            width: 25%;
        }
        .policy-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 8px;
            text-align: center;
        }
        .policy-label {
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            font-weight: 600;
        }
        .policy-val {
            font-size: 10px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 2px;
        }

        /* ── Notes ── */
        .note-box {
            background: #eff6ff;
            border-left: 3px solid #3b82f6;
            padding: 8px 12px;
            font-size: 10px;
            color: #1e40af;
            border-radius: 0 4px 4px 0;
            margin-top: 8px;
        }

        /* ── Footer ── */
        .footer {
            background: #f8fafc;
            padding: 14px 36px;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }
        .footer strong { color: #64748b; }

        /* ── Print ── */
        @media print {
            body { background: #fff; }
            .page { max-width: none; }
        }
    </style>
</head>
<body>
@php
    $providerMetadata = $booking->provider_metadata ?? [];
    if (is_string($providerMetadata)) {
        $providerMetadata = json_decode($providerMetadata, true) ?? [];
    }
    $providerPricing = $providerMetadata['provider_pricing'] ?? [];
    $customerPricing = $providerMetadata['customer_pricing'] ?? [];
    $policies = $providerMetadata['benefits'] ?? $providerMetadata['policies'] ?? [];
    $amounts = $booking->amounts ?? null;
    $currency = $booking->booking_currency ?? 'EUR';

    $paidPercentage = $booking->total_amount > 0 ? round(($booking->amount_paid / $booking->total_amount) * 100) : 0;
    $isPOA = $paidPercentage > 0 && $paidPercentage < 100;
    $duePercentage = 100 - $paidPercentage;

    $statusClass = match($booking->booking_status) {
        'confirmed' => 'status-confirmed',
        'completed' => 'status-completed',
        'cancelled' => 'status-cancelled',
        default => 'status-pending',
    };

    $pickupLocation = $providerMetadata['pickup_location_details'] ?? $providerMetadata['location'] ?? [];
    $dropoffLocation = $providerMetadata['dropoff_location_details'] ?? [];
@endphp
<div class="page">

    {{-- ═══ HEADER ═══ --}}
    <div class="header">
        <div class="header-inner">
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="vertical-align:top;">
                        <div class="brand-name">vrooem.</div>
                        <div class="doc-type">Booking Confirmation</div>
                    </td>
                    <td style="text-align:right; vertical-align:top;">
                        <div class="booking-num">#{{ $booking->booking_number }}</div>
                        <div class="booking-date">{{ date('F j, Y', strtotime($booking->created_at)) }}</div>
                        <div class="status-pill {{ $statusClass }}">{{ ucfirst($booking->booking_status) }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ═══ QUICK SUMMARY STRIP ═══ --}}
    <div class="summary-strip">
        <table>
            <tr>
                <td>
                    <div class="sum-label">Pickup</div>
                    <div class="sum-value">{{ date('M j, Y', strtotime($booking->pickup_date)) }}</div>
                </td>
                <td>
                    <div class="sum-label">Return</div>
                    <div class="sum-value">{{ date('M j, Y', strtotime($booking->return_date)) }}</div>
                </td>
                <td>
                    <div class="sum-label">Duration</div>
                    <div class="sum-value">{{ $booking->total_days }} {{ $booking->total_days == 1 ? 'Day' : 'Days' }}</div>
                </td>
                <td>
                    <div class="sum-label">Currency</div>
                    <div class="sum-value">{{ $currency }}</div>
                </td>
                <td style="text-align:right;">
                    <div class="sum-label">Total</div>
                    <div class="sum-value" style="font-size:14px; color:#153B4F;">{{ $currency }} {{ number_format($booking->total_amount ?? 0, 2) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">

        {{-- ═══ TRIP DETAILS ═══ --}}
        <div class="section">
            <div class="section-head">Trip Details</div>
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="width:55%; vertical-align:top; padding-right:16px;">
                        {{-- Pickup --}}
                        <table style="width:100%; border-collapse:collapse; margin-bottom:12px;">
                            <tr>
                                <td style="width:16px; vertical-align:top; padding-top:3px;">
                                    <div class="loc-dot loc-dot-pickup"></div>
                                </td>
                                <td>
                                    <div class="loc-label loc-label-pickup">Pickup</div>
                                    <div class="loc-datetime">{{ date('D, M j, Y', strtotime($booking->pickup_date)) }} &middot; {{ date('g:i A', strtotime($booking->pickup_time)) }}</div>
                                    <div class="loc-place">{{ $booking->pickup_location }}</div>
                                    @if(!empty($pickupLocation['name']))
                                        <div class="loc-place" style="font-weight:600; color:#334155;">{{ $pickupLocation['name'] }}</div>
                                    @endif
                                </td>
                            </tr>
                        </table>
                        {{-- Return --}}
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td style="width:16px; vertical-align:top; padding-top:3px;">
                                    <div class="loc-dot loc-dot-return"></div>
                                </td>
                                <td>
                                    <div class="loc-label loc-label-return">Return</div>
                                    <div class="loc-datetime">{{ date('D, M j, Y', strtotime($booking->return_date)) }} &middot; {{ date('g:i A', strtotime($booking->return_time)) }}</div>
                                    <div class="loc-place">{{ $booking->return_location ?? $booking->pickup_location }}</div>
                                    @if(!empty($dropoffLocation['name']))
                                        <div class="loc-place" style="font-weight:600; color:#334155;">{{ $dropoffLocation['name'] }}</div>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style="width:45%; vertical-align:top;">
                        {{-- Vehicle card --}}
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px;">
                            @if(isset($vehicle->images[0]['image_url']) || (is_array($vehicle) && isset($vehicle['images'][0]['image_url'])))
                                @php
                                    $imgUrl = is_object($vehicle) ? ($vehicle->images[0]['image_url'] ?? '') : ($vehicle['images'][0]['image_url'] ?? '');
                                @endphp
                                @if($imgUrl)
                                    <img src="{{ $imgUrl }}" alt="Vehicle" style="width:100%; height:80px; object-fit:contain; border-radius:6px; margin-bottom:8px;">
                                @endif
                            @endif
                            <div style="font-size:14px; font-weight:800; color:#0f172a; margin-bottom:4px;">
                                {{ is_object($vehicle) ? ($vehicle->brand ?? '') . ' ' . ($vehicle->model ?? $vehicle->vehicle_name ?? '') : (($vehicle['brand'] ?? '') . ' ' . ($vehicle['model'] ?? $vehicle['vehicle_name'] ?? '')) }}
                            </div>
                            <table style="width:100%; border-collapse:collapse; margin-top:6px;">
                                @php
                                    $trans = is_object($vehicle) ? ($vehicle->transmission ?? null) : ($vehicle['transmission'] ?? null);
                                    $fuel = is_object($vehicle) ? ($vehicle->fuel ?? null) : ($vehicle['fuel'] ?? null);
                                    $seats = is_object($vehicle) ? ($vehicle->seating_capacity ?? null) : ($vehicle['seating_capacity'] ?? null);
                                @endphp
                                <tr>
                                    @if($trans)
                                        <td style="font-size:9px; color:#64748b; padding:2px 0;">
                                            <span style="display:inline-block; padding:2px 6px; background:#e2e8f0; border-radius:3px; font-weight:600; font-size:8px; color:#475569;">{{ ucfirst($trans) }}</span>
                                        </td>
                                    @endif
                                    @if($fuel)
                                        <td style="font-size:9px; color:#64748b; padding:2px 0;">
                                            <span style="display:inline-block; padding:2px 6px; background:#e2e8f0; border-radius:3px; font-weight:600; font-size:8px; color:#475569;">{{ ucfirst($fuel) }}</span>
                                        </td>
                                    @endif
                                    @if($seats)
                                        <td style="font-size:9px; color:#64748b; padding:2px 0;">
                                            <span style="display:inline-block; padding:2px 6px; background:#e2e8f0; border-radius:3px; font-weight:600; font-size:8px; color:#475569;">{{ $seats }} Seats</span>
                                        </td>
                                    @endif
                                </tr>
                            </table>
                            @if($booking->plan)
                                <div style="margin-top:6px; font-size:9px; color:#2ea7ad; font-weight:700;">Plan: {{ $booking->plan }}</div>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- ═══ PAYMENT SUMMARY ═══ --}}
        <div class="section">
            <div class="section-head">Payment Summary</div>

            <table class="price-table">
                <thead>
                    <tr>
                        <th style="width:55%;">Description</th>
                        <th>Details</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Vehicle Rental</td>
                        <td style="color:#94a3b8;">{{ $booking->total_days }} {{ $booking->total_days == 1 ? 'day' : 'days' }}</td>
                        <td>{{ $currency }} {{ number_format($booking->base_price ?? 0, 2) }}</td>
                    </tr>

                    @if($booking->extras && $booking->extras->count() > 0)
                        @foreach($booking->extras as $extra)
                            <tr>
                                <td>{{ $extra->extra_name ?? $extra->name ?? 'Extra' }}</td>
                                <td style="color:#94a3b8;">{{ $extra->quantity ?? 1 }}x</td>
                                <td>{{ $currency }} {{ number_format(($extra->price ?? 0) * ($extra->quantity ?? 1), 2) }}</td>
                            </tr>
                        @endforeach
                    @elseif($booking->extra_charges > 0)
                        <tr>
                            <td>Extras & Add-ons</td>
                            <td style="color:#94a3b8;">&mdash;</td>
                            <td>{{ $currency }} {{ number_format($booking->extra_charges, 2) }}</td>
                        </tr>
                    @endif

                    @if($booking->tax_amount > 0)
                        <tr>
                            <td>Taxes & Fees</td>
                            <td style="color:#94a3b8;">&mdash;</td>
                            <td>{{ $currency }} {{ number_format($booking->tax_amount, 2) }}</td>
                        </tr>
                    @endif

                    @if($booking->discount_amount > 0)
                        <tr>
                            <td style="color:#059669;">Discount</td>
                            <td style="color:#94a3b8;">&mdash;</td>
                            <td style="color:#059669;">-{{ $currency }} {{ number_format($booking->discount_amount, 2) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>

            {{-- Grand total --}}
            <div class="grand-total-bar">
                <table>
                    <tr>
                        <td><span class="gt-label">Grand Total</span></td>
                        <td style="text-align:right;"><span class="gt-amount">{{ $currency }} {{ number_format($booking->total_amount ?? 0, 2) }}</span></td>
                    </tr>
                </table>
            </div>

            {{-- Payment split --}}
            @if($booking->amount_paid > 0)
                <div class="payment-split">
                    <table>
                        <tr>
                            <td style="padding-left:0; width:50%;">
                                <div class="pay-card pay-card-paid">
                                    <div class="pay-card-label pay-card-label-paid">{{ $isPOA ? "Paid Online ({$paidPercentage}%)" : 'Paid in Full' }}</div>
                                    <div class="pay-card-amount pay-card-amount-paid">{{ $currency }} {{ number_format($booking->amount_paid, 2) }}</div>
                                    <div class="pay-card-note" style="color:#059669;">via Stripe</div>
                                </div>
                            </td>
                            @if($booking->pending_amount > 0)
                                <td style="padding-right:0; width:50%;">
                                    <div class="pay-card pay-card-due">
                                        <div class="pay-card-label pay-card-label-due">Due at Pickup ({{ $duePercentage }}%)</div>
                                        <div class="pay-card-amount pay-card-amount-due">{{ $currency }} {{ number_format($booking->pending_amount, 2) }}</div>
                                        <div class="pay-card-note" style="color:#d97706;">Pay to vendor on arrival</div>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    </table>
                </div>
            @endif

            {{-- Payment info --}}
            <div style="margin-top:8px; font-size:9px; color:#94a3b8;">
                <strong style="color:#64748b;">Payment:</strong>
                {{ ucfirst($payment->payment_method ?? $payment->method ?? 'Card') }}
                @if(!empty($payment->transaction_id) && $payment->transaction_id !== 'N/A')
                    &middot; Transaction: {{ $payment->transaction_id }}
                @endif
                @if(!empty($payment->payment_date))
                    &middot; {{ date('M j, Y', strtotime($payment->payment_date)) }}
                @endif
            </div>

            {{-- Multi-currency note --}}
            @if($amounts && $amounts->vendor_currency && $amounts->vendor_currency !== $amounts->booking_currency)
                <div class="note-box" style="margin-top:10px;">
                    <strong>Currency Note:</strong>
                    Vendor receives {{ $amounts->vendor_currency }} {{ number_format($amounts->vendor_total_amount, 2) }}.
                    @if($amounts->booking_to_vendor_rate && $amounts->booking_to_vendor_rate != 1)
                        Exchange rate: 1 {{ $amounts->booking_currency }} &asymp; {{ number_format($amounts->booking_to_vendor_rate, 4) }} {{ $amounts->vendor_currency }}.
                    @endif
                </div>
            @endif
        </div>

        {{-- ═══ DEPOSIT & SECURITY ═══ --}}
        @php
            $benefitsData = $providerMetadata['benefits'] ?? [];
            $depositAmount = $benefitsData['deposit_amount'] ?? $providerMetadata['deposit_amount'] ?? $providerMetadata['provider_pricing']['deposit_amount'] ?? $providerMetadata['deposit'] ?? $providerMetadata['Deposit'] ?? null;
            $securityDeposit = $benefitsData['security_deposit'] ?? null;
            $depositPaymentMethod = $benefitsData['deposit_payment_method'] ?? null;
            $selectedDepositType = $benefitsData['selected_deposit_type'] ?? null;
            $excessAmount = $benefitsData['excess_amount'] ?? $providerMetadata['excess_amount'] ?? $providerMetadata['provider_pricing']['excess_amount'] ?? $providerMetadata['excess'] ?? $providerMetadata['Excess'] ?? null;
            $excessTheftAmount = $benefitsData['excess_theft_amount'] ?? $providerMetadata['excess_theft_amount'] ?? $providerMetadata['provider_pricing']['excess_theft_amount'] ?? null;
            $depositCurrency = $benefitsData['deposit_currency'] ?? $providerMetadata['deposit_currency'] ?? $providerMetadata['provider_pricing']['deposit_currency'] ?? $providerMetadata['currency'] ?? $currency;
            // Use security_deposit for internal vehicles if no provider deposit_amount
            $displayDeposit = $securityDeposit ?: $depositAmount;
        @endphp
        @if($displayDeposit || $excessAmount)
            <div class="section">
                <div class="section-head">Deposit & Insurance</div>
                <table class="grid-2">
                    <tr>
                        @if($displayDeposit)
                            <td>
                                <div class="info-block">
                                    <div class="info-block-label">Security Deposit</div>
                                    <div class="info-block-value">{{ $depositCurrency }} {{ number_format($displayDeposit, 2) }}</div>
                                    @if($selectedDepositType)
                                        <div style="font-size:8px; color:#3b82f6; margin-top:2px;">Payment via: {{ ucwords(str_replace(['_', '-'], ' ', $selectedDepositType)) }}</div>
                                    @elseif($depositPaymentMethod)
                                        @php
                                            $methods = $depositPaymentMethod;
                                            if (is_string($methods)) {
                                                $decoded = json_decode($methods, true);
                                                $methods = is_array($decoded) ? $decoded : [$methods];
                                            }
                                            $methodText = collect((array) $methods)->map(fn($m) => ucwords(str_replace(['_', '-'], ' ', $m)))->implode(', ');
                                        @endphp
                                        <div style="font-size:8px; color:#3b82f6; margin-top:2px;">Accepted: {{ $methodText }}</div>
                                    @else
                                        <div style="font-size:8px; color:#94a3b8; margin-top:2px;">Held on card at pickup, refundable</div>
                                    @endif
                                </div>
                            </td>
                        @endif
                        @if($excessAmount)
                            <td>
                                <div class="info-block">
                                    <div class="info-block-label">Insurance Excess</div>
                                    <div class="info-block-value">{{ $depositCurrency }} {{ number_format($excessAmount, 2) }}</div>
                                    <div style="font-size:8px; color:#94a3b8; margin-top:2px;">Deductible amount</div>
                                </div>
                            </td>
                        @endif
                        @if($excessTheftAmount)
                            <td>
                                <div class="info-block">
                                    <div class="info-block-label">Theft Excess</div>
                                    <div class="info-block-value">{{ $depositCurrency }} {{ number_format($excessTheftAmount, 2) }}</div>
                                    <div style="font-size:8px; color:#94a3b8; margin-top:2px;">Theft deductible</div>
                                </div>
                            </td>
                        @endif
                    </tr>
                </table>
            </div>
        @endif

        {{-- ═══ RENTAL POLICIES ═══ --}}
        @if(!empty($policies) || $booking->provider_source)
            @php
                $fuelPolicy = $policies['fuel_policy'] ?? $policies['fuelpolicy'] ?? $providerMetadata['fuel_policy'] ?? null;

                // Mileage - check limited_km fields
                $mileage = 'Unlimited';
                if (!empty($policies['limited_km_per_day']) && ($policies['limited_km_per_day'] == 1 || $policies['limited_km_per_day'] === true)) {
                    $range = $policies['limited_km_per_day_range'] ?? null;
                    $pricePerKm = $policies['price_per_km_per_day'] ?? null;
                    $mileage = $range ? "{$range} km/day" : 'Limited';
                    if ($pricePerKm) $mileage .= " (+{$pricePerKm}/km)";
                } elseif (isset($policies['unlimited_mileage']) && !$policies['unlimited_mileage']) {
                    $mileage = $policies['included_km'] ?? 'Limited';
                } elseif (!empty($policies['mileage']) && $policies['mileage'] !== 'Unlimited') {
                    $mileage = $policies['mileage'];
                }

                $minAge = $policies['minimum_driver_age'] ?? $providerMetadata['min_age'] ?? null;

                // Cancellation
                $cancellation = 'Non-refundable';
                if (!empty($policies['cancellation_available_per_day']) && ($policies['cancellation_available_per_day'] == 1 || $policies['cancellation_available_per_day'] === true)) {
                    $days = $policies['cancellation_available_per_day_date'] ?? null;
                    $cancellation = $days ? "Free cancellation ({$days} days before)" : 'Free cancellation';
                }
            @endphp
            <div class="section">
                <div class="section-head">Rental Policies</div>
                <table class="policy-grid">
                    <tr>
                        @if($fuelPolicy)
                        <td>
                            <div class="policy-box">
                                <div class="policy-label">Fuel Policy</div>
                                <div class="policy-val">{{ ucfirst($fuelPolicy) }}</div>
                            </div>
                        </td>
                        @endif
                        <td>
                            <div class="policy-box">
                                <div class="policy-label">Mileage</div>
                                <div class="policy-val">{{ $mileage }}</div>
                            </div>
                        </td>
                        @if($minAge)
                        <td>
                            <div class="policy-box">
                                <div class="policy-label">Min Age</div>
                                <div class="policy-val">{{ $minAge }}+</div>
                            </div>
                        </td>
                        @endif
                        <td>
                            <div class="policy-box">
                                <div class="policy-label">Cancellation</div>
                                <div class="policy-val" style="font-size:9px;">{{ $cancellation }}</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        {{-- ═══ CUSTOMER DETAILS ═══ --}}
        @if($booking->customer)
            <div class="section">
                <div class="section-head">Customer Details</div>
                <table class="grid-2">
                    <tr>
                        <td>
                            <div class="info-block">
                                <div class="info-block-label">Full Name</div>
                                <div class="info-block-value">{{ $booking->customer->first_name }} {{ $booking->customer->last_name }}</div>
                            </div>
                        </td>
                        <td>
                            <div class="info-block">
                                <div class="info-block-label">Email</div>
                                <div class="info-block-value">{{ $booking->customer->email ?? '-' }}</div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        @if($booking->customer->phone)
                            <td>
                                <div class="info-block">
                                    <div class="info-block-label">Phone</div>
                                    <div class="info-block-value">{{ $booking->customer->phone }}</div>
                                </div>
                            </td>
                        @endif
                        @if($booking->customer->driver_age)
                            <td>
                                <div class="info-block">
                                    <div class="info-block-label">Driver Age</div>
                                    <div class="info-block-value">{{ $booking->customer->driver_age }}</div>
                                </div>
                            </td>
                        @endif
                    </tr>
                    @if($booking->customer->flight_number)
                        <tr>
                            <td colspan="2">
                                <div class="info-block">
                                    <div class="info-block-label">Flight Number</div>
                                    <div class="info-block-value">{{ $booking->customer->flight_number }}</div>
                                </div>
                            </td>
                        </tr>
                    @endif
                </table>
            </div>
        @endif

        {{-- ═══ COMPANY & VENDOR DETAILS ═══ --}}
        @if($vendorCompany || $vendorUser || $booking->provider_source)
            <div class="section">
                <div class="section-head">{{ $vendorCompany ? 'Company & Vendor Details' : ($booking->provider_source ? 'Provider Information' : 'Vendor Details') }}</div>
                <table class="contact-grid">
                    <tr>
                        {{-- Company details --}}
                        @if($vendorCompany && $vendorCompany->company_name)
                            <td>
                                <div class="contact-card">
                                    <div class="contact-card-title">Company Information</div>
                                    <div class="contact-name">{{ $vendorCompany->company_name }}</div>
                                    @if($vendorCompany->company_email)
                                        <div class="contact-detail">{{ $vendorCompany->company_email }}</div>
                                    @endif
                                    @if($vendorCompany->company_phone_number)
                                        <div class="contact-detail">{{ $vendorCompany->company_phone_number }}</div>
                                    @endif
                                    @if($vendorCompany->company_address)
                                        <div class="contact-detail" style="margin-top:4px;">{{ $vendorCompany->company_address }}</div>
                                    @endif
                                    @if($vendorCompany->company_gst_number)
                                        <div class="contact-detail" style="margin-top:4px; font-size:9px;">GST: {{ $vendorCompany->company_gst_number }}</div>
                                    @endif
                                </div>
                            </td>
                        @endif

                        {{-- Vendor (person) details --}}
                        @if($vendorUser)
                            <td>
                                <div class="contact-card">
                                    <div class="contact-card-title">Vendor Contact</div>
                                    <div class="contact-name">{{ $vendorUser->first_name }} {{ $vendorUser->last_name }}</div>
                                    @if($vendorUser->email)
                                        <div class="contact-detail">{{ $vendorUser->email }}</div>
                                    @endif
                                    @if($vendorUser->phone)
                                        <div class="contact-detail">{{ $vendorUser->phone }}</div>
                                    @endif
                                </div>
                            </td>
                        @endif

                        {{-- External provider fallback --}}
                        @if(!$vendorCompany && !$vendorUser && $booking->provider_source)
                            <td>
                                <div class="contact-card">
                                    <div class="contact-card-title">Provider</div>
                                    <div class="contact-name">{{ ucfirst(str_replace('_', ' ', $booking->provider_source)) }}</div>
                                    @if(!empty($pickupLocation['telephone']) || !empty($pickupLocation['phone']))
                                        <div class="contact-detail">Tel: {{ $pickupLocation['telephone'] ?? $pickupLocation['phone'] }}</div>
                                    @endif
                                    @if(!empty($pickupLocation['email']))
                                        <div class="contact-detail">{{ $pickupLocation['email'] }}</div>
                                    @endif
                                </div>
                            </td>
                            @if(!empty($pickupLocation['address_1']) || !empty($pickupLocation['address_city']))
                                <td>
                                    <div class="contact-card">
                                        <div class="contact-card-title">Pickup Office</div>
                                        @if(!empty($pickupLocation['name']))
                                            <div class="contact-name" style="font-size:11px;">{{ $pickupLocation['name'] }}</div>
                                        @endif
                                        <div class="contact-detail">
                                            {{ $pickupLocation['address_1'] ?? '' }}
                                            @if(!empty($pickupLocation['address_city']))
                                                , {{ $pickupLocation['address_city'] }}
                                            @endif
                                            @if(!empty($pickupLocation['address_postcode']))
                                                {{ $pickupLocation['address_postcode'] }}
                                            @endif
                                        </div>
                                        @if(!empty($pickupLocation['collection_details']) || !empty($pickupLocation['pickup_instructions']))
                                            <div class="contact-detail" style="margin-top:4px; font-style:italic; color:#d97706;">
                                                {{ $pickupLocation['collection_details'] ?? $pickupLocation['pickup_instructions'] }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        @endif
                    </tr>
                </table>
            </div>
        @endif

        {{-- ═══ BOOKING REFERENCES ═══ --}}
        <div class="section">
            <div class="section-head">Booking References</div>
            <table class="grid-2">
                <tr>
                    <td>
                        <div class="info-block">
                            <div class="info-block-label">Booking Number</div>
                            <div class="info-block-value" style="font-family:monospace; letter-spacing:0.03em;">{{ $booking->booking_number }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="info-block">
                            <div class="info-block-label">Booked On</div>
                            <div class="info-block-value">{{ date('M j, Y', strtotime($booking->created_at)) }}</div>
                        </div>
                    </td>
                </tr>
                <tr>
                    @if($booking->provider_source)
                        <td>
                            <div class="info-block">
                                <div class="info-block-label">Provider</div>
                                <div class="info-block-value">{{ ucfirst(str_replace('_', ' ', $booking->provider_source)) }}</div>
                            </div>
                        </td>
                    @endif
                    @if($booking->provider_booking_ref)
                        <td>
                            <div class="info-block">
                                <div class="info-block-label">Provider Reference</div>
                                <div class="info-block-value" style="font-family:monospace;">{{ $booking->provider_booking_ref }}</div>
                            </div>
                        </td>
                    @endif
                </tr>
            </table>
        </div>

        {{-- ═══ NOTES ═══ --}}
        @if($booking->notes || $booking->cancellation_reason)
            <div class="section">
                <div class="section-head">Additional Notes</div>
                @if($booking->notes)
                    <div class="info-block-full">
                        <div class="info-block-label">Notes</div>
                        <div class="info-block-value">{{ $booking->notes }}</div>
                    </div>
                @endif
                @if($booking->cancellation_reason)
                    <div class="info-block-full" style="background:#fef2f2; border-color:#fecaca;">
                        <div class="info-block-label" style="color:#dc2626;">Cancellation Reason</div>
                        <div class="info-block-value" style="color:#991b1b;">{{ $booking->cancellation_reason }}</div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- ═══ FOOTER ═══ --}}
    <div class="footer">
        <strong>Thank you for choosing Vrooem!</strong><br>
        For support, contact us at support@vrooem.com<br>
        <span style="margin-top:4px; display:inline-block;">This is an automatically generated document &middot; {{ date('F j, Y, g:i A') }}</span>
    </div>

</div>
</body>
</html>
