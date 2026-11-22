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
Phases 0–7, 9 and 10 ✅ — backend, panel, both Flutter apps.
**Only Phase 8 (hardening) is left.**

## Session log
Phases 0–6 (foundation through the mobile API v1 contract) are rotated out —
  see `docs/history/2026-11.md` for the index and one file per phase. Admin
  login is `ADMIN_*`; `users:set-admin` resets it. The API contract itself is
  `docs/api/v1-common.md`.
`2026-11-22` Phase 7: dashboards. One template for every role — the service
  asks each module's policy `viewAny` which cards the viewer gets (admin six
  tiles/four charts, telecaller four/two), so no role test in the controller
  or Blade. Reads only `…ForUser` methods on sibling contracts, so the clamp
  stays with the module; found attendance had no `region_scope`, which would
  have charted every rep to a scoped viewer. Added daily trends, visits per
  rep and two counts. ApexCharts 3.46.0 (already committed; Cuba's demo
  `chart-custom.js` not loaded). The dashboard moved into its module, so
  `routes/web.php` is route-free. 56 panel checks green.
  Details in `docs/history/2026-11-phase7.md`.
`2026-11-22` Phase 9: MR app rebuilt on the API v1 contract. What was there
  was a generic-CRM scaffold (customers/orders/products) on endpoints deleted
  in Phase 2, and it did not compile — 4,548 analyzer issues. Now four
  screens: login, geo check-in/out, visit form + history, inbox. `ApiClient`
  decodes the envelope once and throws a typed `ApiFailure`; screens switch
  on `errors.reason`. Flutter moved Material out of the SDK, so lib/ imports
  `package:material_ui` and the UI deps are on migrated versions. Fixed three
  device bugs: INTERNET was debug-manifest only (release could not reach the
  API), no location permissions, no iOS usage string. Analyze clean, 40
  tests, fixtures captured live. `docs/history/2026-11-phase9.md`.
`2026-11-22` Phase 10: telecaller app, same shape as the MR one. Its
  scaffold was dead *and* would not install (`flutter_riverpod: ^2.61.0`
  never existed). Queue, log-a-call, call-backs, sent. The queue is one
  request: `/persons` is region-scoped and each row carries `last_call`.
  `CallStatus.needsFollowUp` mirrors the server's one awkward rule —
  `follow_up_at` required on `callback`, refused otherwise — and the
  repository tests assert the body. Package ids are real now:
  `com.prishapharma.mr` / `.telecaller`. Analyze clean, 34 tests (MR still
  40). `docs/history/2026-11-phase10.md`.

## Next step
Phase 8 (hardening) is all that remains: `tests/smoke/` covers the contract
end-to-end, but `tests/Feature` is Laravel's stock example and fails because
`/` is behind `auth`. Then demo seeders and a docs refresh. Before any
release, neither app has run on real hardware and both keep their token in
`SharedPreferences` — see each app's README.

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
