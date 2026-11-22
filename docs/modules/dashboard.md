# Module: Dashboard        Status: ✅ done (2/2) — Phase 7
Paths: `app/Modules/Dashboard/` · Tables: none (reads others)

## Functions
| Function | File | Status |
|---|---|---|
| read model for the screen | `Services/DashboardService.php` | ✅ |
| one route, one view, every role | `Http/Controllers/Web/DashboardController.php` | ✅ |

## Notes
Owns no table and no repository: every number comes from another module's
**service contract**, through a `…ForUser` method, so the scope is clamped
where the rule lives and a tile always matches the screen it links to.

One template serves admin and telecaller. The service asks each module's
**policy** (`viewAny`) which cards the viewer may have, so there is no role
test in the controller or the Blade — a telecaller gets persons, calls,
conversion and messages; an admin also gets attendance and visits. A card
nobody may see costs no queries. (It names sibling *models* only as the
subject of a Gate check, never to read from.)

Charts are ApexCharts 3.46.0, already committed under
`public/assets/js/chart/apex-chart/`. Cuba's `chart-custom.js` beside it is
demo data bound to demo element ids and is deliberately not loaded. Donut
colours come from `CallStatus::badge()`, so a slice matches the badge the
same status wears on the call report.

`?days` takes 7, 30 or 90 and falls back to 30; the range is zero-filled so a
quiet week reads as zeros rather than closing up.

## Next
Phase 8 hardening. A mobile summary endpoint is **not** built: neither app
asks for one — the MR app opens on check-in, the telecaller app on its queue.
