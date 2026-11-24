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
Admin adds clients (doctor/hospital/medical, types editable), regions, MRs
and telecallers. MR app: check-in/out presence + visit entry, both
geo-stamped. Telecaller app: region-scoped client list, call status + notes,
and messages to an MR — in-app, **no Firebase**. Telecallers also get scoped
panel access.

## Current phase
**All phases 0–12 ✅** — backend, panel, both Flutter apps, hardening,
reports, branding. Gate before any commit: `bash tests/smoke/phase8-suite.sh`.

## Session log
Phases 0–6 (foundation through the mobile API v1 contract) are rotated out —
  see `docs/history/2026-11.md` for the index and one file per phase. Admin
  login is `ADMIN_*`; `users:set-admin` resets it. The API contract itself is
  `docs/api/v1-common.md`.
`2026-11-22` Phases 7, 9 and 10 (dashboards, MR app, telecaller app) are
  rotated out — see `docs/history/2026-11-phase{7,9,10}.md`.
`2026-11-22` Phases 8 and 11 (hardening: 284 tests, module factories,
  `DemoSeeder`, three scope fixes; reports: six viewer-clamped reports and
  streamed CSV in a module owning no table) are rotated out — see
  `docs/history/2026-11-phase{8,11}.md`.
`2026-11-22` Fix: the Reports menu was invisible on any database seeded
  before Phase 11 — the new permission rows did not exist, and `RoleSeeder`
  *skipped* an existing non-admin role. It now **tops up** a role with the
  defaults it lacks (adds, never syncs). Re-run `php artisan db:seed` after
  any catalogue change.
`2026-11-23` Phase 12: branding. Prisha artwork replaces the stock Cuba
  logos and favicon; Cuba's purple is repainted teal in `style.css` (402
  values, `ui-theme-patches.md`) and `css/prisha.css` is our layer on top —
  gradient table heads, Bootstrap-5 pagination in a `.table-foot` row,
  `.card-toolbar` / `.filter-bar`, flex buttons (feather's 24px SVG was
  wrapping labels) and six coloured dashboard tiles. **Person is "client" in
  the panel**: URLs and route names are `clients.*`, permissions stay
  `persons.*`. New **ClientTypes** module — doctor/hospital/medical were an
  enum, now editable rows; `persons.type` still stores the slug, so the API
  and both apps are untouched. Both Flutter apps get a splash screen, the
  launcher icon, and a light brand theme (pale gradients, coloured icons,
  branded bottom bar); no SDK or package version moved. 389 tests green.
`2026-11-24` Fix: action-column icons. Bare `.btn-link` glyphs laid out
  inline — Bootstrap underlined each one, the `<form>` buttons sat half a
  line below the `<a>` ones, and `.txt-danger` (`!important`) beat the delete
  colour. Now Cuba's `.product-action` shape: a `.table-actions` flex row of
  34px tiles, `.btn-icon-danger` for delete. Seven views, `ui-actions.md`.

## Next step
Feature work is done. Before a release: neither Flutter app has run on real
hardware (a web build in a headless browser is as far as this container
goes), and both keep their token in `SharedPreferences` — see each README.
The **module** reports (visits, calls, attendance, messages) still show every
MR and region in their filter pickers whatever the viewer covers; rows and
totals are clamped, so it is a name-only leak. Worth aligning one day.

## Open items
- ProKit (Medilab) lives in the MR app repo under `design/`, excluded from
  analysis. Its card shapes and spacing survive in both apps; its palette was
  replaced by Prisha's in Phase 12, and its nb_utils + MobX code never came.
- Cuba needs no upload: its CSS was always complete. Markup in
  `docs/ui-cuba.md`, assets in `ui-assets.md`, our layer in `ui-brand.md`.
- Both apps' icons and splash art are generated from the two source images;
  regenerate by hand if the artwork ever changes.
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
