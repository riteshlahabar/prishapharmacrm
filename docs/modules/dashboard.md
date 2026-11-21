# Module: Dashboard        Status: ⬜ pending (0/3) — Phase 7
Paths: `app/Modules/Dashboard/` · Tables: none (reads others)

## Functions
| Function | File | Status |
|---|---|---|
| admin dashboard (Cuba charts) | `Http/Controllers/Web/DashboardController.php` | ⬜ |
| telecaller dashboard (scoped) | same, permission-gated | ⬜ |
| app dashboard summary API | `Http/Controllers/Api/DashboardController.php` | ⬜ |

## Notes
Reads through other modules' **service contracts** only — no direct queries
into their tables. Metrics: today's attendance, visits/period, calls by status,
conversion rate, per-region breakdown.
Legacy `App\Http\Controllers\DashboardController` to be replaced here.

## Next
Wait for Phases 3–5 data, then build read models.
