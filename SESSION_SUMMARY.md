# Session Summary

This note captures what was reviewed so you do not have to re-explain it next time.
No code changes were made in this session.

## Scope We Reviewed

- Unified location search UI and provider dropoff handling in the search bar.
- SPA booking flow across results -> extras -> checkout -> success/details.
- Provider-specific package/extras logic and totals in booking extras step.
- Stripe checkout initiation and booking creation from session metadata.
- Provider reservation triggers for Adobe, Locauto, GreenMotion/USave, Renteon, OK Mobility, and internal.
- Provider controllers and services for GreenMotion/USave, Adobe, Locauto, Renteon, OK Mobility, Wheelsys.
- Unified location clustering/lookup services and update command.

## Key Flow (High Level)

1. `SearchBar.vue` uses `unified_locations.json` to select pickup/dropoff and sets provider context (`mixed` when external providers exist).
2. `SearchResults.vue` renders vehicles, handles currency conversion, and drives SPA steps (results -> extras -> checkout).
3. `BookingExtrasStep.vue` builds provider-specific packages/extras and computes totals, payable/pending amounts, and selected extras payload.
4. `StripeCheckoutButton.vue` posts booking data to create a Stripe Checkout session and redirects.
5. `StripeBookingService::createBookingFromSession` persists booking/payment/extras, then triggers provider reservations based on `provider_source`.
6. `Booking/Success.vue` and `Booking/BookingDetails.vue` render confirmation and booking detail views.
7. Provider controllers/services handle availability, booking, and cancellation flows per provider API.
8. Unified location services normalize pickup/dropoff across providers.

## Files Reviewed (Primary)

Frontend:
- `resources/js/Pages/SearchResults.vue`
- `resources/js/Components/SearchBar.vue`
- `resources/js/Components/BookingExtrasStep.vue`
- `resources/js/Components/StripeCheckoutButton.vue`
- `resources/js/Pages/Booking/Success.vue`
- `resources/js/Pages/Booking/BookingDetails.vue`

Backend:
- `app/Services/StripeBookingService.php`
- `app/Http/Controllers/SearchController.php`
- `app/Http/Controllers/StripeCheckoutController.php`
- `app/Http/Controllers/StripeWebhookController.php`
- `app/Http/Controllers/AdobeProviderController.php`
- `app/Http/Controllers/LocautoProviderController.php`
- `app/Http/Controllers/GreenMotionProviderController.php`
- `app/Http/Controllers/OkMobilityProviderController.php`
- `app/Http/Controllers/RenteonProviderController.php`
- `app/Http/Controllers/WheelsysProviderController.php`
- `app/Services/AdobeService.php`
- `app/Services/LocautoService.php`
- `app/Services/GreenMotionService.php`
- `app/Services/OkMobilityService.php`
- `app/Services/RenteonService.php`
- `app/Services/WheelsysService.php`
- `app/Services/UnifiedLocationService.php`
- `app/Services/UnifiedLocationClusterService.php`
- `app/Console/Commands/UpdateUnifiedLocationsCommand.php`
- `app/Models/Booking.php`
- `app/Models/GreenMotionBooking.php`
- `app/Models/OkMobilityBooking.php`
- `app/Models/WheelsysBooking.php`
- `app/Models/Vehicle.php`

## What We Mapped

- Unified location selection, provider dropoff list fetching, and mixed-provider search setup in `SearchBar.vue`.
- SPA transitions and currency conversion logic in `SearchResults.vue`.
- Provider-specific package/extras models and totals for Adobe, Locauto, Internal, Renteon, OK Mobility, and GreenMotion/USave in `BookingExtrasStep.vue`.
- Stripe session creation and metadata usage for booking creation in `StripeBookingService`.
- Search aggregation across providers and mixed-provider handling in `SearchController`.
- Stripe checkout session creation and webhook processing for booking/payment updates.
- Provider controller endpoints for availability, booking, and cancellation.
- Provider services for API auth, rate mapping, booking payloads, and reservation calls.
- Unified location clustering/normalization and update command refresh.
- Provider reservation payloads and response handling for Adobe, Locauto, GreenMotion/USave, Renteon, OK Mobility, and internal bookings.

## Provider Flow Map (Compact)

- Internal: `SearchController` uses local `Vehicle` data; booking stored in `Booking`/`BookingPayment`; no external reservation call.
- GreenMotion/USave: `SearchController` pulls availability; `GreenMotionProviderController` + `GreenMotionService` handle booking and optional cancel; booking stored in `GreenMotionBooking` plus core `Booking`.
- Adobe: `SearchController` availability; `AdobeProviderController` + `AdobeService` handle protections/extras and booking; reservation triggered from `StripeBookingService`.
- LocautoRent: `SearchController` availability; `LocautoProviderController` + `LocautoService` handle booking/cancel; reservation triggered from `StripeBookingService`.
- OK Mobility: `SearchController` availability; `OkMobilityProviderController` + `OkMobilityService` handle booking/cancel; booking stored in `OkMobilityBooking` plus core `Booking`.
- Renteon: `SearchController` availability; `RenteonProviderController` + `RenteonService` handle booking/cancel; reservation triggered from `StripeBookingService`.
- Wheelsys: `SearchController` availability; `WheelsysProviderController` + `WheelsysService` handle booking/cancel; booking stored in `WheelsysBooking` plus core `Booking`.

## Related Docs (Already in Repo)

- `PROJECT_SUMMARY.md`
- `BOOKING_FLOW_DOCUMENTATION.md`
