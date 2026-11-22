# Conventions

## Modular monolith
- One module per business capability: `app/Modules/<Name>/`.
- Module owns `Http/{Controllers/Web,Controllers/Api,Requests,Resources}`,
  `Services`, `Repositories`, `Models`, `Policies`, `Routes`,
  `Database/Migrations`, `Resources/Views`.
- Register a module via its provider in `config/modules.php`; its
  `bindings()` and `policies()` do the rest.
- Cross-module access goes through the other module's **service contract**,
  never its repository or model.

## SOLID rules
- **SRP** Controller validates + delegates. Service holds business rules.
  Repository holds persistence. No queries in controllers or views.
- **OCP** Extend `App\Core\Repositories\EloquentRepository`; override
  `applyFilters()` instead of editing the base class.
- **LSP/ISP** Small contracts in `Services/Contracts` and
  `Repositories/Contracts`; a consumer depends only on what it calls.
- **DIP** Type-hint contracts; bind concretes in the provider's `bindings()`.

## API
- All mobile endpoints under `/api/v1`, auth via Sanctum bearer token.
- Every response built by `App\Core\Http\ApiResponse`; thrown exceptions go
  through `ApiExceptionRenderer`, so a failure decodes like a success and
  carries a stable `errors.reason`. Never hand-roll a JSON shape.
- Envelope: `{success, message, data}`; errors add `errors{}`; lists `meta{}`.
- Page size from `App\Core\Http\PerPage` (20, max 100).
- Gates, 403 vs 404: `authorization.md`, `api/v1-common.md`.

## Panel (Blade + Cuba)
- Extend `layouts.master`. Assets live in `public/assets` (Cuba, committed).
- Every web route sits behind `auth`; capability via spatie permissions.

## Naming
- Tables plural snake_case; models singular. Enums as PHP enums in the module.
- Times stored UTC; geo as `decimal(10,7)` lat/lng.

## Docs discipline
- Each `docs/**` file ≤ 2 KB, `CLAUDE.md` ≤ 100 lines; enforced by
  `docs/check-docs.sh` (run before committing).
- A commit touching a module updates that module's status table + one line in
  the `CLAUDE.md` session log.
