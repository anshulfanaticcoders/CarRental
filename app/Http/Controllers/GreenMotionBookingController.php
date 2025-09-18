<?php

namespace App\Http\Controllers;

use App\Models\GreenMotionBooking;
use App\Services\GreenMotionService;
use Illuminate\Http\Request;
use SimpleXMLElement;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\User; // Added for admin notification
use App\Notifications\Booking\GreenMotionBookingCreatedAdminNotification; // Added for admin notification
use App\Notifications\Booking\GreenMotionBookingCreatedCustomerNotification; // Added for customer notification
use App\Jobs\SendGreenMotionBookingNotificationJob;

class GreenMotionBookingController extends Controller
{
    public function __construct(private GreenMotionService $greenMotionService)
    {
    }

    public function processGreenMotionBookingPayment(Request $request)
    {
        $provider = $request->input('provider', 'greenmotion');
        try {
            $this->greenMotionService->setProvider($provider);
        } catch (\Exception $e) {
            return response()->json(['error' => "Invalid provider specified: {$provider}"], 400);
        }

        Stripe::setApiKey(config('stripe.secret'));

        $validatedData = $request->validate([
            'location_id' => 'required|string',
            'start_date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_date' => 'required|date_format:Y-m-d',
            'end_time' => 'required|date_format:H:i',
            'rentalCode' => 'required|string',
            'age' => 'required|integer|min:18',
            'dropoff_location_id' => 'nullable|string',
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
            'extras.*.option_qty' => 'required|integer|min:0',
            'extras.*.option_total' => 'required|numeric|min:0',
            'extras.*.pre_pay' => 'nullable|string|in:Yes,No',
            'vehicle_id' => 'required|string',
            'vehicle_total' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'grand_total' => 'required|numeric|min:0',
            'paymentHandlerRef' => 'nullable|string|max:255',
            'quoteid' => 'required|string|max:255',
            'payment_type' => 'nullable|string|in:POA,PREPAID',
            'customer.bplace' => 'nullable|string|max:255',
            'customer.bdate' => 'nullable|date_format:Y-m-d',
            'customer.idno' => 'nullable|string|max:50',
            'customer.idplace' => 'nullable|string|max:255',
            'customer.idissue' => 'nullable|date_format:Y-m-d',
            'customer.idexp' => 'nullable|date_format:Y-m-d',
            'customer.licissue' => 'nullable|date_format:Y-m-d',
            'customer.licplace' => 'nullable|string|max:255',
            'customer.licexp' => 'nullable|date_format:Y-m-d',
            'customer.idurl' => 'nullable|url',
            'customer.id_rear_url' => 'nullable|url',
            'customer.licurl' => 'nullable|url',
            'customer.lic_rear_url' => 'nullable|url',
            'customer.verification_response' => 'nullable|string|max:1000',
            'customer.custimage' => 'nullable|url',
            'customer.dvlacheckcode' => 'nullable|string|max:50',
            'remarks' => 'nullable|string|max:1000',
            'user_id' => 'nullable|exists:users,id', // Add user_id validation
            'vehicle_location' => 'nullable|string|max:255', // Add vehicle_location validation
            'provider' => 'nullable|string',
        ]);

        $customerDetails = $validatedData['customer'];
        $customerDetails['comments'] = 'Test Booking - ' . ($customerDetails['comments'] ?? '');

        try {
            $xmlResponse = $this->greenMotionService->makeReservation(
                $validatedData['location_id'],
                $validatedData['start_date'],
                $validatedData['start_time'],
                $validatedData['end_date'],
                $validatedData['end_time'],
                $validatedData['age'],
                $customerDetails,
                $validatedData['vehicle_id'],
                $validatedData['vehicle_total'],
                $validatedData['currency'],
                $validatedData['grand_total'],
                $validatedData['paymentHandlerRef'] ?? null,
                $validatedData['quoteid'],
                $validatedData['extras'] ?? [],
                $validatedData['dropoff_location_id'] ?? null,
                $validatedData['payment_type'] ?? 'POA',
                $validatedData['rentalCode'],
                $validatedData['remarks'] ?? null
            );

            if (is_null($xmlResponse) || empty($xmlResponse)) {
                Log::error('GreenMotion API returned null or empty XML response for MakeReservation.');
                return response()->json(['error' => 'Failed to submit booking to GreenMotion API. API returned empty response.'], 500);
            }

            libxml_use_internal_errors(true);
            $xmlObject = simplexml_load_string($xmlResponse);

            if ($xmlObject === false) {
                $errors = libxml_get_errors();
                foreach ($errors as $error) {
                    Log::error('XML Parsing Error (MakeReservation): ' . $error->message);
                }
                libxml_clear_errors();
                return response()->json(['error' => 'Failed to parse XML response for booking submission from GreenMotion API.'], 500);
            }

            $bookingReference = (string) $xmlObject->response->booking_ref ?? null;
            $status = (string) $xmlObject->response->status ?? 'pending'; // Default to pending if not explicitly returned

            if (!$bookingReference) {
                Log::error('GreenMotion API did not return a booking reference for MakeReservation.', [
                    'response_xml' => $xmlResponse,
                    'parsed_response_array' => json_decode(json_encode($xmlObject), true) // Log as array
                ]);
                return response()->json(['error' => 'Booking submitted to GreenMotion, but no reference received. Please check logs.'], 500);
            }

            // Save booking to database
            $greenMotionBooking = GreenMotionBooking::create([
                'user_id' => $validatedData['user_id'] ?? auth()->id(), // Use provided user_id or authenticated user's ID
                'greenmotion_booking_ref' => $bookingReference,
                'vehicle_id' => $validatedData['vehicle_id'],
                'location_id' => $validatedData['location_id'],
                'vehicle_location' => $validatedData['vehicle_location'] ?? null, // Save vehicle location
                'start_date' => $validatedData['start_date'],
                'start_time' => $validatedData['start_time'],
                'end_date' => $validatedData['end_date'],
                'end_time' => $validatedData['end_time'],
                'age' => $validatedData['age'],
                'rental_code' => $validatedData['rentalCode'],
                'customer_details' => $customerDetails,
                'selected_extras' => $validatedData['extras'] ?? [],
                'vehicle_total' => $validatedData['vehicle_total'],
                'currency' => $validatedData['currency'],
                'grand_total' => $validatedData['grand_total'],
                'payment_handler_ref' => null, // Initially null
                'quote_id' => $validatedData['quoteid'],
                'payment_type' => $validatedData['payment_type'] ?? 'POA',
                'dropoff_location_id' => $validatedData['dropoff_location_id'] ?? null,
                'remarks' => $validatedData['remarks'] ?? null,
                'booking_status' => $status,
                'api_response' => json_decode(json_encode($xmlObject), true),
            ]);
            Log::info('GreenMotion booking saved to database successfully with ref: ' . $bookingReference);

            // Create Stripe Checkout Session
            $session = Session::create([
                'payment_method_types' => ['card', 'bancontact', 'klarna'], // Added more payment methods
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($validatedData['currency']),
                        'product_data' => [
                            'name' => 'GreenMotion Car Rental Booking',
                            'description' => "Booking for vehicle ID {$validatedData['vehicle_id']} (Ref: {$bookingReference})",
                        ],
                        'unit_amount' => $validatedData['grand_total'] * 100, // Amount in cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => url(app()->getLocale() . '/green-motion-booking-success?session_id={CHECKOUT_SESSION_ID}&booking_id=' . $greenMotionBooking->id),
                'cancel_url' => route('greenmotion.booking.cancel', ['locale' => app()->getLocale(), 'booking_id' => $greenMotionBooking->id]),
                'metadata' => [
                    'greenmotion_booking_id' => $greenMotionBooking->id,
                    'greenmotion_booking_ref' => $bookingReference,
                ],
            ]);

            // Save the session ID to the booking record
            $greenMotionBooking->update(['payment_handler_ref' => $session->id]);

            return response()->json([
                'sessionId' => $session->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in GreenMotionBookingController::processGreenMotionBookingPayment: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
            ]);
            return response()->json(['error' => 'An error occurred during booking or payment initiation: ' . $e->getMessage()], 500);
        }
    }

    public function greenMotionBookingSuccess(Request $request)
    {
        Stripe::setApiKey(config('stripe.secret'));

        $sessionId = $request->query('session_id');
        $greenMotionBookingId = $request->query('booking_id');

        if (!$sessionId || !$greenMotionBookingId) {
            Log::error('Missing session_id or booking_id in greenMotionBookingSuccess');
            return redirect()->route('greenmotion.booking.cancel', ['locale' => app()->getLocale(), 'booking_id' => $greenMotionBookingId])->with('error', 'Invalid payment session');
        }

        try {
            $session = Session::retrieve($sessionId);
            $greenMotionBooking = GreenMotionBooking::where('payment_handler_ref', $sessionId)->firstOrFail();

            if ($session->payment_status === 'paid') {
                $greenMotionBooking->update([
                    'booking_status' => 'confirmed',
                    'payment_handler_ref' => $session->payment_intent,
                ]);

                // Send notifications
                try {
                    dispatch(new SendGreenMotionBookingNotificationJob($greenMotionBooking));
                } catch (\Exception $e) {
                    Log::error('Failed to dispatch notification job for GreenMotion booking: ' . $e->getMessage(), [
                        'booking_id' => $greenMotionBooking->id
                    ]);
                }
                
                Log::info('GreenMotion booking confirmed via Stripe success callback. Booking ID: ' . $greenMotionBooking->id);
                
                // Redirect to the profile bookings page
                return redirect()->route('profile.bookings.green-motion', ['locale' => app()->getLocale()]);

            } else {
                Log::warning('Stripe session not paid for GreenMotion booking. Session ID: ' . $sessionId);
                $greenMotionBooking->update(['booking_status' => 'payment_failed']);
                return redirect()->route('greenmotion.booking.cancel', ['locale' => app()->getLocale(), 'booking_id' => $greenMotionBooking->id])->with('error', 'Payment not completed.');
            }

        } catch (\Exception $e) {
            Log::error('Error in GreenMotionBookingController::greenMotionBookingSuccess: ' . $e->getMessage(), [
                'exception' => $e,
                'session_id' => $sessionId,
                'booking_id' => $greenMotionBookingId
            ]);
            return redirect()->route('greenmotion.booking.cancel', ['locale' => app()->getLocale(), 'booking_id' => $greenMotionBookingId])->with('error', 'Error processing success callback: ' . $e->getMessage());
        }
    }

    public function greenMotionBookingCancel(Request $request)
    {
        $greenMotionBookingId = $request->query('booking_id');
        $greenMotionBooking = null;

        if ($greenMotionBookingId) {
            $greenMotionBooking = GreenMotionBooking::find($greenMotionBookingId);
            if ($greenMotionBooking) {
                $greenMotionBooking->update(['booking_status' => 'cancelled_by_user']);
                Log::info('GreenMotion booking cancelled by user via Stripe cancel callback. Booking ID: ' . $greenMotionBookingId);
            }
        }

        return Inertia::render('GreenMotionBookingCancel', [
            'bookingId' => $greenMotionBookingId,
            'booking' => $greenMotionBooking,
        ]);
    }

    public function getCustomerGreenMotionBookings()
    {
        $bookings = \App\Models\GreenMotionBooking::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return Inertia::render('Profile/Bookings/GreenMotionBookings', [
            'bookings' => $bookings,
        ]);
    }
}
