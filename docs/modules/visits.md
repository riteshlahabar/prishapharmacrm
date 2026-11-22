# Module: Visits        Status: ✅ done (6/6) — Phases 3, 6
Paths: `app/Modules/Visits/` · Tables: `visits`

## Functions
| Function | File | Status |
|---|---|---|
| migration + model | `Database/Migrations/`, `Models/Visit.php` | ✅ |
| save visit + capture geo | `Services/VisitService.php::createFor` | ✅ |
| validation (out ≥ in time) | `Http/Requests/StoreVisitRequest.php` | ✅ |
| admin visit report + detail | `Http/Controllers/Web/VisitController.php` | ✅ |
| app endpoints | `Http/Controllers/Api/VisitController.php` | ✅ |
| policy (territory scope) | `Policies/VisitPolicy.php` | ✅ |

## Notes
MR fills: person, purpose (`VisitPurpose` enum), in-time, out-time. On save the
app sends lat/lng; the backend stores them plus `location_fetched_at` and keeps
`created_at` as the server receive time, so a visit entered hours late shows as
one. `mr_id` comes from the token, never the payload.

The rule now lives in `VisitPolicy`; the service translates a denial into
404. Scoping stays in the service, not the controller: `paginateForUser`,
`summaryForUser` and `findForUser` all clamp a non-admin to the regions they
cover — list, counters and detail alike. A visit outside them reads as **404**,
not 403, since a 403 would confirm it exists. `destroy` resolves through the
same check.

## Next
Phase 7 charts visits per MR, and moves the panel's own checks onto
`VisitPolicy` (Phase 6 wired it for the API path only).
