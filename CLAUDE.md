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
Admin adds clients (doctor/hospital/medical, types editable), products,
regions, MRs and telecallers. MR app: check-in/out presence + visit entry, both
geo-stamped. Telecaller app: region-scoped client list, call status + notes,
and messages to an MR — in-app, **no Firebase**. Telecallers also get scoped
panel access.

## Current phase
**All phases 0–14 ✅** — backend, panel, both apps, hardening, reports,
branding, clients from the apps, products. Gate before a commit:
`bash tests/smoke/phase8-suite.sh`.

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
`2026-11-22` Fix: `RoleSeeder` tops up an existing non-admin role with the
  defaults it lacks — see `history/2026-11-fixes.md`.
`2026-11-23` Phase 12: branding — Prisha logo and favicon, Cuba repainted
  teal, `css/prisha.css` as our layer, "client" in the panel, the editable
  ClientTypes master, and a splash + launcher icon + light theme in both
  apps. Rotated out: `history/2026-11-phase12.md`.
`2026-11-24` Phases 13 and 14 (clients from an app; products) and two UI
  fixes are rotated out — `history/2026-11-phase{13,14}.md`,
  `history/2026-11-fixes.md`.
`2026-11-24` Phase 15: visit photo + a map in the view. One optional
  shopfront photo per visit, taken in the MR app and sent in the **same
  multipart POST** as the visit; it lands on a **private** disk and is
  served only by `GET /visits/{id}/photo`, which resolves through
  `findForUser` so the file inherits the visit's territory scope. The apps
  get `has_photo`, never a URL. The visit view also embeds a keyless
  **OpenStreetMap** frame (`Visit::mapEmbedUrl`) beside the photo — no API
  key, no billing. Contract and the cPanel upload-limit note:
  `docs/visit-photos.md`.
`2026-11-25` Edit Client from an app: `PATCH /api/v1/persons/{id}`, clamped
  on **ownership** (`updateForApp` — anyone else's client is a 404). My
  Clients in the MR app grew an edit button and a map button that hands the
  address to the phone (`url_launcher`). `api/v1-clients{,-lists}.md`.

## Next step
Before a release: neither app has run on real hardware (this container has
no device, emulator or web platform — `analyze` + tests are the ceiling),
and both keep their token in `SharedPreferences` — see each README. The **module** reports
(visits, calls, attendance, messages) still list every MR and region in
their filter pickers whatever the viewer covers; rows and totals are
clamped, so it is a name-only leak. Worth aligning one day.

## Open items
- ProKit (Medilab) lives in the MR app repo under `design/`, excluded from
  analysis. Its card shapes survive in both apps; its palette was replaced
  by Prisha's in Phase 12, and its nb_utils + MobX code never came.
- Cuba needs no upload: its CSS was always complete. Markup in `ui-cuba.md`,
  assets in `ui-assets.md`, our layer in `ui-brand.md`.
- Both apps' icons and splash art are generated from the two source images;
  regenerate by hand if the artwork ever changes.
- Panel admin comes from `ADMIN_EMAIL` / `ADMIN_PASSWORD`; `.env.example`
  holds weak dev defaults so local setup is one command. **Change them
  before anything is internet-reachable**; reset an existing admin with
  `php artisan users:set-admin <email>` (the seeder will not).
- cPanel MySQL deliberately **not** connected: ephemeral container IP, port
  3306 blocked, production data at risk. Dev runs on in-container MariaDB.
- `AGENTS.md` still holds the Laravel Boost stub. Boost was **not**
  installed: `boost:install` would overwrite this file. Ask first.
- A converted `customers` table arrives with every row typed `doctor`:
  re-type hospitals and medical stores by hand after deploy. The product
  catalogue starts empty, so add products before a rep opens a visit form.

## Working rules
- Dev branch: `claude/happy-ramanujan-zthjt1` (all three repos). Never push
  elsewhere without asking. No PR unless asked.
- A commit touching a module updates that module's status table in
  `docs/modules/<name>.md` **and** adds one line to the session log above.
- Each `docs/**` file ≤ 2 KB; split rather than grow.
