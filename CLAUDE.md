# Development Workflow - CarRental Project

> **Last Updated:** 2026-02-18
> **Project:** Laravel 10 + Vue.js 3 Car Rental Platform

## Overview

This workflow ensures consistent, high-quality development across the CarRental codebase. Follow these 13 steps for all code changes, with exceptions for read-only queries.

**Core Principle:** Guideline mode - follow this workflow but remain flexible when appropriate.

---

## The 13-Step Workflow

### 1. Prompt Engineering (ALWAYS)
**Skill:** `prompt-engineering-patterns`
**When:** Before executing ANY user prompt
**Action:** Load skill, optimize the prompt, then execute

### 2. Brainstorming (Creative Work)
**Skill:** `superpowers:brainstorming`
**When:** Before ANY creative work (new features, components, behavior changes)
**Action:** Explore context, ask questions, propose approaches, get approval

### 3. Karpathy Guidelines (Code Writing)
**Skill:** `karpathy-guidelines`
**When:** Before writing or editing ANY code
**Action:** Follow guidelines for surgical changes, minimal assumptions, verifiable success

### 4. Domain Skills (Context-Specific)
**Skills by file type:**
- `*.vue` → `vue-best-practices` (Composition API with `<script setup>`)
- Laravel PHP → `laravel-specialist`
- MySQL migrations → `mysql`
- API design → `backend-development:api-design-principles`
- Frontend UI → `frontend-design`

### 5. Code Search (LSP-Based)
**Tools:** LSP for JavaScript, LSP for PHP
**When:** Searching code for symbols, references, patterns
**Action:** Use semantic code search, not text search

### 6. Error Handling (After Code Changes)
**Skill:** `error-handling-patterns`
**When:** After editing or writing code
**Action:** Verify all edge cases handled, proper error messages, graceful failures

### 7. Type Check & Lint
**Tools:**
- Frontend: `npm run type-check`, `npm run lint`
- Backend: `php artisan code:analyse` (if available), Laravel Pint
**When:** After code changes

### 8. MySQL Best Practices
**Skill:** `mysql` (for migrations only)
**When:** Creating or modifying database migrations
**Action:** Follow MySQL patterns for indexes, constraints, data types

### 9. Documentation Research (Ref + Exa MCP)
**Tools:** `ref_search_documentation`, `exa` MCP
**When:** Implementing new features, debugging, using new APIs
**Action:** Gather latest info from both sources BEFORE implementation

### 10. Browser Testing
**Skill:** `agent-browser` or `superpowers-chrome:browsing`
**When:** After all changes complete
**Action:** End-to-end browser testing of the feature

### 11. Git Commit
**When:** After all changes verified by user who operates claude
**Action:** Commit with descriptive message following project conventions

### 12. Codex Verification (Optional)
**Skill:** `codex` with GPT-5.3
**When:** For complex features or second model verification
**Action:** Run codex for plan approval and verification

### 13. Verification Before Completion
**Skill:** `superpowers:verification-before-completion`
**When:** Before claiming work is complete
**Action:** Run verification commands, confirm output, evidence before assertions

---

## Exceptions (Skip Workflow)

**Read-Only Queries - Safe to skip:**
- Reading files (Read tool)
- Searching code (Grep, Glob, LSP)
- Listing directory contents
- Viewing git status/logs

**Already-Loaded Skills:**
- Check if skill invoked in last 5 turns
- Skip redundant loading
- Exception: Re-invoke `superpowers:brainstorming` for NEW creative work

**Simple Clarifications:**
- Answering "what is X" questions
- Explaining existing code
- Providing project context

---

## Skill Loading Priority

When multiple skills apply, load in this order:

1. `superpowers:brainstorming` - Before creative work
2. `prompt-engineering-patterns` - Before executing prompts
3. `karpathy-guidelines` - Before writing code
4. Domain-specific skills (Vue, Laravel, MySQL, etc.)
5. `error-handling-patterns` - After code changes
6. `superpowers:verification-before-completion` - Before claiming done

---

## Project-Specific Conventions

### Laravel + Vue.js Structure

**Backend (Laravel):**
- `app/Http/Controllers/` - Controllers
- `app/Models/` - Eloquent models
- `database/migrations/` - Database migrations
- `routes/web.php` - Web routes
- `routes/api.php` - API routes

**Frontend (Vue.js):**
- `resources/js/Pages/` - Page components
- `resources/js/Components/` - Reusable components
- `resources/js/Layouts/` - Layout components
- Inertia.js for Laravel-Vue bridge

**Testing:**
- `tests/Feature/` - Feature tests
- `tests/Unit/` - Unit tests
- PHPUnit for backend, Vitest for frontend

### Common Commands

```bash
# Backend
php artisan migrate:fresh --seed
php artisan test
php artisan route:list
composer dump-autoload

# Frontend
npm run dev
npm run build
npm run type-check
npm run lint

# Git
git status
git add .
git commit -m "feat: description"
```

---

## Quick Reference

**For New Feature:**
1. brainstorming → Design → approve
2. writing-plans → Implementation plan
3. executing-plans → Build with TDD
4. verification-before-completion → Confirm
5. Git commit

**For Bug Fix:**
1. systematic-debugging → Diagnose
2. karpathy-guidelines → Fix
3. error-handling-patterns → Validate
4. verification-before-completion → Confirm
5. Git commit

**For Vue Component:**
1. frontend-design → UI design
2. vue-best-practices → Implementation
3. agent-browser → Test
4. Git commit

---

## Additional Resources

- **Project Summary:** `PROJECT_SUMMARY.md`
- **Bug Prevention:** `docs/bug-prevention/README.md`
- **Design Documents:** `docs/plans/`
