# Skyscanner Separate Implementation Tasks

## Purpose

This file is the working task tracker for the direct Skyscanner integration work.

Main rule:

- build Skyscanner separately
- do not disturb the current working platform flow
- reuse backend logic only where safe
- do not change existing live integrations unless absolutely required

## Current Platform Context

The platform currently supports:

- our own cars
- our own vendors
- external API providers
- internal vehicles exposed to consumers

These flows are already working properly, so the Skyscanner integration must not interfere with them.

## Final Architecture Decision

We will create a **separate Skyscanner integration layer**.

We will **not** expose the current internal provider API directly to Skyscanner.

We will reuse internal search and booking logic only where safe.

## Working Scope

Current working assumption:

- Skyscanner integration starts as a separate integration
- existing flows remain untouched
- internal inventory remains the first safe source for Skyscanner preparation
- wider inventory scope can be added later only if explicitly approved

## Status Legend

- `[ ]` Not started
- `[~]` In progress
- `[x]` Done
- `[B]` Blocked

## Confirmed Facts

- [x] Existing production flow will remain separate
- [x] Existing internal provider API will not be exposed directly to Skyscanner
- [x] Existing internal API routes already exist for internal gateway use
- [x] Existing normalized internal vehicle transformation already exists
- [x] Existing booking and availability logic already exists
- [x] Skyscanner onboarding is active under case `PSM-46100`
- [x] Technical survey is required when our website side is ready
- [x] IP allowlisting is required before Skyscanner testing
- [x] DV is mandatory before go-live
- [x] Keyword tracking is conditional on commercial agreement
- [x] Email-based process documented in `SKYSCANNER_CASE_PSM_46100_PROCESS.md`

## Existing Code Reuse Map

### Safe candidates to reuse as backend logic

- internal availability and inventory filtering logic
- internal vehicle normalization logic
- existing booking/domain models
- existing location grouping concepts

### Existing files worth reviewing and reusing carefully

- `routes/api.php`
- `app/Http/Controllers/Api/InternalProviderController.php`
- `app/Http/Controllers/Api/InternalVehicleController.php`
- `app/Http/Controllers/Api/InternalLocationController.php`
- `app/Services/Search/InternalSearchVehicleFactory.php`
- `app/Models/Vehicle.php`
- `app/Models/Booking.php`

### Existing flows that must remain untouched

- current website search flow
- current consumer booking flow
- current internal provider API flow
- current vendor flow
- current external provider flow

### Important rule

We may reuse logic from existing classes, but we should not plug Skyscanner directly into existing public/internal routes.

## Current Blockers

- [B] Final Skyscanner contract is not yet available
- [B] Final DV documentation is not yet available
- [B] Final reporting specification is not yet available
- [B] Local automated verification is not yet configured for this direct setup

## External Process Milestones

### Before Technical Survey

- [x] finalize our isolated integration design
- [ ] finalize inventory scope
- [x] finalize security approach
- [x] finalize redirect/deeplink approach
- [x] finalize testing readiness answers

### Before Skyscanner Testing

- [ ] technical survey submitted with case `PSM-46100`
- [ ] Skyscanner IPs allowlisted on our side
- [x] isolated Skyscanner endpoints ready for partner testing

### Before Go-Live

- [B] final DV documentation received from Skyscanner
- [ ] DV implemented
- [ ] keyword tracking confirmed as agreed or not agreed
- [B] final reporting format confirmed by Skyscanner

## Phase 1: Safe Foundation Review

### Task 1: Record the separate integration rule

- [x] Confirm Skyscanner must remain separate from current flow
- [x] Confirm current production flow must remain unchanged
- [x] Record that all new code should be isolated

### Task 2: Record reusable platform pieces

- [~] Identify exactly which search logic can be reused safely
- [~] Identify exactly which pricing logic can be reused safely
- [~] Identify exactly which booking logic can be reused safely
- [~] Identify exactly which location logic can be reused safely

### Task 3: Record non-reusable integration pieces

- [x] Mark existing internal provider endpoints as internal-only
- [x] Mark existing current website flow as non-Skyscanner
- [x] Mark tracking/reporting/deeplink logic as new work

## Phase 2: Skyscanner Module Design

### Task 4: Define isolated module structure

- [x] Create target file/module list for Skyscanner
- [x] Define controller responsibilities
- [x] Define service responsibilities
- [x] Define mapper responsibilities
- [~] Define tracking responsibilities

### Proposed first-pass module list

- `app/Http/Controllers/Skyscanner/CarHireSearchController.php`
- `app/Http/Controllers/Skyscanner/CarHireRedirectController.php`
- `app/Services/Skyscanner/CarHireSearchService.php`
- `app/Services/Skyscanner/CarHireQuoteMapper.php`
- `app/Services/Skyscanner/CarHireFieldStrategyService.php`
- `app/Services/Skyscanner/CarHireTrackingService.php`
- `app/Services/Skyscanner/CarHireAuditLogService.php`
- `app/Services/Skyscanner/CarHireSecurityService.php`
- `app/Services/Skyscanner/CarHireQuoteValidationService.php`
- `app/Services/Skyscanner/CarHireInternalInventoryService.php`
- `routes/skyscanner.php` or isolated Skyscanner section in `routes/api.php`

These are only isolated targets. They should not replace existing classes.

### Task 5: Define isolated route structure

- [x] Decide Skyscanner API route prefix
- [x] Decide Skyscanner redirect route
- [x] Decide whether to separate public and private endpoints
- [x] Confirm no existing route behavior changes are required

### Task 6: Define quote lifecycle

- [x] Define quote creation flow
- [x] Define quote storage flow
- [x] Define quote expiry flow
- [x] Define quote revalidation flow

## Phase 3: Data Readiness

### Task 7: Audit minimum Skyscanner-ready vehicle fields

- [x] Brand
- [x] Model
- [x] Price
- [x] Currency
- [x] Transmission
- [x] Fuel
- [x] Seats
- [x] Doors
- [x] Image
- [x] Pickup location
- [x] Fuel policy
- [x] Mileage policy
- [x] Cancellation policy

### Task 8: Decide field gaps

- [x] Decide SIPP strategy
- [x] Decide supplier naming strategy
- [x] Decide location identifier strategy
- [x] Decide final quote validation rules

## Phase 4: Tracking And Security Design

### Task 9: Define redirect tracking

- [x] Define redirect ID capture
- [x] Define redirect-to-quote mapping
- [x] Define redirect-to-booking mapping
- [x] Define logging rules
- [~] Define DV-ready internal event structure
- [~] Define keyword-tracking readiness inputs

### Task 10: Define security model

- [x] Define partner auth strategy
- [x] Define IP allowlist strategy
- [x] Define signed deeplink strategy
- [x] Define backend validation strategy
- [x] Define survey-ready testing access approach

## Phase 5: Isolated Implementation

### Task 11: Build isolated Skyscanner skeleton

- [x] Create Skyscanner routes
- [x] Create Skyscanner controller
- [x] Create Skyscanner service
- [x] Create Skyscanner mapper
- [x] Create isolated tracking/security service stubs
- [x] Keep existing flows untouched

### Task 12: Build quote and redirect flow

- [x] Add quote model/storage design
- [x] Add redirect landing handler
- [x] Add quote lookup and expiry handling
- [x] Add quote mismatch handling

### Task 13: Build booking correlation

- [x] Store Skyscanner redirect ID
- [x] Link redirect to booking
- [x] Prepare reporting output

## Phase 6: Partner-Specific Completion

### Task 14: Map to final Skyscanner contract

- [ ] Match final request fields
- [ ] Match final response fields
- [ ] Match final DV implementation
- [ ] Match final reporting format

## Immediate Next Build Tasks

These are the next safest tasks to execute in code:

1. expand internal quote mapping for final Skyscanner-facing payload readiness
2. prepare for final contract mapping once Skyscanner shares the exact partner spec
3. keep DV and reporting wiring ready for the final Skyscanner docs
4. decide whether to surface validation failures in the future search endpoint or keep them internal only

## Current Isolated Search Rule

- [x] only validation-ready quotes are eligible for future Skyscanner output
- [x] validation failures remain internal only
- [x] rejected candidates are audit logged and excluded from quote output
- [x] authenticated isolated search controller returns internal inventory quotes only

## Direct Rule For This Work

If any implementation step risks changing the current production flow unexpectedly:

- stop
- isolate the change further
- do not merge it into existing behavior

This work exists specifically to avoid that risk.
