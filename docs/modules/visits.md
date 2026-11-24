# Module: Visits        Status: ✅ done (6/6) — Phases 3, 6
Paths: `app/Modules/Visits/` · Tables: `visits`, `visit_products`

## Functions
| Function | File | Status |
|---|---|---|
| migration + model | `Database/Migrations/`, `Models/Visit.php` | ✅ |
| save visit + capture geo | `Services/VisitService.php::createFor` | ✅ |
| validation (out ≥ in time) | `Http/Requests/StoreVisitRequest.php` | ✅ |
| admin visit report + detail | `Http/Controllers/Web/VisitController.php` | ✅ |
| app endpoints | `Http/Controllers/Api/VisitController.php` | ✅ |
| policy (territory scope) | `Policies/VisitPolicy.php` | ✅ |
| trend + per-rep counts | `Repositories/EloquentVisitRepository.php` | ✅ |

| product lines (optional) | `Models/VisitProduct.php` | ✅ |

## Notes
A visit may carry product lines — what was detailed, and how many boxes.
Priced through the **Products contract** so the cost is snapshotted, never
joined (`api/v1-products.md`). Posting replaces the set; the panel's visit
detail lists them.

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
Phase 8 hardening. `trendForUser` and `countsByMrForUser` feed the dashboard;
only the clamped forms are exposed, so a caller cannot forget to scope one.
