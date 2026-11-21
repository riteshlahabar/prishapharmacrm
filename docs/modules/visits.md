# Module: Visits        Status: ⬜ pending (0/4) — Phase 3
Paths: `app/Modules/Visits/` · Tables: `visits`

## Functions
| Function | File | Status |
|---|---|---|
| migration + model | `Database/Migrations/`, `Models/Visit.php` | ⬜ |
| save visit + capture geo | `Services/VisitService.php::create` | ⬜ |
| validation (out ≥ in time) | `Http/Requests/StoreVisitRequest.php` | ⬜ |
| admin visit report | `Http/Controllers/Web/VisitController.php` | ⬜ |

## Notes
MR fills: person, visit purpose, in-time, out-time. On save the app sends
lat/lng; backend stores them plus `location_fetched_at` and the server receive
time, so the record shows both device and server time.

## Next
Migration, then `StoreVisitRequest` + service.
