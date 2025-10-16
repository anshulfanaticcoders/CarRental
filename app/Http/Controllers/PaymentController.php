<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VendorProfile;
use App\Notifications\Booking\BookingCreatedAdminNotification;
use App\Notifications\Booking\BookingCreatedCompanyNotification;
use App\Notifications\Booking\BookingCreatedCustomerNotification;
use App\Notifications\Booking\BookingCreatedVendorNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Illuminate\Support\Str;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\BookingPayment;
use App\Models\BookingExtra;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Session as LaravelSession;
use App\Models\Message; // Added for sending messages
use App\Events\NewMessage; // Added for broadcasting new messages
use App\Models\Affiliate\AffiliateCommission;
use App\Models\Affiliate\AffiliateCustomerScan;
use App\Models\Affiliate\AffiliateQrCode;
use App\Services\Affiliate\AffiliateQrCodeService;

class PaymentController extends Controller
{
    public function charge(Request $request)
    {
        Log::info('PaymentController::charge - Starting payment processing');

        Stripe::setApiKey(config('stripe.secret'));

        // Configure Guzzle client with proxy if needed (for network issues)
        if (app()->environment('local') && config('services.proxy.http')) {
            Stripe::setApiBase('https://api.stripe.com');
            $httpClient = new \GuzzleHttp\Client([
                'timeout' => 30,
                'connect_timeout' => 10,
                'proxy' => config('services.proxy.http'),
                'verify' => false, // Only for development, remove in production
            ]);
            Stripe::setHttpClient($httpClient);
        }

        $bookingData = $request->input('bookingData');

        // Validate booking data
        if (!$bookingData || !isset($bookingData['customer'], $bookingData['vehicle_id'], $bookingData['amount_paid'])) {
            Log::error('PaymentController::charge - Invalid booking data', ['bookingData' => $bookingData]);
            return response()->json(['error' => 'Invalid booking data provided'], 400);
        }

        // Validate required customer fields
        $requiredCustomerFields = ['first_name', 'last_name', 'email', 'driver_age'];
        foreach ($requiredCustomerFields as $field) {
            if (empty($bookingData['customer'][$field])) {
                Log::error("PaymentController::charge - Missing required customer field: {$field}");
                return response()->json(['error' => "Missing required customer information: {$field}"], 400);
            }
        }

        // Validate amount
        if (!is_numeric($bookingData['amount_paid']) || $bookingData['amount_paid'] <= 0) {
            Log::error('PaymentController::charge - Invalid payment amount', ['amount_paid' => $bookingData['amount_paid']]);
            return response()->json(['error' => 'Invalid payment amount'], 400);
        }

        // Get currency from booking data, default to EUR
        $currency = strtolower($bookingData['currency'] ?? 'eur');

        try {
            // Store booking data temporarily in cache with 30 minute expiry
            $bookingToken = Str::random(32);
            cache()->put("booking_data_{$bookingToken}", $bookingData, now()->addMinutes(30));

            Log::info('PaymentController::charge - Booking data stored in cache', ['token' => $bookingToken]);

            // Create Stripe customer first
            $stripeCustomer = \Stripe\Customer::create([
                'email' => $bookingData['customer']['email'],
                'name' => $bookingData['customer']['first_name'] . ' ' . $bookingData['customer']['last_name'],
                'phone' => $bookingData['customer']['phone'] ?? null,
            ]);

            Log::info('PaymentController::charge - Stripe customer created', ['customer_id' => $stripeCustomer->id]);

            // Create Checkout Session (frontend expects this)
            Log::info('PaymentController::charge - Creating Checkout Session', [
                'amount' => $bookingData['amount_paid'],
                'currency' => $currency,
                'vehicle_id' => $bookingData['vehicle_id']
            ]);

            $session = Session::create([
                'customer' => $stripeCustomer->id,
                'payment_method_types' => ['card', 'bancontact', 'klarna'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => 'Car Rental Booking',
                            'description' => "Vehicle ID: {$bookingData['vehicle_id']} - {$bookingData['pickup_date']} to {$bookingData['return_date']}",
                        ],
                        'unit_amount' => (int) ($bookingData['amount_paid'] * 100), // Convert to cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => url(app()->getLocale() . '/booking-success/details?session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => url(app()->getLocale() . '/payment/cancel'),
                'metadata' => [
                    'booking_token' => $bookingToken,
                    'vehicle_id' => $bookingData['vehicle_id'],
                    'customer_email' => $bookingData['customer']['email'],
                    'user_id' => auth()->id() ?? null,
                ],
                'expires_at' => now()->addMinutes(30)->timestamp, // Session expires in 30 minutes
            ]);

            Log::info('PaymentController::charge - Checkout Session created successfully', [
                'session_id' => $session->id
            ]);

            // Clear session storage
            session()->forget(['pending_booking_id', 'driverInfo', 'rentalDates', 'selectionData']);
            LaravelSession::forget('can_access_booking_page');

            return response()->json([
                'sessionId' => $session->id, // Frontend expects 'sessionId'
            ]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('PaymentController::charge - Stripe API Error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'stripe_code' => $e->getStripeCode() ?? 'unknown'
            ]);
            return response()->json(['error' => 'Payment processing failed: ' . $e->getMessage()], 400);

        } catch (\Exception $e) {
            Log::error('PaymentController::charge - General Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    
    /**
     * Send booking notifications (separated for better error handling)
     */
    private function sendBookingNotifications(Booking $booking, Customer $customer)
    {
        try {
            $vehicle = Vehicle::find($booking->vehicle_id);
            if (!$vehicle) {
                Log::warning('PaymentController::sendBookingNotifications - Vehicle not found', ['vehicle_id' => $booking->vehicle_id]);
                return;
            }

            // Admin notification
            $adminEmail = env('VITE_ADMIN_EMAIL', 'default@admin.com');
            $admin = User::where('email', $adminEmail)->first();
            if ($admin) {
                $admin->notify(new BookingCreatedAdminNotification($booking, $customer, $vehicle));
            }

            // Vendor notification
            $vendor = User::find($vehicle->vendor_id);
            if ($vendor) {
                $vendor->notify(new BookingCreatedVendorNotification($booking, $customer, $vehicle, $vendor));
            }

            // Company notification
            $vendorProfile = VendorProfile::where('user_id', $vehicle->vendor_id)->first();
            if ($vendorProfile && $vendorProfile->company_email) {
                Notification::route('mail', $vendorProfile->company_email)
                    ->notify(new BookingCreatedCompanyNotification($booking, $customer, $vehicle, $vendorProfile));
            }

            // Customer notification
            $customer->notify(new BookingCreatedCustomerNotification($booking, $customer, $vehicle));

            // Send welcome message from vendor
            if ($vendor) {
                $message = Message::create([
                    'sender_id' => $vendor->id,
                    'receiver_id' => $customer->user_id,
                    'booking_id' => $booking->id,
                    'message' => 'Hello, Thank you for booking. Feel free to ask anything!',
                ]);

                $message->load(['sender', 'receiver']);
                broadcast(new NewMessage($message))->toOthers();
            }

            Log::info('PaymentController::sendBookingNotifications - All notifications sent', [
                'booking_id' => $booking->id
            ]);

        } catch (\Exception $e) {
            Log::error('PaymentController::sendBookingNotifications - Notification error', [
                'error' => $e->getMessage(),
                'booking_id' => $booking->id
            ]);
            // Don't throw here - notifications shouldn't fail the booking
        }
    }

    /**
     * Create affiliate commission record if booking came from affiliate QR code
     */
    private function createAffiliateCommission(Booking $booking, Customer $customer): void
    {
        try {
            // Get affiliate data from session
            $affiliateData = session('affiliate_data');

            if (!$affiliateData || !isset($affiliateData['customer_scan_id'])) {
                Log::info('PaymentController::createAffiliateCommission - No affiliate data found');
                return;
            }

            // Get the customer scan record
            $customerScan = AffiliateCustomerScan::find($affiliateData['customer_scan_id']);
            if (!$customerScan) {
                Log::warning('PaymentController::createAffiliateCommission - Customer scan not found', [
                    'customer_scan_id' => $affiliateData['customer_scan_id']
                ]);
                return;
            }

            // Get QR code and business details
            $qrCode = AffiliateQrCode::find($customerScan->qr_code_id);
            if (!$qrCode) {
                Log::warning('PaymentController::createAffiliateCommission - QR code not found', [
                    'qr_code_id' => $customerScan->qr_code_id
                ]);
                return;
            }

            $business = $qrCode->business;
            if (!$business) {
                Log::warning('PaymentController::createAffiliateCommission - Business not found', [
                    'business_id' => $qrCode->business_id
                ]);
                return;
            }

            // Get business model for commission calculation
            $businessModel = $business->getEffectiveBusinessModel();

            // Calculate commission
            $bookingAmount = $booking->total_amount;
            $discountAmount = $affiliateData['discount_amount'] ?? 0;
            $commissionableAmount = $bookingAmount;

            $commissionAmount = 0;
            $commissionRate = $businessModel['commission_rate'] ?? 0;
            $commissionType = $businessModel['commission_type'] ?? 'percentage';

            if ($commissionType === 'percentage') {
                $commissionAmount = ($commissionableAmount * $commissionRate) / 100;
            } else {
                $commissionAmount = min($commissionRate, $commissionableAmount);
            }

            // Update customer scan record with booking completion
            $customerScan->update([
                'booking_completed' => true,
                'booking_id' => $booking->id,
                'booking_type' => 'platform',
                'conversion_time_minutes' => $customerScan->created_at->diffInMinutes(now()),
                'discount_applied' => $discountAmount,
                'discount_percentage' => $affiliateData['discount_rate'] ?? 0,
            ]);

            // Update QR code conversion tracking
            $qrCode->update([
                'conversion_count' => $qrCode->conversion_count + 1,
                'total_revenue_generated' => $qrCode->total_revenue_generated + $bookingAmount,
                'last_scanned_at' => now(),
            ]);

            // Create commission record
            $commission = AffiliateCommission::create([
                'uuid' => Str::uuid(),
                'business_id' => $business->id,
                'location_id' => $qrCode->location_id,
                'booking_id' => $booking->id,
                'customer_id' => $customer->user_id,
                'qr_scan_id' => $customerScan->id,
                'booking_amount' => $bookingAmount,
                'commissionable_amount' => $commissionableAmount,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'discount_amount' => $discountAmount,
                'tax_amount' => 0, // Can be calculated based on business location
                'net_commission' => $commissionAmount, // Net commission after tax
                'booking_type' => 'platform',
                'commission_type' => $commissionType,
                'status' => 'pending', // Requires admin approval
                'scheduled_payout_date' => now()->addDays(30), // Default 30 days
                'audit_log' => [
                    [
                        'action' => 'created',
                        'data' => [
                            'booking_id' => $booking->id,
                            'customer_scan_id' => $customerScan->id,
                            'qr_code_id' => $qrCode->id,
                            'created_by' => 'system',
                            'timestamp' => now()->toISOString(),
                        ]
                    ]
                ],
                'compliance_checked' => false,
                'fraud_review_required' => false,
            ]);

            Log::info('PaymentController::createAffiliateCommission - Commission created successfully', [
                'commission_id' => $commission->id,
                'business_id' => $business->id,
                'booking_id' => $booking->id,
                'commission_amount' => $commissionAmount,
                'commission_rate' => $commissionRate,
                'commission_type' => $commissionType,
            ]);

        } catch (\Exception $e) {
            Log::error('PaymentController::createAffiliateCommission - Error creating commission', [
                'error' => $e->getMessage(),
                'booking_id' => $booking->id,
                'customer_id' => $customer->id,
                'trace' => $e->getTraceAsString(),
            ]);
            // Don't throw here - commission creation failure shouldn't break the booking
        }
    }


    public function success(Request $request)
{
    Log::info('PaymentController::success - Processing successful payment');

    try {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            Log::error('PaymentController::success - Missing session_id');
            return redirect()->route('payment.cancel')->with('error', 'Invalid payment session');
        }

        Stripe::setApiKey(config('stripe.secret'));

        // Retrieve the Checkout Session
        $session = Session::retrieve($sessionId);

        if ($session->payment_status !== 'paid') {
            Log::warning('PaymentController::success - Payment not completed', [
                'session_id' => $sessionId,
                'payment_status' => $session->payment_status
            ]);
            return redirect()->route('payment.cancel')->with('error', 'Payment not completed');
        }

        Log::info('PaymentController::success - Payment successful, creating booking', [
            'session_id' => $sessionId
        ]);

        // Get booking data from cache using token
        $bookingToken = $session->metadata->booking_token ?? null;

        if (!$bookingToken) {
            Log::error('PaymentController::success - No booking token found in session metadata');
            return redirect()->route('payment.cancel')->with('error', 'Booking session expired');
        }

        $bookingData = cache()->get("booking_data_{$bookingToken}");

        if (empty($bookingData)) {
            Log::error('PaymentController::success - No booking data found in cache', ['token' => $bookingToken]);
            return redirect()->route('payment.cancel')->with('error', 'Booking session expired or not found');
        }

        Log::info('PaymentController::success - Booking data retrieved from cache', ['token' => $bookingToken]);

        // Use database transaction to create all records atomically
        $booking = DB::transaction(function () use ($session, $bookingData) {
            try {
                // Find or create customer
                $customer = Customer::where('email', $bookingData['customer']['email'])->first();
                if (!$customer) {
                    $customer = Customer::create([
                        'user_id' => auth()->id(),
                        'first_name' => $bookingData['customer']['first_name'],
                        'last_name' => $bookingData['customer']['last_name'],
                        'email' => $bookingData['customer']['email'],
                        'phone' => $bookingData['customer']['phone'] ?? null,
                        'flight_number' => $bookingData['customer']['flight_number'] ?? null,
                        'driver_age' => $bookingData['customer']['driver_age'] ?? null,
                    ]);
                    Log::info('PaymentController::success - New customer created', ['customer_id' => $customer->id]);
                }

                // Create booking with 'pending' status - vendor needs to confirm
                $booking = Booking::create([
                    'booking_number' => uniqid('BOOK-'),
                    'booking_reference' => Str::random(32),
                    'customer_id' => $customer->id,
                    'vehicle_id' => $bookingData['vehicle_id'],
                    'pickup_date' => $bookingData['pickup_date'],
                    'return_date' => $bookingData['return_date'],
                    'pickup_time' => $bookingData['pickup_time'],
                    'return_time' => $bookingData['return_time'],
                    'pickup_location' => $bookingData['pickup_location'],
                    'return_location' => $bookingData['return_location'],
                    'total_days' => $bookingData['total_days'],
                    'base_price' => $bookingData['base_price'],
                    'preferred_day' => $bookingData['preferred_day'] ?? null,
                    'extra_charges' => $bookingData['extra_charges'] ?? 0,
                    'tax_amount' => $bookingData['tax_amount'] ?? 0,
                    'discount_amount' => $bookingData['discount_amount'] ?? 0,
                    'total_amount' => $bookingData['total_amount'],
                    'pending_amount' => $bookingData['pending_amount'],
                    'amount_paid' => $bookingData['amount_paid'],
                    'booking_currency' => strtoupper($bookingData['currency'] ?? 'USD'),
                    'payment_status' => 'completed', // Payment completed
                    'booking_status' => 'pending', // Vendor needs to confirm
                    'plan' => $bookingData['plan'] ?? null,
                    'plan_price' => $bookingData['plan_price'] ?? null,
                    'notes' => $bookingData['notes'] ?? null,
                    'cancellation_reason' => null,
                ]);

                Log::info('PaymentController::success - Booking created', ['booking_id' => $booking->id]);

                // Save extras if any
                if (!empty($bookingData['extras'])) {
                    $extrasData = [];
                    foreach ($bookingData['extras'] as $extra) {
                        if ($extra['quantity'] > 0) {
                            $extrasData[] = [
                                'booking_id' => $booking->id,
                                'extra_type' => $extra['extra_type'],
                                'extra_name' => $extra['extra_name'],
                                'quantity' => $extra['quantity'],
                                'price' => $extra['price'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                    if (!empty($extrasData)) {
                        BookingExtra::insert($extrasData);
                        Log::info('PaymentController::success - Booking extras created', ['count' => count($extrasData)]);
                    }
                }

                // Create payment record
                $payment = BookingPayment::create([
                    'booking_id' => $booking->id,
                    'payment_method' => 'stripe',
                    'transaction_id' => $session->payment_intent ?? $session->id,
                    'amount' => $bookingData['amount_paid'],
                    'payment_status' => 'succeeded',
                ]);

                Log::info('PaymentController::success - Payment record created', ['payment_id' => $payment->id]);

                // Create affiliate commission if applicable
                $this->createAffiliateCommission($booking, $customer);

                // Send notifications (non-blocking)
                $this->sendBookingNotifications($booking, $customer);

                // Clean up cache
                if (isset($session->metadata->booking_token)) {
                    cache()->forget("booking_data_{$session->metadata->booking_token}");
                    Log::info('PaymentController::success - Cache cleaned up', ['token' => $session->metadata->booking_token]);
                }

                return $booking;

            } catch (\Exception $e) {
                Log::error('PaymentController::success - Error during transaction', [
                    'error' => $e->getMessage(),
                    'session_id' => $session->id
                ]);
                throw $e; // Re-throw to trigger transaction rollback
            }
        });

        // Load relationships for the view
        $booking->load(['customer', 'vehicle', 'payments', 'vehicle.vendor']);
        $vendorProfile = VendorProfile::where('user_id', $booking->vehicle->vendor_id)->first();

        // Clear session storage
        session()->forget(['pending_booking_id', 'driverInfo', 'rentalDates', 'selectionData', 'affiliate_data']);
        LaravelSession::forget('can_access_booking_page');

        Log::info('PaymentController::success - Showing success page', [
            'booking_id' => $booking->id,
            'session_id' => $sessionId
        ]);

        return Inertia::render('Booking/Success', [
            'booking' => $booking,
            'vehicle' => $booking->vehicle,
            'customer' => $booking->customer,
            'payment' => $booking->payments->first(), // Get the first payment record
            'vendorProfile' => $vendorProfile,
            'plan' => $booking->plan ? ['plan_type' => $booking->plan, 'price' => $booking->plan_price] : null,
            'session_id' => $sessionId,
            'payment_status' => $session->payment_status,
        ]);

    } catch (\Exception $e) {
        Log::error('PaymentController::success - Error', [
            'error' => $e->getMessage(),
            'session_id' => $request->query('session_id'),
            'trace' => $e->getTraceAsString()
        ]);
        return redirect()->route('payment.cancel')->with('error', 'Error processing successful payment: ' . $e->getMessage());
    }
}

public function cancel(Request $request)
{
    $bookingId = $request->route('booking_id'); // Get booking_id from route if it exists
    $error = $request->session()->get('error'); // Get error message if any

    // If we have a booking_id, try to find the booking
    if ($bookingId) {
        $booking = Booking::find($bookingId);

        if ($booking) {
            // Optionally, you can update the booking status to 'cancelled' if you want
            // $booking->update(['booking_status' => 'cancelled']);
        }
    }

    return Inertia::render('Booking/Cancel', [
        'bookingId' => $bookingId,
        'error' => $error,
    ]);
}

public function retryPayment(Request $request)
{
    Log::info('PaymentController::retryPayment - Starting payment retry');

    $bookingId = $request->input('booking_id');

    if (!$bookingId) {
        Log::error('PaymentController::retryPayment - Missing booking_id');
        return response()->json(['error' => 'Booking ID is required'], 400);
    }

    try {
        Stripe::setApiKey(config('stripe.secret'));

        $booking = Booking::findOrFail($bookingId);

        if ($booking->payment_status === 'completed') {
            Log::warning('PaymentController::retryPayment - Payment already completed', ['booking_id' => $bookingId]);
            return response()->json(['error' => 'Payment already completed'], 400);
        }

        // Recreate booking data for retry
        $bookingData = [
            'customer' => [
                'first_name' => $booking->customer->first_name,
                'last_name' => $booking->customer->last_name,
                'email' => $booking->customer->email,
                'phone' => $booking->customer->phone,
                'driver_age' => $booking->customer->driver_age,
            ],
            'vehicle_id' => $booking->vehicle_id,
            'pickup_date' => $booking->pickup_date,
            'return_date' => $booking->return_date,
            'pickup_time' => $booking->pickup_time,
            'return_time' => $booking->return_time,
            'pickup_location' => $booking->pickup_location,
            'return_location' => $booking->return_location,
            'total_days' => $booking->total_days,
            'base_price' => $booking->base_price,
            'preferred_day' => $booking->preferred_day,
            'extra_charges' => $booking->extra_charges,
            'tax_amount' => $booking->tax_amount,
            'discount_amount' => $booking->discount_amount,
            'total_amount' => $booking->total_amount,
            'pending_amount' => $booking->pending_amount,
            'amount_paid' => $booking->amount_paid,
            'plan' => $booking->plan,
            'plan_price' => $booking->plan_price,
            'notes' => $booking->notes,
        ];

        // Get extras if any
        $extras = BookingExtra::where('booking_id', $booking->id)->get();
        if ($extras->isNotEmpty()) {
            $bookingData['extras'] = $extras->map(function ($extra) {
                return [
                    'extra_type' => $extra->extra_type,
                    'extra_name' => $extra->extra_name,
                    'quantity' => $extra->quantity,
                    'price' => $extra->price,
                ];
            })->toArray();
        }

        // Store retry booking data in cache
        $bookingToken = Str::random(32);
        cache()->put("booking_data_{$bookingToken}", $bookingData, now()->addMinutes(30));

        // Create new Checkout Session
        $session = Session::create([
            'customer' => $booking->customer->email, // Use email as customer identifier
            'payment_method_types' => ['card', 'bancontact', 'klarna'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Car Rental Booking Retry',
                        'description' => "Retry for Booking ID: {$booking->id}",
                    ],
                    'unit_amount' => (int) ($booking->amount_paid * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => url(app()->getLocale() . '/booking-success/details?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => url(app()->getLocale() . '/payment/cancel'),
            'metadata' => [
                'booking_token' => $bookingToken,
                'vehicle_id' => $booking->vehicle_id,
                'customer_email' => $booking->customer->email,
                'user_id' => auth()->id() ?? null,
                'retry_booking_id' => $booking->id, // Mark this as a retry
            ],
            'expires_at' => now()->addMinutes(30)->timestamp,
        ]);

        Log::info('PaymentController::retryPayment - New Checkout Session created', [
            'original_booking_id' => $booking->id,
            'new_session_id' => $session->id
        ]);

        return response()->json([
            'sessionId' => $session->id, // Frontend expects 'sessionId'
        ]);

    } catch (\Exception $e) {
        Log::error('PaymentController::retryPayment - Error', [
            'error' => $e->getMessage(),
            'booking_id' => $bookingId
        ]);
        return response()->json(['error' => 'Failed to retry payment: ' . $e->getMessage()], 500);
    }
}
    
}
