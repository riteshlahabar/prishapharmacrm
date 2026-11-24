# Roadmap — phases

Decisions: customers → persons; `spatie/laravel-permission`; MySQL; docs on
the code branch.

| # | Phase | Scope | Status |
|---|---|---|---|
| 0 | Foundation | Sanctum + spatie, `app/Core`, module skeletons, `config/modules.php`, docs | ✅ |
| 1 | Auth, roles, users | 3 roles + permissions, admin CRUD for MR + telecaller, scoped sidebar | ✅ |
| 2 | Masters | regions CRUD + `telecaller_region`; `customers`→`persons` with region/MR/telecaller assignment | ✅ |
| 3 | MR backend | attendance check-in/out + geo, visits, admin lists | ✅ |
| 4 | Telecaller backend | region-scoped client list; call logs with status, notes, follow-up | ✅ |
| 5 | Messaging | `messages` telecaller → MR, no Firebase: unread count, list, mark-read | ✅ |
| 6 | Mobile API v1 | versioned endpoints, Resources, policies, one envelope | ✅ |
| 7 | Dashboards | admin charts (visits, attendance, calls, conversion), scoped for a telecaller | ✅ |
| 8 | Hardening | feature tests, demo seeders, Pint; gate `tests/smoke/phase8-suite.sh` | ✅ |
| 9 | MR app | check-in/out home, visit form + geolocator, inbox | ✅ |
| 10 | Telecaller app | region client list, call + status/notes, send-to-MR | ✅ |
| 11 | Reports | 6 reports (MR, telecaller, coverage, region, register, follow-ups) + CSV, no new tables | ✅ |
| 12 | Branding | Prisha logo + favicon; panel repainted teal/green; clients + client types; splash, icon and light theme in both apps | ✅ |
| 13 | Products | product master (name, box qty, cost), shared `/products`, optional product lines on a visit and a call in both apps | ✅ |

## Blockers
- No device or emulator here: the apps are verified by `flutter analyze`,
  their tests and a web build driven headless — not a run on real hardware.
  Open them on a phone before sign-off.

## Dropped from scope
The generic CRM's `products` and `orders` went in Phase 2; `customers`
became `persons` by rename, so rows survived. Phase 13's `products` is a
new table of our own, unrelated to the old one.

