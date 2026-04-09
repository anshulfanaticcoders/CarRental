# Skyscanner Docs Re-Check Checklist

This file is the clean re-check after reviewing the official Skyscanner partner documentation again.

Use this file to understand:

- what is already covered from our side
- what is still left from the docs
- what is blocked until Skyscanner provides account-specific details

## 1. Confirmed From Official Docs

These points are clearly stated in the Skyscanner partner/support documentation:

- Skyscanner needs our API, deeplink flow, and tracking/reporting setup
- Booking happens on our website, not on Skyscanner
- Supported API styles include:
  - REST
  - SOAP
  - GraphQL over HTTP POST
- Their pre-integration process requires:
  - technical survey
  - API docs/endpoints
  - credentials/access
  - logos
  - deeplink information
  - technical contact
- IP allowlisting is required if our side restricts access
- Downstream Visibility (DV) is required before go-live
- Keyword tracking/reporting is required if commercially agreed
- Deeplink must land on the exact/nearest valid offer page on our site
- Deeplink should not land on homepage
- Deeplink page should not mention Skyscanner
- Location mapping requires office/location IDs and airport/non-airport structure
- Quote quality matters for filtering and ranking
- User IP is not mandatory for access, but it improves supply coverage

## 2. Already Covered From Our Side

These items are already prepared/tested locally:

- separate Skyscanner integration structure
- authenticated Skyscanner search API
- quote generation
- quote storage
- quote expiry handling
- signed redirect validation
- redirect tracking correlation
- booking correlation
- report-row generation
- reporting/export foundation
- quote validation/filtering foundation
- local isolated Skyscanner test coverage

### Local flows already verified

- search API works
- API key auth works
- redirect works
- signed redirect works
- expired quote handling works
- booking correlation works
- report export works locally

## 3. Still Left From The Docs

These items are still pending and are required by the official documentation:

- final request contract aligned to Skyscanner’s real required format
- final response contract aligned to Skyscanner’s real required format
- live-environment readiness for Skyscanner testing
- final production deeplink to the actual website results page
- brand/logo package for Skyscanner onboarding
- final location mapping package
- stronger quote completeness for ranking/filtering quality
- user IP decision for supply coverage
- final production monitoring and rollout setup

## 4. Still Blocked By Skyscanner

These items cannot be finished correctly until Skyscanner shares account-specific details:

- final partner-specific API contract
- DV implementation docs and parameters
- final reporting delivery method/format
- final keyword tracking process if enabled commercially
- final partner-side testing/go-live sequence after technical survey review

## 5. Current Practical Verdict

### From our side

We are in a good position locally.

The isolated technical foundation is already strong and the main local API flow has been tested successfully.

### From documentation/compliance side

We are **not fully complete yet**.

Reason:

- some partner requirements are still pending from Skyscanner
- some production-specific items are still not finalized
- some quote-quality/location/deeplink/live-access items still need final readiness work

## 6. What We Should Do Next

### Immediate next step

- prepare the technical survey answers using the work already completed

### Internal next steps

- review live-environment readiness
- review final deeplink destination behavior
- review location mapping completeness
- review quote metadata completeness

### External next steps

- move forward with Skyscanner technical survey
- wait for Skyscanner’s final contract/DV/reporting docs

## 7. Final Summary

### Done

- local isolated Skyscanner API flow
- local security/redirect/tracking flow
- local booking correlation/reporting foundation

### Left

- production readiness
- final contract alignment
- DV and final reporting process

### Blocked by Skyscanner

- account-specific technical details after survey/onboarding progress
