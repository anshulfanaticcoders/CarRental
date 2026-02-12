# Unified search orchestrator design

## overview

We will replace the current mixed provider search logic with a single search orchestrator that resolves a unified location, calls every provider for that location, normalizes the vehicles into one schema, and returns a single combined list plus provider status. The search will require `unified_location_id` from the dropdown and will not fall back to free text. Default scope is all providers for the chosen location.

This plan targets these issues:

- Inconsistent provider selection and location matching
- Different time rules per provider handled in multiple places
- Wrong location results due to fallback logic
- Pricing and category fields not normalized across providers

## goals

- Always use unified location mapping as the source of truth
- Fetch results from all providers by default for a location
- Normalize results to a consistent schema
- Keep internal vehicles in the same search pipeline
- Return provider status and errors without hiding failures

## non-goals

- No caching or background refresh in this phase
- No change to provider API contracts beyond normalization
- No redesign of the UI outside the search flow

## proposed architecture

Add a `SearchOrchestratorService` that owns provider selection, time normalization, and aggregation. `SearchController` will validate input, enforce `unified_location_id`, and delegate to the orchestrator. Each provider becomes an adapter with a shared interface.

New classes:

- `SearchOrchestratorService` - resolves unified location, builds provider contexts, calls adapters, aggregates results
- `ProviderSearchAdapterInterface` - single `search(ProviderSearchContext $context): SearchResult` method
- `ProviderSearchContext` - provider name, pickup/dropoff ids, dates, times, rentalDays, location metadata
- `SearchResult` - vehicles, optional_extras, provider errors, raw meta

Internal vehicles will be handled by an `InternalSearchAdapter` to keep schema consistency.

## data flow

1. Validate request, require `unified_location_id`, normalize times, compute rentalDays.
2. Load unified location from `public/unified_locations.json`.
3. Build provider contexts from unified location providers and requested dates.
4. For each provider, run adapter search and collect vehicles and extras.
5. Normalize vehicles into the shared schema and return a single list.
6. Return `provider_status` for every adapter call.

Provider dropoff handling:

- If provider supports dropoff list, use selected dropoff id.
- If not supported, dropoff id is forced to pickup id.

## normalized vehicle schema

Required fields for all providers:

- `id`, `source`, `provider_pickup_id`, `provider_return_id`
- `brand`, `model`, `category`, `sipp_code`
- `total_price`, `price_per_day`, `currency`
- `transmission`, `fuel`, `seating_capacity`, `features`
- `latitude`, `longitude`, `full_vehicle_address`
- `benefits`, `products`, `extras`, `insurance_options`

Normalization rules:

- `total_price` is the booking total. `price_per_day` is derived from rentalDays.
- Always use unified location lat/lng unless provider returns precise station data.
- Parse SIPP when present, fallback to provider category names.

## error handling and logging

Each adapter returns a `SearchResult` even on failure. Errors are captured in a provider status array and do not crash the search. We wait for all providers to finish, then return results plus status.

Error patterns:

- Validation errors: return error in `provider_status`, no retry.
- Network timeouts: retry 2 to 3 times with backoff.
- Provider errors: return empty vehicle list and a structured error.

Log each provider call with unified_location_id, pickup_id, dropoff_id, dates, and timing. This will make wrong location and empty inventory issues traceable.

## ui changes

- Search only proceeds if a location is selected from the dropdown.
- Default provider scope is mixed (all providers for the unified location).
- Dropoff selection appears only for providers that support it.
- Search results use the single `vehicles` list returned by the orchestrator.
- Show a small warning banner when any provider returns an error.

## migration plan

1. Add the orchestrator and provider adapter interface.
2. Implement adapters for internal, GreenMotion, OkMobility, Adobe, Wheelsys, Locauto, Renteon, Favrica, XDrive.
3. Move provider time normalization into the orchestrator.
4. Update `SearchController` to require `unified_location_id` and call orchestrator.
5. Update `SearchResults.vue` to use unified list and provider status.
6. Update `SearchBar.vue` to enforce dropdown selection.

## testing plan

- Unit tests for time normalization and dropoff handling.
- Contract tests per provider adapter using recorded responses.
- End to end search tests for mixed provider locations and internal only locations.
- Manual validation of pricing and location display for at least three providers.
