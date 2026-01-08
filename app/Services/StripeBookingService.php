<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\BookingExtra;
use App\Models\Customer;
use App\Services\AdobeCarService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StripeBookingService
{
    protected $adobeCarService;

    public function __construct(AdobeCarService $adobeCarService)
    {
        $this->adobeCarService = $adobeCarService;
    }

    /**
     * Create a booking from a Stripe Checkout Session
     */
    public function createBookingFromSession($session)
    {
        Log::info('StripeBookingService: Starting creation', ['session_id' => $session->id]);

        // Idempotency check
        $existingBooking = Booking::where('stripe_session_id', $session->id)->first();
        if ($existingBooking) {
            Log::info('StripeBookingService: Booking already exists', ['booking_id' => $existingBooking->id]);
            return $existingBooking;
        }

        $metadata = $session->metadata;

        // Start Transaction
        DB::beginTransaction();

        try {
            // Find or create customer
            $customer = $this->findOrCreateCustomer($metadata);
            Log::info('StripeBookingService: Customer processed', ['customer_id' => $customer->id]);

            // Create booking
            // For internal vehicles, set vehicle_id to actual vehicle ID
            $vehicleId = null;
            if (($metadata->vehicle_source ?? '') === 'internal' && !empty($metadata->vehicle_id)) {
                $vehicleId = (int) $metadata->vehicle_id;
            }

            $booking = Booking::create([
                'booking_number' => Booking::generateBookingNumber(),
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicleId, // Set for internal, null for external
                'provider_source' => $metadata->vehicle_source ?? 'greenmotion',
                'provider_vehicle_id' => $metadata->vehicle_id ?? null,
                'vehicle_name' => ($metadata->vehicle_brand ?? '') . ' ' . ($metadata->vehicle_model ?? ''),
                'vehicle_image' => $metadata->vehicle_image ?? null,
                'pickup_date' => $metadata->pickup_date,
                'pickup_time' => $metadata->pickup_time,
                'return_date' => $metadata->dropoff_date,
                'return_time' => $metadata->dropoff_time,
                'pickup_location' => $metadata->pickup_location,
                'return_location' => $metadata->dropoff_location ?? $metadata->pickup_location,
                'plan' => $metadata->package ?? 'BAS',
                'total_days' => (int) ($metadata->number_of_days ?? 1),
                'base_price' => (float) ($metadata->total_amount ?? 0),
                'tax_amount' => 0,
                'total_amount' => (float) ($metadata->total_amount ?? 0),
                'amount_paid' => (float) ($metadata->payable_amount ?? 0),
                'pending_amount' => (float) ($metadata->pending_amount ?? 0),
                'booking_currency' => $metadata->currency ?? 'EUR',
                'payment_status' => 'partial',
                'booking_status' => 'confirmed',
                'stripe_session_id' => $session->id,
                'stripe_payment_intent_id' => $session->payment_intent,
                'provider_booking_ref' => $metadata->provider_booking_ref ?? null,
            ]);

            Log::info('StripeBookingService: Booking record created', ['booking_id' => $booking->id]);

            // Create payment record
            BookingPayment::create([
                'booking_id' => $booking->id,
                'payment_method' => 'stripe',
                'transaction_id' => $session->payment_intent,
                'amount' => (float) ($metadata->payable_amount ?? 0),
                'payment_status' => 'succeeded',
                'payment_date' => now(),
            ]);

            // Create extras
            $extrasData = json_decode($metadata->extras_data ?? '[]', true);

            if (!empty($extrasData)) {
                // New Logic: Use detailed data from frontend
                foreach ($extrasData as $extraItem) {
                    BookingExtra::create([
                        'booking_id' => $booking->id,
                        'extra_type' => 'optional',
                        'extra_name' => $extraItem['name'] ?? 'Unknown Extra',
                        'quantity' => (int) ($extraItem['qty'] ?? 1),
                        'price' => (float) ($extraItem['total'] ?? 0),
                    ]);
                }
            } else {
                // Fallback Logic (Old)
                $extras = json_decode($metadata->extras ?? '[]', true);
                if (!empty($extras)) {
                    // Pre-fetch all needed addons to avoid N+1
                    $addonIds = array_keys($extras);
                    $addons = \App\Models\BookingAddon::whereIn('id', $addonIds)->get()->keyBy('id');

                    foreach ($extras as $extraId => $quantity) {
                        if ($quantity > 0) {
                            $addon = $addons->find($extraId);

                            // Fallback if addon not found (shouldn't happen if IDs are valid)
                            $name = $addon ? $addon->extra_name : "Extra #$extraId";
                            $price = $addon ? $addon->price : 0;

                            BookingExtra::create([
                                'booking_id' => $booking->id,
                                'extra_type' => 'optional',
                                'extra_name' => $name,
                                'quantity' => (int) $quantity,
                                'price' => (float) $price,
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            Log::info('StripeBookingService: Transaction committed successfully', ['booking_id' => $booking->id]);

            // Phase 2: Trigger Provider Reservations
            if ($booking->provider_source === 'locauto_rent') {
                $this->triggerLocautoReservation($booking, $metadata);
            } elseif ($booking->provider_source === 'greenmotion' || $booking->provider_source === 'usave') {
                $this->triggerGreenMotionReservation($booking, $metadata);
            } elseif ($booking->provider_source === 'adobe') {
                $this->triggerAdobeReservation($booking, $metadata);
            } elseif ($booking->provider_source === 'internal') {
                // Internal vehicles don't require external API reservation
                Log::info('StripeBookingService: Internal vehicle booking confirmed', [
                    'booking_id' => $booking->id,
                    'vehicle_id' => $booking->vehicle_id,
                    'plan' => $booking->plan,
                    'total_amount' => $booking->total_amount,
                ]);
            }

            return $booking;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('StripeBookingService: Error creating booking - Transaction Rolled Back', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function findOrCreateCustomer($metadata)
    {
        $email = $metadata->customer_email ?? null;

        if ($email) {
            $customer = Customer::where('email', $email)->first();
            if ($customer) {
                return $customer;
            }
        }

        return Customer::create([
            'name' => $metadata->customer_name ?? 'Guest',
            'email' => $email ?? 'guest_' . time() . '@temp.com',
            'phone' => $metadata->customer_phone ?? null,
        ]);
    }

    /**
     * Trigger reservation on Locauto API
     */
    protected function triggerLocautoReservation($booking, $metadata)
    {
        Log::info('Locauto: Triggering reservation for booking', ['booking_id' => $booking->id]);

        try {
            $locautoService = app(\App\Services\LocautoRentService::class);

            // Split name into first and last
            $nameParts = explode(' ', $metadata->customer_name ?? 'Guest User', 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? 'Guest';

            // Prepare extras (including protection plan)
            $extras = [];

            // Add protection code if present
            if (!empty($metadata->protection_code)) {
                $extras[] = [
                    'code' => $metadata->protection_code,
                    'quantity' => 1
                ];
            }

            // Add other extras
            $rawExtras = json_decode($metadata->extras ?? '[]', true);
            foreach ($rawExtras as $code => $qty) {
                if ($qty > 0) {
                    $realCode = str_replace('locauto_extra_', '', $code);
                    $extras[] = [
                        'code' => $realCode,
                        'quantity' => $qty
                    ];
                }
            }

            $reservationData = [
                'pickup_date' => $metadata->pickup_date,
                'pickup_time' => $metadata->pickup_time,
                'return_date' => $metadata->dropoff_date,
                'return_time' => $metadata->dropoff_time,
                'pickup_location_code' => $metadata->pickup_location_code,
                'return_location_code' => $metadata->return_location_code ?? $metadata->pickup_location_code,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'sipp_code' => $metadata->sipp_code,
                'extras' => $extras,
                'driver_age' => $metadata->customer_driver_age ?? 35,
                'email' => $metadata->customer_email ?? '',
                'phone' => $metadata->customer_phone ?? '',
            ];

            Log::info('Locauto: Sending reservation request', ['data' => $reservationData]);
            $xmlResponse = $locautoService->makeReservation($reservationData);

            if ($xmlResponse) {
                libxml_use_internal_errors(true);
                $xmlObject = simplexml_load_string($xmlResponse);

                if ($xmlObject !== false) {
                    $xmlObject->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
                    $xmlObject->registerXPathNamespace('ota', 'http://www.opentravel.org/OTA/2003/05');
                    $xmlObject->registerXPathNamespace('locauto', 'https://nextrent.locautorent.com');

                    // Standard OTA Success check
                    $success = $xmlObject->xpath('//ota:Success');
                    $uniqueId = $xmlObject->xpath('//ota:UniqueID');
                    
                    $confirmationNumber = null;
                    if (!empty($uniqueId)) {
                        $confirmationNumber = (string) $uniqueId[0]['ID'];
                    }

                    if ($confirmationNumber) {
                        Log::info('Locauto: Reservation successful', ['conf' => $confirmationNumber]);

                        $booking->update([
                            'provider_booking_ref' => $confirmationNumber,
                            'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Locauto Conf: " . $confirmationNumber
                        ]);
                    } else {
                        // Extract error
                        $errors = $xmlObject->xpath('//ota:Error');
                        $errorMessage = 'Confirmation number missing in response.';
                        if (!empty($errors)) {
                            $errorMessage = (string) $errors[0];
                            if (empty($errorMessage)) {
                                $errorMessage = (string) $errors[0]['ShortText'];
                            }
                        }
                        
                        Log::error('Locauto: Reservation failed', ['error' => $errorMessage, 'response' => $xmlResponse]);
                        $booking->update([
                            'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Locauto Reservation failed: " . $errorMessage
                        ]);
                    }
                } else {
                    Log::error('Locauto: Failed to parse XML response', ['response' => $xmlResponse]);
                    $booking->update([
                        'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Locauto Reservation failed: Invalid XML response."
                    ]);
                }
                libxml_clear_errors();
            } else {
                Log::error('Locauto: Empty response from API');
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Locauto Reservation failed: No response from API."
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Locauto: Exception during reservation trigger', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $booking->update([
                'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Locauto Reservation Error: " . $e->getMessage()
            ]);
        }
    }

    /**
     * Trigger reservation on GreenMotion API
     */
    protected function triggerGreenMotionReservation($booking, $metadata)
    {
        Log::info('GreenMotion: Triggering reservation for booking', [
            'booking_id' => $booking->id,
            'metadata' => (array) $metadata
        ]);

        try {
            $greenMotionService = app(\App\Services\GreenMotionService::class);
            $greenMotionService->setProvider($booking->provider_source); // greenmotion or usave

            // Split name into first and last
            $nameParts = explode(' ', $metadata->customer_name ?? 'Guest User', 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? 'Guest';

            $customerDetails = [
                'firstname' => $firstName,
                'surname' => $lastName,
                'phone' => $metadata->customer_phone,
                'email' => $metadata->customer_email,
                'flight_number' => $metadata->flight_number ?? '',
            ];

            // Parse detailed extras if available
            $extras = [];
            $rawExtras = json_decode($metadata->extras_data ?? '[]', true);
            foreach ($rawExtras as $ex) {
                if (($ex['qty'] ?? 0) > 0) {
                    $extras[] = [
                        'id' => $ex['id'] ?? $ex['optionID'],
                        'option_qty' => $ex['qty'],
                        'option_total' => $ex['total'],
                        'pre_pay' => $ex['pre_pay'] ?? 'No'
                    ];
                }
            }

            // Get Vehicle ID - strip provider prefix if present
            $vehicleId = $metadata->vehicle_id;
            if (strpos($vehicleId, $booking->provider_source . '_') === 0) {
                $vehicleId = substr($vehicleId, strlen($booking->provider_source . '_'));
            }

            $xmlResponse = $greenMotionService->makeReservation(
                $metadata->pickup_location_code,
                $metadata->pickup_date,
                $metadata->pickup_time,
                $metadata->dropoff_date,
                $metadata->dropoff_time,
                $metadata->customer_driver_age ?? 35,
                $customerDetails,
                $vehicleId,
                $metadata->vehicle_total ?? $metadata->total_amount, // vehicleTotal
                $metadata->currency,
                $metadata->total_amount, // grandTotal
                $booking->stripe_session_id,
                $metadata->quoteid,
                $extras,
                $metadata->dropoff_location_code ?? $metadata->pickup_location_code,
                'POA', // Payment type
                $metadata->package ?? $metadata->rental_code ?? '1' // rentalCode (Product Type)
            );

            if ($xmlResponse) {
                libxml_use_internal_errors(true);
                $xmlObject = simplexml_load_string($xmlResponse);
                $confirmationNumber = (string) ($xmlObject->response->booking_ref ?? $xmlObject->response->bookingReference ?? '');

                if ($xmlObject !== false && !empty($confirmationNumber)) {
                    Log::info('GreenMotion: Reservation successful', ['conf' => $confirmationNumber]);

                    $booking->update([
                        'provider_booking_ref' => $confirmationNumber,
                        'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "GreenMotion Conf: " . $confirmationNumber
                    ]);
                } else {
                    $errorMessage = 'Confirmation number missing in response.';
                    if ($xmlObject !== false && isset($xmlObject->response->errors->message)) {
                        $errorMessage = (string) $xmlObject->response->errors->message;
                    } elseif ($xmlObject !== false && isset($xmlObject->response->errors->error->message)) {
                        $errorMessage = (string) $xmlObject->response->errors->error->message;
                    }
                    
                    Log::error('GreenMotion: Reservation failed', ['error' => $errorMessage, 'response' => $xmlResponse]);
                    $booking->update([
                        'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "GreenMotion Reservation failed: " . $errorMessage
                    ]);
                }
                libxml_clear_errors();
            } else {
                Log::error('GreenMotion: Empty response from API');
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "GreenMotion Reservation failed: No response from API."
                ]);
            }

        } catch (\Exception $e) {
            Log::error('GreenMotion: Exception during reservation trigger', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $booking->update([
                'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "GreenMotion Reservation Error: " . $e->getMessage()
            ]);
        }
    }

    /**
     * Trigger reservation on Adobe API
     * 
     * IMPORTANT: Adobe API requires:
     * 1. Lowercase field names: startdate, enddate, pickupoffice, returnoffice
     * 2. The `items` array MUST be populated with protections/extras from GetCategoryWithFare
     * 3. Separate name fields: name, lastName, fullName
     */
    protected function triggerAdobeReservation($booking, $metadata)
    {
        Log::info('Adobe: Triggering reservation for booking', ['booking_id' => $booking->id]);

        try {
            $pickupOffice = $metadata->pickup_location_code ?? '';
            $returnOffice = $metadata->return_location_code ?? $pickupOffice;
            $startDate = $metadata->pickup_date . ' ' . $metadata->pickup_time;
            $endDate = ($metadata->dropoff_date ?? $metadata->return_date ?? '') . ' ' . ($metadata->dropoff_time ?? $metadata->return_time ?? '');
            
            // IMPORTANT: Use adobe_category (single-letter code like 'e', 'n') NOT vehicle_category (descriptive like 'Sedan')
            $category = $metadata->adobe_category ?? '';
            if (empty($category)) {
                // Fallback: try to extract from vehicle_id (format: adobe_OCO_e)
                $vehicleId = $metadata->vehicle_id ?? '';
                if (strpos($vehicleId, 'adobe_') === 0) {
                    $parts = explode('_', $vehicleId);
                    $category = $parts[2] ?? '';
                }
            }
            if (empty($category)) {
                $category = $metadata->sipp_code ?? $metadata->vehicle_category ?? '';
            }
            
            Log::info('Adobe: Using category for booking', ['category' => $category, 'vehicle_id' => $metadata->vehicle_id ?? '']);


            // Fetch protections and extras from Adobe API - THIS IS MANDATORY
            $categoryItems = $this->adobeCarService->getProtectionsAndExtras(
                $pickupOffice,
                $category,
                ['startdate' => $startDate, 'enddate' => $endDate]
            );

            // Build the items array for booking payload
            $bookingItems = [];
            $allItems = array_merge($categoryItems['protections'] ?? [], $categoryItems['extras'] ?? []);
            
            // Check for user-selected protections from metadata
            $selectedProtectionCodes = [];
            if (!empty($metadata->protection_code)) {
                $protectionCode = $metadata->protection_code;
                // Strip adobe_protection_ prefix if present
                if (strpos($protectionCode, 'adobe_protection_') === 0) {
                    $protectionCode = substr($protectionCode, strlen('adobe_protection_'));
                }
                // Handle comma-separated codes
                $codes = explode(',', $protectionCode);
                foreach ($codes as $code) {
                    $code = trim($code);
                    if ($code) {
                        $selectedProtectionCodes[] = $code;
                    }
                }
            }
            
            Log::info('Adobe: Selected protection codes', ['codes' => $selectedProtectionCodes]);
            
            // Parse extras from both 'extras' and 'extras_data' fields
            $extrasData = json_decode($metadata->extras_data ?? '[]', true);
            $extrasSimple = json_decode($metadata->extras ?? '[]', true);
            
            // Merge both sources
            if (!empty($extrasSimple) && is_array($extrasSimple)) {
                $extrasData = array_merge($extrasData ?? [], $extrasSimple);
            }
            
            $selectedExtraCodes = [];
            if (!empty($extrasData) && is_array($extrasData)) {
                foreach ($extrasData as $extra) {
                    // Handle various key formats from frontend
                    $code = $extra['id'] ?? $extra['Code'] ?? $extra['code'] ?? $extra['extraCode'] ?? '';
                    // Strip adobe_ prefix if present
                    if (strpos($code, 'adobe_extra_') === 0) {
                        $code = substr($code, strlen('adobe_extra_'));
                    }
                    if ($code) {
                        $selectedExtraCodes[$code] = $extra['qty'] ?? $extra['Quantity'] ?? $extra['quantity'] ?? 1;
                    }
                }
            }
            
            Log::info('Adobe: Extracted extras from metadata', [
                'protection_codes' => $selectedProtectionCodes,
                'extra_codes' => $selectedExtraCodes,
                'raw_extras_data' => $metadata->extras_data ?? 'null',
                'raw_extras' => $metadata->extras ?? 'null'
            ]);


            // IMPORTANT: Only send items that should be INCLUDED in the booking
            // Adobe ignores quantity for items - only counts items where included=true
            foreach ($allItems as $item) {
                $code = $item['code'] ?? '';
                $isRequired = $item['required'] ?? false;
                $isProtection = ($item['type'] ?? '') === 'Proteccion';
                $isExtra = ($item['type'] ?? '') === 'Adicionales';

                // Determine if this item should be included
                $shouldInclude = false;
                $quantity = 0;
                
                if ($isRequired) {
                    $shouldInclude = true;
                    $quantity = 1;
                } elseif ($isProtection && in_array($code, $selectedProtectionCodes)) {
                    $shouldInclude = true;
                    $quantity = 1;
                } elseif ($isExtra && isset($selectedExtraCodes[$code])) {
                    $shouldInclude = true;
                    $quantity = $selectedExtraCodes[$code];
                }

                // Only add items that should be included (minimal items approach)
                if ($shouldInclude) {
                    $bookingItems[] = [
                        'code' => $code,
                        'quantity' => $quantity,
                        'total' => $item['total'] ?? 0,
                        'order' => $item['order'] ?? 0,
                        'type' => $item['type'] ?? '',
                        'included' => true, // MUST be true for Adobe to charge it
                        'description' => $item['description'] ?? '',
                        'information' => $item['information'] ?? '',
                        'name' => $item['name'] ?? '',
                        'required' => $isRequired
                    ];
                }
            }
            
            Log::info('Adobe: Built items array for booking (minimal)', [
                'items_count' => count($bookingItems),
                'items' => array_map(fn($i) => $i['code'] . ':qty=' . $i['quantity'], $bookingItems)
            ]);


            // Construct detailed comment
            $comment = "Website Booking (Post-Payment). ";
            if (isset($metadata->notes) && $metadata->notes) {
                $comment .= "Notes: " . $metadata->notes;
            }

            $nameParts = explode(' ', $metadata->customer_name ?? 'Guest User', 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '.';

            // Build payload with CORRECT Swagger schema field names
            $adobeParams = [
                'bookingNumber' => 0, // Required for new bookings
                'category' => $category,
                'startdate' => $startDate, // lowercase!
                'pickupoffice' => $pickupOffice, // lowercase!
                'enddate' => $endDate, // lowercase!
                'returnoffice' => $returnOffice, // lowercase!
                'customerCode' => $this->adobeCarService->getCustomerCode(),
                'name' => $firstName,
                'lastName' => $lastName,
                'fullName' => $metadata->customer_name ?? 'Guest User',
                'email' => $metadata->customer_email ?? '',
                'phone' => $metadata->customer_phone ?? '',
                'country' => 'CR',
                'language' => 'en',
                'customerComment' => $comment,
                'flightNumber' => $metadata->flight_number ?? '',
                'items' => $bookingItems // MANDATORY items array
            ];

            Log::info('Adobe: Sending reservation request', ['data' => $adobeParams]);
            $adobeResponse = $this->adobeCarService->createBooking($adobeParams);

            if (isset($adobeResponse['result']) && $adobeResponse['result'] && isset($adobeResponse['data']['bookingNumber'])) {
                $providerBookingRef = $adobeResponse['data']['bookingNumber'];
                Log::info('Adobe: Reservation successful', ['booking_ref' => $providerBookingRef]);

                $booking->update([
                    'provider_booking_ref' => $providerBookingRef,
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Adobe Conf: " . $providerBookingRef
                ]);
            } else {
                Log::error('Adobe: Reservation failed', ['response' => $adobeResponse]);
                $booking->update([
                    'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Adobe Reservation failed: " . json_encode($adobeResponse)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Adobe: Exception during reservation trigger', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $booking->update([
                'notes' => ($booking->notes ? $booking->notes . "\n" : "") . "Adobe Reservation Error: " . $e->getMessage()
            ]);
        }
    }
}
