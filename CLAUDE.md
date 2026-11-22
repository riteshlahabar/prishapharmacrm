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
Phases 0–5 ✅ (foundation · auth/roles/users · masters · MR backend ·
telecaller backend · messaging). **Phase 6 next:** mobile API v1 polish.

## Session log
Phases 0–2 (foundation, auth/roles/users, masters) are rotated out — see
  `docs/history/2026-11.md` for the index and one file per phase. Admin login
  comes from `ADMIN_*`; `users:set-admin` resets it.
`2026-11-22` Phase 3: `attendances` + `visits` with server-set times, 409
  sequence guards, four MR endpoints behind `ability:role:mr`, admin reports.
  Closed a scoping hole: visit summary/show/destroy now clamp to the caller's
  regions. Fixed three Cuba JS bugs that threw on every detail page — see
  `docs/ui-theme-patches.md`. 62-check smoke run green.
  Details in `docs/history/2026-11-phase3.md`.
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

## Next step
Phase 6 (mobile API v1): the endpoints exist — this pass adds policies, a
check that every list is scoped and paginated the same way, consistent 404 vs
403, and the `api/` docs as the contract the two Flutter apps build against.
Then Phase 7 dashboards, which read through service contracts only.

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
