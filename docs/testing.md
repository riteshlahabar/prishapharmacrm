# Testing        Status: ✅ done (Phase 8)

Two layers. Run both before a commit.

## The gate
```
bash tests/smoke/phase8-suite.sh
```
Pint · `php artisan test` · migrate + seeders + rollback · `check-docs.sh`.
No server and no MySQL needed. `phase6-api.sh` / `phase7-dashboard.sh` are the
other half: they drive a **live** server over HTTP.

## Layout
`tests/Feature/<Module>/` — one file per surface, e.g. `CallLogApiTest` for
`/api/v1/call-logs`, `CallLogPanelTest` for `/calls`.
`ApiContractTest` holds what every list shares (envelope, paging, 403-vs-404),
so a module file asserts only its own rules.

## Tests\TestCase
`RefreshDatabase` on sqlite `:memory:` (phpunit.xml), plus the Access seeders
every test needs — the panel gates on permissions, so without them a 403 means
nothing. Helpers: `admin()`, `mr($region)`, `telecaller([$regions])`,
`grant()`, `revoke()`, `appTokenFor()`, `actingAsApp()`, `asGuestApp()`.

**`actingAsApp()` forgets the guards first.** Laravel keeps one container per
test, so the guard holds the user it resolved for the *previous* request. Hold
a token and re-call `actingAsApp($token)` per request, or every assertion about
an account changing behind a live token silently passes.

## Factories
Module-owned: `app/Modules/<Name>/Database/Factories/`, wired by `#[UseFactory]`.
States say what a row *means* — `openShift()`, `followUpDue()`, `unfinished()`,
`inactive()`. `UserFactory` defaults to **no role** (a pre-Phase-1 row, which
cannot sign in), so a test states the role it wants. `withRole()` sets
`users.role` *and* the spatie role; both are needed.

## Demo data
```
php artisan db:seed --class=Database\\Seeders\\DemoSeeder
```
3 regions, 3 MRs, 2 telecallers, 21 persons, ~48 attendances, ~54 visits,
30 calls, 12 messages. Refuses to run in production, skips itself if its own
rows exist. Password: `Password#123`; logins `mr1@`/`tc1@demo.prishapharma.local`.
