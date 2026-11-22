# Roadmap — phases

Decisions: customers → persons (products/orders dropped);
`spatie/laravel-permission`; MySQL; docs on the code branch.

| # | Phase | Scope | Status |
|---|---|---|---|
| 0 | Foundation | composer install, MySQL `.env`, Sanctum + spatie installed & migrated, `app/Core` base classes, 10 module skeletons, `config/modules.php`, docs + CLAUDE.md | ✅ |
| 1 | Auth, roles, users | 3 roles + 30 permissions, user columns, admin CRUD for MR + telecaller, `auth` on web routes, scoped sidebar, Sanctum login | ✅ |
| 2 | Masters | regions CRUD + `telecaller_region`; `customers`→`persons` with region/MR/telecaller assignment + filters; products/orders dropped | ✅ |
| 3 | MR backend | attendance check-in/out + geo; visits; admin lists | ✅ |
| 4 | Telecaller backend | region-scoped person list; call logs with status, notes, follow-up; scoped panel | ✅ |
| 5 | Messaging | `messages` telecaller → MR (no Firebase): unread count, list, mark-read; admin view | ✅ |
| 6 | Mobile API v1 | versioned endpoints, Resources, policies, one envelope, documented in `api/` | ✅ |
| 7 | Dashboards | admin charts (visits, attendance, calls, conversion); scoped telecaller dashboard | ✅ |
| 8 | Hardening | 284 feature tests, demo seeders, Pint, docs; gate: `tests/smoke/phase8-suite.sh` (see `testing.md`) | ✅ |
| 9 | MR app | ProKit UI, check-in/out home, visit form + geolocator, inbox | ✅ |
| 10 | Telecaller app | ProKit UI, region person list, call + status/notes, send-to-MR | ✅ |
| 11 | Reports | 6 reports (MR, telecaller, coverage, region, register, follow-ups) + CSV; own module, no new tables | ✅ |

## Blockers
- No device or emulator in the build container: the Flutter apps are verified
  by `flutter analyze`, tests and golden renders, not a run on real hardware.
  Open Phase 9 on a phone before sign-off.

## Dropped from scope
`products`, `orders` — removed in Phase 2, and the MR app's screens for them
in Phase 9. `customers` became `persons` by rename, so rows survived.

