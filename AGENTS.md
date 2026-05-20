# Vrooem / CarRental Agent Instructions

This repo is the main Laravel 10 + Vue 3 + Inertia application for Vrooem Car Rentals.

## Project Map
- Main web/admin/customer/vendor app: `C:\laragon\www\CarRental`
- Supplier gateway service: `C:\laragon\www\vrooem-gateway`
- Expo mobile app: `C:\laragon\www\vrooem-mobile`

## Mandatory Context
- Read `CLAUDE.md` before significant work. It contains the canonical workflow, safety rules, design DNA, task-type flows, and architecture notes.
- Read `resources/js/design-system.md` before any frontend/UI change.
- Prefer existing components in `resources/js/Components/ui/` and existing feature/page patterns before creating new primitives.
- For provider/supplier integration work, inspect both this repo and `C:\laragon\www\vrooem-gateway`.
- For mobile API work, inspect both this repo's `routes/api.php` `/api/mobile` routes and `C:\laragon\www\vrooem-mobile\src\api` callers.

## Stack
- Backend: Laravel 10, PHP 8.1+, Sanctum, Inertia Laravel, queues/jobs, Pusher, Stripe, Mailtrap, Sentry, Twilio.
- Frontend: Vue 3 with `<script setup>`, Inertia.js, Tailwind CSS, Shadcn-Vue/Radix Vue, Lucide Vue Next.
- Data: MySQL in Laravel; gateway also uses Redis/PostgreSQL/MySQL paths depending on adapter/service.

## Architecture Notes
- Laravel owns web/admin/vendor/customer flows and the mobile API under `routes/api.php` prefix `mobile`.
- Gateway calls Laravel internal APIs via `gateway.token` routes and normalizes supplier APIs.
- Mobile app calls Laravel through `EXPO_PUBLIC_API_URL`, defaulting to `/api/mobile`.
- Key domains include bookings, vehicles, search, Skyscanner, affiliate, notifications, payments, SEO, sitemaps, messages, documents, and vendor operations.

## Safety Rules
- Never run destructive commands without explicit user approval: `git reset`, `git checkout --`, `git clean`, deletes, rollback/fresh migrations, force push, database wipes, package removals.
- Do not commit or push unless the user asks.
- Protect secrets: do not paste `.env`, API keys, MCP tokens, or mobile signing credentials into responses or docs.
- Work surgically. Match existing code style and ownership boundaries.

## Required Workflow For Code Changes
1. Understand the task and inspect relevant files first.
2. Use relevant skills: `karpathy-guidelines` before edits; `laravel-specialist` for PHP/Laravel; `vue-best-practices` for Vue; `mysql` for migrations/queries; `fastapi-templates` for gateway Python; `redis-best-practices` or `supabase-postgres-best-practices` when relevant; UI/design skills for frontend work.
3. For UI/design changes, route visual implementation to Claude as primary designer/implementer and require Impeccable (`pbakaus/impeccable`) for shape/audit/polish before the UI is considered done.
4. For UI changes, follow the design system exactly: brand teal `#153b4f`, cyan `#22d3ee`, Plus Jakarta Sans headings, IBM Plex Sans body, `full-w-container`, scoped styles, targeted transitions, Lucide icons.
5. Validate with the smallest relevant checks, then broader checks when risk is higher.

## Impeccable UI/UX Pro Max Playbook
- When the user asks for a new design, redesign, premium UI, UI/UX Pro Max, visual polish, animation, or theme matching, use Claude as the primary visual designer and apply Impeccable commands as the quality system.
- Default premium-design flow: `/impeccable shape` -> `/impeccable critique` -> `/impeccable layout` -> `/impeccable typeset` -> `/impeccable colorize` -> `/impeccable animate` -> `/impeccable adapt` -> `/impeccable harden` -> `/impeccable optimize` -> `/impeccable polish`.
- Use `/impeccable craft` when the user approved the direction and wants the design built end to end. Use `/impeccable live` when iterating in the browser. Use `/impeccable teach` or `/impeccable document` when PRODUCT.md/DESIGN.md context is missing or stale. Use `/impeccable extract` when a design pattern should become reusable.
- Available Impeccable commands to consider by task: `craft`, `teach`, `document`, `extract`, `shape`, `critique`, `audit`, `polish`, `bolder`, `quieter`, `distill`, `harden`, `onboard`, `animate`, `colorize`, `typeset`, `layout`, `delight`, `overdrive`, `clarify`, `adapt`, `optimize`, `live`.
- Before implementing any approved design, inspect the live page, homepage, existing components, and `resources/js/design-system.md`; create previews only when the user asks to approve a direction first.

## Automatic Task Router
- Agent assignment rule: use Claude as the primary implementer for frontend/UI/design-heavy tasks, with Impeccable as the mandatory UI/UX quality pass. Use Codex as the primary implementer/reviewer for backend, API, database, provider gateway, security, test strategy, code review, and verification-heavy tasks.
- Full-stack tasks: split ownership. Claude handles UI surfaces and visual polish; Codex handles Laravel/FastAPI/mobile API contracts, data flow, security, tests, and review.
- Read-only code questions: use `rg`/Serena and answer from files. Do not load every skill.
- Bug fixes: inspect failing path, reproduce if practical, use `karpathy-guidelines`, then domain skill. Use debugging skill for Vue/runtime bugs.
- New feature or behavior change: use `prompt-engineering-patterns`, `brainstorming`, `karpathy-guidelines`, then domain skills.
- Laravel/API work: `laravel-specialist`; for public/external API contracts also use API design guidance and security review.
- Vue/UI work: `vue-best-practices`, `frontend-design`, Impeccable (`shape`, `audit`, and `polish` as appropriate), and `resources/js/design-system.md`.
- Gateway work: `fastapi-templates`; use Ref for current library docs if APIs are uncertain.
- Mobile work: inspect `vrooem-mobile/src/api`, matching Laravel `/api/mobile` route/controller, and mobile types together.
- Database work: `mysql` for MySQL, `supabase-postgres-best-practices` for Postgres/Supabase; dry-run migrations before real DB changes.
- External/current docs: use Ref first for official docs; use Exa/web only when current or non-official discovery is needed.
- Browser/UI verification: use Browser/Chrome DevTools after significant frontend changes.
- Linear/Gmail/calendar-style MCPs: use only when the task asks for that external system.

## Subagent Use
- Use subagents for independent multi-repo exploration, parallel review, or separate implementation areas with disjoint files.
- Keep the immediate blocking implementation path local.
- Give subagents tight scope, expected output, and file ownership. Do not duplicate the same investigation.
- Prefer Codex subagents for backend/review/verification slices. Prefer Claude/frontend-capable sessions for UI slices when available.

## Common Commands
- Laravel tests: `php artisan test`
- Laravel format check: `./vendor/bin/pint --test`
- Route list: `php artisan route:list`
- Frontend dev: `npm run dev`
- Frontend build: `npm run build`
- Migration dry run: `php artisan migrate --pretend`

## Verification Expectations
- PHP/Laravel changes: run Pint/test or explain why not run.
- Vue/frontend changes: run build/type/lint where scripts exist; browser-check UI when practical.
- Mobile API contract changes: verify both Laravel controller/route and mobile caller/types.
- Provider changes: verify gateway tests/lint plus Laravel integration points.

## Task Memory Policy
- After significant completed tasks, update `docs/implementation-log.md` with date, task summary, repos/files touched, decisions, checks run, and follow-ups.
- Significant means: feature work, security review, API contract change, payment/auth/upload work, database/provider changes, release readiness review, or fixes touching more than one repo.
- Do not log tiny read-only questions, typo fixes, or temporary exploration.
- Add reusable future rules to `AGENTS.md`; add task-specific notes to `docs/implementation-log.md`.
- Keep memory entries caveman-lite: short, factual, no long narrative.
