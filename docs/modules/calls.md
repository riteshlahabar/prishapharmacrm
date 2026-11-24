# Module: Calls        Status: ✅ done (5/5) — Phases 4, 6
Paths: `app/Modules/Calls/` · Tables: `call_logs`, `call_log_products`

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

| product lines (optional) | `Models/CallLogProduct.php` | ✅ |

## Notes
A call may carry product lines as a visit does, priced through the
**Products contract** (`api/v1-products.md`). On a PATCH an absent
`products` leaves them alone; `[]` clears them.

Status: interested, not_interested, callback, not_reachable, converted.
`called_at` is server-set like the attendance stamps, so work cannot be
back-dated. Calling one person twice a day is normal — no uniqueness.

**Only `callback` carries a `follow_up_at`**, and it must be in the future;
a date on any other status is rejected, so nothing sits in the queue with
nothing to close it. A newer call on that person closes any call-back still
open, and `follow-up-done` closes one without logging a call.

`person_id` is validated against the **caller's own regions**, not merely
`exists`: the app only shows a scoped queue, so anything else is a tampered
payload. No regions assigned means no one is callable.

Scope is the **caller, not the territory** — unlike visits. A telecaller's
notes are their own working record, so `paginateForUser`, `summaryForUser`
and `findForUser` clamp to `telecaller_id`; a colleague's call reads 404.

## Next
Done. `CallLogPolicy::update` stays owner-only — an admin reads but does
not rewrite.
