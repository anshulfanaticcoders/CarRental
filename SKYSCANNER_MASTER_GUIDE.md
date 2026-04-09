# Skyscanner Master Guide

## Purpose

This is the single main Skyscanner handoff document for the team.

Use this file for:

- what has been done so far
- what is pending
- what we do next
- what is needed later on production

Separate from this file, only keep:

- `SKYSCANNER_SEPARATE_IMPLEMENTATION_TASKS.md` for task tracking

## Current Position

We are building Skyscanner as a **separate integration**.

We are **not** connecting Skyscanner directly to the current live consumer/vendor/external-provider flow.

Reason:

- current platform is already working
- Skyscanner needs separate API, redirect, tracking, and reporting handling
- this approach avoids breaking the live website flow

## What Skyscanner Needs From Us

Skyscanner does not take data from our website pages.

They need:

- our API
- our deeplink/redirect flow
- our tracking/reporting setup

Flow:

1. Skyscanner sends a search request to our API.
2. Our API returns car quotes.
3. Skyscanner shows those quotes on their site.
4. User clicks our offer.
5. User is redirected to our site.
6. Booking is completed on our site.

## Phase 1: Foundation Decision

### Completed

- Skyscanner integration will stay separate
- current live platform flow will remain untouched
- internal inventory is the first rollout scope

### Result

We now have a safe isolated direction for the integration.

## Phase 2: Local Coding Work Completed

### Separate Skyscanner backend layer created

Created locally:

- isolated Skyscanner services
- isolated Skyscanner controllers
- isolated Skyscanner route file
- isolated Skyscanner config

### Search/API foundation created

Built locally:

- authenticated Skyscanner search controller
- separate internal inventory adapter for Skyscanner
- isolated search service that returns only valid quotes

### Quote flow created

Built locally:

- quote creation
- quote storage
- quote expiry
- quote revalidation
- quote mismatch handling

### Security created

Built locally:

- API key validation
- signed redirect protection
- redirect signature validation
- allowlist-ready security structure

### Tracking created

Built locally:

- redirect ID capture
- redirect to quote mapping
- redirect to booking mapping

### Logging created

Built locally:

- quote created logs
- rejected quote logs
- redirect logs
- booking correlation logs

### Data readiness created

Built locally:

- inventory readiness audit
- SIPP strategy
- supplier naming strategy
- location identifier strategy
- quote validation rules

### Internal-only filtering created

Current local rule:

- only valid quotes are allowed for future Skyscanner output
- invalid quotes stay internal only
- invalid candidates are logged and excluded

### Current local test status

Local isolated Skyscanner test suite is passing:

- `43 tests`
- `127 assertions`

## Phase 3: What Is Done vs Not Done

### Done from our side

- separate Skyscanner structure
- internal search foundation
- authenticated API foundation
- quote lifecycle
- redirect and booking correlation
- logging
- validation
- security layer
- internal filtering

### Not done yet

- final Skyscanner request contract
- final Skyscanner response contract
- DV implementation
- final reporting delivery format
- production rollout wiring
- final live partner testing with Skyscanner

## Phase 4: Immediate Next Step

### What we do now

Prepare the technical survey answers using what is already built.

We now have enough internal clarity for:

- architecture
- security
- testing direction
- search/quote approach
- tracking foundation

### What must be confirmed before survey submission

- business/commercial contact
- technical contact
- rollout scope confirmation
- keyword tracking agreement status

### What we should not do yet

- do not enable production routes
- do not expose unfinished live integration
- do not guess final Skyscanner request/response contract

## Phase 5: What Still Depends On Skyscanner

We still need final partner-specific details from Skyscanner for:

- final API request format
- final API response format
- DV documentation
- final reporting method and format

These are the main external blockers now.

## Phase 6: What Will Be Needed On Production `vrooem.com`

Production work should happen only after contract details are finalized.

### Production tasks later

- add production Skyscanner config values
- add production API key and signing secret
- allowlist Skyscanner IPs
- expose isolated routes in a controlled way
- verify redirect/signature handling
- verify quote lookup and booking correlation
- add production monitoring for Skyscanner flow
- implement DV once Skyscanner shares the final docs
- implement report delivery once format is confirmed

## Phase 7: Current Practical Status

### Developer status

Backend foundation is in strong shape locally.

### Integration status

The final Skyscanner-facing integration is **not fully complete yet**, because partner-specific contract details are still pending from Skyscanner.

### Safe conclusion

We are no longer at zero.

We already have:

- local isolated API foundation
- local security and validation foundation
- local quote and redirect foundation
- local tracking and logging foundation

What remains is:

- survey submission
- partner-specific final contract alignment
- production rollout later

## Simple Step Order

1. Keep Skyscanner separate from the live platform.
2. Use the local isolated module as the base.
3. Prepare and submit the technical survey.
4. Wait for final contract details from Skyscanner.
5. Adjust the isolated API to the exact partner contract.
6. Prepare staging/testing access.
7. Allowlist IPs before testing.
8. Test with Skyscanner.
9. Implement DV and final reporting when docs arrive.
10. Roll out carefully on production later.

## Final Summary

From our side, the main local coding foundation is mostly done.

The remaining work is mostly:

- survey submission
- final contract alignment
- Skyscanner-specific testing
- production rollout later

For day-to-day progress tracking, use:

- `SKYSCANNER_SEPARATE_IMPLEMENTATION_TASKS.md`
