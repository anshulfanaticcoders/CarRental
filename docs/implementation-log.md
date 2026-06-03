# Implementation Log

Concise durable memory for significant completed work.

## Rules
- Log feature work, security reviews, API contract changes, payment/auth/upload work, database/provider changes, release readiness reviews, and multi-repo fixes.
- Skip tiny read-only questions, typo fixes, and temporary exploration.
- Keep entries short. Do not include secrets, tokens, `.env` values, or private credentials.

## Entry Template

```md
### YYYY-MM-DD - Task name
- Scope: repos/files touched.
- Decision: key implementation or review decision.
- Verification: commands/checks run, or why skipped.
- Follow-ups: remaining work, if any.
```

### 2026-05-25 - Awin Google Merchant vehicle feed
- Scope: `CarRental` merchant feed config, snapshot table/model, feed refresh command, XML writer, public feed route, hourly scheduler, and feed tests.
- Decision: generate a UTF-8 Google Merchant RSS XML snapshot from internal vehicles and curated gateway searches; keep last good XML live if refresh fails and avoid marking external items stale when all gateway searches fail.
- Verification: PHP syntax checks passed; `php artisan migrate --pretend` passed; `php artisan route:list --path=feeds` passed; `vendor\bin\pint --test` passed; `php artisan test --filter=MerchantFeed` passed.
- Follow-ups: full `php artisan test` timed out after 5 minutes locally; run in CI/staging after migration.

### 2026-05-25 - Merchant feed clicked vehicle priority
- Scope: `CarRental` merchant feed links and gateway search result ordering.
- Decision: add feed target identifiers to external feed links and move the matching live vehicle to the first result when users arrive from a feed click.
- Verification: `php artisan test --filter=GatewaySearchServiceTest`, `php artisan test --filter=MerchantFeed`, `vendor\bin\pint --test`, and `git diff --check` passed.
- Follow-ups: redeploy and regenerate the feed so XML links include the new feed target query params.

### 2026-05-25 - Merchant feed gross commission pricing
- Scope: `CarRental` merchant feed payable-percentage resolver and item mapping.
- Decision: publish customer-facing gross feed prices by applying the admin Payable Amount percentage from `payable_settings.payment_percentage` to internal and external net daily rates before writing XML price/description.
- Verification: PHP syntax checks passed; `php artisan test --filter=MerchantFeed`, `vendor\bin\pint --test`, and `git diff --check` passed.
- Follow-ups: redeploy and refresh `merchant-feed:refresh awin` before sending the XML.

### 2026-05-25 - Search page SSR fallback
- Scope: `CarRental` Inertia SSR entrypoint.
- Decision: keep the public SSR allowlist, but return a client-only placeholder for non-SSR pages such as `SearchResults` so production SSR cannot turn search links into 500 responses.
- Verification: `npm run build:ssr` passed; direct SSR render smoke for `SearchResults` returned a valid SSR payload with the original component name preserved for client hydration.
- Follow-ups: deploy and restart the SSR worker/application before rechecking live feed links.

### 2026-05-18 - Compatibility-safe security hardening
- Scope: `CarRental` mobile/admin APIs, checkout/date validation, chat uploads, gateway token handling; `vrooem-mobile` API URL resolution and root error fallback; `vrooem-gateway` runtime config validation, provider rate limits, CORS/TLS defaults, and generic provider 500s.
- Decision: kept existing successful mobile/web response shapes and dev compatibility while making production fail closed for missing/default secrets and unsafe supplier TLS.
- Verification: Laravel targeted tests passed; mobile `npm run typecheck` passed; gateway touched Python files passed `py_compile`.
- Follow-ups: temporary regression tests were removed at user request; gateway `pytest`/`ruff` could not run on this machine because the available Python environment has no `uv`, `pytest`, or `ruff` installed.

### 2026-05-19 - Web security hardening pass
- Scope: `CarRental` web/admin/vendor/customer routes and controllers for debug routes, uploads, security headers, rate limits, Stripe webhook config, chat/review/vendor document ownership.
- Decision: added defensive middleware and targeted authorization/upload checks without changing successful customer/vendor/admin workflows.
- Verification: PHP syntax checks passed; Pint passed on touched files; route-list checks confirmed debug-route guards and checkout/form/message throttles.
- Follow-ups: legacy authenticated `BookingController@store` still trusts old client pricing fields and needs a separate compatibility review before changing.

### 2026-05-19 - Booking flow resilience hardening
- Scope: `CarRental` Stripe checkout/session handling, internal vehicle availability holds, booking outcome UI, admin booking rescue filters; `vrooem-gateway` supplier booking idempotency.
- Decision: kept successful search-to-payment behavior intact while adding duplicate-submit protection, pre-payment holds, honest payment outcome redirects, and admin visibility for supplier/refund rescue cases.
- Verification: PHP syntax checks passed; Python compile checks passed; route-list and migration pretend checks passed. Broader Laravel tests and Vue typecheck are blocked by existing test DB/type issues unrelated to the touched files.
- Follow-ups: run full smoke tests against a clean staging database before production deploy, especially checkout success/cancel, expired quotes, supplier pending, and admin rescue filters.

### 2026-05-19 - Stripe checkout smoke and Pay Now display alignment
- Scope: `CarRental` booking extras pricing composable and checkout summary display.
- Decision: aligned frontend Pay Now amount/percentage with backend Stripe charge math for markup vehicles, while preserving percentage-deposit fallback for non-markup totals.
- Verification: Stripe test success, cancel, declined-card, and post-booking availability smoke checks passed; `npm run build` passed with existing warnings.
- Follow-ups: existing build warnings remain for stale Browserslist data, ambiguous Tailwind ease class, unresolved `blogbgimage`, lottie eval, and large chunks.

### 2026-05-19 - Booking outcome theme alignment
- Scope: `CarRental` booking status, cancel, unsuccessful, success, and booking details UI.
- Decision: replaced generic payment outcome screens with a shared Vrooem-themed outcome surface using brand teal, cyan CTA, existing header/footer, booking illustration, and project typography.
- Verification: `npm run build` passed; browser checks passed for quote expired, payment cancelled, payment not completed, and cancel routes with no console errors.
- Follow-ups: existing build warnings remain unchanged.

### 2026-05-19 - Booking outcome illustration assets
- Scope: `CarRental` booking outcome illustrations and shared outcome component wiring.
- Decision: used the user's white-background illustration set as optimized WebP assets under `resources/assets/booking-outcomes`, keeping each file below 100 KB and preserving the existing SVG fallback.
- Verification: `npm run build` passed; browser checks passed for quote expired and payment cancelled routes with no console errors and expected image requests.
- Follow-ups: existing build warnings remain unchanged.

### 2026-05-19 - Booking outcome smoke fixes
- Scope: `CarRental` booking status and unsuccessful payment routes.
- Decision: localized booking outcome CTAs and made the legacy unsuccessful payment page public so failed/cancelled payment users are not sent to login.
- Verification: `npm run build`, `php artisan route:list --path=booking`, and browser smoke checks passed for confirmed, supplier pending, cancelled, incomplete payment, expired quote, refund, supplier failure, invalid session, support review, cancel, and legacy unsuccessful routes.
- Follow-ups: saved-search session storage fallback was verified; direct session storage injection was blocked by browser automation security policy.

### 2026-05-20 - Header currency selector refresh
- Scope: `CarRental` shared currency selector plus guest/auth header currency controls.
- Decision: replaced the text-only header dropdown with a mobile-inspired selector showing real flag images, code, symbol, searchable list, popular currencies, selected state, and drawer-friendly layout.
- Verification: `npm run build` passed; desktop and mobile browser smoke checks passed on the About page with no console errors.
- Follow-ups: existing build warnings remain unchanged.

### 2026-05-20 - Universal currency registry
- Scope: `CarRental` currency APIs/services, checkout normalization, web currency displays/selectors, registry tests; `vrooem-mobile` currency API types, picker metadata, and generated fallback snapshot.
- Decision: made `resources/data/currencies.json` the Laravel-owned source of truth and exposed only rate-ready/selectable currencies through compatible web/mobile API shapes.
- Verification: `php artisan test --filter=Currency`, PHP syntax checks, `npm run build`, and mobile `npm run typecheck` passed.
- Follow-ups: full `php artisan test` did not finish within 6 minutes on this machine; targeted currency checks passed and the broad run should be retried in CI/staging.

### 2026-05-20 - Checkout Pay Now percentage label fix
- Scope: `CarRental` booking extras pricing composable and final checkout summary.
- Decision: show the admin configured payable percentage in the Pay Now label instead of recalculating `payable / grand total`; payment amounts and Stripe payload behavior were not changed.
- Verification: `npm run build` passed.
- Follow-ups: existing build warnings remain unchanged.

### 2026-05-20 - Booking currency/profile and provider search fixes
- Scope: `CarRental` social auth profile currency, guest checkout profile currency, stale provider search location recovery, and dry-run profile currency backfill command.
- Decision: preserve existing profile currencies, fill only missing values from checkout currency/session currency/country/default, and recover old web search URLs by resolving visible search text before provider gateway calls.
- Verification: PHP syntax checks, Pint touched-file check, `php artisan test --filter=Currency`, dry-run `profiles:backfill-currencies`, Tinker resolver checks, and browser search smoke for stale Dubai Airport id passed.
- Follow-ups: run `php artisan profiles:backfill-currencies --commit` only after admin approval or during a maintenance window; no external provider bookings were created.

### 2026-05-20 - Searchbar location quality pass
- Scope: `CarRental` search location normalization and gateway merge flags; `vrooem-gateway` location ranking for exact IATA searches.
- Decision: normalize stale web search ids before rendering results and prefer exact airport-code matches so provider branch aliases do not pollute searchbar results.
- Verification: `DXB` API search returned only Dubai Airport DXB; stale `432` Dubai Airport URL loaded cleanly; data checks found no duplicate unified ids/provider keys and no empty provider locations.
- Follow-ups: gateway refresh-service tests need PyYAML installed locally before the full location refresh test group can run.

### 2026-05-20 - Live unified location refresh
- Scope: `vrooem-gateway` provider-backed `unified_locations.json`, location refresh timeout config, and DB sync timeout guard.
- Decision: deleted stale live JSON, regenerated it from live provider APIs over the Frankfurt VPN, and made provider refresh timeout configurable through `LOCATION_REFRESH_PROVIDER_TIMEOUT_SECONDS`.
- Verification: VPN IP was Frankfurt; refresh completed with 16 providers succeeded, 0 failed, 2,875 provider rows, and 2,022 unified locations; JSON validation found no duplicate unified ids/provider keys, no empty-provider rows, and no missing required ids/names; gateway status and Laravel DXB search matched the fresh data.
- Follow-ups: Docker image rebuild was blocked by Docker Hub metadata timeout, so updated files were copied into running containers for this local run; rebuild images once Docker Hub is reachable.

### 2026-05-20 - DXB/DWC internal search isolation
- Scope: `CarRental` search internal-vehicle filtering and PHPUnit DB safety; `vrooem-gateway` internal adapter location metadata.
- Decision: require a selected unified location's internal provider mapping before showing internal cars, including mixed searches; pass Laravel internal IATA codes into gateway location unification; point PHPUnit at `carrental_testing` and hard-stop tests on non-test DB names.
- Verification: targeted Laravel search regression tests passed; gateway unified location refresh completed with 16 providers succeeded, 0 failed, 2,876 raw locations, and 2,023 unified locations; live smoke showed DXB mixed/internal includes internal cars and DWC mixed/internal includes 0 internal cars.
- Follow-ups: local gateway `uv run` hit a Windows `.venv/lib64` permission issue and the running container lacks pytest/ruff dev deps, so gateway pytest/ruff were not available; runtime adapter check and Python compile passed.

### 2026-05-21 - Strict search execution fallback removal
- Scope: `CarRental` web search location resolution, internal vehicle filtering, gateway search params, and search empty state UI.
- Decision: search execution now requires an exact verified unified/provider location and rejects conflicting airport/country request data; no free-text fallback vehicles are shown or saved. Empty results can show nearby location suggestions as separate searches only.
- Verification: PHP syntax checks passed; Pint touched-file check passed; targeted Laravel location/search tests passed; `npm run build` passed with existing warnings.
- Follow-ups: browser automation could not navigate the local in-app page from the available DevTools tool set, so visual verification should be done manually on DWC/no-result URLs.

### 2026-05-21 - Booking extras premium UX pass
- Scope: `CarRental` booking extras/customize step, checkout summary logistics, package/protection/extra selection UI.
- Decision: normalized pickup/return display into one frontend helper, removed repeated instruction banners, carried existing dropoff details into checkout payload, and polished decision cards without changing pricing/provider behavior.
- Verification: `npm run build` passed; `git diff --check` passed with existing CRLF warnings for touched Vue files.
- Follow-ups: Chrome DevTools MCP was blocked by an already-running profile, so browser screenshot verification still needs a manual/local browser pass.

### 2026-05-21 - Booking customize state persistence
- Scope: `CarRental` search results booking draft, booking extras state rehydration, Locauto protection-code handling.
- Decision: keep selected package, protection/extras, and deposit type in the parent booking draft before checkout, then rehydrate customize when returning from checkout by button, stepper, or browser back.
- Verification: `git diff --check` passed with existing CRLF warnings; `npm run build` passed; browser smoke passed on a Green Motion vehicle with Premium plus two extras across checkout/back-button and checkout/stepper return paths with no console errors.
- Follow-ups: existing build warnings remain unchanged.

### 2026-05-21 - One-way provider dropoff integrity
- Scope: `CarRental` booking customize/checkout location details and gateway search params; `vrooem-gateway` one-way provider dispatch and Surprice station parsing.
- Decision: skip provider one-way searches without a real provider dropoff mapping, filter stale cached one-way vehicles without distinct return data, and stop duplicated pickup office payloads from becoming customer-facing return details.
- Verification: Laravel targeted tests passed for gateway params, gateway search service, and checkout location fallback; JS location/Surprice tests passed; PHP syntax checks passed; `npm run build` passed; browser smoke passed for DXB to Dubai Downtown Surprice exclusion, Green Motion return details, and customize/checkout/back state retention; gateway Python compile passed. Gateway pytest/ruff were blocked by missing local/container dev deps and PyPI timeout during isolated `uv` setup.
- Follow-ups: review whether provider status copy should be surfaced differently for skipped one-way providers; remove `C:\laragon\www\vrooem-gateway\.codex-venv` after explicit cleanup approval.

### 2026-05-21 - Round-trip customize return collapse
- Scope: `CarRental` booking extras map, timeline, summary, and location display helper.
- Decision: treat same pickup/dropoff as one return-to-pickup rental across map and UI, even when provider pickup coordinates differ from search airport coordinates or supplier repeats dropoff details.
- Verification: JS booking location display tests passed; Surprice JS tests passed; `npm run build` passed; browser smoke passed for Surprice DXB round trip showing one pickup marker and "Same as pickup" return state.
- Follow-ups: existing form-field accessibility issues in browser DevTools remain outside this change.

### 2026-05-22 - Trabber partner integration foundation
- Scope: `CarRental` Trabber API config/routes/controllers/services, click attribution table, checkout metadata handoff, CSV export commands, partner docs, and tests.
- Decision: kept Trabber fully separate from Skyscanner; search uses Vrooem internal fleet normalization/availability, redirect stores last-click 90-day attribution, and reports read Trabber-attributed bookings from the last year.
- Verification: `php artisan test --filter=Trabber`, Trabber touched-file Pint check, PHP syntax checks, route-list, and migration pretend passed. Full-repo Pint still blocked by existing unrelated style backlog.
- Follow-ups: do not schedule report delivery until Trabber confirms recipient email, filename patterns, response schema, `clickid`, and logo delivery address.

### 2026-05-25 - Trabber offer-page deeplink fix
- Scope: `CarRental` Trabber redirect, offer cache, dedicated offer route/controller/service, booking adapter, and Trabber tests.
- Decision: Trabber deeplinks now redirect to `/en/trabber/offers/{offerId}` and render `OfferResults` with the clicked vehicle first plus cached alternatives below; click rows are created only when Trabber appends `clickid`.
- Verification: PHP syntax checks passed; `php artisan test --filter=Trabber` passed with 9 tests and 43 assertions; Trabber touched-file Pint check passed; route-list shows `/api/trabber/*` plus `{locale}/trabber/offers/{offerId}`.
- Follow-ups: in manual testing, append `&clickid=TEST-...` to the redirect URL when checking DB attribution rows.

### 2026-05-25 - Trabber gross price alignment
- Scope: `CarRental` Trabber search pricing, offer-page pricing snapshots, booking adapter, and Trabber tests.
- Decision: Trabber API prices now use the admin payable percentage as customer-facing gross price; offer pages display gross pricing while checkout receives net booking context so existing platform markup is not double-applied.
- Verification: PHP syntax checks passed; `php artisan test --filter=Trabber` passed with 9 tests and 56 assertions; Trabber touched-file Pint check passed.
- Follow-ups: keep `PayableSetting.payment_percentage` and `PROVIDER_MARKUP_PERCENT` aligned until platform pricing config is unified.

### 2026-05-26 - Trabber production delivery readiness
- Scope: `CarRental` Trabber daily/monthly report commands, mail attachment delivery, scheduler, environment sample, partner docs, and tests.
- Decision: daily/monthly reports support `--send`, attach CSV files to `reports@trabber.com` when configured, and scheduler entries are guarded by Trabber config.
- Verification: pending.
- Follow-ups: generate production `TRABBER_API_KEY`, send it through a secure channel, and send Vrooem SVG logo to `ofrias@trabber.com`.

### 2026-05-26 - Searchbar luxury date range selector
- Scope: `CarRental` public searchbar date range picker in `resources/js/Components/SearchBar.vue`.
- Decision: kept `@vuepic/vue-datepicker` and existing search query fields, then restyled desktop/mobile date selection with dark brand surfaces, cyan range states, chip-based selected dates, Lucide controls, and clearer duration/time footer.
- Verification: `git diff --check` passed; `npm run build` passed with existing project warnings. `npm run typecheck` is not available in this repo.
- Follow-ups: browser visual verification was blocked by rejected Chrome DevTools permission, so desktop/mobile picker interaction should be checked manually on the local site.

### 2026-05-27 - Admin shell light luxury redesign
- Scope: `CarRental` admin sidebar, admin dashboard layout shell, and shared admin shell CSS.
- Decision: replaced indigo/violet shell accents with Vrooem teal/cyan, kept sidebar/navigation behavior intact, and let `AdminDashboardLayout` honor an optional `title` prop before route-title fallback.
- Verification: `npm run build` passed with existing project warnings; `git diff --check` passed; shell accent grep found no indigo/violet leftovers. `npm run typecheck` is not available in this repo.
- Follow-ups: browser admin shell visual check still needs an authenticated admin session; Chrome redirected `/admin-dashboard` to `/en/login`.

### 2026-05-28 - Locauto extras and deposit policy correction
- Scope: `vrooem-gateway` Locauto priced-equipment/deposit normalization; `CarRental` gateway vehicle transform and Locauto web/search pricing helpers.
- Decision: remove Locauto one-way fee codes from customer-selectable extras, keep them out of booking extras payloads, respect supplier XML charge units/max charge days for extras totals, and normalize Locauto's attached 0.01 deposit entries to customer-facing no-deposit copy.
- Verification: gateway Locauto pytest passed via temp `uv` runner with injected runtime packages; Laravel transformer test passed; JS protection-plan test passed; PHP syntax checks passed; `npm run build` passed with existing project warnings; deposit update rechecked with targeted gateway and Laravel tests.
- Follow-ups: confirm with Locauto whether their `0.01` spreadsheet deposit value should remain stored as raw audit metadata or be changed to exact `0.00` in future supplier condition files.

### 2026-05-28 - Locauto live extras parity
- Scope: `CarRental` booking customize/checkout Locauto protection and extras UI; `vrooem-gateway` Locauto priced-equipment exposure.
- Decision: match Locauto's live services flow with Basic, Smart Cover, and Don't Worry protection choices; show included Safety and Assistance only when Don't Worry is selected; group paid items into Extra optionals and Extra Services; display Locauto extras as per-day prices while preserving booking totals; prevent Locauto protection from being counted twice in checkout totals.
- Verification: live Locauto site checked for protection/extras copy and pricing shape; local browser smoke passed on the MXP round-trip URL; JS protection-plan tests passed; Laravel gateway transformer test passed; Stripe checkout PHP syntax passed; gateway Locauto adapter pytest passed; `npm run build` passed with existing project warnings.
- Follow-ups: confirm with Locauto whether Roadside Plus and Protection Against Injuries should ever be sold separately under Basic or Smart Cover; current UI only surfaces them as included under Don't Worry based on the live reference flow.

### 2026-05-28 - Gateway booking DB fallback
- Scope: `vrooem-gateway` booking orchestration for Laravel-origin supplier bookings.
- Decision: Laravel/MySQL remains the booking source of truth; old gateway booking lookup/persistence is now best-effort only, so its outage cannot block the supplier reservation call or prevent the supplier reference from returning to Laravel.
- Verification: `laravel.log` confirmed booking `BK2026052581` failed after MySQL booking creation because `/api/v1/bookings` returned 500; gateway `/ready` showed Redis connected and the old gateway DB disconnected; Python compile passed in the running gateway container; a container smoke test proved the DB-down path still calls the adapter and returns a supplier booking id. Local/container ruff and pytest remain unavailable because dev tools are missing.
- Follow-ups: retry one clean Locauto test booking after the running gateway reloads this code; old gateway booking persistence is no longer required for Laravel direct bookings.

### 2026-05-28 - Gateway booking persistence removal
- Scope: `vrooem-gateway` booking endpoint, readiness, config, compose files, JSON location refresh, old DB sync scripts/tests, and project agent docs.
- Decision: removed old gateway DB/Alembic/session/model usage completely; supplier bookings now use Redis locking plus adapter calls, while Laravel/MySQL stores booking, payment, customer, extras, and supplier metadata.
- Verification: Locauto test booking `BK2026057297` succeeded with supplier reference `156081433-2026`; Laravel/MySQL rows verified for booking, customer, extras, payment, and provider pricing metadata.
- Follow-ups: run gateway lint/tests and `/ready` after the container reloads the removed DB code.

### 2026-05-28 - Search and booking login return
- Scope: `CarRental` login controllers, auth header links, login page social links, search result favorite login path, and auth return-url tests.
- Decision: only search (`/{locale}/s`) and booking (`/{locale}/booking...`) pages can carry a post-login return URL; all other login sources still redirect customers to profile.
- Verification: PHP syntax checks passed; `AuthReturnUrlTest` passed; `npm run build` passed with existing warnings; login page HTTP smoke returned 200 with `returnTo` prop. Feature auth tests remain blocked by missing local `carrental_testing` database.
- Follow-ups: browser-check an actual logged-out booking/search login on a machine with a known customer credential.

### 2026-05-29 - Search integrity source-of-truth hardening
- Scope: `CarRental` Laravel location search, web/mobile internal vehicle filtering, Skyscanner/Trabber internal resolution, search audit command/tests; `vrooem-gateway` location refresh/status diagnostics.
- Decision: Laravel now verifies internal airport mappings by exact IATA plus country and active searchable vehicles, strips stale gateway internal rows, keeps gateway provider mappings external-only for supplier search, and exposes `search:audit-location` for DXB/DWC style audits.
- Verification: PHP syntax checks passed; touched-file Pint check passed; `LocationSearchServiceTest`, `SearchAuditLocationCommandTest`, `SearchControllerInternalLocationFilterTest`, and `SkyscannerLocationsApiTest` passed; Skyscanner search and Trabber targeted tests passed in combined run; local DXB/DWC audit and HTTP smoke returned expected internal/no-internal behavior. Gateway Python compile passed in the running container.
- Follow-ups: gateway pytest/ruff remain unavailable because local/container dev deps are missing; existing `MerchantFeedTest` and `Skyscanner\CarHireSearchServiceTest` failures need separate cleanup; do not commit generated `vrooem-gateway/data/unified_locations.json`.

### 2026-05-29 - Locauto protection booking payload fix
- Scope: `vrooem-gateway` Locauto reservation extra normalization.
- Decision: selected Locauto protection IDs like `locauto_protection_136` now map back to their OTA equipment codes and are sent in `SpecialEquipPrefs`; duplicate/non-prebookable codes are ignored.
- Verification: patched gateway container Python compile passed; direct adapter capture confirmed booking XML includes GPS `EquipType="19"` and Don't Worry `EquipType="136"`; new test booking `BK2026054248` succeeded with Locauto supplier ref `156182156-2026`; Laravel DB verified provider total `EUR 1067.00`, customer total `EUR 1227.05`, commission `EUR 160.05`, and extras `19`/`136`.
- Follow-ups: gateway pytest/ruff still unavailable in the current local/container dev env because `pytest`/`ruff` are missing.

### 2026-05-29 - Trabber fuel policy and Spanish offer page fix
- Scope: `CarRental` Trabber API fuel policy normalization, localized Trabber offer-page copy, locale translation sharing, and Trabber feature tests.
- Decision: raw supplier fuel codes are normalized before Trabber sees them (`SL` => `Same Level`); `/es/trabber/offers/{offerId}` now receives Spanish offer-page labels instead of hardcoded English copy.
- Verification: PHP syntax checks passed; `php artisan test --filter=Trabber --stop-on-failure` passed with 12 tests and 74 assertions; touched-file Pint check passed; `npm run build` passed with existing project warnings.
- Follow-ups: tell Trabber that `SL` means same fuel level as pickup and ask them to retest an `/es/` deeplink after deployment.

### 2026-05-29 - Trabber missing fuel policy fallback
- Scope: `CarRental` Trabber fuel policy formatter and feature tests.
- Decision: missing supplier/internal fuel policy now falls back to Vrooem's default `Full to Full` label so Trabber search responses do not emit `fuel_policy: null`.
- Verification: PHP syntax checks passed; touched-file Pint check passed; `php artisan test --filter=Trabber --stop-on-failure` passed with 12 tests and 75 assertions.
- Follow-ups: deploy before retesting the production Trabber search endpoint.

### 2026-05-29 - Trabber free eSIM offer exposure
- Scope: `CarRental` Trabber search API, Trabber partner docs, and feature tests.
- Decision: Trabber search offers now expose active search offers like Skyscanner does, including `free_esim_included`, normalized `applied_offers`, and a readable `Free eSIM included` inclusion line.
- Verification: PHP syntax checks passed; touched-file Pint check passed; `php artisan test --filter=Trabber --stop-on-failure` passed with 12 tests and 80 assertions.
- Follow-ups: send updated API docs to Trabber after deployment.

### 2026-05-30 - Trabber rich offer metadata parity
- Scope: `CarRental` Trabber search API, shared gateway vehicle transform, partner docs, and Trabber feature tests.
- Decision: Trabber offers now include Skyscanner-style `vehicle`, `specs`, `pricing`, `policies`, pickup/drop-off office details, security deposit, capacity, mileage allowance, and reliable CDW/TP/TW coverage data. Legacy gateway-shaped provider vehicles now retain normalized specs/pricing/policies/location blocks for partner feeds.
- Verification: PHP syntax checks passed; `php artisan test --filter=Trabber --stop-on-failure` passed with 12 tests and 103 assertions.
- Follow-ups: send Trabber the updated API docs and deploy before asking Oscar to retest live cards.

### 2026-05-30 - Partner offer page redesign implementation
- Scope: `CarRental` shared Skyscanner/Trabber `OfferResults` page and Trabber offer-page metadata pass-through.
- Decision: replaced the old split offer layout with a Vrooem-branded partner hero, vertical selected-vehicle card, compact office/included/alternative sections, sticky desktop price summary, mobile-safe stacking, and conditional free eSIM/inclusion copy from stored partner offer metadata.
- Verification: PHP syntax passed; `tests/Feature/TrabberIntegrationTest.php` passed with 12 tests and 103 assertions; `npm run build` passed with existing project warnings; local Trabber deeplink browser smoke passed on desktop and mobile screenshots.
- Follow-ups: local free eSIM visibility depends on an active free-eSIM offer in the DB; production will show the badge when `free_esim_included` is true in the stored offer payload.

### 2026-05-30 - Offer page related cards and partner smoke checks
- Scope: `resources/js/Pages/OfferResults.vue`.
- Decision: aligned the offer page container to the site width, changed the selected offer and price summary row to flex, moved post-offer content into full-width sections, replaced alternative offers with `CarListingCard`, and limited related cards to 20 with `Load more`.
- Tracking: Trabber related-card booking uses the existing Trabber attribution cookie/session, so the original `clickid` remains on checkout metadata and reports.
- Verification: `npm run build` passed; `./vendor/bin/pint --test --dirty` passed; local HTTP smoke passed for Trabber and Skyscanner redirects with selected vehicle and API total present on the offer page; browser screenshot saved during visual check.
- Test notes: `SkyscannerOfferPageTest` is blocked by local testing DB missing `currency_rates`; `TrabberIntegrationTest` timed out on gateway location sync calls in this environment.

### 2026-05-30 - Partner public supplier branding
- Scope: `CarRental` Skyscanner/Trabber public search payloads, partner offer-page booking contexts, and shared offer-page display.
- Decision: public partner-facing supplier/company display is now always `Vrooem`; underlying provider source/code metadata remains intact for booking routing, provider payloads, reporting, and diagnostics.
- Verification: PHP syntax checks passed; targeted Skyscanner unit tests passed; targeted Trabber external-provider feature test passed; `./vendor/bin/pint --test --dirty` passed; `npm run build` passed with existing project warnings.
- Test notes: existing Skyscanner cache-backed tests with hardcoded April 2026 expiry dates now fail because those dates are in the past on May 30, 2026; this is separate from supplier branding.

### 2026-06-01 - Locauto website XML/T&C alignment
- Scope: `CarRental` Locauto booking extras display, rental policy transform, and transformer tests; `vrooem-gateway` Locauto adapter, conditions YAML, search payload extras metadata, and adapter tests.
- Decision: Locauto deposit now stays as EUR 0.01; refuelling and out-of-hours policy copy includes VAT plus airport/railway fee conditions; cancellation/no-show copy includes group SS; Bau the Way and Tollpass are blocked as counter-only; customer-facing Locauto add-on cards display supplier XML prices instead of Vrooem marked-up totals.
- Verification: PHP syntax checks passed; focused Locauto transformer tests passed; touched-file Pint check passed; `npm run build` passed with existing project warnings; gateway changed-file AST syntax check passed; gateway Locauto adapter pytest passed.
- Test notes: gateway-wide `ruff check app` still reports existing lint debt across unrelated providers and services, so it was not cleaned up in this Locauto compliance pass. Existing dirty `vrooem-gateway/data/unified_locations.json` remains excluded from this task.

### 2026-06-01 - OK Mobility EV/PHEV fuel normalization
- Scope: `vrooem-gateway` OK Mobility adapter and shared SIPP fuel derivation.
- Decision: ambiguous SIPP fuel code `S` no longer defaults to petrol; OK Mobility now derives EV/PHEV fuel from clear model names before falling back to SIPP fuel parsing.
- Verification: focused gateway pytest passed for OK Mobility and SIPP specs, including Trabber's reported BCN examples: Smart ForTwo Electric `MTES`, Fiat 500e `MSES`, Peugeot 2008 EV `SSES`, Nissan Leaf `CMES`, and Peugeot 3008 Plug-in Hybrid `SMPS`.
- Test notes: targeted ruff still reports pre-existing long-line lint debt in `ok_mobility.py` and an existing test line, so no broad formatting cleanup was included.

### 2026-06-01 - Surprice ambiguous fuel hardening
- Scope: `vrooem-gateway` Surprice adapter fuel normalization and adapter tests.
- Decision: Surprice no longer guesses petrol when the SIPP/ACRISS fuel character is missing or ambiguous; clear EV/PHEV model names now set electric/hybrid, while deterministic petrol codes still publish petrol.
- Verification: focused gateway pytest passed for Surprice, OK Mobility, and shared SIPP specs with 36 tests.
- Test notes: targeted ruff still reports pre-existing long-line lint debt in `surprice.py`, so no broad provider formatting cleanup was included.

### 2026-06-01 - Trabber offer page plans and extras parity
- Scope: `CarRental` Trabber offer-page quote assembly, shared gateway vehicle transformer, and partner parity audit.
- Decision: Trabber offer pages now retain provider package variants and customer-selectable extras from `extras_preview`, `extras`, or `options`; shared transformer also derives a fallback SIPP when suppliers omit one so website, Trabber, and Skyscanner do not disagree.
- Verification: PHP syntax checks passed; touched-file Pint check passed; `GatewayVehicleTransformerTest`, focused Trabber provider products/extras regression, and Skyscanner offer booking adapter tests passed. Partner parity audit passed for Adobe, Click2Rent, Easirent, EMR, Favrica, Locauto, OK Mobility, RecordGo, Surprice, and XDrive on local live gateway responses.
- Test notes: Renteon returned no live vehicles for sampled local locations/dates, so it remains no-result rather than pass/fail. Full Trabber suite passed before the final guard tweak with 13 tests and 125 assertions; later full-suite reruns timed out in the local PowerShell session before output.
- Follow-ups: deploy before retesting the live Barcelona Trabber offer link; existing cached production offer IDs need a fresh Trabber search/deeplink to pick up the richer cached payload.

### 2026-06-02 - Free eSIM booking visibility
- Scope: `CarRental` active offer sharing, search result car cards, customize/checkout booking summaries, and checkout payload.
- Decision: active free-eSIM perk offers are shared to search/checkout pages, displayed as compact included badges on car listing cards and booking summaries, and sent in checkout payload as `free_esim_included` plus `perk_offers`; server-side offer resolution remains authoritative for stored booking metadata.
- Verification: PHP syntax check passed for shared Inertia offer props; `git diff --check` passed; local offer service resolved `free_esim_included=true`; `npm run build` passed with existing project warnings.
- Follow-ups: confirm admin/vendor/customer booking detail screens read stored booking offer metadata where needed.

### 2026-06-03 - Worldwide search parity audit tooling
- Scope: `CarRental` gateway service, provider parity audit command, booking details extras normalization; `vrooem-gateway` supplier registry.
- Decision: added repeatable raw gateway vs Laravel transform audit capture under ignored `storage/app/search-parity-audits`; gateway supplier inventory now skips non-supplier support YAML and exposes adapter/config one-way mismatches.
- Verification: PHP syntax checks passed; targeted Pint passed for changed PHP files; `npm run build` passed with existing warnings; gateway registry pytest/Ruff targeted checks passed; full provider audit captured raw/mapped rows at `storage/app/search-parity-audits/20260603_061624_474671_18324`.
- Follow-ups: restart/redeploy gateway so the supplier endpoint reflects the strict registry loader; resolve Docker/host DNS for `nextrent.locautorent.com` before Locauto live API smoke tests.
