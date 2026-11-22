# Prisha Pharma CRM — Agent Memory

Keep this file **under 100 lines**. Rotate old log lines into
`docs/history/YYYY-MM.md`. Verify with `bash docs/check-docs.sh`.

## Start here
`docs/index.md` is the router: need → doc → code path. Read it before
searching the codebase. Phases live in `docs/roadmap.md`.

## Stack & conventions
- Laravel 13.43 · PHP 8.3 · MySQL (MariaDB 10.11 in-container for dev)
- Cuba admin theme, committed at `public/assets` (144 MB, tracked)
- Auth: session for the panel, Sanctum bearer tokens for the two Flutter apps
- Roles: `spatie/laravel-permission` 8.5 — `admin`, `mr`, `telecaller`
  (`mr` is app-only; `admin` + `telecaller` reach the panel)
- Modular monolith: `app/Modules/<Name>/`, registered in `config/modules.php`
- Shared base classes in `app/Core/` (repository, API envelope, module provider)
- Full rules in `docs/conventions.md`. Follow SOLID: controller → service →
  repository, contracts type-hinted, no queries outside repositories.

## Project scope (one line)
Admin adds persons (doctor/hospital/medical), regions, MRs and telecallers.
MR app: check-in/out presence + visit entry, both geo-stamped. Telecaller app:
region-scoped person list, call status + notes, and messages to an MR —
in-app, **no Firebase**. Telecallers also get scoped panel access.

## Current phase
Phases 0–6 ✅ (foundation · auth/roles/users · masters · MR backend ·
telecaller backend · messaging · mobile API v1). **Phase 7 next:** dashboards.

## Session log
Phases 0–3 (foundation, auth/roles/users, masters, MR backend) are rotated
  out — see `docs/history/2026-11.md` for the index and one file per phase.
  Admin login comes from `ADMIN_*`; `users:set-admin` resets it.
`2026-11-22` Phase 4: `call_logs` + `CallStatus`, call-back queue with an
  explicit `follow_up_done_at`, six telecaller endpoints, admin call report.
  `/api/v1/persons` shipped early (role-scoped) so the app has a queue; each
  row carries `last_call` via the Calls contract. Calls scope on the caller,
  not the region — unlike visits. 76 smoke + 35 panel checks green.
  Details in `docs/history/2026-11-phase4.md`.
`2026-11-22` Phase 5: `messages` telecaller → MR, no Firebase. Read state is
  `read_at` alone (planned `is_read` dropped, derived in the API); the poll is
  one indexed COUNT; marking is idempotent and recipient-only. Recipients are
  MRs in your regions plus the named person's own rep. Panel scope is either
  party. 72 smoke checks green.
  Details in `docs/history/2026-11-phase5.md`.
`2026-11-22` Phase 6: one API contract. `ApiExceptionRenderer` puts every
  thrown failure in the envelope with a stable `errors.reason` (401/422 had
  Laravel's own shape, 404 leaked the model class, 404/405 a stack trace).
  `PerPage` clamps page size 1–100 — `per_page=-5` was a 500 from raw SQL,
  `100000` was honoured. Filled `user.regions[]`, `[]` since Phase 2. Tokens
  now die with their account (`api.auth`): deactivating an MR used to leave
  theirs filing visits. Policies per module via a `policies()` provider hook;
  services delegate row checks, so one rule reads 403 in the panel and 404 on
  the API. 102 smoke checks green — `tests/smoke/phase6-api.sh`.
  Details in `docs/history/2026-11-phase6.md`.

## Next step
Phase 7 (dashboards): admin charts for visits, attendance, calls and
conversion, plus a region-scoped telecaller dashboard — reading through
service contracts only, and moving the panel's own checks onto the Phase 6
policies. `app/Http/Controllers/DashboardController` moves into the module.

## Open items
- ProKit (Medilab) **received** as a 2.2 MB zip: 123 dart files + 68 images,
  primary `#1157FA`, built on nb_utils + MobX (we use Riverpod — harvest the
  design, not the state layer). Persisted in the MR app repo under `design/`.
- Cuba needs no upload: its CSS was always complete. Markup contract in
  `docs/ui-cuba.md`, load order and logos in `docs/ui-assets.md`.
- Logos are still stock Cuba artwork. Dropping Prisha files over
  `public/assets/images/logo/*.png` rebrands panel + login; no markup change.
- Panel admin comes from `ADMIN_EMAIL` / `ADMIN_PASSWORD`. The `.env.example`
  values are deliberately weak dev defaults so local setup is one command.
  **Change them before anything is internet-reachable**; reset an existing
  admin with `php artisan users:set-admin <email>` (the seeder will not).
- cPanel MySQL deliberately **not** connected: ephemeral container IP, port
  3306 blocked by the egress proxy, and production data at risk. Dev runs on
  in-container MariaDB; `.env.example` is cPanel-shaped for deploy.
- `AGENTS.md` still holds the Laravel Boost bootstrap stub. Boost was **not**
  installed — `boost:install` would overwrite this file and break the
  100-line session-log requirement. Ask before installing.
- `app/Http/Controllers` now holds only the placeholder `DashboardController`;
  Phase 7 moves it into the Dashboard module.
- A `customers` table converted from a live deployment arrives with every row
  typed `doctor`. Re-type hospitals and medical stores by hand after deploy.

## Working rules
- Dev branch: `claude/happy-ramanujan-zthjt1` (all three repos). Never push
  elsewhere without asking. No PR unless asked.
- A commit touching a module updates that module's status table in
  `docs/modules/<name>.md` **and** adds one line to the session log above.
- Each `docs/**` file ≤ 2 KB; split rather than grow.
