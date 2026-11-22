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
**All phases 0–10 ✅** — backend, panel, both Flutter apps, hardening.
Gate before any commit: `bash tests/smoke/phase8-suite.sh`.

## Session log
Phases 0–6 (foundation through the mobile API v1 contract) are rotated out —
  see `docs/history/2026-11.md` for the index and one file per phase. Admin
  login is `ADMIN_*`; `users:set-admin` resets it. The API contract itself is
  `docs/api/v1-common.md`.
`2026-11-22` Phases 7, 9 and 10 (dashboards, MR app, telecaller app) are
  rotated out — see `docs/history/2026-11-phase{7,9,10}.md`.
`2026-11-22` Phase 8: hardening. 284 feature tests replace Laravel's stock
  example (which failed, since `/` is behind `auth`): one file per module
  surface, plus `ApiContractTest` for what every list shares. Module-owned
  factories wired by `#[UseFactory]`; `Tests\TestCase` seeds the Access
  seeders, since the panel gates on permissions. Found and fixed three real
  holes: the **attendance** panel report was the only list not clamped, so a
  telecaller granted `attendance.view` read every rep in the company;
  **persons** `show`/`edit` never asked `PersonPolicy::view`, making the
  list's region clamp cosmetic; region `code` and user `email` were
  normalised *after* validating, so `unique` tested a different string than
  the one stored (a 500 on a case-sensitive collation). Also fixed the
  persons `down()`, which only rolled back on MySQL. `DemoSeeder` builds a
  demo world and refuses to run in production. Pint clean.
  Details in `docs/testing.md`.

## Next step
Feature work is done. Before a release: neither Flutter app has run on real
hardware, and both keep their token in `SharedPreferences` — see each app's
README. Panel filter dropdowns (MR/region pickers on every report) list all
options regardless of the viewer's territory; the rows are clamped, so this
is a name-only leak to a scoped telecaller, left as-is deliberately.

## Open items
- ProKit (Medilab) lives in the MR app repo under `design/`, excluded from
  analysis. Its palette (`#1157FA`) and card shapes are in both apps' themes;
  its nb_utils + MobX code is not — we took the design, not the state layer.
- Cuba needs no upload: its CSS was always complete. Markup contract in
  `docs/ui-cuba.md`, load order and logos in `docs/ui-assets.md`.
- Logos are still stock Cuba artwork. Dropping Prisha files over
  `public/assets/images/logo/*.png` rebrands panel + login; no markup change.
- Panel admin comes from `ADMIN_EMAIL` / `ADMIN_PASSWORD`. The `.env.example`
  values are deliberately weak dev defaults so local setup is one command.
  **Change them before anything is internet-reachable**; reset an existing
  admin with `php artisan users:set-admin <email>` (the seeder will not).
- cPanel MySQL deliberately **not** connected: ephemeral container IP, port
  3306 blocked, production data at risk. Dev runs on in-container MariaDB.
- `AGENTS.md` still holds the Laravel Boost bootstrap stub. Boost was **not**
  installed — `boost:install` would overwrite this file and break the
  100-line session-log requirement. Ask before installing.
- A `customers` table converted from a live deployment arrives with every row
  typed `doctor`. Re-type hospitals and medical stores by hand after deploy.

## Working rules
- Dev branch: `claude/happy-ramanujan-zthjt1` (all three repos). Never push
  elsewhere without asking. No PR unless asked.
- A commit touching a module updates that module's status table in
  `docs/modules/<name>.md` **and** adds one line to the session log above.
- Each `docs/**` file ≤ 2 KB; split rather than grow.
