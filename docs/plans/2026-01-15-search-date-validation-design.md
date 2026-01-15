# Search Date Validation Design

## Goals
- Prevent past dates from being applied via URL query params or manual edits.
- Centralize date validation logic with explicit, user-friendly errors.
- Keep existing UI patterns and styling; show inline error near the date picker.
- Avoid watcher feedback loops when sanitizing dates and syncing URL state.

## Non-Goals
- Redesign SearchBar or change overall layout.
- Add new global toast/banner components.
- Expand validation beyond past dates and basic date validity.

## Current Behavior (Root Cause)
`date_from` and `date_to` are accepted directly from `usePage().props.filters` and
from `handleSearchUpdate` without validation. As a result, past dates from URL
params or manual edits become state and UI, and are re-synced to the URL.

## Proposed Design
Introduce a small validation helper and a single application path:
- `ValidationError` typed error (name + message + optional field).
- `validateSearchDates({ date_from, date_to })` returns Result-like object:
  `{ ok: true, value }` or `{ ok: false, error }`.
- `applyValidatedDates({ date_from, date_to }, source)` applies valid values,
  or reverts invalid ones to last-known-good (fallback to today only if none).

Validation rules:
- Parse dates as calendar dates; reject invalid formats.
- Compare to today at start-of-day; fail fast on any past date.
- Produce a clear message: "Dates can't be in the past. Please choose today or later."

## Data Flow and UI
- On initial load and subsequent query param changes, pass
  `usePage().props.filters.date_from/date_to` to `applyValidatedDates`.
- On SearchBar updates, route through the same `applyValidatedDates`.
- Store last valid dates in `lastValidDates` so invalid attempts do not clobber
  state; fallback to today only if there is no last valid value.
- Show inline error message directly under the SearchBar in the header.

## Loop Avoidance
- Guard with `isSanitizingDates` to prevent re-entrant watcher loops.
- Only write to form when sanitized values differ from current values.

## Testing
If a JS unit test harness exists (e.g., Vitest/Jest), add tests for:
- Past dates rejected and revert to last valid / today on first invalid.
- Invalid date formats rejected with the correct error message.
- Valid dates applied and clear the error.
If no harness is present, confirm whether to waive TDD for this change.
