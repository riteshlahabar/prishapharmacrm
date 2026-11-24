# Roadmap — phases

Decisions: customers → persons; `spatie/laravel-permission`; MySQL; docs on
the code branch.

| # | Phase | Scope | Status |
|---|---|---|---|
| 0 | Foundation | Sanctum + spatie, `app/Core`, module skeletons, `config/modules.php`, docs | ✅ |
| 1 | Auth, roles, users | 3 roles + permissions, admin CRUD, scoped sidebar | ✅ |
| 2 | Masters | regions CRUD + `telecaller_region`, `customers`→`persons` | ✅ |
| 3 | MR backend | attendance check-in/out + geo, visits, admin lists | ✅ |
| 4 | Telecaller backend | region-scoped client list, call logs with status, notes, follow-up | ✅ |
| 5 | Messaging | `messages` telecaller → MR, no Firebase: unread count, list, read | ✅ |
| 6 | Mobile API v1 | versioned endpoints, Resources, policies, one envelope | ✅ |
| 7 | Dashboards | admin charts (visits, attendance, calls, conversion), scoped per viewer | ✅ |
| 8 | Hardening | feature tests, demo seeders, Pint, `phase8-suite.sh` | ✅ |
| 9 | MR app | check-in/out home, visit form + geolocator, inbox | ✅ |
| 10 | Telecaller app | region client list, call + status/notes, send-to-MR | ✅ |
| 11 | Reports | 6 reports (rep, caller, coverage, region, register, follow-ups) + CSV | ✅ |
| 12 | Branding | logo, favicon, teal/green panel, client types, app splash + light theme | ✅ |
| 13 | Clients from an app | `POST /persons` + assignee picker, My Clients in both apps | ✅ |
| 14 | Products | product master, shared `/products`, optional lines on a visit and a call | ✅ |
| 15 | Visit photo | shopfront photo per visit (private disk, scoped) + OSM map in the view | ✅ |

## Blockers
- No device or emulator here: the apps are verified by `flutter analyze`
  and their tests, not real hardware (and no web platform is configured,
  so that build is out too). Open them on a phone before sign-off.

## Dropped from scope
The generic CRM's `products` and `orders` went in Phase 2; `customers`
became `persons` by rename, so rows survived. Phase 14's `products` is new,
unrelated to the old one.

