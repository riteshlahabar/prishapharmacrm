# Module: Visits        Status: ✅ done (8/8) — Phases 3, 6, 15
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
| shopfront photo | `Services/VisitPhotoStore.php` | ✅ |

## Notes
MR fills: person, purpose (`VisitPurpose` enum), in-time, out-time, and may
attach one photo of the place. On save the app sends lat/lng; the backend
stores them plus `location_fetched_at` and keeps `created_at` as the server
receive time, so a visit entered hours late shows as one. `mr_id` comes from
the token, never the payload.

Product lines are priced through the **Products contract** so the cost is
snapshotted, never joined (`api/v1-products.md`). Posting replaces the set.

Scoping lives in the service, not the controller: `paginateForUser`,
`summaryForUser` and `findForUser` all clamp a non-admin to the regions they
cover. A visit outside them reads as **404**, not 403 — a 403 would confirm
it exists. The rule itself is in `VisitPolicy`, so panel and API cannot drift.

## Photo and map — `visit-photos.md`
Private disk, served by `VisitPhotoController` behind the same scope; the
detail page draws a keyless OpenStreetMap frame. Both in that doc.
