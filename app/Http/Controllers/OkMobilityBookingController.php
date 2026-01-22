<?php

namespace App\Http\Controllers;

use App\Models\OkMobilityBooking;
use App\Services\OkMobilityService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\User;
use App\Notifications\Booking\OkMobilityBookingCreatedAdminNotification;
use App\Notifications\Booking\OkMobilityBookingCreatedCustomerNotification;
use App\Jobs\SendOkMobilityBookingNotificationJob;
use App\Models\Affiliate\AffiliateCommission;
use App\Models\Affiliate\AffiliateCustomerScan;
use App\Models\Affiliate\AffiliateQrCode;
use App\Services\Affiliate\AffiliateQrCodeService;
use App\Services\CurrencyConversionService;
use Illuminate\Support\Facades\Session as LaravelSession;
use Illuminate\Support\Str;

class OkMobilityBookingController extends Controller
{
    public function __construct(private OkMobilityService $okMobilityService)
    {
    }

    public function processOkMobilityBookingPayment(Request $request)
    {
        Stripe::setApiKey(config('stripe.secret'));

        // Retrieve Payment Percentage
        $payableSetting = \App\Models\PayableSetting::first();
        $paymentPercentage = $payableSetting ? $payableSetting->payment_percentage : 0;

        $validatedData = $request->validate([
            'pickup_station_id' => 'required|string',
            'dropoff_station_id' => 'nullable|string',
            'start_date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_date' => 'required|date_format:Y-m-d',
            'end_time' => 'required|date_format:H:i',
            'customer.title' => 'nullable|string',
            'customer.firstname' => 'required|string|max:255',
            'customer.surname' => 'required|string|max:255',
            'customer.email' => 'required|email|max:255',
            'customer.phone' => 'required|string|max:20',
            'customer.address1' => 'required|string|max:255',
            'customer.address2' => 'nullable|string|max:255',
            'customer.address3' => 'nullable|string|max:255',
            'customer.town' => 'required|string|max:255',
            'customer.postcode' => 'required|string|max:20',
            'customer.country' => 'required|string|max:100',
            'customer.driver_licence_number' => 'required|string|max:50',
            'customer.flight_number' => 'nullable|string|max:50',
            'customer.comments' => 'nullable|string|max:1000',
            'extras' => 'array',
            'extras.*.id' => 'required|string',
            'extras.*.quantity' => 'required|integer|min:0',
            'extras.*.option_total' => 'required|numeric|min:0',
            'vehicle_id' => 'required|string',
            'vehicle_total' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'grand_total' => 'required|numeric|min:0',
            'paymentHandlerRef' => 'nullable|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'vehicle_location' => 'nullable|string|max:255',
            'ok_mobility_group_id' => 'required|string',
            'ok_mobility_token' => 'required|string',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $customerDetails = $validatedData['customer'];
        $customerDetails['comments'] = 'OK Mobility Booking - ' . ($customerDetails['comments'] ?? '');

        try {
            $reservationData = [
                'reference' => 'OM-' . $validatedData['vehicle_id'] . '-' . now()->format('YmdHis'),
                'group_code' => $validatedData['ok_mobility_group_id'],
                'token' => $validatedData['ok_mobility_token'],
                'pickup_date' => $validatedData['start_date'],
                'pickup_time' => $validatedData['start_time'],
                'pickup_station_id' => $validatedData['pickup_station_id'],
                'dropoff_date' => $validatedData['end_date'],
                'dropoff_time' => $validatedData['end_time'],
                'dropoff_station_id' => $validatedData['dropoff_station_id'] ?? $validatedData['pickup_station_id'],
                'driver_name' => $customerDetails['firstname'] . ' ' . $customerDetails['surname'],
                'driver_surname' => $customerDetails['surname'],
                'driver_email' => $customerDetails['email'],
                'driver_phone' => $customerDetails['phone'],
                'driver_address' => $customerDetails['address1'],
                'driver_city' => $customerDetails['town'],
                'driver_postal_code' => $customerDetails['postcode'],
                'driver_country' => $customerDetails['country'],
                'driver_license_number' => $customerDetails['driver_licence_number'],
                'driver_age' => 25, // Default age, can be added to form if needed
                'extras' => $validatedData['extras'] ?? [],
                'remarks' => $validatedData['remarks'] ?? null,
            ];

            // Save booking to database before payment
            $okMobilityBooking = OkMobilityBooking::create([
                'user_id' => $validatedData['user_id'] ?? auth()->id(),
                'ok_mobility_booking_ref' => null,
                'vehicle_id' => $validatedData['vehicle_id'],
                'vehicle_location' => $validatedData['vehicle_location'] ?? null,
                'start_date' => $validatedData['start_date'],
                'start_time' => $validatedData['start_time'],
                'end_date' => $validatedData['end_date'],
                'end_time' => $validatedData['end_time'],
                'pickup_station_id' => $validatedData['pickup_station_id'],
                'dropoff_station_id' => $validatedData['dropoff_station_id'] ?? null,
                'age' => $reservationData['driver_age'] ?? 25,
                'customer_details' => $customerDetails,
                'selected_extras' => $validatedData['extras'] ?? [],
                'vehicle_total' => $validatedData['vehicle_total'],
                'currency' => $validatedData['currency'],
                'grand_total' => $validatedData['grand_total'],
                'payment_handler_ref' => null,
                'booking_status' => 'payment_pending',
                'api_response' => null,
                'remarks' => $validatedData['remarks'] ?? null,
            ]);

            $okMobilityBooking->update([
                'api_response' => [
                    'reservation_payload' => $reservationData,
                ],
            ]);

            // Calculate Amount to Charge based on Payment Percentage
            $amountToCharge = $validatedData['grand_total'];
            if ($paymentPercentage > 0) {
                $amountToCharge = ($validatedData['grand_total'] * $paymentPercentage) / 100;
            }

            // Create Stripe Checkout Session
            $session = Session::create([
                'payment_method_types' => ['card', 'bancontact', 'klarna'], // Added more payment methods
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => strtolower($validatedData['currency']),
                            'product_data' => [
                                'name' => 'OK Mobility Car Rental Booking',
                                'description' => "Booking for vehicle ID {$validatedData['vehicle_id']} - Pay Now (" . ($paymentPercentage > 0 ? $paymentPercentage . '%' : 'Full Amount') . ")",
                            ],
                            'unit_amount' => round($amountToCharge * 100), // Amount in cents, rounded
                        ],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',
                'success_url' => url(app()->getLocale() . '/ok-mobility-booking-success?session_id={CHECKOUT_SESSION_ID}&booking_id=' . $okMobilityBooking->id),
                'cancel_url' => route('okmobility.booking.cancel', ['locale' => app()->getLocale(), 'booking_id' => $okMobilityBooking->id]),
                'metadata' => [
                    'okmobility_booking_id' => $okMobilityBooking->id,
                ],
            ]);

            // Save the session ID to the booking record
            $okMobilityBooking->update(['payment_handler_ref' => $session->id]);

            return response()->json([
                'sessionId' => $session->id,
            ]);

        } catch (\Stripe\Exception\ApiConnectionException $e) {
            Log::error('Stripe API Connection Error: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
            ]);
            return response()->json(['error' => 'Could not connect to the payment gateway. Please check your server\'s network connection and firewall settings. It seems there is an issue connecting to Stripe.'], 500);
        } catch (\Exception $e) {
            Log::error('Error in OkMobilityBookingController::processOkMobilityBookingPayment: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
            ]);
            return response()->json(['error' => 'An error occurred during booking or payment initiation: ' . $e->getMessage()], 500);
        }
    }

    public function okMobilityBookingSuccess(Request $request)
    {
        Stripe::setApiKey(config('stripe.secret'));

        $sessionId = $request->query('session_id');
        $okMobilityBookingId = $request->query('booking_id');

        if (!$sessionId || !$okMobilityBookingId) {
            Log::error('Missing session_id or booking_id in okMobilityBookingSuccess');
            return redirect()->route('okmobility.booking.cancel', ['locale' => app()->getLocale(), 'booking_id' => $okMobilityBookingId])->with('error', 'Invalid payment session');
        }

        try {
            $session = Session::retrieve($sessionId);
            $okMobilityBooking = OkMobilityBooking::where('payment_handler_ref', $sessionId)->firstOrFail();

            if ($session->payment_status === 'paid') {
                $reservationPayload = $okMobilityBooking->api_response['reservation_payload'] ?? null;

                if (!$reservationPayload) {
                    $okMobilityBooking->update([
                        'booking_status' => 'api_failed',
                        'payment_handler_ref' => $session->payment_intent,
                    ]);
                    return redirect()->route('okmobility.booking.cancel', ['locale' => app()->getLocale(), 'booking_id' => $okMobilityBooking->id])
                        ->with('error', 'Payment completed, but reservation data was missing. Please contact support.');
                }

                $xmlResponse = $this->okMobilityService->makeReservation($reservationPayload);

                if (is_null($xmlResponse) || empty($xmlResponse)) {
                    $okMobilityBooking->update([
                        'booking_status' => 'api_failed',
                        'payment_handler_ref' => $session->payment_intent,
                    ]);
                    return redirect()->route('okmobility.booking.cancel', ['locale' => app()->getLocale(), 'booking_id' => $okMobilityBooking->id])
                        ->with('error', 'Payment completed, but OK Mobility booking failed. Please contact support.');
                }

                libxml_use_internal_errors(true);
                $xmlObject = simplexml_load_string($xmlResponse);

                if ($xmlObject === false) {
                    $okMobilityBooking->update([
                        'booking_status' => 'api_failed',
                        'payment_handler_ref' => $session->payment_intent,
                    ]);
                    return redirect()->route('okmobility.booking.cancel', ['locale' => app()->getLocale(), 'booking_id' => $okMobilityBooking->id])
                        ->with('error', 'Payment completed, but OK Mobility booking response could not be parsed.');
                }

                $bookingReference = (string) ($xmlObject->Body->createReservationResponse->createReservationResult->Reservation_Nr ?? null);
                $status = (string) ($xmlObject->Body->createReservationResponse->createReservationResult->Status ?? 'pending');

                if (!$bookingReference) {
                    $okMobilityBooking->update([
                        'booking_status' => 'api_failed',
                        'payment_handler_ref' => $session->payment_intent,
                        'api_response' => json_decode(json_encode($xmlObject), true),
                    ]);
                    return redirect()->route('okmobility.booking.cancel', ['locale' => app()->getLocale(), 'booking_id' => $okMobilityBooking->id])
                        ->with('error', 'Payment completed, but OK Mobility booking reference was missing.');
                }

                $okMobilityBooking->update([
                    'booking_status' => $status,
                    'payment_handler_ref' => $session->payment_intent,
                    'ok_mobility_booking_ref' => $bookingReference,
                    'api_response' => json_decode(json_encode($xmlObject), true),
                ]);

                // Create affiliate commission if applicable (same as other payment controllers)
                $this->createAffiliateCommission($okMobilityBooking);

                // Send notifications
                try {
                    dispatch(new SendOkMobilityBookingNotificationJob($okMobilityBooking));
                } catch (\Exception $e) {
                    Log::error('Failed to dispatch notification job for OK Mobility booking: ' . $e->getMessage(), [
                        'booking_id' => $okMobilityBooking->id
                    ]);
                }

                // Clear session storage (same as other payment controllers)
                session()->forget(['pending_booking_id', 'driverInfo', 'rentalDates', 'selectionData', 'affiliate_data']);
                LaravelSession::forget('can_access_booking_page');

                Log::info('OK Mobility booking confirmed via Stripe success callback. Booking ID: ' . $okMobilityBooking->id);
                Log::info('Session storage cleared for OK Mobility booking success');

                // Redirect to the profile bookings page
                return redirect()->route('profile.bookings.ok-mobility', ['locale' => app()->getLocale()]);

            } else {

                Log::warning('Stripe session not paid for OK Mobility booking. Session ID: ' . $sessionId);
                $okMobilityBooking->update(['booking_status' => 'payment_failed']);
                return redirect()->route('okmobility.booking.cancel', ['locale' => app()->getLocale(), 'booking_id' => $okMobilityBooking->id])->with('error', 'Payment not completed.');
            }

        } catch (\Exception $e) {
            Log::error('Error in OkMobilityBookingController::okMobilityBookingSuccess: ' . $e->getMessage(), [
                'exception' => $e,
                'session_id' => $sessionId,
                'booking_id' => $okMobilityBookingId
            ]);
            return redirect()->route('okmobility.booking.cancel', ['locale' => app()->getLocale(), 'booking_id' => $okMobilityBookingId])->with('error', 'Error processing success callback: ' . $e->getMessage());
        }
    }

    public function okMobilityBookingCancel(Request $request)
    {
        $okMobilityBookingId = $request->query('booking_id');
        $okMobilityBooking = null;

        if ($okMobilityBookingId) {
            $okMobilityBooking = OkMobilityBooking::find($okMobilityBookingId);
            if ($okMobilityBooking) {
                $okMobilityBooking->update(['booking_status' => 'cancelled_by_user']);
                Log::info('OK Mobility booking cancelled by user via Stripe cancel callback. Booking ID: ' . $okMobilityBookingId);
            }
        }

        return Inertia::render('OkMobilityBookingCancel', [
            'bookingId' => $okMobilityBookingId,
            'booking' => $okMobilityBooking,
        ]);
    }

    public function getCustomerOkMobilityBookings()
    {
        $bookings = \App\Models\OkMobilityBooking::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return Inertia::render('Profile/Bookings/OkMobilityBookings', [
            'bookings' => $bookings,
        ]);
    }

    /**
     * Create affiliate commission record if OK Mobility booking came from affiliate QR code
     */
    private function createAffiliateCommission(OkMobilityBooking $booking): void
    {
        try {
            // Get affiliate data from session
            $affiliateData = session('affiliate_data');

            if (!$affiliateData || !isset($affiliateData['customer_scan_id'])) {
                Log::info('OkMobilityBookingController::createAffiliateCommission - No affiliate data found');
                return;
            }

            // Get the customer scan record
            $customerScan = AffiliateCustomerScan::find($affiliateData['customer_scan_id']);
            if (!$customerScan) {
                Log::warning('OkMobilityBookingController::createAffiliateCommission - Customer scan not found', [
                    'customer_scan_id' => $affiliateData['customer_scan_id']
                ]);
                return;
            }

            // Get QR code and business details
            $qrCode = AffiliateQrCode::find($customerScan->qr_code_id);
            if (!$qrCode) {
                Log::warning('OkMobilityBookingController::createAffiliateCommission - QR code not found', [
                    'qr_code_id' => $customerScan->qr_code_id
                ]);
                return;
            }

            $business = $qrCode->business;
            if (!$business) {
                Log::warning('OkMobilityBookingController::createAffiliateCommission - Business not found', [
                    'business_id' => $qrCode->business_id
                ]);
                return;
            }

            // Get business model for commission calculation
            $businessModel = $business->getEffectiveBusinessModel();

            // Calculate commission (using OK Mobility booking data)
            $bookingAmount = $booking->grand_total;
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

            // Convert commission amounts to business currency
            $businessCurrency = $business->currency ?? 'EUR';
            $bookingCurrency = $booking->currency ?? 'EUR';

            // Perform currency conversion if needed
            if ($bookingCurrency !== $businessCurrency) {
                try {
                    $currencyService = new CurrencyConversionService();

                    // Convert booking amount
                    $bookingConversion = $currencyService->convert((float) $bookingAmount, $bookingCurrency, $businessCurrency);
                    $convertedBookingAmount = $bookingConversion['success'] ? $bookingConversion['converted_amount'] : $bookingAmount;

                    // Convert commission amount
                    $commissionConversion = $currencyService->convert((float) $commissionAmount, $bookingCurrency, $businessCurrency);
                    $convertedCommissionAmount = $commissionConversion['success'] ? $commissionConversion['converted_amount'] : $commissionAmount;

                    // Convert discount amount
                    $discountConversion = $currencyService->convert((float) $discountAmount, $bookingCurrency, $businessCurrency);
                    $convertedDiscountAmount = $discountConversion['success'] ? $discountConversion['converted_amount'] : $discountAmount;

                    // Update amounts for commission record
                    $bookingAmount = $convertedBookingAmount;
                    $commissionAmount = $convertedCommissionAmount;
                    $discountAmount = $convertedDiscountAmount;
                    $commissionableAmount = $convertedBookingAmount;

                    Log::info('OkMobilityBookingController::createAffiliateCommission - Currency conversion performed', [
                        'from_currency' => $bookingCurrency,
                        'to_currency' => $businessCurrency,
                        'original_booking_amount' => $booking->grand_total,
                        'converted_booking_amount' => $convertedBookingAmount,
                        'original_commission_amount' => ($booking->grand_total * $commissionRate) / 100,
                        'converted_commission_amount' => $convertedCommissionAmount,
                        'conversion_rate_used' => $bookingConversion['rate'] ?? 1.0,
                    ]);

                } catch (\Exception $e) {
                    Log::error('OkMobilityBookingController::createAffiliateCommission - Currency conversion failed', [
                        'error' => $e->getMessage(),
                        'from_currency' => $bookingCurrency,
                        'to_currency' => $businessCurrency,
                        'original_amount' => $commissionAmount,
                    ]);
                    // Keep original amounts if conversion fails
                }
            }

            // Update customer scan record with booking completion
            $customerScan->update([
                'booking_completed' => true,
                'booking_id' => $booking->id,
                'booking_type' => 'okmobility',
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
                'customer_id' => $booking->user_id,
                'qr_scan_id' => $customerScan->id,
                'booking_amount' => $bookingAmount,
                'commissionable_amount' => $commissionableAmount,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'discount_amount' => $discountAmount,
                'tax_amount' => 0,
                'net_commission' => $commissionAmount,
                'booking_type' => 'okmobility',
                'commission_type' => $commissionType,
                'status' => 'pending',
                'scheduled_payout_date' => now()->addDays(30),
                'audit_log' => [
                    [
                        'action' => 'created',
                        'data' => [
                            'booking_id' => $booking->id,
                            'customer_scan_id' => $customerScan->id,
                            'qr_code_id' => $qrCode->id,
                            'created_by' => 'system',
                            'timestamp' => now()->toISOString(),
                            'currency_conversion' => [
                                'from_currency' => $bookingCurrency,
                                'to_currency' => $businessCurrency,
                                'conversion_performed' => $bookingCurrency !== $businessCurrency,
                                'original_booking_amount' => $booking->grand_total,
                                'converted_booking_amount' => $bookingAmount,
                                'original_commission_amount' => ($booking->grand_total * $commissionRate) / 100,
                                'converted_commission_amount' => $commissionAmount,
                            ]
                        ]
                    ]
                ],
                'compliance_checked' => false,
                'fraud_review_required' => false,
            ]);

            Log::info('OkMobilityBookingController::createAffiliateCommission - Commission created successfully', [
                'commission_id' => $commission->id,
                'business_id' => $business->id,
                'booking_id' => $booking->id,
                'commission_amount' => $commissionAmount,
                'commission_rate' => $commissionRate,
                'commission_type' => $commissionType,
            ]);

        } catch (\Exception $e) {
            Log::error('OkMobilityBookingController::createAffiliateCommission - Error creating commission', [
                'error' => $e->getMessage(),
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'trace' => $e->getTraceAsString(),
            ]);
            // Don't throw here - commission creation failure shouldn't break the booking
        }
    }
}
