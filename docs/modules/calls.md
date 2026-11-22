# Module: Calls        Status: ✅ done (5/5) — Phases 4, 6
Paths: `app/Modules/Calls/` · Tables: `call_logs`

## Functions
| Function | File | Status |
|---|---|---|
| migration + `CallStatus` enum | `Database/Migrations/`, `Enums/CallStatus.php` | ✅ |
| log a call (status + notes) | `Services/CallLogService.php::logFor` | ✅ |
| policy (caller-owned rows) | `Policies/CallLogPolicy.php` | ✅ |
| daily trend for the chart | `Repositories/EloquentCallLogRepository.php` | ✅ |
| region-scoped person queue | `Persons` API + `lastCallsFor` | ✅ |
| follow-up list | `Http/Controllers/Api/CallLogController.php` | ✅ |
| admin call report + detail | `Http/Controllers/Web/CallLogController.php` | ✅ |

## Notes
Status enum: interested, not_interested, callback, not_reachable, converted.
`called_at` is server-set like the attendance stamps, so work cannot be
back-dated. Calling the same person twice a day is normal — no uniqueness.

**Only `callback` carries a `follow_up_at`**, and it must be in the future;
the request rejects a date on any other status, so nothing can sit in the
queue with nothing to close it. Logging a newer call on that person closes any
call-back still open, and `follow-up-done` closes one without logging a call.

`person_id` is validated against the **caller's own regions**, not merely
`exists`: the app only ever shows a scoped queue, so anything else is a
tampered payload. No regions assigned means no one is callable.

Scope is the **caller, not the territory** — unlike visits. A telecaller's
notes are their own working record, so `paginateForUser`, `summaryForUser` and
`findForUser` clamp to `telecaller_id`, and a colleague's call reads as 404.

## Next
Phase 8 hardening. Conversion and the status donut are on the dashboard;
`CallLogPolicy::update` stays owner-only — an admin reads but does not rewrite.
