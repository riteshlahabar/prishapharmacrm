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
- Roles: `spatie/laravel-permission` 8.5 — roles `admin`, `telecaller`
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
Phase 0 (foundation) ✅ complete. **Phase 1 next:** auth, roles, users.

## Session log
`2026-11-21` Audited all 3 repos; found generic customers/products/orders CRM,
  no pharma domain; Sanctum missing though routes used it; web routes unguarded.
`2026-11-21` Decisions: customers→persons, drop products/orders; spatie
  permissions; MySQL; docs on the code branch.
`2026-11-21` Phase 0: composer install, Sanctum 4.5 + spatie 8.5 installed and
  migrated, MySQL `.env`, `app/Core` base classes, 10 module skeletons,
  `config/modules.php`, `docs/` tree + `check-docs.sh`. Details in
  `docs/history/2026-11.md`.

## Next step
Phase 1: users migration (mobile, role, region_id, is_active) → role +
permission seeders → admin CRUD for MR and telecaller → `auth` middleware on
every web route → permission-scoped sidebar → Sanctum login endpoint.

## Open items
- **ProKit design not attached** (>30 MB, chat cap). Preferred route: push it to
  a private repo (e.g. `prishapharmadesign`) and tell me the name — I attach it
  with `add_repo`. Cuba is already in this repo, nothing needed.
- cPanel MySQL deliberately **not** connected: ephemeral container IP, port
  3306 blocked by the egress proxy, and production data at risk. Dev runs on
  in-container MariaDB; `.env.example` is cPanel-shaped for deploy.
- `AGENTS.md` still holds the Laravel Boost bootstrap stub. Boost was **not**
  installed — `boost:install` would overwrite this file and break the
  100-line session-log requirement. Ask before installing.
- Legacy flat controllers in `app/Http/Controllers` still serve the old
  customers/products/orders routes; they move into modules in Phases 1–2.

## Working rules
- Dev branch: `claude/happy-ramanujan-zthjt1` (all three repos). Never push
  elsewhere without asking. No PR unless asked.
- A commit touching a module updates that module's status table in
  `docs/modules/<name>.md` **and** adds one line to the session log above.
- Each `docs/**` file ≤ 2 KB; split rather than grow.
