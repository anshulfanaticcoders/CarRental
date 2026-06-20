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

### 2026-06-16 - Booking payload contract fixes
- Scope: `CarRental` Stripe gateway reservation payload and unit test; `vrooem-gateway` booking/search cache binding and tests.
- Decision: forward checkout driver licence into gateway `driver.driving_license_number`; cache originating gateway `search_id` with vehicles/searches and reject mismatched booking contexts while allowing legacy cache entries without a search id.
- Verification: PHP syntax checks passed; focused Stripe booking service test file passed; Pint check passed; gateway booking/search pytest passed; gateway ruff check passed with pre-existing `E501` ignored; Python compile passed; diff checks passed.
- Follow-ups: none.

### 2026-06-08 - Newsletter Turnstile protection
- Scope: `CarRental` newsletter subscribe API, footer/homepage/shared newsletter forms, shared Turnstile composable, and focused tests.
- Decision: require Cloudflare Turnstile on public newsletter subscriptions before creating/updating records or sending confirmation emails; add IP and normalized-email throttles; keep double opt-in unchanged.
- Verification: PHP syntax checks passed; `php artisan test tests\Feature\NewsletterSubscriptionTest.php` passed; `node --test tests\js\newsletterTurnstile.test.js` passed; `node --test tests\js\footerNewsletterLayout.test.js` passed; focused Pint check passed; `npm run build` passed; local browser smoke confirmed contact and footer Turnstile widgets render and pre-token submits stay disabled.
- Follow-ups: ensure production has `VITE_TURNSTILE_SITE_KEY` available at build time and `TURNSTILE_SECRET_KEY` available at runtime before deploy.

### 2026-06-08 - Unified location API production hardening
- Scope: `CarRental` public unified-location API route, gateway config fallback, endpoint feature tests.
- Decision: moved `/api/unified-locations` off the heavy search page controller into a dedicated lightweight API controller; preserve the autocomplete JSON contract and return an empty array instead of 500 when gateway/config/logging fails.
- Verification: `php -l` on touched PHP files passed; `php artisan test --filter=UnifiedLocationApiTest` passed; `php artisan test --filter=LocationSearchServiceTest` passed on clean rerun; `php artisan test --filter=SearchControllerInternalLocationFilterTest` passed; focused Pint check passed; local endpoint returned 5 Dubai results.
- Follow-ups: deploy to production, clear Laravel/opcache/route/config caches, verify production gateway env uses `VROOEM_GATEWAY_URL` or legacy `VROOEM_GATEWAY_BASE_URL`, then retest live endpoint.

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

### 2026-06-04 - Dynamic cinematic About page replacement
- Scope: `CarRental` public About page template, dynamic page-section contract, admin page editor initialization, section editor content handling, and frontend About Vue components.
- Decision: replaced the live About presentation with admin-driven cinematic sections while preserving the existing route, Inertia props, translations, and SEO resolver. About template defaults now use semantic fields and Unsplash CDN images; missing template sections are appended as draft admin rows; localized section settings fall back field-by-field to English.
- Verification: PHP syntax checks passed for touched PHP/config files; `npm run build` passed for client and SSR with existing project warnings; browser smoke on `/en/page/about-us` passed at 375, 768, 1024, and 1440 with no document horizontal overflow and responsive About sections rendered. Reduced-motion guards were verified in the component code paths.
- Test notes: repo-wide Pint still reports existing style debt, and targeted Pint on `PageController.php` would reformat pre-existing controller code outside this task. Local DevTools reported one existing shared-shell form-field accessibility issue, not an About Vue runtime error.

### 2026-06-04 - About page hero wrap and motion polish
- Scope: `CarRental` cinematic About Vue components and route-level reveal controller.
- Decision: grouped animated hero letters inside non-breaking word spans so words cannot split mid-line; added clipped/blurred hero image-card entrance animation; changed section/card reveals to IntersectionObserver-driven scroll reveals with reduced-motion and no-JS visible fallbacks.
- Verification: `npm run build` passed for client and SSR with existing project warnings; `git diff --check` passed; Chrome checks passed at 375, 768, 1024, and 1440 with no horizontal overflow, no broken hero words, visible hero card animation CSS, and scroll reveals activating per section.
- Test notes: Chrome console only showed existing Pusher ping/pong logs during the About check.

### 2026-06-04 - About page translated production seeder
- Scope: `CarRental` one-time About page content seeder.
- Decision: `AboutUsCinematicSeeder` now seeds production-ready About section copy for `en`, `fr`, `nl`, `es`, and `ar`, including section titles, body copy, structured cards, alt text, labels, stats, and CTA copy. The seeder remains standalone and is not called by `DatabaseSeeder`.
- Verification: `php -l database\seeders\AboutUsCinematicSeeder.php` passed.
- Follow-ups: run the seeder once on production after a DB backup, confirm the live About page, then remove the one-time seeder from git if desired.

### 2026-06-05 - Localized SEO routing hardening
- Scope: `CarRental` public Vue navigation, Inertia/Ziggy locale state, page alias redirects, hreflang sharing, sitemap static inventory, and localized SEO audit tooling.
- Decision: centralized Vue page URL resolution by matching internal slugs, custom slugs, English slugs, and translated slugs; footer/header links now use canonical `/{locale}/page/{translated_slug}` URLs and `/affiliate/register`. SPA navigation now refreshes Ziggy location, `<html lang>`, `dir`, `#app[data-page]`, `og:locale`, and hreflang. Custom page aliases and legacy `/business/register`/bad Terms/About URLs now 301 to canonical URLs; FR/NL About data repair is idempotent.
- Verification: targeted migration applied locally; JS resolver test passed; focused localized SEO feature tests passed with 6 tests/31 assertions; touched-file Pint passed; `npm run build` passed with existing warnings; browser smoke passed across `en/fr/nl/es/ar` homepages, Spanish Terms, blog list, and single blog. Source checks confirmed localized `lang`, canonical, `og:url`, `og:locale`, and six alternates.
- Follow-ups: regenerate public sitemap files in an environment with production `SITEMAP_BASE_URL`; local generation correctly refused `http://127.0.0.1:8000`. Audit still warns on content-quality slugs, including Spanish `auto-huren-malaga`.

### 2026-06-05 - Localized SEO mutation safety
- Scope: `CarRental` page/blog model observers, SEO redirects, sitemap refresh triggers, custom page aliases, and regression tests.
- Decision: made `Page` accessors safe before translations exist; added page delete/translation delete 410 handling; refreshed sitemaps after page/blog translation saves/deletes; retained 301 behavior for localized slug updates; moved custom page alias routing to the end of the locale group so future admin `custom_slug` values redirect to canonical localized page URLs without stealing concrete routes.
- Verification: local DB mutation smoke created, updated, deleted, and rolled back temporary localized page/blog records; old slugs returned 301, deleted page/blog URLs returned 410, new URLs kept localized canonical/OG/hreflang, and an arbitrary Spanish custom alias returned 301 to its translated canonical page. Focused SEO tests passed with 10 tests/54 assertions; localized SEO audit passed with existing content-quality warnings only; touched-file Pint passed.
- Follow-ups: full-repo Pint still reports unrelated pre-existing style debt.

### 2026-06-05 - SEO report phase 2 cleanup
- Scope: `CarRental` localized page aliases, legacy blog URL routing, blog detail canonicals, auth page SEO, sitemap static inventory, and blog listing payloads.
- Decision: wrong-locale `/page/{slug}` URLs now 301 to translated page slugs; legacy localized/root blog URLs resolve known translation slugs and stale SEO-meta canonicals to current canonical blog URLs; blog detail pages ignore stale admin canonical overrides while preserving title/description/image metadata. Login/register now emit canonical/meta plus `noindex,follow` and are excluded from public sitemaps. Blog listing Inertia payloads now send card-sized data instead of full translated content.
- Verification: focused SEO tests passed with 14 tests/82 assertions; touched-file Pint passed; `npm run build` passed with existing warnings. Local smoke confirmed `/es/page/privacy-policy`, `/es/page/about-us`, `/fr/blog/car-rental-florence-airport-flr`, and the old root Belgian blog URL now end at `200`; Arabic blog canonical is self-canonical with one title/meta-description; `/es/login` has canonical, one meta description, and `noindex,follow`; `/es/us/blog` dropped from ~3.13 MB to ~489 KB.
- Follow-ups: content-quality slug warnings remain admin/content work, not routing code defects.

### 2026-06-08 - Live location search diagnostics and SSR guard
- Scope: `CarRental` unified location API diagnostics and Footer SSR data loading.
- Decision: kept public autocomplete resilient but added production-safe logs for empty lookups, empty searches, gateway error context, and hidden exceptions. Footer no longer fetches relative API URLs during server-side rendering, preventing Node SSR `ERR_INVALID_URL` crashes for localized pages.
- Verification: PHP syntax checks passed for touched backend files; `UnifiedLocationApiTest` passed with 4 tests/8 assertions; Footer SSR safety node test passed; `npm run build` passed for client and SSR with existing project warnings.
- Follow-ups: deploy, run the live `/api/unified-locations?search_term=dubai&limit=1` curl, then inspect `storage/logs/laravel.log` for `UnifiedLocations:` or `VrooemGateway:` lines to identify the remaining live HTTP/PHP-FPM mismatch if the response is still empty.

### 2026-06-08 - Admin dark dashboard demo
- Scope: `CarRental` static admin design preview only.
- Decision: added a standalone Deep Vrooem dark admin prototype at `public/admin-dark-dashboard-demo.html` with dashboard and bookings screens, static data, embedded CSS/SVG/JS, and no Laravel/Vue/backend changes.
- Verification: static checks passed for ASCII, no external URLs/CDNs, no `transition: all`, no gradient text, and no colored side-stripe pattern. Browser smoke passed at 1440, 1024, 768, and 375 with no horizontal overflow, desktop sidebar collapse, screen switching, notification/profile dropdowns, booking status filtering, row detail updates, mobile drawer navigation, reduced-motion CSS, and no console errors.
- Follow-ups: review demo, then convert approved direction into real admin Vue layout/components in a separate implementation task.

### 2026-06-08 - Admin dark theme shell port
- Scope: `CarRental` real admin Vue layout/sidebar styling and admin-scoped global CSS.
- Decision: ported the approved Deep Vrooem dark demo direction into `AdminDashboardLayout`, `AdminSiderBar`, and admin-only CSS overrides for cards, tabs, tables, forms, badges, dropdowns, popovers, hover/focus/active/disabled states, and reduced-motion behavior. No admin routes, backend logic, data fetching, links, or table actions were changed.
- Verification: `npm run build` passed for client and SSR with existing project warnings. Browser navigation to `/admin-dashboard` on local port 8000 reached the app but redirected to `/en/login`, so logged-in visual review is still needed in an authenticated admin session.
- Follow-ups: review the logged-in admin dashboard and dense admin pages at desktop/tablet/mobile widths, then tune any page-specific light utility leftovers if they appear.

### 2026-06-08 - Admin-only table and feedback polish
- Scope: `CarRental` admin layout runtime styling hooks and admin-scoped CSS only.
- Decision: kept shared Shadcn/customer/vendor UI primitives untouched; added admin-only table labeling, compact responsive wide-table card mode, route loading feedback, page arrival motion, dark dialogs, dark toasts, form label polish, and stronger admin table overflow handling.
- Verification: `git diff --check` passed; `npm run build` passed for client and SSR with existing project warnings. Browser navigation to `/admin-dashboard` still redirects to `/en/login` without an authenticated admin session.
- Follow-ups: perform logged-in admin visual QA across dashboard, bookings, users, vendors, vehicles, payments, settings, and reports at desktop/tablet/mobile widths.

### 2026-06-08 - Admin confirmation and compact table pass
- Scope: `CarRental` admin-only confirmation dialog wrapper, admin delete/status flows, admin layout table tagging, admin sidebar typing, and admin-scoped CSS.
- Decision: added `AdminConfirmDialog` under `AdminDashboardPages/Shared`; replaced remaining native admin `confirm()` flows found in Offers, Schema, Header/Footer scripts, Radius, Pages, Newsletter campaigns, Vendors, Users, Vehicles, Blogs, SEO, Media, Newsletter subscribers, Plans, and Vehicle Addons while preserving existing routes and handlers. Added dark-safe cancel styling for both the new wrapper and older raw admin `AlertDialog` usage. Extended admin table tagging to Shadcn `overflow-auto` wrappers so compact table styling reaches more A-Z admin pages. Removed table/card hover lift in the admin dark shell, restored safe horizontal overflow for unconverted tables, and converted the densest admin rows in Vendors, Bookings, Users, Vehicles, Payments, External Bookings, and Vehicle Categories to summary-first rows with expandable detail panels.
- Verification: `git diff --check` passed; admin page search found no native `confirm()` calls; direct `vue-tsc --noEmit` no longer reports the admin sidebar route type error but still fails on existing non-admin Skyscanner/OfferResults type debt; `npm run build` passed after the confirmation pass and again after the compact table pass with existing project warnings.
- Follow-ups: complete final logged-in browser visual QA at desktop/tablet/mobile widths when the user is ready for browser testing.

### 2026-06-08 - Admin consistency correction pass
- Scope: `CarRental` admin layout runtime UI helpers, admin-scoped CSS, and compact admin table detail toggles.
- Decision: made the admin header search a real command palette with keyboard shortcut and route jumps; added a dark route loading overlay to mask white page-transition flashes. Normalized admin-only status badges and common action buttons/icons without touching shared Shadcn primitives. Forced teleported admin dialogs/forms into dark readable controls, cleaned nested table borders, made detail rows left-aligned with label/value scan rows, and changed compact row details to single-open behavior across Vendors, Bookings, Users, Vehicles, Payments, External Bookings, and Vehicle Categories.
- Verification: `git diff --check` passed with existing CRLF warnings on three admin Vue files; searches found no native admin `confirm()` calls and no compact detail toggles appending multiple rows; `npm run build` passed with existing project warnings.
- Follow-ups: finish logged-in browser QA for badges, dialogs, details, search, and route loader on representative admin pages at desktop/tablet/mobile widths.

### 2026-06-09 - Admin loader and sidebar route QA
- Scope: `CarRental` admin route loader, sidebar submenu layout/click handling, header search route navigation, and Lottie loader asset.
- Decision: moved admin route loading overlay to the root Inertia app with lazy Lottie loading, timer-delayed admin visits so the loader paints before page switches, restored the sidebar submenu button contract, forced open submenu content to reserve height, aligned collapsed sidebar/search route jumps with the same loader path, and made the loading layer blur/dim the page while blocking background clicks. No backend routes, database queries, API calls, form handlers, or permissions were changed.
- Verification: `git diff --check` passed; `npm run build` passed with existing project warnings. Browser QA in an authenticated admin session verified the loader appears with the car Lottie and `Loading...`, applies blur with `pointer-events: auto`, then clears after dashboard/profile/vendors/vehicles/bookings/affiliates route switches, header search result navigation, collapsed sidebar icon navigation, and mobile 375px sidebar route navigation.
- Follow-ups: existing non-loader Vue warnings around dialog close listeners remain outside this pass; continue page-by-page visual polish separately if needed.

### 2026-06-10 - Skyscanner API and offer-page parity fixes
- Scope: `CarRental` Skyscanner quote API serialization, quote snapshots, provider customer pricing, offer-page checkout currency display, partner supplier labels, and Surprice coverage amount labels.
- Decision: public Skyscanner quotes now return Vrooem as supplier, gross customer-facing provider prices, pickup and drop-off location details, insurance/coverage data, and hidden net pricing for checkout so the 15% service price is not double-counted. Partner offer/customization pages preserve the quote currency and Vrooem supplier label through package, extras, summary, and pay-now/pay-arrival rows.
- Verification: PHP syntax checks passed for touched Skyscanner services; targeted Pint passed; `npm run build` passed with existing warnings; browser smoke via headless Chrome verified local Skyscanner external quote API-to-offer-to-customization parity for price, currency, Vrooem supplier display, pickup/drop-off, coverages, deposit/excess, extras, package, and summary.
- Test notes: focused PHPUnit could not run after the local `carrental_testing` database became unavailable; before that environment issue, the focused Skyscanner feature/unit tests passed earlier in the session.

### 2026-06-11 - Surprice reservation failure hardening
- Scope: `CarRental` checkout price cache, Surprice checkout metadata, gateway reservation failure logging, gateway vehicle transform, and `vrooem-gateway` Surprice booking adapter.
- Decision: persisted booking-critical supplier context in the verified price cache, restored Surprice reservation identifiers before Stripe checkout metadata is stored, blocked checkout when a Surprice quote is missing mandatory reservation context, and preserved sanitized gateway/supplier failure payloads on failed reservations. Gateway now fails Surprice bookings with explicit missing-context or missing-reference errors instead of returning an empty supplier booking id with no useful reason.
- Verification: PHP syntax checks passed for touched Laravel files; touched-file Pint passed; gateway Surprice adapter `py_compile` passed; booking-critical Laravel unit tests passed with 37 tests/534 assertions.
- Test notes: gateway `ruff` and `pytest` were unavailable in the local Python environment; full `PriceVerificationServiceTest` still needs the local `carrental_testing` database restored.

### 2026-06-11 - Booking failure zero-tolerance hardening
- Scope: `CarRental` provider checkout validation, Stripe-to-gateway reservation handling, internal provider API booking lock, legacy booking endpoint, gateway booking schema, gateway booking response normalization, and gateway booking cache TTL.
- Decision: added a central `ProviderBookingContract` so external Stripe sessions are blocked before payment when gateway/search/provider context is missing. Removed unsafe gateway vehicle id fallback, require confirmed gateway status plus supplier reference, store booking-safe provider context in the verified price cache, redact PII from gateway POST logs, and block the old `POST /booking` path with a 410 response. Gateway booking requests now reject blank vehicle/search ids and invalid extra quantities, normalize adapter responses, and expose structured failure context instead of accepting empty confirmations.
- Verification: PHP syntax checks passed for touched Laravel files; focused Pint passed for the new contract, touched tests, and hardened service/controller files; `ProviderBookingContractTest` passed with 3 tests/8 assertions; gateway `ruff check`, gateway `ruff format --check`, and `tests/test_booking_service.py` passed with 3 tests.
- Test notes: DB-backed `StripeBookingServiceAccountingTest --filter gateway` is blocked because local MySQL database `carrental_testing` does not exist. Separate Pint on legacy `BookingController.php` still reports pre-existing formatter debt; the new 410 guard is syntax-clean. No database creation or destructive action was run.

### 2026-06-11 - Manual refund policy correction
- Scope: `CarRental` Stripe booking failure handling and provider reservation retry failure job.
- Decision: removed automated Stripe refund creation from failed reservation and inventory-race paths. Failed paid bookings still move to `refund_pending`/rescue visibility, but the app now logs manual refund/reconciliation requirements instead of calling Stripe Refund APIs.
- Verification: PHP syntax checks passed for `StripeBookingService` and `TriggerProviderReservationJob`; targeted Pint passed for both files; grep confirmed no remaining `Stripe\Refund`/`Refund::create`/auto-refund call patterns in PHP app/tests.
- Follow-ups: handle all customer refunds and provider payout corrections manually in Stripe/admin workflows.

### 2026-06-11 - Provider no-reference cancellation outcome
- Scope: `CarRental` external provider reservation retry failure and Stripe booking outcome routing.
- Decision: if provider reservation retries exhaust without a supplier reference, the saved booking now becomes `cancelled` with `payment_status = payment_cancelled` and cancellation reason `Payment cancelled: supplier did not confirm the reservation or return a provider reference.` Manual refund/reconciliation is still logged, but no Stripe refund API is called.
- Verification: PHP syntax checks passed for `TriggerProviderReservationJob` and `StripeCheckoutController`; targeted Pint passed for both files; grep confirmed no automated Stripe refund call patterns in PHP app/tests.

### 2026-06-11 - Surprice FDW and extras booking parity
- Scope: `CarRental` Surprice booking extras adapter, Stripe checkout Surprice package context, provider booking contract, Stripe-to-gateway reservation payload, and `vrooem-gateway` Surprice booking adapter.
- Decision: Surprice FDW/full coverage is now exposed as a package when supplier data contains FDW totals, checkout uses FDW total/deposit/excess and requires FDW vendor rate context before payment, and gateway reservation switches to the FDW rate when selected. Selected Surprice extras are now attached to the Surprice reservation payload instead of only being built locally.
- Verification: Surprice JS pricing tests passed with 11 tests; PHP syntax checks passed for touched checkout/contract/booking service files; gateway Surprice adapter tests passed with 10 tests; `npm run build` passed with existing project warnings.
- Follow-ups: run one Surprice sandbox/live test booking with a selected extra before promising Surprice extras are provider-confirmed in production.

### 2026-06-12 - Booking audit and provider failure reporting
- Scope: `CarRental` admin booking list trip dates, provider checkout contract aliases, external cancellation supplier mapping, live-provider smoke script, and `vrooem-gateway` booking failure normalization.
- Decision: admin bookings now expose trip from/to date and time without touching price columns. Provider checkout validation now canonicalizes real frontend aliases such as `okmobility`, `u_save`, and `sicilybycar` before enforcing provider-specific booking context. Customer/admin/mobile cancellation fallback IDs now use gateway-canonical `usave` and `recordgo`. Gateway booking adapters now return structured failed booking responses with supplier, vehicle, search, exception type, and sanitized failure reason instead of losing provider context behind a generic 502. AdobeCar and Surprice supplier one-way metadata now matches adapter behavior.
- Verification: PHP syntax checks passed for touched Laravel files and the smoke script; Pint passed for touched Laravel files; `ProviderBookingContractTest` passed with 4 tests/12 assertions; admin/customer cancellation tests passed with 6 tests/22 assertions; gateway `ruff check` and `ruff format --check` passed on touched booking files; gateway booking service tests passed with 4 tests; `npm run build` passed with existing project warnings.
- Follow-ups: live provider booking/cancellation smoke script was generated but not executed. It requires fresh gateway search IDs/vehicle IDs and explicit `--confirm-live=YES` because it creates real supplier reservations before cancelling them.

### 2026-06-12 - Provider frontend parity and claims audit
- Scope: `CarRental` provider vehicle transform, search card/customize/checkout claim rendering, extras quantity validation, mobile booking details, parity audit command, and `vrooem-gateway` cancellation/spec/extra serialization.
- Decision: removed default/invented cancellation and non-refundable claims from customer-facing search/customize/booking summaries, changed package cards to show a neutral missing-benefits state, preserved explicit provider fuel/transmission specs over SIPP fallback, exposed supplier extra `max_quantity`, blocked checkout quantities above supplier limits, and made OK Mobility missing cancellation data remain unknown instead of defaulting to free cancellation.
- Verification: PHP syntax checks passed for touched Laravel files; `PriceVerificationServiceTest` passed with 6 tests/19 assertions; JS parity tests passed with 10 tests; `npm run build` passed with existing project warnings; gateway syntax compile passed; gateway vehicle schema/search payload/OK Mobility targeted tests passed with 9 tests. Local `search:audit-provider-parity --candidate-limit=1 --vehicles-per-provider=1` completed for all enabled providers and wrote local captures under `storage/app/search-parity-audits/20260612_065803_214538_52416`.
- Follow-ups: gateway global Ruff still reports pre-existing style issues across many adapters; targeted Ruff on touched gateway files is blocked by those existing line-length/import warnings. Awin `MerchantFeedTest` has two pre-existing failures around unavailable internal vehicles and preserving external items after gateway failure; fix separately before relying on merchant-feed partner QA as green.

### 2026-06-12 - Provider parity audit follow-up cleanup
- Scope: `CarRental` Awin merchant feed refresh clock handling and targeted `vrooem-gateway` Ruff cleanup for audit-touched files.
- Decision: changed merchant feed refresh to use Laravel's app clock via `now()` before converting to `CarbonImmutable`, so tests and scheduled runs share the same time source. Applied scoped Ruff fixes/formatting only to gateway files touched by the provider parity audit.
- Verification: `MerchantFeedTest` now passes with 6 tests/44 assertions; `ProviderBookingContractTest`, `TrabberIntegrationTest`, and `MerchantFeedTest` pass together with 23 tests/181 assertions; `PriceVerificationServiceTest` passes with 6 tests/19 assertions; JS parity tests pass with 10 tests; targeted gateway `ruff check`, `ruff format --check`, and vehicle schema/search payload/OK Mobility tests pass with 9 tests.
- Follow-ups: full gateway `ruff check app --statistics` still reports 306 historical lint issues in unrelated gateway files, so only audit-touched gateway files are clean in this pass.

### 2026-06-12 - Public location response hardening
- Scope: `CarRental` public unified location API, web search bar pickup/drop-off selection, and unified location API tests.
- Decision: `/api/unified-locations` now returns a public-safe location shape and hides provider pickup IDs, dropoff mappings, and extended provider codes. Web search now uses `unified_location_id` plus public drop-off candidates and clears legacy provider pickup IDs for mixed searches; Laravel still resolves full provider mappings server-side before calling the gateway.
- Verification: PHP syntax checks passed for touched controller/test; `UnifiedLocationApiTest` passed with 5 tests/14 assertions; `npm run build` passed with existing Vite/browserlist/chunk warnings.

### 2026-06-12 - Search result payload hardening
- Scope: `CarRental` gateway search Inertia payload, checkout provider-context restore, and search-service tests.
- Decision: search results now cache the full provider booking context server-side before returning a public-safe vehicle payload to Vue. Public search vehicles hide raw supplier payloads, quote IDs, provider pickup/drop-off IDs, booking tokens, rate/list IDs, RecordGo product booking IDs, and provider net/VAT/gross internals while keeping display fields, packages, extras, location labels, and price hashes. Checkout now resolves RecordGo selected product after server-side price verification restores the cached provider context, so booking validation remains server-trusted.
- Verification: PHP syntax checks passed for touched files; `GatewaySearchServiceTest` passed with 12 tests/77 assertions; `ProviderBookingContractTest` passed with 4 tests/12 assertions; `PriceVerificationServiceTest` passed with 6 tests/19 assertions; `StripeCheckoutControllerRenteonVariantSelectionTest` passed with 1 test/11 assertions; Pint passed for touched PHP files.

### 2026-06-13 - Remaining provider parity and Wheelsys location guard
- Scope: `CarRental` provider parity audit plus `vrooem-gateway` location refresh country-scope filtering.
- Decision: browser-checked Easirent and Favrica customize flows against raw/transformed source data. Easirent renders a neutral pay-at-pickup package, CDW inclusion, supplier address, and pickup instructions without unsupported cancellation claims. Favrica renders LCF/SCDW under protection plans, Baby Seat/Additional Driver under additional services, and summary pricing matches provider-local amounts plus Vrooem customer pricing. Gateway location refresh now filters provider station rows outside each supplier YAML `countries` scope, preventing stale Wheelsys US/MCO rows from being regenerated for a Greece-only supplier.
- Verification: `php artisan search:audit-provider-parity` rerun for Click2Rent, Renteon, and Wheelsys; Click2Rent/Renteon returned clean no-vehicle results for sampled locations. Gateway `python -m unittest tests.test_location_json_refresh_service` passed with 10 tests, and `python -m py_compile` passed for the changed gateway service/test files.
- Follow-ups: run the gateway location refresh after deploying/reloading this change so generated `data/unified_locations.json` drops the existing stale Wheelsys MCO row. Do not commit generated unified-location JSON.

### 2026-06-13 - Newsletter Turnstile invisible-until-needed UX
- Scope: `CarRental` newsletter Turnstile composable and public newsletter forms.
- Decision: kept server-side newsletter Turnstile validation in place, but switched newsletter widgets to Cloudflare's official `appearance: interaction-only` and `execution: execute` flow. Newsletter forms now run Turnstile on submit and send the returned token, so normal visitors do not see a footer checkbox while suspicious traffic can still be challenged.
- Verification: `node --test tests\js\newsletterTurnstile.test.js`, `node --test tests\js\footerNewsletterLayout.test.js`, `git diff --check`, and `npm run build` passed. Chrome DevTools browser smoke was blocked by a locked local Chrome profile.

### 2026-06-13 - Easirent source-backed policy parity
- Scope: `vrooem-gateway` Easirent adapter/reference data and `CarRental` gateway vehicle transform.
- Decision: Easirent US offers now expose supplier-document-backed deposit/excess, payment/deposit card rules, fuel/mileage, pickup, cancellation/no-show, and counter-only service terms while keeping API quote prices dynamic. Removed unsupported free-cancellation wording for Easirent and passed generic provider terms, driver requirements, rental policies, and package benefits through Laravel so the customize page can render source-backed data for any provider.
- Verification: PHP syntax checks passed for touched transformer/test files; Pint passed for touched Laravel files; `GatewayVehicleTransformerTest --filter=Easirent` passed; gateway targeted `ruff check`, `ruff format --check`, and Easirent/search-vehicle payload tests passed with 9 tests.
- Follow-ups: local browser still showed the old Easirent payload because the active gateway Docker image does not mount app code. Docker rebuild was attempted but blocked by Docker Hub metadata timeout for `python:3.11-slim`; rebuild/redeploy gateway before browser QA.

### 2026-06-16 - RecordGo checkout payload cleanup
- Scope: `CarRental` RecordGo extras adapter, checkout price verification, Stripe checkout naming fallback, and related unit/JS tests.
- Decision: RecordGo protection plans now use real supplier complement IDs, checkout resolves selected RecordGo complements from the selected package's server-trusted product context, and Stripe line item names fall back to display/SIPP/provider IDs when brand/model are empty.
- Verification: browser RecordGo ATH search returned 11 vehicles; selected package, full coverage, and additional driver reached Stripe Checkout without payment. Targeted PHPUnit, JS adapter test, Pint, gateway booking/search tests, and `npm run build` passed with existing build warnings.
- Follow-ups: Renteon and Locauto live clean status still depends on getting provider vehicles; retry with VPN/IP state changed if searches return no results.

### 2026-06-17 - Gateway reservation cache-miss fallback
- Scope: `CarRental` checkout price cache, Stripe checkout payload storage, Stripe-to-gateway reservation payload, and `vrooem-gateway` booking schema/service/tests.
- Decision: checkout now stores a server-verified gateway vehicle snapshot with the Stripe payload and forwards it to the gateway reservation request. Gateway still uses Redis cached vehicles first, but cache hits must match the checkout search ID. Expired or ambiguous cache entries can only continue through Laravel's guarded snapshot after matching vehicle/search IDs and checking snapshot expiry.
- Verification: PHP syntax checks passed for touched Laravel files; Pint passed for touched Laravel files; `PriceVerificationServiceTest` and `StripeBookingServiceAccountingTest` passed with 21 tests/103 assertions; focused gateway `ruff check`, `ruff format --check`, and `tests/test_booking_service.py` passed with 10 tests.
- Follow-ups: no live payment, Stripe redirect continuation, or provider reservation API call was run for this change.

### 2026-06-17 - Gateway Redis memory cap
- Scope: `vrooem-gateway` Docker Compose Redis runtime configuration.
- Decision: Redis now starts with `--maxmemory 256mb --maxmemory-policy allkeys-lru`, capping cache memory and evicting least-recently-used temporary keys before Redis can grow without bound.
- Verification: `docker compose config` passed; Redis/gateway were restarted; live Redis config reports `maxmemory=268435456` and `maxmemory-policy=allkeys-lru`; gateway `/health` is healthy and `/ready` reports Redis/MySQL connected.

### 2026-06-17 - Gateway unified location refresh for remaining suppliers
- Scope: `vrooem-gateway` generated `data/unified_locations.json` and live provider checkout audit for the remaining suppliers.
- Decision: refreshed the gateway unified-location JSON from live supplier location APIs after VPN/IP access was restored. The refreshed file now includes GreenMotion, USave, Sicily by Car, and Wheelsys mappings.
- Verification: `python -m app.scripts.refresh_locations_json` completed with 16/16 providers succeeded, 2,047 unified locations, and zero provider failures. Refreshed mapping counts: GreenMotion 511, USave 260, Sicily by Car 15, Wheelsys 1. Gateway was restarted and `/health` returned healthy. Live-safe checkout payload audit passed for GreenMotion, USave, and Sicily by Car with 3/3 clean and zero issues; no Stripe payment or provider reservation was made.
- Follow-ups: Wheelsys can now be found in unified locations, but its only station returned supplier stop-sale responses with zero rates across tested windows, so no real Wheelsys checkout payload can be audited until Wheelsys returns sellable inventory.

### 2026-06-17 - Skyscanner offer data parity
- Scope: `CarRental` Skyscanner offer adapter/page/API cache headers and `vrooem-gateway` canonical search vehicle payload contract.
- Decision: Skyscanner offer booking contexts now use the stored quote as the canonical customer-display source for public pricing, products, extras, insurance options, coverages, and policies, with provider payload data only as fallback when quote fields are missing. OfferResults renders the same normalized package/protection/coverage/extra facts that continue into BookingExtrasStep. Skyscanner JSON search and redirect responses now send no-store cache headers.
- Verification: PHP syntax checks passed for touched Laravel files; targeted Skyscanner PHPUnit suite passed with 22 tests/189 assertions; `node --test tests\js\defaultAdapter.test.js` passed with 6 tests; `npm run build` passed with existing Vite warnings. Gateway touched-file `ruff check`/`ruff format --check` passed; gateway payload-builder/contract-file tests passed with 9 tests.
- Follow-ups: gateway `tests/test_search_vehicle_contract_fixtures.py` could not run in the local Codex Python env because `jsonschema` is not installed. Full gateway Ruff still reports historical unrelated lint/format issues outside touched files.

### 2026-06-17 - Skyscanner offer parity audit hardening
- Scope: `CarRental` Skyscanner offer Inertia props, offer booking adapter, booking extras pricing, and parity tests.
- Decision: offer pages now send display-safe quote props using the public Skyscanner serializer plus quote-level search/products/extras only, so raw provider `booking_context` cannot leak stale supplier values into Vue. Offer booking contexts now overwrite provider-payload products, extras, and policies with quote-canonical values. Booking extras no longer applies provider markup to partner quote vehicles because Skyscanner offer prices are already customer-facing.
- Verification: browser checked a seeded Skyscanner quote through offer page -> booking extras: offer/extras showed Basic `€312.45`, Premium `€345.45`, GPS `€24.00`, Child Seat `€21.00`, and no stale provider labels or inflated prices. `SkyscannerOfferPageTest`, `CarHireOfferBookingAdapterTest`, and `CarHireRedirectControllerTest` passed with 17 tests/228 assertions; `SkyscannerSearchApiTest` passed with 6 tests/55 assertions; JS pricing/default-adapter tests passed with 10 tests; Pint passed on touched PHP; `npm run build` passed with existing Vite warnings.
- Follow-ups: Skyscanner public JSON still intentionally does not expose `products` or `extras_preview`; if those are added to the partner API later, add them to the public serializer and parity tests in the same change.

### 2026-06-18 - Supplier package reservation handoff
- Scope: `CarRental` Stripe-to-gateway reservation payload and `vrooem-gateway` booking request/service.
- Decision: browser-checked GreenMotion Dubai Airport checkout with Premium Plus plus Additional Driver, RecordGo Athens Airport checkout with Just Go, and U-Save Dubai Airport checkout with Premium Plus plus Additional Driver. Frontend Pay Now payloads were correct, but the deferred gateway reservation payload did not carry the selected package. Laravel now forwards the selected package, and the gateway applies the matching cached supplier product before calling the provider adapter, so supplier reservations use the selected product total and, where present, selected provider product IDs/complements instead of cached defaults. U-Save returned no quote id in live vehicle context, so the inherited GreenMotion reservation adapter now allows a blank quote id instead of failing before provider submission.
- Verification: no card/payment entered and no provider reservation was made. GreenMotion payload included `package=PMP`, supplier vehicle total `109.96`, extra code `2`. RecordGo payload included `package=RG_7_1578`, supplier vehicle total `187.14`, product ID/version data. U-Save payload included `package=PMP`, supplier vehicle total `103.49`, total `151.69`, extra code `2`, pickup/dropoff provider id `59610`, and Stripe sandbox amount `19.79`. PHP syntax passed for `StripeBookingService.php`; gateway `tests/test_usave_adapter.py`, `tests/test_booking_service.py`, and `tests/test_location_json_refresh_service.py` passed with 25 tests; touched gateway test/service files passed Ruff.
- Follow-ups: full gateway Ruff still has unrelated historical lint issues outside the touched files. Live full location refresh could not be accepted while Renteon locations returned `401`; committed location JSON baseline still contains all 16 providers including U-Save and Renteon.

### 2026-06-18 - Skyscanner offer checkout verification context
- Scope: `CarRental` Skyscanner offer page checkout handoff, offer booking adapter, and Skyscanner offer regression tests.
- Decision: offer pages now attach a `skyscanner_offer_*` price-verification session, `price_hash`, gateway search ID, and gateway vehicle ID to the booking context before rendering checkout. OfferResults passes those IDs to BookingExtrasStep/BookingCheckoutStep, so Pay Now sends the same server-verified context as normal search checkout instead of a null search session.
- Verification: PHP syntax checks passed for touched Skyscanner controller/adapter/tests; `php artisan test tests\Feature\SkyscannerOfferPageTest.php tests\Unit\Skyscanner\CarHireOfferBookingAdapterTest.php` passed with 9 tests/207 assertions; `npm run build` passed with existing Vite/Browserslist/lottie/chunk warnings.
- Follow-ups: no Stripe payment or provider reservation was made during this fix.

### 2026-06-18 - Trabber offer checkout verification context
- Scope: `CarRental` Trabber gateway offer search, offer page checkout handoff, offer booking adapter, and Trabber integration tests.
- Decision: Trabber external offers now carry gateway search/vehicle IDs from gateway search results through the stored offer payload, normalized quote, booking context, and shared OfferResults checkout props. Trabber offer pages now attach a `trabber_offer_*` price-verification session and `price_hash` before rendering checkout, matching the Skyscanner offer-page fix.
- Verification: PHP syntax checks passed for touched Trabber controller/services/test; Pint passed for touched Trabber PHP files; `php artisan test tests\Feature\TrabberIntegrationTest.php` passed with 13 tests/143 assertions; the focused external provider offer-page test passed with 1 test/40 assertions after formatting.
- Follow-ups: no Stripe payment or provider reservation was made during this fix.

### 2026-06-18 - Shared offer page preview-aligned redesign
- Scope: `CarRental` shared Skyscanner/Trabber offer results page.
- Decision: removed the rejected dark header from the results step and aligned the page with the approved preview structure: main vehicle card, pickup/return office row, package/extras/policy panels, and right-side sticky checkout summary. Existing customize, extras, checkout, and Pay Now data flow stays unchanged.
- Verification: `npm run build` passed with existing Vite/Browserslist/lottie/chunk warnings. Browser smoke verified desktop render, vehicle image load, no desktop/mobile horizontal overflow, no clipped mobile target text, no large lower-page gap, and `Continue to checkout` still opens the existing customize step.

### 2026-06-18 - Shared offer page detail-card polish
- Scope: `CarRental` shared Skyscanner/Trabber offer results page.
- Decision: replaced cramped text-only vehicle facts with icon-led two-column spec cards, added icons to checkout confidence chips, and reworked checkout summary rows so long add-on descriptions do not crowd prices.
- Verification: `npm run build` passed with existing Vite/Browserslist/lottie/chunk warnings. Browser smoke verified desktop/mobile spec cards have icons, no visible text overflow, no horizontal overflow, and the rejected dark header remains hidden.

### 2026-06-18 - Shared offer page customer visual balance
- Scope: `CarRental` shared Skyscanner/Trabber offer results page.
- Decision: tightened the first-view customer hierarchy with smaller vehicle titles, compact mobile expired alert, stronger vehicle media balance, cleaner trip/spec cards, and steadier checkout summary sizing. Checkout behavior and offer data flow were not changed.
- Verification: browser smoke verified desktop and mobile first views, mobile specs/details, no horizontal overflow, and no clipped alert/spec/summary text.

### 2026-06-18 - Shared offer page information architecture cleanup
- Scope: `CarRental` shared Skyscanner/Trabber offer results page.
- Decision: removed duplicated pickup/return context from the vehicle card and regrouped customer-facing data by purpose: vehicle/specs in the first card, pickup/return/counter policies in one trip section, and package/protection/extras in one offer-details section. Checkout behavior, Stripe handoff, and provider payload data flow were not changed.
- Verification: `npm run build` passed with existing Vite/Browserslist/lottie/chunk warnings. Browser smoke verified desktop/mobile render, pickup and return appear once each, no `Choose coverage` prompt remains on the offer page, and there is no horizontal overflow or clipped mobile section text.

### 2026-06-18 - Expired partner offer search recovery
- Scope: `CarRental` Skyscanner and Trabber expired offer recovery links.
- Decision: expired offer pages now generate the `Search again` URL only when needed and recover missing `unified_location_id` values from stored unified IDs, provider location lookup, search text, or nearest-location fallback. This keeps stale provider-location cache data from sending customers to a malformed search URL.
- Verification: PHP syntax checks passed for touched controllers/services/tests; `SkyscannerOfferPageTest` passed with 4 tests/159 assertions; `TrabberIntegrationTest` passed with 14 tests/157 assertions; `npm run build` passed with existing Vite warnings. Browser smoke confirmed the expired-offer button includes `unified_location_id=3385755165`, returns `200 OK`, and click-navigates to `/en/s` instead of home.

### 2026-06-19 - Expired partner offer canonical search recovery
- Scope: `CarRental` Skyscanner and Trabber expired offer `Search again` links.
- Decision: recovery links now rebuild pickup/dropoff display and coordinates from the canonical unified location record and always reopen mixed inventory. Provider terminal labels and provider-only filters such as `provider_pickup_id=DXB-T1` are no longer carried into customer search URLs.
- Verification: Pint passed for touched PHP/test files; `php artisan test tests\Feature\SkyscannerOfferPageTest.php tests\Feature\TrabberIntegrationTest.php` passed with 18 tests/316 assertions; browser smoke confirmed the clicked link lands on `Dubai Airport (DXB)`, has `provider=mixed`, shows no `Dubai Airport Terminal 1` text, and displays `102 cars available` locally.

### 2026-06-19 - Trabber offer currency and display pricing parity
- Scope: `CarRental` Trabber search, stored offer quote, offer booking adapter, shared offer page summary, and Trabber integration tests.
- Decision: Trabber now converts provider prices/products/extras from supplier currency to the requested customer currency before storing the offer, keeps provider net data only in explicit `net_pricing`/`booking_products`, and sends public gross pricing into the offer page and customize step. OfferResults now uses cents-safe rounding for Pay Now/Pay On Arrival estimates.
- Verification: Pint passed for touched PHP files; `php artisan test tests\Feature\TrabberIntegrationTest.php` passed with 15 tests/219 assertions; `npm run build` passed with existing Vite warnings. Local Trabber API smoke for DXB returned 50 EUR offers; browser smoke verified Kia Sportage and Chevrolet Captiva offer pages and customize summaries matched customer totals without entering Stripe or creating a provider reservation.

### 2026-06-19 - Affiliate booking commission smoke
- Scope: `CarRental` Stripe booking completion to affiliate commission/QR scan tracking.
- Decision: Stripe booking-created affiliate commissions now use the booking customer's `users.id` and the valid `platform` booking type. This prevents commissions from pointing at `customers.id` or silently failing the affiliate commission enum.
- Verification: `php artisan test tests\Unit\StripeBookingServiceAccountingTest.php --filter=affiliate` passed with 1 test/15 assertions; full `StripeBookingServiceAccountingTest` passed with 13 tests/87 assertions; `AffiliateRegistrationTest` passed with 4 tests/27 assertions; `AdminAffiliateBusinessVerificationTest` passed with 1 test/6 assertions; Pint passed for touched PHP/test files. Local Smoke Customer booking created booking `102` and commission `1` for user `181`, customer `29`, affiliate business `5`, QR `2`, with commission `EUR 6.00` on `EUR 200.00`.
- Follow-ups: local smoke booking used faked notifications/jobs and did not call a live provider reservation or real Stripe payment.

### 2026-06-19 - Affiliate browser tracking smoke
- Scope: `CarRental` localized affiliate QR tracking routes and browser smoke for affiliate registration -> scan -> internal booking -> commission.
- Decision: fixed localized route parameter order for `AffiliateQrCodeController::track` and `qrLanding`, and encoded the short-code landing payload before handing it to `AffiliateQrCodeService`. Before the fix, `/en/affiliate/track/{trackingData}` passed `en` as the tracking payload, so browser QR scans redirected without creating scans.
- Verification: headless Chrome created affiliate business `17`, opened the fixed tracking URL and created scan `5`; internal-only booking completion created booking `104` and commission `3` for Smoke Customer user `181` with provider source `internal`, booking type `platform`, and commission `EUR 16.65` on `EUR 555.00`. `AffiliateQrTrackingRouteTest` passed with 2 tests/6 assertions; `AffiliateRegistrationTest` passed with 4 tests/27 assertions; `StripeBookingServiceAccountingTest --filter=affiliate` passed with 1 test/15 assertions; Pint passed for touched PHP/test files.
- Follow-ups: Mailtrap local test account is rate-limited (`550 Too many emails per second`), but registration catches notification failure and still creates the affiliate account.

### 2026-06-19 - Affiliate registration duplicate phone handling
- Scope: `CarRental` affiliate registration backend validation and register form error display.
- Decision: `contact_phone` now validates against the unique `users.phone` index before creating the affiliate user, and duplicate-phone errors return users to the business details step with a visible phone-field validation error instead of a 500.
- Verification: `AffiliateRegistrationTest` passed with 5 tests/33 assertions; Pint passed for touched PHP/test files; `npm run build` passed with existing Browserslist/Vite/lottie/chunk warnings.

### 2026-06-19 - Affiliate registration strict step validation
- Scope: `CarRental` affiliate registration multi-step form and backend store rules.
- Decision: Step 2 now requires phone/city/country, validates phone format locally, checks phone/email uniqueness through `/validate-contact` before allowing Bank/Terms, and final submit re-runs the same server check before posting. Backend store validation now requires phone/city/country and uses the shared phone format rule.
- Verification: `AffiliateRegistrationTest` passed with 7 tests/41 assertions; Pint passed for touched PHP/test files; `npm run build` passed with existing Browserslist/Vite/lottie/chunk warnings. Browser smoke confirmed duplicate phone stays on Business step with 422 `/validate-contact`, and valid phone reaches affiliate dashboard.

### 2026-06-19 - Affiliate registration payout currency parity
- Scope: `CarRental` affiliate registration payout currency validation and Bank Info form display.
- Decision: backend registration now validates payout currency against the shared selectable currency registry instead of a hard-coded short list, so currencies exposed in the dropdown such as `INR` can be submitted. The Bank Info step now shows server-side currency errors and no longer appends duplicate hard-coded currency options.
- Verification: live browser video review reproduced the India/INR path as a 302 validation bounce, not a current 500; `AffiliateRegistrationTest` passed with 8 tests/44 assertions including an `INR` registration case; `npm run build` passed with existing Browserslist/Vite/lottie/chunk warnings.

### 2026-06-19 - Affiliate registration no raw duplicate-data errors
- Scope: `CarRental` affiliate registration validation endpoints, final registration store, and register form error display.
- Decision: email and phone uniqueness now checks both `users` and `affiliate_businesses`, final database duplicate-key failures are converted to field validation messages, and the form has a visible general registration error area plus later-step field errors.
- Verification: `AffiliateRegistrationTest` passed with 12 tests/62 assertions; Pint passed for touched PHP/test files; `npm run build` passed with existing Browserslist/Vite/lottie/chunk warnings. Browser smoke confirmed duplicate email returns 422 and shows “This email is already taken...”; duplicate phone returns 422 and shows “This phone number is already taken...”.

### 2026-06-20 - Affiliate reset and QR admin/customer hardening
- Scope: `CarRental` affiliate forgot-password, partner QR management, admin affiliate partner detail/actions, affiliate QR tracking legacy routes, and affiliate dashboard QR form.
- Decision: password reset now resolves legacy affiliate business contact emails and provisions missing linked affiliate users safely before sending reset links. Partner/admin QR delete routes now soft-delete QR codes with storage cleanup guarded by logs/errors. Old root QR links redirect to localized routes. Admin quick search no longer exposes removed affiliate pages. QR creation now defaults to/selects New Location, shows manual address/coordinate fields and validation, and converts QR generation/storage failures into form errors instead of raw 500 pages.
- Verification: Pint passed for touched PHP/test files; `PasswordResetTest`, `AffiliateQrCodeManagementTest`, `AffiliateQrTrackingRouteTest`, `AdminAffiliateDeleteTest`, and `AdminAffiliateBusinessVerificationTest` passed; `node --test tests\js\adminAffiliateDeleteAction.test.js` passed; `npm run build` passed with existing Vite warnings. Browser smoke created affiliate `smoke-affiliate-20260620-1932@example.test`, reached dashboard, approved it locally, created QR `DJJFXKOXAM`, deleted it through the partner UI (`DELETE /en/affiliate/qr-codes/9` -> 303/200), confirmed soft delete in DB, and forgot-password showed "We have emailed your password reset link" for the affiliate email.
- Follow-ups: local QR generation logs a PNG Imagick warning and falls back to SVG successfully. Local Mailtrap rate limiting can still warn during registration notifications, but registration/reset flows do not become 500s.
