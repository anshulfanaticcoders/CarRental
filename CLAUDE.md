# CarRental Project - Mandatory Development Workflow

> **Last Updated:** 2026-04-17
> **Project:** Laravel 10 + Vue.js 3 Car Rental Platform + Vrooem Gateway (FastAPI/Python)
> **Enforcement Level:** MANDATORY - Every step MUST be followed. No rationalizing, no skipping.

---

## CRITICAL RULES

1. **NEVER write code without completing the pre-code steps first.**
2. **NEVER claim work is done without running verification.**
3. **NEVER skip a skill invocation** - use the `Skill` tool for each one.
4. **Skills MUST be invoked via the `Skill` tool**, not remembered from previous conversations.
5. **Each step produces visible output** - announce what you're doing before each skill invocation.
6. **If a step doesn't apply, say "Skipping step N: [reason]"** - never silently skip.
7. **NEVER run destructive or risky commands without asking the user first.** This includes:
   - `git commit`, `git push`, `git reset`, `git checkout --`, `git clean`, `git rebase`, `git merge`
   - `git branch -D`, `git push --force`, `git stash drop`
   - `php artisan migrate:fresh`, `php artisan migrate:rollback`, `php artisan db:wipe`
   - `rm`, `rm -rf`, deleting any files or directories
   - `composer remove`, `npm uninstall`, dropping packages
   - `docker-compose down -v` (destroys volumes/data)
   - Any command that modifies database data in production
   - Any command that overwrites uncommitted changes
   - **When in doubt, ASK.** It is always safer to ask than to assume.

---

## COMMUNICATION STYLE: CAVEMAN MODE (ALWAYS ACTIVE)

> Caveman mode is **always on** at `lite` level unless user says "stop caveman" or "normal mode".

**Rules:**
- Drop filler words: "just", "really", "basically", "actually", "simply"
- Drop pleasantries: "Sure!", "Certainly!", "I'd be happy to", "Of course"
- Drop hedging: "I think", "It seems like", "It might be"
- Keep articles (a/an/the) and full sentences at `lite` level
- Technical terms stay exact. Code blocks unchanged. Error messages quoted exact.
- Pattern: `[thing] [action] [reason]. [next step].`
- Security warnings, irreversible action confirmations: write in full clarity, then resume caveman.

**Levels** (switch with `/caveman lite|full|ultra`):
| Level | Behavior |
|-------|----------|
| `lite` (default) | No filler/hedging. Full sentences. Professional but tight. |
| `full` | Fragments OK. Drop articles too. |
| `ultra` | Maximum compression. Telegraphic style. |

**Examples:**
- BAD: "Sure! I'd be happy to help you with that. The issue you're experiencing is likely caused by..."
- GOOD: "Bug in auth middleware. Token expiry check uses `<` not `<=`. Fix:"

---

## STEP-BY-STEP WORKFLOW (Follow in Order)

Every task goes through these phases. For each phase, invoke the skill, follow its instructions, then move to the next phase.

### PHASE 1: UNDERSTAND (Before doing anything)

**Step 1 - Optimize the Prompt**
```
Skill: prompt-engineering-patterns
```
- YOU MUST invoke this skill FIRST for every task
- Analyze what the user is really asking
- Identify ambiguities, missing context, edge cases
- Reframe the task internally before proceeding
- If the task is a simple read-only query (read file, search code, git status), skip to execution

**Step 2 - Research & Context Gathering**
```
Tools: ref_search_documentation, exa MCP, Grep, Glob, LSP, Read
```
- Search documentation for any APIs, libraries, or patterns involved
- Read all relevant files BEFORE proposing changes
- Use LSP for symbol search, Grep for text search
- Understand the existing code before touching it
- Check `docs/plans/` and `docs/apidata/` for project-specific context

---

### PHASE 2: DESIGN (Before writing any code)

**Step 3 - Brainstorm (MANDATORY for all creative work)**
```
Skill: superpowers:brainstorming
```
- YOU MUST invoke this for: new features, new components, behavior changes, refactoring, UI changes
- Explore the user's intent and requirements
- Ask clarifying questions if needed
- Propose 2-3 approaches with tradeoffs
- Get user approval before proceeding
- Skip ONLY for: pure bug fixes with obvious solutions, typo fixes, config changes

**Step 4 - Write Implementation Plan (For multi-step tasks)**
```
Skill: superpowers:writing-plans
```
- YOU MUST invoke this when the task requires changes to 3+ files or has multiple steps
- Create a clear, numbered plan with file paths
- Identify dependencies between steps
- Get user approval on the plan before coding
- Skip for: single-file changes, simple fixes

---

### PHASE 3: IMPLEMENT (Writing code)

**Step 5 - Load Karpathy Guidelines**
```
Skill: karpathy-guidelines
```
- YOU MUST invoke this BEFORE writing or editing ANY code
- Make surgical, minimal changes
- Don't over-engineer or add unnecessary abstractions
- Surface assumptions explicitly
- Define verifiable success criteria

**Step 6 - Load Domain Skill (based on what you're editing)**

Invoke the MATCHING skill based on the file type you're about to edit:

| File/Context | Skill to Invoke | Notes |
|---|---|---|
| `*.vue` files | `vue-best-practices` + `antfu/vue` | Composition API with `<script setup>`, Anthony Fu patterns |
| Vue composables | `vueuse-functions` | VueUse patterns for composable authoring |
| `*.php` Laravel files | `laravel-specialist` + `laravel-11-12-app-guidelines` | Models, Controllers, Services + modern Laravel patterns |
| Database migrations | `mysql` | Indexes, constraints, data types |
| API routes/design | `backend-development:api-design-principles` | REST endpoints, response formats |
| Frontend UI/styling | `frontend-design` | Visual design, CSS, layouts |
| Stripe/payment code | `stripe-integration` | Checkout, webhooks, payment intents |
| Email / Mailables / Notifications | `mailtrap-laravel` | Mailtrap sending API, templates, webhooks, sandbox |
| FastAPI Python (`vrooem-gateway/`) | `fastapi-templates` | Async patterns, dependency injection |
| Redis caching | `redis-best-practices` | Cache strategies, key design |
| Supabase/PostgreSQL | `supabase-postgres-best-practices` | Query optimization, schema design |
| Vue Router changes | `vue-router-best-practices` | Navigation guards, route params |
| Pinia store changes | `vue-pinia-best-practices` | State management patterns |
| Vue testing | `vue-testing-best-practices` | Vitest, Vue Test Utils |
| Jobs / Queue workers | `laravel-specialist` | Retries, failures, backoff, rate limiting |
| Events / Listeners | `laravel-specialist` | Event-driven patterns, async dispatch |
| Console Commands / Scheduling | `laravel-specialist` | Artisan commands, `schedule()` method |
| Observers | `laravel-specialist` | Model lifecycle hooks, side effects |
| Middleware | `laravel-specialist` + `security-best-practices` | Request pipeline, auth, rate limiting |
| Broadcasting / Channels | `laravel-specialist` | Pusher, presence channels, WebSocket |
| Skyscanner services | `backend-development:api-design-principles` + `laravel-specialist` | External API integration, response mapping |
| Affiliate system | `laravel-specialist` | Commission tracking, payouts, QR codes |
| Sitemap generation | `laravel-specialist` + `seo-audit` | Spatie Sitemap, crawl rules |

- If editing MULTIPLE file types, invoke ALL matching skills
- If no domain skill matches, proceed without one
- **For ANY frontend UI work**, ALWAYS read `resources/js/design-system.md` first for brand colors, typography, buttons, spacing, shadows, and component patterns. Every new page MUST follow this design system.

**Step 6b - Security Review (for sensitive code)**
```
Skill: security-best-practices
```
- YOU MUST invoke this when editing: payment flows, authentication, user data handling, API endpoints, file uploads
- Review for OWASP top 10, injection, XSS, CSRF, insecure deserialization
- Skip for: UI-only changes, styling, non-sensitive config

**Step 7 - Write the Code**
- Make changes following all loaded skill guidelines
- Write clean, minimal code - no unnecessary additions
- Follow existing patterns in the codebase
- Do NOT add comments, docstrings, or type annotations to code you didn't change

---

### PHASE 4: VALIDATE (After writing code)

**Step 8 - Error Handling Review**
```
Skill: error-handling-patterns
```
- YOU MUST invoke this AFTER writing or editing code
- Review all code changes for: edge cases, error messages, graceful failures
- Ensure proper try/catch, validation, and user-facing error messages
- Fix any issues found before proceeding

**Step 8a - Code Simplification Pass**
```
Agent: code-simplifier (subagent_type via Agent tool)
```
- YOU MUST delegate to this agent AFTER writing or editing non-trivial code (>20 lines changed, OR any new function/class/component)
- Agent reviews only code changed in the current session and **auto-applies** refinements that preserve functionality
- Expected fixes: reduce nesting, remove redundant abstractions, eliminate nested ternaries, consolidate related logic
- **Preserves behavior** — rejects any change that alters what the code does
- Skip for: typo fixes, config tweaks, single-line edits, pure data additions (seeds, translations, fixtures)

**Step 8b - Performance Review (for performance-sensitive code)**
```
Skill: performance-optimization OR web-performance-optimization
```
- Invoke `performance-optimization` for: database queries, API endpoints, service layer, caching logic
- Invoke `web-performance-optimization` for: frontend changes, page load, Core Web Vitals, image handling
- Skip for: minor text changes, config updates, simple bug fixes

**Step 9 - Type Check & Lint**

Run the appropriate commands based on what you changed:

```bash
# If you changed Vue/JS/TS files:
npm run type-check && npm run lint

# If you changed PHP files:
./vendor/bin/pint --test  # or: php artisan code:analyse

# If you changed Python gateway files (run from vrooem-gateway/):
ruff check app/ && ruff format app/ --check

# If you changed database migrations:
php artisan migrate --pretend  # Dry run to verify SQL
```
- Fix ALL errors and warnings before proceeding
- Re-run until clean

**Step 10 - Run Tests**

```bash
# Backend PHP tests:
php artisan test

# Frontend tests:
npm run test

# Gateway tests (from vrooem-gateway/):
pytest
```
- Run tests relevant to your changes
- Fix any failures before proceeding

---

### PHASE 5: VERIFY & SHIP (Before claiming done)

**Step 11 - Browser Testing (for UI changes)**
```
Skill: agent-browser OR superpowers-chrome:browsing
```
- YOU MUST invoke this for ANY frontend/UI changes
- Test the actual feature in the browser
- Verify visual appearance, interactions, responsive behavior
- Skip for: backend-only changes, migration-only changes, config changes

**Step 12 - Verification Before Completion**
```
Skill: superpowers:verification-before-completion
```
- YOU MUST invoke this BEFORE claiming ANY work is complete
- Run verification commands and show output
- Evidence before assertions - never say "done" without proof
- This is the FINAL gate before declaring success

**Step 12b - Codex Second Opinion (For critical/complex code)**
```
Skill: codex
```
- Uses OpenAI GPT-5.2 as an independent second model to review your work
- A different AI architecture catches different blind spots than Claude
- **MANDATORY for:** payment/Stripe flows, security-sensitive code, complex algorithms, database migrations with data loss risk, multi-service changes
- **RECOMMENDED for:** new features touching 5+ files, refactors of core business logic, API contract changes
- **SKIP for:** simple bug fixes, styling changes, config updates, single-file edits
- How: delegate the review via `codex` skill — it runs Codex CLI and returns findings
- Act on any issues found before proceeding to commit

**Step 13 - Git Commit (Only when user asks)**
- Do NOT commit automatically - wait for user to request it
- Use conventional commit format: `feat:`, `fix:`, `refactor:`, `chore:`, `docs:`
- Include Co-Authored-By line

---

## TASK-TYPE QUICK FLOWS

These show which steps are MANDATORY (M) vs SKIP-IF-NOT-APPLICABLE (S) for common task types:

### New Feature
| Step | Status |
|---|---|
| 1. prompt-engineering-patterns | M |
| 2. Research & context | M |
| 3. superpowers:brainstorming | M |
| 4. superpowers:writing-plans | M |
| 5. karpathy-guidelines | M |
| 6. Domain skill(s) | M |
| 7. Write code | M |
| 8. error-handling-patterns | M |
| 8a. code-simplifier (agent) | M |
| 9. Lint & type-check | M |
| 10. Run tests | M |
| 11. Browser test | M (if UI) / S (if backend-only) |
| 12. superpowers:verification-before-completion | M |
| 12b. Codex second opinion | S (M if 5+ files or core logic) |
| 13. Git commit | When user asks |

### Bug Fix
| Step | Status |
|---|---|
| 1. prompt-engineering-patterns | M |
| 2. Research & context | M |
| 3. superpowers:systematic-debugging | M (replaces brainstorming) |
| 4. Writing plan | S (skip for simple fixes) |
| 5. karpathy-guidelines | M |
| 6. Domain skill(s) | M |
| 7. Write fix | M |
| 8. error-handling-patterns | M |
| 8a. code-simplifier (agent) | M |
| 9. Lint & type-check | M |
| 10. Run tests | M |
| 11. Browser test | S (only if UI bug) |
| 12. superpowers:verification-before-completion | M |
| 12b. Codex second opinion | S (M if payment/security/data-loss bug) |
| 13. Git commit | When user asks |

### Vue Component
| Step | Status |
|---|---|
| 1. prompt-engineering-patterns | M |
| 2. Research & context | M |
| 3. superpowers:brainstorming | M |
| 4. Writing plan | S |
| 5. karpathy-guidelines | M |
| 6. vue-best-practices + antfu/vue + frontend-design | M |
| 7. Write component | M |
| 8. error-handling-patterns | M |
| 8a. code-simplifier (agent) | M |
| 9. npm run type-check && npm run lint | M |
| 10. Run tests | M |
| 11. Browser test (agent-browser) | M |
| 12. superpowers:verification-before-completion | M |
| 13. Git commit | When user asks |

### Gateway (FastAPI/Python)
| Step | Status |
|---|---|
| 1. prompt-engineering-patterns | M |
| 2. Research & context | M |
| 3. superpowers:brainstorming | M |
| 4. superpowers:writing-plans | M |
| 5. karpathy-guidelines | M |
| 6. fastapi-templates + redis-best-practices + supabase-postgres-best-practices | M (relevant ones) |
| 7. Write code | M |
| 8. error-handling-patterns | M |
| 8a. code-simplifier (agent) | M |
| 9. ruff check + ruff format | M |
| 10. pytest | M |
| 11. Browser test | S |
| 12. superpowers:verification-before-completion | M |
| 12b. Codex second opinion | S (M if API contracts or multi-service) |
| 13. Git commit | When user asks |

### Database Migration
| Step | Status |
|---|---|
| 1. prompt-engineering-patterns | M |
| 2. Research & context | M |
| 3. Brainstorming | S (skip for simple schema changes) |
| 4. Writing plan | S |
| 5. karpathy-guidelines | M |
| 6. mysql + laravel-specialist | M |
| 7. Write migration | M |
| 8. error-handling-patterns | S |
| 8a. code-simplifier (agent) | S |
| 9. php artisan migrate --pretend | M |
| 10. php artisan test | M |
| 11. Browser test | S |
| 12. superpowers:verification-before-completion | M |
| 12b. Codex second opinion | S (M if dropping columns, renaming tables, or data loss risk) |
| 13. Git commit | When user asks |

### SEO Work
| Step | Status |
|---|---|
| 1. prompt-engineering-patterns | M |
| 2. Research (Ahrefs MCP + exa + ref) | M |
| 3. superpowers:brainstorming | M |
| 4. superpowers:writing-plans | M (for multi-page SEO) |
| 5. karpathy-guidelines | M |
| 6. seo-audit (technical SEO issues) | M |
| 7. seo-geo (location-based pages - car rental is geo!) | M (if location pages) |
| 8. ai-seo (AI search visibility) | S (for AI search optimization) |
| 9. programmatic-seo (template pages at scale) | S (for bulk page generation) |
| 10. content-strategy (what content to create) | S (for content planning) |
| 11. web-performance-optimization (Core Web Vitals) | M |
| 12. superpowers:verification-before-completion | M |
| 13. Git commit | When user asks |

### Stripe / Payment Changes
| Step | Status |
|---|---|
| 1. prompt-engineering-patterns | M |
| 2. Research & context | M |
| 3. superpowers:brainstorming | M |
| 4. superpowers:writing-plans | M |
| 5. karpathy-guidelines | M |
| 6. stripe-integration + laravel-specialist | M |
| 6b. security-best-practices | M (ALWAYS for payment code) |
| 7. Write code | M |
| 8. error-handling-patterns | M |
| 8a. code-simplifier (agent) | M |
| 9. Lint & type-check | M |
| 10. Run tests | M |
| 11. Browser test | M |
| 12. superpowers:verification-before-completion | M |
| 12b. Codex second opinion | M (ALWAYS for payment code) |
| 13. Git commit | When user asks |

### Provider Integration (Gateway + Laravel)
| Step | Status |
|---|---|
| 1. prompt-engineering-patterns | M |
| 2. Research: read existing adapter in `vrooem-gateway/app/adapters/`, provider API docs in `docs/apidata/`, and `config/suppliers/*.yaml` | M |
| 3. superpowers:brainstorming | M |
| 4. superpowers:writing-plans | M (always multi-file: adapter + config + Laravel service + env vars) |
| 5. karpathy-guidelines | M |
| 6. `fastapi-templates` + `laravel-specialist` + `backend-development:api-design-principles` | M |
| 6b. security-best-practices | M (external API calls, credentials handling) |
| 7. Write code (adapter → YAML config → Laravel service → .env.example) | M |
| 8. error-handling-patterns | M (HTTP timeouts, rate limits, malformed responses) |
| 8a. code-simplifier (agent) | M |
| 9. ruff check + ruff format (gateway) + ./vendor/bin/pint --test (Laravel) | M |
| 10. pytest (gateway) + php artisan test (Laravel) | M |
| 11. Browser test | S |
| 12. superpowers:verification-before-completion | M |
| 12b. Codex second opinion | M (external API + money handling) |
| 13. Git commit | When user asks |

### Notification Changes
| Step | Status |
|---|---|
| 1. prompt-engineering-patterns | M |
| 2. Research: read existing notification in same subdirectory, check `FormatsBookingAmounts` trait | M |
| 3. Brainstorming | S (M if new notification type) |
| 4. Writing plan | S |
| 5. karpathy-guidelines | M |
| 6. `laravel-specialist` + `mailtrap-laravel` | M |
| 7. Write notification class (must include `title`, `role` fields for DB channel filtering) | M |
| 8. error-handling-patterns | M |
| 8a. code-simplifier (agent) | S |
| 9. ./vendor/bin/pint --test | M |
| 10. php artisan test | M |
| 11. Browser test | S (M if testing email template appearance) |
| 12. superpowers:verification-before-completion | M |
| 13. Git commit | When user asks |

### Skyscanner Integration
| Step | Status |
|---|---|
| 1. prompt-engineering-patterns | M |
| 2. Research: read `app/Services/Skyscanner/`, `routes/skyscanner.php`, `docs/skyscanner/` | M |
| 3. superpowers:brainstorming | M |
| 4. superpowers:writing-plans | M (if multi-service change) |
| 5. karpathy-guidelines | M |
| 6. `laravel-specialist` + `backend-development:api-design-principles` | M |
| 7. Write code | M |
| 8. error-handling-patterns | M (Skyscanner has strict API contract) |
| 8a. code-simplifier (agent) | S |
| 9. ./vendor/bin/pint --test | M |
| 10. php artisan test | M |
| 11. Browser test | S |
| 12. superpowers:verification-before-completion | M |
| 12b. Codex second opinion | S (M if changing API response format) |
| 13. Git commit | When user asks |

### Admin Dashboard Pages
| Step | Status |
|---|---|
| 1. prompt-engineering-patterns | M |
| 2. Research: read existing admin page in `Pages/AdminDashboardPages/`, check Shadcn `Components/ui/` | M |
| 3. superpowers:brainstorming | M |
| 4. Writing plan | S |
| 5. karpathy-guidelines | M |
| 6. `vue-best-practices` + `frontend-design` | M |
| 7. Write page (use Shadcn `rounded-xl border bg-card shadow` pattern for admin cards) | M |
| 8. error-handling-patterns | M |
| 8a. code-simplifier (agent) | M |
| 9. npm run type-check && npm run lint | M |
| 10. Run tests | M |
| 11. Browser test | M |
| 12. superpowers:verification-before-completion | M |
| 13. Git commit | When user asks |

### Console Commands / Jobs
| Step | Status |
|---|---|
| 1. prompt-engineering-patterns | M |
| 2. Research: read existing commands in `app/Console/Commands/`, check `Kernel.php` schedule | M |
| 3. Brainstorming | S |
| 4. Writing plan | S |
| 5. karpathy-guidelines | M |
| 6. `laravel-specialist` | M |
| 7. Write command/job (register in Kernel if scheduled) | M |
| 8. error-handling-patterns | M (jobs MUST handle failures gracefully: retries, backoff, `failed()` method) |
| 8a. code-simplifier (agent) | S |
| 9. ./vendor/bin/pint --test | M |
| 10. php artisan test | M |
| 11. Browser test | S |
| 12. superpowers:verification-before-completion | M |
| 13. Git commit | When user asks |

### Middleware Changes
| Step | Status |
|---|---|
| 1. prompt-engineering-patterns | M |
| 2. Research: read existing middleware in `app/Http/Middleware/`, check `Kernel.php` registration | M |
| 3. Brainstorming | S (M if new middleware) |
| 4. Writing plan | S |
| 5. karpathy-guidelines | M |
| 6. `laravel-specialist` + `security-best-practices` | M |
| 7. Write middleware (register in Kernel.php, correct group: web/api/global) | M |
| 8. error-handling-patterns | M (middleware failures affect ALL requests in its group) |
| 8a. code-simplifier (agent) | S |
| 9. ./vendor/bin/pint --test | M |
| 10. php artisan test | M |
| 11. Browser test | S (M if middleware affects frontend rendering) |
| 12. superpowers:verification-before-completion | M |
| 13. Git commit | When user asks |

### Affiliate System
| Step | Status |
|---|---|
| 1. prompt-engineering-patterns | M |
| 2. Research: read `app/Models/Affiliate/` (8 models), `app/Services/Affiliate/`, `app/Notifications/Affiliate/` | M |
| 3. superpowers:brainstorming | M |
| 4. superpowers:writing-plans | M |
| 5. karpathy-guidelines | M |
| 6. `laravel-specialist` + `stripe-integration` (if payout-related) | M |
| 6b. security-best-practices | M (commission calculations, payout handling) |
| 7. Write code | M |
| 8. error-handling-patterns | M |
| 8a. code-simplifier (agent) | M |
| 9. ./vendor/bin/pint --test | M |
| 10. php artisan test | M |
| 11. Browser test | M (if UI changes) |
| 12. superpowers:verification-before-completion | M |
| 12b. Codex second opinion | S (M if touching commission/payout logic) |
| 13. Git commit | When user asks |

---

## EXCEPTIONS - When to Skip the Full Workflow

Only these tasks skip the workflow:

1. **Read-only queries**: Reading files, searching code, git status/log, explaining code
2. **Simple questions**: "What does X do?", "Where is Y defined?", "Show me Z"
3. **Already-loaded skills**: If a skill was invoked in the last 5 turns AND the context hasn't changed, skip re-invocation (but still announce "Using previously loaded [skill]")

Everything else follows the full workflow. When in doubt, follow the workflow.

---

## ENVIRONMENT VARIABLES PATTERNS

When adding new features or providers, follow these naming patterns (check `.env.example` for full list):

```bash
# Provider API credentials (pattern: PROVIDERNAME_FIELD)
GREENMOTION_USERNAME=
GREENMOTION_PASSWORD=
USAVE_API_URL=
USAVE_USERNAME=
SURPRICE_API_KEY=
SURPRICE_RATE_CODE=
FAVRICA_BASE_URL=
XDRIVE_BASE_URL=
XDRIVE_IMAGE_BASE_URL=
OKMOBILITY_CUSTOMER_CODE=

# Payments
STRIPE_KEY=           # Public key (frontend)
STRIPE_SECRET=        # Secret key (backend)

# Broadcasting (real-time chat)
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=

# Currency
DEFAULT_CURRENCY=EUR
EXCHANGE_RATE_API_KEY=

# Affiliate
TAPFILIATE_API_KEY=
AWIN_ADVERTISER_ID=

# Email
MAILTRAP_API_KEY=     # Or SMTP: MAIL_HOST, MAIL_PORT, MAIL_USERNAME
MAILTRAP_SANDBOX=     # true/false for testing

# Monitoring
SENTRY_LARAVEL_DSN=
MICROSOFT_CLARITY_PROJECT_ID=

# Storage
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
```

**Rule:** When adding a new provider, ALWAYS add its env vars to `.env.example` with empty defaults and document them in `config/services.php` or a dedicated config file.

---

## PROJECT STRUCTURE

### Backend (Laravel 10)
```
app/Http/Controllers/          # 108 controllers across 6 subdirectories
  ├── Admin/                   # 41 admin controllers (dashboard, settings, reports)
  ├── Auth/                    # 10 authentication controllers
  ├── Api/                     # 7 internal API controllers
  ├── Affiliate/               # 3 affiliate program controllers
  ├── Skyscanner/              # 4 Skyscanner integration controllers
  ├── Vendor/                  # 9 vendor management controllers
  └── (root)                   # Bookings, vehicles, search, messages, notifications
app/Http/Middleware/            # 22 middleware (auth, locale, currency, SEO, Awin)
app/Http/Requests/             # Form request validation classes
app/Models/                    # 83 models (User, Booking, Vehicle, Affiliate, etc.)
  └── Affiliate/               # 8 affiliate models (Business, Commission, Payout, QR)
app/Services/                  # 55 services across 8 subdirectories
  ├── Affiliate/               # Commission, business services
  ├── Bookings/                # Internal booking snapshots
  ├── Search/                  # Gateway + internal search orchestration
  ├── Seo/                     # SEO data resolution
  ├── Sitemaps/                # 10 files - public sitemap generation
  ├── Skyscanner/              # 18 Skyscanner-specific services
  └── Vehicles/                # Availability, transformers, deletion
app/Notifications/             # 40+ notification classes across 6 subdirectories
  ├── Affiliate/               # Business registration, status changes
  ├── ApiBooking/              # API booking created/cancelled
  ├── Booking/                 # Lifecycle (created, cancelled, reminders, status)
  ├── Payment/                 # Payment failure notifications
  ├── Review/                  # Review submitted
  ├── Vendor/                  # Vendor-specific notifications
  └── Concerns/                # FormatsBookingAmounts trait
app/Jobs/                      # SendAwinConversion, Newsletter campaign/recipient jobs
app/Events/                    # 6 events (NewMessage, MessageRead, TypingIndicator, etc.)
app/Listeners/                 # LogSuccessfulLogin, LogSuccessfulLogout
app/Observers/                 # Blog, BlogTranslation, PageTranslation, Vehicle observers
app/Mail/                      # NewsletterCampaignMail
app/Console/Commands/          # 9 commands (sitemaps, currency, reminders, SEO, GeoIP)
app/Helpers/                   # ActivityLog, Hreflang, ImageCompression, Locale, SchemaBuilder
app/Policies/                  # UserDocumentPolicy
app/Providers/                 # 6 providers (App, Auth, Broadcast, Currency, Event, Route)
app/Rules/                     # PhoneNumber validation
database/migrations/           # 158+ migrations
config/                        # 26 config files (see Key Architecture)
```

### Routes
```
routes/web.php             # Main web routes (53KB, Inertia pages)
routes/api.php             # REST API endpoints (bookings, vehicles, vendors, admin)
routes/auth.php            # Authentication routes (Breeze)
routes/skyscanner.php      # Skyscanner-specific API routes
routes/channels.php        # Broadcasting channels (Pusher)
routes/console.php         # Console command routes
routes/chatify/            # Chat/messaging routes
```

### Frontend (Vue.js 3 + Inertia.js)
```
resources/js/Pages/            # Page components (Inertia pages)
  ├── AdminDashboardPages/     # 45+ admin pages (Analytics, Bookings, SEO, Settings, etc.)
  ├── Affiliate/               # Affiliate dashboard pages
  ├── Auth/                    # Login, register, password reset
  ├── Booking/                 # Booking flow (search, details, success)
  ├── Destinations/            # Destination/location pages
  ├── Frontend/                # Public site pages + Templates/
  ├── Messages/                # Messaging interface
  ├── Notifications/           # Notification pages
  ├── Profile/                 # User profile (Bookings, Documents, Review)
  └── Vendor/                  # Vendor dashboard (Bookings, Vehicles, Payments, etc.)
resources/js/Components/       # 317 components
  ├── Admin/                   # Admin-specific components
  ├── BookingExtras/           # Add-ons UI (adapters, composables, modals, sections)
  ├── ReusableComponents/      # Shared UI components
  ├── Skyscanner/              # Skyscanner integration components
  ├── Welcome/                 # Landing page components
  └── ui/                      # 40+ Shadcn-Vue/Radix components (see list below)
resources/js/Layouts/          # Layout components
resources/js/composables/      # Vue composables (useBookingData.js, etc.)
resources/js/features/         # Feature modules (search/, skyscanner/ with utils)
resources/js/types/            # TypeScript type definitions
resources/js/utils/            # Helper utilities
resources/js/data/             # Static data files
resources/js/plugins/          # Vue plugins
resources/js/lib/              # Utility libraries
resources/css/                 # Stylesheets
```

### Available Shadcn-Vue Components (`Components/ui/`)
```
Accordion, Alert, AlertDialog, Avatar, Badge, Breadcrumb, Button,
Calendar, Card, Carousel, Chart (Bar/Line/Pie), Checkbox, Collapsible,
Dialog, DropdownMenu, Input, Label, Pagination, Popover, Select,
Separator, Sheet, Sidebar, Skeleton, Sonner (toast), Stepper, Switch,
Table, Tabs, Textarea, Toast, Tooltip
```
Always check `Components/ui/` before creating custom components.

### Vrooem Gateway (FastAPI/Python) - at `/mnt/c/laragon/www/vrooem-gateway/`
```
app/main.py                # FastAPI entry point
app/core/                  # Config, auth, exceptions
app/schemas/               # Pydantic schemas
app/adapters/              # Provider adapters (per car rental supplier)
app/services/              # Cache (Redis), circuit breaker
app/db/                    # SQLAlchemy models + async session
app/api/v1/                # API route handlers
config/suppliers/          # YAML configs per provider
```

### Key Architecture
- **3-tier currency**: Customer / Admin(EUR) / Vendor - each gets amounts in own currency via `BookingAmountService`
- **15% platform commission**: Applied to ALL vehicles via `PROVIDER_MARKUP_PERCENT`
- **Inertia.js**: Laravel-Vue bridge (no separate API for frontend)
- **Price verification**: SHA-256 hash integrity between search and checkout via `PriceVerificationService`
- **Email service**: Mailtrap (transactional sending API + sandbox testing)
- **Notifications**: `app/Notifications/` - 40+ classes across 6 subdirectories (Affiliate, ApiBooking, Booking, Payment, Review, Vendor)
- **Notification routing**: Email via Mailtrap + DB channel for website notification bell (role-based filtering via `title` + `role` fields)
- **Real-time messaging**: Pusher broadcasting for chat (Events → Listeners → Channels)
- **Affiliate system**: Tapfiliate + Awin integration, commission tracking, payouts, QR codes (8 models)
- **Skyscanner integration**: Dedicated route file, 4 controllers, 18 services — separate API contract
- **Error tracking**: Sentry (sentry.php config, DSN in .env)
- **SMS**: Twilio SDK for phone verification and notifications
- **Social auth**: Laravel Socialite (Google, Facebook login)
- **Image processing**: Intervention Image 3.11 for uploads and thumbnails
- **PDF generation**: DomPDF for booking receipts (`booking-receipt.blade.php`)
- **Sitemap generation**: Spatie Sitemap with 10 service files for automated public sitemaps
- **Currency system**: CurrencyServiceProvider, ExchangeRate API, `RefreshCurrencyRates` command
- **SEO system**: HandleSeoRedirects middleware, `app/Services/Seo/`, schema markup via `SchemaBuilder` helper
- **Localization**: Multi-locale support via SetLocale + SetCurrency + LocalizationMiddleware
- **Queue/Jobs**: 3 jobs (Awin conversion, newsletter send/process) via Laravel queue
- **Observers**: Auto-trigger on model events (Blog, BlogTranslation, PageTranslation, Vehicle)
- **17+ provider adapters**: External car rental suppliers integrated via vrooem-gateway (FastAPI)

---

## COMMON COMMANDS

```bash
# Laravel Backend
php artisan migrate:fresh --seed    # Reset DB with seeds
php artisan migrate --pretend       # Dry run migration SQL
php artisan test                    # Run PHPUnit tests
php artisan route:list              # List all routes
php artisan tinker                  # REPL
composer dump-autoload              # Refresh autoloader

# Laravel Queue & Scheduling
php artisan queue:work              # Process queue jobs
php artisan schedule:run            # Run scheduled commands
php artisan queue:failed            # List failed jobs
php artisan queue:retry all         # Retry all failed jobs

# Laravel Artisan Commands (Custom)
php artisan sitemap:generate        # Generate public sitemaps
php artisan currency:refresh        # Refresh exchange rates from API
php artisan reminders:send          # Send pending booking reminders
php artisan geoip:update            # Update GeoIP database

# Vue Frontend
npm run dev                         # Dev server
npm run build                       # Production build
npm run type-check                  # TypeScript check
npm run lint                        # ESLint
npm run test                        # Vitest

# Gateway (from /mnt/c/laragon/www/vrooem-gateway/)
docker-compose up -d                # Start gateway + Redis + PostgreSQL
docker-compose down                 # Stop all
ruff check app/                     # Lint Python
ruff format app/                    # Format Python
pytest                              # Run tests

# Git
git status
git diff
git log --oneline -10
```

---

## INSTALLED SKILLS & PLUGINS INVENTORY

### Process Skills (Superpowers v5.0.5)
| Skill | When to Use |
|---|---|
| `superpowers:brainstorming` | Before ANY creative work |
| `superpowers:writing-plans` | Before multi-step tasks |
| `superpowers:executing-plans` | When executing a written plan |
| `superpowers:systematic-debugging` | For any bug or unexpected behavior |
| `superpowers:test-driven-development` | When implementing features with TDD |
| `superpowers:verification-before-completion` | Before claiming work is done |
| `superpowers:dispatching-parallel-agents` | For 2+ independent tasks |
| `superpowers:subagent-driven-development` | Executing plans with parallel agents |
| `superpowers:finishing-a-development-branch` | When ready to merge/PR |
| `superpowers:requesting-code-review` | Before merging major features |
| `superpowers:receiving-code-review` | When handling review feedback |
| `superpowers:using-git-worktrees` | For isolated feature work |

### Code Quality Skills
| Skill | When to Use |
|---|---|
| `prompt-engineering-patterns` | First step of every task |
| `karpathy-guidelines` | Before writing/editing ANY code |
| `error-handling-patterns` | After writing code |
| `security-best-practices` | Payment flows, auth, user data, API endpoints |
| `performance-optimization` | Backend performance, DB queries, API endpoints |
| `web-performance-optimization` | Frontend performance, Core Web Vitals, page speed |
| `code-simplifier` (agent, not skill) | After writing/editing non-trivial code — auto-refines for clarity, preserves behavior |

### Domain Skills - Laravel
| Skill | When to Use |
|---|---|
| `laravel-specialist` | Any Laravel PHP code |
| `laravel-11-12-app-guidelines` | Modern Laravel patterns, latest conventions |
| `mysql` | Database migrations, queries, schema design |

### Domain Skills - Vue.js
| Skill | When to Use |
|---|---|
| `vue-best-practices` | Any Vue component work |
| `antfu/vue` (installed as `vue`) | Anthony Fu's Vue patterns (core team) |
| `vueuse-functions` | VueUse composable patterns |
| `vue-router-best-practices` | Router, navigation guards |
| `vue-pinia-best-practices` | Pinia state management |
| `vue-testing-best-practices` | Vitest, Vue Test Utils |
| `vue-debug-guides` | Vue runtime errors, debugging |

### Domain Skills - Gateway (Python)
| Skill | When to Use |
|---|---|
| `fastapi-templates` | FastAPI application patterns |
| `async-python-patterns` | Python async/await patterns |
| `redis-best-practices` | Redis caching, data structures |
| `supabase-postgres-best-practices` | PostgreSQL optimization |

### Domain Skills - Payments & Email
| Skill | When to Use |
|---|---|
| `stripe-integration` | Stripe checkout, webhooks, payment intents |
| `mailtrap-laravel` | Mailables, email templates, Mailtrap webhooks, sandbox testing |

### SEO & Marketing Skills
| Skill | When to Use |
|---|---|
| `seo-audit` | Technical SEO issues, site health |
| `seo-geo` | Location-based SEO (car rental cities/airports) |
| `ai-seo` | AI search optimization (ChatGPT, Perplexity visibility) |
| `programmatic-seo` | Template pages at scale (city/airport landing pages) |
| `content-strategy` | Content planning, topic clusters, editorial calendar |

### UI/Design Skills
| Skill | When to Use |
|---|---|
| `frontend-design` | Creating web components, pages, UI |
| `impeccable:*` | Design quality suite (critique, polish, animate, adapt, etc.) |
| `elements-of-style:writing-clearly-and-concisely` | Prose quality for UI text, docs |

### Browser & Testing Skills
| Skill | When to Use |
|---|---|
| `agent-browser` | Browser automation, form testing, E2E |
| `superpowers-chrome:browsing` | Chrome DevTools Protocol, direct browser control |

### Utility Skills
| Skill | When to Use |
|---|---|
| `codex` | GPT-5.2 second opinion — M for payments/security, S for complex features (Step 12b) |
| `find-skills` | Discover new skills (`npx skills find [query]`) |
| `superpowers:writing-skills` | Create custom skills with Skill Creator 2.0 |
| `superpowers-lab:mcp-cli` | Use MCP servers on-demand |

### Installed Plugins
| Plugin | Version | Purpose |
|---|---|---|
| `superpowers` | 5.0.5 | Core process skills (brainstorm, plan, debug, TDD) |
| `superpowers-chrome` | 1.5.0 | Browser DevTools Protocol |
| `superpowers-lab` | 0.1.0 | Experimental skills incubator |
| `superpowers-developing-for-claude-code` | 0.2.0 | Claude Code development docs |
| `impeccable` | 1.5.0 | Design quality suite |
| `elements-of-style` | 1.0.0 | Writing quality |
| `frontend-design` | latest | UI/UX design patterns |
| `code-documentation` | latest | Technical documentation |
| `debugging-toolkit` | latest | Advanced debugging |
| `error-diagnostics` | latest | Error pattern analysis |
| `git-pr-workflows` | latest | Git & PR workflows |
| `backend-development` | latest | Backend architecture patterns |
| `codex` | 1.0.3 | GPT-5.2 second-model review via Codex CLI |
| `php-lsp` | 1.0.0 | PHP semantic code intelligence |
| `typescript-lsp` | 1.0.0 | TypeScript semantic code intelligence |

### MCP Servers
| Server | Purpose |
|---|---|
| `Ref` | Search framework/library documentation |
| `exa` | Web search for code context |
| `Ahrefs` | SEO data, keyword research, backlink analysis |
| `Linear` | Project/issue tracking |
| `Gmail` | Email integration |
| `SERP API` | Google search results |
| `Chrome DevTools` | Browser automation |
| `Sequential Thinking` | Complex reasoning chains |

---

## ADDITIONAL RESOURCES

- **Project Summary:** `PROJECT_SUMMARY.md`
- **Bug Prevention:** `docs/bug-prevention/README.md`
- **Design Documents:** `docs/plans/`
- **Gateway Guide:** `docs/plans/vrooem-gateway-implementation-guide.md`
- **Provider API Data:** `docs/apidata/` (12 provider API docs)
- **Gateway Codebase:** `/mnt/c/laragon/www/vrooem-gateway/`
- **Skills Marketplace:** https://skills.sh/ (browse & install new skills)
- **Skill Creator 2.0:** Use `superpowers:writing-skills` to create, eval, improve, and benchmark custom skills
- **Design System:** `resources/js/design-system.md` (colors, typography, spacing, components)

---

## FRONTEND CO-PILOT RULES

> **You are my rapid frontend prototyping co-pilot.** Every UI output must match my established design standard. No generic UI. No Bootstrap aesthetics. No placeholder styling. Ship-ready from the first render.

### Stack (Non-Negotiable)
- **Vue 3** with `<script setup>` Composition API - ALWAYS
- **Inertia.js** for Laravel-Vue bridge - use `Link`, `router`, `usePage()`
- **Tailwind CSS** for utility classes + scoped `<style>` for complex CSS
- **Shadcn-Vue / Radix Vue** for UI primitives (`Components/ui/`)
- **Lucide Vue Next** for icons - NEVER use img-based icons for standard UI
- **CSS custom properties** for theming (`--ease`, `--duration`, `--custom-primary`)

### Design DNA - Match This Exactly

**Brand Identity:**
- Primary: `#153b4f` (dark teal). Accent: `#22d3ee` (cyan). NEVER use generic blue.
- Shadows use brand color at low opacity: `rgba(21, 59, 79, 0.08)` - NOT gray shadows.
- Gradients: `linear-gradient(135deg, #153b4f, #1c4d66)` for buttons. `linear-gradient(135deg, #0b2230, #153b4f 45%, #0b1b26)` for dark sections.

**Typography:**
- Headings: Plus Jakarta Sans, weight 600-800
- Body: IBM Plex Sans, weight 400-500
- Section labels: `0.66rem`, uppercase, `letter-spacing: 0.16em`, color `#94a3b8`

**Spacing & Layout:**
- Container: `full-w-container` class (`width: min(92%, 1440px); margin-inline: auto`)
- Section padding: `clamp(3rem, 6vw, 6rem)`
- Card padding: `2rem`. Card radius: `14px-20px`
- Consistent gap system: `6px` between header actions, `8-10px` between offcanvas items

**Interactions (MANDATORY on every interactive element):**
- Easing: `cubic-bezier(0.22, 1, 0.36, 1)` - NEVER use `ease` or `ease-in-out`
- Duration: `0.3s` for hovers, `0.35s` for hamburger bars, `0.5s` for offcanvas slide
- Hover lift: `transform: translateY(-1px)` to `translateY(-6px)` with shadow increase
- NEVER use `transition: all` - always target specific properties
- Store easing/duration in CSS variables on the component root element (scoped styles can't access `:root`)

**Dark Sections (Hero, Footer):**
- Glassmorphism: `backdrop-filter: blur(20px) saturate(1.4)` with gradient overlay
- Glass elements: `background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1)`
- Text: white at 70-85% opacity for body, 100% for headings, cyan `#22d3ee` for accents
- Hover: increase bg opacity to 0.12, border to 0.2, add brand shadow

**Light Sections (Inner pages):**
- Background: `#fff` with `border-bottom: 1px solid rgba(226,232,240,0.6)`
- Elements: `background: #f8fafc; border: 1px solid #e2e8f0`
- Hover: `background: #f0f8fc; border-color: #153b4f`

### Component Patterns

**Header:** Two modes via single class toggle (`.is-hero` / `.is-light`). Logo left, nav center (hero only, hidden <1024px), actions right. Hamburger always visible. One "Log in" button on all screens - signup is offcanvas only. Height 60px mobile, 72px desktop.

**Offcanvas:** Slide from right with `transform: translateX(100%)` → `translateX(0)` at `0.5s`. Blurred overlay. Sections: Account → Settings → Explore → Footer (WhatsApp/Call). Items are white cards with 14px radius, slide-right 4px on hover.

**Footer:** Dark bg `#0e2a3a` with subtle cyan radial glow. Logo + description + social (lucide icons) on left, 4-column link grid on right. Newsletter horizontal strip between links and bottom bar. Cyan subscribe button. 2-column grid on all mobile breakpoints.

**Cards:** 20px radius, brand-tinted shadow, lift on hover. Dark section cards use glassmorphism. Admin cards use Shadcn `rounded-xl border bg-card shadow`.

**Buttons:** 12px radius for header/UI, pill (999px) for marketing CTAs. Gradient for primary (`#153b4f → #1c4d66`). Ghost: transparent with border. Always lift + shadow on hover.

### Code Style Rules

1. **Scoped styles** with CSS custom properties on the component root (NOT `:root`)
2. **One-line CSS** for simple properties, multi-line for complex selectors
3. **BEM-lite naming**: `.hdr-icon`, `.hdr-trigger`, `.oc-panel`, `.oc-item` - short, flat, no nesting
4. **No `transition: all`** - always list specific properties
5. **No unused imports** - clean up what you remove
6. **Minimize lines** - compress maps/objects, use ternaries, avoid verbose multi-line when one line is clear
7. **Every interactive element** gets: transition, hover state, and focus-visible outline
8. **Mobile-first responsive** - base styles for mobile, `@media (min-width)` for larger
9. **`full-w-container`** for page-width consistency - never create custom containers
10. **Inertia `Link`** for internal navigation - never use `<a href>` for internal routes

### When Creating New Pages/Components

1. Read `resources/js/design-system.md` FIRST
2. Use `full-w-container` for layout
3. Match the dark/light section pattern from Welcome page
4. Include hover transitions on ALL interactive elements
5. Test at 375px, 768px, 1024px, 1440px
6. Use existing Shadcn components from `Components/ui/` before creating custom ones
7. Logo: `<ApplicationLogo :logoColor="dark ? '#ffffff' : '#153b4f'" />`
