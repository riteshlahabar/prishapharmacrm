# Roadmap — phases

Decisions: customers → persons (products/orders dropped);
`spatie/laravel-permission`; MySQL; docs on the code branch.

| # | Phase | Scope | Status |
|---|---|---|---|
| 0 | Foundation | composer install, MySQL `.env`, Sanctum + spatie, `app/Core` base classes, module skeletons, `config/modules.php`, docs | ✅ |
| 1 | Auth, roles, users | 3 roles + permissions, admin CRUD for MR + telecaller, `auth` on web routes, scoped sidebar, Sanctum login | ✅ |
| 2 | Masters | regions CRUD + `telecaller_region`; `customers`→`persons` with region/MR/telecaller assignment; products/orders dropped | ✅ |
| 3 | MR backend | attendance check-in/out + geo; visits; admin lists | ✅ |
| 4 | Telecaller backend | region-scoped client list; call logs with status, notes, follow-up | ✅ |
| 5 | Messaging | `messages` telecaller → MR (no Firebase): unread count, list, mark-read | ✅ |
| 6 | Mobile API v1 | versioned endpoints, Resources, policies, one envelope, in `api/` | ✅ |
| 7 | Dashboards | admin charts (visits, attendance, calls, conversion); scoped for a telecaller | ✅ |
| 8 | Hardening | feature tests, demo seeders, Pint; gate: `tests/smoke/phase8-suite.sh` (`testing.md`) | ✅ |
| 9 | MR app | ProKit UI, check-in/out home, visit form + geolocator, inbox | ✅ |
| 10 | Telecaller app | ProKit UI, region client list, call + status/notes, send-to-MR | ✅ |
| 11 | Reports | 6 reports (MR, telecaller, coverage, region, register, follow-ups) + CSV; own module, no new tables | ✅ |
| 12 | Branding | Prisha logo + favicon; panel repainted teal/green; clients + editable client types; splash, icon and light theme in both apps | ✅ |

## Blockers
- No device or emulator in the build container: the Flutter apps are verified
  by `flutter analyze` and a web build driven in a headless browser, not a
  run on real hardware. Open Phase 9 on a phone before sign-off.

## Dropped from scope
`products`, `orders` — removed in Phase 2. `customers` became `persons` by
rename, so rows survived.

