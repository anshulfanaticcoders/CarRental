<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Receipt - {{ $booking->booking_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
        }
        .header {
            background: linear-gradient(135deg, #153B4F 0%, #245f7d 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d1e7dd; color: #0f5132; }
        .status-completed { background: #cff4fc; color: #055160; }
        .status-cancelled { background: #f8d7da; color: #842029; }

        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #153B4F;
            border-bottom: 2px solid #153B4F;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .info-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        .info-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .vehicle-card {
            display: flex;
            gap: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .vehicle-image {
            width: 150px;
            height: 100px;
            object-fit: cover;
            border-radius: 6px;
            background: #e9ecef;
        }
        .vehicle-info h3 {
            font-size: 18px;
            color: #153B4F;
            margin-bottom: 8px;
        }
        .vehicle-specs {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: #666;
        }

        .payment-summary {
            background: #f8f9fa;
            color: #333;
            padding: 20px;
            border-radius: 8px;
        }
        .payment-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        .payment-row:last-child {
            border-bottom: none;
        }
        .payment-row.total {
            font-size: 18px;
            font-weight: bold;
            padding-top: 12px;
            margin-top: 5px;
            border-top: 2px solid rgba(255,255,255,0.3);
        }

        .footer {
            background: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #dee2e6;
        }
        @media print {
            body { background: white; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Booking Confirmation</h1>
            <p>Reference: {{ $booking->booking_number }}</p>
            <p>Date: {{ date('F j, Y', strtotime($booking->created_at)) }}</p>
        </div>

        <div class="content">
            <!-- Status -->
            <div class="section">
                <span class="status-badge status-{{ $booking->booking_status }}">
                    {{ ucfirst($booking->booking_status) }}
                </span>
            </div>

            <!-- Vehicle Information -->
            <div class="section">
                <h2 class="section-title">Vehicle Information</h2>
                <div class="vehicle-card">
                    @if(isset($vehicle->images[0]['image_url']))
                        <img src="{{ $vehicle->images[0]['image_url'] }}" alt="Vehicle" class="vehicle-image">
                    @else
                        <div class="vehicle-image" style="display:flex;align-items:center;justify-content:center;background:#e9ecef;color:#999;">No Image</div>
                    @endif
                    <div class="vehicle-info">
                        <h3>{{ $vehicle->brand ?? '' }} {{ $vehicle->model ?? $vehicle->vehicle_name ?? '' }}</h3>
                        @if(isset($vehicle->category->name))
                            <p style="color:#666;margin-bottom:8px;">{{ $vehicle->category->name }}</p>
                        @endif
                        <div class="vehicle-specs">
                            @if($vehicle->transmission)
                                <span>🚗 {{ ucfirst($vehicle->transmission) }}</span>
                            @endif
                            @if($vehicle->fuel)
                                <span>⛽ {{ ucfirst($vehicle->fuel) }}</span>
                            @endif
                            @if($vehicle->seating_capacity)
                                <span>👥 {{ $vehicle->seating_capacity }} Seats</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trip Details -->
            <div class="section">
                <h2 class="section-title">Trip Details</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Pickup Location</div>
                        <div class="info-value">{{ $booking->pickup_location }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Drop-off Location</div>
                        <div class="info-value">{{ $booking->return_location }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Pickup Date & Time</div>
                        <div class="info-value">{{ date('M j, Y', strtotime($booking->pickup_date)) }} at {{ date('g:i A', strtotime($booking->pickup_time)) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Drop-off Date & Time</div>
                        <div class="info-value">{{ date('M j, Y', strtotime($booking->return_date)) }} at {{ date('g:i A', strtotime($booking->return_time)) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Rental Duration</div>
                        <div class="info-value">{{ $booking->total_days }} {{ $booking->total_days == 1 ? 'Day' : 'Days' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Booking Status</div>
                        <div class="info-value" style="text-transform: capitalize;">{{ $booking->booking_status }}</div>
                    </div>
                </div>
            </div>

            <!-- Payment Summary -->
            <div class="section">
                <h2 class="section-title">Payment Summary</h2>
                <div class="payment-summary">
                    <div class="payment-row">
                        <span>Daily Rate</span>
                        <span>{{ $booking->booking_currency ?? 'USD' }} {{ number_format($booking->total_amount / max($booking->total_days, 1), 2) }}</span>
                    </div>
                    <div class="payment-row">
                        <span>Duration</span>
                        <span>{{ $booking->total_days }} {{ $booking->total_days == 1 ? 'day' : 'days' }}</span>
                    </div>
                    @if($booking->extras && $booking->extras->count() > 0)
                        @foreach($booking->extras as $extra)
                            <div class="payment-row">
                                <span>{{ $extra->name }}</span>
                                <span>{{ $booking->booking_currency ?? 'USD' }} {{ number_format($extra->price, 2) }}</span>
                            </div>
                        @endforeach
                    @endif
                    @if(isset($booking->plan) && $booking->plan)
                        <div class="payment-row">
                            <span>Plan ({{ $booking->plan }})</span>
                            <span>{{ $booking->plan_price ? number_format($booking->plan_price, 2) : 'Included' }}</span>
                        </div>
                    @endif
                    <div class="payment-row total">
                        <span>Total Amount</span>
                        <span>{{ $booking->booking_currency ?? 'USD' }} {{ number_format($booking->total_amount ?? 0, 2) }}</span>
                    </div>
                    @if($payment && $payment->amount)
                        <div class="payment-row">
                            <span>Amount Paid</span>
                            <span>{{ $payment->currency ?? 'USD' }} {{ number_format($payment->amount, 2) }}</span>
                        </div>
                    @endif
                    @if($payment && $payment->status)
                        <div style="text-align:center;margin-top:10px;font-size:12px;opacity:0.9;">
                            Payment Status: {{ ucfirst($payment->status) }}
                        </div>
                    @endif
                    @if($payment && $payment->payment_method)
                        <div style="text-align:center;font-size:12px;opacity:0.9;">
                            Payment Method: {{ ucfirst($payment->payment_method) }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Customer Information -->
            <div class="section">
                <h2 class="section-title">Customer Information</h2>
                <div class="info-grid">
                    @if($booking->customer)
                        <div class="info-item">
                            <div class="info-label">Customer Name</div>
                            <div class="info-value">{{ $booking->customer->first_name }} {{ $booking->customer->last_name }}</div>
                        </div>
                        @if($booking->customer->email)
                            <div class="info-item">
                                <div class="info-label">Email</div>
                                <div class="info-value">{{ $booking->customer->email }}</div>
                            </div>
                        @endif
                        @if($booking->customer->phone)
                            <div class="info-item">
                                <div class="info-label">Phone</div>
                                <div class="info-value">{{ $booking->customer->phone }}</div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            @if($vendorProfile && $vendorProfile->user)
                <!-- Vendor Information -->
                <div class="section">
                    <h2 class="section-title">Vendor Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Vendor Name</div>
                            <div class="info-value">{{ $vendorProfile->user->first_name }} {{ $vendorProfile->user->last_name }}</div>
                        </div>
                        @if($vendorProfile->user->email)
                            <div class="info-item">
                                <div class="info-label">Email</div>
                                <div class="info-value">{{ $vendorProfile->user->email }}</div>
                            </div>
                        @endif
                        @if($vendorProfile->user->phone)
                            <div class="info-item">
                                <div class="info-label">Phone</div>
                                <div class="info-value">{{ $vendorProfile->user->phone }}</div>
                            </div>
                        @endif
                        @if($vendorProfile->address_line1 || $vendorProfile->city)
                            <div class="info-item" style="grid-column: 1 / -1;">
                                <div class="info-label">Address</div>
                                <div class="info-value">
                                    {{ $vendorProfile->address_line1 }}
                                    {{ $vendorProfile->city ? ', ' . $vendorProfile->city : '' }}
                                    {{ $vendorProfile->state ? ', ' . $vendorProfile->state : '' }}
                                    {{ $vendorProfile->country ? ', ' . $vendorProfile->country : '' }}
                                    {{ $vendorProfile->postal_code ? ' ' . $vendorProfile->postal_code : '' }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Thank you for your booking!</strong></p>
            <p style="margin-top:5px;">For any questions or changes, please contact our support team.</p>
            <p style="margin-top:10px;font-size:11px;">This is an automatically generated receipt. Generated on {{ date('F j, Y, g:i A') }}</p>
        </div>
    </div>
</body>
</html>
